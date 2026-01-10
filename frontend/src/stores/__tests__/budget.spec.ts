import { describe, it, expect, beforeEach, vi } from "vitest";
import { setActivePinia, createPinia } from "pinia";
import { useBudgetStore } from "../budget";
import * as budgetsApi from "@/api/budgets";
import type { Budget } from "@/types";

vi.mock("@/api/budgets");

describe("Budget Store", () => {
  beforeEach(() => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
  });

  it("should initialize with empty state", () => {
    const store = useBudgetStore();
    expect(store.budgets).toEqual([]);
    expect(store.currentBudget).toBeNull();
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it("should fetch budgets successfully", async () => {
    const mockBudgets: Budget[] = [
      {
        id: 1,
        userId: 1,
        month: "2024-01",
        name: "Budget Janvier 2024",
        revenueCents: 300000,
        generatedFromTemplateId: 1,
        createdAt: "2024-01-01T00:00:00.000Z",
        updatedAt: "2024-01-01T00:00:00.000Z",
      },
    ];

    vi.mocked(budgetsApi.budgetsApi.list).mockResolvedValue({
      data: mockBudgets,
      total: 1,
    });

    const store = useBudgetStore();
    await store.fetchBudgets();

    expect(store.budgets).toEqual(mockBudgets);
    expect(store.loading).toBe(false);
    expect(store.error).toBeNull();
  });

  it("should handle fetch budgets error", async () => {
    const errorMessage = "Network error";
    vi.mocked(budgetsApi.budgetsApi.list).mockRejectedValue(new Error(errorMessage));

    const store = useBudgetStore();

    try {
      await store.fetchBudgets();
    } catch (err) {
      // Expected to throw
    }

    expect(store.budgets).toEqual([]);
    expect(store.loading).toBe(false);
    expect(store.error).toBe("Erreur lors du chargement des budgets");
  });

  it("should fetch budgets filtered by month", async () => {
    const mockBudget: Budget = {
      id: 1,
      userId: 1,
      month: "2024-01",
      name: "Budget Janvier 2024",
      revenueCents: 300000,
      generatedFromTemplateId: 1,
      createdAt: "2024-01-01T00:00:00.000Z",
      updatedAt: "2024-01-01T00:00:00.000Z",
    };

    vi.mocked(budgetsApi.budgetsApi.list).mockResolvedValue({
      data: [mockBudget],
      total: 1,
    });

    const store = useBudgetStore();
    await store.fetchBudgets("2024-01");

    expect(budgetsApi.budgetsApi.list).toHaveBeenCalledWith("2024-01");
    expect(store.budgets).toEqual([mockBudget]);
  });

  it("should generate budget from template", async () => {
    const mockBudget: Budget = {
      id: 2,
      userId: 1,
      month: "2024-02",
      name: "Budget Février 2024",
      revenueCents: 300000,
      generatedFromTemplateId: 1,
      createdAt: "2024-02-01T00:00:00.000Z",
      updatedAt: "2024-02-01T00:00:00.000Z",
    };

    vi.mocked(budgetsApi.budgetsApi.generate).mockResolvedValue(mockBudget);

    const store = useBudgetStore();
    const result = await store.generateBudget("2024-02");

    expect(result).toEqual(mockBudget);
    expect(budgetsApi.budgetsApi.generate).toHaveBeenCalledWith("2024-02");
  });

  it("should update budget", async () => {
    const updatedBudget: Budget = {
      id: 1,
      userId: 1,
      month: "2024-01",
      name: "Budget Janvier Modifié",
      revenueCents: 350000,
      generatedFromTemplateId: 1,
      createdAt: "2024-01-01T00:00:00.000Z",
      updatedAt: "2024-01-01T00:00:00.000Z",
    };

    vi.mocked(budgetsApi.budgetsApi.update).mockResolvedValue(updatedBudget);

    const store = useBudgetStore();
    const result = await store.updateBudget(1, {
      name: "Budget Janvier Modifié",
      revenueCents: 350000,
    });

    expect(result).toEqual(updatedBudget);
    expect(budgetsApi.budgetsApi.update).toHaveBeenCalledWith(1, {
      name: "Budget Janvier Modifié",
      revenueCents: 350000,
    });
  });

  it("should delete budget", async () => {
    const mockBudgets: Budget[] = [
      {
        id: 1,
        userId: 1,
        month: "2024-01",
        name: "Budget Janvier 2024",
        revenueCents: 300000,
        generatedFromTemplateId: 1,
        createdAt: "2024-01-01T00:00:00.000Z",
        updatedAt: "2024-01-01T00:00:00.000Z",
      },
      {
        id: 2,
        userId: 1,
        month: "2024-02",
        name: "Budget Février 2024",
        revenueCents: 300000,
        generatedFromTemplateId: 1,
        createdAt: "2024-02-01T00:00:00.000Z",
        updatedAt: "2024-02-01T00:00:00.000Z",
      },
    ];

    const store = useBudgetStore();
    store.budgets = mockBudgets;

    vi.mocked(budgetsApi.budgetsApi.delete).mockResolvedValue();

    await store.deleteBudget(1);

    expect(store.budgets).toHaveLength(1);
    expect(store.budgets[0].id).toBe(2);
    expect(budgetsApi.budgetsApi.delete).toHaveBeenCalledWith(1);
  });
});
