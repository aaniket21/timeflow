# Timeflow V2

Timeflow is an open-source, mobile-first productivity and gamification platform. Built with Laravel 11, Inertia.js, and Vue 3.

## Features

- **Gamified Productivity**: Earn XP, unlock badges, and level up by completing pomodoro sessions.
- **Dynamic Challenges**: Complete daily challenges to boost your rank.
- **Mobile-First PWA**: Installable on Android and iOS with offline support for timers.
- **Detailed Analytics**: Weekly and monthly productivity reports.
- **Robust Security**: API rate limiting, CSP headers, and forced HTTPS in production.
- **Admin Dashboard**: Built with Filament v3 for managing users, challenges, and analytics.

## One-Command Local Setup

Requires PHP 8.2+ and Node.js 20+.

```bash
git clone https://github.com/your-username/timeflow.git
cd timeflow
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```
In a new terminal:
```bash
npm run dev
```

## Environment Variables

| Variable | Description | Example |
|---|---|---|
| `APP_URL` | The base URL of your application. | `http://localhost:8000` |
| `DB_CONNECTION` | Database connection to use. | `mysql` |
| `DB_DATABASE` | The name of your database. | `timeflow` |
| `REDIS_HOST` | Redis host for cache and queues. | `127.0.0.1` |
| `VITE_APP_NAME` | The application name exposed to Vue. | `Timeflow` |
| `SENTRY_LARAVEL_DSN` | Sentry DSN for error tracking. | `https://example@sentry.io/1` |
| `VITE_SENTRY_DSN_PUBLIC` | Sentry DSN for Vue frontend. | `https://example@sentry.io/1` |

## Running Tests

Timeflow uses Pest for testing. To run the full test suite (100+ tests):

```bash
php artisan test
```

## Deployment

Deploying to production is fast and easy using Railway or similar platforms.

[![Deploy on Railway](https://railway.app/button.svg)](https://railway.app/template/laravel)

Before deploying, ensure you configure the following commands in your deploy script:
```bash
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build
php artisan migrate --force
```

### Health Check

Timeflow exposes a `/health` endpoint for uptime monitoring which verifies database connectivity.
