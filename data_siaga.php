<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// Cek dan buat tabel jika belum ada
$conn->query("CREATE TABLE IF NOT EXISTS data_siaga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit VARCHAR(50),
    bulan VARCHAR(20),
    target_2025 DECIMAL(10,2),
    realisasi_2025 DECIMAL(10,2),
    persen_pencapaian DECIMAL(5,2)
)");

// Hanya insert jika tabel kosong
$count = $conn->query("SELECT COUNT(*) as c FROM data_siaga")->fetch_assoc()['c'];
if($count == 0){
    $data = [
        ['UP3 BEKASI','JANUARI',100,100,100],
        ['ULP BEKASI KOTA','JANUARI',100,100,100],
        // ... (data lainnya sama seperti sebelumnya)
        ['ULP PRIMA BEKASI','DESEMBER',100,0,0]
    ];

    $stmt = $conn->prepare("INSERT INTO data_siaga (unit, bulan, target_2025, realisasi_2025, persen_pencapaian) VALUES (?,?,?,?,?)");
    foreach($data as $d){
        $stmt->bind_param("ssddd", $d[0], $d[1], $d[2], $d[3], $d[4]);
        $stmt->execute();
    }
}

// Filter
$unit_filter  = isset($_GET['unit']) ? $conn->real_escape_string($_GET['unit']) : '';
$bulan_filter = isset($_GET['bulan']) ? $conn->real_escape_string($_GET['bulan']) : '';

$sql = "SELECT * FROM data_siaga WHERE 1=1";
if($unit_filter != '') $sql .= " AND unit='$unit_filter'";
if($bulan_filter != '') $sql .= " AND bulan='$bulan_filter'";
$sql .= " ORDER BY FIELD(bulan,'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'), unit ASC";
$result = $conn->query($sql);

// Data untuk grafik
$labels = $targets = $realisasi = $pencapaian = [];
$total_target = $total_realisasi = $total_pencapaian = $count = 0;
while($row = $result->fetch_assoc()){
    $labels[] = $row['unit'].' ('.$row['bulan'].')';
    $targets[] = $row['target_2025'];
    $realisasi[] = $row['realisasi_2025'];
    $pencapaian[] = $row['persen_pencapaian'];
    
    $total_target += $row['target_2025'];
    $total_realisasi += $row['realisasi_2025'];
    $total_pencapaian += $row['persen_pencapaian'];
    $count++;
}

$avg_pencapaian = $count ? $total_pencapaian/$count : 0;

// Dropdown
$units  = $conn->query("SELECT DISTINCT unit FROM data_siaga ORDER BY unit");
$bulans = $conn->query("SELECT DISTINCT bulan FROM data_siaga ORDER BY FIELD(bulan,'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER')");

