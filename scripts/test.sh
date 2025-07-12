#!/bin/bash

# Test runner script for GreenTrip API
#
# Database Configuration:
# - Main database: greentrip (connection: mysql)
# - Test database: greentrip_testing (connection: mysql_testing)
#
# Usage: ./scripts/test.sh [options]
# Options:
#   --unit      Run only unit tests
#   --feature   Run only feature tests
#   --coverage  Run tests with coverage report
#   --style     Run code style checks
#   --static    Run static analysis
#   --all       Run all checks (default)

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}$1${NC}"
}

print_success() {
    echo -e "${GREEN}$1${NC}"
}

print_warning() {
    echo -e "${YELLOW}$1${NC}"
}

print_error() {
    echo -e "${RED}$1${NC}"
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "❌ Docker is not running. Please start Docker and try again."
        exit 1
    fi
}

# Check if container is running
check_container() {
    if ! docker ps --format "table {{.Names}}" | grep -q "greentrip_app"; then
        print_warning "⚠️  greentrip_app container is not running. Starting containers..."
        docker-compose up -d app db redis mailhog
        sleep 5
    fi
}

# Wait for database
wait_for_db() {
    print_status "⏳ Waiting for database to be ready..."
    for i in {1..30}; do
        if docker exec greentrip_app php artisan migrate:status > /dev/null 2>&1; then
            break
        fi
        sleep 1
    done
}

# Clean up test database
cleanup_test_database() {
    print_status "🧹 Cleaning up test database..."
    # mysql_testing connection points to greentrip_testing database
    # Use explicit environment variables to ensure we target the test database
    docker exec greentrip_app bash -c "DB_CONNECTION=mysql_testing php artisan db:wipe --force" 2>/dev/null || true
    print_success "✅ Test database cleaned up!"
}

# Run unit tests
run_unit_tests() {
    print_status "🧪 Running unit tests..."
    docker exec greentrip_app php artisan test --testsuite=Unit
}

# Run feature tests
run_feature_tests() {
    print_status "🧪 Running feature tests..."
    docker exec greentrip_app php artisan test --testsuite=Feature
}

# Run all tests using composer (automatically handles migrations)
run_all_tests() {
    print_status "🧪 Running all tests..."
    docker exec greentrip_app composer test
}

# Run tests with coverage
run_tests_with_coverage() {
    print_status "🧪 Running tests with coverage..."
    if docker exec greentrip_app php artisan test --coverage; then
        print_success "✅ Tests with coverage completed!"
    else
        print_warning "⚠️  Coverage driver not available. Running tests without coverage..."
        docker exec greentrip_app composer test
        print_status "💡 To enable coverage, install Xdebug or PCOV in the Docker container"
    fi
}

# Run code style checks
run_style_checks() {
    print_status "🔍 Running code style checks..."
    if docker exec greentrip_app ./vendor/bin/phpcs --standard=PSR12 app/ tests/; then
        print_success "✅ Code style check passed!"
    else
        print_warning "⚠️  Code style issues found."
        print_status "💡 You can fix code style with: docker exec greentrip_app ./vendor/bin/phpcbf --standard=PSR12 app/ tests/"
    fi
}

# Run static analysis
run_static_analysis() {
    print_status "🔍 Running static analysis..."
    if docker exec greentrip_app test -f ./vendor/bin/phpstan; then
        if docker exec greentrip_app ./vendor/bin/phpstan analyse app/ --level=5; then
            print_success "✅ Static analysis passed!"
        else
            print_warning "⚠️  Static analysis issues found."
        fi
    else
        print_warning "⚠️  PHPStan not installed. Install with: composer require --dev phpstan/phpstan"
    fi
}

# Main execution
main() {
    check_docker
    check_container
    wait_for_db

    case "${1:---all}" in
        --unit)
            run_unit_tests
            cleanup_test_database
            ;;
        --feature)
            run_feature_tests
            cleanup_test_database
            ;;
        --coverage)
            run_tests_with_coverage
            cleanup_test_database
            ;;
        --style)
            run_style_checks
            ;;
        --static)
            run_static_analysis
            ;;
        --all)
            run_all_tests
            cleanup_test_database
            echo ""
            run_style_checks
            echo ""
            run_static_analysis
            ;;
        *)
            print_error "❌ Unknown option: $1"
            echo "Usage: $0 [--unit|--feature|--coverage|--style|--static|--all]"
            exit 1
            ;;
    esac

    print_success "✅ Test run completed!"
}

main "$@"