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

Full developer documentation (installation, configuration, self-hosting, architecture, testing, and the feature pages above) lives in [`docs/`](docs/), built with [VitePress](https://vitepress.dev/). Browse it locally with:

```bash
npm run docs:dev
```

## Installation

```bash
git clone https://github.com/Promethys/ignite.git
cd ignite
```

See the [Getting Started guide](docs/getting-started.md) for the full setup (prerequisites, environment, database, and running the app).

## Contributing

Contributions are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow, code style, and commit conventions.

## Security

Found a vulnerability? See [SECURITY.md](SECURITY.md) for how to report it responsibly.

## License

Ignite is **source-available** under the [Functional Source License, Version 1.1, MIT Future License (FSL-1.1-MIT)](LICENSE), one of the [Fair Source](https://fair.io) licenses.

In plain terms: you can read the code, self-host it for yourself or your organization, modify it, and contribute back, all for free. The one thing you cannot do is offer Ignite as a competing hosted service to others. Two years after each release, that version automatically converts to the [MIT license](https://opensource.org/license/mit) and becomes fully open source.

## Author

Ilainiriko Tambaza - [@nirine1](https://github.com/nirine1)
