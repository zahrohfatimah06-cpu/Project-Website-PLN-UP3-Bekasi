<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Koneksi database
$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Urutan unit dan bulan yang diinginkan
$unit_order = [
    'UP3 BEKASI',
    'ULP BEKASI KOTA',
    'ULP MEDAN SATRIA',
    'ULP BABELAN',
    'ULP BANTARGEBANG',
    'ULP MUSTIKAJAYA',
    'ULP PRIMA BEKASI'
];

$bulan_order = [
    'JANUARI', 'FEBRUARI', 'MARET', 'APRIL', 'MEI', 'JUNI',
    'JULI', 'AGUSTUS', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DESEMBER'
];

$unit_order_sql = "'" . implode("','", $unit_order) . "'";

// Inisialisasi filter
$selected_unit = $_GET['unit'] ?? '';
$selected_bulan = $_GET['bulan'] ?? '';

// Query untuk data tabel utama (dengan filter)
$sql = "
    SELECT * FROM data_gangguan_tm_kurang_5
    WHERE 1=1
";
$params = [];
$types = '';

if (!empty($selected_unit)) {
    $sql .= " AND unit = ?";
    $params[] = $selected_unit;
    $types .= 's';
}
if (!empty($selected_bulan)) {
    $sql .= " AND bulan = ?";
    $params[] = $selected_bulan;
    $types .= 's';
}

$sql .= " ORDER BY
    FIELD(bulan, 'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'),
    FIELD(unit, $unit_order_sql)";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Ambil semua data untuk grafik dan statistik (tanpa filter)
$sql_all = "SELECT * FROM data_gangguan_tm_kurang_5";
$all_data = $conn->query($sql_all)->fetch_all(MYSQLI_ASSOC);

// Ambil data untuk grafik bar (dengan filter)
$bar_data_labels = [];
$bar_data_target = [];
$bar_data_realisasi = [];
$result->data_seek(0);
while ($row = $result->fetch_assoc()) {
    $bar_data_labels[] = $row['unit'] . ' - ' . $row['bulan'];
    $bar_data_target[] = $row['target_2025'];
    $bar_data_realisasi[] = $row['realisasi_2025'];
}

// Hitung statistik untuk card
$stats = [
    'total_data' => count($all_data),
    'total_target' => 0,
    'total_realisasi' => 0
];
$pencapaian_per_unit = [];
$pencapaian_per_bulan = [];

foreach ($all_data as $row) {
    $stats['total_target'] += $row['target_2025'];
    $stats['total_realisasi'] += $row['realisasi_2025'];
    
    $pencapaian = floatval(str_replace('%', '', $row['persentase_pencapaian']));
    
    if (!isset($pencapaian_per_unit[$row['unit']])) {
        $pencapaian_per_unit[$row['unit']] = ['total' => 0, 'count' => 0];
    }
    $pencapaian_per_unit[$row['unit']]['total'] += $pencapaian;
    $pencapaian_per_unit[$row['unit']]['count']++;

    if (!isset($pencapaian_per_bulan[$row['bulan']])) {
        $pencapaian_per_bulan[$row['bulan']] = ['total' => 0, 'count' => 0];
    }
    $pencapaian_per_bulan[$row['bulan']]['total'] += $pencapaian;
    $pencapaian_per_bulan[$row['bulan']]['count']++;
}

// Analisis unit terbaik dan terendah berdasarkan rata-rata pencapaian
$unit_avg_pencapaian = [];
foreach ($pencapaian_per_unit as $unit => $data) {
    $unit_avg_pencapaian[$unit] = round($data['total'] / $data['count'], 2);
}
asort($unit_avg_pencapaian);
$worst_unit = key($unit_avg_pencapaian);
$worst_pencapaian = current($unit_avg_pencapaian);

arsort($unit_avg_pencapaian);
$best_unit = key($unit_avg_pencapaian);
$best_pencapaian = current($unit_avg_pencapaian);

// Persiapan data untuk grafik pie (3 unit terbaik)
$top_3_units = array_slice($unit_avg_pencapaian, 0, 3, true);
$pie_labels = array_keys($top_3_units);
$pie_data = array_values($top_3_units);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Gangguan TM < 5 Menit - PLN UP3 BEKASI</title>

