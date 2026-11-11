import { revalidateTag } from "next/cache";

/**
 * On-demand cache invalidation, called by the Laravel admin panel whenever a
 * product or category is saved.
 *
 * Without this the storefront serves cached data for up to a minute, so a
 * shop owner who adds a product and immediately checks the site concludes
 * nothing saved.
 */
export async function POST(request) {
  const secret = process.env.REVALIDATE_SECRET;

  // No secret configured means anyone could flush the cache at will, so
  // refuse rather than run wide open.
  if (!secret) {
    return Response.json(
      { revalidated: false, message: "REVALIDATE_SECRET is not set." },
      { status: 500 }
    );
  }

  if (request.headers.get("x-revalidate-secret") !== secret) {
    return Response.json(
      { revalidated: false, message: "Invalid secret." },
      { status: 401 }
    );
  }

  let tags = ["products", "categories"];

  try {
    const body = await request.json();
    if (Array.isArray(body?.tags) && body.tags.length > 0) {
      tags = body.tags;
    }
  } catch {
    // No body sent — fall back to flushing everything.
  }

  tags.forEach((tag) => revalidateTag(tag, 'max'));

  return Response.json({ revalidated: true, tags, now: Date.now() });
}
