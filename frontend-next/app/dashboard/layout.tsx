'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth, AuthProvider } from '@/lib/auth-context';
import { Sidebar } from '@/components/layout/sidebar/Sidebar';
import { Header } from '@/components/layout/header/Header';

function DashboardContent({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { user, loading, logout, isAuthenticated } = useAuth();

  useEffect(() => {
    if (!loading && !isAuthenticated) {
      router.push('/login');
    }
  }, [loading, isAuthenticated, router]);

  if (loading) return <div className="min-h-screen flex items-center justify-center text-foreground">Cargando...</div>;
  if (!user) return null;

  return (
    <div className="flex min-h-screen bg-lightgray">
      <a
        href="#main-content"
        className="sr-only focus:not-sr-only focus:absolute focus:z-[100] focus:top-4 focus:left-4 focus:rounded-lg focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-primary-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
      >
        Saltar al contenido principal
      </a>

      <aside className="fixed left-0 top-0 z-40 h-screen w-[270px] hidden xl:block">
        <Sidebar />
      </aside>

      <div className="flex flex-col flex-1 w-full min-h-screen xl:pl-[270px]">
        <Header user={user} onLogout={logout} />
        <main id="main-content" tabIndex={-1} className="flex-1 w-full px-4 sm:px-6 py-4 sm:py-6 max-w-[1600px] mx-auto">
          {children}
        </main>
      </div>
    </div>
  );
}

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
  return (
    <AuthProvider>
      <DashboardContent>{children}</DashboardContent>
    </AuthProvider>
  );
}
