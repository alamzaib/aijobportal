import { GetServerSideProps } from 'next';
import { useRouter } from 'next/router';
import Layout from '@/components/Layout';
import JobCard from '@/components/JobCard';

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
  jobs: Job[];
  totalPages: number;
  currentPage: number;
}

export default function JobsPage({ jobs, totalPages, currentPage }: JobsPageProps) {
  const router = useRouter();

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
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
              <input
                type="text"
                placeholder="Search by title..."
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <input
                type="text"
                placeholder="Location"
                className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <select className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Job Type</option>
                <option value="full-time">Full-time</option>
                <option value="part-time">Part-time</option>
                <option value="contract">Contract</option>
                <option value="internship">Internship</option>
              </select>
              <button className="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                Filter
              </button>
            </div>
          </div>

          {/* Jobs Grid */}
          {jobs.length > 0 ? (
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

export const getServerSideProps: GetServerSideProps = async ({ query, locale }) => {
  const page = parseInt(query.page as string) || 1;
  const perPage = 12;

  // Mock data - replace with actual API call
  const allJobs: Job[] = Array.from({ length: 24 }, (_, i) => ({
    id: `${i + 1}`,
    title: `Job Title ${i + 1}`,
    company: `Company ${i + 1}`,
    location: ['Remote', 'New York, NY', 'San Francisco, CA', 'London, UK'][i % 4],
    type: ['Full-time', 'Part-time', 'Contract', 'Internship'][i % 4],
    salary: `$${50000 + i * 5000} - $${80000 + i * 5000}`,
    postedAt: `${i + 1} days ago`,
    slug: `job-${i + 1}`,
  }));

  const startIndex = (page - 1) * perPage;
  const endIndex = startIndex + perPage;
  const jobs = allJobs.slice(startIndex, endIndex);
  const totalPages = Math.ceil(allJobs.length / perPage);

  return {
    props: {
      jobs,
      totalPages,
      currentPage: page,
    },
  };
};

