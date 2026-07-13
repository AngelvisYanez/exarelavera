import type { Metadata } from "next";
import { Orbitron, Space_Grotesk, Share_Tech_Mono } from "next/font/google";
import "./css/globals.css";
import { ThemeProvider } from "@/components/theme-provider";
import { AuthProvider } from "@/lib/auth-context";
import { Toaster } from "sonner";

const orbitron = Orbitron({
  variable: "--font-orbitron",
  subsets: ["latin"],
});

const spaceGrotesk = Space_Grotesk({
  variable: "--font-space-grotesk",
  subsets: ["latin"],
});

const shareTechMono = Share_Tech_Mono({
  variable: "--font-share-tech-mono",
  weight: "400",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "EXA Relavera - Sistema de Gestión y Trazabilidad",
  description: "Sistema de Gestión y Trazabilidad para Relavera",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html
      lang="es"
      className={`${orbitron.variable} ${spaceGrotesk.variable} ${shareTechMono.variable} h-full antialiased`}
      suppressHydrationWarning
    >
      <body className="min-h-full flex flex-col">
        <ThemeProvider attribute="class" defaultTheme="light" disableTransitionOnChange>
          <AuthProvider>
            {children}
          </AuthProvider>
          <Toaster richColors position="top-right" duration={3000} />
        </ThemeProvider>
      </body>
    </html>
  );
}
