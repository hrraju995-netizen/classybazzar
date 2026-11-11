import Image from 'next/image';
import main_logo from '../../../public/main-logo.png';
import AuthForm from '@/components/AuthForm';

export const metadata = {
  title: 'Login || classyBazar',
  description: 'classybazar Login page',
  icons: {
    icon: '/favLogin.svg',
  },
};

const Login = () => {
  return (
    <section className="px-4 py-8 md:py-16">
      <div className="container mx-auto">
        <div className="flex flex-col lg:flex-row items-center justify-center gap-8 lg:gap-[50px]">
          <div className="w-full lg:w-[950px] flex justify-center">
            <Image
              src={main_logo}
              height={781}
              width={950}
              alt="logo"
              className="w-full max-w-[300px] md:max-w-[500px] lg:max-w-[950px] h-auto object-contain"
            />
          </div>
          <AuthForm mode="login" />
        </div>
      </div>
    </section>
  );
};

export default Login;
