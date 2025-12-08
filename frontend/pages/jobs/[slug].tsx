import { GetServerSideProps } from 'next';
import { useRouter } from 'next/router';
import Layout from '@/components/Layout';

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

interface Job {
  id: string;
  title: string;
  company: string;
  location: string;
  type: string;
  salary?: string;
  postedAt: string;
  description: string;
  requirements: string[];
  benefits: string[];
}

interface JobDetailProps {
  job: Job;
}

export default function JobDetailPage({ job }: JobDetailProps) {
  const router = useRouter();

  return (
    <Layout>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="container mx-auto px-4 max-w-4xl">
          <button
            onClick={() => router.back()}
            className="text-blue-600 hover:text-blue-800 mb-6 flex items-center gap-2"
          >
            ← Back to Jobs
          </button>

          <div className="bg-white rounded-lg shadow-md p-8">
            <div className="mb-6">
              <h1 className="text-4xl font-bold text-gray-900 mb-2">{job.title}</h1>
              <p className="text-xl text-gray-600 mb-4">{job.company}</p>
              <div className="flex flex-wrap gap-4 text-gray-600">
                <span className="flex items-center gap-2">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {job.location}
                </span>
                <span className="flex items-center gap-2">
                  <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                  {job.type}
                </span>
                {job.salary && (
                  <span className="flex items-center gap-2">
                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {job.salary}
                  </span>
                )}
                <span className="text-sm text-gray-500">Posted {job.postedAt}</span>
              </div>
            </div>

            <div className="border-t border-gray-200 pt-6 mb-6">
              <button className="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition w-full sm:w-auto">
                Apply Now
              </button>
            </div>

            <div className="border-t border-gray-200 pt-6 space-y-6">
              <div>
                <h2 className="text-2xl font-bold text-gray-900 mb-4">Job Description</h2>
                <p className="text-gray-700 leading-relaxed whitespace-pre-line">{job.description}</p>
              </div>

              {job.requirements && job.requirements.length > 0 && (
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">Requirements</h2>
                  <ul className="list-disc list-inside space-y-2 text-gray-700">
                    {job.requirements.map((req, index) => (
                      <li key={index}>{req}</li>
                    ))}
                  </ul>
                </div>
              )}

              {job.benefits && job.benefits.length > 0 && (
                <div>
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">Benefits</h2>
                  <ul className="list-disc list-inside space-y-2 text-gray-700">
                    {job.benefits.map((benefit, index) => (
                      <li key={index}>{benefit}</li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}

export const getServerSideProps: GetServerSideProps = async ({ params }) => {
  const slug = params?.slug as string;

  try {
    // Backend now supports both UUID and slug formats
    // Pass the slug as-is - backend will handle UUID extraction or title matching
    // For server-side requests in Docker, use the service name instead of localhost
    // Check if we're in Docker (server-side) by looking for internal API URL env var
    const API_URL = process.env.INTERNAL_API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    const url = `${API_URL}/jobs/${encodeURIComponent(slug)}`;
    
    console.log('[getServerSideProps] Fetching job:', url);
    
    const response = await fetch(url, {
      headers: {
        'Accept': 'application/json',
      },
      // Add cache control to prevent caching issues
      cache: 'no-store',
    });

    console.log('[getServerSideProps] Response status:', response.status);

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      console.error('[getServerSideProps] Error response:', errorData);
      
      if (response.status === 404) {
        // Log available jobs for debugging
        try {
          const debugResponse = await fetch(`${API_URL}/jobs-debug/slugs`);
          if (debugResponse.ok) {
            const availableJobs = await debugResponse.json();
            console.log('[getServerSideProps] Available job slugs:', availableJobs.map((j: any) => j.slug));
          }
        } catch (e) {
          // Ignore debug endpoint errors
        }
        
        return {
          notFound: true,
        };
      }
      throw new Error(`Failed to fetch job: ${response.statusText}`);
    }

    const apiJob: ApiJob = await response.json();
    console.log('[getServerSideProps] Job found:', apiJob.title);

    // Format the job data
    const salaryStr = apiJob.salary_min && apiJob.salary_max
      ? `${apiJob.salary_currency || 'USD'} ${apiJob.salary_min.toLocaleString()} - ${apiJob.salary_max.toLocaleString()}`
      : undefined;

    const postedAt = apiJob.posted_at
      ? new Date(apiJob.posted_at).toLocaleDateString()
      : 'Recently';

    const job: Job = {
      id: apiJob.id,
      title: apiJob.title,
      company: apiJob.company?.name || 'Unknown Company',
      location: apiJob.location || 'Not specified',
      type: apiJob.type || 'Not specified',
      salary: salaryStr,
      postedAt,
      description: apiJob.description,
      requirements: apiJob.requirements || [],
      benefits: apiJob.benefits || [],
    };

    return {
      props: {
        job,
      },
    };
  } catch (error) {
    console.error('Error fetching job:', error);
    return {
      notFound: true,
    };
  }
};

