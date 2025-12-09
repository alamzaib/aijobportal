import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useRouter } from 'next/router';
import { apiClient } from '@/lib/api';

interface User {
  id: string;
  name: string;
  email: string;
  roles?: string[];
  is_blocked?: boolean;
}

interface AuthContextType {
  user: User | null;
  loading: boolean;
  login: (user: User) => void;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);
  const [hasCheckedAuth, setHasCheckedAuth] = useState(false);
  const [isLoggingOut, setIsLoggingOut] = useState(false);
  const router = useRouter();

  const checkAuth = async () => {
    // Don't check auth if we're logging out
    if (isLoggingOut) {
      return;
    }

    setLoading(true);
    try {
      const userData = await apiClient.get<User>('/auth/user');
      setUser(userData);
      setHasCheckedAuth(true);
    } catch (error: any) {
      // Always clear user state on 401 (unauthorized) - session is invalid
      if (error?.response?.status === 401) {
        setUser(null);
        setHasCheckedAuth(true);
      } else {
        // For other errors, don't clear user state immediately
        // This prevents logout on network errors
        console.error('Auth check error:', error);
      }
    } finally {
      setLoading(false);
    }
  };

  const login = (userData: User) => {
    setUser(userData);
    setLoading(false);
    setHasCheckedAuth(true);
  };

  const logout = async () => {
    setIsLoggingOut(true);
    try {
      // Clear user state immediately to prevent UI flicker
      setUser(null);
      setHasCheckedAuth(true); // Mark as checked so we don't re-check auth
      
      // Call logout API - this should clear the session cookie
      await apiClient.post('/auth/logout');
      
      // Force clear any cookies that might still exist (for non-HttpOnly cookies)
      // Note: HttpOnly cookies can only be cleared by the server, but we try anyway
      if (typeof document !== 'undefined') {
        // Get all cookies and try to clear them
        const allCookies = document.cookie.split(';');
        allCookies.forEach(cookie => {
          const cookieName = cookie.split('=')[0].trim();
          // Try to clear with different domain/path combinations
          const domains = [null, 'localhost', '.localhost', window.location.hostname];
          const paths = ['/', ''];
          
          domains.forEach(domain => {
            paths.forEach(path => {
              let cookieString = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=${path};`;
              if (domain) {
                cookieString += ` domain=${domain};`;
              }
              document.cookie = cookieString;
            });
          });
        });
      }
    } catch (error) {
      console.error('Logout error:', error);
      // Even if API call fails, clear local state
      setUser(null);
      setHasCheckedAuth(true);
    } finally {
      setIsLoggingOut(false);
      // Use window.location for hard redirect to ensure clean state
      window.location.href = '/auth/login';
    }
  };

  useEffect(() => {
    // Only check auth once on mount if we haven't checked yet
    // Don't check if we're logging out or if we've already checked
    if (!hasCheckedAuth && !isLoggingOut && !user) {
      checkAuth();
    } else if (user) {
      // If user is already set, we're not loading
      setLoading(false);
    } else if (hasCheckedAuth && !user && !isLoggingOut) {
      // If we've checked and there's no user, stop loading
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, login, logout, checkAuth }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}

