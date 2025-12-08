import type { AppProps } from 'next/app';
import { useRouter } from 'next/router';
import '@/app/globals.css';

export default function App({ Component, pageProps }: AppProps) {
  const router = useRouter();
  
  // Set document direction based on locale for RTL support
  if (typeof window !== 'undefined') {
    document.documentElement.dir = router.locale === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = router.locale || 'en';
  }

  return <Component {...pageProps} />;
}