// Reset pointer untuk tabel
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Siaga - PLN</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Library untuk Ekspor -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
<style>
    :root {
        --primary: #0A4C95;
        --primary-light: #3a6da7;
        --primary-dark: #073b76;
        --secondary: #FFA500;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
        --light: #f8f9fa;
        --dark: #343a40;
        --gray: #6c757d;
        --light-gray: #e9ecef;
        --border: #dee2e6;
        --white: #ffffff;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f5f7fa;
        color: var(--dark);
        line-height: 1.6;
    }

    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Header */
    header {
        background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        color: white;
        padding: 15px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .logo-icon {
        font-size: 2rem;
        color: #FFD700;
    }

    .logo-text h1 {
        font-size: 1.5rem;
        font-weight: 600;
        line-height: 1.2;
    }

    .logo-text span {
        font-size: 0.8rem;
        opacity: 0.9;
        font-weight: 300;
    }

    .user-actions {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: var(--light);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-weight: bold;
    }

    .logout-btn {
        background: rgba(255,255,255,0.15);
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 30px;
        cursor: pointer;
        transition: background 0.3s;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }

    .logout-btn:hover {
        background: rgba(255,255,255,0.25);
    }

    /* Main Content */
    .dashboard-title {
        margin-bottom: 25px;
    }

    .dashboard-title h2 {
        font-size: 1.8rem;
        color: var(--primary);
        font-weight: 600;
        margin-bottom: 5px;
    }

    .dashboard-title p {
        color: var(--gray);
    }

    /* Filter Section */
    .filter-card {
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 30px;
    }

    .filter-title {
        font-size: 1.1rem;
        color: var(--primary);
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-title i {
        color: var(--secondary);
    }

    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--primary-dark);
        font-size: 0.9rem;
    }

    .filter-select {
        width: 100%;
        padding: 10px 15px;
        border: 1px solid var(--border);
        border-radius: 6px;
        background: var(--white);
        font-size: 0.9rem;
        color: var(--dark);
        transition: all 0.3s;
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(10, 76, 149, 0.1);
    }

    .filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }

    .btn-primary {
        background: var(--primary);
        color: var(--white);
    }

    .btn-primary:hover {
        background: var(--primary-dark);
    }

    .btn-secondary {
        background: var(--light-gray);
        color: var(--dark);
    }

    .btn-secondary:hover {
        background: #d1d5db;
    }

    .btn-success {
        background: var(--success);
        color: var(--white);
    }

    .btn-success:hover {
        background: #218838;
    }

    /* Cards Section */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .card {
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        padding: 20px;
        transition: transform 0.3s;
    }

    .card:hover {
        transform: translateY(-5px);
    }

    .card-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 15px;
        font-size: 1.5rem;
    }

    .card-target .card-icon {
        background: rgba(10, 76, 149, 0.1);
        color: var(--primary);
    }

    .card-realisasi .card-icon {
        background: rgba(40, 167, 69, 0.1);
        color: var(--success);
    }

    .card-persen .card-icon {
        background: rgba(220, 53, 69, 0.1);
        color: var(--danger);
    }

    .card h3 {
        font-size: 1rem;
        color: var(--gray);
        margin-bottom: 10px;
        font-weight: 500;
    }

    .card p {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--dark);
    }

    /* Table Section */
    .table-container {
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 30px;
        overflow-x: auto;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .table-title {
        font-size: 1.2rem;
        color: var(--primary);
        font-weight: 600;
    }

    .table-actions {
        display: flex;
        gap: 10px;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .data-table th {
        background: var(--primary);
        color: var(--white);
        text-align: center;
        padding: 12px 15px;
        font-weight: 500;
        font-size: 0.9rem;
    }

    .data-table td {
        padding: 10px 15px;
        border-bottom: 1px solid var(--border);
        text-align: center;
        font-size: 0.9rem;
    }

    .data-table tr:nth-child(even) {
        background: var(--light);
    }

    .data-table tr:hover {
        background: rgba(10, 76, 149, 0.05);
    }

    .highlight {
        font-weight: 600;
    }

    .highlight-success {
        color: var(--success);
    }

    .highlight-warning {
        color: var(--warning);
    }

    .highlight-danger {
        color: var(--danger);
    }

    .no-data {
        text-align: center;
        padding: 30px;
        color: var(--gray);
        font-style: italic;
    }

    /* Charts Section */
    .charts-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
        margin-bottom: 30px;
    }

    .chart-card {
        background: var(--white);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        padding: 20px;
    }

    .chart-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .chart-title {
        font-size: 1.1rem;
        color: var(--primary);
        font-weight: 600;
    }

    .chart-actions {
        display: flex;
        gap: 10px;
    }

    .chart-btn {
        background: var(--light-gray);
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--dark);
        transition: all 0.3s;
    }

    .chart-btn:hover {
        background: var(--primary);
        color: var(--white);
    }

    .chart-wrapper {
        position: relative;
        height: 400px;
    }

    /* Footer */
    footer {
        background: var(--primary);
        color: var(--white);
        padding: 20px 0;
        margin-top: 40px;
    }

    .footer-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .copyright {
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .footer-links {
        display: flex;
        gap: 15px;
    }

    .footer-links a {
        color: var(--white);
        text-decoration: none;
        opacity: 0.8;
        transition: opacity 0.3s;
        font-size: 0.9rem;
    }

    .footer-links a:hover {
        opacity: 1;
    }

    /* Back Link */
    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        color: var(--primary);
        text-decoration: none;
        margin-top: 20px;
        font-weight: 500;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .user-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .footer-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }
