import * as v from "valibot";

export const assetSchema = v.object({
  type: v.pipe(v.string(), v.minLength(1, "Le type est requis")),
  is_liability: v.optional(v.boolean()),
  label: v.pipe(v.string(), v.minLength(1, "Le libellé est requis"), v.maxLength(255)),
  institution: v.optional(v.pipe(v.string(), v.maxLength(255))),
  value_cents: v.pipe(
    v.union([v.string(), v.number()]),
    v.transform(Number),
    v.number(),
    v.minValue(0, "La valeur doit être positive")
  ),
  notes: v.optional(v.string()),
});

export type AssetInput = v.InferOutput<typeof assetSchema>;
