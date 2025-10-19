#!/bin/bash

# Script de gestion des données de test pour Budget Manager
# Usage: ./reset-data.sh [option]

set -e

echo "🔄 Budget Manager - Gestion des données de test"
echo "================================================"
echo ""

case "${1:-help}" in
  fresh|reset)
    echo "📊 Génération de données fraîches..."
    echo ""
    docker compose exec php php artisan migrate:fresh --seed
    echo ""
    echo "✅ Données de test générées avec succès !"
    echo ""
    echo "🔑 Compte démo :"
    echo "   Email    : demo@budgetmanager.local"
    echo "   Password : password"
    ;;

  seed)
    echo "📊 Ajout de données de test (sans reset)..."
    echo ""
    docker compose exec php php artisan db:seed
    echo ""
    echo "✅ Données ajoutées !"
    echo "⚠️  Attention : des doublons peuvent exister"
    ;;

  wipe)
    echo "🗑️  Suppression de toutes les données..."
    echo ""
    read -p "⚠️  Êtes-vous sûr ? Cette action est irréversible. [y/N] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
      docker compose exec php php artisan db:wipe
      echo ""
      echo "✅ Base de données vidée"
      echo "💡 Utilisez './reset-data.sh migrate' pour recréer les tables"
    else
      echo "❌ Annulé"
    fi
    ;;

  migrate)
    echo "🏗️  Création des tables (sans données)..."
    echo ""
    docker compose exec php php artisan migrate
    echo ""
    echo "✅ Tables créées"
    echo "💡 Utilisez './reset-data.sh seed' pour ajouter des données"
    ;;

  status)
    echo "📊 État de la base de données"
    echo ""
    echo "=== Nombre d'enregistrements ==="
    echo ""
    echo -n "👤 Users      : "
    docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -sN -e "SELECT COUNT(*) FROM users;" 2>/dev/null || echo "Erreur"
    echo -n "📋 Templates  : "
    docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -sN -e "SELECT COUNT(*) FROM budget_templates;" 2>/dev/null || echo "Erreur"
    echo -n "📅 Budgets    : "
    docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -sN -e "SELECT COUNT(*) FROM budgets;" 2>/dev/null || echo "Erreur"
    echo -n "💸 Expenses   : "
    docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -sN -e "SELECT COUNT(*) FROM expenses;" 2>/dev/null || echo "Erreur"
    echo -n "💰 Assets     : "
    docker compose exec mysql mysql -u budget_user -pbudget_pass budget_manager -sN -e "SELECT COUNT(*) FROM assets;" 2>/dev/null || echo "Erreur"
    echo ""
    echo "💡 Vous pouvez aussi consulter les données sur phpMyAdmin : http://localhost:8081"
    echo ""
    ;;

  backup)
    BACKUP_FILE="backup_$(date +%Y%m%d_%H%M%S).sql"
    echo "💾 Création d'un backup : $BACKUP_FILE"
    echo ""
    docker compose exec mysql mysqldump -u budget_user -pbudget_pass budget_manager > "$BACKUP_FILE"
    echo ""
    echo "✅ Backup créé : $BACKUP_FILE"
    echo "💡 Pour restaurer : ./reset-data.sh restore $BACKUP_FILE"
    ;;

  restore)
    if [ -z "$2" ]; then
      echo "❌ Erreur : fichier de backup manquant"
      echo "Usage: ./reset-data.sh restore backup_file.sql"
      exit 1
    fi
    if [ ! -f "$2" ]; then
      echo "❌ Erreur : fichier '$2' introuvable"
      exit 1
    fi
    echo "📥 Restauration depuis : $2"
    echo ""
    read -p "⚠️  Cela écrasera les données actuelles. Continuer ? [y/N] " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
      docker compose exec -T mysql mysql -u budget_user -pbudget_pass budget_manager < "$2"
      echo ""
      echo "✅ Backup restauré"
    else
      echo "❌ Annulé"
    fi
    ;;

  help|*)
    echo "Usage: ./reset-data.sh [commande]"
    echo ""
    echo "Commandes disponibles :"
    echo ""
    echo "  fresh, reset   Générer des données de test fraîches (reset complet)"
    echo "  seed           Ajouter des données sans reset (peut créer doublons)"
    echo "  wipe           Vider toutes les données (demande confirmation)"
    echo "  migrate        Créer les tables sans données"
    echo "  status         Afficher l'état de la base de données"
    echo "  backup         Créer un backup de la base de données"
    echo "  restore FILE   Restaurer depuis un fichier de backup"
    echo "  help           Afficher cette aide"
    echo ""
    echo "Exemples :"
    echo "  ./reset-data.sh fresh              # Reset complet avec nouvelles données"
    echo "  ./reset-data.sh status             # Voir combien de données existent"
    echo "  ./reset-data.sh backup             # Sauvegarder avant modifications"
    echo "  ./reset-data.sh restore backup.sql # Restaurer un backup"
    echo ""
    echo "💡 Pour plus de détails, consultez DONNEES_TEST.md"
    ;;
esac
