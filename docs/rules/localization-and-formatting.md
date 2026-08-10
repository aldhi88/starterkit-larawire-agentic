# Localization and Value Formatting

- Use `laravel-lang/common`; do not recreate framework translations. Locale, fallback locale, and Faker locale follow `APP_LOCALE`, `APP_FALLBACK_LOCALE`, and `APP_FAKER_LOCALE` (default: `id`, `id`, `id_ID`). After language dependency updates run `php artisan lang:update --no-interaction` and review the diff.
- Business labels are Indonesian; familiar terms such as username, password, email, role, and module may remain English.
- Format displayed numbers/currency through `Altekno\StarterKit\Support\Starter\StarterNumber`, not scattered `number_format()`. Indonesian formatting uses `1.234` and `1.234,5`; IDR shows locale-appropriate symbol/code and decimals only when present.
- Database/API/input/calculation values remain raw numeric values. Parse locale-formatted input explicitly and test it; never cast `1.234,5` directly to float.
- Store native timestamps and present them in `APP_TIMEZONE` with locale formatter/translator; never persist rendered day/month names.
- Test locale configuration, integer/decimal/negative/currency rendering, and that presentation formatting never changes persisted/calculated values.
