import { GetServerSideProps } from 'next';
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
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {featuredJobs.map((job) => (
              <JobCard key={job.id} job={job} />
            ))}
          </div>
        </section>
      </div>
    </Layout>
  );
}

export const getServerSideProps: GetServerSideProps = async ({ locale }) => {
  // Mock data - replace with actual API call
  const featuredJobs: Job[] = [
    {
      id: '1',
      title: 'Senior Frontend Developer',
      company: 'Tech Corp',
      location: 'Remote',
      type: 'Full-time',
      salary: '$80,000 - $120,000',
      postedAt: '2 days ago',
      slug: 'senior-frontend-developer',
    },
    {
      id: '2',
      title: 'Backend Engineer',
      company: 'StartupXYZ',
      location: 'New York, NY',
      type: 'Full-time',
      salary: '$90,000 - $130,000',
      postedAt: '1 week ago',
      slug: 'backend-engineer',
    },
    {
      id: '3',
      title: 'UI/UX Designer',
      company: 'Design Studio',
      location: 'San Francisco, CA',
      type: 'Contract',
      salary: '$60,000 - $80,000',
      postedAt: '3 days ago',
      slug: 'ui-ux-designer',
    },
  ];

  return {
    props: {
      featuredJobs,
    },
  };
};

