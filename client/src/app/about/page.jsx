import AboutDetailsSection from "@/components/AboutDetailsSection";
import OurSupport from "@/components/OurSupport";
import SubBanner from "@/components/SubBanner";

export const metadata = {
  title: "About || ClassyBazar",
  description: "classyBazar about page",
  icons: {
    icon: "/favAbout.svg",
  },
};
const About = () => {
  return (
    <section>
      <div>
        <SubBanner title={"About Us"} pageName={"About"} />
        <div className="container">
          <div>
            <AboutDetailsSection />
          </div>
          <div>
            <OurSupport />
          </div>
        </div>
      </div>
    </section>
  )
}

export default About
