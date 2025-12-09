import { useEffect, useState } from 'react';
import AdminLayout from '@/components/AdminLayout';
import AdminRouteGuard from '@/components/AdminRouteGuard';
import { apiClient } from '@/lib/api';

interface DashboardStats {
  total_jobs: number;
  pending_jobs: number;
  approved_jobs: number;
  rejected_jobs: number;
  total_users: number;
  blocked_users: number;
  admin_users: number;
  employer_users: number;
  candidate_users: number;
}

export default function AdminDashboard() {
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const data = await apiClient.get<DashboardStats>('/admin/dashboard');
      setStats(data);
      setError(null);
    } catch (error: any) {
      console.error('Failed to fetch dashboard stats:', error);
      if (error.response?.status === 403) {
        const userRoles = error.response?.data?.user_roles || [];
        setError(
          `Access denied. You need Admin role to access this page. Your current roles: ${userRoles.length > 0 ? userRoles.join(', ') : 'None'}. Please contact an administrator to assign the Admin role.`
        );
      } else {
        setError('Failed to load dashboard statistics. Please try again.');
      }
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ title, value, color }: { title: string; value: number; color: string }) => (
    <div className="bg-white rounded-lg shadow p-6">
      <h3 className="text-sm font-medium text-gray-500 mb-2">{title}</h3>
      <p className={`text-3xl font-bold ${color}`}>{value}</p>
    </div>
  );

  return (
    <AdminRouteGuard>
      <AdminLayout>
        {loading ? (
          <div className="flex items-center justify-center h-64">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
        ) : error ? (
          <div className="bg-red-50 border border-red-200 rounded-lg p-6">
            <div className="flex items-start">
              <svg className="w-6 h-6 text-red-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div className="flex-1">
                <h3 className="text-lg font-semibold text-red-800 mb-2">Access Denied</h3>
                <p className="text-red-700 mb-4">{error}</p>
                <div className="bg-white rounded p-4 border border-red-200">
                  <p className="text-sm font-medium text-gray-700 mb-2">To fix this issue:</p>
                  <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1">
                    <li>Make sure roles are seeded: <code className="bg-gray-100 px-2 py-1 rounded">php artisan db:seed --class=RoleSeeder</code></li>
                    <li>Assign Admin role to your user: <code className="bg-gray-100 px-2 py-1 rounded">php artisan user:assign-admin your-email@example.com</code></li>
                    <li>Refresh this page</li>
                  </ol>
                </div>
              </div>
            </div>
          </div>
        ) : stats ? (
          <div className="space-y-6">
            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <StatCard title="Total Jobs" value={stats.total_jobs} color="text-blue-600" />
              <StatCard title="Pending Jobs" value={stats.pending_jobs} color="text-yellow-600" />
              <StatCard title="Approved Jobs" value={stats.approved_jobs} color="text-green-600" />
              <StatCard title="Rejected Jobs" value={stats.rejected_jobs} color="text-red-600" />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <StatCard title="Total Users" value={stats.total_users} color="text-blue-600" />
              <StatCard title="Blocked Users" value={stats.blocked_users} color="text-red-600" />
              <StatCard title="Admin Users" value={stats.admin_users} color="text-purple-600" />
              <StatCard title="Employer Users" value={stats.employer_users} color="text-indigo-600" />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <StatCard title="Candidate Users" value={stats.candidate_users} color="text-teal-600" />
            </div>
          </div>
        ) : (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4">
            <p className="text-red-800">Failed to load dashboard statistics.</p>
          </div>
        )}
      </AdminLayout>
    </AdminRouteGuard>
  );
}

