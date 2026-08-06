# Contributing to Ignite

Thank you for your interest in contributing! 🎉

## Getting Started

1. Read the [Getting Started guide](docs/getting-started.md) for setup instructions
2. Fork the repository
3. Create a feature branch: `git checkout -b feature/your-feature`
4. Make your changes
5. Auto-fix formatting: `npm run fix && ./vendor/bin/pint`
6. Run the checks: `npm run check && php artisan test`
7. Commit with conventional commits: `git commit -m "feat: add feature"`
8. Push and create a Pull Request

## Code Style

- **PHP**: Follow Laravel conventions (enforced by Pint)
- **JavaScript/TypeScript**: Follow project ESLint rules
- **Vue**: Use Composition API with `<script setup>`
- **Commits**: Use [Conventional Commits](https://www.conventionalcommits.org/)

## Pull Request Process

1. Ensure CI checks pass (tests, linting)
2. Update the affected `docs/` pages in the same PR when behavior changes
3. Request review from maintainers
4. Address feedback
5. Merge the PR keeping its commits (a merge commit, not a squash), so the conventional-commit history is preserved on `main`

## Versioning

Ignite follows [Semantic Versioning](https://semver.org/) (`MAJOR.MINOR.PATCH`). Tags carry no `v` prefix (for example `1.5.0`, not `v1.5.0`).

- **PATCH** (`1.5.0` to `1.5.1`): backward-compatible bug fixes, refactors, or chores, with no new user-facing capability. Usually `fix:`, `refactor:`, or `chore:` commits.
- **MINOR** (`1.5.1` to `1.6.0`): a new backward-compatible feature, however large. Resets the patch number to 0. Usually `feat:` commits.
- **MAJOR** (`1.6.0` to `2.0.0`): a breaking change that requires action on upgrade, such as a data migration, a changed or removed config or environment variable, or a removed route or API. A big feature is not major on its own unless it breaks backward compatibility. Signalled by a `feat!:` commit or a `BREAKING CHANGE:` footer.

Because the version bump follows from the commit type, keep commit messages accurate.

### Releases

A tag marks a releasable batch, not a merge. Group related work of one theme under a single tag, and let a lone `chore:` or `docs:` ride along with the next real release. The bump reflects the highest-severity change in the batch. If you cannot name the tag as "a thing that shipped", it is too small to tag on its own.

**Every tag from `1.18.3` onward also gets a [GitHub Release](https://github.com/Promethys/ignite/releases).** The tag is the repository checkpoint; the Release is the public, readable version of it. Earlier tags were never released and are deliberately not backfilled.

Write the body for someone outside the project:

- Lead with a short human summary of what changed and why it matters, in plain prose. Generated notes can go underneath.
- Describe user-visible effects, not commit titles. "Password reset now works on the hosted version" beats "fix(auth): wire reset broker".
- Name security fixes plainly, including what was and was not exposed.
- No em-dashes, matching the rest of the project's written material.

`gh release create <tag> --generate-notes` is fine for later releases, but always pass `--notes-start-tag <previous>` so the generated list covers only the batch.

## Writing Documentation

Public docs live in `docs/` as a VitePress site. Run `npm run docs:dev` to preview and `npm run docs:build` before pushing; the build is a CI gate and fails on dead internal links.

VitePress adds markdown extensions on top of standard syntax. Only the following are used, so that pages read the same way throughout:

- **A language on every fence.** Use `text` for directory trees, command signatures, and plain output. An unrecognised language id fails the build.
- **Custom containers** for an aside the reader can act on out of the flow, never for prose that carries the argument. Roughly two or three per page:
  - `::: danger` when the consequence is lost data, a leaked secret, or being locked out.
  - `::: warning` for a trap that costs time: a silently ignored variable, a default that differs from `.env.example`, a platform limitation.
  - `::: tip` for optional advice, `::: info` for context that is merely surprising.
  - Give the container a title when the first line would otherwise repeat the surrounding heading.
- **Code groups** (`::: code-group` with `[Label]` on each fence) only when the blocks are alternatives the reader picks between. Sequential steps stay as separate blocks.
- **Line highlighting** (` ```json{8} `) when the prose immediately below points at those lines. Do not highlight a block nobody is discussing line by line.
- **Diff markers** for preferred/avoid pairs: `// [!code ++]` on the recommended lines, `// [!code --]` on the ones to avoid, keeping a short `// Preferred` / `// Avoid` label so the meaning does not rest on colour alone. Use `// [!code error]` instead when the line genuinely throws at runtime.

Deliberately not used: line numbers, `[!code focus]`, `<<<` snippet imports, and `[[toc]]`. The outline sidebar already covers the last one, and the others add noise without answering a question a reader has.

Screenshots belong in the user guide (`docs/guide/`) rather than the developer pages, and are lazy loaded globally via `markdown.image.lazyLoading` in `docs/.vitepress/config.ts`. Every image needs alt text describing what it shows.

Tables always span the full content width. The default theme makes each table its own scroll container, which leaves short ones stranded at half width, so `config.ts` wraps every table in a `.table-wrapper` and `docs/.vitepress/theme/style.css` moves the scrolling onto that wrapper. Nothing is needed per table; write plain markdown.

## Adding Translations

All user-visible strings must use translation keys instead of hardcoded text.

- **Frontend (Vue)**: use `$t('domain.key')` in templates. In `<script setup>`, store the key string in data and render with `$t()` in the template.
- **Backend (PHP)**: use `__('domain.key')` for flashed toasts, flash messages, and any server-side string.
- **Lang files**: add keys to both `lang/en/<domain>.php` and `lang/fr/<domain>.php`. Use semantic dotted keys (e.g. `goals.status.active`), never source-string keys.
- **No em-dashes** (`—`) in any translation value. Use a regular hyphen or rephrase.
- **Supported locales** are defined in `config/locales.php`. Add a locale there before creating lang files for it.
- Run `php artisan test --filter=TranslationParityTest` to verify key parity between locales.

## License of contributions

Ignite is source-available under the [FSL-1.1-MIT](LICENSE) license. By submitting a contribution, you agree that it is licensed under the same terms as the project.

## Code of Conduct

Be respectful, inclusive, and constructive in all interactions.

## Questions?

- Check the [docs](docs/) site for technical details
- Open an issue for bugs or feature requests
- Join our Discord for discussions

Happy contributing! 🚀
