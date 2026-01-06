import { z } from "zod";

export const createUserSchema = z.object({
  name: z.string().min(2, "Le nom doit contenir au moins 2 caractères"),
  email: z.email(),
  roleId: z.number().min(1, "Le rôle est requis"),
});

export const updateUserSchema = z.object({
  name: z.string().min(2, "Le nom doit contenir au moins 2 caractères").optional(),
  email: z.email().optional(),
  roleId: z.number().min(1, "Le rôle est requis").optional(),
});

export const updatePasswordSchema = z
  .object({
    password: z.string().min(8, "Le mot de passe doit contenir au moins 8 caractères"),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: "Les mots de passe ne correspondent pas",
    path: ["password_confirmation"],
  });

export type CreateUserFormData = z.infer<typeof createUserSchema>;
export type UpdateUserFormData = z.infer<typeof updateUserSchema>;
export type UpdatePasswordFormData = z.infer<typeof updatePasswordSchema>;
