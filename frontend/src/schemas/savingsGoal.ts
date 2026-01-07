import * as v from "valibot";

// Schéma pour créer/modifier un objectif d'épargne
export const savingsGoalSchema = v.object({
  assetId: v.optional(v.nullable(v.pipe(v.number(), v.minValue(1)))),
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom de l'objectif est requis"),
    v.maxLength(255, "Le nom est trop long")
  ),
  description: v.optional(v.nullable(v.string())),
  targetAmountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(1, "Le montant cible doit être supérieur à 0")
  ),
  startDate: v.pipe(
    v.string(),
    v.regex(/^\d{4}-\d{2}-\d{2}$/, "Format de date invalide (YYYY-MM-DD attendu)")
  ),
  targetDate: v.optional(
    v.nullable(
      v.pipe(
        v.string(),
        v.regex(/^\d{4}-\d{2}-\d{2}$/, "Format de date invalide (YYYY-MM-DD attendu)")
      )
    )
  ),
  status: v.optional(v.picklist(["active", "completed", "abandoned", "paused"], "Statut invalide")),
  priority: v.optional(
    v.pipe(
      v.number(),
      v.minValue(0, "La priorité ne peut pas être négative"),
      v.maxValue(10, "La priorité maximale est 10")
    )
  ),
  notifyMilestones: v.optional(v.boolean()),
  notifyRisk: v.optional(v.boolean()),
  notifyReminder: v.optional(v.boolean()),
  reminderDayOfMonth: v.optional(
    v.nullable(
      v.pipe(
        v.number(),
        v.minValue(1, "Le jour du mois doit être entre 1 et 31"),
        v.maxValue(31, "Le jour du mois doit être entre 1 et 31")
      )
    )
  ),
});

// Schéma pour créer une contribution à un objectif d'épargne
export const savingsGoalContributionSchema = v.object({
  savingsGoalId: v.optional(v.pipe(v.number(), v.minValue(1))),
  amountCents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(1, "Le montant de la contribution doit être supérieur à 0")
  ),
  contributionDate: v.pipe(
    v.string(),
    v.regex(/^\d{4}-\d{2}-\d{2}$/, "Format de date invalide (YYYY-MM-DD attendu)")
  ),
  note: v.optional(v.nullable(v.string())),
});

// Types inférés
export type SavingsGoalInput = v.InferOutput<typeof savingsGoalSchema>;
export type SavingsGoalContributionInput = v.InferOutput<typeof savingsGoalContributionSchema>;
