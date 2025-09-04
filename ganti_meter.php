<?php
// dashboard_ganti_meter_v3.php

// DATA SUMBER
$data = [
    ["UP3 BEKASI","JANUARI",1700,2834,166.71],["ULP BEKASI KOTA","JANUARI",416,511,122.84],["ULP MEDAN SATRIA","JANUARI",239,384,160.67],["ULP BABELAN","JANUARI",330,560,169.70],["ULP BANTARGEBANG","JANUARI",367,702,191.28],["ULP MUSTIKAJAYA","JANUARI",344,677,196.80],["ULP PRIMA BEKASI","JANUARI",4,0,0.00],
    ["UP3 BEKASI","FEBRUARI",3490,5507,157.79],["ULP BEKASI KOTA","FEBRUARI",855,950,111.11],["ULP MEDAN SATRIA","FEBRUARI",489,777,158.90],["ULP BABELAN","FEBRUARI",678,1123,165.63],["ULP BANTARGEBANG","FEBRUARI",755,1383,183.18],["ULP MUSTIKAJAYA","FEBRUARI",705,1274,180.71],["ULP PRIMA BEKASI","FEBRUARI",8,0,0.00],
    ["UP3 BEKASI","MARET",5190,8970,172.83],["ULP BEKASI KOTA","MARET",1271,1751,137.77],["ULP MEDAN SATRIA","MARET",728,1233,169.37],["ULP BABELAN","MARET",1008,1785,177.08],["ULP BANTARGEBANG","MARET",1122,2216,197.50],["ULP MUSTIKAJAYA","MARET",1049,1970,187.80],["ULP PRIMA BEKASI","MARET",12,15,125.00],
    ["UP3 BEKASI","APRIL",6619,11607,175.36],["ULP BEKASI KOTA","APRIL",1621,2298,141.76],["ULP MEDAN SATRIA","APRIL",928,1548,166.81],["ULP BABELAN","APRIL",1285,2343,182.33],["ULP BANTARGEBANG","APRIL",1431,2761,192.94],["ULP MUSTIKAJAYA","APRIL",1338,2634,196.86],["ULP PRIMA BEKASI","APRIL",16,23,143.75],
    ["UP3 BEKASI","MEI",8143,14423,177.12],["ULP BEKASI KOTA","MEI",1994,2860,143.43],["ULP MEDAN SATRIA","MEI",1141,1852,162.31],["ULP BABELAN","MEI",1581,2909,184.00],["ULP BANTARGEBANG","MEI",1761,3331,189.15],["ULP MUSTIKAJAYA","MEI",1646,3438,208.87],["ULP PRIMA BEKASI","MEI",20,33,165.00],
    ["UP3 BEKASI","JUNI",9753,17434,178.76],["ULP BEKASI KOTA","JUNI",2388,3536,148.07],["ULP MEDAN SATRIA","JUNI",1367,2147,157.06],["ULP BABELAN","JUNI",1894,3529,186.33],["ULP BANTARGEBANG","JUNI",2109,4013,190.28],["ULP MUSTIKAJAYA","JUNI",1971,4169,211.52],["ULP PRIMA BEKASI","JUNI",24,40,166.67],
    ["UP3 BEKASI","JULI",11811,0,0.00],["ULP BEKASI KOTA","JULI",2892,0,0.00],["ULP MEDAN SATRIA","JULI",1656,0,0.00],["ULP BABELAN","JULI",2293,0,0.00],["ULP BANTARGEBANG","JULI",2554,0,0.00],["ULP MUSTIKAJAYA","JULI",2387,0,0.00],["ULP PRIMA BEKASI","JULI",29,0,0.00],
    ["UP3 BEKASI","AGUSTUS",13690,0,0.00],["ULP BEKASI KOTA","AGUSTUS",3352,0,0.00],["ULP MEDAN SATRIA","AGUSTUS",1921,0,0.00],["ULP BABELAN","AGUSTUS",2658,0,0.00],["ULP BANTARGEBANG","AGUSTUS",2960,0,0.00],["ULP MUSTIKAJAYA","AGUSTUS",2766,0,0.00],["ULP PRIMA BEKASI","AGUSTUS",33,0,0.00],
    ["UP3 BEKASI","SEPTEMBER",15569,0,0.00],["ULP BEKASI KOTA","SEPTEMBER",3812,0,0.00],["ULP MEDAN SATRIA","SEPTEMBER",2184,0,0.00],["ULP BABELAN","SEPTEMBER",3023,0,0.00],["ULP BANTARGEBANG","SEPTEMBER",3366,0,0.00],["ULP MUSTIKAJAYA","SEPTEMBER",3146,0,0.00],["ULP PRIMA BEKASI","SEPTEMBER",38,0,0.00],
    ["UP3 BEKASI","OKTOBER",17627,0,0.00],["ULP BEKASI KOTA","OKTOBER",4316,0,0.00],["ULP MEDAN SATRIA","OKTOBER",2472,0,0.00],["ULP BABELAN","OKTOBER",3423,0,0.00],["ULP BANTARGEBANG","OKTOBER",3811,0,0.00],["ULP MUSTIKAJAYA","OKTOBER",3562,0,0.00],["ULP PRIMA BEKASI","OKTOBER",43,0,0.00],
    ["UP3 BEKASI","NOVEMBER",19417,0,0.00],["ULP BEKASI KOTA","NOVEMBER",4755,0,0.00],["ULP MEDAN SATRIA","NOVEMBER",2722,0,0.00],["ULP BABELAN","NOVEMBER",3771,0,0.00],["ULP BANTARGEBANG","NOVEMBER",4198,0,0.00],["ULP MUSTIKAJAYA","NOVEMBER",3924,0,0.00],["ULP PRIMA BEKASI","NOVEMBER",47,0,0.00],
    ["UP3 BEKASI","DESEMBER",21296,0,0.00],["ULP BEKASI KOTA","DESEMBER",5215,0,0.00],["ULP MEDAN SATRIA","DESEMBER",2986,0,0.00],["ULP BABELAN","DESEMBER",4135,0,0.00],["ULP BANTARGEBANG","DESEMBER",4604,0,0.00],["ULP MUSTIKAJAYA","DESEMBER",4304,0,0.00],["ULP PRIMA BEKASI","DESEMBER",52,0,0.00]
];

