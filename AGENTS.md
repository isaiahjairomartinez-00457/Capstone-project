# Flora Guard Development Instructions

## Project

Flora Guard is a PHP administration system for native flora and tree records. It uses Firebase Realtime Database, session-based admin authentication, Mapbox, Chart.js, HTML, CSS, and JavaScript.

## Before Making Changes

- Read `knowledge.md` for the repository architecture and data flow.
- Read the relevant skill under `skills/`.
- Inspect existing implementations before modifying them.
- Keep changes focused on the requested behavior.

## Development Rules

- Preserve working features, routes, Firebase field names, and existing public APIs.
- Reuse shared bootstrap, helpers, layouts, and repository functions.
- Keep `declare(strict_types=1)` in PHP files that already use it.
- Escape user-controlled output with the existing `h()` helper.
- Validate input on the server and handle missing or malformed data safely.
- Keep protected pages behind `tg_require_login()`.
- Do not expose Firebase credentials, passwords, or private tokens.
- Do not introduce fake dashboard or report data.
- Preserve responsive behavior and the existing visual design system.

## Architecture Rules

- Use `includes/init.php` and the existing layout partials for shared page setup.
- Use the existing functions in `lib/` for Firebase access and record transformations.
- Use the existing Mapbox and Chart.js integration patterns.
- Preserve the current Firebase data structure unless a change is explicitly required.

## Verification

- Check PHP syntax for modified PHP files.
- Test affected pages through XAMPP when possible.
- Check authentication, validation, empty states, and error paths.
- Inspect the browser console for JavaScript errors when frontend code changes.
- Review `git status` and changed files before committing.

## Git

Work on a feature branch. Do not modify `main` directly unless explicitly instructed.
