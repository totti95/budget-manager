import * as v from "valibot";

// Schéma pour créer/modifier un template
export const budgetTemplateSchema = v.object({
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom du template est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  isDefault: v.optional(v.boolean()),
  revenueCents: v.optional(
    v.pipe(
      v.union([v.string(), v.number()]),
      v.transform(Number),
      v.number(),
      v.minValue(0, "Le revenu ne peut pas être négatif")
    )
  ),
});

// Schéma pour créer/modifier une catégorie de template
export const templateCategorySchema = v.object({
  budgetTemplateId: v.optional(v.pipe(v.number(), v.minValue(1))),
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom de la catégorie est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  sortOrder: v.optional(v.pipe(v.number(), v.minValue(0))),
  plannedAmountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(0, "Le montant ne peut pas être négatif")
  ),
});

// Schéma pour créer/modifier une sous-catégorie de template
export const templateSubcategorySchema = v.object({
  templateCategoryId: v.optional(v.pipe(v.number(), v.minValue(1))),
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom de la sous-catégorie est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  plannedAmountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(0, "Le montant ne peut pas être négatif")
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
export type BudgetTemplateInput = v.InferOutput<typeof budgetTemplateSchema>;
export type TemplateCategoryInput = v.InferOutput<typeof templateCategorySchema>;
export type TemplateSubcategoryInput = v.InferOutput<typeof templateSubcategorySchema>;
