import api from "./axios";
import type { RecurringExpense, RecurringFrequency, DayOfWeek } from "@/types";

export interface CreateRecurringExpenseData {
  template_subcategory_id?: number | null;
  label: string;
  amount_cents: number;
  frequency: RecurringFrequency;
  day_of_month?: number | null;
  day_of_week?: DayOfWeek | null;
  month_of_year?: number | null;
  auto_create?: boolean;
  is_active?: boolean;
  start_date: string;
  end_date?: string | null;
  payment_method?: string | null;
  notes?: string | null;
}

export const recurringExpensesApi = {
  async list(): Promise<RecurringExpense[]> {
    const response = await api.get<RecurringExpense[]>("/recurring-expenses");
    return response.data;
  },

  async get(id: number): Promise<RecurringExpense> {
    const response = await api.get<RecurringExpense>(`/recurring-expenses/${id}`);
    return response.data;
  },

  async create(data: CreateRecurringExpenseData): Promise<RecurringExpense> {
    const response = await api.post<RecurringExpense>("/recurring-expenses", data);
    return response.data;
  },

  async update(id: number, data: Partial<CreateRecurringExpenseData>): Promise<RecurringExpense> {
    const response = await api.put<RecurringExpense>(`/recurring-expenses/${id}`, data);
    return response.data;
  },

  async delete(id: number): Promise<void> {
    await api.delete(`/recurring-expenses/${id}`);
  },

  async toggleActive(id: number): Promise<RecurringExpense> {
    const response = await api.patch<RecurringExpense>(`/recurring-expenses/${id}/toggle-active`);
    return response.data;
  },
};
