/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    // Hostinger shared hosting uses PHP curl to proxy Next.js socket responses.
    // The /_next/image optimizer endpoint returns binary data that the PHP proxy
    // cannot handle correctly, causing broken images. Using unoptimized=true
    // bypasses the optimizer so <Image> tags use the original URL directly.
    unoptimized: true,
    dangerouslyAllowLocalIP: true,
    remotePatterns: [
      { protocol: 'https', hostname: '**' },
      { protocol: 'http', hostname: '127.0.0.1', port: '8000' },
      { protocol: 'http', hostname: 'localhost', port: '8000' },
    ],
  },
  reactCompiler: true,
};

export default nextConfig;
