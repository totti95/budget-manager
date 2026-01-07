import * as v from "valibot";

export const createUserSchema = v.object({
  name: v.pipe(v.string(), v.minLength(2, "Le nom doit contenir au moins 2 caractères")),
  email: v.pipe(v.string(), v.email("L'email est invalide")),
  roleId: v.pipe(v.number(), v.minValue(1, "Le rôle est requis")),
});

export const updateUserSchema = v.object({
  name: v.optional(v.pipe(v.string(), v.minLength(2, "Le nom doit contenir au moins 2 caractères"))),
  email: v.optional(v.pipe(v.string(), v.email("L'email est invalide"))),
  roleId: v.optional(v.pipe(v.number(), v.minValue(1, "Le rôle est requis"))),
});

export const updatePasswordSchema = v.pipe(
  v.object({
    password: v.pipe(v.string(), v.minLength(8, "Le mot de passe doit contenir au moins 8 caractères")),
    password_confirmation: v.string(),
  }),
  v.forward(
    v.partialCheck(
      [["password"], ["password_confirmation"]],
      (input) => input.password === input.password_confirmation,
      "Les mots de passe ne correspondent pas"
    ),
    ["password_confirmation"]
  )
);

export type CreateUserFormData = v.InferOutput<typeof createUserSchema>;
export type UpdateUserFormData = v.InferOutput<typeof updateUserSchema>;
export type UpdatePasswordFormData = v.InferOutput<typeof updatePasswordSchema>;
