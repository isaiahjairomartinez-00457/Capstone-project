# PHP Web Development Skill

## Purpose

Help develop and maintain the Flora Guard PHP web application in this repository.
The application is an authenticated administration system for native flora and tree records.

## Repository Context

- The project runs under XAMPP and Apache.
- PHP pages are located at the repository root.
- Shared bootstrap and layout code is in `includes/`.
- Firebase Realtime Database access is implemented in `lib/`.
- Frontend assets are in `assets/css/`, `assets/js/`, and `assets/vendor/`.
- Main workflows include authentication, users, tree records, scans, species, reports, settings, and dashboards.

## Responsibilities

This skill assists with:

- PHP page and API development
- HTML and CSS changes
- Vanilla JavaScript behavior
- Firebase data access and record mapping
- CRUD workflows
- Session and admin authentication
- Form validation and error handling
- Responsive UI maintenance
- XAMPP and local Apache troubleshooting

## Development Rules

1. Inspect related existing code before making changes.
2. Use `includes/init.php` and the existing layout partials instead of duplicating bootstrap or page chrome.
3. Preserve `declare(strict_types=1)` and existing function contracts.
4. Reuse helpers from `lib/` and `includes/` before adding new data or utility logic.
5. Escape user-controlled output with the existing `h()` helper.
6. Validate form input on the server and handle missing or malformed Firebase data safely.
7. Keep authentication checks on protected pages with `tg_require_login()`.
8. Never commit Firebase keys, Mapbox tokens, passwords, or other secrets.
9. Do not silently change Firebase field names, record shapes, roles, or existing routes.
10. Keep changes focused and preserve working features.

## Data and Error Handling

- Check the existing repository functions before calling Firebase directly.
- Treat absent, null, and unexpected values as normal external data conditions.
- Show useful user-facing errors without exposing credentials or internal stack traces.
- Avoid destructive writes unless the requested workflow explicitly requires them.
- Keep timestamps and identifiers compatible with existing records.

## UI and JavaScript

- Follow the existing CSS variables, components, spacing, and responsive behavior.
- Prefer small page-specific scripts in `assets/js/` for reusable browser behavior.
- Use safe DOM APIs and escape dynamic content before inserting it into HTML.
- Preserve keyboard access, meaningful labels, and existing modal behavior.

## Verification

Before finishing a change:

1. Inspect the affected PHP, include, library, and asset files.
2. Check PHP syntax for modified PHP files.
3. Test the affected page or API through XAMPP when possible.
4. Verify login protection, validation, empty states, and error paths.
5. Confirm that unrelated pages and Firebase record structures were not changed.
