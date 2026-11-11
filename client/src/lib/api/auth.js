import { apiFetch, getToken, setToken } from "./client";

const USER_KEY = "classybazar-user";

export function getStoredUser() {
  if (typeof window === "undefined") return null;
  try {
    const raw = window.localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

export function setStoredUser(user) {
  if (typeof window === "undefined") return;
  try {
    if (user) {
      window.localStorage.setItem(USER_KEY, JSON.stringify(user));
    } else {
      window.localStorage.removeItem(USER_KEY);
    }
    window.dispatchEvent(new Event("classybazar-auth-change"));
  } catch {
    // Ignore storage errors
  }
}

export function isAuthenticated() {
  return Boolean(getToken());
}

export async function register(payload) {
  const data = await apiFetch("/auth/register", {
    method: "POST",
    body: payload,
  });
  setToken(data.token);
  setStoredUser(data.user);
  return data.user;
}

/** `identifier` accepts either an email address or a phone number. */
export async function login(identifier, password) {
  const data = await apiFetch("/auth/login", {
    method: "POST",
    body: { email: identifier, password },
  });
  setToken(data.token);
  setStoredUser(data.user);
  return data.user;
}

/** Sign in or register using a Bangladeshi mobile phone number. */
export async function phoneLogin({ phone, name, password } = {}) {
  const data = await apiFetch("/auth/phone", {
    method: "POST",
    body: { phone, name, password },
  });
  setToken(data.token);
  setStoredUser(data.user);
  return data.user;
}

/** Sign in or register using a Google account. */
export async function googleLogin({ email, name, google_id, avatar, credential } = {}) {
  const data = await apiFetch("/auth/google", {
    method: "POST",
    body: { email, name, google_id, avatar, credential },
  });
  setToken(data.token);
  setStoredUser(data.user);
  return data.user;
}

export async function logout() {
  try {
    await apiFetch("/auth/logout", { method: "POST", auth: true });
  } catch {
    // Ignore network/token errors on logout
  } finally {
    setToken(null);
    setStoredUser(null);
  }
}

export async function me() {
  const data = await apiFetch("/auth/me", { auth: true, cache: "no-store" });
  setStoredUser(data.user);
  return data.user;
}

export async function updateProfile(payload) {
  const data = await apiFetch("/auth/profile", {
    method: "PATCH",
    body: payload,
    auth: true,
  });
  setStoredUser(data.user);
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
