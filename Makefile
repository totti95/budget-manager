.PHONY: help init up down migrate seed test fresh build logs clean

help: ## Show this help
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

init: ## Initialize the project (first time setup)
	@echo "🚀 Initializing Budget Manager..."
	@docker compose build
	@docker compose up -d postgres redis
	@sleep 5
	@docker compose run --rm php composer install
	@docker compose run --rm php cp .env.example .env
	@docker compose run --rm php php artisan key:generate
	@docker compose run --rm node npm install
	@$(MAKE) migrate
	@$(MAKE) seed
	@echo "✅ Initialization complete! Run 'make up' to start the application."

up: ## Start all containers
	@docker compose up -d
	@echo "✅ Application started!"
	@echo "📍 API: http://localhost:8080/api"
	@echo "📍 Frontend: http://localhost:5173"
	@echo "📍 Mailhog: http://localhost:8025"

down: ## Stop all containers
	@docker compose down

migrate: ## Run database migrations
	@docker compose run --rm php php artisan migrate

seed: ## Seed database with demo data
	@docker compose run --rm php php artisan db:seed

test: ## Run tests
	@docker compose run --rm php php artisan test

fresh: ## Fresh migration with seed
	@docker compose run --rm php php artisan migrate:fresh --seed

build: ## Build frontend for production
	@docker compose run --rm node npm run build

logs: ## Show logs
	@docker compose logs -f

clean: ## Clean up containers and volumes
	@docker compose down -v
	@echo "✅ Cleaned up!"

install-backend: ## Install backend dependencies
	@docker compose run --rm php composer install

install-frontend: ## Install frontend dependencies
	@docker compose run --rm node npm install

shell-php: ## Open PHP container shell
	@docker compose exec php sh

shell-node: ## Open Node container shell
	@docker compose exec node sh

artisan: ## Run artisan command (use: make artisan CMD="migrate")
	@docker compose run --rm php php artisan $(CMD)

npm: ## Run npm command (use: make npm CMD="install package")
	@docker compose run --rm node npm $(CMD)
