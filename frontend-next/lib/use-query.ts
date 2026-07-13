'use client';

import { useState, useEffect, useCallback, useRef } from 'react';

interface UseQueryOptions {
  auto?: boolean;
}

export function useQuery<T>(
  fetcher: () => Promise<T>,
  options: UseQueryOptions = {}
) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const fetcherRef = useRef(fetcher);
  fetcherRef.current = fetcher;

  const refetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await fetcherRef.current();
      setData(result);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Error desconocido');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    if (options.auto) {
      refetch();
    }
  }, [options.auto, refetch]);

  return { data, loading, error, refetch };
}
