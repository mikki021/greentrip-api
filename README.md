# GreenTrip API

A **mock API for a sustainable business travel agency**. This project simulates the backend for a modern, eco-conscious travel platform, providing endpoints for user authentication, registration, email verification, and more. It is designed for rapid prototyping, integration testing, and as a reference for best practices in Laravel API development.

---

## Features

- **User Registration & Login** (JWT-based authentication)
- **Email Verification** with expiring links (48h validity)
- **Block login for unverified emails**
- **Swagger/OpenAPI 3.0 documentation** at `/api/swagger`
- **Comprehensive PHPUnit/Laravel tests**
- **Pre-commit & pre-push Git hooks** for code quality (see [`GIT_HOOKS.md`](GIT_HOOKS.md))
- **Dockerized** for easy local development
- **Mailhog** integration for email testing
- **PSR-12 code style** and static analysis ready

---

## Quick Start (Docker)

1. **Clone the repository:**
   ```bash
   git clone <repo-url>
   cd greentrip-api
   ```
2. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```
3. **Start the stack:**
   ```bash
   docker-compose up -d
   ```
4. **Install dependencies:**
   ```bash
   docker exec greentrip_app composer install
   ```
5. **Run migrations:**
   ```bash
   docker exec greentrip_app php artisan migrate
   ```
6. **Access Mailhog UI:**
   - [http://localhost:8025](http://localhost:8025)
7. **View Swagger UI:**
   - [http://localhost:8000/api/swagger](http://localhost:8000/api/swagger)

---

## API Documentation

- **Swagger/OpenAPI 3.0**: [swagger.yaml](swagger.yaml)
- **Interactive UI**: [http://localhost:8000/api/swagger](http://localhost:8000/api/swagger)
- **Spec endpoint**: [http://localhost:8000/api/swagger/spec](http://localhost:8000/api/swagger/spec)

---

## Testing & Code Quality

- **Run all tests:**
  ```bash
  ./scripts/test.sh
  ```
- **Run only unit/feature tests:**
  ```bash
  ./scripts/test.sh --unit
  ./scripts/test.sh --feature
  ```
- **Run with coverage:**
  ```bash
  ./scripts/test.sh --coverage
  # (Requires Xdebug or PCOV in Docker image)
  ```
- **Code style & static analysis:**
  ```bash
  ./scripts/test.sh --style
  ./scripts/test.sh --static
  ```
- **Git hooks:**
  - Pre-commit and pre-push hooks run tests and code checks automatically. See [`GIT_HOOKS.md`](GIT_HOOKS.md).

---

## Development Workflow

- All code is checked for style and tested before commit/push (see hooks)
- Use Mailhog to view outgoing emails (email verification, etc.)
- API is stateless and uses JWT for authentication
- Email verification is required for login

---

## License

MIT. See [LICENSE](LICENSE) for details.
