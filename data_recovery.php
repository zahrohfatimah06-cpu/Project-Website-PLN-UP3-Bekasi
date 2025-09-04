<?php
// --- Koneksi Database ---
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// --- Ambil Filter ---
$filter_unit  = $_GET['unit']  ?? '';
$filter_bulan = $_GET['bulan'] ?? '';
$filter_tahun = $_GET['tahun'] ?? '2025';

// Tentukan nama kolom target dan realisasi
$kolom_target = 'target_' . $filter_tahun;
$kolom_realisasi = 'realisasi_' . $filter_tahun;

// --- Ambil List Dropdown ---
$units_result = $conn->query("SELECT DISTINCT unit FROM data_mv ORDER BY unit");
$months_in_order = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
$months_result = $conn->query("SELECT DISTINCT bulan FROM data_mv ORDER BY FIELD(bulan,'".implode("','",$months_in_order)."')");

// --- Query Data ---
$sql = "SELECT * FROM data_mv WHERE 1=1";
if ($filter_unit) $sql .= " AND unit='$filter_unit'";
if ($filter_bulan) {
    $bulan_index = array_search($filter_bulan, $months_in_order);
    if ($bulan_index !== false) {
        $selected_months = array_slice($months_in_order, 0, $bulan_index + 1);
        $sql .= " AND bulan IN ('".implode("','",$selected_months)."')";
    }
}
$sql .= " ORDER BY FIELD(bulan,'".implode("','",$months_in_order)."')";

$result = $conn->query($sql);

// --- Siapkan data chart dan ringkasan ---
$labels_chart = $data_target_chart = $data_realisasi_chart = [];
$sum_target = $sum_realisasi = 0;
$total_rows = 0;
$data_rows = [];

if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        // Gunakan safe value jika kolom tidak ada
        $target = isset($r[$kolom_target]) ? (float)$r[$kolom_target] : 0;
        $realisasi = isset($r[$kolom_realisasi]) ? (float)$r[$kolom_realisasi] : 0;

        $labels_chart[] = $r['bulan'] . ' - ' . $r['unit'];
        $data_target_chart[] = $target;
        $data_realisasi_chart[] = $realisasi;

        $sum_target += $target;
        $sum_realisasi += $realisasi;

        // Simpan ke data_rows untuk tabel, gunakan kolom aman
        $r['target_safe'] = $target;
        $r['realisasi_safe'] = $realisasi;
        $data_rows[] = $r;
        $total_rows++;
    }
}

$avg_target = $total_rows ? $sum_target / $total_rows : 0;
$avg_realisasi = $total_rows ? $sum_realisasi / $total_rows : 0;
$avg_persen = $avg_target ? ($avg_realisasi / $avg_target) * 100 : 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Recovery PLN</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
    --primary: #0d6efd;
    --secondary: #6c757d;
    --success: #198754;
    --info: #0dcaf0;
    --warning: #ffc107;
    --danger: #dc3545;
    --light: #f8f9fa;
    --dark: #212529;
    --pln-blue: #0A4C95;
    --pln-yellow: #FFA500;
}

