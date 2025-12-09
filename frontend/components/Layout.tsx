import { ReactNode, useState, useEffect, useRef } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/router';
import { useAuth } from '@/contexts/AuthContext';

interface LayoutProps {
  children: ReactNode;
}

export default function Layout({ children }: LayoutProps) {
  const router = useRouter();
  const { locale, pathname, asPath } = router;
  const { user, logout, loading } = useAuth();
  const [showUserMenu, setShowUserMenu] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  // Close menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setShowUserMenu(false);
      }
    };

    if (showUserMenu) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [showUserMenu]);

  const switchLocale = (newLocale: string) => {
    router.push(pathname, asPath, { locale: newLocale });
  };

  return (
    <div className="min-h-screen flex flex-col">
      {/* Header */}
      <header className="bg-white shadow-md">
        <div className="container mx-auto px-4">
          <div className="flex items-center justify-between h-16">
            {/* Logo */}
            <Link href="/" className="flex items-center space-x-2">
              <span className="text-2xl font-bold text-blue-600">AIJobPortal</span>
            </Link>

            {/* Navigation */}
            <nav className="hidden md:flex items-center space-x-6">
              <Link
                href="/"
                className={`text-gray-700 hover:text-blue-600 transition ${
                  pathname === '/' ? 'text-blue-600 font-semibold' : ''
                }`}
              >
                Home
              </Link>
              <Link
                href="/jobs"
                className={`text-gray-700 hover:text-blue-600 transition ${
                  pathname.startsWith('/jobs') ? 'text-blue-600 font-semibold' : ''
                }`}
              >
                Jobs
              </Link>
              
              {!loading && (
                <>
                  {user ? (
                    <div className="relative" ref={menuRef}>
                      <button
                        onClick={() => setShowUserMenu(!showUserMenu)}
                        className="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition"
                      >
                        <span>{user.name}</span>
                        <svg
                          className="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M19 9l-7 7-7-7"
                          />
                        </svg>
                      </button>
                      {showUserMenu && (
                        <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 border border-gray-200">
                          <div className="px-4 py-2 border-b border-gray-200">
                            <p className="text-sm font-medium text-gray-900">{user.name}</p>
                            <p className="text-xs text-gray-500">{user.email}</p>
                          </div>
                          <Link
                            href="/profile"
                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            onClick={() => setShowUserMenu(false)}
                          >
                            Profile
                          </Link>
                          <Link
                            href="/applications"
                            className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            onClick={() => setShowUserMenu(false)}
                          >
                            My Applications
                          </Link>
                          {user.roles && user.roles.includes('Admin') && (
                            <Link
                              href="/admin"
                              className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                              onClick={() => setShowUserMenu(false)}
                            >
                              Admin Panel
                            </Link>
                          )}
                          <button
                            onClick={() => {
                              setShowUserMenu(false);
                              logout();
                            }}
                            className="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                          >
                            Logout
                          </button>
                        </div>
                      )}
                    </div>
                  ) : (
                    <>
                      <Link
                        href="/auth/login"
                        className="text-gray-700 hover:text-blue-600 transition"
                      >
                        Login
                      </Link>
                      <Link
                        href="/auth/register"
                        className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
                      >
                        Sign Up
                      </Link>
                    </>
                  )}
                </>
              )}
            </nav>

            {/* Language Switcher */}
            <div className="flex items-center space-x-2">
              <button
                onClick={() => switchLocale('en')}
                className={`px-3 py-1 rounded ${
                  locale === 'en'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                } transition`}
              >
                EN
              </button>
              <button
                onClick={() => switchLocale('ar')}
                className={`px-3 py-1 rounded ${
                  locale === 'ar'
                    ? 'bg-blue-600 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                } transition`}
              >
                AR
              </button>
            </div>

            {/* Mobile Menu Button */}
            <button className="md:hidden text-gray-700 hover:text-blue-600">
              <svg
                className="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  strokeLinecap="round"
                  strokeLinejoin="round"
                  strokeWidth={2}
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <main className="flex-grow">{children}</main>

      {/* Footer */}
      <footer className="bg-gray-800 text-white py-12">
        <div className="container mx-auto px-4">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div>
              <h3 className="text-xl font-bold mb-4">AIJobPortal</h3>
              <p className="text-gray-400">
                Find your dream job with AI-powered matching and personalized recommendations.
              </p>
            </div>
            <div>
              <h4 className="font-semibold mb-4">For Job Seekers</h4>
              <ul className="space-y-2 text-gray-400">
                <li>
                  <Link href="/jobs" className="hover:text-white transition">
                    Browse Jobs
                  </Link>
                </li>
                <li>
                  <Link href="/auth/register" className="hover:text-white transition">
                    Create Account
                  </Link>
                </li>
                <li>
                  <a href="#" className="hover:text-white transition">
                    Career Resources
                  </a>
                </li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">For Employers</h4>
              <ul className="space-y-2 text-gray-400">
                <li>
                  <a href="#" className="hover:text-white transition">
                    Post a Job
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-white transition">
                    Pricing
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-white transition">
                    Employer Resources
                  </a>
                </li>
              </ul>
            </div>
            <div>
              <h4 className="font-semibold mb-4">Company</h4>
              <ul className="space-y-2 text-gray-400">
                <li>
                  <a href="#" className="hover:text-white transition">
                    About Us
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-white transition">
                    Contact
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-white transition">
                    Privacy Policy
                  </a>
                </li>
                <li>
                  <a href="#" className="hover:text-white transition">
                    Terms of Service
                  </a>
                </li>
              </ul>
            </div>
          </div>
          <div className="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
            <p>&copy; {new Date().getFullYear()} AIJobPortal. All rights reserved.</p>
          </div>
        </div>
      </footer>
    </div>
  );
}

