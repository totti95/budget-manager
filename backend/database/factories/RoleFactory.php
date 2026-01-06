<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'label' => Role::USER,
            'description' => 'Utilisateur standard',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'label' => Role::ADMIN,
            'description' => 'Administrateur',
        ]);
    }
}
