<?php
session_start();
// Cek login. Jika pengguna belum login, arahkan ke halaman login.
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Inisialisasi variabel dengan nilai default untuk menghindari error jika data tidak ada di sesi.
$username = "Guest";
$unit     = "Tidak diketahui";
$role     = "user";

// Periksa apakah data pengguna ada di sesi dan atur variabel.
if (is_array($_SESSION['user'])) {
    $user = $_SESSION['user'];
    $username = htmlspecialchars($user['username'] ?? 'Guest');
    $unit     = htmlspecialchars($user['unit'] ?? 'Tidak diketahui');
    $role     = htmlspecialchars($user['role'] ?? 'user');
} else {
    // Jika sesi hanya berisi string (username), gunakan itu.
    $username = htmlspecialchars($_SESSION['user']);
}

// Inisialisasi variabel untuk data dashboard.
$bulan = $bobot = $skor = "-";
$bulan_pi = $bobot_pi = $skor_pi = "-";
$skor_numeric = $skor_pi_numeric = 0;

// Array untuk menyimpan data NKO per kategori.
$nko_data = [];

try {
    // Buat koneksi ke database.
    $conn = new mysqli("localhost", "root", "", "pln_dashboard");
    if ($conn->connect_error) {
        throw new Exception("Koneksi gagal: " . $conn->connect_error);
    }

    // Ambil data KPI terbaru.
    $sql_kpi = "SELECT bulan, bobot, skor FROM kpi_data ORDER BY id DESC LIMIT 1";
    $result_kpi = $conn->query($sql_kpi);
    if ($result_kpi && $result_kpi->num_rows > 0) {
        $row = $result_kpi->fetch_assoc();
        $bulan = htmlspecialchars($row['bulan']);
        $bobot = number_format((float)$row['bobot'], 2) . '%';
        $skor_numeric = (float)$row['skor'];
        $skor  = number_format($skor_numeric, 2);
    }

    // Ambil data PI terbaru.
    $sql_pi = "SELECT bulan, bobot, skor FROM pi_data ORDER BY id DESC LIMIT 1";
    $result_pi = $conn->query($sql_pi);
    if ($result_pi && $result_pi->num_rows > 0) {
        $row_pi = $result_pi->fetch_assoc();
        $bulan_pi = htmlspecialchars($row_pi['bulan']);
        $bobot_pi = number_format((float)$row_pi['bobot'], 2) . '%';
        $skor_pi_numeric = (float)$row_pi['skor'];
        $skor_pi  = number_format($skor_pi_numeric, 2);
    }

    // Ambil data NKO untuk semua kategori KPI.
    $sql_nko = "SELECT kategori, bulan, nilai_nko, target_nko FROM nko_data ORDER BY bulan DESC, kategori";
    $result_nko = $conn->query($sql_nko);
    
    if ($result_nko && $result_nko->num_rows > 0) {
        while ($row_nko = $result_nko->fetch_assoc()) {
            $kategori = htmlspecialchars($row_nko['kategori']);
            $bulan_nko = htmlspecialchars($row_nko['bulan']);
            $nilai_nko = (float)$row_nko['nilai_nko'];
            $target_nko = (float)$row_nko['target_nko'];
            
            if (!isset($nko_data[$kategori])) {
                $nko_data[$kategori] = [];
            }
            
            $nko_data[$kategori][] = [
                'bulan' => $bulan_nko,
                'nilai' => $nilai_nko,
                'target' => $target_nko,
                'pencapaian' => $target_nko > 0 ? ($nilai_nko / $target_nko) * 100 : 0
            ];
        }
    }

    $conn->close();
} catch (Exception $e) {
    // Log error jika koneksi atau query gagal.
    error_log("Dashboard error: " . $e->getMessage());
}

// Fungsi untuk menentukan warna berdasarkan skor.
function getScoreColor($score) {
    if ($score >= 80) return 'text-green-600';
    if ($score >= 60) return 'text-yellow-600';
    return 'text-red-600';
}

