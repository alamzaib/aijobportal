import { GetServerSideProps } from 'next';
import { useRouter } from 'next/router';
import { useState, useEffect } from 'react';
import Layout from '@/components/Layout';
import { useAuth } from '@/contexts/AuthContext';
import { apiClient, getErrorMessage } from '@/lib/api';

interface Company {
  id: string;
  name: string;
}

interface Job {
  id: string;
  title: string;
  company: Company;
}

interface ParsedCv {
  name: string;
  email: string;
  phone?: string;
  skills: string[];
  experiences: Array<{
    company: string;
    title: string;
    from: string;
    to: string;
    summary: string;
  }>;
  education: Array<{
    institution: string;
    degree?: string;
    field?: string;
    year?: string;
  }>;
}

interface Resume {
  id: string;
  title: string;
  parsed_json: ParsedCv | null;
}

interface User {
  id: number;
  name: string;
  email: string;
}

interface Application {
  id: number;
  user: User;
  resume: Resume | null;
  cover_letter: string | null;
  status: string;
  applied_at: string;
}

interface ApplicantsPageProps {
  job: Job;
  initialApplications: Application[];
}

export default function ApplicantsPage({ job, initialApplications }: ApplicantsPageProps) {
  const router = useRouter();
  const { user, loading: authLoading } = useAuth();
  const [applications, setApplications] = useState<Application[]>(initialApplications);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    if (!authLoading && user === null) {
      router.push('/auth/login?returnUrl=' + encodeURIComponent(router.asPath));
    }
  }, [user, authLoading, router]);

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'accepted':
        return 'bg-green-100 text-green-800';
      case 'rejected':
        return 'bg-red-100 text-red-800';
      case 'shortlisted':
        return 'bg-blue-100 text-blue-800';
      case 'reviewing':
        return 'bg-yellow-100 text-yellow-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getStatusLabel = (status: string) => {
    switch (status) {
      case 'accepted':
        return 'Accepted';
      case 'rejected':
        return 'Rejected';
      case 'shortlisted':
        return 'Shortlisted';
      case 'reviewing':
        return 'Under Review';
      default:
        return 'Pending';
    }
  };

  if (authLoading) {
    return (
      <Layout>
        <div className="min-h-screen bg-gray-50 py-8">
          <div className="container mx-auto px-4 max-w-6xl">
            <div className="text-center">Loading...</div>
          </div>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="min-h-screen bg-gray-50 py-8">
        <div className="container mx-auto px-4 max-w-6xl">
          <button
            onClick={() => router.back()}
            className="text-blue-600 hover:text-blue-800 mb-6 flex items-center gap-2"
          >
            ← Back
          </button>

          <div className="mb-6">
            <h1 className="text-3xl font-bold text-gray-900 mb-2">Applicants for {job.title}</h1>
            <p className="text-gray-600">{job.company.name}</p>
          </div>

          {error && (
            <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
              {error}
            </div>
          )}

          {applications.length === 0 ? (
            <div className="bg-white rounded-lg shadow-md p-8 text-center">
              <p className="text-gray-600">No applications yet.</p>
            </div>
          ) : (
            <div className="space-y-6">
              {applications.map((application) => {
                const parsedCv = application.resume?.parsed_json;
                
                return (
                  <div
                    key={application.id}
                    className="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
                  >
                    <div className="flex items-start justify-between mb-4">
                      <div className="flex-1">
                        <h2 className="text-xl font-semibold text-gray-900 mb-1">
                          {parsedCv?.name || application.user.name}
                        </h2>
                        <p className="text-gray-600 text-sm mb-2">
                          {parsedCv?.email || application.user.email}
                          {parsedCv?.phone && ` • ${parsedCv.phone}`}
                        </p>
                        <p className="text-gray-500 text-xs">
                          Applied on {formatDate(application.applied_at)}
                        </p>
                      </div>
                      <span
                        className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(
                          application.status
                        )}`}
                      >
                        {getStatusLabel(application.status)}
                      </span>
                    </div>

                    {parsedCv ? (
                      <div className="space-y-4 mt-4 border-t pt-4">
                        {/* Skills */}
                        {parsedCv.skills && parsedCv.skills.length > 0 && (
                          <div>
                            <h3 className="text-sm font-semibold text-gray-700 mb-2">Skills</h3>
                            <div className="flex flex-wrap gap-2">
                              {parsedCv.skills.map((skill, idx) => (
                                <span
                                  key={idx}
                                  className="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"
                                >
                                  {skill}
                                </span>
                              ))}
                            </div>
                          </div>
                        )}

                        {/* Experience */}
                        {parsedCv.experiences && parsedCv.experiences.length > 0 && (
                          <div>
                            <h3 className="text-sm font-semibold text-gray-700 mb-2">Experience</h3>
                            <div className="space-y-3">
                              {parsedCv.experiences.map((exp, idx) => (
                                <div key={idx} className="border-l-2 border-blue-200 pl-4">
                                  <div className="flex items-start justify-between mb-1">
                                    <div>
                                      <p className="font-medium text-gray-900">{exp.title}</p>
                                      <p className="text-sm text-gray-600">{exp.company}</p>
                                    </div>
                                    <p className="text-xs text-gray-500 whitespace-nowrap ml-4">
                                      {exp.from} - {exp.to}
                                    </p>
                                  </div>
                                  {exp.summary && (
                                    <p className="text-sm text-gray-700 mt-1">{exp.summary}</p>
                                  )}
                                </div>
                              ))}
                            </div>
                          </div>
                        )}

                        {/* Education */}
                        {parsedCv.education && parsedCv.education.length > 0 && (
                          <div>
                            <h3 className="text-sm font-semibold text-gray-700 mb-2">Education</h3>
                            <div className="space-y-2">
                              {parsedCv.education.map((edu, idx) => (
                                <div key={idx} className="text-sm">
                                  <p className="font-medium text-gray-900">
                                    {edu.institution}
                                    {edu.degree && ` - ${edu.degree}`}
                                    {edu.field && ` in ${edu.field}`}
                                    {edu.year && ` (${edu.year})`}
                                  </p>
                                </div>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                    ) : (
                      <div className="mt-4 border-t pt-4">
                        <p className="text-sm text-gray-500 italic">
                          {application.resume
                            ? 'CV is being processed...'
                            : 'No resume attached'}
                        </p>
                      </div>
                    )}

                    {/* Cover Letter */}
                    {application.cover_letter && (
                      <div className="mt-4 border-t pt-4">
                        <h3 className="text-sm font-semibold text-gray-700 mb-2">Cover Letter</h3>
                        <p className="text-sm text-gray-700 whitespace-pre-wrap">
                          {application.cover_letter}
                        </p>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
}

export const getServerSideProps: GetServerSideProps = async ({ params, req }) => {
  const slug = params?.slug as string;

  try {
    const API_URL = process.env.INTERNAL_API_URL || process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';
    
    // Extract job ID from slug if needed (slug format: "title-slug--uuid" or just uuid)
    let jobId = slug;
    if (slug.includes('--')) {
      // Extract UUID from "title-slug--uuid" format
      const parts = slug.split('--');
      jobId = parts[parts.length - 1];
    }
    
    // Get job and applications
    const response = await fetch(`${API_URL}/jobs/${encodeURIComponent(jobId)}/applications`, {
      headers: {
        'Accept': 'application/json',
        'Cookie': req.headers.cookie || '',
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      if (response.status === 404) {
        return { notFound: true };
      }
      throw new Error(`Failed to fetch applications: ${response.statusText}`);
    }

    const data = await response.json();
    
    return {
      props: {
        job: data.job,
        initialApplications: data.applications || [],
      },
    };
  } catch (error) {
    console.error('Error fetching applicants:', error);
    return {
      notFound: true,
    };
  }
};

