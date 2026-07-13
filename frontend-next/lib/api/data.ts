import { api } from '@/lib/api-client';
import type { DataApiResponse, TableInfo, TablesResponse } from '@/lib/api-types';

export type { DataApiResponse as QueryResult, TableInfo as DescribeResult };

export const dataApi = {
  query(sql: string): Promise<DataApiResponse<Record<string, unknown>[]>> {
    return api.post<DataApiResponse<Record<string, unknown>[]>>('/data/query', { sql });
  },

  list(table: string, where?: Record<string, unknown>, order?: string, limit?: number, offset?: number): Promise<DataApiResponse<Record<string, unknown>[]>> {
    return api.post<DataApiResponse<Record<string, unknown>[]>>('/data/list', { table, where, order, limit, offset });
  },

  get(table: string, id_field: string, id_value: string | number): Promise<DataApiResponse<Record<string, unknown>>> {
    return api.post<DataApiResponse<Record<string, unknown>>>('/data/get', { table, id_field, id_value });
  },

  describe(table: string): Promise<{ success: boolean; columns: TableInfo[]; error?: string }> {
    return api.post<{ success: boolean; columns: TableInfo[]; error?: string }>('/data/describe', { table });
  },

  tables(pattern?: string): Promise<TablesResponse> {
    return api.post<TablesResponse>('/data/tables', { pattern });
  },
};
