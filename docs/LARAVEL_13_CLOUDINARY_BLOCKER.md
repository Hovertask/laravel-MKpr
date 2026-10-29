# Laravel 13 upgrade blocker — Cloudinary integration

Review date: 28 October 2026.

The experimental Laravel 13 dependency-resolution workflow fails before it can produce a candidate lockfile. Composer reported that the root package `cloudinary-labs/cloudinary-laravel:2.2` requires Illuminate Support 10 or 11; this conflicts with Laravel 13. The latest official release checked, `3.0.2`, supports Illuminate Support 11 or 12, not 13.

Official package reference: https://packagist.org/packages/cloudinary-labs/cloudinary-laravel

**Decision:** do not automatically replace the official SDK with an unverified third-party fork, remove uploads, or relax Composer's security advisory policy.

Before continuing the major-version upgrade:
1. Look for an official Cloudinary Laravel 13-compatible release, or approve and audit a compatible alternative.
2. Inventory Cloudinary use in upload, media transformation and retrieval code. Verify API compatibility and permissions.
3. Create an isolated dependency update PR with a Composer-generated lockfile.
4. Run tests, test actual media upload/download flows in staging, and review all remaining security advisories.

The existing PHP tests and PostgreSQL integration suite passed on the original dependency lockfile, but the security audit remains failing. Passing tests do not imply that the installed dependencies are free of vulnerabilities.
