import * as v from "valibot";

export const expenseSchema = v.object({
  budget_subcategory_id: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(1, "Sélectionnez une sous-catégorie")
  ),
  date: v.pipe(v.string(), v.regex(/^\d{4}-\d{2}-\d{2}$/, "Date invalide")),
  label: v.pipe(
    v.string(),
    v.minLength(1, "Le libellé est requis"),
    v.maxLength(255, "Le libellé est trop long")
  ),
  amount_cents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(1, "Le montant doit être supérieur à 0")
  ),
  payment_method: v.optional(v.string()),
  notes: v.optional(v.string()),
  tag_ids: v.optional(v.array(v.number())),
});

export type ExpenseInput = v.InferOutput<typeof expenseSchema>;
