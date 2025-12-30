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
  defaultSpentCents?: number;
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
  defaultSpentCents?: number;
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

export type RecurringFrequency = "monthly" | "weekly" | "yearly";

export type DayOfWeek =
  | "monday"
  | "tuesday"
  | "wednesday"
  | "thursday"
  | "friday"
  | "saturday"
  | "sunday";

export interface RecurringExpense {
  id: number;
  userId: number;
  templateSubcategoryId: number | null;
  templateSubcategory?: TemplateSubcategory & {
    templateCategory?: TemplateCategory;
  };
  label: string;
  amountCents: number;
  frequency: RecurringFrequency;
  dayOfMonth: number | null;
  dayOfWeek: DayOfWeek | null;
  monthOfYear: number | null;
  autoCreate: boolean;
  isActive: boolean;
  startDate: string;
  endDate: string | null;
  paymentMethod: string | null;
  notes: string | null;
  createdAt: string;
  updatedAt: string;
}

export type AssetType = "immobilier" | "épargne" | "investissement" | "autre";

export interface Asset {
  id: number;
  userId: number;
  type: AssetType;
  isLiability: boolean;
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

export interface WealthHistory {
  id: number;
  userId: number;
  recordedAt: string;
  totalAssetsCents: number;
  totalLiabilitiesCents: number;
  netWorthCents: number;
  createdAt: string;
  updatedAt: string;
}

export interface Notification {
  id: number;
  userId: number;
  type: "budget_exceeded" | "savings_goal_reached";
  title: string;
  message: string;
  data: {
    budgetId?: number;
    budgetMonth?: string;
    subcategoryId?: number;
    subcategoryName?: string;
    categoryName?: string;
    plannedCents?: number;
    actualCents?: number;
    percentageUsed?: number;
    thresholdPercent?: number;
  } | null;
  read: boolean;
  readAt: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface NotificationSettings {
  id: number;
  userId: number;
  budgetExceededEnabled: boolean;
  budgetExceededThresholdPercent: number;
  savingsGoalEnabled: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface UpdateNotificationSettingsData {
  budgetExceededEnabled?: boolean;
  budgetExceededThresholdPercent?: number;
  savingsGoalEnabled?: boolean;
}
