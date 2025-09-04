<?php
session_start();

// Validasi session dan autentikasi
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Konfigurasi database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pln_dashboard');

$conn = null;
$result = null;
$message = '';
$chart_data = [];
$total_target = 0;
$total_realisasi = 0;
$total_pencapaian = 0;
$count_data = 0;
$avg_pencapaian = 0;

try {
    // Koneksi database dengan penanganan error yang lebih baik
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception("Koneksi database gagal: " . $conn->connect_error);
    }
    
    // Set karakter set untuk mencegah SQL injection
    $conn->set_charset("utf8mb4");

    // Ambil data unik untuk filter dropdown
    $units = [];
    $units_query = $conn->query("SELECT DISTINCT unit FROM peningkatan_keandalan_jaringan ORDER BY unit");
    if ($units_query) {
        $units = $units_query->fetch_all(MYSQLI_ASSOC);
    }
    
    $months = [
        'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
        'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
    ];

    // Filter GET dengan sanitasi
    $filter_unit = isset($_GET['unit']) ? $conn->real_escape_string($_GET['unit']) : '';
    $filter_bulan = isset($_GET['bulan']) ? $conn->real_escape_string($_GET['bulan']) : '';

    $where_clauses = [];
    $params = [];
    $param_types = '';

    if (!empty($filter_unit)) {
        $where_clauses[] = "unit = ?";
        $params[] = $filter_unit;
        $param_types .= 's';
    }
    
    if (!empty($filter_bulan)) {
        $where_clauses[] = "bulan = ?";
        $params[] = $filter_bulan;
        $param_types .= 's';
    }

    $sql_where = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : '';
    
    // Gunakan prepared statement untuk mencegah SQL injection
    $sql = "SELECT * FROM peningkatan_keandalan_jaringan $sql_where 
            ORDER BY unit, FIELD(bulan, 'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER')";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Persiapan query gagal: " . $conn->error);
    }
    
    if (!empty($param_types)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Eksekusi query gagal: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $data_rows = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data_rows[] = $row;
            $total_target += (float)$row['target'];
            $total_realisasi += (float)$row['realisasi'];
            
            // Bersihkan dan hitung persentase pencapaian
            $pencapaian = str_replace(['%', ','], ['', '.'], $row['persen_pencapaian']);
            $total_pencapaian += (float)$pencapaian;
            $count_data++;
        }
        
        $avg_pencapaian = $count_data > 0 ? $total_pencapaian / $count_data : 0;
        $message = "Data berhasil dimuat. " . count($data_rows) . " rekord ditemukan.";
    } else {
        $message = "Tidak ada data yang ditemukan untuk filter yang dipilih.";
    }

    // Data chart (dengan filter yang sama)
    $sql_chart = "SELECT bulan, SUM(target) AS total_target, SUM(realisasi) AS total_realisasi 
                  FROM peningkatan_keandalan_jaringan $sql_where 
                  GROUP BY bulan 
                  ORDER BY FIELD(bulan, 'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER')";
    
    $chart_stmt = $conn->prepare($sql_chart);
    if (!empty($param_types)) {
        $chart_stmt->bind_param($param_types, ...$params);
    }
    
    $chart_stmt->execute();
    $chart_result = $chart_stmt->get_result();
    $chart_data = $chart_result->fetch_all(MYSQLI_ASSOC);

    $chart_labels = [];
    $chart_target = [];
    $chart_realisasi = [];
    $chart_pencapaian = [];
    $aggregated_data = [];

    foreach ($chart_data as $row) {
        $chart_labels[] = $row['bulan'];
        $chart_target[] = (float)$row['total_target'];
        $chart_realisasi[] = (float)$row['total_realisasi'];
        
        // Hitung persentase pencapaian untuk chart
        $pencapaian = ($row['total_realisasi'] > 0 && $row['total_target'] > 0) 
            ? ($row['total_realisasi'] / $row['total_target']) * 100 
            : 0;
        $chart_pencapaian[] = $pencapaian;
    }

    $total_all_target = array_sum($chart_target);
    $total_all_realisasi = array_sum($chart_realisasi);
    $overall_pencapaian = ($total_all_target > 0) ? ($total_all_realisasi / $total_all_target) * 100 : 0;

} catch (Exception $e) {
    error_log("Lihat Keandalan Jaringan error: " . $e->getMessage());
    $message = "Terjadi kesalahan saat memuat data: " . $e->getMessage();
} finally {
    if ($conn) $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Peningkatan Keandalan Jaringan - PLN Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
@layer utilities {
  .scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }

  .scrollbar::-webkit-scrollbar-track {
    border-radius: 100vh;
    background: #f1f5f9;
  }

  .scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 100vh;
  }

  .scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }
}

