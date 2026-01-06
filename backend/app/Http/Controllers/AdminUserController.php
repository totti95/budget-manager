<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Liste tous les utilisateurs avec filtres et pagination
     */
    public function index(Request $request)
    {
        $query = User::with('role');

        // Filtre par recherche (nom ou email)
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        // Filtre par rôle
        if ($request->has('role') && ! empty($request->role)) {
            $query->whereHas('role', function ($q) use ($request) {
                $q->where('label', $request->role);
            });
        }

        // Filtre par statut (actif/désactivé/tous)
        if ($request->has('status') && ! empty($request->status)) {
            if ($request->status === 'deleted') {
                $query->onlyTrashed();
            } elseif ($request->status === 'active') {
                $query->whereNull('deleted_at');
            }
        } else {
            // Si status est vide ou absent, afficher tous les utilisateurs (actifs + désactivés)
            $query->withTrashed();
        }

        // Pagination
        $users = $query->paginate(12);

        return response()->json($users);
    }

    /**
     * Créer un nouvel utilisateur avec mot de passe généré
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'roleId' => 'required|exists:roles,id',
        ]);

        // Générer un mot de passe aléatoire
        $password = $this->generateRandomPassword();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($password),
            'role_id' => $request->roleId,
        ]);

        $user->load('role');

        return response()->json([
            'user' => $user,
            'password' => $password, // Retourné une seule fois
        ], 201);
    }

    /**
     * Mettre à jour un utilisateur (nom, email, rôle)
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'sometimes|string|min:2|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'role_id' => 'sometimes|exists:roles,id',
        ]);

        $user->update($request->only(['name', 'email', 'role_id']));
        $user->load('role');

        return response()->json($user);
    }

    /**
     * Changer le mot de passe d'un utilisateur sans demander l'ancien
     */
    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }

    /**
     * Soft delete d'un utilisateur
     */
    public function destroy(User $user)
    {
        // Charger la relation role si elle n'est pas déjà chargée
        $user->load('role');

        // Vérifier que ce n'est pas le dernier admin
        if ($user->isAdmin()) {
            $adminCount = User::whereHas('role', function ($q) {
                $q->where('label', Role::ADMIN);
            })->whereNull('deleted_at')->count();

            if ($adminCount <= 1) {
                return response()->json([
                    'message' => 'Impossible de désactiver le dernier administrateur',
                ], 422);
            }
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur désactivé avec succès',
        ]);
    }

    /**
     * Réactiver un utilisateur désactivé (restore soft delete)
     */
    public function restore(User $user)
    {
        // Vérifier que l'utilisateur est bien désactivé
        if (! $user->trashed()) {
            return response()->json([
                'message' => 'Cet utilisateur est déjà actif',
            ], 422);
        }

        $user->restore();
        $user->load('role');

        return response()->json([
            'user' => $user,
            'message' => 'Utilisateur réactivé avec succès',
        ]);
    }

    /**
     * Générer un mot de passe aléatoire sécurisé
     */
    private function generateRandomPassword(int $length = 12): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*-_+=';

        $allChars = $uppercase . $lowercase . $numbers . $symbols;

        // Garantir au moins un caractère de chaque type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];

        // Compléter avec des caractères aléatoires
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Mélanger les caractères
        return str_shuffle($password);
    }
}
