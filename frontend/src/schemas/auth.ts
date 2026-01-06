import { z } from "zod";

export const loginSchema = z.object({
  email: z.email(),
  password: z.string().min(1, "Le mot de passe est requis"),
});

export const registerSchema = z
  .object({
    name: z.string().min(2, "Le nom doit contenir au moins 2 caractères"),
    email: z.email(),
    password: z.string().min(8, "Le mot de passe doit contenir au moins 8 caractères"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Les mots de passe ne correspondent pas",
    path: ["password_confirmation"],
  });

export type LoginInput = z.infer<typeof loginSchema>;
export type RegisterInput = z.infer<typeof registerSchema>;
