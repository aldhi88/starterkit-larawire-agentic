# Contributing

Thank you for helping improve Starterkit Larawire Agentic.

1. Open an issue for a material behavior, compatibility, security, command, or
   architecture change before implementation.
2. Keep changes focused and preserve the package/host boundary. Do not commit a
   Laravel host, `.env`, credentials, databases, generated caches, premium
   template source, or unrelated assets.
3. Add or update Pest tests and documentation/rules that own the changed
   contract.
4. Run:

```bash
composer validate --strict
composer analyse
vendor/bin/pint --test
composer test
```

By submitting a contribution, you confirm that you have the right to provide
it and agree that it may be distributed under this repository's `LICENSE`.
Security vulnerabilities should follow `SECURITY.md`, not a public issue.
