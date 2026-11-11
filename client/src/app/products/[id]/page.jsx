import ProductDetailsSection from "@/components/ProductDetailsSection";
import SectionHeading from "@/components/SectionHeading";
import SubBanner from "@/components/SubBanner";
import RelatedProductsSlider from "@/components/RelatedProductsSlider"; 
import { getProduct, getRelatedProducts } from '@/lib/api/products';
import { redirect } from 'next/navigation';

const ProductDetailsPage = async ({ params }) => {
  const { id: productKey } = await params;
  let findData = null;
  try {
    findData = await getProduct(productKey);
  } catch {
    findData = null;
  }

  if (!findData) {
    return <div className="text-center py-20 font-bold">Product Not Found!</div>;
  }

  if (productKey !== findData.slug) {
    redirect(`/products/${findData.slug}`);
  }

  const { products: relatedProducts } = await getRelatedProducts(findData.slug, 10);

  return (
    <section>
      <div>
         <SubBanner title={"Product Details"} pageName={"Product Details"}/>
      </div>
      <div className="container mx-auto px-4 md:px-6 lg:px-8">
        <div className="pt-6">
          <p className="hidden md:block text-black font-semibold mb-6">
            <span className="font-bold text-black">Product Name:</span> {findData.title}
          </p>
          
          <div className="mb-10">
            <ProductDetailsSection product={findData} />
          </div>
          {relatedProducts.length > 0 && (
            <div className="pb-16 w-full overflow-hidden">
              <div className="mb-8">
                <SectionHeading subHeading={"Related Item"} countDown={false}/>
              </div>
              <RelatedProductsSlider relatedProducts={relatedProducts} />
            </div>
          )}
        </div>
      </div>
    </section>
  );
};

export default ProductDetailsPage;
