import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { useRouter } from 'next/router';
import { apiClient } from '@/lib/api';

interface User {
  id: string;
  name: string;
  email: string;
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
  const router = useRouter();

  const checkAuth = async () => {
    setLoading(true);
    try {
      const userData = await apiClient.get<User>('/auth/user');
      setUser(userData);
    } catch (error: any) {
      // Only set user to null if it's a 401 (unauthorized)
      // Other errors might be network issues, so don't clear user state
      if (error?.response?.status === 401) {
        setUser(null);
      } else {
        // For other errors, don't clear user state immediately
        // This prevents logout on network errors
        console.error('Auth check error:', error);
      }
    } finally {
      setLoading(false);
      setHasCheckedAuth(true);
    }
  };

  const login = (userData: User) => {
    setUser(userData);
    setLoading(false);
    setHasCheckedAuth(true);
  };

  const logout = async () => {
    try {
      await apiClient.post('/auth/logout');
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setHasCheckedAuth(false);
      router.push('/auth/login');
    }
  };

  useEffect(() => {
    // Only check auth once on mount if we haven't checked yet
    if (!hasCheckedAuth && !user) {
      checkAuth();
    } else if (user) {
      // If user is already set, we're not loading
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

