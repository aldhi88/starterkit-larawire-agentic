# Project AI Instructions

This Laravel project uses the `aldhi88/starterkit-larawire-agentic` Composer package as its agentic-coding foundation and execution contract.

Before planning or changing code:

1. Read `vendor/aldhi88/starterkit-larawire-agentic/AGENTS.md` in full as the primary contract.
2. Read only the rules in `vendor/aldhi88/starterkit-larawire-agentic/docs/rules/` routed to the task.
3. Treat `docs/...` mentioned by the starter contract as `vendor/aldhi88/starterkit-larawire-agentic/docs/...`; feature paths such as `app/`, `routes/apps/`, `resources/views/apps/`, `database/migrations/apps/`, `tests/`, and `issues/` are at this Laravel root.
4. Treat `vendor/aldhi88/starterkit-larawire-agentic/` as read-only dependency source. Project features belong to this Laravel project.

Developers provide business context. The agent applies the required workflow, security, performance, validation, audit, pagination, UI, testing, and file ownership standards without asking the developer to repeat them.
