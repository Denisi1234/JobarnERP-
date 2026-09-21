#!/usr/bin/env bash
set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BOLD='\033[1m'
NC='\033[0m' # No Color

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
cd "$DIR"

print_header() {
    echo -e "${CYAN}${BOLD}"
    echo "================================================================="
    echo "       FRONTDESK OS — ENTERPRISE MULTI-PORTAL SUITE              "
    echo "================================================================="
    echo -e "${NC}"
}

ensure_docker_pg() {
    echo -e "${BLUE}▶ Checking PostgreSQL Database...${NC}"
    if docker ps --format '{{.Names}}' | grep -q "^frontdesk-pg$"; then
        echo -e "${GREEN}✓ PostgreSQL container 'frontdesk-pg' is already running.${NC}"
    elif docker ps -a --format '{{.Names}}' | grep -q "^frontdesk-pg$"; then
        echo -e "${YELLOW}Starting existing 'frontdesk-pg' container...${NC}"
        docker start frontdesk-pg >/dev/null
        sleep 2
        echo -e "${GREEN}✓ PostgreSQL started.${NC}"
    else
        echo -e "${YELLOW}Creating and starting 'frontdesk-pg' PostgreSQL container...${NC}"
        docker run --name frontdesk-pg \
            -e POSTGRES_PASSWORD=secret \
            -e POSTGRES_DB=laravel \
            -e POSTGRES_USER=root \
            -p 5432:5432 \
            -d postgres:16-alpine >/dev/null
        sleep 3
        echo -e "${GREEN}✓ PostgreSQL container created and running on port 5432.${NC}"
    fi
}

ensure_app() {
    if [ ! -f .env ]; then
        echo -e "${YELLOW}Creating .env file from .env.example...${NC}"
        cp .env.example .env
        php artisan key:generate --force
    fi

    echo -e "${BLUE}▶ Checking database schema and seeds...${NC}"
    php artisan migrate --force --quiet

    # If demo seed needed
    USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null | tail -n 1)
    if [ "$USER_COUNT" -lt "4" ] 2>/dev/null; then
        echo -e "${YELLOW}Seeding demo users and operational data...${NC}"
        php artisan db:seed --class=PortalUsersSeeder --force
        php artisan db:seed --class=ComprehensiveDemoSeeder --force
        echo -e "${GREEN}✓ Demo data seeded.${NC}"
    fi
}

print_summary() {
    echo ""
    echo -e "${BOLD}${GREEN}✔ Frontdesk OS is ready to serve!${NC}"
    echo ""
    echo -e "${BOLD}Portal Gateways & Access Points:${NC}"
    echo -e "  ${CYAN}• Main Login Gateway:${NC}    http://127.0.0.1:8000/login"
    echo -e "  ${CYAN}• Reception Desk:${NC}        http://127.0.0.1:8000/reception"
    echo -e "  ${CYAN}• IT Service Portal:${NC}     http://127.0.0.1:8000/it"
    echo -e "  ${CYAN}• Sales & Invoicing:${NC}     http://127.0.0.1:8000/sales"
    echo -e "  ${CYAN}• Executive Manager:${NC}     http://127.0.0.1:8000/manager"
    echo -e "  ${CYAN}• Admin Control Center:${NC}  http://127.0.0.1:8000/admin"
    echo -e "  ${CYAN}• System Health Probe:${NC}   http://127.0.0.1:8000/health"
    echo ""
    echo -e "${BOLD}Pre-configured Demo Accounts (Password: 12345678):${NC}"
    echo -e "  ${YELLOW}Receptionist:${NC} reception@jobarn.co.tz"
    echo -e "  ${YELLOW}IT Specialist:${NC} it@jobarn.co.tz"
    echo -e "  ${YELLOW}Sales Officer:${NC} sales@jobarn.co.tz"
    echo -e "  ${YELLOW}Executive Mgr:${NC} manager@jobarn.co.tz"
    echo -e "  ${YELLOW}System Admin:${NC}  admin@jobarn.co.tz"
    echo ""
    echo -e "${BLUE}Press Ctrl+C to stop the server.${NC}"
    echo "-----------------------------------------------------------------"
}

case "$1" in
    build)
        print_header
        echo -e "${BLUE}▶ Compiling frontend assets with Vite...${NC}"
        npm run build
        echo -e "${GREEN}✓ Build completed!${NC}"
        ;;
    seed)
        print_header
        ensure_docker_pg
        echo -e "${BLUE}▶ Reseeding demo database...${NC}"
        php artisan db:seed --class=ComprehensiveDemoSeeder --force
        echo -e "${GREEN}✓ Comprehensive demo data reseeded!${NC}"
        ;;
    dev)
        print_header
        ensure_docker_pg
        ensure_app
        print_summary
        # Run Vite in background and artisan in foreground
        npm run dev &
        VITE_PID=$!
        trap "kill $VITE_PID 2>/dev/null" EXIT
        php artisan serve --host=127.0.0.1 --port=8000
        ;;
    stop)
        echo -e "${YELLOW}Stopping background database containers...${NC}"
        docker stop frontdesk-pg 2>/dev/null || true
        echo -e "${GREEN}✓ Stopped.${NC}"
        ;;
    *)
        print_header
        ensure_docker_pg
        ensure_app
        print_summary
        php artisan serve --host=127.0.0.1 --port=8000
        ;;
esac
