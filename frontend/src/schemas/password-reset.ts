import * as v from "valibot";

export const forgotPasswordSchema = v.object({
  email: v.pipe(v.string(), v.email("Email invalide")),
});

export const resetPasswordSchema = v.pipe(
  v.object({
    email: v.pipe(v.string(), v.email("Email invalide")),
    token: v.pipe(v.string(), v.minLength(1, "Token requis")),
    password: v.pipe(
      v.string(),
      v.minLength(8, "Le mot de passe doit contenir au moins 8 caractères")
    ),
    passwordConfirmation: v.string(),
  }),
  v.forward(
    v.partialCheck(
      [["password"], ["passwordConfirmation"]],
      (input) => input.password === input.passwordConfirmation,
      "Les mots de passe ne correspondent pas"
    ),
    ["passwordConfirmation"]
  )
);

export type ForgotPasswordInput = v.InferOutput<typeof forgotPasswordSchema>;
export type ResetPasswordInput = v.InferOutput<typeof resetPasswordSchema>;
