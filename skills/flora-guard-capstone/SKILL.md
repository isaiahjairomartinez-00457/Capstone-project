---
name: flora-guard-capstone
description: Maintain the Flora Guard PHP administration system when a task spans project architecture, authentication, Firebase data, shared layouts, dashboard features, or verification across multiple areas.
metadata:
  short-description: Flora Guard project conventions
---

# Flora Guard Capstone

Use this skill for cross-cutting work in the Flora Guard web administration system. For a focused PHP implementation, also read [../php-web-development/SKILL.md](../php-web-development/SKILL.md). For dashboard, map, scan, reports, or analytics work, also read [../dashboard-map/SKILL.md](../dashboard-map/SKILL.md).

## Project context

- The application is a PHP administration system for native flora and tree records in Talaingod, Davao del Norte.
- It runs under XAMPP and Apache and uses Firebase Realtime Database, session-based admin authentication, Mapbox GL JS, Chart.js, HTML, CSS, and vanilla JavaScript.
- Root PHP pages are the main routes. Shared startup and layout code lives in `includes/`; Firebase and data transformation code lives in `lib/`; browser assets live in `assets/`; server endpoints live in `api/`.
- Read `knowledge.md` and `CLAUDE.md` before substantial changes, and inspect the relevant existing implementation before editing it.

## Non-negotiable invariants

1. Preserve routes, Firebase field names, timestamp conventions, existing public APIs, and the current session and admin-role checks.
2. Use `includes/init.php`, shared layout partials, and existing repository helpers instead of duplicating bootstrap, page chrome, or Firebase access.
3. Keep protected pages behind `tg_require_login()`, keep `declare(strict_types=1)` where already present, and escape rendered user-controlled values with `h()`.
4. Treat Firebase data as incomplete or malformed external input. Preserve empty states and return safe, useful errors without exposing credentials or stack traces.
5. Keep Mapbox coordinates in `[longitude, latitude]` order. Use only the existing validated public `pk.` token in browser code; never expose Firebase credentials, passwords, or private tokens.
6. Preserve the established responsive visual system and do not introduce fake dashboard, report, or analytics data.

## Change workflow

1. Identify the affected route, partial, repository helper, asset, and authentication boundary before making changes.
2. Keep the patch focused and reuse existing patterns. Avoid changing the Firebase schema unless the task explicitly requires it.
3. Check modified PHP files with `php -l`, exercise affected pages through XAMPP when possible, and inspect browser-console errors for frontend changes.
4. Verify authentication, server validation, malformed and empty data, mobile layout, and error paths when relevant.
5. Review `git status` and the staged diff. Work on a feature branch and do not commit secrets or unrelated local files.

