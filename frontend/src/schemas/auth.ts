import * as v from "valibot";

export const loginSchema = v.object({
  email: v.pipe(v.string(), v.email("L'email est invalide")),
  password: v.pipe(v.string(), v.minLength(1, "Le mot de passe est requis")),
});

export const registerSchema = v.pipe(
  v.object({
    name: v.pipe(v.string(), v.minLength(2, "Le nom doit contenir au moins 2 caractères")),
    email: v.pipe(v.string(), v.email("L'email est invalide")),
    password: v.pipe(
      v.string(),
      v.minLength(8, "Le mot de passe doit contenir au moins 8 caractères")
    ),
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

export type LoginInput = v.InferOutput<typeof loginSchema>;
export type RegisterInput = v.InferOutput<typeof registerSchema>;
