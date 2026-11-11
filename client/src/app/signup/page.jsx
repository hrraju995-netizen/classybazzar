import Image from 'next/image';
import main_logo from '../../../public/main-logo.png';
import AuthForm from '@/components/AuthForm';

export const metadata = {
  title: 'Signup || classyBazar',
  description: 'classybazar signup page',
  icons: {
    icon: '/favSignup.svg',
  },
};

const SignUp = () => {
  return (
    <section className="px-5">
      <div className="container mx-auto">
        <div className="flex flex-col lg:flex-row items-center justify-center gap-10 lg:gap-[50px] py-10">
          <div className="w-full lg:w-[950px] flex justify-center">
            <Image
              src={main_logo}
              height={781}
              width={950}
              alt="logo"
              className="w-full max-w-[300px] md:max-w-[500px] lg:max-w-[950px] h-auto object-contain"
            />
          </div>
          <AuthForm mode="signup" />
        </div>
      </div>
    </section>
  );
};

export default SignUp;
