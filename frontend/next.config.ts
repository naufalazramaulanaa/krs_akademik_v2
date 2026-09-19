// import type { NextConfig } from "next";

// const nextConfig: NextConfig = {
//   async rewrites() {
//     const backendUrl = process.env.NEXT_PUBLIC_API_BACKEND_URL || 'http://127.0.0.1:8000';
//     return [
//       {
//         source: '/api/:path*',
//         destination: `${backendUrl}/api/:path*`,
//       },
//     ];
//   },
// };

// export default nextConfig;

import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  typescript: {
    // Mengabaikan error TypeScript saat build
    ignoreBuildErrors: true,
  },
  async rewrites() {
    const backendUrl = process.env.NEXT_PUBLIC_API_BACKEND_URL || 'http://127.0.0.1:8000';
    return [
      {
        source: '/api/:path*',
        destination: `${backendUrl}/api/:path*`,
      },
    ];
  },
};

export default nextConfig;