// --- LOGIKA PEMROSESAN DATA ---
$unitFilter = isset($_GET['unit']) && $_GET['unit'] !== '' ? $_GET['unit'] : null;
$bulanFilter = isset($_GET['bulan']) && $_GET['bulan'] !== '' ? $_GET['bulan'] : null;

$units = array_unique(array_column($data, 0));
sort($units);
$months = ["JANUARI","FEBRUARI","MARET","APRIL","MEI","JUNI","JULI","AGUSTUS","SEPTEMBER","OKTOBER","NOVEMBER","DESEMBER"];

// Filter data mentah berdasarkan pilihan dropdown
$filteredData = array_filter($data, function($row) use ($unitFilter, $bulanFilter) {
    $unitMatch = !$unitFilter || $row[0] === $unitFilter;
    $bulanMatch = !$bulanFilter || $row[1] === $bulanFilter;
    return $unitMatch && $bulanMatch;
});

// Hitung statistik ringkasan dari data yang sudah difilter
$totalTarget = array_sum(array_column($filteredData, 2));
$totalRealisasi = array_sum(array_column($filteredData, 3));
$avgPencapaian = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0.0;
$uniqueUnitsInFilter = count(array_unique(array_column($filteredData, 0)));

// --- PERSIAPAN DATA GRAFIK YANG CERDAS ---
$aggregatedData = [];
$chartTitle = "Realisasi vs Target";
$groupBy = 'Bulan'; // Default grouping

if ($bulanFilter && !$unitFilter) {
    $groupBy = 'Unit';
    $chartTitle = "Pencapaian Unit di Bulan " . ucfirst(strtolower($bulanFilter));
} elseif ($unitFilter) {
    $groupBy = 'Bulan';
    $chartTitle = "Pencapaian Bulanan: " . $unitFilter;
} else {
    $chartTitle = "Akumulasi Pencapaian per Bulan (Semua Unit)";
}

