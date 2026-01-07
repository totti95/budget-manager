import * as v from "valibot";

export const tagSchema = v.object({
  name: v.pipe(
    v.string(),
    v.minLength(1, "Le nom du tag est requis"),
    v.maxLength(100, "Le nom est trop long (max 100 caractères)")
  ),
  color: v.optional(
    v.pipe(v.string(), v.regex(/^#[0-9A-Fa-f]{6}$/, "Couleur invalide (format #RRGGBB attendu)"))
  ),
});

export type TagInput = v.InferOutput<typeof tagSchema>;