body {
    background-color: #f5f9ff;
    color: #333;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.navbar {
    background: linear-gradient(90deg, var(--pln-blue), #073b76);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navbar-brand {
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: transform 0.3s;
}

.card:hover {
    transform: translateY(-5px);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    font-weight: 600;
}

.summary-card {
    text-align: center;
    padding: 1.5rem;
    border-radius: 12px;
    background: white;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}

.summary-card h6 {
    color: #6c757d;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.summary-card h5 {
    color: var(--pln-blue);
    font-weight: 700;
    margin-bottom: 0;
}

.table th {
    background-color: var(--pln-blue);
    color: white;
    border: none;
}

.table-hover tbody tr:hover {
    background-color: rgba(10, 76, 149, 0.05);
}

.btn-primary {
    background: linear-gradient(90deg, var(--pln-blue), #0d6efd);
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
}

.btn-primary:hover {
    background: linear-gradient(90deg, #073b76, #0a58ca);
}

.form-select:focus, .form-control:focus {
    border-color: var(--pln-blue);
    box-shadow: 0 0 0 0.25rem rgba(10, 76, 149, 0.25);
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.filter-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    margin-bottom: 1.5rem;
}

.page-title {
    color: var(--pln-blue);
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: #6c757d;
    margin-bottom: 1.5rem;
}

.badge-success {
    background-color: var(--success);
}

.badge-warning {
    background-color: var(--warning);
    color: #000;
}

.badge-danger {
    background-color: var(--danger);
}

.progress {
    height: 8px;
    border-radius: 4px;
}

.progress-bar {
    border-radius: 4px;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-bolt"></i> PLN Dashboard
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#"><i class="fas fa-home me-1"></i> Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fas fa-chart-line me-1"></i> Laporan</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i> Pengaturan</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-3">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="page-title"><i class="fas fa-chart-bar me-2"></i>Data Recovery PLN</h1>
            <p class="page-subtitle">Pantau dan analisis data recovery tahun <?= $filter_tahun ?></p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <h5 class="mb-4"><i class="fas fa-filter me-2"></i>Filter Data</h5>
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Unit</label>
                <select name="unit" class="form-select">
                    <option value="">-- Semua Unit --</option>
                    <?php if ($units_result): mysqli_data_seek($units_result,0); while($u=$units_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($u['unit']) ?>" <?= $u['unit']==$filter_unit?'selected':'' ?>>
                            <?= htmlspecialchars($u['unit']) ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Bulan</label>
                <select name="bulan" class="form-select">
                    <option value="">-- Semua Bulan --</option>
                    <?php if ($months_result): mysqli_data_seek($months_result,0); while($m=$months_result->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($m['bulan']) ?>" <?= $m['bulan']==$filter_bulan?'selected':'' ?>>
                            <?= htmlspecialchars($m['bulan']) ?>
                        </option>
                    <?php endwhile; endif; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Tahun</label>
                <select name="tahun" class="form-select">
                    <?php for($t=2024; $t<=2030; $t++): ?>
                        <option value="<?= $t ?>" <?= $t==$filter_tahun?'selected':'' ?>><?= $t ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="d-grid gap-2 d-md-flex">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="fas fa-check me-1"></i> Terapkan
                    </button>
                    <a href="?" class="btn btn-secondary flex-fill">
                        <i class="fas fa-sync me-1"></i> Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Ringkasan -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <h6><i class="fas fa-bullseye me-1"></i> Rata-rata Target</h6>
                <h5><?= number_format($avg_target,2) ?></h5>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <h6><i class="fas fa-chart-line me-1"></i> Rata-rata Realisasi</h6>
                <h5><?= number_format($avg_realisasi,2) ?></h5>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card">
                <h6><i class="fas fa-percent me-1"></i> Rata-rata Pencapaian</h6>
                <h5><?= number_format($avg_persen,2) ?>%</h5>
            </div>
        </div>
    </div>

    <!-- Grafik -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-chart-bar me-2"></i>Grafik Perbandingan Target & Realisasi
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="recoveryChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-2"></i>Data Recovery (<?= count($data_rows) ?> baris)</span>
            <button class="btn btn-sm btn-light" onclick="exportTable()">
                <i class="fas fa-download me-1"></i> Ekspor
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th>Unit</th>
                            <th>Tahun</th>
                            <th class="text-end">Target <?= $filter_tahun ?></th>
                            <th class="text-end">Realisasi <?= $filter_tahun ?></th>
                            <th class="text-center">Persentase</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($data_rows)): ?>
                        <?php foreach ($data_rows as $row): 
                            $persentase = $row['target_safe'] ? ($row['realisasi_safe'] / $row['target_safe'] * 100) : 0;
                            $status_class = 'badge-success';
                            if ($persentase < 80) $status_class = 'badge-danger';
                            elseif ($persentase < 100) $status_class = 'badge-warning';
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($row['bulan']) ?></td>
                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                <td><?= htmlspecialchars($row['tahun']) ?></td>
                                <td class="text-end"><?= number_format($row['target_safe'],2) ?></td>
                                <td class="text-end"><?= number_format($row['realisasi_safe'],2) ?></td>
                                <td class="text-center"><?= number_format($persentase,2) ?>%</td>
                                <td class="text-center"><span class="badge <?= $status_class ?>"><?= number_format($persentase,2) ?>%</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x mb-3 text-muted"></i>
                                <p class="text-muted">Tidak ada data yang ditemukan</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5 py-3 text-center text-muted">
        <p>PLN Dashboard &copy; 2023 - Sistem Monitoring Data Recovery</p>
    </footer>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('recoveryChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labels_chart) ?>,
            datasets: [
                {
                    label: 'Target <?= $filter_tahun ?>', 
                    data: <?= json_encode($data_target_chart) ?>, 
                    backgroundColor: 'rgba(58, 87, 232, 0.7)',
                    borderColor: 'rgba(58, 87, 232, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                },
                {
                    label: 'Realisasi <?= $filter_tahun ?>', 
                    data: <?= json_encode($data_realisasi_chart) ?>, 
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Perbandingan Target & Realisasi per Bulan/Unit',
                    font: { size: 16 }
                },
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});

function exportTable() {
    // Fungsi untuk ekspor tabel (bisa dikembangkan lebih lanjut)
    alert('Fitur ekspor akan dikembangkan lebih lanjut');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>