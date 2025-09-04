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

// Inisialisasi variabel untuk data KPI
$kpi_data = [];
$latest_kpi = [
    'bulan' => '-',
    'bobot' => '-',
    'skor' => '-',
    'skor_numeric' => 0
];

try {
    // Buat koneksi ke database.
    $conn = new mysqli("localhost", "root", "", "pln_dashboard");
    if ($conn->connect_error) {
        throw new Exception("Koneksi gagal: " . $conn->connect_error);
    }

    // Ambil semua data KPI
    $sql_kpi = "SELECT id, bulan, bobot, skor FROM kpi_data ORDER BY bulan DESC";
    $result_kpi = $conn->query($sql_kpi);
    
    if ($result_kpi && $result_kpi->num_rows > 0) {
        while ($row = $result_kpi->fetch_assoc()) {
            $kpi_data[] = [
                'id' => $row['id'],
                'bulan' => htmlspecialchars($row['bulan']),
                'bobot' => number_format((float)$row['bobot'], 2) . '%',
                'bobot_numeric' => (float)$row['bobot'],
                'skor' => number_format((float)$row['skor'], 2),
                'skor_numeric' => (float)$row['skor']
            ];
        }
        
        // Data terbaru untuk ditampilkan di header
        if (!empty($kpi_data)) {
            $latest_kpi = $kpi_data[0];
        }
    }

    $conn->close();
} catch (Exception $e) {
    // Log error jika koneksi atau query gagal.
    error_log("KPI Overview error: " . $e->getMessage());
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
  <title>Ikhtisar KPI - PLN Dashboard</title>
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
      <a href="dashboard.php" class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
      <a href="kpi_overview.php" class="sidebar-link bg-white/20"><i class="fas fa-bullseye"></i> Ikhtisar KPI</a>
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
      <h1 class="font-bold text-pln-blue-500 dark:text-blue-400 text-lg md:absolute md:left-1/2 md:transform md:-translate-x-1/2">Ikhtisar Key Performance Indicator (KPI)</h1>
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
      <!-- Header -->
      <div class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-800 dark:to-blue-900 text-white p-6 rounded-2xl shadow-lg mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
          <div>
            <h2 class="text-2xl font-bold mb-2">Key Performance Indicator</h2>
            <p class="opacity-90">Performa unit <?= $unit; ?> berdasarkan indikator kinerja utama</p>
          </div>
          <div class="mt-4 md:mt-0 text-center md:text-right">
            <div class="text-3xl font-bold <?= getScoreColor($latest_kpi['skor_numeric']) ?>"><?= $latest_kpi['skor']; ?></div>
            <div class="text-sm opacity-90">Skor KPI Terkini (<?= $latest_kpi['bulan']; ?>)</div>
          </div>
        </div>
      </div>

      <!-- Ringkasan KPI -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="stat-card card p-5 border-l-green-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Skor KPI</p>
              <h3 class="text-2xl font-bold mt-1 <?= getScoreColor($latest_kpi['skor_numeric']) ?>"><?= $latest_kpi['skor']; ?></h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Bulan: <?= $latest_kpi['bulan']; ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/20 flex items-center justify-center">
              <i class="fas fa-bullseye text-green-600 dark:text-green-400 text-xl"></i>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex justify-between text-xs mb-1">
              <span>Progress</span>
              <span><?= $latest_kpi['skor_numeric'] ?>%</span>
            </div>
            <div class="progress-bar">
              <div class="progress-value <?= getProgressColor($latest_kpi['skor_numeric']) ?>" style="width: <?= $latest_kpi['skor_numeric'] ?>%"></div>
            </div>
          </div>
        </div>

        <div class="stat-card card p-5 border-l-blue-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Bobot KPI</p>
              <h3 class="text-2xl font-bold mt-1 text-gray-800 dark:text-white"><?= $latest_kpi['bobot']; ?></h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Kontribusi terhadap NKO</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/20 flex items-center justify-center">
              <i class="fas fa-weight text-blue-600 dark:text-blue-400 text-xl"></i>
            </div>
          </div>
        </div>

        <div class="stat-card card p-5 border-l-purple-500">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm text-gray-500 dark:text-gray-400">Status</p>
              <h3 class="text-2xl font-bold mt-1 <?= getScoreColor($latest_kpi['skor_numeric']) ?>">
                <?php 
                if ($latest_kpi['skor_numeric'] >= 80) echo "Baik";
                elseif ($latest_kpi['skor_numeric'] >= 60) echo "Cukup";
                else echo "Perlu Perbaikan";
                ?>
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Evaluasi Terakhir</p>
            </div>
            <div class="w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900/20 flex items-center justify-center">
              <i class="fas fa-chart-line text-purple-600 dark:text-purple-400 text-xl"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Grafik Tren KPI -->
      <div class="card p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Tren KPI 6 Bulan Terakhir</h3>
        <div class="h-64">
          <canvas id="kpiTrendChart"></canvas>
        </div>
      </div>

      <!-- Tabel Data KPI -->
      <div class="card p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-6">
          <h3 class="text-xl font-semibold text-gray-800 dark:text-white">Data Historis KPI</h3>
          <?php if ($role === 'admin'): ?>
            <a href="update_kpi.php" class="mt-2 md:mt-0 bg-pln-blue-500 text-white py-2 px-4 rounded-lg hover:bg-pln-blue-600 transition flex items-center gap-2">
              <i class="fas fa-plus"></i> Tambah Data KPI
            </a>
          <?php endif; ?>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead>
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bulan</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Skor</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Bobot</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                <?php if ($role === 'admin'): ?>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
              <?php if (!empty($kpi_data)): ?>
                <?php foreach ($kpi_data as $kpi): ?>
                  <tr>
                    <td class="px-4 py-3 text-sm"><?= $kpi['bulan'] ?></td>
                    <td class="px-4 py-3 text-sm">
                      <span class="<?= getScoreColor($kpi['skor_numeric']) ?> font-medium"><?= $kpi['skor'] ?></span>
                    </td>
                    <td class="px-4 py-3 text-sm"><?= $kpi['bobot'] ?></td>
                    <td class="px-4 py-3 text-sm">
                      <span class="<?= getScoreColor($kpi['skor_numeric']) ?>">
                        <?php 
                        if ($kpi['skor_numeric'] >= 80) echo "Baik";
                        elseif ($kpi['skor_numeric'] >= 60) echo "Cukup";
                        else echo "Perlu Perbaikan";
                        ?>
                      </span>
                    </td>
                    <?php if ($role === 'admin'): ?>
                    <td class="px-4 py-3 text-sm">
                      <a href="update_kpi.php?id=<?= $kpi['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mr-3">
                        <i class="fas fa-edit"></i>
                      </a>
                      <a href="delete_kpi.php?id=<?= $kpi['id'] ?>" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" onclick="return confirm('Hapus data KPI untuk bulan <?= $kpi['bulan'] ?>?')">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="<?= $role === 'admin' ? 5 : 4 ?>" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada data KPI</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
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

    // Initialize KPI trend chart
    const kpiTrendCtx = document.getElementById('kpiTrendChart').getContext('2d');
    new Chart(kpiTrendCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_column($kpi_data, 'bulan')) ?>,
        datasets: [
          {
            label: 'Skor KPI',
            data: <?= json_encode(array_column($kpi_data, 'skor_numeric')) ?>,
            borderColor: '#0A4C95',
            backgroundColor: 'rgba(10, 76, 149, 0.1)',
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
            min: 0,
            max: 100,
            grid: {
              color: 'rgba(0, 0, 0, 0.1)'
            },
            title: {
              display: true,
              text: 'Skor'
            }
          },
          x: {
            grid: {
              display: false
            },
            title: {
              display: true,
              text: 'Bulan'
            }
          }
        },
        plugins: {
          legend: {
            position: 'top',
          },
          title: {
            display: true,
            text: 'Perkembangan Skor KPI'
          }
        }
      }
    });
  </script>
</body>
</html>