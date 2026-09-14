<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/lib/flora_species.php';

tg_require_login();
tg_session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['scan_action'] ?? '');
    $ruid = (string)($_POST['record_uid'] ?? '');
    $rid = (string)($_POST['record_id'] ?? '');
    $f = (string)($_GET['filter'] ?? 'all');
    $flash = '';

    if ($ruid !== '' && $rid !== '') {
        if ($action === 'approve') {
            if (firebase_db_patch('tree_records/' . $ruid . '/' . $rid, ['scanStatus' => 'approved'])) {
                $flash = 'approved';
            } else {
                $flash = 'error';
            }
        } elseif ($action === 'reclassify') {
            $speciesId = (string)($_POST['species_id'] ?? '');
            $profile = tg_flora_species_by_id($speciesId);
            if ($profile !== null && in_array($speciesId, ['anahaw', 'narra'], true)) {
                $ok = firebase_db_patch('tree_records/' . $ruid . '/' . $rid, [
                    'scanStatus' => 'reclassified',
                    'speciesId' => $speciesId,
                    'title' => $profile['common'],
                    'scientific' => $profile['scientific'],
                ]);
                $flash = $ok ? 'reclassified' : 'error';
            } else {
                $flash = 'invalid_species';
            }
        }
    }

    $qs = 'filter=' . rawurlencode($f);
    if ($flash !== '') {
        $qs .= '&msg=' . rawurlencode($flash);
    }
    header('Location: scans.php?' . $qs, true, 302);
    exit;
}

$records = tg_all_tree_records();
$users = tg_all_users();
$scanCounts = tg_scan_counts($records);
$detectableSpecies = array_values(array_filter(
    tg_flora_species_profiles(),
    static fn (array $p): bool => in_array($p['id'], ['anahaw', 'narra'], true)
));

$filter = strtolower((string)($_GET['filter'] ?? 'all'));
if (!in_array($filter, ['all', 'pending', 'approved', 'reclassified'], true)) {
    $filter = 'all';
}

$filtered = $records;
if ($filter !== 'all') {
    $filtered = array_values(array_filter($records, static function ($r) use ($filter) {
        $st = tg_scan_review_status($r);
        if ($filter === 'reclassified') {
            return $st === 'reclassified' || $st === 'rejected';
        }
        return $st === $filter;
    }));
}

$flashMsg = (string)($_GET['msg'] ?? '');

