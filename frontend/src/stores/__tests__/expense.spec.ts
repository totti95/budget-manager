import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useExpenseStore } from "../expense";
import * as expensesApi from "@/api/expenses";
import type { Expense } from "@/types";

vi.mock("@/api/expenses");

describe("Expense Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it("should initialize with empty state", () => {
    const store = useExpenseStore();
    expect(store.expenses).toEqual([]);
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it("should fetch expenses successfully", async () => {
    const mockExpenses: Expense[] = [
      {
        id: 1,
        budgetSubcategoryId: 1,
        date: "2024-01-15",
        label: "Courses",
        amountCents: 5000,
        paymentMethod: "Carte",
        notes: null,
        createdAt: "2024-01-15T00:00:00.000Z",
        updatedAt: "2024-01-15T00:00:00.000Z",
      },
    ];

    const mockResponse = {
      data: mockExpenses,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 1,
      },
    };

    vi.mocked(expensesApi.expensesApi.list).mockResolvedValue(mockResponse);

    const store = useExpenseStore();
    await store.fetchExpenses(1);

    expect(store.expenses).toEqual(mockExpenses);
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it("should handle fetch expenses error", async () => {
    const errorMessage = "Network error";
    vi.mocked(expensesApi.expensesApi.list).mockRejectedValue(new Error(errorMessage));

    const store = useExpenseStore();

    try {
      await store.fetchExpenses(1);
    } catch (err) {
      // Expected to throw
    }

    expect(store.expenses).toEqual([]);
    expect(store.loading).toBe(false);
    expect(store.error).toBe("Erreur lors du chargement des dépenses");
  });

  it("should create expense", async () => {
    const newExpense: Expense = {
      id: 2,
      budgetSubcategoryId: 1,
      date: "2024-01-16",
      label: "Restaurant",
      amountCents: 3500,
      paymentMethod: "Espèces",
      notes: null,
      createdAt: "2024-01-16T00:00:00.000Z",
      updatedAt: "2024-01-16T00:00:00.000Z",
    };

    vi.mocked(expensesApi.expensesApi.create).mockResolvedValue(newExpense);

    const store = useExpenseStore();
    const result = await store.createExpense(1, {
      budgetSubcategoryId: 1,
      date: "2024-01-16",
      label: "Restaurant",
      amountCents: 3500,
      paymentMethod: "Espèces",
    });

    expect(result).toEqual(newExpense);
    expect(expensesApi.expensesApi.create).toHaveBeenCalledWith(1, {
      budgetSubcategoryId: 1,
      date: "2024-01-16",
      label: "Restaurant",
      amountCents: 3500,
      paymentMethod: "Espèces",
    });
  });

  it("should update expense", async () => {
    const updatedExpense: Expense = {
      id: 1,
      budgetSubcategoryId: 1,
      date: "2024-01-15",
      label: "Courses Carrefour",
      amountCents: 6000,
      paymentMethod: "Carte",
      notes: "Courses du weekend",
      createdAt: "2024-01-15T00:00:00.000Z",
      updatedAt: "2024-01-15T12:00:00.000Z",
    };

    vi.mocked(expensesApi.expensesApi.update).mockResolvedValue(updatedExpense);

    const store = useExpenseStore();
    const result = await store.updateExpense(1, {
      label: "Courses Carrefour",
      amountCents: 6000,
      notes: "Courses du weekend",
    });

    expect(result).toEqual(updatedExpense);
  });

  it("should delete expense", async () => {
    const mockExpenses: Expense[] = [
      {
        id: 1,
        budgetSubcategoryId: 1,
        date: "2024-01-15",
        label: "Courses",
        amountCents: 5000,
        paymentMethod: "Carte",
        notes: null,
        createdAt: "2024-01-15T00:00:00.000Z",
        updatedAt: "2024-01-15T00:00:00.000Z",
      },
      {
        id: 2,
        budgetSubcategoryId: 1,
        date: "2024-01-16",
        label: "Restaurant",
        amountCents: 3500,
        paymentMethod: "Espèces",
        notes: null,
        createdAt: "2024-01-16T00:00:00.000Z",
        updatedAt: "2024-01-16T00:00:00.000Z",
      },
    ];

    const store = useExpenseStore();
    store.expenses = mockExpenses;

    vi.mocked(expensesApi.expensesApi.delete).mockResolvedValue();

    await store.deleteExpense(1);

    expect(store.expenses).toHaveLength(1);
    expect(store.expenses[0].id).toBe(2);
  });

  it("should filter expenses by subcategory", async () => {
    const mockExpenses: Expense[] = [
      {
        id: 1,
        budgetSubcategoryId: 1,
        date: "2024-01-15",
        label: "Courses",
        amountCents: 5000,
        paymentMethod: "Carte",
        notes: null,
        createdAt: "2024-01-15T00:00:00.000Z",
        updatedAt: "2024-01-15T00:00:00.000Z",
      },
    ];

    const mockResponse = {
      data: mockExpenses,
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 50,
        total: 1,
      },
    };

    vi.mocked(expensesApi.expensesApi.list).mockResolvedValue(mockResponse);

    const store = useExpenseStore();
    await store.fetchExpenses(1, { subcatId: 1 });

    expect(expensesApi.expensesApi.list).toHaveBeenCalledWith(1, { subcatId: 1 });
    expect(store.expenses).toEqual(mockExpenses);
  });
});
