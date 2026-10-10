# Flora Guard Project Knowledge

## Project Purpose

This repository contains the PHP web administration system for the Flora Guard native flora detection and management project in Talaingod, Davao del Norte. The wider capstone system uses computer vision, GPS data, geoanalytics, and cloud-based data management. This repository focuses on the web administration interface.

## Runtime Environment

- Windows development environment
- XAMPP and Apache
- PHP with strict typing in the main application files
- Firebase Realtime Database for authentication-related data and application records
- Mapbox GL JS for geographic visualization
- Chart.js for dashboard charts
- Vanilla JavaScript, HTML, and CSS for the frontend

## Main Application Areas

- `login.php`, `auth_login.php`, and `logout.php` handle authentication flows.
- `includes/` contains shared initialization, layouts, navigation, and UI partials.
- `lib/firebase_client.php` contains Firebase communication.
- `lib/data_repository.php` contains application data access and record helpers.
- `lib/flora_species.php` contains species-related data.
- `dashboard.php` provides summary cards, charts, recent activity, and a map preview.
- `map.php` provides Mapbox monitoring, layers, clusters, heatmap behavior, and map analytics.
- `scans.php` displays scan records and scan-location details.
- `reports.php` provides reporting views.
- `species.php`, `trees.php`, and `tree_records.php` support flora and tree record workflows.
- `users.php` and `user_add.php` support user administration.
- `settings.php` contains application settings.
- `assets/css/` contains application styles.
- `assets/js/` contains page-specific browser behavior.
- `assets/vendor/mapbox-gl/` contains local Mapbox GL assets.
- `api/` contains server-side API endpoints.

## Authentication

Protected pages use the shared initialization file and call `tg_require_login()`. The current user is stored in the PHP session under the existing session structure. Preserve admin-role checks and do not weaken authentication while changing page behavior.

## Data Handling

Firebase records may contain missing, null, or differently formatted values. Use the existing repository helpers for reading records, statuses, users, coordinates, display names, and conservation counts. Preserve existing field names and timestamp conventions. Escape rendered values with `h()`.

## Mapping

Mapbox receives a public token only when it is configured correctly. Existing code validates tokens beginning with `pk.` and provides fallback states when a token is unavailable. Mapbox coordinates use `[longitude, latitude]`, while record data may store latitude and longitude separately or in a coordinate string.

## Development Priorities

1. Preserve existing behavior and data compatibility.
2. Reuse existing helpers and UI patterns.
3. Keep authentication and secrets secure.
4. Handle empty data and invalid external values gracefully.
5. Verify affected PHP pages and JavaScript behavior before committing.
