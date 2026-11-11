import ContactFormSection from "@/components/ContacFormSection";
import SubBanner from "@/components/SubBanner";
import { getSettingsSafe } from "@/lib/api/misc";

export const metadata = {
  title: "Contact",
  description: "Contact Classybazar customer support",
};

const Contact = async () => {
  const settings = await getSettingsSafe();

  return (
    <section>
      <div>
        <SubBanner title={"Contact"} pageName={"Contact"} />
      </div>
      <div className="container">
        <div>
          <ContactFormSection contact={settings?.contact} />
        </div>
      </div>
    </section>
  );
};

export default Contact;
