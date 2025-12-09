import { useEffect, useState } from 'react';
import AdminLayout from '@/components/AdminLayout';
import AdminRouteGuard from '@/components/AdminRouteGuard';
import { apiClient, getErrorMessage } from '@/lib/api';

interface Job {
  id: string;
  title: string;
  description: string;
  status: 'pending' | 'approved' | 'rejected';
  is_active: boolean;
  company: {
    id: number;
    name: string;
  };
  created_at: string;
}

interface PaginatedJobs {
  data: Job[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export default function AdminJobs() {
  const [jobs, setJobs] = useState<PaginatedJobs | null>(null);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState<string>('all');
  const [search, setSearch] = useState('');

  useEffect(() => {
    fetchJobs();
  }, [statusFilter, search]);

  const fetchJobs = async () => {
    setLoading(true);
    try {
      const params: any = {};
      if (statusFilter !== 'all') {
        params.status = statusFilter;
      }
      if (search) {
        params.search = search;
      }
      const data = await apiClient.get<PaginatedJobs>('/admin/jobs', { params });
      setJobs(data);
    } catch (error) {
      console.error('Failed to fetch jobs:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleApprove = async (jobId: string) => {
    try {
      await apiClient.post(`/admin/jobs/${jobId}/approve`);
      fetchJobs();
    } catch (error) {
      alert(getErrorMessage(error));
    }
  };

  const handleReject = async (jobId: string) => {
    try {
      await apiClient.post(`/admin/jobs/${jobId}/reject`);
      fetchJobs();
    } catch (error) {
      alert(getErrorMessage(error));
    }
  };

  const getStatusBadge = (status: string) => {
    const colors = {
      pending: 'bg-yellow-100 text-yellow-800',
      approved: 'bg-green-100 text-green-800',
      rejected: 'bg-red-100 text-red-800',
    };
    return (
      <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[status as keyof typeof colors]}`}>
        {status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
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
                  placeholder="Search jobs..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
              </div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              >
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>

          {/* Jobs Table */}
          {loading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>
          ) : jobs && jobs.data.length > 0 ? (
            <div className="bg-white rounded-lg shadow overflow-hidden">
              <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Job Title
                    </th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Company
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
                  {jobs.data.map((job) => (
                    <tr key={job.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{job.title}</div>
                        <div className="text-sm text-gray-500 truncate max-w-md">{job.description}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm text-gray-900">{job.company.name}</div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">{getStatusBadge(job.status)}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {new Date(job.created_at).toLocaleDateString()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        {job.status === 'pending' && (
                          <div className="flex justify-end space-x-2">
                            <button
                              onClick={() => handleApprove(job.id)}
                              className="text-green-600 hover:text-green-900 px-3 py-1 rounded bg-green-50 hover:bg-green-100"
                            >
                              Approve
                            </button>
                            <button
                              onClick={() => handleReject(job.id)}
                              className="text-red-600 hover:text-red-900 px-3 py-1 rounded bg-red-50 hover:bg-red-100"
                            >
                              Reject
                            </button>
                          </div>
                        )}
                        {job.status === 'approved' && (
                          <button
                            onClick={() => handleReject(job.id)}
                            className="text-red-600 hover:text-red-900 px-3 py-1 rounded bg-red-50 hover:bg-red-100"
                          >
                            Reject
                          </button>
                        )}
                        {job.status === 'rejected' && (
                          <button
                            onClick={() => handleApprove(job.id)}
                            className="text-green-600 hover:text-green-900 px-3 py-1 rounded bg-green-50 hover:bg-green-100"
                          >
                            Approve
                          </button>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="bg-white rounded-lg shadow p-8 text-center">
              <p className="text-gray-500">No jobs found.</p>
            </div>
          )}
        </div>
      </AdminLayout>
    </AdminRouteGuard>
  );
}

