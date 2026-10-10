# Flora Guard PHP Web Application

## Project Overview

This repository contains the PHP web application of the Flora Guard native flora detection and management system.

The system provides authentication, dashboard monitoring, flora records, scan records, reports, species information, and Mapbox-based mapping.

## Development Environment

- PHP
- XAMPP
- Apache
- Firebase Realtime Database
- HTML
- CSS
- JavaScript
- Chart.js
- Mapbox

## Project Structure

- `assets/` - CSS, JavaScript, images, and vendor libraries
- `includes/` - Shared PHP layouts and partials
- `lib/` - Firebase and data repository functionality
- `skills/` - AI development skills
- PHP files at the repository root - Main application pages

## Important Features

- User authentication
- Dashboard
- Flora species management
- Flora scan records
- Reports
- Map visualization
- Analytics
- Settings

## Development Rules

1. Read `knowledge.md` and `AGENTS.md` before making major changes.
2. Inspect existing code before modifying it.
3. Preserve existing functionality.
4. Do not unnecessarily rewrite working code.
5. Keep PHP, HTML, CSS, and JavaScript organized.
6. Do not expose credentials or API keys.
7. Validate user input.
8. Handle database errors properly.
9. Test changes before committing.
10. Keep changes focused on the requested task.

## Firebase

Use the existing Firebase implementation in `lib/`.

Do not change the existing database structure without checking how the current application uses it.

## Mapbox

The application uses Mapbox for geographic visualization.

Preserve existing map functionality when modifying map-related code.

## Dashboard

Dashboard statistics and charts should use the existing data sources and should not introduce fake data.

## Git

Work on a feature branch instead of directly modifying `main`.

Before committing:

1. Check `git status`.
2. Review changed files.
3. Test the application.
4. Commit with a descriptive message.
