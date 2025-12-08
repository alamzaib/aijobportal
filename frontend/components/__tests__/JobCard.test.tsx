import { render, screen } from '@testing-library/react';
import { useRouter } from 'next/router';
import JobCard from '../JobCard';

// Mock next/router
jest.mock('next/router', () => ({
  useRouter: jest.fn(),
}));

// Mock next/link
jest.mock('next/link', () => {
  return ({ children, href }: { children: React.ReactNode; href: string }) => {
    return <a href={href}>{children}</a>;
  };
});

describe('JobCard', () => {
  const mockRouter = {
    locale: 'en',
    push: jest.fn(),
    pathname: '/jobs',
    query: {},
  };

  beforeEach(() => {
    (useRouter as jest.Mock).mockReturnValue(mockRouter);
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  const mockJob = {
    id: '123',
    title: 'Senior Frontend Developer',
    company: 'Tech Corp',
    location: 'San Francisco, CA',
    type: 'Full-time',
    salary: '$100,000 - $150,000',
    postedAt: '2 days ago',
    slug: 'senior-frontend-developer--123',
  };

  it('renders job title correctly', () => {
    render(<JobCard job={mockJob} />);
    expect(screen.getByText('Senior Frontend Developer')).toBeInTheDocument();
  });

  it('renders company name correctly', () => {
    render(<JobCard job={mockJob} />);
    expect(screen.getByText('Tech Corp')).toBeInTheDocument();
  });

  it('renders location correctly', () => {
    render(<JobCard job={mockJob} />);
    expect(screen.getByText('San Francisco, CA')).toBeInTheDocument();
  });

  it('renders job type correctly', () => {
    render(<JobCard job={mockJob} />);
    expect(screen.getByText('Full-time')).toBeInTheDocument();
  });

  it('renders salary when provided', () => {
    render(<JobCard job={mockJob} />);
    expect(screen.getByText('$100,000 - $150,000')).toBeInTheDocument();
  });

  it('does not render salary when not provided', () => {
    const jobWithoutSalary = { ...mockJob, salary: undefined };
    render(<JobCard job={jobWithoutSalary} />);
    expect(screen.queryByText(/\$/)).not.toBeInTheDocument();
  });

  it('renders posted date correctly', () => {
    render(<JobCard job={mockJob} />);
    expect(screen.getByText('2 days ago')).toBeInTheDocument();
  });

  it('renders "View Details" link with correct href', () => {
    render(<JobCard job={mockJob} />);
    const link = screen.getByText('View Details →');
    expect(link).toBeInTheDocument();
    expect(link.closest('a')).toHaveAttribute('href', '/jobs/senior-frontend-developer--123');
  });

  it('renders job title as link with correct href', () => {
    render(<JobCard job={mockJob} />);
    const titleLink = screen.getByText('Senior Frontend Developer').closest('a');
    expect(titleLink).toHaveAttribute('href', '/jobs/senior-frontend-developer--123');
  });

  it('handles missing optional fields gracefully', () => {
    const minimalJob = {
      id: '456',
      title: 'Developer',
      company: 'Company',
      location: 'Remote',
      type: 'Contract',
      postedAt: 'Today',
      slug: 'developer--456',
    };
    
    render(<JobCard job={minimalJob} />);
    expect(screen.getByText('Developer')).toBeInTheDocument();
    expect(screen.getByText('Company')).toBeInTheDocument();
    expect(screen.getByText('Remote')).toBeInTheDocument();
    expect(screen.getByText('Contract')).toBeInTheDocument();
  });

  it('applies correct CSS classes for styling', () => {
    const { container } = render(<JobCard job={mockJob} />);
    const card = container.firstChild;
    expect(card).toHaveClass('bg-white', 'rounded-lg', 'shadow-md');
  });

  it('displays all job information in correct structure', () => {
    render(<JobCard job={mockJob} />);
    
    // Check that all main elements are present
    expect(screen.getByText('Senior Frontend Developer')).toBeInTheDocument();
    expect(screen.getByText('Tech Corp')).toBeInTheDocument();
    expect(screen.getByText('San Francisco, CA')).toBeInTheDocument();
    expect(screen.getByText('Full-time')).toBeInTheDocument();
    expect(screen.getByText('$100,000 - $150,000')).toBeInTheDocument();
    expect(screen.getByText('2 days ago')).toBeInTheDocument();
    expect(screen.getByText('View Details →')).toBeInTheDocument();
  });
});

