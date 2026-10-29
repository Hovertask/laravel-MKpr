# Commercial licensing readiness checklist

This document is an internal review aid, not proof of ownership or a legal opinion.

## Rights and provenance

- [ ] Identify every code contributor and verify the right to sublicense their contributions.
- [ ] Confirm whether any employer, contractor, customer, or sponsor owns portions of the code.
- [ ] Check for imported templates, copied source, generated assets, private keys, and third-party datasets.
- [ ] Clarify the repository's actual license. The `MIT` field in `composer.json` came from the Laravel starter and is not a substitute for a rights decision.
- [ ] Review third-party dependency obligations, including transitive and frontend packages. GitHub Actions generates a Composer dependency license inventory as a downloadable build artifact.
- [ ] Confirm that exposing the repository publicly does not conflict with any proposed exclusive commercial license.
- [ ] Verify the prospective buyer's current eligibility, terms, data handling, and licensing requirements directly before submitting.

## Engineering evidence

- [x] Automated PHP installation, tests, and syntax checks in GitHub Actions.
- [x] PostgreSQL payment-reference integrity tests.
- [x] Deterministic frontend installation using the committed `package-lock.json`.
- [x] Production Vite build in CI.
- [ ] Wider PostgreSQL integration and simultaneous payment settlement tests.
- [ ] Fresh-clone installation validated with all required services and migrations.
- [ ] Review dependency vulnerabilities and remediation plan.
- [ ] Document deployment and rollback procedure.

## Database rollout safety

Before running the payment-reference unique-index migration on any populated database:

```sql
SELECT reference, COUNT(*) AS occurrences
FROM initialize_deposits
WHERE reference IS NOT NULL
GROUP BY reference
HAVING COUNT(*) > 1;
```

Reconcile any duplicates with reference to payment-provider records. Do not automatically remove payment rows. Make a verified database backup and test the migration on staging first.

Do not fabricate commit history, contributor rights, test outcomes, or license provenance to meet a buyer's assessment.
