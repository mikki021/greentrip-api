# Git Hooks and Testing

This project includes Git hooks and testing utilities to ensure code quality and prevent broken code from being committed or pushed.

## Git Hooks

### Pre-commit Hook

The pre-commit hook runs automatically before each commit and performs the following checks:

- ✅ Verifies Docker is running
- ✅ Ensures the `greentrip_app` container is running
- ✅ Waits for the database to be ready
- ✅ Runs database migrations
- ✅ Executes all PHPUnit tests

**Location:** `.git/hooks/pre-commit`

**What happens if tests fail:**
- The commit is blocked
- You'll see an error message with instructions
- You must fix the failing tests before committing

### Pre-push Hook

The pre-push hook runs automatically before pushing to remote repositories and performs comprehensive checks:

- ✅ All pre-commit checks
- ✅ PHPUnit tests with coverage reporting
- ✅ PHP CodeSniffer (PSR-12) code style checks
- ✅ PHPStan static analysis (if installed)

**Location:** `.git/hooks/pre-push`

**What happens if checks fail:**
- The push is blocked
- You'll see detailed error messages
- You must fix the issues before pushing

## Test Runner Script

A convenient test runner script is available at `scripts/test.sh` that provides various testing options:

### Usage

```bash
# Run all tests and checks (default)
./scripts/test.sh

# Run only unit tests
./scripts/test.sh --unit

# Run only feature tests
./scripts/test.sh --feature

# Run tests with coverage report
./scripts/test.sh --coverage

# Run code style checks only
./scripts/test.sh --style

# Run static analysis only
./scripts/test.sh --static

# Run all checks (tests + style + static)
./scripts/test.sh --all
```

### Features

- 🐳 Automatically manages Docker containers
- ⏳ Waits for database readiness
- 🔄 Runs migrations automatically
- 🎨 Colored output for better readability
- 📊 Coverage reporting
- 🔍 Code quality checks

## Manual Testing

You can also run tests manually using Docker commands:

```bash
# Run all tests
docker exec greentrip_app php artisan test

# Run specific test suite
docker exec greentrip_app php artisan test --testsuite=Unit
docker exec greentrip_app php artisan test --testsuite=Feature

# Run tests with coverage
docker exec greentrip_app php artisan test --coverage

# Run code style checks
docker exec greentrip_app ./vendor/bin/phpcs --standard=PSR12 app/ tests/

# Fix code style issues
docker exec greentrip_app ./vendor/bin/phpcbf --standard=PSR12 app/ tests/
```

## Code Quality Tools

### PHP CodeSniffer (PSR-12)

Ensures code follows PSR-12 coding standards.

**Installation:**
```bash
composer require --dev squizlabs/php_codesniffer
```

**Usage:**
```bash
# Check code style
./vendor/bin/phpcs --standard=PSR12 app/ tests/

# Fix code style issues
./vendor/bin/phpcbf --standard=PSR12 app/ tests/
```

### PHPStan (Static Analysis)

Performs static analysis to catch potential bugs and code quality issues.

**Installation:**
```bash
composer require --dev phpstan/phpstan
```

**Usage:**
```bash
# Run static analysis
./vendor/bin/phpstan analyse app/ --level=5
```

## Troubleshooting

### Hook Not Running

If the hooks aren't running, ensure they're executable:

```bash
chmod +x .git/hooks/pre-commit
chmod +x .git/hooks/pre-push
```

### Docker Issues

If you encounter Docker-related issues:

1. Ensure Docker is running
2. Check if containers are up: `docker ps`
3. Restart containers: `docker-compose down && docker-compose up -d`

### Database Issues

If tests fail due to database issues:

1. Ensure the database container is running
2. Check database connectivity: `docker exec greentrip_app php artisan migrate:status`
3. Reset database: `docker exec greentrip_app php artisan migrate:fresh`

### Skipping Hooks (Emergency)

In emergency situations, you can skip hooks:

```bash
# Skip pre-commit hook
git commit --no-verify -m "Emergency fix"

# Skip pre-push hook
git push --no-verify
```

⚠️ **Warning:** Only use this in true emergencies. It's better to fix the issues than to skip the quality checks.

## Configuration

### Customizing Test Environment

The test environment is configured in `phpunit.xml`:

- Uses SQLite in-memory database for fast testing
- Disables external services (mail, cache, etc.)
- Sets testing-specific environment variables

### Adding New Hooks

To add new Git hooks:

1. Create the hook file in `.git/hooks/`
2. Make it executable: `chmod +x .git/hooks/hook-name`
3. Add documentation to this file

### CI/CD Integration

These hooks ensure that code quality is maintained locally. For production deployments, consider integrating similar checks into your CI/CD pipeline.