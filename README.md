# Hovertask Laravel API

Laravel 11 backend for Hovertask, including authentication, products, orders, tasks, wallets, withdrawals, referrals, chat and administrative APIs.

## Requirements

- PHP 8.2 or newer, with the PHP extensions required by Composer dependencies
- Composer
- PostgreSQL for the application database
- Credentials for the external services used by the features you enable

## Local setup

```bash
git clone https://github.com/Hovertask/laravel-MKpr.git
cd laravel-MKpr
cp .env.example .env
composer install
php artisan key:generate
```

Configure `.env` with your own PostgreSQL connection details and service credentials. Create the database before running migrations. **Never commit real credentials or production .env files.**

```bash
php artisan migrate
php artisan serve
```

Composer installation intentionally **does not** migrate the database. Database changes should be run explicitly after configuration.

## Isolated Docker Compose setup (local development only)

Install Docker Engine/Desktop with Docker Compose v2, then run:

```bash
cp .env.example .env
```

Set a **unique nonempty** `DB_PASSWORD` in your local `.env`. Do not reuse production credentials. Build the image and generate an application key (prints it without modifying the image):

```bash
docker compose build
docker compose run --rm app php artisan key:generate --show
```

Copy that generated value into `APP_KEY=` in your local `.env`, then start the services and run migrations explicitly:

```bash
docker compose up -d
docker compose exec app php artisan migrate
```

The API is available at `http://localhost:8080` by default (override `APP_PORT` if needed). The PostgreSQL database is reachable from the app at hostname `db`, port `5432`. No third-party API credentials are required for basic startup, but features such as live payments, Cloudinary uploads and email require their own configuration. This setup does not create or start additional external services or background queue workers. Use `docker compose down` to stop the stack; `docker compose down -v` **deletes** the local database volume.

For configuration validation before building, run `docker compose config -q`. This Compose setup is for local development, **not production deployment**.

## Tests and code quality

```bash
php artisan test
vendor/bin/pint --test
```

The test suite currently has limited coverage. Do not treat a successful smoke test as evidence that payment, authentication or authorization flows have been fully verified.

## Working on changes

Make one focused change per branch or pull request. Include tests for bug fixes and new behavior, then run the relevant tests before opening a pull request. Keep credentials, customer data and generated artifacts out of Git.

## Deployment

Review environment variables and service credentials before deploying. Run migrations as a controlled deployment step, independently of `composer install`. Validate the application in a staging environment before promoting changes.

## Licensing

The repository's package metadata declares an MIT license inherited from the Laravel starter. This README does **not** establish ownership of all code or grant third-party licensing rights. Confirm copyright ownership, contributors' rights and third-party dependencies before entering a commercial code license.