$pageTitle = 'Scan Records';
$navActive = 'scans';
$mapboxToken = tg_mapbox_access_token();
$hasMapbox = strncmp(trim($mapboxToken), 'pk.', 3) === 0;
$mapTokenJson = json_encode($mapboxToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$scanMapJsV = (string)@filemtime(__DIR__ . '/assets/js/scan_map.js');
$extraScripts = '';
if ($hasMapbox) {
    $extraScripts =
        '<link href="assets/vendor/mapbox-gl/mapbox-gl.css" rel="stylesheet">'
        . '<script src="assets/vendor/mapbox-gl/mapbox-gl.js"></script>'
        . '<script>window.TG_MAPBOX_TOKEN=' . $mapTokenJson . ';</script>'
        . '<script src="assets/js/scan_map.js?v=' . h($scanMapJsV) . '"></script>';
}

require __DIR__ . '/includes/layout_start.php';

?>
<style>
    /* ---- Scan Records page styles (scoped, self-contained) ---- */
    .scan-page-wrap { --sp-radius: 14px; --sp-radius-sm: 10px; }

    .page-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
    .page-head h1 { margin: 0 0 4px; font-size: 1.6rem; font-weight: 800; }
    .page-head p { margin: 0; color: var(--muted, #6b7a70); font-size: 0.95rem; }

    .scan-flash { border-radius: var(--sp-radius-sm); margin-bottom: 16px; }

    .stat-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }
    .stat-tile {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e3ece6);
        border-radius: var(--sp-radius);
        padding: 16px 18px;
        box-shadow: 0 1px 2px rgba(16, 40, 29, 0.04);
    }
    .stat-tile .lbl { color: var(--muted, #6b7a70); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
    .stat-tile .big { font-size: 1.6rem; font-weight: 800; margin-top: 4px; }
    .stat-tile.pending { background: #fff8e6; border-color: #f3e2ad; }
    .stat-tile.approved { background: #e9f7ef; border-color: #bfe6cf; }
    .stat-tile.reclassified { background: #eef0ff; border-color: #cfd3f7; }

    .filter-pills { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
    .filter-pills a {
        display: inline-flex;
        align-items: center;
        padding: 7px 16px;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--text, #1f3b2c);
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e3ece6);
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .filter-pills a:hover { border-color: var(--brand, #1f6f4a); }
    .filter-pills a.is-active {
        background: var(--brand, #1f6f4a);
        border-color: var(--brand, #1f6f4a);
        color: #fff;
    }

    .scan-page-hint {
        font-size: 0.88rem;
        color: var(--muted, #6b7a70);
        margin: 0 0 16px;
    }
    .scan-page-hint strong { color: var(--text, #1f3b2c); }

    .scan-list { display: flex; flex-direction: column; gap: 16px; }

    .scan-card {
        background: var(--card-bg, #fff);
        border: 1px solid var(--border, #e3ece6);
        border-left: 4px solid var(--border, #e3ece6);
        border-radius: var(--sp-radius);
        padding: 18px 20px;
        box-shadow: 0 1px 2px rgba(16, 40, 29, 0.04);
    }
    .scan-card--pending { border-left-color: #e2b93b; }
    .scan-card--approved { border-left-color: #2ea86b; }
    .scan-card--reclassified,
    .scan-card--rejected { border-left-color: #6c6fe0; }

    .scan-card__header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .scan-card__title { margin: 0; font-size: 1.15rem; font-weight: 800; color: var(--text, #1f3b2c); }
    .scan-card__sci { margin: 2px 0 0; color: var(--muted, #6b7a70); font-size: 0.88rem; }

    .scan-card__header-badges { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
    }
    .badge-pending { background: #fff2cc; color: #8a6a00; }
    .badge-approved { background: #d9f3e4; color: #146c3f; }
    .badge-reclassified { background: #e2e2fb; color: #3f42a8; }

    .scan-confidence {
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--muted, #6b7a70);
        background: var(--chip-bg, #f0f4f1);
        padding: 4px 10px;
        border-radius: 999px;
    }

    .scan-card__main {
        display: grid;
        grid-template-columns: 160px 1fr;
        gap: 18px;
        align-items: start;
    }

    .scan-card__media { display: flex; flex-direction: column; align-items: center; gap: 6px; }
    .scan-thumb-btn {
        display: block;
        width: 100%;
        aspect-ratio: 1 / 1;
        padding: 0;
        border: 1px solid var(--border, #e3ece6);
        border-radius: var(--sp-radius-sm);
        overflow: hidden;
        cursor: zoom-in;
        background: var(--chip-bg, #f0f4f1);
    }
    .scan-thumb-img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .scan-thumb-hint { font-size: 0.72rem; color: var(--muted, #6b7a70); }
    .scan-thumb-placeholder {
        width: 100%;
        aspect-ratio: 1 / 1;
        border-radius: var(--sp-radius-sm);
        background: linear-gradient(135deg, #eafbf1, #d6f0e0);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #2ea86b;
        gap: 6px;
    }
    .scan-thumb-placeholder span { font-size: 0.78rem; font-weight: 700; color: #1f6f4a; }

    .scan-facts {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px 18px;
        margin: 0;
    }
    .scan-facts__item dt {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: var(--muted, #6b7a70);
        margin-bottom: 2px;
    }
    .scan-facts__item dd { margin: 0; font-size: 0.92rem; color: var(--text, #1f3b2c); font-weight: 600; }
    .scan-facts__code {
        background: var(--chip-bg, #f0f4f1);
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.82rem;
    }

    .scan-card__footer {
        margin-top: 16px;
        padding-top: 14px;
        border-top: 1px solid var(--border, #e3ece6);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .scan-card__toolbar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .scan-toolbar-form { margin: 0; }

    .btn { display: inline-flex; align-items: center; gap: 6px; font-weight: 700; cursor: pointer; }
    .btn-sm { padding: 7px 14px; font-size: 0.85rem; border-radius: 8px; }
    .btn svg { width: 15px; height: 15px; flex: none; }

    .scan-map-btn {
        background: #fff;
        border: 1px solid var(--border, #e3ece6);
        color: var(--text, #1f3b2c);
    }
    .scan-map-btn:hover { border-color: var(--brand, #1f6f4a); color: var(--brand, #1f6f4a); }

    .scan-reclassify-bar {
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        background: var(--chip-bg, #f6f9f7);
        border: 1px solid var(--border, #e3ece6);
        border-radius: var(--sp-radius-sm);
        padding: 10px 12px;
    }
    .scan-reclassify-bar__label { font-size: 0.82rem; font-weight: 700; color: var(--muted, #6b7a70); }
    .scan-species-select {
        flex: 1 1 160px;
        min-width: 140px;
        padding: 7px 10px;
        border-radius: 8px;
        border: 1px solid var(--border, #e3ece6);
        background: #fff;
        font-size: 0.88rem;
    }

    .scan-map-modal.modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(16, 40, 29, 0.45);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        padding: 20px;
    }
    .scan-map-modal.is-open { display: flex; }
    .scan-map-modal__panel {
        background: #fff;
        border-radius: 16px;
        width: min(720px, 100%);
        max-height: 90vh;
        overflow-y: auto;
        padding: 22px;
        position: relative;
        box-shadow: 0 18px 40px rgba(16, 40, 29, 0.25);
    }
    .scan-map-modal__close {
        position: absolute;
        top: 14px;
        right: 14px;
        border: none;
        background: var(--chip-bg, #f0f4f1);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        font-size: 1.1rem;
        line-height: 1;
        cursor: pointer;
    }
    .scan-map-modal__title { margin: 0 0 2px; font-size: 1.1rem; font-weight: 800; }
    .scan-map-modal__meta { margin: 0 0 14px; color: var(--muted, #6b7a70); font-size: 0.85rem; }
    .scan-map-modal__map { height: 260px; border-radius: 12px; overflow: hidden; background: var(--chip-bg, #f0f4f1); margin-bottom: 16px; }
    .scan-map-modal__details-title { font-size: 0.85rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.03em; color: var(--muted, #6b7a70); margin: 0 0 10px; }
    .scan-map-details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px 16px; }
    .scan-map-modal__actions { margin-top: 16px; text-align: right; }

    /* Lightbox for enlarged photo */
    .scan-lightbox {
        position: fixed;
        inset: 0;
        background: rgba(10, 20, 15, 0.85);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1100;
        padding: 24px;
    }
    .scan-lightbox.is-open { display: flex; }
    .scan-lightbox img { max-width: 100%; max-height: 85vh; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.4); }
    .scan-lightbox__close {
        position: absolute;
        top: 20px;
        right: 24px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: none;
        background: rgba(255,255,255,0.15);
        color: #fff;
        font-size: 1.3rem;
        cursor: pointer;
    }

    @media (max-width: 900px) {
        .stat-row { grid-template-columns: repeat(2, 1fr); }
        .scan-card__main { grid-template-columns: 110px 1fr; }
        .scan-map-details-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .page-head { flex-direction: column; }
        .stat-row { grid-template-columns: 1fr 1fr; }
        .scan-card__main { grid-template-columns: 1fr; }
        .scan-card__media { flex-direction: row; }
        .scan-thumb-btn, .scan-thumb-placeholder { width: 96px; aspect-ratio: 1/1; }
        .scan-card__footer { gap: 12px; }
        .scan-reclassify-bar { flex-direction: column; align-items: stretch; }
        .scan-species-select { width: 100%; }
    }
</style>

<div class="scan-page-wrap">

<div class="page-head">
    <div>
        <h1>Scan Records</h1>
        <p>Review captured flora images, coordinates, and validator corrections.</p>
    </div>
</div>

<?php if ($flashMsg === 'approved') : ?>
    <div class="alert alert-success scan-flash" role="status">Scan approved.</div>
<?php elseif ($flashMsg === 'reclassified') : ?>
    <div class="alert alert-success scan-flash" role="status">Species reclassified successfully.</div>
<?php elseif ($flashMsg === 'invalid_species') : ?>
    <div class="alert alert-error scan-flash" role="alert">Choose a valid species (Anahaw or Narra).</div>
<?php elseif ($flashMsg === 'error') : ?>
    <div class="alert alert-error scan-flash" role="alert">Could not update this record. Try again.</div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat-tile">
        <div class="lbl">Total Scans</div>
        <div class="big"><?= count($records) ?></div>
    </div>
    <div class="stat-tile pending">
        <div class="lbl">Pending</div>
        <div class="big"><?= (int)$scanCounts['pending'] ?></div>
    </div>
    <div class="stat-tile approved">
        <div class="lbl">Approved</div>
        <div class="big"><?= (int)$scanCounts['approved'] ?></div>
    </div>
    <div class="stat-tile reclassified">
        <div class="lbl">Reclassified</div>
        <div class="big"><?= (int)$scanCounts['reclassified'] ?></div>
    </div>
</div>

<p style="font-size:0.9rem;margin:0 0 8px"><strong>Filter by status:</strong></p>
<div class="filter-pills">
    <?php
    $pills = [
        'all' => 'All',
        'pending' => 'Pending',
        'approved' => 'Approved',
        'reclassified' => 'Reclassified',
    ];
    foreach ($pills as $key => $label) :
        ?>
        <a href="scans.php?filter=<?= h($key) ?>" class="<?= $filter === $key ? 'is-active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
</div>

<p class="scan-page-hint">Review each scan below — tap the photo to enlarge or use <strong>View on map</strong> for location and full details.</p>

<div class="scan-list">
<?php foreach ($filtered as $r) : ?>
    <?php
    $uid = (string)($r['uid'] ?? '');
    $rid = (string)($r['id'] ?? '');
    $st = tg_scan_review_status($r);
    $badgeClass = match ($st) {
        'pending' => 'badge-pending',
        'reclassified', 'rejected' => 'badge-reclassified',
        default => 'badge-approved',
    };
    $badgeLabel = tg_scan_review_label($st);
    $canApprove = tg_scan_can_approve($st);
    $canReclassify = tg_scan_can_reclassify($st);
    $officer = tg_user_display_name($uid, $users);
    $ts = (int)($r['createdAt'] ?? 0);
    $when = $ts > 0 ? date('M j, Y, h:i A', (int)floor($ts / 1000)) : '—';
    $conf = (float)($r['confidence'] ?? 0);
    $imageUrl = trim((string)($r['imageUrl'] ?? ''));
    $ll = tg_record_lat_lng($r);
    if ($ll !== null) {
        $coords = sprintf('%.6f, %.6f', $ll[0], $ll[1]);
        $hasCoords = true;
        $mapLat = $ll[0];
        $mapLng = $ll[1];
    } else {
        $coords = (string)($r['coordinates'] ?? '—');
        $hasCoords = false;
        $mapLat = 0.0;
        $mapLng = 0.0;
    }
    $speciesId = (string)($r['speciesId'] ?? '');
    $scanTitle = (string)($r['title'] ?? 'Scan');
    $conservationBucket = tg_status_bucket((string)($r['status'] ?? ''));
    $floraProfile = $speciesId !== '' ? tg_flora_species_by_id($speciesId) : null;

    $scanMapPayload = [
        'title' => $scanTitle,
        'scientific' => (string)($r['scientific'] ?? '—'),
        'speciesId' => $speciesId !== '' ? $speciesId : '—',
        'conservationStatus' => tg_status_label($conservationBucket),
        'scanReviewStatus' => $badgeLabel,
        'location' => (string)($r['locationName'] ?? '—'),
        'coordinates' => $coords,
        'latitude' => $hasCoords ? (string)$mapLat : '—',
        'longitude' => $hasCoords ? (string)$mapLng : '—',
        'scannedBy' => $officer,
        'scannedAt' => $when,
        'confidence' => (string)round($conf) . '%',
        'recordId' => $rid,
        'userId' => $uid,
        'hasImage' => $imageUrl !== '' ? 'Yes' : 'No',
    ];

    if (is_array($floraProfile)) {
        $scanMapPayload['origin'] = (string)($floraProfile['origin'] ?? '—');
        $scanMapPayload['habitat'] = (string)($floraProfile['distribution_habitat']['habitat'] ?? '—');
        $scanMapPayload['distribution'] = (string)($floraProfile['distribution_habitat']['distribution'] ?? '—');
        $scanMapPayload['climate'] = (string)($floraProfile['distribution_habitat']['climate'] ?? '—');
        $scanMapPayload['floraConservation'] = (string)($floraProfile['conservation'] ?? '—');
        $desc = (string)($floraProfile['description'] ?? '');
        if ($desc !== '') {
            $scanMapPayload['description'] = $desc;
        }
    }

    $scanMapJson = htmlspecialchars(
        json_encode($scanMapPayload, JSON_THROW_ON_ERROR),
        ENT_QUOTES,
        'UTF-8'
    );
    ?>
    <article class="scan-card scan-card--<?= h($st) ?>">
        <header class="scan-card__header">
            <div class="scan-card__title-block">
                <h2 class="scan-card__title"><?= h($scanTitle) ?></h2>
                <p class="scan-card__sci"><em><?= h((string)($r['scientific'] ?? '—')) ?></em></p>
            </div>
            <div class="scan-card__header-badges">
                <span class="badge <?= $badgeClass ?>"><?= h($badgeLabel) ?></span>
                <span class="scan-confidence"><?= h((string)round($conf)) ?>% match</span>
            </div>
        </header>

        <div class="scan-card__main">
            <div class="scan-card__media">
                <?php if ($imageUrl !== '') : ?>
                    <button
                        type="button"
                        class="scan-thumb-btn"
                        data-scan-image="<?= h($imageUrl) ?>"
                        data-scan-title="<?= h($scanTitle) ?>"
                        aria-label="Enlarge captured scan for <?= h($scanTitle) ?>"
                    >
                        <img
                            src="<?= h($imageUrl) ?>"
                            alt="Captured scan — <?= h($scanTitle) ?>"
                            class="scan-thumb-img"
                            loading="lazy"
                            data-scan-thumb
                        >
                    </button>
                    <span class="scan-thumb-hint">Tap to enlarge</span>
                <?php else : ?>
                    <div class="scan-thumb-placeholder" aria-hidden="true">
                        <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1Z"></path>
                            <circle cx="12" cy="13" r="3.2"></circle>
                        </svg>
                        <span>No photo</span>
                    </div>
                <?php endif; ?>
            </div>

            <dl class="scan-facts">
                <div class="scan-facts__item">
                    <dt>Scanned</dt>
                    <dd><?= h($when) ?></dd>
                </div>
                <div class="scan-facts__item">
                    <dt>Officer</dt>
                    <dd><?= h($officer) ?></dd>
                </div>
                <div class="scan-facts__item">
                    <dt>Location</dt>
                    <dd><?= h((string)($r['locationName'] ?? '—')) ?></dd>
                </div>
                <div class="scan-facts__item">
                    <dt>Coordinates</dt>
                    <dd><?= h($coords) ?></dd>
                </div>
                <?php if ($speciesId !== '') : ?>
                    <div class="scan-facts__item">
                        <dt>Species ID</dt>
                        <dd><code class="scan-facts__code"><?= h($speciesId) ?></code></dd>
                    </div>
                <?php endif; ?>
                <div class="scan-facts__item">
                    <dt>Conservation</dt>
                    <dd><?= h(tg_status_label($conservationBucket)) ?></dd>
                </div>
            </dl>
        </div>

        <footer class="scan-card__footer">
            <div class="scan-card__toolbar">
                <?php if ($hasCoords && $hasMapbox) : ?>
                    <button
                        type="button"
                        class="btn btn-outline btn-sm scan-map-btn"
                        data-lat="<?= h((string)$mapLat) ?>"
                        data-lng="<?= h((string)$mapLng) ?>"
                        data-scan-detail="<?= $scanMapJson ?>"
                    >
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="currentColor" aria-hidden="true">
                            <path d="M12 2C7.86 2 4.5 5.36 4.5 9.5c0 5.25 6.3 11.6 6.57 11.87a1.33 1.33 0 0 0 1.86 0c.27-.27 6.57-6.62 6.57-11.87C19.5 5.36 16.14 2 12 2Zm0 10.25a2.75 2.75 0 1 1 0-5.5 2.75 2.75 0 0 1 0 5.5Z"></path>
                        </svg>
                        View on map
                    </button>
                <?php endif; ?>
                <?php if ($canApprove) : ?>
                    <form method="post" action="scans.php?filter=<?= h($filter) ?>" class="scan-toolbar-form">
                        <input type="hidden" name="record_uid" value="<?= h($uid) ?>">
                        <input type="hidden" name="record_id" value="<?= h($rid) ?>">
                        <button type="submit" name="scan_action" value="approve" class="btn btn-primary btn-sm">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 6 9 17l-5-5"></path>
                            </svg>
                            Approve
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <?php if ($canReclassify) : ?>
                <form method="post" action="scans.php?filter=<?= h($filter) ?>" class="scan-reclassify-bar">
                    <input type="hidden" name="record_uid" value="<?= h($uid) ?>">
                    <input type="hidden" name="record_id" value="<?= h($rid) ?>">
                    <label class="scan-reclassify-bar__label" for="species-<?= h($rid) ?>">Reclassify</label>
                    <select id="species-<?= h($rid) ?>" name="species_id" class="scan-species-select" required>
                        <?php foreach ($detectableSpecies as $sp) : ?>
                            <option value="<?= h($sp['id']) ?>"<?= $speciesId === $sp['id'] ? ' selected' : '' ?>>
                                <?= h($sp['common']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="scan_action" value="reclassify" class="btn btn-outline btn-sm">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 12a9 9 0 0 1 15.4-6.4L21 8"></path>
                            <path d="M21 3v5h-5"></path>
                            <path d="M21 12a9 9 0 0 1-15.4 6.4L3 16"></path>
                            <path d="M3 21v-5h5"></path>
                        </svg>
                        Apply
                    </button>
                </form>
            <?php endif; ?>
        </footer>
    </article>
<?php endforeach; ?>
</div>

<?php if (count($filtered) === 0) : ?>
    <div class="card"><p style="margin:0;color:var(--muted)">No scan records for this filter.</p></div>
<?php endif; ?>

</div>

<?php if ($hasMapbox) : ?>
<div id="scanMapModal" class="modal-overlay scan-map-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="scanMapModalTitle">
    <div class="scan-map-modal__panel" role="document">
        <button type="button" class="scan-map-modal__close" id="scanMapModalClose" aria-label="Close map">&times;</button>
        <div class="scan-map-modal__head">
            <h3 id="scanMapModalTitle" class="scan-map-modal__title">Tagged location</h3>
            <p id="scanMapModalMeta" class="scan-map-modal__meta"></p>
        </div>
        <div id="scanMapCanvas" class="scan-map-modal__map" aria-label="Map showing this scan only"></div>
        <section class="scan-map-modal__details" aria-labelledby="scanMapDetailsHeading">
            <h4 id="scanMapDetailsHeading" class="scan-map-modal__details-title">Scan details</h4>
            <div id="scanMapDetails" class="scan-map-details-grid"></div>
        </section>
        <div class="scan-map-modal__actions">
            <button type="button" class="btn btn-primary btn-sm" id="scanMapModalDone">Close</button>
        </div>
    </div>
</div>
<?php endif; ?>

<div id="scanLightbox" class="scan-lightbox" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="scanLightboxTitle">
    <button type="button" class="scan-lightbox__close" id="scanLightboxClose" aria-label="Close photo">&times;</button>
    <img id="scanLightboxImg" src="" alt="">
</div>

<script>
(function () {
    // Photo lightbox — tap thumbnail to enlarge
    var lightbox = document.getElementById('scanLightbox');
    var lightboxImg = document.getElementById('scanLightboxImg');
    if (lightbox && lightboxImg) {
        document.querySelectorAll('[data-scan-image]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var src = btn.getAttribute('data-scan-image');
                var title = btn.getAttribute('data-scan-title') || '';
                if (!src) { return; }
                lightboxImg.src = src;
                lightboxImg.alt = title;
                lightbox.classList.add('is-open');
                lightbox.setAttribute('aria-hidden', 'false');
            });
        });
        var closeLightbox = function () {
            lightbox.classList.remove('is-open');
            lightbox.setAttribute('aria-hidden', 'true');
            lightboxImg.src = '';
        };
        var lbClose = document.getElementById('scanLightboxClose');
        if (lbClose) { lbClose.addEventListener('click', closeLightbox); }
        lightbox.addEventListener('click', function (e) {
            if (e.target === lightbox) { closeLightbox(); }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeLightbox(); }
        });
    }
})();
</script>

<?php
require __DIR__ . '/includes/layout_end.php';