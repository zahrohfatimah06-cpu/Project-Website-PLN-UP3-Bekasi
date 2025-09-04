<?php
// ==================== SESSION & KONEKSI ====================
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'pln_dashboard';

// Membuat koneksi
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// ==================== HELPERS ====================
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// ==================== INPUT FILTER ====================
$bulanList = ["JANUARI","FEBRUARI","MARET","APRIL","MEI","JUNI","JULI","AGUSTUS","SEPTEMBER","OKTOBER","NOVEMBER","DESEMBER"];

$selectedUnit  = $_GET['unit']  ?? '';
$selectedBulan = $_GET['bulan'] ?? '';
$selectedTahun = $_GET['tahun'] ?? '2025';

$flash = "";

// ==================== AMBIL UNIT UNTUK DROPDOWN ====================
$unitList = [];
$resUnit = $conn->query("SELECT DISTINCT unit FROM data_daya ORDER BY unit");
if ($resUnit) {
    while ($rowU = $resUnit->fetch_assoc()) {
        $unitList[] = $rowU['unit'];
    }
}

// ==================== QUERY DATA UNTUK TABEL/GRAFIK ====================
$data = [];
$dataExists = false;

if ($selectedUnit && $selectedBulan && $selectedTahun) {
    $idx = array_search(strtoupper($selectedBulan), $bulanList);
    $bulanFilter = ($idx !== false) ? array_slice($bulanList, 0, $idx + 1) : [];
    
    if ($bulanFilter) {
        $placeholders = implode(',', array_fill(0, count($bulanFilter), '?'));
        $sql = "SELECT * FROM data_daya WHERE unit = ? AND bulan IN ($placeholders) ORDER BY FIELD(bulan, $placeholders)";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $params = array_merge([$selectedUnit], $bulanFilter, $bulanFilter);
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
            $dataExists = count($data) > 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Tambah Daya PLN</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<style>
/* ========== STYLE SAMA SEPERTI SEBELUMNYA ========== */
:root {
  --primary: #0d6efd;
  --secondary: #6c757d;
  --success: #198754;
  --info: #0dcaf0;
  --warning: #ffc107;
  --danger: #dc3545;
  --light: #f8f9fa;
  --dark: #212529;
}
body { background: #f7f9fb; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.page-header { background: linear-gradient(135deg, #f4b41a, #143d82); color: white; padding: 1.5rem; border-radius: 14px; margin-bottom: 1.5rem; box-shadow: 0 6px 18px rgba(0,0,0,.08); }
.card { border: none; box-shadow: 0 6px 18px rgba(0,0,0,.06); border-radius: 14px; transition: transform 0.2s, box-shadow 0.2s; margin-bottom: 1.5rem; }
.card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,.1); }
.card-header { background: #143d82; color: #fff; border-top-left-radius: 14px !important; border-top-right-radius: 14px !important; padding: 1rem 1.5rem; font-weight: 600; }
.table thead th { background: #143d82; color: #fff; vertical-align: middle; font-weight: 500; }
.badge-year { background: #eef5ff; color: var(--primary); font-weight: 500; padding: 0.5rem 0.8rem; border-radius: 8px; }
.btn-pill { border-radius: 50px; padding: 0.5rem 1.2rem; font-weight: 500; transition: all 0.2s; }
.btn-pill:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,.15); }
.alert-warning { background-color: #fff3cd; border-color: #ffecb5; color: #664d03; border-radius: 12px; }
.filter-card { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 6px 18px rgba(0,0,0,.06); margin-bottom: 1.5rem; }
.filter-title { font-weight: 600; margin-bottom: 1rem; color: #143d82; }
.stats-card { text-align: center; padding: 1.5rem; }
.stats-value { font-size: 1.8rem; font-weight: 700; color: #143d82; }
.stats-label { font-size: 0.9rem; color: var(--secondary); }
.action-buttons .btn { margin: 0 3px; }
.back-button { margin-bottom: 1.5rem; }
.visualization-section { background: white; border-radius: 14px; padding: 1.5rem; box-shadow: 0 6px 18px rgba(0,0,0,.06); margin-bottom: 1.5rem; }
.visualization-title { font-weight: 600; margin-bottom: 1rem; color: #143d82; border-bottom: 2px solid #143d82; padding-bottom: 0.5rem; }
.negative { color: red; font-weight: bold; }
</style>
</head>
<body>

<div class="container py-4">
  <!-- Tombol Kembali -->
  <div class="back-button">
    <a href="lihat_pi.php" class="btn btn-outline-primary btn-pill">
      <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard PI
    </a>
  </div>

  <!-- Header Halaman -->
  <div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h3 mb-0"><i class="fas fa-bolt me-2"></i> Dashboard Tambah Daya PLN</h1>
        <p class="mb-0 mt-1 opacity-75">Pantau dan kelola data tambah daya dengan mudah</p>
      </div>
      <a href="tambah_data.php" class="btn btn-light btn-pill">
        <i class="fas fa-plus me-2"></i> Tambah Data
      </a>
    </div>
  </div>

  <!-- Filter -->
  <div class="filter-card">
    <h5 class="filter-title"><i class="fas fa-filter me-2"></i> Filter Data</h5>
    <form method="get" class="row g-3">
      <div class="col-md-4">
        <label class="form-label fw-semibold">Unit</label>
        <select name="unit" class="form-select" required>
          <option value="">-- Pilih Unit --</option>
          <?php foreach($unitList as $u): ?>
            <option value="<?=e($u)?>" <?=($u===$selectedUnit?'selected':'')?>><?=e($u)?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">Bulan s.d</label>
        <select name="bulan" class="form-select" required>
          <option value="">-- Pilih Bulan --</option>
          <?php foreach($bulanList as $b): ?>
            <option value="<?=e($b)?>" <?=($b===$selectedBulan?'selected':'')?>><?=e($b)?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label fw-semibold">Tahun</label>
        <select name="tahun" class="form-select" required>
          <option value="2025" <?=('2025'==$selectedTahun?'selected':'')?>>2025</option>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100 btn-pill" style="background-color: #143d82; border-color: #143d82;">
          <i class="fas fa-eye me-2"></i> Tampilkan
        </button>
      </div>
    </form>
  </div>

  <?php if ($selectedUnit && $selectedBulan && $selectedTahun): ?>
    <!-- Tabel -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <i class="fas fa-building me-2"></i> <strong><?=e($selectedUnit)?></strong>
          <span class="badge badge-year ms-2">s.d <?=e($selectedBulan)?> <?=$selectedTahun?></span>
        </div>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-hover align-middle text-center">
          <thead>
            <tr>
              <th>No</th><th>Unit</th><th>Bulan</th>
              <th>Realisasi 2024</th><th>Target 2025</th><th>Realisasi 2025</th><th>Pencapaian</th>
            </tr>
          </thead>
          <tbody>
            <?php if($dataExists): $no=1; foreach($data as $row): ?>
            <tr>
              <td><?=$no++?></td>
              <td><?=e($row['unit'])?></td>
              <td><span class="badge bg-light text-dark"><?=e($row['bulan'])?></span></td>
              <td><?= number_format($row['realisasi_2024'], 2) ?></td>
              <td class="text-success"><?= number_format($row['target_2025'], 2) ?></td>
              <td class="text-primary"><?= number_format($row['realisasi_2025'], 2) ?></td>
              <td>
                <?php if($row['persen_pencapaian'] < 0): ?>
                  <span class="negative"><?= number_format($row['persen_pencapaian'], 2) ?>%</span>
                <?php else: ?>
                  <span class="badge bg-<?= $row['persen_pencapaian'] >= 100 ? 'success' : ($row['persen_pencapaian'] >= 80 ? 'warning' : 'danger') ?>">
                    <?= number_format($row['persen_pencapaian'], 2) ?>%
                  </span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-center py-4">Tidak ada data</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Visualisasi -->
    <div class="visualization-section">
      <h5 class="visualization-title"><i class="fas fa-chart-line me-2"></i> Visualisasi Data</h5>
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">Tren Tambah Daya</div>
            <div class="card-body">
              <div style="height:300px;"><canvas id="lineChart"></canvas></div>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">Perbandingan Tahun 2024 vs 2025</div>
            <div class="card-body">
              <div style="height:300px;"><canvas id="barChart"></canvas></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php
if ($selectedUnit && $selectedBulan && $selectedTahun && $dataExists) {
    $labels = array_column($data, 'bulan');
    $realisasi2024 = array_column($data, 'realisasi_2024');
    $target2025 = array_column($data, 'target_2025');
    $realisasi2025 = array_column($data, 'realisasi_2025');
    echo "const labels = " . json_encode($labels) . ";\n";
    echo "const realisasi2024 = " . json_encode($realisasi2024) . ";\n";
    echo "const target2025 = " . json_encode($target2025) . ";\n";
    echo "const realisasi2025 = " . json_encode($realisasi2025) . ";\n";
}
?>

if (typeof labels !== 'undefined') {
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: { labels, datasets:[
      { label:'Realisasi 2024', data:realisasi2024, borderColor:'#0d6efd', backgroundColor:'rgba(13,110,253,.2)', fill:true, tension:.3 },
      { label:'Target 2025', data:target2025, borderColor:'#198754', backgroundColor:'rgba(25,135,84,.2)', fill:true, tension:.3 },
      { label:'Realisasi 2025', data:realisasi2025, borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,.2)', fill:true, tension:.3 }
    ]},
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}} }
  });

  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: { labels, datasets:[
      { label:'Realisasi 2024', data:realisasi2024, backgroundColor:'rgba(13,110,253,.7)' },
      { label:'Target 2025', data:target2025, backgroundColor:'rgba(25,135,84,.7)' },
      { label:'Realisasi 2025', data:realisasi2025, backgroundColor:'rgba(220,53,69,.7)' }
    ]},
    options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}} }
  });
}
</script>
</body>
</html>

<?php $conn->close(); ?>