</style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="logo-text">
                        <h1>PLN DASHBOARD</h1>
                        <span>Monitoring Data Siaga</span>
                    </div>
                </div>
                <div class="user-actions">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin') ?></span>
                    </div>
                    <button class="logout-btn" onclick="location.href='logout.php'">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container">
        <div class="dashboard-title">
            <h2><i class="fas fa-shield-alt"></i> Data PI - Waktu Siaga</h2>
            <p>Monitoring performa waktu siaga per unit dan bulan</p>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <div class="filter-title">
                <i class="fas fa-filter"></i> Filter Data
            </div>
            <form method="get" class="filter-form">
                <div class="filter-group">
                    <label for="unit">Unit</label>
                    <select id="unit" name="unit" class="filter-select">
                        <option value="">Semua Unit</option>
                        <?php while($u = $units->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($u['unit']) ?>" <?= ($u['unit']==$unit_filter)?'selected':'' ?>>
                                <?= htmlspecialchars($u['unit']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="bulan">Bulan</label>
                    <select id="bulan" name="bulan" class="filter-select">
                        <option value="">Semua Bulan</option>
                        <?php $bulans->data_seek(0); while($b = $bulans->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($b['bulan']) ?>" <?= ($b['bulan']==$bulan_filter)?'selected':'' ?>>
                                <?= htmlspecialchars($b['bulan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Terapkan Filter
                    </button>
                    <a href="data_siaga.php" class="btn btn-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Cards -->
        <div class="cards">
            <div class="card card-target">
                <div class="card-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Total Target (menit)</h3>
                <p><?= number_format($total_target, 2) ?></p>
            </div>
            <div class="card card-realisasi">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Total Realisasi (menit)</h3>
                <p><?= number_format($total_realisasi, 2) ?></p>
            </div>
            <div class="card card-persen">
                <div class="card-icon">
                    <i class="fas fa-percent"></i>
                </div>
                <h3>Rata-rata % Pencapaian</h3>
                <p><?= number_format($avg_pencapaian, 2) ?>%</p>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="table-container">
            <div class="table-header">
                <div class="table-title">
                    <i class="fas fa-table"></i> Data Waktu Siaga
                </div>
                <div class="table-actions">
                    <button class="btn btn-primary" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Ekspor PDF
                    </button>
                    <button class="btn btn-success" onclick="exportToExcel()">
                        <i class="fas fa-file-excel"></i> Ekspor Excel
                    </button>
                </div>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>UNIT</th>
                        <th>BULAN</th>
                        <th>TARGET (menit)</th>
                        <th>REALISASI (menit)</th>
                        <th>% PENCAPAIAN</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($count > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['unit']) ?></td>
                                <td><?= htmlspecialchars($row['bulan']) ?></td>
                                <td><?= number_format($row['target_2025'], 2) ?></td>
                                <td><?= number_format($row['realisasi_2025'], 2) ?></td>
                                <td class="highlight 
                                    <?php 
                                    $persen = $row['persen_pencapaian'];
                                    if ($persen >= 100) echo 'highlight-success';
                                    elseif ($persen >= 80) echo 'highlight-warning';
                                    else echo 'highlight-danger';
                                    ?>">
                                    <?= number_format($persen, 2) ?>%
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-database"></i> Tidak ada data yang ditemukan
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Charts Section -->
        <div class="charts-container">
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <i class="fas fa-chart-bar"></i> Perbandingan Target dan Realisasi
                    </div>
                    <div class="chart-actions">
                        <button class="chart-btn"><i class="fas fa-expand"></i></button>
                        <button class="chart-btn"><i class="fas fa-download"></i></button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="barLineChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <i class="fas fa-chart-pie"></i> Distribusi % Pencapaian per Unit
                    </div>
                    <div class="chart-actions">
                        <button class="chart-btn"><i class="fas fa-expand"></i></button>
                        <button class="chart-btn"><i class="fas fa-download"></i></button>
                    </div>
                </div>
                <div class="chart-wrapper">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>
        
        <a href="lihat_pi.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke PI
        </a>
    </main>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="copyright">
                    &copy; <?= date('Y') ?> PT. PLN (Persero) - Dashboard Waktu Siaga
                </div>
                <div class="footer-links">
                    <a href="#"><i class="fas fa-info-circle"></i> Tentang</a>
                    <a href="#"><i class="fas fa-book"></i> Dokumentasi</a>
                    <a href="#"><i class="fas fa-headset"></i> Bantuan</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
    // Prepare chart data
    const labels = <?= json_encode($labels) ?>;
    const targets = <?= json_encode($targets) ?>;
    const realisasi = <?= json_encode($realisasi) ?>;
    const pencapaian = <?= json_encode($pencapaian) ?>;
    
    // Bar + Line Chart
    new Chart(document.getElementById('barLineChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Target 2025 (menit)',
                    data: targets,
                    backgroundColor: 'rgba(10, 76, 149, 0.7)',
                    borderColor: 'rgba(10, 76, 149, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                },
                {
                    label: 'Realisasi 2025 (menit)',
                    data: realisasi,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                },
                {
                    label: '% Pencapaian',
                    data: pencapaian,
                    type: 'line',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    borderWidth: 2,
                    pointRadius: 5,
                    pointBackgroundColor: 'rgba(220, 53, 69, 1)',
                    fill: true,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label.includes('%')) {
                                return label + ': ' + context.raw.toFixed(2) + '%';
                            }
                            return label + ': ' + context.raw.toFixed(2) + ' menit';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Waktu (menit)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: '% Pencapaian'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });
    
    // Pie Chart
    new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: pencapaian,
                backgroundColor: [
                    '#0A4C95', '#28a745', '#ff5733', '#ffc107', '#6610f2', 
                    '#20c997', '#fd7e14', '#6f42c1', '#17a2b8', '#e83e8c',
                    '#198754', '#dc3545', '#0dcaf0', '#6c757d'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.raw.toFixed(2) + '%';
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });

function exportToPDF() {
    try {
        // Buat elemen loading
        const loading = document.createElement('div');
        loading.style.position = 'fixed';
        loading.style.top = '0';
        loading.style.left = '0';
        loading.style.width = '100%';
        loading.style.height = '100%';
        loading.style.backgroundColor = 'rgba(0,0,0,0.5)';
        loading.style.display = 'flex';
        loading.style.justifyContent = 'center';
        loading.style.alignItems = 'center';
        loading.style.zIndex = '9999';
        loading.innerHTML = '<div style="background: white; padding: 20px; border-radius: 5px;"><i class="fas fa-spinner fa-spin"></i> Membuat PDF...</div>';
        document.body.appendChild(loading);

        // Dapatkan elemen yang akan di-capture (seluruh body atau bagian tertentu)
        const element = document.body; // atau document.querySelector('main') untuk bagian tertentu
        
        // Opsi html2canvas
        const options = {
            scale: 2,
            useCORS: true,
            allowTaint: true,
            scrollX: 0,
            scrollY: 0,
            windowWidth: document.documentElement.scrollWidth,
            windowHeight: document.documentElement.scrollHeight,
            backgroundColor: '#ffffff'
        };

        // Gunakan html2canvas untuk menangkap konten
        html2canvas(element, options).then(canvas => {
            const imgData = canvas.toDataURL('image/jpeg', 1.0);
            const pdf = new window.jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });

            const imgWidth = 210; // Lebar A4 dalam mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            const pageHeight = 297; // Tinggi A4 dalam mm
            
            // Hitung jumlah halaman yang diperlukan
            let heightLeft = imgHeight;
            let position = 0;
            
            // Tambahkan halaman pertama
            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
            
            // Tambahkan halaman tambahan jika konten lebih panjang dari satu halaman
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }
            
            // Simpan PDF
            pdf.save('Data_Siaga_PLN_' + new Date().toISOString().slice(0, 10) + '.pdf');
            
            // Hapus loading indicator
            document.body.removeChild(loading);
        }).catch(err => {
            console.error('Error generating PDF:', err);
            document.body.removeChild(loading);
            alert('Gagal membuat PDF. Silakan coba lagi atau hubungi administrator.');
        });
    } catch (err) {
        console.error('Error in PDF export function:', err);
        if (document.querySelector('div[style*="position: fixed;"]')) {
            document.body.removeChild(document.querySelector('div[style*="position: fixed;"]'));
        }
        alert('Terjadi kesalahan sistem saat membuat PDF. Silakan coba lagi nanti.');
    }
}

    // Fungsi untuk ekspor ke Excel (hanya data tabel)
    function exportToExcel() {
        try {
            // Dapatkan tabel
            const table = document.querySelector('.data-table');
            
            // Buat workbook
            const wb = XLSX.utils.book_new();
            
            // Konversi tabel ke worksheet
            const ws = XLSX.utils.table_to_sheet(table);
            
            // Tambahkan worksheet ke workbook
            XLSX.utils.book_append_sheet(wb, ws, 'Data_Siaga');
            
            // Ekspor ke file Excel
            XLSX.writeFile(wb, 'Data_Siaga_PLN.xlsx');
        } catch (err) {
            console.error('Error generating Excel:', err);
            alert('Gagal membuat Excel: ' + err.message);
        }
    }
    </script>
</body>
</html>
<?php $conn->close(); ?>