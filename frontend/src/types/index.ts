export interface Role {
  id: number;
  label: string;
  description: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface User {
  id: number;
  name: string;
  email: string;
  roleId: number;
  role?: Role;
  deletedAt: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface UserWithPassword {
  user: User;
  password: string;
}

export interface BudgetTemplate {
  id: number;
  userId: number;
  name: string;
  isDefault: boolean;
  categories?: TemplateCategory[];
  createdAt: string;
  updatedAt: string;
}

export interface TemplateCategory {
  id: number;
  budgetTemplateId: number;
  name: string;
  sortOrder: number;
  plannedAmountCents: number;
  subcategories?: TemplateSubcategory[];
}

export interface TemplateSubcategory {
  id: number;
  templateCategoryId: number;
  name: string;
  plannedAmountCents: number;
  sortOrder: number;
}

export interface Budget {
  id: number;
  userId: number;
  month: string;
  name: string;
  generatedFromTemplateId: number | null;
  categories?: BudgetCategory[];
  expenses?: Expense[];
  createdAt: string;
  updatedAt: string;
}

export interface BudgetCategory {
  id: number;
  budgetId: number;
  name: string;
  sortOrder: number;
  plannedAmountCents: number;
  subcategories?: BudgetSubcategory[];
}

export interface BudgetSubcategory {
  id: number;
  budgetCategoryId: number;
  name: string;
  plannedAmountCents: number;
  sortOrder: number;
  expenses?: Expense[];
}

export interface Expense {
  id: number;
  budgetId: number;
  budgetSubcategoryId: number;
  date: string;
  label: string;
  amountCents: number;
  paymentMethod: string | null;
  notes: string | null;
  subcategory?: BudgetSubcategory;
  createdAt: string;
  updatedAt: string;
}

export type AssetType = "immobilier" | "épargne" | "investissement" | "autre";

export interface Asset {
  id: number;
  userId: number;
  type: AssetType;
  label: string;
  institution: string | null;
  valueCents: number;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface SavingsPlan {
  id: number;
  userId: number;
  month: string;
  plannedCents: number;
  actualCents?: number;
  createdAt: string;
  updatedAt: string;
}

export interface BudgetStats {
  totalPlannedCents: number;
  totalActualCents: number;
  varianceCents: number;
  variancePercentage: number | null;
  expenseCount: number;
}

export interface CategoryStats {
  id: number;
  name: string;
  plannedAmountCents: number;
  actualAmountCents: number;
  varianceCents: number;
  variancePercentage: number | null;
  expenseCount: number;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface AuthResponse {
  user: User;
  token: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}