<!-- Bootstrap & Icon -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- Chart.js & Export Tools -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<style>
    body {
        background: #f4f6f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    /* Header PLN */
    .header-container {
        background: linear-gradient(90deg, #005BAC, #008DDA);
        padding: 25px;
        border-radius: 12px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        text-align: center;
    }
    .header-container h1 {
        font-weight: 700;
    }
    /* Card statistik */
    .stats-card {
        border-radius: 14px;
        padding: 18px;
        color: white;
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    .stats-card:hover {
        transform: translateY(-4px);
    }
    /* Filter */
    .filter-card {
        background: white;
        padding: 20px;
        border-radius: 14px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    /* Table */
    table thead {
        background-color: #005BAC;
        color: white;
    }
    .unit-badge {
        background-color: #008DDA;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.85rem;
    }
    .month-badge {
        background-color: #6c757d;
        color: white;
        padding: 4px 10px;
        border-radius: 12px;
    }
    .pencapaian-terpenuhi {
        background-color: #d1e7dd !important;
        color: #0f5132;
        font-weight: bold;
    }
    .pencapaian-belum {
        background-color: #f8d7da !important;
        color: #842029;
        font-weight: bold;
    }
</style>
</head>
<body>

<div class="container-fluid py-3" id="dashboard-content">

    <!-- Tombol & Action -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <a href="dashboard.php" class="btn btn-primary">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <div class="d-flex gap-2">
            <button onclick="exportToXLSX()" class="btn btn-success"><i class="bi bi-file-earmark-excel"></i> Excel</button>
            <button onclick="printPDF()" class="btn btn-danger"><i class="bi bi-file-earmark-pdf"></i> PDF</button>
            <button onclick="exportToWord()" class="btn btn-info text-white"><i class="bi bi-file-earmark-word"></i> Word</button>
        </div>
    </div>

    <!-- Header PLN -->
    <div class="header-container">
        <h1><i class="bi bi-graph-up-arrow"></i> Dashboard Gangguan TM &lt; 5 Menit</h1>
        <p class="lead mb-0">Analisis Data Gangguan Tahun 2025 - PLN UP3 BEKASI</p>
    </div>

    <!-- Filter -->
    <div class="filter-card mb-4">
        <h5><i class="bi bi-funnel"></i> Filter Data</h5>
        <form method="GET" action="" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label">Pilih Unit</label>
                <select name="unit" class="form-select">
                    <option value="">Semua Unit</option>
                    <?php foreach ($unit_order as $unit) {
                        echo "<option value='$unit' " . ($selected_unit == $unit ? 'selected' : '') . ">$unit</option>";
                    } ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label">Pilih Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="">Semua Bulan</option>
                    <?php foreach ($bulan_order as $month) {
                        echo "<option value='$month' " . ($selected_bulan == $month ? 'selected' : '') . ">$month</option>";
                    } ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Terapkan</button>
            </div>
        </form>
    </div>

    <!-- Statistik -->
    <div class="row g-4 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background-color: #198754;">
                <h5><i class="bi bi-award-fill"></i> Unit Terbaik</h5>
                <h4><?= htmlspecialchars($best_unit) ?></h4>
                <p>Pencapaian: <?= htmlspecialchars($best_pencapaian) ?>%</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background-color: #dc3545;">
                <h5><i class="bi bi-exclamation-triangle-fill"></i> Unit Terendah</h5>
                <h4><?= htmlspecialchars($worst_unit) ?></h4>
                <p>Pencapaian: <?= htmlspecialchars($worst_pencapaian) ?>%</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background-color: #0d6efd;">
                <h5><i class="bi bi-bar-chart-line-fill"></i> Total Realisasi</h5>
                <h2><?= number_format($stats['total_realisasi'], 0, ',', '.') ?></h2>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="stats-card" style="background-color: #ffc107; color: black;">
                <h5><i class="bi bi-check-circle-fill"></i> Total Target</h5>
                <h2><?= number_format($stats['total_target'], 0, ',', '.') ?></h2>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="text-center"><i class="bi bi-bar-chart"></i> Target vs Realisasi</h5>
                <div style="height: 350px;"><canvas id="targetRealisasiChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card p-4">
                <h5 class="text-center"><i class="bi bi-pie-chart"></i> Top 3 Unit Terbaik</h5>
                <div style="height: 350px;"><canvas id="topUnitChart"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5><i class="bi bi-table"></i> Data Gangguan TM &lt; 5 Menit</h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="dataTable">
                <thead>
                    <tr>
                        <th class="text-center">No</th>
                        <th>Unit</th>
                        <th class="text-center">Bulan</th>
                        <th class="text-center">Target 2025</th>
                        <th class="text-center">Realisasi 2025</th>
                        <th class="text-center">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        $no = 1;
                        $current_month = "";
                        $result->data_seek(0);
                        while($row = $result->fetch_assoc()) {
                            if ($current_month != $row['bulan']) {
                                echo "<tr class='table-secondary'><td colspan='6' class='text-center fw-bold'><span class='month-badge'>{$row['bulan']}</span></td></tr>";
                                $current_month = $row['bulan'];
                            }
                            $pencapaian = floatval(str_replace('%', '', $row['persentase_pencapaian']));
                            $row_class = $pencapaian >= 100 ? 'pencapaian-terpenuhi' : 'pencapaian-belum';
                            echo "<tr>
                                <td class='text-center'>{$no}</td>
                                <td><span class='unit-badge'>{$row['unit']}</span></td>
                                <td class='text-center'>{$row['bulan']}</td>
                                <td class='text-center'>{$row['target_2025']}</td>
                                <td class='text-center'>{$row['realisasi_2025']}</td>
                                <td class='text-center {$row_class}'>{$row['persentase_pencapaian']}%</td>
                            </tr>";
                            $no++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center text-muted py-4'>Tidak ada data</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script>
    // Data untuk grafik
    const barLabels = <?= json_encode($bar_data_labels) ?>;
    const barTargetData = <?= json_encode($bar_data_target) ?>;
    const barRealisasiData = <?= json_encode($bar_data_realisasi) ?>;
    const pieLabels = <?= json_encode($pie_labels) ?>;
    const pieData = <?= json_encode($pie_data) ?>;

    // Grafik Target vs Realisasi (Bar Chart)
    const ctxBar = document.getElementById('targetRealisasiChart').getContext('2d');
    new Chart(ctxBar, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Target',
                data: barTargetData,
                backgroundColor: 'rgba(13, 110, 253, 0.8)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }, {
                label: 'Realisasi',
                data: barRealisasiData,
                backgroundColor: barRealisasiData.map((realisasi, index) => {
                    const target = barTargetData[index];
                    return realisasi >= target ? 'rgba(25, 135, 84, 0.8)' : 'rgba(220, 53, 69, 0.8)';
                }),
                borderColor: barRealisasiData.map((realisasi, index) => {
                    const target = barTargetData[index];
                    return realisasi >= target ? 'rgba(25, 135, 84, 1)' : 'rgba(220, 53, 69, 1)';
                }),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Jumlah'
                    }
                }
            },
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Grafik Top 3 Unit (Pie Chart)
    const ctxPie = document.getElementById('topUnitChart').getContext('2d');
    new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: pieLabels,
            datasets: [{
                label: 'Rata-rata Pencapaian (%)',
                data: pieData,
                backgroundColor: ['#198754', '#ffc107', '#0d6efd'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Fungsi untuk Export ke XLSX
    function exportToXLSX() {
        const table = document.getElementById('dataTable');
        const ws = XLSX.utils.table_to_sheet(table);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "Data Gangguan TM < 5 Menit");
        XLSX.writeFile(wb, "Data_Gangguan_TM_Kurang_5.xlsx");
    }

    // Fungsi untuk Cetak PDF
    function printPDF() {
        const { jsPDF } = window.jspdf;
        const element = document.getElementById('dashboard-content');
        html2canvas(element, { scale: 2, useCORS: true }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgWidth = 210;
            const pageHeight = 295;
            const imgHeight = canvas.height * imgWidth / canvas.width;
            let heightLeft = imgHeight;
            let position = 0;
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            pdf.save("Analisis_Gangguan_TM_Kurang_5.pdf");
        });
    }

    // Fungsi untuk Export ke Word
    function exportToWord() {
        const header = `
            <h1>Analisis Gangguan TM < 5 Menit</h1>
            <p>Tahun 2025 - PLN UP3 BEKASI</p>
            <br>
        `;
        const content = document.getElementById('dashboard-content').outerHTML;
        const htmlContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <title>Analisis Gangguan TM < 5 Menit</title>
                <style>
                    body { font-family: sans-serif; }
                    table { width: 100%; border-collapse: collapse; }
                    th, td { border: 1px solid black; padding: 8px; text-align: left; }
                    .header-container { background-color: #0d6efd; color: white; text-align: center; padding: 20px; }
                    .card { border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
                </style>
            </head>
            <body>
                ${header}
                ${content}
            </body>
            </html>
        `;
        const blob = new Blob([htmlContent], { type: 'application/msword' });
        saveAs(blob, 'Analisis_Gangguan_TM_Kurang_5.doc');
    }
</script>
</body>
</html>