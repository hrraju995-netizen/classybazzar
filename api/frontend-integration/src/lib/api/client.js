const BASE_URL =
  process.env.NEXT_PUBLIC_API_URL || "http://127.0.0.1:8000/api";

const TOKEN_KEY = "classybazar-token";

export function getToken() {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
  if (typeof window === "undefined") return;
  if (token) window.localStorage.setItem(TOKEN_KEY, token);
  else window.localStorage.removeItem(TOKEN_KEY);
}

export class ApiError extends Error {
  constructor(message, status, errors) {
    super(message);
    this.name = "ApiError";
    this.status = status;
    // Laravel validation errors: { field: ["message", ...] }
    this.errors = errors || {};
  }

  /** First message for a given field, for inline form errors. */
  fieldError(field) {
    return this.errors?.[field]?.[0] ?? null;
  }
}

/**
 * Thin fetch wrapper.
 *
 * `next` is passed straight through so server components can still use
 * revalidation: apiFetch("/products", { next: { revalidate: 60 } }).
 */
export async function apiFetch(path, options = {}) {
  const { body, headers, auth = false, ...rest } = options;

  const finalHeaders = {
    Accept: "application/json",
    ...(body ? { "Content-Type": "application/json" } : {}),
    ...headers,
  };

  if (auth) {
    const token = getToken();
    if (token) finalHeaders.Authorization = `Bearer ${token}`;
  }

  const res = await fetch(`${BASE_URL}${path}`, {
    ...rest,
    headers: finalHeaders,
    body: body ? JSON.stringify(body) : undefined,
  });

  if (res.status === 204) return null;

  let data = null;
  try {
    data = await res.json();
  } catch {
    // Non-JSON response (502, HTML error page, etc.)
  }

  if (!res.ok) {
    throw new ApiError(
      data?.message || `Request failed with status ${res.status}`,
      res.status,
      data?.errors
    );
  }

  return data;
}

export function buildQuery(params = {}) {
  const search = new URLSearchParams();

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === "") return;
    search.append(key, String(value));
  });

  const query = search.toString();
  return query ? `?${query}` : "";
}