foreach ($filteredData as $row) {
    $key = ($groupBy === 'Unit') ? $row[0] : $row[1];
    if (!isset($aggregatedData[$key])) {
        $aggregatedData[$key] = ['target' => 0, 'realisasi' => 0];
    }
    $aggregatedData[$key]['target'] += $row[2];
    $aggregatedData[$key]['realisasi'] += $row[3];
}

// Urutkan data berdasarkan urutan bulan jika grouping per bulan
if ($groupBy === 'Bulan') {
    uksort($aggregatedData, fn($a, $b) => array_search($a, $months) <=> array_search($b, $months));
}

$chartLabels = array_keys($aggregatedData);
$chartTarget = array_column($aggregatedData, 'target');
$chartRealisasi = array_column($aggregatedData, 'realisasi');
$chartPencapaian = array_map(fn($t, $r) => $t > 0 ? round(($r / $t) * 100, 2) : 0, $chartTarget, $chartRealisasi);

// Data untuk Pie Chart (Distribusi realisasi per unit)
$pieStats = [];
foreach ($filteredData as $r) {
    $unit = rtrim(str_replace("ULP ", "", $r[0]));
    if ($r[0] === 'UP3 BEKASI') continue; // Jangan masukkan UP3 di pie chart ULP
    if (!isset($pieStats[$unit])) $pieStats[$unit] = 0;
    $pieStats[$unit] += $r[3];
}
arsort($pieStats); // Urutkan dari terbesar
$pieLabels = array_keys($pieStats);
$pieRealisasi = array_values($pieStats);
$showPieChart = (count($pieLabels) > 1);

