# Contributing to Ignite

Thank you for your interest in contributing! 🎉

## Getting Started

1. Read the [Developer Documentation](DEVELOPER.md) for setup instructions
2. Fork the repository
3. Create a feature branch: `git checkout -b feature/your-feature`
4. Make your changes
5. Run tests: `php artisan test`
6. Run linters: `npm run lint && ./vendor/bin/pint`
7. Commit with conventional commits: `git commit -m "feat: add feature"`
8. Push and create a Pull Request

## Code Style

- **PHP**: Follow Laravel conventions (enforced by Pint)
- **JavaScript/TypeScript**: Follow project ESLint rules
- **Vue**: Use Composition API with `<script setup>`
- **Commits**: Use [Conventional Commits](https://www.conventionalcommits.org/)

## Pull Request Process

1. Ensure CI checks pass (tests, linting)
2. Update documentation if needed
3. Request review from maintainers
4. Address feedback
5. Squash commits before merge

## Versioning

Ignite follows [Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`). Tags carry no `v` prefix (for example `1.5.0`, not `v1.5.0`).

- **PATCH** (`1.5.0` to `1.5.1`): backward-compatible bug fixes, refactors, or chores, with no new user-facing capability. Usually `fix:`, `refactor:`, or `chore:` commits.
- **MINOR** (`1.5.1` to `1.6.0`): a new backward-compatible feature, however large. Resets the patch number to 0. Usually `feat:` commits.
- **MAJOR** (`1.6.0` to `2.0.0`): a breaking change that requires action on upgrade, such as a data migration, a changed or removed config or environment variable, or a removed route or API. A big feature is not major on its own unless it breaks backward compatibility. Signalled by a `feat!:` commit or a `BREAKING CHANGE:` footer.

Because the version bump follows from the commit type, keep commit messages accurate.

## Code of Conduct

Be respectful, inclusive, and constructive in all interactions.

## Questions?

- Check [DEVELOPER.md](DEVELOPER.md) for technical details
- Open an issue for bugs or feature requests
- Join our Discord for discussions

Happy contributing! 🚀
