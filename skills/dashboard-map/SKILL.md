# Dashboard and Map Development Skill

## Purpose

Help develop and maintain the Flora Guard administration dashboard, mapping, scan monitoring, reports, and analytics features in this repository.

## Owned Features

This skill applies especially to:

- `dashboard.php` and `dashboard_overview.php`
- `map.php`
- `scans.php`
- `reports.php`
- `assets/js/dashboard_map_preview.js`
- `assets/js/scan_map.js`
- Mapbox GL JS integration in `assets/vendor/mapbox-gl/`
- Chart.js dashboard and analytics charts
- Tree-record statistics, conservation statuses, locations, and scan activity

## Responsibilities

This skill assists with:

- Dashboard KPIs and summary cards
- Monthly scan and tree-record trends
- Conservation-status charts
- Map markers, clusters, heatmaps, and layers
- Mapbox token handling and fallback states
- Scan-location detail modals
- Species and location aggregation
- Reports and analytics filtering
- Responsive dashboard layout and empty states

## Data Rules

1. Inspect `lib/data_repository.php` before changing record queries or aggregation logic.
2. Preserve the existing Firebase record field names and status values.
3. Use the existing helpers for coordinates, status buckets, users, and display names.
4. Handle missing coordinates, missing species, empty records, and invalid timestamps without breaking the page.
5. Keep latitude and longitude ordering correct: Mapbox uses `[longitude, latitude]`.
6. Do not expose private Firebase credentials in browser code.
7. Only pass the public Mapbox token to the browser, and preserve the existing `pk.` validation.

## Mapbox Guidelines

- Reuse the existing local Mapbox GL assets and initialization patterns.
- Keep map initialization conditional when the token is missing or invalid.
- Provide a clear fallback message when a map cannot load.
- Resize maps after opening them in a modal or changing their container visibility.
- Keep markers, clusters, heatmaps, legends, and controls synchronized with the active data.
- Avoid creating duplicate map instances or event listeners during refreshes.
- Preserve the existing Talaingod and Davao del Norte map defaults unless the requested feature changes them.

## Chart and Analytics Guidelines

- Derive displayed totals from the same records shown by the page.
- Label units and statuses clearly and keep chart colors consistent with the existing conservation legend.
- Handle zero-value and empty datasets without JavaScript errors.
- Destroy or reuse chart instances when refreshing data to prevent duplicate canvases.
- Keep analytics calculations deterministic and explainable from the underlying records.

## UI Guidelines

- Reuse existing cards, buttons, modal, legend, KPI, and responsive grid styles.
- Keep dashboard controls usable with keyboard and visible state changes.
- Preserve readable mobile layouts for charts, map controls, tables, and modals.
- Escape or safely assign dynamic scan details before displaying them.

## Verification

Before finishing a change:

1. Test with records containing valid coordinates and records without coordinates.
2. Test empty records and missing Mapbox configuration.
3. Check chart totals against the source records.
4. Verify map refresh, layer toggles, markers, clusters, heatmap, and modal behavior when affected.
5. Check the dashboard and map at desktop and mobile widths.
6. Run PHP syntax checks and inspect the browser console for JavaScript errors.
