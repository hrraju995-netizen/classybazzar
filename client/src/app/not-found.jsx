import Link from "next/link";

export default function NotFound() {
  return (
    <div className="w-full flex items-center justify-center py-30 px-6 bg-white">
      <div className="max-w-xl w-full text-center">
        <div className="relative flex items-center justify-center mb-6">
          <span className="text-[110px] sm:text-[140px] font-extrabold text-gray-100 leading-none select-none">
            404
          </span>
          <span className="absolute text-3xl sm:text-4xl font-extrabold text-[var(--color-primary)] tracking-tight">
            NOT FOUND
          </span>
        </div>
        <h1 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">
          Your visited page not found. You may go home page.
        </h1>
        <Link
          href="/"
          className="inline-block px-6 py-3 rounded-lg text-sm font-semibold mt-5 text-white bg-gray-900 hover:bg-gray-800 transition"
        >
          Back to home
        </Link>
      </div>
    </div>
  );
}