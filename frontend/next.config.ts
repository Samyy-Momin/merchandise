import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  output: "standalone",

  images: {
    remotePatterns: [
      { protocol: "https", hostname: "devsso.cubeone.in" },
      { protocol: "http", hostname: "localhost", port: "8000" },
      { protocol: "http", hostname: "kong", port: "8000" },
      { protocol: "https", hostname: "utfs.io" },
    ],
  },

  async headers() {
    return [
      {
        source: "/silent-check-sso.html",
        headers: [
          { key: "Content-Type", value: "text/html; charset=utf-8" },
        ],
      },
    ];
  },

  async rewrites() {
    return [
      {
        source: "/silent-check-sso",
        destination: "/silent-check-sso.html",
      },
    ];
  },
};

export default nextConfig;
