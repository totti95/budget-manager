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
  revenueCents?: number;
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
  revenueCents?: number;
  savingsRatePercent?: number;
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

export interface Tag {
  id: number;
  userId: number;
  name: string;
  color: string;
  createdAt: string;
  updatedAt: string;
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
  tags?: Tag[];
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

export interface TagStats {
  tagId: number;
  tagName: string;
  tagColor: string;
  totalAmountCents: number;
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
  type:
    | "budget_exceeded"
    | "savings_goal_reached"
    | "savings_goal_milestone"
    | "savings_goal_risk"
    | "savings_goal_reminder";
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
    goalId?: number;
    milestone?: number;
    currentAmountCents?: number;
    targetAmountCents?: number;
    progressPercentage?: number;
    timeProgressPercentage?: number;
    deficitPercentage?: number;
    suggestedAmountCents?: number;
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
  savingsGoalMilestoneEnabled: boolean;
  savingsGoalRiskEnabled: boolean;
  savingsGoalReminderEnabled: boolean;
  createdAt: string;
  updatedAt: string;
}

export interface UpdateNotificationSettingsData {
  budgetExceededEnabled?: boolean;
  budgetExceededThresholdPercent?: number;
  savingsGoalEnabled?: boolean;
  savingsGoalMilestoneEnabled?: boolean;
  savingsGoalRiskEnabled?: boolean;
  savingsGoalReminderEnabled?: boolean;
}

export interface CategoryComparisonStats {
  name: string;
  plannedCents: number;
  actualCents: number;
  varianceCents: number;
  variancePercent: number;
}

export interface BudgetComparisonStats {
  totalPlannedCents: number;
  totalActualCents: number;
  varianceCents: number;
  variancePercent: number;
  byCategory: CategoryComparisonStats[];
}

export interface BudgetWithStats extends Budget {
  stats?: BudgetComparisonStats;
}

export interface CategoryEvolution {
  categoryName: string;
  values: number[];
  evolutionPercent: number;
}

export interface BudgetComparison {
  budgets: BudgetWithStats[];
  comparison: {
    evolution: CategoryEvolution[];
  };
}

export interface SavingsGoal {
  id: number;
  userId: number;
  assetId: number | null;
  name: string;
  description: string | null;
  targetAmountCents: number;
  currentAmountCents: number;
  startDate: string;
  targetDate: string | null;
  status: "active" | "completed" | "abandoned" | "paused";
  priority: number;
  notifyMilestones: boolean;
  notifyRisk: boolean;
  notifyReminder: boolean;
  reminderDayOfMonth: number | null;
  suggestedMonthlyAmountCents: number | null;
  createdAt: string;
  updatedAt: string;

  // Relations (optionnelles)
  asset?: Asset;
  contributions?: SavingsGoalContribution[];

  // Computed attributes (retournés par l'API)
  progressPercentage?: number;
  daysRemaining?: number;
  timeProgressPercentage?: number;
  isOnTrack?: boolean;
}

export interface SavingsGoalContribution {
  id: number;
  savingsGoalId: number;
  userId: number;
  amountCents: number;
  contributionDate: string;
  note: string | null;
  createdAt: string;
  updatedAt: string;
}

export interface CreateSavingsGoalData {
  assetId?: number | null;
  name: string;
  description?: string | null;
  targetAmountCents: number;
  startDate: string;
  targetDate?: string | null;
  priority?: number;
  notifyMilestones?: boolean;
  notifyRisk?: boolean;
  notifyReminder?: boolean;
  reminderDayOfMonth?: number | null;
  suggestedMonthlyAmountCents?: number | null;
}

export interface UpdateSavingsGoalData
  extends Partial<CreateSavingsGoalData> {
  status?: "active" | "completed" | "abandoned" | "paused";
}

export interface CreateContributionData {
  amountCents: number;
  contributionDate: string;
  note?: string | null;
}

// Widget System Types
export type WidgetType =
  | "current-month-summary"
  | "top-5-categories"
  | "asset-evolution"
  | "savings-rate"
  | "expense-distribution";

export interface WidgetLayoutItem {
  i: WidgetType;
  x: number;
  y: number;
  w: number;
  h: number;
  minW?: number;
  minH?: number;
  maxW?: number;
  maxH?: number;
}

export interface WidgetSettings {
  [widgetId: string]: Record<string, any>;
}

export interface DashboardLayout {
  id?: number;
  userId?: number;
  layoutConfig: WidgetLayoutItem[];
  widgetSettings: WidgetSettings;
  createdAt?: string;
  updatedAt?: string;
}

// Top Categories
export interface TopCategoryStats {
  id: number;
  name: string;
  actualCents: number;
  plannedCents: number;
  varianceCents: number;
  expenseCount: number;
}

// Savings Rate
export interface SavingsRateDataPoint {
  month: string;
  monthLabel: string;
  revenueCents: number;
  expensesCents: number;
  savingsCents: number;
  savingsRatePercent: number | null;
}

// Widget Props Base
export interface BaseWidgetProps {
  loading?: boolean;
  onRefresh?: () => void;
}
