import { GetServerSideProps } from 'next';
import Layout from '@/components/Layout';
import JobCard from '@/components/JobCard';

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

interface HomeProps {
  featuredJobs: Job[];
}

export default function Home({ featuredJobs }: HomeProps) {
  return (
    <Layout>
      <div className="min-h-screen bg-gray-50">
        {/* Hero Section */}
        <section className="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
          <div className="container mx-auto px-4">
            <div className="max-w-3xl mx-auto text-center">
              <h1 className="text-4xl md:text-5xl font-bold mb-6">
                Find Your Dream Job Today
              </h1>
              <p className="text-xl mb-8 text-blue-100">
                Discover thousands of opportunities with AI-powered job matching
              </p>
              <div className="flex flex-col sm:flex-row gap-4 justify-center">
                <input
                  type="text"
                  placeholder="Search jobs..."
                  className="px-6 py-3 rounded-lg text-gray-900 flex-1 max-w-md"
                />
                <button className="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                  Search
                </button>
              </div>
            </div>
          </div>
        </section>

        {/* Featured Jobs Section */}
        <section className="container mx-auto px-4 py-16">
          <h2 className="text-3xl font-bold mb-8 text-gray-900">Featured Jobs</h2>
          {featuredJobs.length > 0 ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {featuredJobs.map((job) => (
                <JobCard key={job.id} job={job} />
              ))}
            </div>
          ) : (
            <p className="text-gray-600 text-center py-8">No featured jobs available at the moment.</p>
          )}
        </section>
      </div>
    </Layout>
  );
}

export const getServerSideProps: GetServerSideProps = async ({ locale }) => {
  try {
    // For server-side requests in Docker, use the service name instead of localhost
    const API_URL = process.env.INTERNAL_API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    
    // Fetch featured jobs (first 6 active jobs)
    const params = new URLSearchParams();
    params.append('page', '1');
    params.append('per_page', '6');

    const response = await fetch(`${API_URL}/jobs?${params.toString()}`, {
      headers: {
        'Accept': 'application/json',
      },
    });

    if (!response.ok) {
      throw new Error(`Failed to fetch jobs: ${response.statusText}`);
    }

    const data: PaginatedResponse = await response.json();

    // Helper function to format job data (same as jobs/index.tsx)
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

    const featuredJobs = data.data.map(formatJob);

    return {
      props: {
        featuredJobs,
      },
    };
  } catch (error) {
    console.error('Error fetching featured jobs:', error);
    // Return empty array on error
    return {
      props: {
        featuredJobs: [],
      },
    };
  }
};

