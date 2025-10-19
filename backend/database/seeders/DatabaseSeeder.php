<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\BudgetTemplate;
use App\Models\Budget;
use App\Models\Asset;
use App\Models\SavingsPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les rôles (créés par la migration)
        $userRole = Role::where('label', Role::USER)->first();
        $adminRole = Role::where('label', Role::ADMIN)->first();

        // Create admin user
        $admin = User::create([
            'name' => 'Administrateur',
            'email' => 'admin@budgetmanager.local',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id,
        ]);

        $this->command->info('');
        $this->command->info('===========================================');
        $this->command->info('  Compte administrateur créé avec succès  ');
        $this->command->info('===========================================');
        $this->command->info('  Email    : admin@budgetmanager.local');
        $this->command->info('  Password : admin123');
        $this->command->info('===========================================');
        $this->command->info('');

        // Create demo user
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@budgetmanager.local',
            'password' => Hash::make('password'),
            'role_id' => $userRole->id,
        ]);

        // Create default budget template
        $template = $user->budgetTemplates()->create([
            'name' => 'Template Standard',
            'is_default' => true,
        ]);

        // Create template categories with subcategories
        $categoriesData = [
            'Logement' => [
                'planned' => 120000, // 1200€
                'subcategories' => [
                    ['name' => 'Loyer', 'planned' => 80000],
                    ['name' => 'Électricité', 'planned' => 10000],
                    ['name' => 'Eau', 'planned' => 5000],
                    ['name' => 'Internet', 'planned' => 3000],
                    ['name' => 'Assurance habitation', 'planned' => 2000],
                ],
            ],
            'Transports' => [
                'planned' => 20000, // 200€
                'subcategories' => [
                    ['name' => 'Essence', 'planned' => 15000],
                    ['name' => 'Transport en commun', 'planned' => 5000],
                ],
            ],
            'Alimentation' => [
                'planned' => 40000, // 400€
                'subcategories' => [
                    ['name' => 'Courses', 'planned' => 30000],
                    ['name' => 'Marché', 'planned' => 10000],
                ],
            ],
            'Restaurants' => [
                'planned' => 15000, // 150€
                'subcategories' => [
                    ['name' => 'McDo', 'planned' => 5000],
                    ['name' => 'Restaurants', 'planned' => 10000],
                ],
            ],
            'Épargne' => [
                'planned' => 30000, // 300€
                'subcategories' => [
                    ['name' => 'Livret A', 'planned' => 15000],
                    ['name' => 'PEA', 'planned' => 15000],
                ],
            ],
            'Loisirs' => [
                'planned' => 10000, // 100€
                'subcategories' => [
                    ['name' => 'Cinéma', 'planned' => 3000],
                    ['name' => 'Sport', 'planned' => 5000],
                    ['name' => 'Autres loisirs', 'planned' => 2000],
                ],
            ],
            'Divers' => [
                'planned' => 5000, // 50€
                'subcategories' => [
                    ['name' => 'Imprévu', 'planned' => 5000],
                ],
            ],
        ];

        $sortOrder = 0;
        foreach ($categoriesData as $catName => $catData) {
            $category = $template->categories()->create([
                'name' => $catName,
                'planned_amount_cents' => $catData['planned'],
                'sort_order' => $sortOrder++,
            ]);

            $subSortOrder = 0;
            foreach ($catData['subcategories'] as $subData) {
                $category->subcategories()->create([
                    'name' => $subData['name'],
                    'planned_amount_cents' => $subData['planned'],
                    'sort_order' => $subSortOrder++,
                ]);
            }
        }

        // Create budgets for last 3 months
        $months = [
            Carbon::now()->subMonths(2)->startOfMonth(),
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->startOfMonth(),
        ];

        foreach ($months as $month) {
            $budget = $user->budgets()->create([
                'month' => $month,
                'name' => 'Budget ' . $month->isoFormat('MMMM YYYY'),
                'generated_from_template_id' => $template->id,
            ]);

            // Copy categories from template
            foreach ($template->categories as $templateCat) {
                $budgetCat = $budget->categories()->create([
                    'name' => $templateCat->name,
                    'planned_amount_cents' => $templateCat->planned_amount_cents,
                    'sort_order' => $templateCat->sort_order,
                ]);

                foreach ($templateCat->subcategories as $templateSubcat) {
                    $budgetSubcat = $budgetCat->subcategories()->create([
                        'name' => $templateSubcat->name,
                        'planned_amount_cents' => $templateSubcat->planned_amount_cents,
                        'sort_order' => $templateSubcat->sort_order,
                    ]);

                    // Add some random expenses
                    $expenseCount = rand(1, 5);
                    for ($i = 0; $i < $expenseCount; $i++) {
                        $budget->expenses()->create([
                            'budget_subcategory_id' => $budgetSubcat->id,
                            'date' => $month->copy()->addDays(rand(0, 27)),
                            'label' => $this->getRandomExpenseLabel($templateSubcat->name),
                            'amount_cents' => rand(500, $templateSubcat->planned_amount_cents / 2),
                            'payment_method' => $this->getRandomPaymentMethod(),
                            'notes' => rand(0, 1) ? 'Note de test' : null,
                        ]);
                    }
                }
            }

            // Create savings plan
            $user->savingsPlans()->create([
                'month' => $month,
                'planned_cents' => 30000,
            ]);
        }

        // Create assets
        $user->assets()->createMany([
            [
                'type' => 'épargne',
                'label' => 'Compte Courant',
                'institution' => 'Crédit Agricole',
                'value_cents' => 350000, // 3500€
                'notes' => 'Compte principal',
            ],
            [
                'type' => 'épargne',
                'label' => 'Livret A',
                'institution' => 'Crédit Agricole',
                'value_cents' => 1500000, // 15000€
                'notes' => 'Épargne de précaution',
            ],
            [
                'type' => 'investissement',
                'label' => 'PEA',
                'institution' => 'Boursorama',
                'value_cents' => 2500000, // 25000€
                'notes' => 'Investissements long terme',
            ],
            [
                'type' => 'immobilier',
                'label' => 'Appartement',
                'institution' => null,
                'value_cents' => 20000000, // 200000€
                'notes' => 'Résidence principale',
            ],
        ]);
    }

    private function getRandomExpenseLabel(string $subcategory): string
    {
        $labels = [
            'Loyer' => ['Loyer ' . Carbon::now()->format('m/Y')],
            'Courses' => ['Carrefour', 'Leclerc', 'Auchan', 'Lidl'],
            'Essence' => ['Station Total', 'Station BP', 'Station Esso'],
            'Restaurants' => ['Restaurant Le Bon Coin', 'Pizzeria', 'Sushi Bar'],
            'McDo' => ['McDonald\'s', 'Quick', 'Burger King'],
            'Cinéma' => ['UGC', 'Pathé', 'Gaumont'],
        ];

        if (isset($labels[$subcategory])) {
            return $labels[$subcategory][array_rand($labels[$subcategory])];
        }

        return 'Dépense ' . $subcategory;
    }

    private function getRandomPaymentMethod(): string
    {
        $methods = ['CB', 'Espèces', 'Virement', 'Prélèvement'];
        return $methods[array_rand($methods)];
    }
}