?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Ganti Meter 2025 | V3</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.1/jspdf.plugin.autotable.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb; --primary-dark: #1d4ed8;
            --accent: #22c55e; --accent-dark: #16a34a;
            --danger: #ef4444; --warning: #f97316;
            --bg-light: #f8fafc; --bg-main: #eef2f6;
            --text-main: #1e293b; --text-secondary: #64748b;
            --border-color: #e2e8f0; --white: #fff;
            --shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif; background: var(--bg-main);
            color: var(--text-main); padding: 24px;
        }
        .container {
            max-width: 1400px; margin: 0 auto;
            display: flex; flex-direction: column; gap: 24px;
        }
        .header {
            display: flex; justify-content: space-between; align-items: center;
            flex-wrap: wrap; gap: 16px;
        }
        .header h1 { font-size: 28px; font-weight: 700; color: var(--text-main); }
        .controls { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; }
        .select, .btn {
            padding: 10px 16px; border-radius: 8px; border: 1px solid var(--border-color);
            background: var(--white); font-size: 14px; font-weight: 500;
            color: var(--text-main); transition: all 0.2s ease; cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        .select:hover, .btn:hover { border-color: var(--primary); }
        .select:focus { outline: 2px solid var(--primary); border-color: transparent; }
        .btn { display: flex; align-items: center; gap: 8px; }
        .btn-primary { background: var(--primary); color: var(--white); border-color: var(--primary); }
        .btn-primary:hover { background: var(--primary-dark); border-color: var(--primary-dark); }
        .btn-ghost { background: transparent; box-shadow: none; text-decoration: none; }
        .export-group { position: relative; }
        .export-menu {
            position: absolute; top: calc(100% + 8px); right: 0;
            background: var(--white); border: 1px solid var(--border-color);
            border-radius: 8px; box-shadow: var(--shadow-lg);
            z-index: 10; width: 180px; padding: 8px;
            opacity: 0; transform: translateY(-10px); pointer-events: none;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }
        .export-menu.active { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .export-menu button {
            display: block; width: 100%; text-align: left;
            padding: 8px 12px; background: transparent; border: none;
            border-radius: 6px; cursor: pointer; font-size: 14px;
        }
        .export-menu button:hover { background: var(--bg-light); color: var(--primary); }
        .summary-grid {
            display: grid; gap: 24px;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }
        .card {
            background: var(--white); padding: 24px;
            border-radius: 12px; border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md); display: flex;
            align-items: center; gap: 20px; transition: all 0.3s ease;
        }
        .card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
        .card-icon {
            font-size: 24px; width: 52px; height: 52px;
            display: grid; place-items: center; border-radius: 50%;
        }
        .icon-target { background: #e0f2fe; color: #0ea5e9; }
        .icon-realisasi { background: #dcfce7; color: #22c55e; }
        .icon-pencapaian { background: #ffedd5; color: #f97316; }
        .icon-unit { background: #e0e7ff; color: #4f46e5; }
        .card-content h4 {
            font-size: 14px; font-weight: 500; color: var(--text-secondary);
            margin-bottom: 4px; text-transform: uppercase;
        }
        .card-content .value { font-size: 26px; font-weight: 700; color: var(--text-main); }
        .positive { color: var(--accent-dark); }
        .negative { color: var(--danger); }
        .warning { color: var(--warning); } /* Added for progress bar */
        .chart-grid {
            display: grid; gap: 24px;
            grid-template-columns: 2fr 1fr;
        }
        .chart-card {
            background: var(--white); padding: 24px;
            border-radius: 12px; border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md); display: flex; flex-direction: column;
        }
        .chart-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 20px;
        }
        .chart-header strong { font-size: 18px; font-weight: 600; }
        .chart-container { position: relative; flex-grow: 1; min-height: 350px; }
        .table-wrap {
            background: var(--white); border: 1px solid var(--border-color);
            border-radius: 12px; box-shadow: var(--shadow-md);
            overflow-x: auto;
        }
        .data-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .data-table th, .data-table td {
            padding: 14px 16px; text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        .data-table thead th {
            background: var(--bg-light); font-size: 12px; font-weight: 600;
            color: var(--text-secondary); text-transform: uppercase;
            letter-spacing: 0.5px; position: sticky; top: 0;
        }
        .data-table tbody tr:hover { background: #f8fafc; }
        .data-table tbody td { font-size: 14px; color: var(--text-main); font-weight: 500; }
        .data-table .text-center { text-align: center; }
        .progress-bar-container {
            display: flex; align-items: center; gap: 8px;
            min-width: 150px;
        }
        .progress-bar-bg {
            flex-grow: 1; height: 8px; background: var(--border-color);
            border-radius: 4px; overflow: hidden;
        }
        .progress-bar-fill {
            height: 100%;
            border-radius: 4px; transition: width 0.5s ease;
        }
        .progress-bar-fill.positive { background-color: var(--accent); }
        .progress-bar-fill.negative { background-color: var(--danger); }
        .progress-bar-fill.warning { background-color: var(--warning); }
        @media (max-width: 1024px) {
            .chart-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            body { padding: 16px; }
            .header h1 { font-size: 24px; }
            .controls { flex-direction: column; align-items: stretch; }
            .filter-form { flex-direction: column; align-items: stretch; width: 100%; }
            .export-group { align-self: flex-end; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Dashboard Ganti Meter 2025</h1>
        <div class="controls">
            <form id="filterForm" class="filter-form" method="GET">
                <select name="unit" class="select">
                    <option value="">Semua Unit</option>
                    <?php foreach($units as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>" <?= ($unitFilter === $u) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="bulan" class="select">
                    <option value="">Semua Bulan</option>
                    <?php foreach($months as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>" <?= ($bulanFilter === $m) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst(strtolower($m))) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Terapkan Filter</button>
            </form>
            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-ghost"><i class="fa-solid fa-rotate-left"></i> Reset</a>
            <div class="export-group">
                <button type="button" class="btn" id="exportBtnToggle">
                    <i class="fa-solid fa-download"></i> Ekspor
                </button>
                <div class="export-menu" id="exportMenu">
                    <button data-export="xlsx"><i class="fa-solid fa-file-excel"></i> Excel (.xlsx)</button>
                    <button data-export="pdf"><i class="fa-solid fa-file-pdf"></i> PDF (.pdf)</button>
                    <button data-export="csv"><i class="fa-solid fa-file-csv"></i> CSV (.csv)</button>
                    <button data-export="word"><i class="fa-solid fa-file-word"></i> Word (.doc)</button>
                </div>
            </div>
        </div>
    </div>

        <div class="header-actions">
    <!-- Tombol Kembali -->
    <a href="lihat_kpi.php" class="btn">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>

    <div class="summary-grid">
        <div class="card">
            <div class="card-icon icon-target"><i class="fa-solid fa-crosshairs"></i></div>
            <div class="card-content">
                <h4>Total Target</h4>
                <div class="value"><?= number_format($totalTarget) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-icon icon-realisasi"><i class="fa-solid fa-check-circle"></i></div>
            <div class="card-content">
                <h4>Total Realisasi</h4>
                <div class="value"><?= number_format($totalRealisasi) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-icon icon-pencapaian"><i class="fa-solid fa-percent"></i></div>
            <div class="card-content">
                <h4>Pencapaian</h4>
                <div class="value <?= $avgPencapaian >= 100 ? 'positive' : 'negative' ?>">
                    <?= number_format($avgPencapaian, 2) ?>%
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-icon icon-unit"><i class="fa-solid fa-building-user"></i></div>
            <div class="card-content">
                <h4>Unit Terdata</h4>
                <div class="value"><?= number_format($uniqueUnitsInFilter) ?></div>
            </div>
        </div>
    </div>

    <div class="chart-grid">
        <div class="chart-card">
            <div class="chart-header">
                <strong id="mainChartTitle"></strong>
            </div>
            <div class="chart-container">
                <canvas id="mixedChart"></canvas>
            </div>
        </div>
        <div class="chart-card" id="pieChartCard" style="<?= !$showPieChart ? 'display: none;' : '' ?>">
            <div class="chart-header">
                <strong>Distribusi Realisasi ULP</strong>
            </div>
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table" id="dataTable">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Bulan</th>
                    <th class="text-center">Target</th>
                    <th class="text-center">Realisasi</th>
                    <th>% Pencapaian</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($filteredData)): ?>
                    <?php foreach ($filteredData as $r):
                        $pencapaian = $r[4];
                        $pencapaianClamped = max(0, min(100, $pencapaian)); // Batasi antara 0-100 untuk bar
                        $colorClass = $pencapaian >= 100 ? 'positive' : ($pencapaian > 50 ? 'warning' : 'negative');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($r[0]) ?></td>
                        <td><?= htmlspecialchars(ucfirst(strtolower($r[1]))) ?></td>
                        <td class="text-center"><?= number_format($r[2]) ?></td>
                        <td class="text-center"><?= number_format($r[3]) ?></td>
                        <td>
                            <div class="progress-bar-container">
                                <span class="<?= $colorClass ?>" style="min-width: 55px; text-align:right; font-weight: 600;"><?= number_format($pencapaian, 2) ?>%</span>
                                <div class="progress-bar-bg">
                                    <div class="progress-bar-fill <?= $colorClass ?>" style="width: <?= $pencapaianClamped ?>%;"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-secondary);">Tidak ada data yang sesuai dengan filter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // --- Pass PHP Data to JS ---
    const chartData = {
        title: <?= json_encode($chartTitle) ?>,
        labels: <?= json_encode($chartLabels) ?>,
        target: <?= json_encode($chartTarget) ?>,
        realisasi: <?= json_encode($chartRealisasi) ?>,
        pencapaian: <?= json_encode($chartPencapaian) ?>
    };
    const pieData = {
        labels: <?= json_encode($pieLabels) ?>,
        realisasi: <?= json_encode($pieRealisasi) ?>
    };
    const tableData = <?= json_encode(array_map(fn($r) => ['Unit' => $r[0],'Bulan' => $r[1],'Target' => $r[2],'Realisasi' => $r[3],'Pencapaian' => $r[4]], array_values($filteredData)), JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        // --- Dynamic Chart Title ---
        document.getElementById('mainChartTitle').innerText = chartData.title;

        // --- Mixed Chart (bar + line) ---
        const ctx = document.getElementById('mixedChart').getContext('2d');
        const mixedChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [
                    { type: 'bar', label: 'Target', data: chartData.target, backgroundColor: '#93c5fd', order: 2 },
                    { type: 'bar', label: 'Realisasi', data: chartData.realisasi, backgroundColor: '#86efac', order: 2 },
                    { type: 'line', label: '% Pencapaian', data: chartData.pencapaian, borderColor: '#f97316',  yAxisID: 'yPercent', tension: 0.3, fill: true, order: 1 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { position: 'top' } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' }, title: { display: true, text: 'Jumlah Meter' } },
                    yPercent: { type: 'linear', position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Persentase (%)' }, ticks: { callback: v => v + '%' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // --- Pie Chart ---
        if (document.getElementById('pieChartCard')) {
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: pieData.labels,
                    datasets: [{
                        data: pieData.realisasi,
                        backgroundColor: ['#3b82f6','#16a34a','#f97316','#ef4444','#8b5cf6','#0ea5e9','#facc15'],
                        borderWidth: 2, borderColor: '#f8fafc'
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { position: 'right', labels: { boxWidth: 12, padding: 15 } } }
                }
            });
        }

        // --- Export Menu Logic ---
        const exportBtn = document.getElementById('exportBtnToggle');
        const exportMenu = document.getElementById('exportMenu');
        exportBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenu.classList.toggle('active');
        });
        document.addEventListener('click', () => exportMenu.classList.remove('active'));
        exportMenu.addEventListener('click', (e) => {
            if (e.target.dataset.export) {
                const format = e.target.dataset.export;
                if (format === 'xlsx') exportXLSX();
                else if (format === 'pdf') exportPDF();
                else if (format === 'word') exportWord();
                else if (format === 'csv') exportCSV();
            }
        });
        
        // --- EXPORT FUNCTIONS ---
        const fileName = `data_ganti_meter_${new Date().toISOString().slice(0,10)}`;
        const headers = ['Unit','Bulan','Target','Realisasi','% Pencapaian'];
        const formatRow = (r) => ({Unit: r.Unit, Bulan: r.Bulan, Target: r.Target, Realisasi: r.Realisasi, Pencapaian: r.Pencapaian.toFixed(2) + '%'});

        function exportXLSX() {
            const ws = XLSX.utils.json_to_sheet(tableData.map(formatRow));
            ws['!cols'] = [{ wpx:200 }, { wpx:100 }, { wpx:100 }, { wpx:100 }, { wpx:120 }];
            const wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, 'Data');
            XLSX.writeFile(wb, `${fileName}.xlsx`);
        }
        function exportCSV() {
            const ws = XLSX.utils.json_to_sheet(tableData.map(formatRow));
            const csv = XLSX.utils.sheet_to_csv(ws);
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.setAttribute("download", `${fileName}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        function exportPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text("Laporan Ganti Meter 2025", 14, 20);
            doc.autoTable({
                head: [headers],
                body: tableData.map(r => [r.Unit, r.Bulan, r.Target, r.Realisasi, r.Pencapaian.toFixed(2) + '%']),
                startY: 25,
                headStyles: { fillColor: [37, 99, 235] },
            });
            doc.save(`${fileName}.pdf`);
        }
        function exportWord() {
            let html = `<html><head><meta charset='utf-8'><title>Export</title></head><body>
                <h2>Laporan Ganti Meter 2025</h2>
                <table border="1" style="border-collapse:collapse; width:100%;">
                <thead><tr>${headers.map(h => `<th>${h}</th>`).join('')}</tr></thead>
                <tbody>${tableData.map(r => `<tr>${Object.values(formatRow(r)).map(val => `<td>${String(val).replace(/&/g, '&amp;').replace(/</g, '&lt;')}</td>`).join('')}</tr>`).join('')}</tbody>
                </table></body></html>`;
            const blob = new Blob(['\ufeff', html], { type: 'application/msword' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = `${fileName}.doc`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    });
</script>

</body>
</html>