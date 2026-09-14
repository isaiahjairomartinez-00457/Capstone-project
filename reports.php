<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

tg_require_login();

$records = tg_all_tree_records();
$counts = tg_conservation_counts($records);

$locTally = [];
foreach ($records as $r) {
    $ln = trim((string)($r['locationName'] ?? ''));
    if ($ln === '' || $ln === '-') {
        $ln = 'Unknown';
    }
    $locTally[$ln] = ($locTally[$ln] ?? 0) + 1;
}
arsort($locTally);
$locLabels = array_keys(array_slice($locTally, 0, 6, true));
$locValues = array_values(array_slice($locTally, 0, 6, true));
if (count($locLabels) === 0) {
    $locLabels = ['Tagum City', 'Davao del Norte'];
    $locValues = [0, 0];
}

$speciesTally = [];
foreach ($records as $r) {
    $k = trim((string)($r['title'] ?? 'Unknown'));
    $speciesTally[$k] = ($speciesTally[$k] ?? 0) + 1;
}
arsort($speciesTally);
$topSpecies = array_slice($speciesTally, 0, 5, true);

$locKeys = [];
foreach ($records as $r) {
    $ln = trim((string)($r['locationName'] ?? ''));
    if ($ln !== '' && $ln !== '-') {
        $locKeys[$ln] = true;
    }
}
$locCount = count($locKeys);
$monitoring = $counts['critical'] + $counts['endangered'];

$barLoc = [
    'labels' => $locLabels,
    'datasets' => [['label' => 'Trees', 'data' => $locValues, 'backgroundColor' => '#14532d']],
];
$pie = [
    'labels' => ['Endangered', 'Critically endangered', 'Vulnerable'],
    'datasets' => [[
        'data' => [
            (int)$counts['endangered'],
            (int)$counts['critical'],
            (int)$counts['vulnerable'],
        ],
        'backgroundColor' => ['#ea580c', '#dc2626', '#ca8a04'],
    ]],
];

$pageTitle = 'Reports & Analytics';
$navActive = 'reports';
$extraScripts = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>'
    . '<script>(function(){var L=' . json_encode($barLoc, JSON_THROW_ON_ERROR)
    . ';var P=' . json_encode($pie, JSON_THROW_ON_ERROR) . ';'
    . 'document.addEventListener("DOMContentLoaded",function(){'
    . 'new Chart(document.getElementById("repBar"),{type:"bar",data:L,options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});'
    . 'new Chart(document.getElementById("repPie"),{type:"pie",data:P,options:{responsive:true,maintainAspectRatio:false}});'
    . '});})();</script>';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Reports &amp; Analytics</h1>
        <p>Comprehensive analysis of protected tree data.</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm">&#8681; Export Full Report</button>
</div>

<div class="grid-4" style="margin-bottom:18px">
    <div class="card stat-card">
        <div class="lbl">Total Trees</div>
        <div class="big"><?= (int)$counts['total'] ?></div>
        <div style="font-size:0.75rem;color:var(--green-600);margin-top:6px">+18% from last month <span style="color:var(--muted)">(demo)</span></div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Unique Species</div>
        <div class="big"><?= count(array_unique(array_map(static fn ($r) => (string)($r['title'] ?? ''), $records))) ?></div>
        <div style="font-size:0.75rem;color:var(--muted);margin-top:6px">Protected varieties</div>
    </div>
    <div class="card stat-card">
        <div class="lbl">Locations</div>
        <div class="big"><?= (int)$locCount ?></div>
        <div style="font-size:0.75rem;color:var(--muted);margin-top:6px">Across <?= h(APP_REGION) ?></div>
    </div>
    <div class="card stat-card critical">
        <div class="lbl">Critical Status</div>
        <div class="big"><?= (int)$monitoring ?></div>
        <div style="font-size:0.75rem;color:var(--status-critical);margin-top:6px">Require monitoring</div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:18px">
    <div class="card">
        <h2>Tree Distribution by Location</h2>
        <p class="sub">Recorded trees per area</p>
        <div class="chart-wrap"><canvas id="repBar"></canvas></div>
    </div>
    <div class="card">
        <h2>Conservation Status Distribution</h2>
        <p class="sub">Protected species by threat level</p>
        <div class="chart-wrap"><canvas id="repPie"></canvas></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Most Scanned Species</h2>
        <p class="sub">Top identifications</p>
        <?php if (count($topSpecies) === 0) : ?>
            <p style="color:var(--muted)">No data yet.</p>
        <?php else : ?>
            <ul style="margin:0;padding-left:18px;line-height:1.8">
                <?php foreach ($topSpecies as $sp => $n) : ?>
                    <li><strong><?= h($sp) ?></strong> — <?= (int)$n ?> scans</li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="card">
        <h2>Scan Activity Trend</h2>
        <p class="sub">Last 6 months (placeholder)</p>
        <p style="color:var(--muted);font-size:0.9rem">Connect a time-series query or BigQuery export to render a line chart here.</p>
    </div>
</div>

<?php
require __DIR__ . '/includes/layout_end.php';
