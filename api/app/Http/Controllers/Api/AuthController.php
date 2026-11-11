<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['auth_provider'] = filled($validated['email'] ?? null) ? 'email' : 'phone';

        $user = User::create($validated);
        $user->claimMatchingGuestOrders();

        return response()->json([
            'user' => $this->profile($user->fresh()),
            'token' => $user->createToken('classybazar')->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $rawIdentifier = trim($request->string('email')->toString());
        $isEmail = filter_var($rawIdentifier, FILTER_VALIDATE_EMAIL) !== false;
        $identifier = $isEmail ? Str::lower($rawIdentifier) : $this->normalizePhone($rawIdentifier);

        $throttleKey = $identifier.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 8)) {
            throw ValidationException::withMessages([
                'email' => 'Too many login attempts. Try again in '
                    .RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        $field = $isEmail ? 'email' : 'phone';
        $user = User::query()->where($field, $identifier)->first();

        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Invalid email/phone number or password.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        $user->claimMatchingGuestOrders();

        return response()->json([
            'user' => $this->profile($user->fresh()),
            'token' => $user->createToken('classybazar')->plainTextToken,
        ]);
    }

    /**
     * POST /api/auth/phone — Sign in or register with a Bangladeshi phone number.
     */
    public function phoneLogin(Request $request): JsonResponse
    {
        $rawPhone = $this->normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $rawPhone]);

        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'name' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'min:6'],
        ], [
            'phone.regex' => 'Enter a valid 11-digit Bangladeshi mobile number (e.g. 01712345678).',
        ]);

        $user = User::query()->where('phone', $data['phone'])->first();

        if ($user) {
            // If the account is an admin account, always require password
            if ($user->is_admin) {
                if (empty($data['password']) || ! Hash::check($data['password'], $user->password)) {
                    throw ValidationException::withMessages([
                        'phone' => 'Please sign in with your admin password.',
                    ]);
                }
            } elseif (! empty($data['password']) && ! Hash::check($data['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Incorrect password for this phone number.',
                ]);
            }

            if (filled($data['name'] ?? null) && str_starts_with($user->name, 'Customer ')) {
                $user->update(['name' => trim($data['name'])]);
            }
        } else {
            $name = filled($data['name'] ?? null)
                ? trim((string) $data['name'])
                : 'Customer '.substr($data['phone'], -4);

            $user = User::create([
                'name' => $name,
                'phone' => $data['phone'],
                'email' => null,
                'password' => filled($data['password'] ?? null) ? $data['password'] : Str::random(16),
                'auth_provider' => 'phone',
            ]);
        }

        $user->claimMatchingGuestOrders();

        return response()->json([
            'user' => $this->profile($user->fresh()),
            'token' => $user->createToken('classybazar')->plainTextToken,
        ]);
    }

    /**
     * POST /api/auth/google — Sign in or register with Google account.
     */
    public function googleLogin(Request $request): JsonResponse
    {
        $googleEmail = null;
        $googleName = null;
        $googleId = null;
        $avatar = null;

        // 1. If a real Google ID token (credential) was supplied, verify it via Google's tokeninfo endpoint
        if ($request->filled('credential')) {
            try {
                $res = Http::timeout(6)->get('https://oauth2.googleapis.com/tokeninfo', [
                    'id_token' => $request->string('credential')->toString(),
                ]);

                if ($res->successful()) {
                    $payload = $res->json();
                    $googleEmail = $payload['email'] ?? null;
                    $googleName = $payload['name'] ?? null;
                    $googleId = $payload['sub'] ?? null;
                    $avatar = $payload['picture'] ?? null;
                }
            } catch (\Throwable) {
                // Fall back to direct profile fields if offline
            }
        }

        // 2. Accept direct Google profile payload (for local/popup Google sign-in flow)
        if (! $googleEmail) {
            $validated = $request->validate([
                'email' => ['required', 'email', 'max:180'],
                'name' => ['nullable', 'string', 'max:120'],
                'google_id' => ['nullable', 'string', 'max:180'],
                'avatar' => ['nullable', 'string', 'max:500'],
            ]);

            $googleEmail = Str::lower(trim($validated['email']));
            $googleName = filled($validated['name'] ?? null)
                ? trim($validated['name'])
                : Str::headline( explode('@', $googleEmail)[0] );
            $googleId = $validated['google_id'] ?? ('google_'.md5($googleEmail));
            $avatar = $validated['avatar'] ?? null;
        }

        $user = User::query()
            ->where('google_id', $googleId)
            ->orWhereRaw('LOWER(email) = ?', [Str::lower($googleEmail)])
            ->first();

        if ($user) {
            $updates = [];
            if (blank($user->google_id) && $googleId) {
                $updates['google_id'] = $googleId;
            }
            if (blank($user->avatar) && $avatar) {
                $updates['avatar'] = $avatar;
            }
            if ($user->auth_provider === 'email' && $googleId) {
                $updates['auth_provider'] = 'google';
            }
            if ($updates !== []) {
                $user->update($updates);
            }
        } else {
            $user = User::create([
                'name' => $googleName ?: 'Google User',
                'email' => $googleEmail,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'email_verified_at' => now(),
                'password' => Str::random(24),
                'auth_provider' => 'google',
            ]);
        }

        $user->claimMatchingGuestOrders();

        return response()->json([
            'user' => $this->profile($user->fresh()),
            'token' => $user->createToken('classybazar')->plainTextToken,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->claimMatchingGuestOrders();

        return response()->json(['user' => $this->profile($user->fresh())]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->has('phone') && filled($request->input('phone'))) {
            $request->merge([
                'phone' => $this->normalizePhone((string) $request->input('phone')),
            ]);
        } elseif ($request->has('phone') && blank($request->input('phone'))) {
            $request->merge(['phone' => null]);
        }

        if ($request->has('email') && blank($request->input('email'))) {
            $request->merge(['email' => null]);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:180', 'unique:users,email,'.$user->id],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^01[3-9]\d{8}$/', 'unique:users,phone,'.$user->id],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
        ], [
            'phone.regex' => 'Enter a valid 11-digit Bangladeshi mobile number (e.g. 01712345678).',
        ]);

        $user->update($data);
        $user->claimMatchingGuestOrders();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->profile($user->fresh()),
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['nullable', 'string'],
            'password' => ['required', 'confirmed', 'min:6'],
        ]);

        $user = $request->user();

        // Only require current_password if the user originally signed up with email/password
        if ($user->auth_provider === 'email' && filled($data['current_password'] ?? null)) {
            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Your current password is incorrect.',
                ]);
            }
        } elseif ($user->auth_provider === 'email' && blank($data['current_password'] ?? null)) {
            throw ValidationException::withMessages([
                'current_password' => 'Please enter your current password.',
            ]);
        }

        $user->update(['password' => $data['password']]);

        // Force every other device to sign in again after a password change.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password updated successfully.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    protected function normalizePhone(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value);

        return preg_replace('/^88(?=01)/', '', (string) $digits);
    }

    protected function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'avatar' => $user->avatar,
            'authProvider' => $user->auth_provider ?? 'email',
            'isAdmin' => (bool) $user->is_admin,
            'ordersCount' => $user->orders()->count(),
            'joinedAt' => $user->created_at?->toIso8601String(),
        ];
    }
}
