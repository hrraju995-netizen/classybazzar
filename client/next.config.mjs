/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    // Next.js 16 blocks private/local IP image optimization by default.
    // Uploaded product images are served by the local Laravel API.
    dangerouslyAllowLocalIP: true,
    remotePatterns: [
      // Seeded demo images (picsum.photos) and any other https host.
      { protocol: 'https', hostname: '**' },
      // Product images uploaded through the admin API are served from
      // the Laravel app's public storage disk over plain http locally.
      { protocol: 'http', hostname: '127.0.0.1', port: '8000' },
      { protocol: 'http', hostname: 'localhost', port: '8000' },
    ],
  },
  reactCompiler: true,
};

export default nextConfig;
