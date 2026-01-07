import * as v from "valibot";

// Schéma pour créer/modifier une dépense récurrente
export const recurringExpenseSchema = v.object({
  templateSubcategoryId: v.optional(
    v.nullable(v.pipe(v.number(), v.minValue(1, "Sélectionnez une sous-catégorie")))
  ),
  label: v.pipe(
    v.string(),
    v.minLength(1, "Le libellé est requis"),
    v.maxLength(255, "Le libellé est trop long")
  ),
  amountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(1, "Le montant doit être supérieur à 0")
  ),
  frequency: v.picklist(
    ["monthly", "weekly", "yearly"],
    "Fréquence invalide (monthly, weekly ou yearly)"
  ),
  dayOfMonth: v.optional(
    v.nullable(
      v.pipe(
        v.number(),
        v.minValue(1, "Le jour du mois doit être entre 1 et 31"),
        v.maxValue(31, "Le jour du mois doit être entre 1 et 31")
      )
    )
  ),
  dayOfWeek: v.optional(
    v.nullable(
      v.picklist(
        ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"],
        "Jour de la semaine invalide"
      )
    )
  ),
  monthOfYear: v.optional(
    v.nullable(
      v.pipe(
        v.number(),
        v.minValue(1, "Le mois doit être entre 1 et 12"),
        v.maxValue(12, "Le mois doit être entre 1 et 12")
      )
    )
  ),
  autoCreate: v.optional(v.boolean()),
  isActive: v.optional(v.boolean()),
  startDate: v.pipe(
    v.string(),
    v.regex(/^\d{4}-\d{2}-\d{2}$/, "Format de date invalide (YYYY-MM-DD attendu)")
  ),
  endDate: v.optional(
    v.nullable(
      v.pipe(
        v.string(),
        v.regex(/^\d{4}-\d{2}-\d{2}$/, "Format de date invalide (YYYY-MM-DD attendu)")
      )
    )
  ),
  paymentMethod: v.optional(v.nullable(v.string())),
  notes: v.optional(v.nullable(v.string())),
});

// Type inféré
export type RecurringExpenseInput = v.InferOutput<typeof recurringExpenseSchema>;
