#!/bin/bash

echo "🔍 Vérification de la base de données Budget Manager"
echo "===================================================="
echo ""

# Vérifier si Docker est lancé
if ! docker compose ps | grep -q "budget_php"; then
    echo "❌ Les conteneurs Docker ne sont pas démarrés"
    echo "👉 Lancez: make up"
    exit 1
fi

echo "📊 Contenu de la base de données:"
echo "=================================="

docker compose exec -T php php artisan tinker --execute="
echo 'Utilisateurs:      ' . str_pad(App\Models\User::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Rôles:             ' . str_pad(App\Models\Role::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Templates:         ' . str_pad(App\Models\BudgetTemplate::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Budgets:           ' . str_pad(App\Models\Budget::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Catégories:        ' . str_pad(App\Models\BudgetCategory::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Dépenses:          ' . str_pad(App\Models\Expense::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Assets:            ' . str_pad(App\Models\Asset::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Tags:              ' . str_pad(App\Models\Tag::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Dépenses récur.:   ' . str_pad(App\Models\RecurringExpense::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Objectifs épargne: ' . str_pad(App\Models\SavingsGoal::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
echo 'Notifications:     ' . str_pad(App\Models\Notification::count(), 3, ' ', STR_PAD_LEFT) . PHP_EOL;
"

echo ""
echo "👥 Comptes utilisateurs:"
echo "========================"

docker compose exec -T php php artisan tinker --execute="
foreach(App\Models\User::with('role')->get() as \$user) {
    \$role = str_pad(\$user->role->label, 5, ' ');
    echo '  [' . \$role . '] ' . str_pad(\$user->email, 35, ' ') . ' - ' . \$user->name . PHP_EOL;
}
"

echo ""
echo "🔐 Comptes de test disponibles:"
echo "================================"
echo "  Admin: admin@budgetmanager.local / admin123"
echo "  Demo:  demo@budgetmanager.local  / password"
echo ""

# Test de connexion
echo "🧪 Test de connexion API:"
echo "=========================="

RESPONSE=$(curl -s -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@budgetmanager.local","password":"password"}')

if echo "$RESPONSE" | jq -e '.token' > /dev/null 2>&1; then
    echo "✅ Connexion API fonctionnelle"
    TOKEN=$(echo "$RESPONSE" | jq -r '.token')
    echo "   Token: ${TOKEN:0:20}..."
else
    echo "❌ Échec de connexion à l'API"
    echo "$RESPONSE" | jq '.'
fi

echo ""
echo "✅ Vérification terminée !"
