import { useEffect, useState } from 'react';
import AdminLayout from '@/components/AdminLayout';
import AdminRouteGuard from '@/components/AdminRouteGuard';
import { apiClient, getErrorMessage } from '@/lib/api';

interface User {
  id: string;
  name: string;
  email: string;
  is_blocked: boolean;
  role_names: string[];
  created_at: string;
}

interface PaginatedUsers {
  data: User[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export default function AdminUsers() {
  const [users, setUsers] = useState<PaginatedUsers | null>(null);
  const [loading, setLoading] = useState(true);
  const [roleFilter, setRoleFilter] = useState<string>('all');
  const [blockedFilter, setBlockedFilter] = useState<string>('all');
  const [search, setSearch] = useState('');

  useEffect(() => {
    fetchUsers();
  }, [roleFilter, blockedFilter, search]);

  const fetchUsers = async () => {
    setLoading(true);
    try {
      const params: any = {};
      if (roleFilter !== 'all') {
        params.role = roleFilter;
      }
      if (blockedFilter !== 'all') {
        params.is_blocked = blockedFilter === 'blocked';
      }
      if (search) {
        params.search = search;
      }
      const data = await apiClient.get<PaginatedUsers>('/admin/users', { params });
      setUsers(data);
    } catch (error) {
      console.error('Failed to fetch users:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleBlock = async (userId: string) => {
    if (!confirm('Are you sure you want to block this user?')) return;
    try {
      await apiClient.post(`/admin/users/${userId}/block`);
      fetchUsers();
    } catch (error) {
      alert(getErrorMessage(error));
    }
  };

  const handleUnblock = async (userId: string) => {
    try {
      await apiClient.post(`/admin/users/${userId}/unblock`);
      fetchUsers();
    } catch (error) {
      alert(getErrorMessage(error));
    }
  };

  const handleAssignRole = async (userId: string, role: string) => {
    if (!confirm(`Are you sure you want to assign ${role} role to this user?`)) return;
    try {
      await apiClient.post(`/admin/users/${userId}/assign-role`, { role });
      fetchUsers();
    } catch (error) {
      alert(getErrorMessage(error));
    }
  };

  const getRoleBadge = (roles: string[]) => {
    const colors: Record<string, string> = {
      Admin: 'bg-purple-100 text-purple-800',
      Employer: 'bg-indigo-100 text-indigo-800',
      Candidate: 'bg-teal-100 text-teal-800',
    };
    return roles.map((role) => (
      <span
        key={role}
        className={`px-2 py-1 rounded-full text-xs font-medium mr-1 ${colors[role] || 'bg-gray-100 text-gray-800'}`}
      >
        {role}
      </span>
    ));
  };

  return (
    <AdminRouteGuard>
      <AdminLayout>
        <div className="space-y-6">
          {/* Filters */}
          <div className="bg-white rounded-lg shadow p-4">
            <div className="flex flex-col md:flex-row gap-4">
              <div className="flex-1">
                <input
                  type="text"
                  placeholder="Search users..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <select
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">All Roles</option>
                <option value="Admin">Admin</option>
                <option value="Employer">Employer</option>
                <option value="Candidate">Candidate</option>
              </select>
              <select
                value={blockedFilter}
                onChange={(e) => setBlockedFilter(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">All Users</option>
                <option value="blocked">Blocked</option>
                <option value="active">Active</option>
              </select>
            </div>
          </div>

          {/* Users Table */}
          {loading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
          ) : users && users.data.length > 0 ? (
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Name
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Email
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Roles
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Created
                    </th>
                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                  {users.data.map((user) => (
                    <tr key={user.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{user.name}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">{user.email}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex flex-wrap">{getRoleBadge(user.role_names || [])}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {user.is_blocked ? (
                          <span className="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Blocked
                          </span>
                        ) : (
                          <span className="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Active
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(user.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex justify-end items-center space-x-2">
                          {!user.is_blocked ? (
                            <button
                              onClick={() => handleBlock(user.id)}
                              className="text-red-600 hover:text-red-900 px-3 py-1 rounded bg-red-50 hover:bg-red-100"
                            >
                              Block
                            </button>
                          ) : (
                            <button
                              onClick={() => handleUnblock(user.id)}
                              className="text-green-600 hover:text-green-900 px-3 py-1 rounded bg-green-50 hover:bg-green-100"
                            >
                              Unblock
                            </button>
                          )}
                          <select
                            onChange={(e) => handleAssignRole(user.id, e.target.value)}
                            className="px-2 py-1 border border-gray-300 rounded text-xs"
                            defaultValue=""
                          >
                            <option value="" disabled>
                              Assign Role
                            </option>
                            <option value="Admin">Admin</option>
                            <option value="Employer">Employer</option>
                            <option value="Candidate">Candidate</option>
                          </select>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="bg-white rounded-lg shadow p-8 text-center">
              <p className="text-gray-500">No users found.</p>
            </div>
          )}
        </div>
      </AdminLayout>
    </AdminRouteGuard>
  );
}

