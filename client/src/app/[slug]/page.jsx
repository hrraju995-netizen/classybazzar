import { notFound } from "next/navigation";
import { getPage } from "@/lib/api/misc";
import SubBanner from "@/components/SubBanner";

export async function generateMetadata({ params }) {
  const { slug } = await params;

  try {
    const page = await getPage(slug);
    return {
      title: page.metaTitle,
      description: page.metaDescription ?? undefined,
    };
  } catch {
    return { title: "Page not found" };
  }
}

/**
 * Renders any CMS page created in the admin panel at /<slug>.
 *
 * This sits below every real route in the app directory, so /products and
 * /cart still win — only unmatched paths reach here.
 */
const CmsPage = async ({ params }) => {
  const { slug } = await params;

  let page;
  try {
    page = await getPage(slug);
  } catch {
    notFound();
  }

  return (
    <>
      <SubBanner title={page.title} pageName={page.title} />
      <section className="container mx-auto px-4 md:px-0 py-10 md:py-16">
        <article
          className="prose prose-lg max-w-none prose-headings:font-semibold prose-a:text-[var(--color-primary)]"
          dangerouslySetInnerHTML={{ __html: page.content ?? "" }}
        />
      </section>
    </>
  );
};

export default CmsPage;
