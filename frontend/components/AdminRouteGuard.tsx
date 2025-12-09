import { ReactNode, useEffect, useState } from 'react';
import { useRouter } from 'next/router';
import { useAuth } from '@/contexts/AuthContext';

interface User {
  id: string;
  name: string;
  email: string;
  roles?: string[];
  is_blocked?: boolean;
}

interface AdminRouteGuardProps {
  children: ReactNode;
}

export default function AdminRouteGuard({ children }: AdminRouteGuardProps) {
  const { user, loading, checkAuth } = useAuth();
  const router = useRouter();
  const [refreshing, setRefreshing] = useState(true);
  const [refreshedUser, setRefreshedUser] = useState<User | null>(null);

  useEffect(() => {
    const verifyAccess = async () => {
      if (loading) {
        return;
      }

      // Always refresh user data to ensure roles are up to date
      // This is important because roles might have been assigned after login
      try {
        setRefreshing(true);
        // Call the API directly to get fresh user data
        const { apiClient } = await import('@/lib/api');
        const userData = await apiClient.get<User>('/auth/user');
        setRefreshedUser(userData);
        
        // Also update the auth context
        await checkAuth();
      } catch (error: any) {
        console.error('Failed to refresh auth:', error);
        if (error?.response?.status === 401) {
          router.push('/auth/login');
          return;
        }
      } finally {
        setRefreshing(false);
      }
    };

    verifyAccess();
  }, [loading, router, checkAuth]);

  // Use refreshed user data if available, otherwise fall back to context user
  const currentUser = refreshedUser || user;

  if (loading || refreshing) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
          <p className="mt-4 text-gray-600">Loading...</p>
        </div>
      </div>
    );
  }

  if (!currentUser) {
    return null;
  }

  if (!currentUser.roles || !currentUser.roles.includes('Admin')) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
          <div className="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
            <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h2 className="text-2xl font-bold text-gray-900 mb-2">Access Denied</h2>
          <p className="text-gray-600 mb-6">
            You need Admin role to access this page.
          </p>
          <div className="bg-gray-50 rounded-lg p-4 mb-6">
            <p className="text-sm font-medium text-gray-700 mb-2">Your current roles:</p>
            <div className="flex flex-wrap gap-2">
              {currentUser.roles && currentUser.roles.length > 0 ? (
                currentUser.roles.map((role) => (
                  <span key={role} className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm">
                    {role}
                  </span>
                ))
              ) : (
                <span className="text-gray-500 text-sm">No roles assigned</span>
              )}
            </div>
          </div>
          <div className="space-y-3">
            <p className="text-sm font-medium text-gray-700">To fix this:</p>
            <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1">
              <li>Make sure roles are seeded:
                <code className="block mt-1 bg-gray-100 px-2 py-1 rounded text-xs">php artisan db:seed --class=RoleSeeder</code>
              </li>
              <li>Assign Admin role:
                <code className="block mt-1 bg-gray-100 px-2 py-1 rounded text-xs">php artisan user:assign-admin {currentUser.email}</code>
              </li>
              <li>Clear cache:
                <code className="block mt-1 bg-gray-100 px-2 py-1 rounded text-xs">php artisan cache:clear</code>
              </li>
              <li>Refresh this page</li>
            </ol>
          </div>
          <button
            onClick={() => router.push('/')}
            className="mt-6 w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
          >
            Go to Home
          </button>
        </div>
      </div>
    );
  }

  return <>{children}</>;
}

