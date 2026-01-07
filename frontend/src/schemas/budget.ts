import * as v from "valibot";

// Schéma pour générer un budget depuis un template
export const generateBudgetSchema = v.object({
  month: v.pipe(v.string(), v.regex(/^\d{4}-\d{2}$/, "Format de mois invalide (YYYY-MM attendu)")),
  budgetTemplateId: v.pipe(v.number(), v.minValue(1, "Sélectionnez un template de budget")),
});

// Schéma pour modifier un budget
export const updateBudgetSchema = v.object({
  name: v.optional(
    v.pipe(
      v.string(),
      v.minLength(1, "Le nom du budget est requis"),
      v.maxLength(255, "Le nom est trop long")
    )
  ),
  revenueCents: v.optional(
    v.pipe(
      v.union([v.string(), v.number()]),
      v.transform(Number),
      v.number(),
      v.minValue(0, "Le revenu ne peut pas être négatif")
    )
  ),
});

// Schéma pour modifier une catégorie de budget
export const updateBudgetCategorySchema = v.object({
  name: v.optional(
    v.pipe(
      v.string(),
      v.minLength(1, "Le nom de la catégorie est requis"),
      v.maxLength(255, "Le nom est trop long")
    )
  ),
  plannedAmountCents: v.optional(
    v.pipe(
      v.union([v.string(), v.number()]),
      v.transform(Number),
      v.number(),
      v.minValue(0, "Le montant ne peut pas être négatif")
    )
  ),
  sortOrder: v.optional(v.pipe(v.number(), v.minValue(0))),
});

// Schéma pour modifier une sous-catégorie de budget
export const updateBudgetSubcategorySchema = v.object({
  name: v.optional(
    v.pipe(
      v.string(),
      v.minLength(1, "Le nom de la sous-catégorie est requis"),
      v.maxLength(255, "Le nom est trop long")
    )
  ),
  plannedAmountCents: v.optional(
    v.pipe(
      v.union([v.string(), v.number()]),
      v.transform(Number),
      v.number(),
      v.minValue(0, "Le montant ne peut pas être négatif")
    )
  ),
  defaultSpentCents: v.optional(
    v.pipe(
      v.union([v.string(), v.number()]),
      v.transform(Number),
      v.number(),
      v.minValue(0, "Le montant ne peut pas être négatif")
    )
  ),
  sortOrder: v.optional(v.pipe(v.number(), v.minValue(0))),
});

// Types inférés
export type GenerateBudgetInput = v.InferOutput<typeof generateBudgetSchema>;
export type UpdateBudgetInput = v.InferOutput<typeof updateBudgetSchema>;
export type UpdateBudgetCategoryInput = v.InferOutput<typeof updateBudgetCategorySchema>;
export type UpdateBudgetSubcategoryInput = v.InferOutput<typeof updateBudgetSubcategorySchema>;
