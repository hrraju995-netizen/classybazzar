<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function store(ContactRequest $request): JsonResponse
    {
        ContactMessage::create($request->validated());

        return response()->json([
            'message' => 'Thanks for reaching out. We will get back to you shortly.',
        ], 201);
    }
}
