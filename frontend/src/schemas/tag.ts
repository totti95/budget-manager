import { z } from "zod";

export const tagSchema = z.object({
  name: z
    .string()
    .min(1, "Le nom du tag est requis")
    .max(100, "Le nom est trop long (max 100 caractères)"),
  color: z
    .string()
    .regex(/^#[0-9A-Fa-f]{6}$/, "Couleur invalide (format #RRGGBB attendu)")
    .optional(),
});

export type TagInput = z.infer<typeof tagSchema>;