// Fungsi untuk menentukan warna progress bar.
function getProgressColor($percentage) {
    if ($percentage >= 80) return 'bg-green-500';
    if ($percentage >= 60) return 'bg-yellow-500';
    return 'bg-red-500';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard NKO - PLN</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          colors: {
            'pln-blue': {
              500: '#0A4C95',
              600: '#084280',
              700: '#06386c'
            }
          }
        }
      }
    }
  </script>
  <style>
    body { 
        font-family: 'Inter', sans-serif; 
        background: #f8fafc; 
        color: #334155; 
        transition: background-color 0.3s, color 0.3s;
    }
    .dark body {
        background: #0f172a;
        color: #cbd5e1;
    }
    .pln-blue-gradient { 
        background: linear-gradient(135deg, #0A4C95 0%, #073b76 100%); 
    }
    .dark .pln-blue-gradient {
        background: linear-gradient(135deg, #0c5ab3 0%, #094990 100%);
    }
    .card { 
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .dark .card {
        background: #1e293b;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    .card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    .dark .card:hover {
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }
    .sidebar-link { 
        display: flex; 
        align-items: center; 
        gap: 0.75rem; 
        padding: 0.75rem 1rem; 
        border-radius: 0.5rem; 
        transition: all 0.2s; 
    }
    .sidebar-link:hover { 
        background-color: rgba(255, 255, 255, 0.15); 
    }
    .progress-bar {
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
        background-color: #e2e8f0;
    }
    .dark .progress-bar {
        background-color: #334155;
    }
    .progress-value {
        height: 100%;
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    .tab-active {
        border-bottom: 3px solid #0A4C95;
        color: #0A4C95;
        font-weight: 600;
    }
    .dark .tab-active {
        border-bottom-color: #3b82f6;
        color: #3b82f6;
    }
    .stat-card {
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .nav-tab {
        padding: 12px 20px;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-tab:hover {
        border-bottom: 2px solid #cbd5e1;
        color: #0A4C95;
    }
    .dark .nav-tab:hover {
        border-bottom: 2px solid #475569;
        color: #3b82f6;
    }
  </style>
</head>
<body class="min-h-screen flex bg-gray-50 dark:bg-gray-900">
  <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden" onclick="toggleSidebar()"></div>

  <aside id="sidebar" class="pln-blue-gradient text-white w-64 fixed md:static inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50 flex flex-col">
    <div class="p-6 flex items-center justify-between border-b border-white/20">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 bg-white rounded-lg p-1 flex items-center justify-center">
          <i class="fas fa-bolt text-2xl text-blue-800"></i>
        </div>
        <div>
          <h1 class="text-lg font-bold">PLN Dashboard</h1>
          <p class="text-xs opacity-80">Indikator Kinerja 2025</p>
        </div>
      </div>
      <button id="close-sidebar" class="md:hidden text-white" onclick="toggleSidebar()">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>
    <nav class="flex-1 p-4 space-y-2">
      <a href="dashboard.php" class="sidebar-link bg-white/20"><i class="fas fa-home"></i> Dashboard</a>
      <a href="kpi_overview.php" class="sidebar-link"><i class="fas fa-bullseye"></i> Ikhtisar KPI</a>
      <a href="pi_overview.php" class="sidebar-link"><i class="fas fa-chart-bar"></i> Ikhtisar PI</a>
      <a href="nko_trends.php" class="sidebar-link"><i class="fas fa-chart-line"></i> Tren NKO</a>

      <?php if ($role === 'admin'): ?>
      <div class="pt-4 border-t border-white/20">
        <p class="text-xs uppercase text-white/70 pl-4 mb-2">Administrasi</p>
        <a href="manage_users.php" class="sidebar-link"><i class="fas fa-users-cog"></i> Manajemen User</a>
        <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a>
      </div>
      <?php endif; ?>
    </nav>
    <div class="p-4 border-t border-white/20 space-y-4">
      <div class="flex items-center justify-between">
        <span class="text-sm">Mode Gelap</span>
        <label class="theme-toggle">
          <input type="checkbox" id="theme-toggle">
          <span class="theme-slider"></span>
        </label>
      </div>
      
      <div class="flex items-center gap-2 text-sm">
        <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center">
          <i class="fas fa-user text-white text-sm"></i>
        </div>
        <div>
          <div class="font-medium"><?= $username; ?></div>
          <div class="text-xs opacity-80"><?= $role; ?> · <?= $unit; ?></div>
        </div>
      </div>
      <a href="logout.php" class="block text-center bg-white text-pln-blue-500 font-semibold py-2 px-6 rounded-lg hover:bg-gray-100 transition flex items-center justify-center">
        <i class="fas fa-sign-out-alt mr-2"></i>Logout
      </a>
    </div>
  </aside>

  <div class="flex-1 flex flex-col min-h-screen">
    <header class="bg-white dark:bg-gray-800 shadow-sm px-4 py-3 flex items-center justify-between md:justify-end sticky top-0 z-40">
      <button id="menu-btn" class="text-pln-blue-500 dark:text-blue-400 text-xl p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 md:hidden" onclick="toggleSidebar()">
        <div class="hamburger" id="hamburger">
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
          <span class="hamburger-line"></span>
        </div>
      </button>
      <h1 class="font-bold text-pln-blue-500 dark:text-blue-400 text-lg md:absolute md:left-1/2 md:transform md:-translate-x-1/2">Dashboard Utama - PLN</h1>
      <div class="flex items-center gap-4">
        <div class="hidden md:flex items-center gap-2 text-sm">
          <div class="w-8 h-8 rounded-full bg-blue-700 flex items-center justify-center">
            <i class="fas fa-user text-white text-sm"></i>
          </div>
          <div class="hidden lg:block">
            <div class="font-medium dark:text-white"><?= $username; ?></div>
            <div class="text-xs text-gray-500 dark:text-gray-400"><?= $role; ?> · <?= $unit; ?></div>
          </div>
        </div>
      </div>
    </header>

    <main class="p-4 md:p-6 flex-1">
      <!-- Header Selamat Datang -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-blue-900 text-white p-6 rounded-2xl shadow-lg mb-6">
        <h2 class="text-2xl font-bold mb-2">Selamat datang, <?= $username; ?>!</h2>
        <p class="opacity-90">Berikut adalah ringkasan performa unit <?= $unit; ?> bulan <?= $bulan; ?>.</p>
      </div>

      <!-- Tab Navigasi -->
      <div class="flex border-b border-gray-200 dark:border-gray-700 mb-6 overflow-x-auto">
        <div class="nav-tab tab-active" data-tab="overview">
          <i class="fas fa-home mr-2"></i>Ikhtisar
        </div>
        <div class="nav-tab" data-tab="kpi">
          <i class="fas fa-bullseye mr-2"></i>KPI
        </div>
        <div class="nav-tab" data-tab="pi">
          <i class="fas fa-chart-bar mr-2"></i>PI
        </div>
        <div class="nav-tab" data-tab="nko">
          <i class="fas fa-chart-line mr-2"></i>NKO
        </div>
      </div>

      <!-- Konten Tab: Ikhtisar -->
      <div id="tab-overview" class="tab-content">
        <!-- Ringkasan Performa -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="stat-card card p-5 border-l-green-500">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Skor KPI</p>
                <h3 class="text-2xl font-bold mt-1 <?= getScoreColor($skor_numeric) ?>"><?= $skor; ?></h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Bobot: <?= $bobot; ?></p>
              </div>
              <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
                <i class="fas fa-bullseye text-green-600 dark:text-green-400 text-xl"></i>
              </div>
            </div>
            <div class="mt-4">
              <div class="flex justify-between text-xs mb-1">
                <span>Progress</span>
                <span><?= $skor_numeric ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-value <?= getProgressColor($skor_numeric) ?>" style="width: <?= $skor_numeric ?>%"></div>
              </div>
            </div>
          </div>

          <div class="stat-card card p-5 border-l-blue-500">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Skor PI</p>
                <h3 class="text-2xl font-bold mt-1 <?= getScoreColor($skor_pi_numeric) ?>"><?= $skor_pi; ?></h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Bobot: <?= $bobot_pi; ?></p>
              </div>
              <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
                <i class="fas fa-chart-bar text-blue-600 dark:text-blue-400 text-xl"></i>
              </div>
            </div>
            <div class="mt-4">
              <div class="flex justify-between text-xs mb-1">
                <span>Progress</span>
                <span><?= $skor_pi_numeric ?>%</span>
              </div>
              <div class="progress-bar">
                <div class="progress-value <?= getProgressColor($skor_pi_numeric) ?>" style="width: <?= $skor_pi_numeric ?>%"></div>
              </div>
            </div>
          </div>

          <div class="stat-card card p-5 border-l-purple-500">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm text-gray-500 dark:text-gray-400">Status Unit</p>
                <h3 class="text-2xl font-bold mt-1 text-gray-800 dark:text-white">Aktif</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Terakhir update: <?= date('d M Y'); ?></p>
              </div>
              <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
                <i class="fas fa-check-circle text-purple-600 dark:text-purple-400 text-xl"></i>
              </div>
            </div>
            <div class="mt-4">
              <div class="flex justify-between text-xs mb-1">
                <span>Performa</span>
                <span class="<?= getScoreColor(($skor_numeric + $skor_pi_numeric)/2) ?>">
                  <?php 
                  $avg_score = ($skor_numeric + $skor_pi_numeric)/2;
                  if ($avg_score >= 80) echo "Baik";
                  elseif ($avg_score >= 60) echo "Cukup";
                  else echo "Perlu Perbaikan";
                  ?>
                </span>
              </div>
              <div class="progress-bar">
                <div class="progress-value <?= getProgressColor($avg_score) ?>" style="width: <?= $avg_score ?>%"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Grafik Tren Bulanan -->
        <div class="card p-6 mb-6">
          <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Tren Performa 6 Bulan Terakhir</h3>
          <div class="h-64">
            <canvas id="performanceTrendChart"></canvas>
          </div>
        </div>

        <!-- Aktivitas Terbaru -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
              <i class="fas fa-history text-green-500"></i>
              Aktivitas Terbaru
            </h3>
            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center mt-1">
                  <i class="fas fa-sync text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                  <p class="text-sm font-medium dark:text-gray-200">Data terupdate untuk bulan <?= $bulan; ?></p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Hari ini, <?= date('H:i'); ?></p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center mt-1">
                  <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                  <p class="text-sm font-medium dark:text-gray-200">Laporan bulanan telah diverifikasi</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kemarin, 15:42</p>
                </div>
              </div>
              <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center mt-1">
                  <i class="fas fa-user-plus text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                  <p class="text-sm font-medium dark:text-gray-200">Admin menambahkan user baru</p>
                  <p class="text-xs text-gray-500 dark:text-gray-400">2 hari yang lalu</p>
                </div>
              </div>
            </div>
          </div>

          <div class="card p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 flex items-center gap-2">
              <i class="fas fa-info-circle text-blue-500"></i>
              Informasi PLN
            </h3>
            <p class="text-gray-700 dark:text-gray-300 leading-relaxed text-sm mb-4">
              PT PLN (Persero) adalah Badan Usaha Milik Negara (BUMN) yang bergerak di bidang ketenagalistrikan di Indonesia. 
              Dengan visi "Menjadi Perusahaan Listrik Terkemuka se-Asia Tenggara", PLN terus melakukan transformasi digital 
              dan meningkatkan pelayanan kepada pelanggan.
            </p>
            <div class="flex gap-2">
              <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg text-center">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">98%</div>
                <div class="text-xs text-gray-600 dark:text-gray-300">Electrification Ratio</div>
              </div>
              <div class="flex-1 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg text-center">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">35.5</div>
                <div class="text-xs text-gray-600 dark:text-gray-300">GW Kapasitas</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Konten Tab: KPI -->
      <div id="tab-kpi" class="tab-content hidden">
        <div class="card p-6 mb-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Key Performance Indicator (KPI)</h3>
            <div class="flex items-center gap-2 mt-2 md:mt-0">
              <div class="text-sm text-gray-500 dark:text-gray-400">Periode: <?= $bulan; ?></div>
              <?php if ($role === 'admin'): ?>
                <a href="update_kpi.php?bulan=<?= urlencode($bulan); ?>" class="text-sm bg-pln-blue-500 text-white py-1 px-3 rounded-lg hover:bg-pln-blue-600 transition">
                  <i class="fas fa-edit mr-1"></i>Edit
                </a>
              <?php endif; ?>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 p-5 rounded-lg">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center">
                  <i class="fas fa-bullseye text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 dark:text-white">Skor KPI</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Nilai pencapaian</p>
                </div>
              </div>
              <div class="text-center py-4">
                <div class="text-4xl font-bold <?= getScoreColor($skor_numeric) ?>"><?= $skor; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">dari target 100</div>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 p-5 rounded-lg">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                  <i class="fas fa-weight text-gray-600 dark:text-gray-400"></i>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 dark:text-white">Bobot KPI</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kontribusi terhadap NKO</p>
                </div>
              </div>
              <div class="text-center py-4">
                <div class="text-4xl font-bold text-gray-800 dark:text-white"><?= $bobot; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">dari total penilaian</div>
              </div>
            </div>
          </div>

          <div class="mt-6">
            <h4 class="font-semibold text-gray-800 dark:text-white mb-3">Detail Kategori KPI</h4>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead>
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Kategori</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bulan</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nilai</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Target</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pencapaian</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                  <?php if (!empty($nko_data)): ?>
                    <?php foreach ($nko_data as $kategori => $data): ?>
                      <?php $latest = $data[0]; ?>
                      <tr>
                        <td class="px-4 py-3 text-sm"><?= $kategori ?></td>
                        <td class="px-4 py-3 text-sm"><?= $latest['bulan'] ?></td>
                        <td class="px-4 py-3 text-sm"><?= number_format($latest['nilai'], 2) ?></td>
                        <td class="px-4 py-3 text-sm"><?= number_format($latest['target'], 2) ?></td>
                        <td class="px-4 py-3 text-sm">
                          <span class="<?= getScoreColor($latest['pencapaian']) ?>">
                            <?= number_format($latest['pencapaian'], 2) ?>%
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data KPI</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <div class="mt-6 text-center">
            <a href="lihat_kpi.php" class="inline-flex items-center justify-center gap-2 bg-pln-blue-500 text-white font-medium py-2 px-6 rounded-lg hover:bg-pln-blue-600 transition">
              Lihat Detail Lengkap
              <i class="fas fa-arrow-right text-xs"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Konten Tab: PI -->
      <div id="tab-pi" class="tab-content hidden">
        <div class="card p-6 mb-6">
          <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Performance Indicator (PI)</h3>
            <div class="text-sm text-gray-500 dark:text-gray-400 mt-2 md:mt-0">Periode: <?= $bulan_pi; ?></div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-green-50 dark:bg-green-900/20 p-5 rounded-lg">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-800 flex items-center justify-center">
                  <i class="fas fa-chart-bar text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 dark:text-white">Skor PI</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Nilai pencapaian</p>
                </div>
              </div>
              <div class="text-center py-4">
                <div class="text-4xl font-bold <?= getScoreColor($skor_pi_numeric) ?>"><?= $skor_pi; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">dari target 100</div>
              </div>
            </div>

            <div class="bg-gray-50 dark:bg-gray-800 p-5 rounded-lg">
              <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                  <i class="fas fa-weight text-gray-600 dark:text-gray-400"></i>
                </div>
                <div>
                  <h4 class="font-semibold text-gray-800 dark:text-white">Bobot PI</h4>
                  <p class="text-xs text-gray-500 dark:text-gray-400">Kontribusi terhadap NKO</p>
                </div>
              </div>
              <div class="text-center py-4">
                <div class="text-4xl font-bold text-gray-800 dark:text-white"><?= $bobot_pi; ?></div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-2">dari total penilaian</div>
              </div>
            </div>
          </div>

          <div class="mt-6 text-center">
            <a href="lihat_pi.php" class="inline-flex items-center justify-center gap-2 bg-green-600 text-white font-medium py-2 px-6 rounded-lg hover:bg-green-700 transition">
              Lihat Detail Lengkap
              <i class="fas fa-arrow-right text-xs"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Konten Tab: NKO -->
      <div id="tab-nko" class="tab-content hidden">
        <div class="card p-6 mb-6">
          <h3 class="text-xl font-semibold text-gray-800 dark:text-white mb-6">Nilai Kinerja Organisasi (NKO)</h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <?php if (!empty($nko_data)): ?>
              <?php foreach ($nko_data as $kategori => $data): ?>
                <div class="bg-white dark:bg-gray-800 p-5 rounded-lg border border-gray-200 dark:border-gray-700">
                  <h4 class="font-semibold text-gray-800 dark:text-white mb-4"><?= $kategori ?></h4>
                  <div class="h-64">
                    <canvas id="nkoChart-<?= preg_replace('/[^a-zA-Z0-9]/', '_', $kategori) ?>"></canvas>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-span-2 text-center py-8">
                <i class="fas fa-chart-bar text-4xl text-gray-400 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">Data NKO belum tersedia</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <footer class="text-center text-gray-500 dark:text-gray-400 text-sm pt-6 border-t border-gray-200 dark:border-gray-700">
        <p class="font-semibold text-gray-700 dark:text-gray-300">PT PLN (Persero) - Sistem Manajemen Kinerja 2025</p>
        <p>Update terakhir: <?= date('d F Y'); ?> | © 2025 Hak Cipta Dilindungi</p>
      </footer>
    </main>
  </div>

  <script>
    // Toggle sidebar function
    function toggleSidebar() {
      const sidebar = document.getElementById('sidebar');
      const overlay = document.getElementById('sidebar-overlay');
      const hamburger = document.getElementById('hamburger');
      
      sidebar.classList.toggle('-translate-x-full');
      overlay.classList.toggle('hidden');
      hamburger.classList.toggle('hamburger-active');
    }

    // Tab navigation
    document.querySelectorAll('.nav-tab').forEach(tab => {
      tab.addEventListener('click', () => {
        // Remove active class from all tabs
        document.querySelectorAll('.nav-tab').forEach(t => {
          t.classList.remove('tab-active');
        });
        
        // Add active class to clicked tab
        tab.classList.add('tab-active');
        
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(content => {
          content.classList.add('hidden');
        });
        
        // Show the selected tab content
        const tabId = tab.getAttribute('data-tab');
        document.getElementById(`tab-${tabId}`).classList.remove('hidden');
      });
    });

    // Theme toggle functionality
    const themeToggle = document.getElementById('theme-toggle');
    
    // Check for saved theme preference or respect OS preference
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
      themeToggle.checked = true;
    } else {
      document.documentElement.classList.remove('dark');
      themeToggle.checked = false;
    }
    
    // Listen for toggle changes
    themeToggle.addEventListener('change', function() {
      if (this.checked) {
        document.documentElement.classList.add('dark');
        localStorage.theme = 'dark';
      } else {
        document.documentElement.classList.remove('dark');
        localStorage.theme = 'light';
      }
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
      const sidebar = document.getElementById('sidebar');
      const menuBtn = document.getElementById('menu-btn');
      
      if (window.innerWidth < 768 && 
          !sidebar.contains(e.target) && 
          e.target !== menuBtn && 
          !menuBtn.contains(e.target)) {
        sidebar.classList.add('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.add('hidden');
        document.getElementById('hamburger').classList.remove('hamburger-active');
      }
    });

    // Initialize performance trend chart
    const trendCtx = document.getElementById('performanceTrendChart').getContext('2d');
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'],
        datasets: [
          {
            label: 'KPI',
            data: [75, 78, 82, 80, 85, 88],
            borderColor: '#0A4C95',
            backgroundColor: 'rgba(10, 76, 149, 0.1)',
            fill: true,
            tension: 0.3
          },
          {
            label: 'PI',
            data: [70, 72, 75, 77, 80, 82],
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: false,
            min: 60,
            max: 100,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
          }
        }
      }
    });

    // Initialize NKO charts
    <?php if (!empty($nko_data)): ?>
      <?php foreach ($nko_data as $kategori => $data): ?>
        <?php
        $chart_id = preg_replace('/[^a-zA-Z0-9]/', '_', $kategori);
        $labels = [];
        $nilai_data = [];
        $target_data = [];
        
        foreach ($data as $item) {
          $labels[] = $item['bulan'];
          $nilai_data[] = $item['nilai'];
          $target_data[] = $item['target'];
        }
        ?>
        const ctx<?= $chart_id ?> = document.getElementById('nkoChart-<?= $chart_id ?>').getContext('2d');
        new Chart(ctx<?= $chart_id ?>, {
          type: 'bar',
          data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
              {
                label: 'Nilai NKO',
                data: <?= json_encode($nilai_data) ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
              },
              {
                label: 'Target NKO',
                data: <?= json_encode($target_data) ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                type: 'line',
                fill: false
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: {
                beginAtZero: true,
                title: {
                  display: true,
                  text: 'Nilai'
                }
              },
              x: {
                title: {
                  display: true,
                  text: 'Bulan'
                }
              }
            },
            plugins: {
              title: {
                display: true,
                text: 'NKO <?= $kategori ?>'
              }
            }
          }
        });
      <?php endforeach; ?>
    <?php endif; ?>

    // Add help tooltips throughout the page
    document.addEventListener('DOMContentLoaded', function() {
      // Add tooltips to key metrics
      const tooltipTriggers = document.querySelectorAll('.info-tooltip');
      tooltipTriggers.forEach(trigger => {
        trigger.addEventListener('mouseenter', function() {
          this.querySelector('.tooltip-text').style.visibility = 'visible';
          this.querySelector('.tooltip-text').style.opacity = '1';
        });
        trigger.addEventListener('mouseleave', function() {
          this.querySelector('.tooltip-text').style.visibility = 'hidden';
          this.querySelector('.tooltip-text').style.opacity = '0';
        });
      });
    });
  </script>
</body>
</html>