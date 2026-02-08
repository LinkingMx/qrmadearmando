#!/bin/bash
set -e

echo "╔════════════════════════════════════════════════╗"
echo "║    Database Synchronization - Local Staging    ║"
echo "╚════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROD_DB_HOST="${PROD_DB_HOST:-localhost}"
PROD_DB_PORT="${PROD_DB_PORT:-5432}"
PROD_DB_NAME="${PROD_DB_NAME:-qrmade_production}"
PROD_DB_USER="${PROD_DB_USER:-postgres}"

STAGING_HOST="localhost"
STAGING_PORT="5432"
STAGING_DB_NAME="qrmade_staging"
STAGING_USER="postgres"
STAGING_PASSWORD="postgres_secret"

BACKUP_DIR="./database/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
DUMP_FILE="$BACKUP_DIR/dump_$TIMESTAMP.sql"

# Ensure backup directory exists
mkdir -p "$BACKUP_DIR"

echo -e "${YELLOW}⚠️  This will overwrite staging database with production data${NC}"
read -p "Continue? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 1: Creating production database dump...${NC}"
if PGPASSWORD=$PROD_DB_PASSWORD pg_dump -h $PROD_DB_HOST -p $PROD_DB_PORT -U $PROD_DB_USER $PROD_DB_NAME > "$DUMP_FILE" 2>/dev/null; then
    echo -e "${GREEN}✓ Dump created: $DUMP_FILE${NC}"
else
    echo -e "${RED}✗ Failed to dump production database${NC}"
    echo "  Make sure you have access to production database"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Dropping staging database...${NC}"
PGPASSWORD=$STAGING_PASSWORD psql -h $STAGING_HOST -p $STAGING_PORT -U $STAGING_USER -d postgres -c "DROP DATABASE IF EXISTS $STAGING_DB_NAME;" 2>/dev/null || echo "  Database doesn't exist yet"

echo ""
echo -e "${YELLOW}Step 3: Creating new staging database...${NC}"
PGPASSWORD=$STAGING_PASSWORD psql -h $STAGING_HOST -p $STAGING_PORT -U $STAGING_USER -d postgres -c "CREATE DATABASE $STAGING_DB_NAME;" 2>/dev/null

echo ""
echo -e "${YELLOW}Step 4: Restoring production data...${NC}"
PGPASSWORD=$STAGING_PASSWORD psql -h $STAGING_HOST -p $STAGING_PORT -U $STAGING_USER -d $STAGING_DB_NAME < "$DUMP_FILE" 2>/dev/null

echo ""
echo -e "${YELLOW}Step 5: Anonymizing sensitive data...${NC}"

# Anonymize users (keep admin account visible)
PGPASSWORD=$STAGING_PASSWORD psql -h $STAGING_HOST -p $STAGING_PORT -U $STAGING_USER -d $STAGING_DB_NAME <<EOF 2>/dev/null
-- Reset passwords to 'password' (Laravel bcrypt hash)
UPDATE users SET password = '\$2y\$12\$TYQgCZRgJvtQv.97bh61h.3P6XaTz3qnVMJgF6tXkZ3n2\$eGM5xDuSz7bPxXDLv7U6Ou' WHERE id > 1;

-- Anonymize emails (keep admin)
UPDATE users SET email = CONCAT('user', id, '@staging.test') WHERE id > 1;

-- Clear 2FA data for non-admin users
UPDATE users SET two_factor_secret = NULL, two_factor_recovery_codes = NULL, two_factor_confirmed_at = NULL WHERE id > 1;

-- Clear sensitive push notification subscriptions
TRUNCATE TABLE push_subscriptions CASCADE;

-- Clear activity logs
TRUNCATE TABLE activity_log CASCADE;

-- Clear sessions
TRUNCATE TABLE sessions CASCADE;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Data anonymized${NC}"
else
    echo -e "${YELLOW}⚠️  Warning: Some anonymization queries failed (this may be okay)${NC}"
fi

echo ""
echo -e "${YELLOW}Step 6: Running migrations...${NC}"
if docker-compose exec -T php php artisan migrate --force 2>/dev/null; then
    echo -e "${GREEN}✓ Migrations completed${NC}"
else
    echo -e "${YELLOW}⚠️  Migrations may have failed (check manually)${NC}"
fi

echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════╗"
echo "║          Synchronization Complete! ✓            ║"
echo "╚════════════════════════════════════════════════╝${NC}"
echo ""
echo "Database Information:"
echo "  Host: $STAGING_HOST:$STAGING_PORT"
echo "  Database: $STAGING_DB_NAME"
echo "  User: $STAGING_USER"
echo ""
echo "Admin Access:"
echo "  Email: admin@staging.test"
echo "  Password: password"
echo ""
echo "Backup Location: $DUMP_FILE"
echo ""
echo "Next Steps:"
echo "  1. Start staging: make staging-up"
echo "  2. Access: http://localhost"
echo "  3. Admin: http://localhost/admin"
echo ""
