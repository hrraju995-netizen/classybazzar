import { Poppins, Inter } from "next/font/google";
import "./globals.css";
import Topbar from "@/components/Topbar";
import Navbar from "@/components/Navbar";
import Footer from "@/components/Footer";
import { ToastContainer } from "react-toastify";
import 'react-toastify/dist/ReactToastify.css';
import MenuBar from "@/components/MenuBar";
import MultiChatFAB from "@/components/MultiChatFAB";
import ThemeStyles from "@/components/site/ThemeStyles";
import TrackingScripts from "@/components/site/TrackingScripts";
import { getSettingsSafe } from "@/lib/api/misc";
import CartDrawer from "@/components/CartDrawer";

const poppins = Poppins({
  variable: "--font-poppins",
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
});

const inter = Inter({
  variable: "--font-inter",
  subsets: ["latin"],
  weight: ["400", "500", "600", "700"],
});

/** Title, description, keywords and favicon all come from the admin panel. */
export async function generateMetadata() {
  const settings = await getSettingsSafe();
  const siteName = settings.site?.name || "Classybazar";
  const defaultTitle = settings.site?.metaTitle || siteName;
  const description = settings.site?.metaDescription || settings.site?.tagline || undefined;
  const keywords = settings.site?.metaKeywords || undefined;

  return {
    title: {
      default: defaultTitle,
      template: `%s | ${siteName}`,
    },
    description,
    keywords,
    icons: settings.site?.favicon ? { icon: settings.site.favicon } : undefined,
  };
}

export default async function RootLayout({ children }) {
  const settings = await getSettingsSafe();
  return (
    <html 
      suppressHydrationWarning
      data-theme="light"
      lang="en"
      className={`${poppins.variable} ${inter.variable} h-full antialiased`}
    >
      <head>
        <ThemeStyles theme={settings.theme} />
      </head>
      <body suppressHydrationWarning className="min-h-full flex flex-col">
        <TrackingScripts tracking={settings.tracking} />
        <Topbar settings={settings} />
        <Navbar settings={settings} />
        <MenuBar settings={settings} />
        <CartDrawer />
        {children}
        <MultiChatFAB settings={settings} />
        <Footer settings={settings} />
        <ToastContainer 
          position="top-center" 
          autoClose={3000} 
          style={{ 
            width: "max-content", 
            maxWidth: "75vw",
            left: "50%", 
            transform: "translateX(-50%)", 
            marginTop: "10px", 
            display: "flex",
            justifyContent: "center"
          }} 
        />
      </body>
    </html>
  );
}