body { 
  font-family: 'Poppins', sans-serif; 
  background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
  color: #1e293b;
}

.pln-blue-gradient { 
  background: linear-gradient(135deg, #0A4C95 0%, #073b76 100%);
  box-shadow: 0 4px 12px rgba(2, 44, 92, 0.2);
}

.card-shadow { 
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); 
  transition: all 0.3s ease;
}

.card-shadow:hover {
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
  transform: translateY(-3px);
}

.table-custom { 
  border-collapse: separate; 
  border-spacing: 0; 
}

.table-custom th, .table-custom td { 
  padding: 14px 20px; 
  border-bottom: 1px solid #e2e8f0;
}

.table-custom thead th { 
  background-color: #0A4C95;
  color: white;
  position: sticky; 
  top: 0; 
  z-index: 10;
}

.table-custom tbody tr:nth-child(even) {
  background-color: #f8fafc;
}

.table-custom tbody tr:hover {
  background-color: #f1f5f9;
}

.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}

.gradient-bg {
  background: linear-gradient(135deg, #ffffff 0%, #f0f7ff 100%);
  border-radius: 16px;
}

.progress-bar {
  height: 10px;
  border-radius: 5px;
  background-color: #e2e8f0;
  overflow: hidden;
}

.progress-value {
  height: 100%;
  border-radius: 5px;
  background: linear-gradient(90deg, #3b82f6, #60a5fa);
  transition: width 0.5s ease;
}

.kpi-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 500;
}

.kpi-success {
  background-color: #dcfce7;
  color: #16a34a;
}

.kpi-warning {
  background-color: #fef9c3;
  color: #ca8a04;
}

.kpi-danger {
  background-color: #fee2e2;
  color: #dc2626;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in {
  animation: fadeIn 0.5s ease-out forwards;
}
</style>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        'pln-blue': '#0A4C95',
        'pln-dark': '#073b76',
        'pln-light': '#3b82f6',
        'pln-accent': '#60a5fa'
      }
    }
  }
}
</script>
</head>
<body class="min-h-screen antialiased">

<header class="pln-blue-gradient text-white shadow-xl sticky top-0 z-50">
  <div class="container mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between">
    <div class="flex items-center gap-3 mb-3 md:mb-0">
      <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-lg">
        <i class="fas fa-bolt text-xl text-pln-blue"></i>
      </div>
      <div>
        <div class="text-xl font-bold tracking-tight">PLN Performance Dashboard</div>
        <div class="text-xs opacity-90">Peningkatan Keandalan Jaringan</div>
      </div>
    </div>
    
    <div class="flex items-center gap-3">
      <div class="hidden md:flex items-center text-sm bg-white/20 px-3 py-1 rounded-full">
        <i class="fas fa-user-circle mr-2"></i>
        <span><?= htmlspecialchars($_SESSION['user']['nama'] ?? 'Admin') ?></span>
      </div>
      <a href="dashboard.php" class="bg-white text-pln-blue font-medium py-1.5 px-5 rounded-full shadow hover:bg-gray-50 transition-all duration-300 flex items-center">
        <i class="fas fa-arrow-left mr-2 text-sm"></i>Kembali
      </a>
    </div>
  </div>
</header>

