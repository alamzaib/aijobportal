import { useState, useEffect, useRef } from 'react';
import { GetServerSideProps } from 'next';
import { useRouter } from 'next/router';
import Layout from '@/components/Layout';
import JobCard from '@/components/JobCard';
import { apiClient, getErrorMessage } from '@/lib/api';

interface Company {
  id: string;
  name: string;
}

interface ApiJob {
  id: string;
  title: string;
  description: string;
  location: string | null;
  type: string | null;
  salary_min: number | null;
  salary_max: number | null;
  salary_currency: string;
  requirements: string[] | null;
  benefits: string[] | null;
  posted_at: string | null;
  company: Company;
}

interface PaginatedResponse {
  data: ApiJob[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

interface Job {
  id: string;
  title: string;
  company: string;
  location: string;
  type: string;
  salary?: string;
  postedAt: string;
  slug: string;
}

interface JobsPageProps {
  initialJobs: Job[];
  totalPages: number;
  currentPage: number;
  initialError?: string;
}

export default function JobsPage({ initialJobs, totalPages, currentPage, initialError }: JobsPageProps) {
  const router = useRouter();
  const [jobs, setJobs] = useState<Job[]>(initialJobs);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(initialError || null);
  const [filters, setFilters] = useState({
    search: (router.query.search as string) || '',
    location: (router.query.location as string) || '',
    type: (router.query.type as string) || '',
  });
  const isInitialMount = useRef(true);

  // Helper function to format job data
  const formatJob = (apiJob: ApiJob): Job => {
    const salaryStr = apiJob.salary_min && apiJob.salary_max
      ? `${apiJob.salary_currency || 'USD'} ${apiJob.salary_min.toLocaleString()} - ${apiJob.salary_max.toLocaleString()}`
      : undefined;

    const postedAt = apiJob.posted_at
      ? new Date(apiJob.posted_at).toLocaleDateString()
      : 'Recently';

    // Generate slug from title and id (format: title-slug--uuid)
    // Using double dash as delimiter to separate title from UUID
    const titleSlug = apiJob.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    const slug = `${titleSlug}--${apiJob.id}`;

    return {
      id: apiJob.id,
      title: apiJob.title,
      company: apiJob.company?.name || 'Unknown Company',
      location: apiJob.location || 'Not specified',
      type: apiJob.type || 'Not specified',
      salary: salaryStr,
      postedAt,
      slug,
    };
  };

  // Update jobs when router query changes (client-side navigation)
  // Skip initial mount to avoid double-fetching (getServerSideProps already fetched)
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    if (!router.isReady) return;

    const fetchJobs = async () => {
      setLoading(true);
      setError(null);

      try {
        const queryPage = router.query.page as string || '1';
        const querySearch = (router.query.search as string) || '';
        const queryLocation = (router.query.location as string) || '';
        const queryType = (router.query.type as string) || '';

        const params = new URLSearchParams();
        params.append('page', queryPage);
        params.append('per_page', '12');
        
        if (querySearch) params.append('search', querySearch);
        if (queryLocation) params.append('location', queryLocation);
        if (queryType) params.append('type', queryType);

        const response = await apiClient.get<PaginatedResponse>(`/jobs?${params.toString()}`);
        const formattedJobs = response.data.map(formatJob);
        setJobs(formattedJobs);
        
        // Update filters to match query
        setFilters({
          search: querySearch,
          location: queryLocation,
          type: queryType,
        });
      } catch (err) {
        setError(getErrorMessage(err));
        setJobs([]);
      } finally {
        setLoading(false);
      }
    };

    fetchJobs();
  }, [router.query.page, router.query.search, router.query.location, router.query.type, router.isReady]);

  const handleFilterChange = (key: string, value: string) => {
    setFilters(prev => ({ ...prev, [key]: value }));
    router.push({
      pathname: '/jobs',
      query: { ...router.query, [key]: value || undefined, page: 1 },
    });
  };

