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
