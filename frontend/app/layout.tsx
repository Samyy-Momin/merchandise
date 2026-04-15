import type { Metadata } from "next";
import { ThemeProvider } from "next-themes";
import { QueryProvider } from "@/lib/providers/query-provider";
import { Toaster } from "sonner";
import { Inter } from "next/font/google";
const inter = Inter({ subsets: ["latin"], variable: "--font-geist-sans", display: "swap" });
import "./globals.css";

export const metadata: Metadata = {
  title: "Merchandise Procurement Portal",
  description: "OneFoodDialer Merchandise Procurement & Management System",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="en"
      className={inter.variable}
      suppressHydrationWarning
    >
      <body>
        <ThemeProvider attribute="class" defaultTheme="system" enableSystem>
          <QueryProvider>
            {children}
            <Toaster richColors position="top-right" />
          </QueryProvider>
        </ThemeProvider>
      </body>
    </html>
  );
}
