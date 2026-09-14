<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

tg_require_login();

$records = tg_all_tree_records();
$counts = tg_conservation_counts($records);

$mappedPoints = 0;
$speciesSet = [];
$locationSet = [];
foreach ($records as $r) {
    $ll = tg_record_lat_lng($r);
    if ($ll !== null) {
        $mappedPoints++;
    }
    $sp = trim((string)($r['scientific'] ?? ''));
    if ($sp === '' || $sp === '-') {
        $sp = trim((string)($r['title'] ?? ''));
    }
    if ($sp !== '') {
        $speciesSet[$sp] = true;
    }
    $loc = trim((string)($r['locationName'] ?? ''));
    if ($loc !== '' && $loc !== '-') {
        $locationSet[$loc] = true;
    }
}

$pageTitle = 'Map Monitoring';
$navActive = 'map';
$mapboxToken = tg_mapbox_access_token();
$hasMapbox = strncmp(trim($mapboxToken), 'pk.', 3) === 0;
$tokenJson = json_encode($mapboxToken, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

$extraScripts = '<link href="assets/vendor/mapbox-gl/mapbox-gl.css" rel="stylesheet">'
    . '<script src="assets/vendor/mapbox-gl/mapbox-gl.js"></script>'
    . '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';

require __DIR__ . '/includes/layout_start.php';
?>

<div class="page-head">
    <div>
        <h1>Map Monitoring</h1>
        <p>Dynamic multi-layer geo analytics for tagged trees (points, clusters, heatmap, and charts).</p>
    </div>
    <div style="display:flex;gap:10px">
        <button type="button" class="btn btn-outline btn-sm" id="btnMapRefresh">Refresh Data</button>
        <button type="button" class="btn btn-outline btn-sm" id="btnMapLayers">Layers</button>
    </div>
</div>

<?php if (!$hasMapbox) : ?>
    <div class="alert alert-error" style="margin-bottom:14px">Mapbox token missing or invalid. Set <code>MAPBOX_ACCESS_TOKEN</code> in <code>config.php</code> (must start with <code>pk.</code>).</div>
<?php endif; ?>

<div class="map-analytics-grid">
    <section class="card map-main-card">
        <div class="map-head-kpis">
            <div class="map-kpi">
                <span class="lbl">Mapped Trees</span>
                <strong id="kpiMapped"><?= (int)$mappedPoints ?></strong>
            </div>
            <div class="map-kpi">
                <span class="lbl">Species</span>
                <strong id="kpiSpecies"><?= count($speciesSet) ?></strong>
            </div>
            <div class="map-kpi">
                <span class="lbl">Locations</span>
                <strong id="kpiLocations"><?= count($locationSet) ?></strong>
            </div>
            <div class="map-kpi">
                <span class="lbl">Hotspots</span>
                <strong id="kpiHotspots">-</strong>
            </div>
        </div>
        <div class="map-style-bar" id="mapStyleBar">
            <span style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.04em;padding:2px 4px 4px">Basemap</span>
            <button type="button" class="is-active" data-style="mapbox://styles/mapbox/satellite-streets-v12">Satellite</button>
            <button type="button" data-style="mapbox://styles/mapbox/streets-v12">Street</button>
            <button type="button" data-style="mapbox://styles/mapbox/outdoors-v12">Terrain</button>
            <span class="map-style-bar-divider" aria-hidden="true"></span>
            <span style="font-size:0.72rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.04em;padding:2px 4px 4px">Layers</span>
            <button type="button" class="is-active" id="viewAll" data-view="all">All</button>
            <button type="button" id="viewHeat" data-view="heat">Heatmap</button>
            <button type="button" id="viewPoints" data-view="points">Points</button>
            <button type="button" class="is-active" id="toggleHeat">Heatmap: ON</button>
            <button type="button" class="is-active" id="toggleClusters">Clusters: ON</button>
        </div>
        <div class="map-page-card map-page-card--xl">
            <div id="map"></div>
            <div class="map-legend map-legend--modern">
                <h4>Tag density (heatmap)</h4>
                <div class="map-heat-gradient" aria-hidden="true"></div>
                <div class="map-heat-labels"><span>Low</span><span>High</span></div>
                <h4 style="margin-top:12px">Conservation status</h4>
                <div class="legend-row"><span class="dot" style="background:#dc2626"></span> Critical <span id="legendCritical">(<?= (int)$counts['critical'] ?>)</span></div>
                <div class="legend-row"><span class="dot" style="background:#ea580c"></span> Endangered <span id="legendEndangered">(<?= (int)$counts['endangered'] ?>)</span></div>
                <div class="legend-row"><span class="dot" style="background:#ca8a04"></span> Vulnerable <span id="legendVulnerable">(<?= (int)$counts['vulnerable'] ?>)</span></div>
            </div>
        </div>
    </section>

    <aside class="map-side-panel">
        <div class="card map-side-card">
            <h2>Status Mix</h2>
            <p class="sub">Pie chart from realtime records</p>
            <div class="chart-wrap map-side-chart"><canvas id="mapPie"></canvas></div>
        </div>
        <div class="card map-side-card">
            <h2>Monthly Tags</h2>
            <p class="sub">Recent trend (6 months)</p>
            <div class="chart-wrap map-side-chart"><canvas id="mapTrend"></canvas></div>
        </div>
        <div class="card map-side-card">
            <h2>Top Hotspot Areas</h2>
            <p class="sub">Highest cluster density</p>
            <div id="hotspotList" class="map-hotspot-list">
                <p style="margin:0;color:var(--muted)">Loading hotspot analytics...</p>
            </div>
        </div>
    </aside>
</div>

<script>
(function () {
  var TOKEN = <?= $tokenJson ?>;
  var mapEl = document.getElementById("map");
  if (!mapEl) return;

  function esc(v) {
    return String(v || "").replace(/[&<>'"]/g, function (ch) {
      return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", "'": "&#39;", '"': "&quot;" })[ch];
    });
  }

  if (!TOKEN || TOKEN.indexOf("pk.") !== 0) {
    mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;padding:24px;text-align:center;color:#64748b;font:14px system-ui">Set a valid Mapbox public token (pk.) in <strong>config.php</strong>.</div>';
    return;
  }

  function loadScriptSequential(urls, done) {
    if (!urls.length) {
      done(false);
      return;
    }
    var url = urls.shift();
    var s = document.createElement("script");
    s.src = url;
    s.async = false;
    s.onload = function () { done(true); };
    s.onerror = function () { loadScriptSequential(urls, done); };
    document.head.appendChild(s);
  }

  function ensureMapboxCss() {
    if (document.querySelector('link[data-mapbox-local="1"]')) return;
    ["assets/vendor/mapbox-gl/mapbox-gl.css", "./assets/vendor/mapbox-gl/mapbox-gl.css", "/WEBphp/assets/vendor/mapbox-gl/mapbox-gl.css"]
      .forEach(function (href) {
        var l = document.createElement("link");
        l.rel = "stylesheet";
        l.href = href;
        l.setAttribute("data-mapbox-local", "1");
        document.head.appendChild(l);
      });
  }

  function startMapApp() {
    mapboxgl.accessToken = TOKEN;
    var map = new mapboxgl.Map({
    container: "map",
    style: "mapbox://styles/mapbox/satellite-streets-v12",
    center: [125.8078, 7.4475],
    zoom: 8.7,
    pitch: 40,
    antialias: true
  });

  map.addControl(new mapboxgl.NavigationControl({ showCompass: true, showZoom: true, visualizePitch: true }), "top-right");
  map.addControl(new mapboxgl.FullscreenControl(), "top-right");
  map.addControl(new mapboxgl.ScaleControl({ maxWidth: 120, unit: "metric" }), "bottom-right");

  var chartPie = null;
  var chartTrend = null;
  var heatVisible = true;
  var clustersVisible = true;
  var mapViewMode = "all";

  function bucketStatus(status) {
    var s = String(status || "").toLowerCase().trim();
    if (["critical", "critically_endangered", "critically"].indexOf(s) >= 0) return "critical";
    if (s === "endangered") return "endangered";
    return "vulnerable";
  }

  function reviewStatus(rec) {
    var s = String(rec.scanStatus || rec.reviewStatus || "").toLowerCase().trim();
    if (s === "rejected") return "reclassified";
    if (s === "pending" || s === "approved" || s === "reclassified") return s;
    return "approved";
  }

  function parsePoint(rec) {
    var lat = Number(rec.latitude);
    var lng = Number(rec.longitude);
    if (isFinite(lat) && isFinite(lng)) {
      return { lat: lat, lng: lng };
    }
    var raw = String(rec.coordinates || "");
    if (!raw || raw === "-") return null;
    var parts = raw.trim().split(/[,\s]+/);
    if (parts.length < 2) return null;
    lat = Number(parts[0]);
    lng = Number(parts[1]);
    if (!isFinite(lat) || !isFinite(lng)) return null;
    return { lat: lat, lng: lng };
  }

  /** Show all tagged scans on the map (not only approved). */
  function isMappableRecord(rec) {
    var st = reviewStatus(rec);
    return st === "approved" || st === "pending" || st === "reclassified";
  }

  function groupHotspots(points) {
    var cell = 0.01;
    var bins = Object.create(null);
    points.forEach(function (p) {
      var gx = Math.floor(p.lat / cell);
      var gy = Math.floor(p.lng / cell);
      var key = gx + ":" + gy;
      if (!bins[key]) bins[key] = [];
      bins[key].push(p);
    });
    var out = Object.keys(bins).map(function (k) {
      var arr = bins[k];
      var lat = 0, lng = 0;
      arr.forEach(function (p) { lat += p.lat; lng += p.lng; });
      return { lat: lat / arr.length, lng: lng / arr.length, count: arr.length };
    });
    out.sort(function (a, b) { return b.count - a.count; });
    return out;
  }

  function buildTrend(records) {
    var now = new Date();
    var months = [];
    for (var i = 5; i >= 0; i--) {
      var d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      var key = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
      months.push({ key: key, label: d.toLocaleString(undefined, { month: "short" }), count: 0 });
    }
    var idx = {};
    months.forEach(function (m, i) { idx[m.key] = i; });
    records.forEach(function (r) {
      var ms = Number(r.createdAt || 0);
      if (!isFinite(ms) || ms <= 0) return;
      var d = new Date(ms);
      var key = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
      if (idx[key] !== undefined) months[idx[key]].count++;
    });
    return months;
  }

  function upsertCharts(counts, trendMonths) {
    var pieCtx = document.getElementById("mapPie");
    var trendCtx = document.getElementById("mapTrend");
    if (!pieCtx || !trendCtx || !window.Chart) return;

    var pieData = {
      labels: ["Critical", "Endangered", "Vulnerable"],
      datasets: [{
        data: [counts.critical, counts.endangered, counts.vulnerable],
        backgroundColor: ["#dc2626", "#ea580c", "#ca8a04"]
      }]
    };
    var trendData = {
      labels: trendMonths.map(function (m) { return m.label; }),
      datasets: [{
        label: "Tags",
        data: trendMonths.map(function (m) { return m.count; }),
        backgroundColor: "rgba(20,83,45,.2)",
        borderColor: "#14532d",
        borderWidth: 2,
        fill: true,
        tension: 0.35
      }]
    };

    if (chartPie) {
      chartPie.data = pieData;
      chartPie.update();
    } else {
      chartPie = new Chart(pieCtx, {
        type: "pie",
        data: pieData,
        options: { responsive: true, maintainAspectRatio: false }
      });
    }
    if (chartTrend) {
      chartTrend.data = trendData;
      chartTrend.update();
    } else {
      chartTrend = new Chart(trendCtx, {
        type: "line",
        data: trendData,
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
      });
    }
  }

  function setLegend(counts) {
    var c = document.getElementById("legendCritical");
    var e = document.getElementById("legendEndangered");
    var v = document.getElementById("legendVulnerable");
    if (c) c.textContent = "(" + counts.critical + ")";
    if (e) e.textContent = "(" + counts.endangered + ")";
    if (v) v.textContent = "(" + counts.vulnerable + ")";
  }

  function setKpis(metrics) {
    var mapped = document.getElementById("kpiMapped");
    var species = document.getElementById("kpiSpecies");
    var locations = document.getElementById("kpiLocations");
    var hotspots = document.getElementById("kpiHotspots");
    if (mapped) mapped.textContent = String(metrics.mapped);
    if (species) species.textContent = String(metrics.species);
    if (locations) locations.textContent = String(metrics.locations);
    if (hotspots) hotspots.textContent = String(metrics.hotspots);
  }

  function setHotspotList(items) {
    var el = document.getElementById("hotspotList");
    if (!el) return;
    if (!items.length) {
      el.innerHTML = '<p style="margin:0;color:var(--muted)">No hotspot clusters yet.</p>';
      return;
    }
    el.innerHTML = items.slice(0, 6).map(function (h, idx) {
      return '<div class="hotspot-item"><span class="hotspot-rank">#' + (idx + 1) + '</span><span class="hotspot-coord">' + h.lat.toFixed(4) + ', ' + h.lng.toFixed(4) + '</span><span class="hotspot-count">' + h.count + ' tags</span></div>';
    }).join("");
  }

  var heatPaint = {
    "heatmap-weight": [
      "interpolate", ["linear"], ["get", "weight"],
      0, 0.4,
      1, 1
    ],
    "heatmap-intensity": [
      "interpolate", ["linear"], ["zoom"],
      7, 0.6,
      10, 1.1,
      14, 1.6
    ],
    "heatmap-radius": [
      "interpolate", ["linear"], ["zoom"],
      7, 18,
      10, 28,
      14, 42
    ],
    "heatmap-opacity": [
      "interpolate", ["linear"], ["zoom"],
      7, 0.75,
      14, 0.55
    ],
    "heatmap-color": [
      "interpolate", ["linear"], ["heatmap-density"],
      0, "rgba(34,197,94,0)",
      0.15, "rgba(74,222,128,0.45)",
      0.35, "rgba(250,204,21,0.55)",
      0.55, "rgba(249,115,22,0.7)",
      0.75, "rgba(239,68,68,0.82)",
      1, "rgba(185,28,28,0.95)"
    ]
  };

  function buildGeoJson(points) {
    return {
      type: "FeatureCollection",
      features: points.map(function (p) {
        return {
          type: "Feature",
          properties: {
            title: p.title || "Tree",
            scientific: p.scientific || "-",
            locationName: p.locationName || "-",
            status: p.bucket,
            weight: 1
          },
          geometry: { type: "Point", coordinates: [p.lng, p.lat] }
        };
      })
    };
  }

  function addHeatLayer() {
    if (map.getLayer("heat")) return;
    map.addLayer({
      id: "heat",
      type: "heatmap",
      source: "trees-heat",
      maxzoom: 15,
      paint: heatPaint
    });
  }

  function addPointLayers() {
    if (map.getLayer("clusters")) return;

    map.addLayer({
      id: "clusters",
      type: "circle",
      source: "trees-points",
      filter: ["has", "point_count"],
      paint: {
        "circle-color": "#14532d",
        "circle-stroke-color": "#ffffff",
        "circle-stroke-width": 2,
        "circle-radius": [
          "step", ["get", "point_count"],
          14, 10, 18, 25, 22, 50, 28
        ]
      }
    });
    map.addLayer({
      id: "cluster-count",
      type: "symbol",
      source: "trees-points",
      filter: ["has", "point_count"],
      layout: { "text-field": ["get", "point_count_abbreviated"], "text-size": 12 },
      paint: { "text-color": "#fff" }
    });

    map.addLayer({
      id: "unclustered-glow",
      type: "circle",
      source: "trees-points",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-color": [
          "match", ["get", "status"],
          "critical", "#dc2626",
          "endangered", "#ea580c",
          "#22c55e"
        ],
        "circle-radius": 14,
        "circle-blur": 0.85,
        "circle-opacity": 0.28
      }
    });

    map.addLayer({
      id: "unclustered",
      type: "circle",
      source: "trees-points",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-color": [
          "match", ["get", "status"],
          "critical", "#dc2626",
          "endangered", "#ea580c",
          "#ca8a04"
        ],
        "circle-stroke-color": "#ffffff",
        "circle-stroke-width": 1.5,
        "circle-radius": 6
      }
    });

    map.addLayer({
      id: "unclustered-ring",
      type: "circle",
      source: "trees-points",
      filter: ["!", ["has", "point_count"]],
      paint: {
        "circle-color": "rgba(0,0,0,0)",
        "circle-stroke-color": [
          "match", ["get", "status"],
          "critical", "#fecaca",
          "endangered", "#fed7aa",
          "#bbf7d0"
        ],
        "circle-stroke-width": 2,
        "circle-radius": 10,
        "circle-opacity": 0.9
      }
    });

    map.on("click", "clusters", function (e) {
      var f = e.features && e.features[0];
      if (!f) return;
      var clusterId = f.properties.cluster_id;
      map.getSource("trees-points").getClusterExpansionZoom(clusterId, function (err, zoom) {
        if (err) return;
        map.easeTo({ center: f.geometry.coordinates, zoom: zoom + 0.3, duration: 500 });
      });
    });

    map.on("click", "unclustered", function (e) {
      var f = e.features && e.features[0];
      if (!f) return;
      var p = f.properties || {};
      new mapboxgl.Popup({ closeButton: true, maxWidth: 280 })
        .setLngLat(f.geometry.coordinates)
        .setHTML(
          '<div style="font-size:13px;line-height:1.45">' +
          '<strong>' + esc(p.title) + '</strong><br>' +
          '<span style="color:#475569"><i>' + esc(p.scientific) + '</i></span><br>' +
          '<span style="color:#64748b">Location: ' + esc(p.locationName) + '</span><br>' +
          '<span style="color:#64748b">Status: ' + esc(p.status) + '</span>' +
          '</div>'
        )
        .addTo(map);
    });

    map.on("mouseenter", "clusters", function () { map.getCanvas().style.cursor = "pointer"; });
    map.on("mouseleave", "clusters", function () { map.getCanvas().style.cursor = ""; });
    map.on("mouseenter", "unclustered", function () { map.getCanvas().style.cursor = "pointer"; });
    map.on("mouseleave", "unclustered", function () { map.getCanvas().style.cursor = ""; });
  }

  function rebuildMapLayers(points) {
    var data = buildGeoJson(points);

    if (map.getSource("trees-heat")) {
      map.getSource("trees-heat").setData(data);
      map.getSource("trees-points").setData(data);
      updateLayerVisibility();
      return;
    }

    map.addSource("trees-heat", { type: "geojson", data: data });
    map.addSource("trees-points", {
      type: "geojson",
      data: data,
      cluster: true,
      clusterMaxZoom: 14,
      clusterRadius: 42
    });

    addHeatLayer();
    addPointLayers();
    updateLayerVisibility();
  }

  function setViewMode(mode) {
    mapViewMode = mode;
    if (mode === "heat") {
      heatVisible = true;
      clustersVisible = false;
    } else if (mode === "points") {
      heatVisible = false;
      clustersVisible = true;
    } else {
      heatVisible = true;
      clustersVisible = true;
    }
    var styleBar = document.getElementById("mapStyleBar");
    if (styleBar) {
      styleBar.querySelectorAll("button[data-view]").forEach(function (b) {
        b.classList.toggle("is-active", b.getAttribute("data-view") === mode);
      });
    }
    var toggleHeat = document.getElementById("toggleHeat");
    var toggleClusters = document.getElementById("toggleClusters");
    if (toggleHeat) toggleHeat.textContent = "Heatmap: " + (heatVisible ? "ON" : "OFF");
    if (toggleClusters) toggleClusters.textContent = "Clusters: " + (clustersVisible ? "ON" : "OFF");
    if (toggleHeat) toggleHeat.classList.toggle("is-active", heatVisible);
    if (toggleClusters) toggleClusters.classList.toggle("is-active", clustersVisible);
    updateLayerVisibility();
  }

  function updateLayerVisibility() {
    if (map.getLayer("heat")) {
      map.setLayoutProperty("heat", "visibility", heatVisible ? "visible" : "none");
    }
    var clusterVis = clustersVisible ? "visible" : "none";
    ["clusters", "cluster-count", "unclustered", "unclustered-glow", "unclustered-ring"].forEach(function (id) {
      if (map.getLayer(id)) map.setLayoutProperty(id, "visibility", clusterVis);
    });
  }

  function refreshData(fitBounds) {
    fetch("api/tree_records.php?_t=" + Date.now())
      .then(function (r) { return r.json(); })
      .then(function (json) {
        var records = Array.isArray(json.records) ? json.records : [];
        var mapRecords = records.filter(isMappableRecord);
        var points = [];
        var speciesSet = Object.create(null);
        var locationSet = Object.create(null);
        var counts = { critical: 0, endangered: 0, vulnerable: 0 };
        mapRecords.forEach(function (rec) {
          var pt = parsePoint(rec);
          var bucket = bucketStatus(rec.status || "");
          counts[bucket] += 1;
          var sp = (rec.scientific && rec.scientific !== "-") ? String(rec.scientific) : String(rec.title || "");
          if (sp) speciesSet[sp] = true;
          var loc = String(rec.locationName || "").trim();
          if (loc && loc !== "-") locationSet[loc] = true;
          if (!pt) return;
          points.push({
            lat: pt.lat, lng: pt.lng,
            title: String(rec.title || "Tree"),
            scientific: String(rec.scientific || "-"),
            locationName: String(rec.locationName || "-"),
            bucket: bucket,
            createdAt: Number(rec.createdAt || 0)
          });
        });

        var hotspots = groupHotspots(points);
        setKpis({
          mapped: points.length,
          species: Object.keys(speciesSet).length,
          locations: Object.keys(locationSet).length,
          hotspots: hotspots.length
        });
        setLegend(counts);
        setHotspotList(hotspots);
        upsertCharts(counts, buildTrend(mapRecords));
        rebuildMapLayers(points);
        updateLayerVisibility();

        if (fitBounds && points.length > 0) {
          var b = new mapboxgl.LngLatBounds();
          points.forEach(function (p) { b.extend([p.lng, p.lat]); });
          map.fitBounds(b, { padding: 90, maxZoom: 14, duration: 800 });
        }
      })
      .catch(function () {
        // keep last rendered state
      });
  }

  map.on("load", function () {
    refreshData(true);
  });

  setInterval(function () {
    refreshData(false);
  }, 12000);

  var styleBar = document.getElementById("mapStyleBar");
  if (styleBar) {
    styleBar.querySelectorAll("button[data-style]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var style = this.getAttribute("data-style");
        if (!style) return;
        styleBar.querySelectorAll("button[data-style]").forEach(function (b) { b.classList.remove("is-active"); });
        this.classList.add("is-active");
        map.setStyle(style);
        map.once("style.load", function () {
          refreshData(false);
        });
      });
    });
  }

  var toggleHeat = document.getElementById("toggleHeat");
  if (toggleHeat) {
    toggleHeat.addEventListener("click", function () {
      heatVisible = !heatVisible;
      this.textContent = "Heatmap: " + (heatVisible ? "ON" : "OFF");
      this.classList.toggle("is-active", heatVisible);
      syncViewModeButtons();
      updateLayerVisibility();
    });
  }
  var toggleClusters = document.getElementById("toggleClusters");
  if (toggleClusters) {
    toggleClusters.addEventListener("click", function () {
      clustersVisible = !clustersVisible;
      this.textContent = "Clusters: " + (clustersVisible ? "ON" : "OFF");
      this.classList.toggle("is-active", clustersVisible);
      syncViewModeButtons();
      updateLayerVisibility();
    });
  }

  function syncViewModeButtons() {
    var mode = "all";
    if (heatVisible && !clustersVisible) mode = "heat";
    else if (!heatVisible && clustersVisible) mode = "points";
    var styleBar = document.getElementById("mapStyleBar");
    if (styleBar) {
      styleBar.querySelectorAll("button[data-view]").forEach(function (b) {
        b.classList.toggle("is-active", b.getAttribute("data-view") === mode);
      });
    }
  }

  ["viewAll", "viewHeat", "viewPoints"].forEach(function (id) {
    var btn = document.getElementById(id);
    if (!btn) return;
    btn.addEventListener("click", function () {
      setViewMode(this.getAttribute("data-view") || "all");
    });
  });
  var btnLayers = document.getElementById("btnMapLayers");
  if (btnLayers && styleBar) {
    btnLayers.addEventListener("click", function () {
      styleBar.classList.toggle("is-collapsed");
    });
  }
    var btnRefresh = document.getElementById("btnMapRefresh");
    if (btnRefresh) {
      btnRefresh.addEventListener("click", function () {
        refreshData(false);
      });
    }
  }

  if (typeof window.mapboxgl !== "undefined") {
    startMapApp();
  } else {
    ensureMapboxCss();
    loadScriptSequential(
      [
        "assets/vendor/mapbox-gl/mapbox-gl.js",
        "./assets/vendor/mapbox-gl/mapbox-gl.js",
        "/WEBphp/assets/vendor/mapbox-gl/mapbox-gl.js",
      ],
      function (ok) {
        if (!ok || typeof window.mapboxgl === "undefined") {
          mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;padding:24px;text-align:center;color:#64748b;font:14px system-ui">Mapbox library failed to load from local assets. Open <code>WEBphp/assets/vendor/mapbox-gl/</code> and verify files exist, then refresh.</div>';
          return;
        }
        startMapApp();
      }
    );
  }
})();
</script>

<?php
require __DIR__ . '/includes/layout_end.php';
