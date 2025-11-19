import { z } from "zod";

export const assetSchema = z.object({
  type: z.string().min(1, "Le type est requis"),
  is_liability: z.boolean().optional(),
  label: z.string().min(1, "Le libellé est requis").max(255),
  institution: z.string().max(255).optional(),
  value_cents: z.coerce.number().min(0, "La valeur doit être positive"),
  notes: z.string().optional(),
});

export type AssetInput = z.infer<typeof assetSchema>;
