# Composer vulnerability audit — 28 October 2026

Source: GitHub Actions run [40780134477](https://github.com/Hovertask/laravel-MKpr/actions/runs/40780134477), downloadable artifact `composer-security-audit`. This is a snapshot of the **locked dependencies**, not a claim that every advisory is exploitable in this application.

**Result:** 67 advisories across 20 packages. The dependency-security-audit CI job correctly fails. The installation/build and PostgreSQL jobs passed independently.

## Priority 1: Critical findings

- `laravel/framework`: 9 advisories (including critical, high, medium and low).
- `laravel/reverb`: 1 critical advisory.

Update these first **together with their transitive dependencies as required**, pin the resulting versions in `composer.lock`, and rerun API/auth/payment tests. Assess whether Reverb is exposed to the network.

## Priority 2: High-severity findings

- `guzzlehttp/guzzle` (9 advisories)
- `league/commonmark` (15)
- `phpseclib/phpseclib` (5)
- `phpunit/phpunit` (1; development dependency)
- `symfony/http-foundation` (4)
- `symfony/mime` (2)
- `symfony/process` (1)
- `symfony/yaml` (4)

Counts include all severities; these packages each have at least one high advisory.

## Other advisories

`firebase/php-jwt` (1), `guzzlehttp/psr7` (4), `league/flysystem` (1), `nesbot/carbon` (1), `paragonie/sodium_compat` (3), `psy/psysh` (1), `symfony/cache` (1), `symfony/mailer` (1), `symfony/polyfill-intl-idn` (1), `symfony/routing` (2).

## Remediation workflow

1. Review the complete JSON artifact for advisory IDs, affected ranges and fixed releases. Check the current lockfile and package constraints before selecting upgrades.
2. Make a **focused Composer dependency update** on a branch, regenerate `composer.lock` with Composer (never hand-edit it), and record dependency changes.
3. Run `composer validate`, `composer audit --locked`, PHP tests, PostgreSQL integration tests, and a staging smoke test of payments, login, queues and Reverb.
4. Repeat for remaining packages. Keep the audit CI check failing until all reportable advisories are resolved or an explicitly documented, reviewed exception is approved.
5. Do not deploy a dependency refresh without backups, staging verification and rollback preparation.

The repository's dependency audit is **not green**. No vulnerabilities were waived or concealed.
