import { z } from "zod";

export const expenseSchema = z.object({
  budget_subcategory_id: z.coerce.number().positive("Sélectionnez une sous-catégorie"),
  date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/, "Date invalide"),
  label: z.string().min(1, "Le libellé est requis").max(255, "Le libellé est trop long"),
  amount_cents: z.coerce.number().positive("Le montant doit être supérieur à 0"),
  payment_method: z.string().optional(),
  notes: z.string().optional(),
  tag_ids: z.array(z.number()).optional(),
});

export type ExpenseInput = z.infer<typeof expenseSchema>;
