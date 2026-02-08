.PHONY: help staging-up staging-down staging-restart staging-logs staging-bash staging-seed staging-fresh staging-test staging-db-sync

help:
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║           QR Made Armando - Docker Staging Commands             ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""
	@echo "Staging Management:"
	@echo "  make staging-up             Start staging environment"
	@echo "  make staging-down           Stop staging environment"
	@echo "  make staging-restart        Restart all services"
	@echo "  make staging-fresh          Fresh staging (clean DB)"
	@echo "  make staging-logs           Follow staging logs"
	@echo ""
	@echo "Database Operations:"
	@echo "  make staging-seed           Seed database with test data"
	@echo "  make staging-migrate        Run migrations only"
	@echo "  make staging-db-sync        Sync production data to staging"
	@echo ""
	@echo "Utilities:"
	@echo "  make staging-bash           Enter PHP container shell"
	@echo "  make staging-test           Run all tests"
	@echo "  make staging-tinker         Enter Laravel Tinker REPL"
	@echo "  make staging-db-shell       Enter PostgreSQL shell"
	@echo ""

staging-up:
	@echo "Starting staging environment..."
	docker-compose up -d
	@echo "✓ Staging environment started"
	@echo "  URL: http://localhost"
	@echo "  API: http://localhost/api"
	@echo "  Admin: http://localhost/admin"

staging-down:
	@echo "Stopping staging environment..."
	docker-compose down
	@echo "✓ Staging environment stopped"

staging-restart:
	@echo "Restarting all services..."
	docker-compose restart
	@echo "✓ All services restarted"

staging-logs:
	docker-compose logs -f

staging-bash:
	docker-compose exec php sh

staging-seed:
	@echo "Seeding database..."
	docker-compose exec php php artisan db:seed
	@echo "✓ Database seeded"

staging-migrate:
	@echo "Running migrations..."
	docker-compose exec php php artisan migrate
	@echo "✓ Migrations completed"

staging-fresh:
	@echo "⚠️  WARNING: This will reset the entire database!"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose exec php php artisan migrate:fresh --seed; \
		echo "✓ Database reset and seeded"; \
	fi

staging-test:
	@echo "Running tests in staging..."
	docker-compose exec php php artisan test
	@echo "✓ Tests completed"

staging-tinker:
	docker-compose exec php php artisan tinker

staging-db-shell:
	docker-compose exec postgres psql -U postgres -d qrmade_staging

staging-db-sync:
	@echo "Syncing production data to staging..."
	@bash scripts/db-sync.sh
	@echo "✓ Database synced"

staging-build:
	@echo "Building Docker images..."
	docker-compose build
	@echo "✓ Docker images built"

staging-clean:
	@echo "⚠️  WARNING: This will remove all containers and volumes!"
	@read -p "Continue? [y/N] " -n 1 -r; \
	echo; \
	if [[ $$REPLY =~ ^[Yy]$$ ]]; then \
		docker-compose down -v; \
		echo "✓ All containers and volumes removed"; \
	fi

staging-health:
	@echo "Checking service health..."
	@docker-compose exec php php artisan migrate:status
	@echo "PHP Status: ✓"
	@docker-compose exec postgres pg_isready -U postgres && echo "PostgreSQL Status: ✓"
	@docker-compose exec redis redis-cli ping && echo "Redis Status: ✓"

# Aliases
start: staging-up
stop: staging-down
restart: staging-restart
logs: staging-logs
bash: staging-bash
test: staging-test