  const handleFilterSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    router.push({
      pathname: '/jobs',
      query: { ...filters, page: 1 },
    });
  };

  return (
    <Layout>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="container mx-auto px-4">
          <div className="mb-8">
            <h1 className="text-4xl font-bold text-gray-900 mb-4">All Jobs</h1>
            <p className="text-gray-600">Browse all available job opportunities</p>
          </div>

          {/* Filters */}
          <div className="bg-white rounded-lg shadow-md p-6 mb-8">
            <form onSubmit={handleFilterSubmit}>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <input
                  type="text"
                  placeholder="Search by title..."
                  value={filters.search}
                  onChange={(e) => handleFilterChange('search', e.target.value)}
                  className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <input
                  type="text"
                  placeholder="Location"
                  value={filters.location}
                  onChange={(e) => handleFilterChange('location', e.target.value)}
                  className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                />
                <select
                  value={filters.type}
                  onChange={(e) => handleFilterChange('type', e.target.value)}
                  className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                >
                  <option value="">Job Type</option>
                  <option value="full-time">Full-time</option>
                  <option value="part-time">Part-time</option>
                  <option value="contract">Contract</option>
                  <option value="internship">Internship</option>
                </select>
                <button
                  type="submit"
                  className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition"
                >
                  Filter
                </button>
              </div>
            </form>
          </div>

          {/* Error State */}
          {error && (
            <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-8">
              {error}
            </div>
          )}

          {/* Loading State */}
          {loading && (
            <div className="text-center py-16">
              <div className="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
              <p className="mt-4 text-gray-600">Loading jobs...</p>
            </div>
          )}

          {/* Jobs Grid */}
          {!loading && jobs.length > 0 ? (
            <>
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                {jobs.map((job) => (
                  <JobCard key={job.id} job={job} />
                ))}
              </div>

              {/* Pagination */}
              {totalPages > 1 && (
                <div className="flex justify-center gap-2">
                  <button
                    onClick={() => router.push(`/jobs?page=${currentPage - 1}`)}
                    disabled={currentPage === 1}
                    className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                  >
                    Previous
                  </button>
                  {Array.from({ length: totalPages }, (_, i) => i + 1).map((page) => (
                    <button
                      key={page}
                      onClick={() => router.push(`/jobs?page=${page}`)}
                      className={`px-4 py-2 border rounded-lg ${
                        currentPage === page
                          ? 'bg-blue-600 text-white border-blue-600'
                          : 'border-gray-300 hover:bg-gray-50'
                      }`}
                    >
                      {page}
                    </button>
                  ))}
                  <button
                    onClick={() => router.push(`/jobs?page=${currentPage + 1}`)}
                    disabled={currentPage === totalPages}
                    className="px-4 py-2 border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
                  >
                    Next
                  </button>
                </div>
              )}
            </>
          ) : (
            <div className="text-center py-16">
              <p className="text-gray-600 text-lg">No jobs found. Try adjusting your filters.</p>
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
}

export const getServerSideProps: GetServerSideProps = async ({ query }) => {
  const page = parseInt(query.page as string) || 1;
  const perPage = 12;

  try {
    // For server-side requests in Docker, use the service name instead of localhost
    const API_URL = process.env.INTERNAL_API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    const params = new URLSearchParams();
    params.append('page', page.toString());
    params.append('per_page', perPage.toString());
    
    if (query.search) params.append('search', query.search as string);
    if (query.location) params.append('location', query.location as string);
    if (query.type) params.append('type', query.type as string);

    const response = await fetch(`${API_URL}/jobs?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch jobs: ${response.statusText}`);
    }

    const data: PaginatedResponse = await response.json();

    const formatJob = (apiJob: ApiJob): Job => {
      const salaryStr = apiJob.salary_min && apiJob.salary_max
        ? `${apiJob.salary_currency || 'USD'} ${apiJob.salary_min.toLocaleString()} - ${apiJob.salary_max.toLocaleString()}`
        : undefined;

      const postedAt = apiJob.posted_at
        ? new Date(apiJob.posted_at).toLocaleDateString()
        : 'Recently';

      // Generate slug from title and id (format: title-slug--uuid)
      const titleSlug = apiJob.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
      const slug = `${titleSlug}--${apiJob.id}`;

      return {
        id: apiJob.id,
        title: apiJob.title,
        company: apiJob.company?.name || 'Unknown Company',
        location: apiJob.location || 'Not specified',
        type: apiJob.type || 'Not specified',
        salary: salaryStr,
        postedAt,
        slug,
      };
    };

    const jobs = data.data.map(formatJob);

    return {
      props: {
        initialJobs: jobs,
        totalPages: data.last_page,
        currentPage: data.current_page,
      },
    };
  } catch (error) {
    console.error('Error fetching jobs:', error);
    return {
      props: {
        initialJobs: [],
        totalPages: 0,
        currentPage: 1,
        initialError: error instanceof Error ? error.message : 'Failed to load jobs',
      },
    };
  }
};

