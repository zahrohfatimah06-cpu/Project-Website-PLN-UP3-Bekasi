<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Data contoh untuk laporan (dalam aplikasi nyata ini akan diambil dari database)
$reports = [
    [
        'id' => 'KPI-2025-Q1',
        'title' => 'Laporan KPI Triwulan I 2025',
        'type' => 'KPI',
        'period' => 'Januari - Maret 2025',
        'date' => '2025-04-05',
        'author' => 'Departemen Kinerja',
        'downloads' => 128,
        'status' => 'Disetujui',
        'summary' => 'Pencapaian target KPI triwulan pertama menunjukkan peningkatan 2.3% dibandingkan periode yang sama tahun lalu.'
    ],
    [
        'id' => 'PI-2025-04',
        'title' => 'Laporan PI Bulan April 2025',
        'type' => 'PI',
        'period' => 'April 2025',
        'date' => '2025-05-03',
        'author' => 'Tim Analisis Operasional',
        'downloads' => 76,
        'status' => 'Disetujui',
        'summary' => 'Performa operasional bulan April menunjukkan peningkatan keandalan jaringan namun perlu perhatian pada kepuasan pelanggan.'
    ],
    [
        'id' => 'KPI-2024-AN',
        'title' => 'Laporan Kinerja Tahunan 2024',
        'type' => 'KPI',
        'period' => 'Tahun 2024',
        'date' => '2025-01-15',
        'author' => 'Divisi Strategi',
        'downloads' => 215,
        'status' => 'Final',
        'summary' => 'Pencapaian kinerja tahun 2024 menunjukkan pertumbuhan yang stabil dengan peningkatan 5.7% pada kepuasan pelanggan.'
    ],
    [
        'id' => 'PI-2025-05',
        'title' => 'Laporan PI Bulan Mei 2025',
        'type' => 'PI',
        'period' => 'Mei 2025',
        'date' => '2025-06-02',
        'author' => 'Tim Analisis Operasional',
        'downloads' => 42,
        'status' => 'Draft',
        'summary' => 'Preliminary report menunjukkan peningkatan signifikan pada indikator keandalan jaringan setelah implementasi program baru.'
    ]
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kinerja - PLN Performance Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0A4C95;        /* PLN Blue */
            --primary-dark: #073b76;
            --primary-light: #e6f0ff;
            --secondary: #FFA500;      /* Accent Orange */
            --accent: #00C6FF;         /* PLN Light Blue */
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --background: linear-gradient(135deg, #f0f5ff 0%, #e6f0ff 100%);
            --card-bg: #ffffff;
            --shadow: rgba(0, 0, 0, 0.08);
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', sans-serif;
        }

        body {
            background: var(--background);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header/Navbar */
        .header {
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .logo i {
            font-size: 24px;
            color: var(--primary);
        }

        .header-title {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 30px;
            transition: all 0.3s ease;
        }

        .user-info a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .user-info i {
            font-size: 18px;
        }

        .welcome-text {
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Main Content */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .dashboard-title {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            position: relative;
            display: inline-block;
        }

        .dashboard-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .dashboard-subtitle {
            color: var(--text-light);
            font-size: 18px;
            max-width: 700px;
            margin: 25px auto 0;
            line-height: 1.7;
        }

        /* Filter Section */
        .filter-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 6px 20px var(--shadow);
            margin-bottom: 40px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary);
        }

        .filter-group select, .filter-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #f9fbfd;
            font-size: 15px;
            transition: all 0.3s;
        }

        .filter-group select:focus, .filter-group input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(10, 76, 149, 0.1);
        }

        .filter-button {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .filter-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* Report Stats */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 6px 20px var(--shadow);
            text-align: center;
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 24px;
            color: var(--primary);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .stat-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--text-dark);
        }

        /* Report List */
        .reports-container {
            margin-top: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            font-size: 22px;
        }

        .report-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .report-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 6px 20px var(--shadow);
            overflow: hidden;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .report-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
        }

        .report-header {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-type {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .report-id {
            font-size: 14px;
            opacity: 0.8;
        }

        .report-content {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .report-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 15px;
            line-height: 1.4;
        }

        .report-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--text-light);
        }

        .meta-item i {
            color: var(--primary);
        }

        .report-summary {
            color: var(--text-light);
            font-size: 15px;
            line-height: 1.7;
            margin-bottom: 25px;
            flex-grow: 1;
        }

        .report-status {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .status-approved {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success);
        }

        .status-draft {
            background: rgba(255, 193, 7, 0.15);
            color: var(--warning);
        }

        .status-final {
            background: rgba(13, 110, 253, 0.15);
            color: #0d6efd;
        }

        .report-actions {
            display: flex;
            gap: 15px;
            margin-top: auto;
        }

        .download-btn {
            flex: 1;
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .download-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .preview-btn {
            flex: 1;
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
            text-decoration: none;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .preview-btn:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        /* Chart Section */
        .chart-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 6px 20px var(--shadow);
            margin: 50px 0;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .chart-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 20px;
            text-align: center;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 25px;
            color: var(--text-light);
            font-size: 15px;
            border-top: 1px solid #e0e7ff;
            margin-top: 80px;
            background: var(--card-bg);
        }

        .footer-logo {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 15px;
        }

        /* Responsiveness */
        @media (max-width: 992px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .logo-container {
                justify-content: center;
            }
            
            .user-info {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .dashboard-title {
                font-size: 28px;
            }
            
            .report-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-container">
            <div class="logo">
                <i class="fas fa-bolt"></i>
            </div>
            <div>
                <div class="header-title">PLN - Performance Dashboard</div>
                <div>Laporan Kinerja 2025</div>
            </div>
        </div>
        
        <div class="user-info">
            <span class="welcome-text">
                <i class="fas fa-user-circle"></i> Selamat datang, <strong><?php echo $_SESSION['user']; ?></strong>
            </span>
            <a href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Laporan Kinerja</h1>
            <div class="dashboard-subtitle">
                Akses dan unduh laporan kinerja terbaru. Pantau perkembangan indikator kinerja utama (KPI) dan indikator kinerja (PI) untuk evaluasi strategis.
            </div>
        </div>

        <!-- Report Stats -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value">24</div>
                <div class="stat-title">Total Laporan</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-download"></i>
                </div>
                <div class="stat-value">461</div>
                <div class="stat-title">Total Unduhan</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value">18</div>
                <div class="stat-title">Laporan Disetujui</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-pen"></i>
                </div>
                <div class="stat-value">3</div>
                <div class="stat-title">Dalam Proses</div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label for="report-type"><i class="fas fa-filter"></i> Jenis Laporan</label>
                <select id="report-type">
                    <option value="all">Semua Jenis</option>
                    <option value="kpi">KPI (Indikator Kinerja Utama)</option>
                    <option value="pi">PI (Indikator Kinerja)</option>
                    <option value="other">Laporan Lainnya</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="time-period"><i class="fas fa-calendar"></i> Periode</label>
                <select id="time-period">
                    <option value="all">Semua Periode</option>
                    <option value="monthly">Bulanan</option>
                    <option value="quarterly">Triwulan</option>
                    <option value="yearly">Tahunan</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search"><i class="fas fa-search"></i> Cari Laporan</label>
                <input type="text" id="search" placeholder="Masukkan kata kunci...">
            </div>
            
            <div class="filter-group" style="align-self: flex-end;">
                <button class="filter-button">
                    <i class="fas fa-sync-alt"></i> Terapkan Filter
                </button>
            </div>
        </div>

        <!-- Performance Charts -->
        <div class="chart-section">
            <h2 class="section-title"><i class="fas fa-chart-line"></i> Tren Kinerja Terkini</h2>
            
            <div class="chart-grid">
                <div class="chart-container">
                    <div class="chart-title">Pencapaian Target KPI (2024-2025)</div>
                    <canvas id="kpiChart"></canvas>
                </div>
                
                <div class="chart-container">
                    <div class="chart-title">Kepuasan Pelanggan per Kuartal</div>
                    <canvas id="satisfactionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Report List -->
        <div class="reports-container">
            <h2 class="section-title"><i class="fas fa-file-pdf"></i> Daftar Laporan Tersedia</h2>
            
            <div class="report-list">
                <?php foreach ($reports as $report): ?>
                <div class="report-card">
                    <div class="report-header">
                        <div class="report-type"><?php echo $report['type']; ?></div>
                        <div class="report-id">ID: <?php echo $report['id']; ?></div>
                    </div>
                    
                    <div class="report-content">
                        <h3 class="report-title"><?php echo $report['title']; ?></h3>
                        
                        <div class="report-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span>Periode: <?php echo $report['period']; ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <span>Oleh: <?php echo $report['author']; ?></span>
                            </div>
                        </div>
                        
                        <div class="report-status <?php 
                            echo ($report['status'] == 'Disetujui') ? 'status-approved' : 
                                 (($report['status'] == 'Draft') ? 'status-draft' : 'status-final'); 
                        ?>">
                            <i class="fas fa-circle"></i> <?php echo $report['status']; ?>
                        </div>
                        
                        <p class="report-summary"><?php echo $report['summary']; ?></p>
                        
                        <div class="meta-item">
                            <i class="fas fa-download"></i>
                            <span><?php echo $report['downloads']; ?> kali diunduh</span>
                        </div>
                        
                        <div class="report-actions">
                            <a href="#" class="download-btn">
                                <i class="fas fa-download"></i> Unduh
                            </a>
                            <a href="#" class="preview-btn">
                                <i class="fas fa-eye"></i> Pratinjau
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-logo">
            <i class="fas fa-bolt"></i>
        </div>
        <p>PT PLN (Persero) - Sistem Manajemen Kinerja 2025</p>
        <p>Update terakhir: <?php echo date('d F Y'); ?> | © 2025 Hak Cipta Dilindungi</p>
    </div>

    <script>
        // Inisialisasi chart
        document.addEventListener('DOMContentLoaded', function() {
            // KPI Achievement Chart
            const kpiCtx = document.getElementById('kpiChart').getContext('2d');
            const kpiChart = new Chart(kpiCtx, {
                type: 'line',
                data: {
                    labels: ['Q1 2024', 'Q2 2024', 'Q3 2024', 'Q4 2024', 'Q1 2025'],
                    datasets: [{
                        label: 'Pencapaian Target',
                        data: [92.5, 94.2, 95.8, 97.1, 98.7],
                        borderColor: '#0A4C95',
                        backgroundColor: 'rgba(10, 76, 149, 0.1)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Target',
                        data: [95, 95, 95, 96, 97],
                        borderColor: '#FFA500',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        y: {
                            min: 90,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Persentase (%)'
                            }
                        }
                    }
                }
            });

            // Customer Satisfaction Chart
            const satisfactionCtx = document.getElementById('satisfactionChart').getContext('2d');
            const satisfactionChart = new Chart(satisfactionCtx, {
                type: 'bar',
                data: {
                    labels: ['Q1 2024', 'Q2 2024', 'Q3 2024', 'Q4 2024', 'Q1 2025'],
                    datasets: [{
                        label: 'Kepuasan Pelanggan',
                        data: [82.3, 84.5, 85.7, 86.2, 87.5],
                        backgroundColor: [
                            'rgba(10, 76, 149, 0.7)',
                            'rgba(10, 76, 149, 0.7)',
                            'rgba(10, 76, 149, 0.7)',
                            'rgba(10, 76, 149, 0.7)',
                            'rgba(255, 165, 0, 0.7)'
                        ],
                        borderColor: [
                            '#0A4C95',
                            '#0A4C95',
                            '#0A4C95',
                            '#0A4C95',
                            '#FFA500'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            min: 80,
                            max: 90,
                            title: {
                                display: true,
                                text: 'Persentase (%)'
                            }
                        }
                    }
                }
            });

            // Report filtering simulation
            const filterButton = document.querySelector('.filter-button');
            filterButton.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menerapkan...';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-check"></i> Filter Diterapkan';
                    this.style.background = '#28a745';
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = '<i class="fas fa-sync-alt"></i> Terapkan Filter';
                        this.style.background = '';
                    }, 2000);
                }, 1000);
            });

            // Report card hover effect
            const reportCards = document.querySelectorAll('.report-card');
            reportCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>