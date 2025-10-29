import { z } from "zod";

export const forgotPasswordSchema = z.object({
  email: z.string().email("Email invalide"),
});

export const resetPasswordSchema = z
  .object({
    email: z.string().email("Email invalide"),
    token: z.string().min(1, "Token requis"),
    password: z
      .string()
      .min(8, "Le mot de passe doit contenir au moins 8 caractères"),
    passwordConfirmation: z.string(),
  })
  .refine((data) => data.password === data.passwordConfirmation, {
    message: "Les mots de passe ne correspondent pas",
    path: ["passwordConfirmation"],
  });

export type ForgotPasswordInput = z.infer<typeof forgotPasswordSchema>;
export type ResetPasswordInput = z.infer<typeof resetPasswordSchema>;