<main class="container mx-auto px-4 py-6">
  <div class="bg-white rounded-2xl shadow-lg overflow-hidden gradient-bg animate-fade-in">
    <div class="p-6 border-b border-gray-100">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <h1 class="text-2xl font-bold text-pln-dark">Data Peningkatan Keandalan Jaringan</h1>
          <p class="text-gray-600 mt-1">Monitoring kinerja unit dalam peningkatan keandalan jaringan</p>
        </div>
        
        <div class="flex items-center gap-2">
          <div class="relative group">
            <button class="bg-pln-blue text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-pln-dark transition-colors">
              <i class="fas fa-download"></i>
              <span>Export</span>
              <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="absolute right-0 mt-1 w-48 bg-white shadow-lg rounded-lg py-1 hidden group-hover:block z-20">
              <a href="#" onclick="exportToExcel()" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center gap-2">
                <i class="fas fa-file-excel text-green-600"></i> Excel
              </a>
              <a href="#" onclick="exportToPDF()" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center gap-2">
                <i class="fas fa-file-pdf text-red-600"></i> PDF
              </a>
              <a href="#" onclick="exportChart('barChart', 'png')" class="block px-4 py-2 text-gray-800 hover:bg-gray-100 flex items-center gap-2">
                <i class="fas fa-image text-blue-600"></i> Grafik (PNG)
              </a>
            </div>
          </div>
          <button onclick="window.print()" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-gray-200 transition-colors">
            <i class="fas fa-print"></i>
            <span class="hidden md:inline">Cetak</span>
          </button>
        </div>
      </div>
      
      <?php if (!empty($message)): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?= strpos($message, 'berhasil') !== false ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' ?>">
          <i class="fas <?= strpos($message, 'berhasil') !== false ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> mr-2"></i>
          <?= htmlspecialchars($message) ?>
        </div>
      <?php endif; ?>
      
      <div class="mb-6 bg-gray-50 p-4 rounded-xl border border-gray-200">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Filter Unit</label>
            <select name="unit" id="unit" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pln-blue focus:border-pln-blue transition-colors">
              <option value="">Semua Unit</option>
              <?php foreach ($units as $unit_row): ?>
                <option value="<?= htmlspecialchars($unit_row['unit']) ?>" <?= $filter_unit === $unit_row['unit'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($unit_row['unit']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div>
            <label for="bulan" class="block text-sm font-medium text-gray-700 mb-1">Filter Bulan</label>
            <select name="bulan" id="bulan" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-pln-blue focus:border-pln-blue transition-colors">
              <option value="">Semua Bulan</option>
              <?php foreach ($months as $month): ?>
                <option value="<?= htmlspecialchars($month) ?>" <?= $filter_bulan === $month ? 'selected' : '' ?>>
                  <?= ucfirst(strtolower($month)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="flex items-end gap-2">
            <button type="submit" class="w-full bg-pln-blue text-white py-2.5 px-4 rounded-lg hover:bg-pln-dark transition-colors flex items-center justify-center gap-2">
              <i class="fas fa-filter"></i> Terapkan Filter
            </button>
            <a href="lihat_keandalan_jaringan.php" class="w-full bg-gray-100 text-gray-700 py-2.5 px-4 rounded-lg hover:bg-gray-200 transition-colors flex items-center justify-center gap-2">
              <i class="fas fa-undo"></i> Reset
            </a>
          </div>
        </form>
      </div>
    </div>
    
    <div class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="bg-gradient-to-r from-pln-blue to-pln-dark text-white p-5 rounded-xl card-shadow">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium opacity-90">Total Target</p>
              <p class="text-3xl font-bold mt-1"><?= number_format($total_target, 2, ',', '.') ?></p>
              <div class="mt-3 flex items-center">
                <span class="text-xs bg-white/20 px-2 py-1 rounded">Semua Unit</span>
              </div>
            </div>
            <div class="bg-white/10 p-3 rounded-full">
              <i class="fas fa-bullseye text-2xl"></i>
            </div>
          </div>
        </div>
        
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white p-5 rounded-xl card-shadow">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium opacity-90">Total Realisasi</p>
              <p class="text-3xl font-bold mt-1"><?= number_format($total_realisasi, 2, ',', '.') ?></p>
              <div class="mt-3 flex items-center">
                <?php if ($total_target > 0): ?>
                  <?php 
                  $percentage = ($total_realisasi / $total_target) * 100;
                  $status = $percentage >= 100 ? 'success' : ($percentage >= 80 ? 'warning' : 'danger');
                  ?>
                  <span class="kpi-badge kpi-<?= $status ?>">
                    <i class="fas <?= $status === 'success' ? 'fa-check-circle' : ($status === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle') ?> mr-1"></i>
                    <?= number_format($percentage, 2) ?>%
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="bg-white/10 p-3 rounded-full">
              <i class="fas fa-check-circle text-2xl"></i>
            </div>
          </div>
        </div>
        
        <div class="bg-gradient-to-r from-green-600 to-green-800 text-white p-5 rounded-xl card-shadow">
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium opacity-90">Rata-rata Pencapaian</p>
              <p class="text-3xl font-bold mt-1"><?= number_format($avg_pencapaian, 2, ',', '.') ?>%</p>
              <div class="mt-3">
                <?php 
                $avg_status = $avg_pencapaian >= 100 ? 'success' : ($avg_pencapaian >= 80 ? 'warning' : 'danger');
                ?>
                <span class="kpi-badge kpi-<?= $avg_status ?>">
                  <i class="fas <?= $avg_status === 'success' ? 'fa-trophy' : ($avg_status === 'warning' ? 'fa-chart-line' : 'fa-exclamation-circle') ?> mr-1"></i>
                  <?= $avg_status === 'success' ? 'Sangat Baik' : ($avg_status === 'warning' ? 'Cukup Baik' : 'Perlu Perhatian') ?>
                </span>
              </div>
            </div>
            <div class="bg-white/10 p-3 rounded-full">
              <i class="fas fa-chart-line text-2xl"></i>
            </div>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-5 rounded-xl border border-gray-200 card-shadow">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-pln-dark">Realisasi & Target per Bulan</h3>
            <button onclick="exportChart('barChart', 'png')" class="text-pln-blue hover:text-pln-dark">
              <i class="fas fa-download"></i>
            </button>
          </div>
          <div class="chart-container">
            <canvas id="barChart"></canvas>
          </div>
        </div>
        
        <div class="bg-white p-5 rounded-xl border border-gray-200 card-shadow">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-pln-dark">Perbandingan Realisasi vs Target</h3>
            <button onclick="exportChart('pieChart', 'png')" class="text-pln-blue hover:text-pln-dark">
              <i class="fas fa-download"></i>
            </button>
          </div>
          <div class="chart-container">
            <canvas id="pieChart"></canvas>
          </div>
        </div>
        
        <div class="bg-white p-5 rounded-xl border border-gray-200 card-shadow lg:col-span-2">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-pln-dark">Tren Pencapaian Keandalan Jaringan</h3>
            <button onclick="exportChart('lineChart', 'png')" class="text-pln-blue hover:text-pln-dark">
              <i class="fas fa-download"></i>
            </button>
          </div>
          <div class="chart-container">
            <canvas id="lineChart"></canvas>
          </div>
        </div>
      </div>
      
      <div class="bg-white p-5 rounded-xl border border-gray-200 card-shadow">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-pln-dark">Detail Data</h3>
          <div class="flex gap-2">
            <button onclick="exportToExcel()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-1 text-sm">
              <i class="fas fa-file-excel"></i> Excel
            </button>
            <button onclick="exportToPDF()" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg flex items-center gap-1 text-sm">
              <i class="fas fa-file-pdf"></i> PDF
            </button>
          </div>
        </div>
        
        <div class="overflow-x-auto scrollbar">
          <table id="dataTable" class="w-full table-custom rounded-lg overflow-hidden">
            <thead>
              <tr>
                <th class="py-3 px-5 rounded-tl-lg">No</th>
                <th class="py-3 px-5">Unit</th>
                <th class="py-3 px-5">Bulan</th>
                <th class="py-3 px-5">Target</th>
                <th class="py-3 px-5">Realisasi</th>
                <th class="py-3 px-5">% Pencapaian</th>
                <th class="py-3 px-5 rounded-tr-lg">Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($data_rows)): ?>
                <?php foreach ($data_rows as $index => $row): ?>
                  <?php 
                  $pencapaian = str_replace(['%', ','], ['', '.'], $row['persen_pencapaian']);
                  $status = $pencapaian >= 100 ? 'success' : ($pencapaian >= 80 ? 'warning' : 'danger');
                  $status_text = $pencapaian >= 100 ? 'Tercapai' : ($pencapaian >= 80 ? 'Hampir' : 'Belum');
                  $status_color = $pencapaian >= 100 ? 'bg-green-100 text-green-800' : ($pencapaian >= 80 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                  ?>
                  <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                    <td class="py-3 px-5 text-center"><?= $index + 1 ?></td>
                    <td class="py-3 px-5 font-medium"><?= htmlspecialchars($row['unit']) ?></td>
                    <td class="py-3 px-5"><?= htmlspecialchars($row['bulan']) ?></td>
                    <td class="py-3 px-5"><?= number_format($row['target'], 2, ',', '.') ?></td>
                    <td class="py-3 px-5"><?= number_format($row['realisasi'], 2, ',', '.') ?></td>
                    <td class="py-3 px-5 font-semibold">
                      <div class="flex items-center gap-3">
                        <span><?= htmlspecialchars($row['persen_pencapaian']) ?></span>
                        <span class="kpi-badge kpi-<?= $status ?>"><?= $status_text ?></span>
                      </div>
                    </td>
                    <td class="py-3 px-5">
                      <div class="progress-bar">
                        <div class="progress-value" style="width: <?= min($pencapaian, 100) ?>%"></div>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" class="py-8 text-center text-gray-500">
                    <i class="fas fa-database text-3xl mb-3 text-gray-300"></i>
                    <p class="text-lg">Tidak ada data yang ditemukan</p>
                    <p class="mt-2 text-sm">Coba gunakan filter yang berbeda</p>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</main>

<footer class="mt-10 py-6 text-center text-gray-500 text-sm">
  <div class="flex flex-col items-center">
    <div class="flex items-center justify-center gap-2 mb-3">
      <i class="fas fa-bolt text-xl text-pln-blue"></i>
      <p class="text-lg font-medium text-pln-dark">PT PLN (Persero) - Sistem Manajemen Kinerja 2025</p>
    </div>
    <p>Hak Cipta Dilindungi | Laporan dibuat pada: <?= date('d F Y H:i') ?></p>
  </div>
</footer>

<script>
// Data untuk chart
const chartLabels = <?= json_encode($chart_labels) ?>;
const chartTarget = <?= json_encode($chart_target) ?>;
const chartRealisasi = <?= json_encode($chart_realisasi) ?>;
const chartPencapaian = <?= json_encode($chart_pencapaian) ?>;
const totalAllTarget = <?= json_encode($total_all_target) ?>;
const totalAllRealisasi = <?= json_encode($total_all_realisasi) ?>;
const overallPencapaian = <?= json_encode($overall_pencapaian) ?>;

// Inisialisasi chart setelah halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
  // Bar Chart
  if (chartLabels.length > 0) {
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Target',
          data: chartTarget,
          backgroundColor: 'rgba(10, 76, 149, 0.7)',
          borderColor: 'rgba(10, 76, 149, 1)',
          borderWidth: 1
        }, {
          label: 'Realisasi',
          data: chartRealisasi,
          backgroundColor: 'rgba(59, 130, 246, 0.7)',
          borderColor: 'rgba(59, 130, 246, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          tooltip: { mode: 'index', intersect: false }
        },
        scales: {
          y: { beginAtZero: true, title: { display: true, text: 'Nilai' } },
          x: { title: { display: true, text: 'Bulan' } }
        }
      }
    });
    
    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: ['Target', 'Realisasi'],
        datasets: [{
          data: [totalAllTarget, totalAllRealisasi],
          backgroundColor: ['rgba(10, 76, 149, 0.8)', 'rgba(59, 130, 246, 0.8)'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.raw.toLocaleString()}` } }
        },
        cutout: '65%'
      }
    });
    
    // Line Chart
    const lineCtx = document.getElementById('lineChart').getContext('2d');
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: chartLabels,
        datasets: [{
          label: 'Persentase Pencapaian',
          data: chartPencapaian,
          fill: false,
          borderColor: 'rgba(22, 163, 74, 1)',
          backgroundColor: 'rgba(22, 163, 74, 0.1)',
          tension: 0.3,
          pointRadius: 4,
          pointBackgroundColor: 'rgba(22, 163, 74, 1)'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          annotation: {
            annotations: {
              line1: {
                type: 'line',
                yMin: 100,
                yMax: 100,
                borderColor: 'rgba(220, 38, 38, 0.7)',
                borderWidth: 1,
                borderDash: [5, 5],
                label: {
                  content: 'Target 100%',
                  enabled: true,
                  position: 'end',
                  backgroundColor: 'rgba(220, 38, 38, 0.1)'
                }
              }
            }
          }
        },
        scales: {
          y: {
            min: 0,
            max: 120,
            ticks: { callback: value => value + '%' },
            title: { display: true, text: 'Persentase Pencapaian' }
          },
          x: { title: { display: true, text: 'Bulan' } }
        }
      }
    });
  } else {
    // Tampilkan pesan jika tidak ada data chart
    document.querySelectorAll('.chart-container').forEach(container => {
      container.innerHTML = '<div class="h-full flex flex-col items-center justify-center text-gray-400"><i class="fas fa-chart-pie text-4xl mb-2"></i><p>Data tidak tersedia untuk chart</p></div>';
    });
  }
  
  // Animasi progress bar
  document.querySelectorAll('.progress-value').forEach(bar => {
    const width = bar.style.width;
    bar.style.width = '0';
    setTimeout(() => {
      bar.style.width = width;
    }, 300);
  });
});

// Fungsi export
function exportToExcel() {
  const table = document.getElementById('dataTable');
  const wb = XLSX.utils.table_to_book(table, {sheet: "Data Keandalan Jaringan"});
  XLSX.writeFile(wb, 'Data_Keandalan_Jaringan_PLN.xlsx');
}

function exportToPDF() {
  alert('Fitur export PDF akan membuka halaman cetak. Silahkan gunakan opsi "Save as PDF" pada dialog cetak browser Anda.');
  setTimeout(() => window.print(), 500);
}

function exportChart(chartId, format = 'png') {
  const chartCanvas = document.getElementById(chartId);
  if (chartCanvas) {
    const link = document.createElement('a');
    link.href = chartCanvas.toDataURL(`image/${format}`);
    link.download = `chart_${chartId}_keandalan_jaringan_pln.${format}`;
    link.click();
  }
}
</script>

</body>
</html>