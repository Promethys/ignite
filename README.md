# Ignite 🔥

A Laravel + Vue.js goal tracking application that helps users visualize their progress and stay motivated to achieve their objectives.

## About

Ignite is a full-stack web application designed to combat project abandonment by making progress visible and tangible. Research shows that visual cues (progress bars, checklists, levels, etc.) motivate the brain by making advancement concrete. Ignite provides various visualization tools to help users track and complete their goals.

## Features

- **[Goal Types](docs/features/goal-types.md)**: simple, quantifiable, recurring, and multi-step goals, each with its own progress model
- **[Milestones](docs/features/milestones.md)**: break large goals into smaller checkpoints
- **[Categories](docs/features/categories.md)**: organize goals by area of life
- **[Streaks](docs/features/streaks.md)**: track consistency on recurring goals
- **[Authentication](docs/features/authentication.md)**: Fortify-based auth with two-factor support
- **[Internationalization](docs/features/internationalization.md)**: English and French, with more locales addable
- **[Admin Panel](docs/features/admin-panel.md)**: usage stats and abandonment-rate insights for self-hosters
- **[Feedback & Ops](docs/features/feedback-and-ops.md)**: optional Formbricks feedback survey and Discord ops relay

## Documentation

Full developer documentation, covering installation, configuration, self-hosting, architecture, testing, and each feature above, is published at **[ignite-docs.promethys.dev](https://ignite-docs.promethys.dev/)**.

The source lives in [`docs/`](docs/) and is built with [VitePress](https://vitepress.dev/). To browse it locally:

```bash
npm run docs:dev
```

## Installation

With Docker, from a fresh clone:

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
cp .env.example .env
docker compose -f compose.dev.yaml up -d --build
```

The app comes up on `http://localhost:8080` with hot reload, a seeded database, and no PHP, Node or PostgreSQL needed on your machine. A native setup is equally supported.

See the [Getting Started guide](docs/getting-started.md) for both paths, and [Self-Hosting](docs/self-hosting.md) to run your own instance for real.

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow, code style, and commit conventions.

## Sponsor

Ignite is source-available and free to self-host under the [FSL-1.1-MIT](LICENSE) license. If it is useful to you and you would like to support its development, you can [sponsor the project](https://github.com/sponsors/nirine1). Sponsoring is optional: it does not unlock features and does not change your rights under the license.

## Security

Found a vulnerability? See [SECURITY.md](SECURITY.md) for how to report it responsibly.

## License

Ignite is **source-available** under the [Functional Source License, Version 1.1, MIT Future License (FSL-1.1-MIT)](LICENSE), one of the [Fair Source](https://fair.io) licenses.

In plain terms: you can read the code, self-host it for yourself or your organization, modify it, and contribute back, all for free. The one thing you cannot do is offer Ignite as a competing hosted service to others. Two years after each release, that version automatically converts to the [MIT license](https://opensource.org/license/mit) and becomes fully open source.

## Author

Ilainiriko Tambaza - [@nirine1](https://github.com/nirine1)
