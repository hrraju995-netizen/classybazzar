import { apiFetch, setToken } from "./client";

export async function register(payload) {
  const data = await apiFetch("/auth/register", {
    method: "POST",
    body: payload,
  });
  setToken(data.token);
  return data.user;
}

/** `identifier` accepts either an email address or a phone number. */
export async function login(identifier, password) {
  const data = await apiFetch("/auth/login", {
    method: "POST",
    body: { email: identifier, password },
  });
  setToken(data.token);
  return data.user;
}

export async function logout() {
  try {
    await apiFetch("/auth/logout", { method: "POST", auth: true });
  } finally {
    // Clear locally even if the request fails, so the user isn't stuck
    // "logged in" with a token the server has already rejected.
    setToken(null);
  }
}

export async function me() {
  const data = await apiFetch("/auth/me", { auth: true, cache: "no-store" });
  return data.user;
}

export async function updateProfile(payload) {
  const data = await apiFetch("/auth/profile", {
    method: "PATCH",
    body: payload,
    auth: true,
  });
  return data.user;
}

export function changePassword(currentPassword, password, passwordConfirmation) {
  return apiFetch("/auth/change-password", {
    method: "POST",
    auth: true,
    body: {
      current_password: currentPassword,
      password,
      password_confirmation: passwordConfirmation,
    },
  });
}
