# Docker Setup for GreenTrip API

This project uses Docker Compose for local development with the following services:

## Services

- **PHP-FPM 8.2** - Application server
- **Nginx** - Web server (port 8000)
- **MySQL 8.0** - Database (port 3333)
- **Redis** - Cache and sessions (port 6379)
- **MailHog** - Local mail catcher (SMTP: 1025, Web UI: 8025)

## Quick Start

1. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

2. **Update .env with Docker settings:**
   ```env
   DB_HOST=db
   DB_DATABASE=greentrip
   DB_USERNAME=greentrip
   DB_PASSWORD=password

   REDIS_HOST=redis
   REDIS_PORT=6379

   MAIL_HOST=mailhog
   MAIL_PORT=1025
   ```

3. **Build and start containers:**
   ```bash
   docker-compose up -d --build
   ```

4. **Install dependencies:**
   ```bash
   docker-compose exec app composer install
   ```

5. **Generate application key:**
   ```bash
   docker-compose exec app php artisan key:generate
   ```

6. **Run migrations:**
   ```bash
   docker-compose exec app php artisan migrate
   ```

## Access Points

- **API**: http://localhost:8000
- **MailHog UI**: http://localhost:8025
- **Health Check**: http://localhost:8000/up

## Useful Commands

```bash
# View logs
docker-compose logs -f

# Access PHP container
docker-compose exec app bash

# Run artisan commands
docker-compose exec app php artisan [command]

# Stop containers
docker-compose down

# Stop and remove volumes
docker-compose down -v
```

## Database Credentials

- **Host**: db
- **Database**: greentrip
- **Username**: greentrip
- **Password**: password
- **Root Password**: root

## Mail Configuration

All emails sent by the application will be caught by MailHog and can be viewed at http://localhost:8025