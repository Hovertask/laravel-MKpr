# Seeder security

`database/seeders/LaratrustSeeder.php` previously assigned predictable passwords to seeded accounts. It now uses `SEED_ADMIN_PASSWORD` when configured, otherwise a random 32-character value.

For an environment in which seeded accounts must be accessible, provide a **unique, securely generated** `SEED_ADMIN_PASSWORD` through the environment or secret manager before running this seeder. Do not commit the password to Git or print it in logs. Without that variable, a random password will be generated and will not be displayed. All newly seeded accounts from a single run share the configured or generated seed password; rotate/disable those accounts after provisioning and prefer individual account onboarding.

Existing accounts created by `firstOrCreate` will **not** have their passwords changed by rerunning this seeder. Audit existing privileged accounts and rotate any passwords that may have used the old defaults.

The previously tracked `.env.swp` editor swap file was deleted and `*.swp` / `*.swo` were added to `.gitignore`. **Deleting it from the current branch does not remove it from Git history.** Treat any credentials that may have been included as exposed: audit the historical file carefully in a controlled environment, rotate affected credentials, and review whether a managed history rewrite is required, accounting for downstream clones.
