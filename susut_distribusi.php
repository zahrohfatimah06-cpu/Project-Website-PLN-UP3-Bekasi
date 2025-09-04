<?php
// ==================== SESSION & KONEKSI ====================
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// ==================== HELPERS ====================
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function hasColumn($conn, $table, $col) {
    $res = $conn->query("SHOW COLUMNS FROM `".$conn->real_escape_string($table)."` LIKE '".$conn->real_escape_string($col)."'");
    return $res && $res->num_rows > 0;
}

// ==================== INPUT FILTER ====================
$bulanList = ["JANUARI","FEBRUARI","MARET","APRIL","MEI","JUNI","JULI","AGUSTUS","SEPTEMBER","OKTOBER","NOVEMBER","DESEMBER"];

$selectedUnit  = $_GET['unit']  ?? '';
$selectedBulan = $_GET['bulan'] ?? '';
$selectedTahun = $_GET['tahun'] ?? date("Y");
$tahunIni  = intval($selectedTahun);
$tahunLalu = $tahunIni - 1;

$flash = "";

// ==================== HANDLE UPDATE / DELETE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ===== UPDATE (aman, auto-create kolom jika perlu) =====
    if ($action === 'update') {
        $id     = intval($_POST['id'] ?? 0);
        $tahun  = intval($_POST['tahun'] ?? 0);
        $rPrev  = strlen($_POST['realisasi_prev']??'') ? floatval($_POST['realisasi_prev']) : null;
        $tNow   = strlen($_POST['target_now']??'')      ? floatval($_POST['target_now'])      : null;
        $rNow   = strlen($_POST['realisasi_now']??'')   ? floatval($_POST['realisasi_now'])   : null;
        $p      = strlen($_POST['persentase_pencapaian']??'') ? floatval($_POST['persentase_pencapaian']) : null;

        if (!$id || !$tahun) {
            $flash = "Gagal: parameter update tidak lengkap.";
        } else {
            $colPrev = "realisasi_".($tahun-1);
            $colTgt  = "target_".$tahun;
            $colNow  = "realisasi_".$tahun;

            // pastikan kolom ada, jika tidak buat otomatis
            $alterNeeded = [];
            if (!hasColumn($conn, 'data_susut_distribusi', $colPrev)) {
                $alterNeeded[] = "ADD COLUMN `$colPrev` DOUBLE NULL";
            }
            if (!hasColumn($conn, 'data_susut_distribusi', $colTgt)) {
                $alterNeeded[] = "ADD COLUMN `$colTgt` DOUBLE NULL";
            }
            if (!hasColumn($conn, 'data_susut_distribusi', $colNow)) {
                $alterNeeded[] = "ADD COLUMN `$colNow` DOUBLE NULL";
            }
            if ($alterNeeded) {
                $sqlAlter = "ALTER TABLE `data_susut_distribusi` ".implode(",", $alterNeeded);
                $conn->query($sqlAlter);
            }

            // rakit query update dinamis (kolom sudah ada)
            $updateCols = [];
            $params = [];
            $types  = "";

            // kita tetap update semua kolom (kolom sudah dipastikan ada)
            $updateCols[] = "`$colPrev`=?";
            $params[] = $rPrev; $types .= "d";

            $updateCols[] = "`$colTgt`=?";
            $params[] = $tNow; $types .= "d";

            $updateCols[] = "`$colNow`=?";
            $params[] = $rNow; $types .= "d";

            $updateCols[] = "`persentase_pencapaian`=?";
            $params[] = $p; $types .= "d";

            $params[] = $id; $types .= "i";

            $sql = "UPDATE `data_susut_distribusi` SET ".implode(",", $updateCols)." WHERE id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $flash = "Gagal menyiapkan query: ".$conn->error;
            } else {
                $stmt->bind_param($types, ...$params);
                $flash = $stmt->execute() ? "Data berhasil diperbarui." : ("Gagal memperbarui data: ".$stmt->error);
                $stmt->close();
            }
        }
    }

    // ===== DELETE =====
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if (!$id) {
            $flash = "Gagal: parameter hapus tidak lengkap.";
        } else {
            $stmt = $conn->prepare("DELETE FROM `data_susut_distribusi` WHERE id=?");
            if (!$stmt) {
                $flash = "Gagal menyiapkan query: ".$conn->error;
            } else {
                $stmt->bind_param("i", $id);
                $flash = $stmt->execute() ? "Data berhasil dihapus." : ("Gagal menghapus data: ".$stmt->error);
                $stmt->close();
            }
        }
    }
}

// ==================== AMBIL UNIT UNTUK DROPDOWN ====================
$unitList = [];
$resUnit = $conn->query("SELECT DISTINCT unit FROM `data_susut_distribusi` ORDER BY unit");
if ($resUnit) {
    while ($rowU = $resUnit->fetch_assoc()) {
        $unitList[] = $rowU['unit'];
    }
}

$tahunList = range(2024, date("Y") + 5);

// ==================== QUERY DATA UNTUK TABEL/GRAFIK ====================
$data = [];
$dataExists = false;
$colPrev = "realisasi_".($tahunIni-1);
$colTgt  = "target_".$tahunIni;
$colNow  = "realisasi_".$tahunIni;

if ($selectedUnit && $selectedBulan && $selectedTahun) {
    $idx = array_search(strtoupper($selectedBulan), $bulanList);
    $bulanFilter = ($idx !== false) ? array_slice($bulanList, 0, $idx + 1) : [];

    if ($bulanFilter) {
        $placeholders = implode(',', array_fill(0, count($bulanFilter), '?'));
        $sql = "SELECT * FROM `data_susut_distribusi` WHERE `unit` = ? AND `bulan` IN ($placeholders) ORDER BY FIELD(bulan, $placeholders)";

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

        if (!$dataExists) {
            $stmt2 = $conn->prepare("SELECT * FROM `data_susut_distribusi` WHERE `unit` = ? AND `bulan` IN ($placeholders) ORDER BY FIELD(bulan, $placeholders)");
            if ($stmt2) {
                $params2 = array_merge([$selectedUnit], $bulanFilter, $bulanFilter);
                $types2 = str_repeat('s', count($params2));
                $stmt2->bind_param($types2, ...$params2);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                while ($r = $res2->fetch_assoc()) {
                    $data[] = $r;
                }
                $stmt2->close();
                $dataExists = count($data) > 0;
            }
        }

        if (!$dataExists) {
            $havePrevCol = hasColumn($conn, 'data_susut_distribusi', $colPrev);
            $prevValues = [];
            if ($havePrevCol) {
                $placeholdersB = implode(',', array_fill(0, count($bulanFilter), '?'));
                $sqlPrev = "SELECT `bulan`, `$colPrev` FROM `data_susut_distribusi` WHERE `unit` = ? AND `bulan` IN ($placeholdersB)";
                $stmt3 = $conn->prepare($sqlPrev);
                if ($stmt3) {
                    $params3 = array_merge([$selectedUnit], $bulanFilter);
                    $types3 = str_repeat('s', count($params3));
                    $stmt3->bind_param($types3, ...$params3);
                    $stmt3->execute();
                    $r3 = $stmt3->get_result();
                    while ($row3 = $r3->fetch_assoc()) {
                        $prevValues[strtoupper($row3['bulan'])] = $row3[$colPrev];
                    }
                    $stmt3->close();
                }
            }

            foreach ($bulanFilter as $b) {
                $valPrev = $prevValues[strtoupper($b)] ?? null;
                $data[] = [
                    'id' => '',
                    'unit' => $selectedUnit,
                    'bulan' => $b,
                    $colPrev => $valPrev,
                    $colTgt => null,
                    $colNow => null,
                    'persentase_pencapaian' => null
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Susut Distribusi</title>
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
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
  --card-shadow: 0 4px 12px rgba(0,0,0,.08);
  --card-radius: 12px;
}

body { 
  background: #f7f9fb; 
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  padding-bottom: 2rem;
}

.page-header {
  background: linear-gradient(135deg, var(--primary), #0a58ca);
  color: white;
  padding: 1.5rem;
  border-radius: var(--card-radius);
  margin-bottom: 1.5rem;
  box-shadow: var(--card-shadow);
}

.card { 
  border: none; 
  box-shadow: var(--card-shadow); 
  border-radius: var(--card-radius); 
  transition: transform 0.2s, box-shadow 0.2s;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 16px rgba(0,0,0,.1);
}

.card-header { 
  background: var(--primary); 
  color: #fff; 
  border-top-left-radius: var(--card-radius) !important; 
  border-top-right-radius: var(--card-radius) !important; 
  padding: 1rem 1.5rem;
  font-weight: 600;
  border: none;
}

.table thead th { 
  background: var(--primary); 
  color: #fff; 
  vertical-align: middle;
  font-weight: 500;
  border: none;
}

.table td {
  border-color: #f1f1f1;
  vertical-align: middle;
}

.badge-year { 
  background: rgba(255, 255, 255, 0.2); 
  color: #fff; 
  font-weight: 500;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
}

.btn-pill { 
  border-radius: 50px; 
  padding: 0.4rem 1rem;
  font-weight: 500;
  transition: all 0.2s;
  font-size: 0.875rem;
}

.btn-pill:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,.15);
}

.alert-warning { 
  background-color: #fff3cd; 
  border-color: #ffecb5; 
  color: #664d03; 
  border-radius: 10px;
}

.filter-card {
  background: white;
  border-radius: var(--card-radius);
  padding: 1.5rem;
  box-shadow: var(--card-shadow);
  margin-bottom: 1.5rem;
}

.filter-title {
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--primary);
  font-size: 1.1rem;
}

.stats-card {
  text-align: center;
  padding: 1.5rem;
}

.stats-value {
  font-size: 1.8rem;
  font-weight: 700;
  color: var(--primary);
}

.stats-label {
  font-size: 0.9rem;
  color: var(--secondary);
}

.action-buttons .btn {
  margin: 0 2px;
  font-size: 0.75rem;
  padding: 0.25rem 0.6rem;
}

.back-button {
  margin-bottom: 1.5rem;
}

.visualization-section {
  background: white;
  border-radius: var(--card-radius);
  padding: 1.5rem;
  box-shadow: var(--card-shadow);
  margin-bottom: 1.5rem;
}

.visualization-title {
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: var(--primary);
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #f1f1f1;
  font-size: 1.1rem;
}

.chart-container {
  position: relative;
  height: 220px;
  width: 100%;
}

/* Improved table styling */
.table-responsive {
  border-radius: var(--card-radius);
}

.table-hover tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05);
}

/* Modal improvements */
.modal-content {
  border-radius: var(--card-radius);
  border: none;
  box-shadow: 0 10px 30px rgba(0,0,0,.2);
}

.modal-header {
  background: var(--primary);
  color: white;
  border-top-left-radius: var(--card-radius) !important;
  border-top-right-radius: var(--card-radius) !important;
}

/* Form improvements */
.form-select, .form-control {
  border-radius: 8px;
  padding: 0.5rem 1rem;
}

.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #495057;
}

/* Badge improvements */
.badge {
  font-weight: 500;
  padding: 0.4em 0.6em;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .card-header {
    padding: 0.75rem 1rem;
  }
  
  .page-header {
    padding: 1rem;
  }
  
  .filter-card {
    padding: 1rem;
  }
  
  .btn-pill {
    padding: 0.35rem 0.8rem;
    font-size: 0.8rem;
  }
  
  .chart-container {
    height: 200px;
  }
}

/* Animation for alerts */
.alert {
  animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Custom scrollbar for table */
.table-responsive::-webkit-scrollbar {
  height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 10px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style>
</head>
<body>

<div class="container py-4">
  <!-- Tombol Kembali -->
  <div class="back-button">
    <a href="lihat_kpi.php" class="btn btn-outline-primary btn-pill">
      <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard KPI
    </a>
  </div>

  <!-- Header Halaman -->
  <div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <h1 class="h4 mb-0"><i class="fas fa-bolt me-2"></i> Dashboard Susut Distribusi</h1>
        <p class="mb-0 mt-1 opacity-75">Pantau dan kelala data susut distribusi dengan mudah</p>
      </div>
      <a href="tambah_data.php" class="btn btn-light btn-pill mt-2 mt-md-0">
        <i class="fas fa-plus me-2"></i> Tambah Data
      </a>
    </div>
  </div>

  <?php if($flash): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <i class="fas fa-info-circle me-2"></i> <?=e($flash)?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <!-- Card Filter -->
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
          <?php foreach($tahunList as $t): ?>
            <option value="<?=$t?>" <?=($t==$selectedTahun?'selected':'')?>><?=$t?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100 btn-pill">
          <i class="fas fa-eye me-2"></i> Tampilkan
        </button>
      </div>
    </form>
  </div>

  <?php if ($selectedUnit && $selectedBulan && $selectedTahun): ?>
    <!-- Card Tabel Data -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
        <div class="d-flex align-items-center">
          <i class="fas fa-building me-2"></i> <strong><?=e($selectedUnit)?></strong>
          <span class="badge badge-year ms-2">s.d <?=e($selectedBulan)?> <?=$tahunIni?></span>
        </div>
        <div class="mt-2 mt-md-0">
          <button class="btn btn-sm btn-outline-light">
            <i class="fas fa-download me-1"></i> Ekspor
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle text-center mb-0">
            <thead>
              <tr>
                <th>No</th>
                <th>Unit</th>
                <th>Bulan</th>
                <th>Realisasi <?=$tahunLalu?></th>
                <th>Target <?=$tahunIni?></th>
                <th>Realisasi <?=$tahunIni?></th>
                <th>Pencapaian</th>
                <th style="width:130px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php $no=1; foreach($data as $row): ?>
              <tr>
                <td class="fw-semibold"><?=$no++?></td>
                <td><?=e($row['unit'] ?? $selectedUnit)?></td>
                <td><span class="badge bg-light text-dark"><?=e($row['bulan'])?></span></td>
                <td class="fw-semibold"><?= isset($row[$colPrev]) && $row[$colPrev] !== null ? e($row[$colPrev]) : '-' ?></td>
                <td class="fw-semibold text-success"><?= isset($row[$colTgt]) && $row[$colTgt] !== null ? e($row[$colTgt]) : '-' ?></td>
                <td class="fw-semibold text-primary"><?= isset($row[$colNow]) && $row[$colNow] !== null ? e($row[$colNow]) : '-' ?></td>
                <td>
                  <?php if(isset($row['persentase_pencapaian']) && $row['persentase_pencapaian'] !== null): ?>
                    <span class="badge bg-<?= $row['persentase_pencapaian'] >= 100 ? 'success' : ($row['persentase_pencapaian'] >= 80 ? 'warning' : 'danger') ?>">
                      <?=e($row['persentase_pencapaian'])?>%
                    </span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="action-buttons">
                  <?php if(!empty($row['id'])): ?>
                    <button class="btn btn-sm btn-warning btn-pill"
                      data-bs-toggle="modal" data-bs-target="#editModal"
                      data-id="<?=$row['id']?>"
                      data-unit="<?=e($row['unit'])?>"
                      data-bulan="<?=e($row['bulan'])?>"
                      data-rprev="<?=isset($row[$colPrev])?e($row[$colPrev]):''?>"
                      data-tnow="<?=isset($row[$colTgt])?e($row[$colTgt]):''?>"
                      data-rnow="<?=isset($row[$colNow])?e($row[$colNow]):''?>"
                      data-p="<?=isset($row['persentase_pencapaian'])?e($row['persentase_pencapaian']):''?>"
                      data-tahun="<?=$tahunIni?>">
                      <i class="fas fa-edit me-1"></i> Edit
                    </button>
                    <button class="btn btn-sm btn-danger btn-pill"
                      data-bs-toggle="modal" data-bs-target="#delModal"
                      data-id="<?=$row['id']?>"
                      data-unit="<?=e($row['unit'])?>"
                      data-bulan="<?=e($row['bulan'])?>">
                      <i class="fas fa-trash me-1"></i> Hapus
                    </button>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Section Visualisasi Data -->
    <div class="visualization-section">
      <h5 class="visualization-title"><i class="fas fa-chart-line me-2"></i> Visualisasi Data</h5>
      
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">Tren Susut Distribusi</div>
            <div class="card-body p-3">
              <div class="chart-container">
                <canvas id="lineChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">Perbandingan Tahun <?=$tahunLalu?> vs <?=$tahunIni?></div>
            <div class="card-body p-3">
              <div class="chart-container">
                <canvas id="barChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- Placeholder ketika belum memilih filter -->
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">Silakan pilih Unit, Bulan, dan Tahun untuk melihat data</h5>
        <p class="text-muted">Gunakan form filter di atas untuk memilih parameter yang diinginkan</p>
      </div>
    </div>
  <?php endif; ?>

</div>

<!-- MODAL EDIT -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <input type="hidden" name="tahun" id="edit-tahun">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body row g-3">
        <div class="col-md-6">
          <label class="form-label">Unit</label>
          <input id="edit-unit" class="form-control" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label">Bulan</label>
          <input id="edit-bulan" class="form-control" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label">Realisasi <span id="label-rprev"></span></label>
          <input name="realisasi_prev" id="edit-rprev" type="number" step="0.01" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Target <span id="label-tnow"></span></label>
          <input name="target_now" id="edit-tnow" type="number" step="0.01" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Realisasi <span id="label-rnow"></span></label>
          <input name="realisasi_now" id="edit-rnow" type="number" step="0.01" class="form-control">
        </div>
        <div class="col-md-6">
          <label class="form-label">Pencapaian (%)</label>
          <input name="persentase_pencapaian" id="edit-p" type="number" step="0.01" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary btn-pill" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL DELETE -->
<div class="modal fade" id="delModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="del-id">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-trash me-2"></i> Hapus Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Yakin ingin menghapus data untuk <b id="del-unit"></b> - <b id="del-bulan"></b>?</p>
        <p class="text-muted">Data yang dihapus tidak dapat dikembalikan.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger btn-pill" type="submit">Ya, Hapus Data</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ============ CHARTS ============
<?php
// Prepare arrays for charts even jika data kosong
if ($selectedUnit && $selectedBulan && $selectedTahun) {
    $labels = array_column($data, 'bulan');
    $rPrevArr = [];
    $tNowArr  = [];
    $rNowArr  = [];
    foreach ($data as $row) {
        $rPrevArr[] = isset($row[$colPrev]) && $row[$colPrev] !== null ? floatval($row[$colPrev]) : null;
        $tNowArr[]  = isset($row[$colTgt])  && $row[$colTgt]  !== null ? floatval($row[$colTgt])  : null;
        $rNowArr[]  = isset($row[$colNow])  && $row[$colNow]  !== null ? floatval($row[$colNow])  : null;
    }
    // json encode
    $js_labels = json_encode($labels);
    $js_rPrev  = json_encode($rPrevArr);
    $js_tNow   = json_encode($tNowArr);
    $js_rNow   = json_encode($rNowArr);
    echo "const labels = {$js_labels};\n";
    echo "const rPrev = {$js_rPrev};\n";
    echo "const tNow = {$js_tNow};\n";
    echo "const rNow = {$js_rNow};\n";
}
?>

if (typeof labels !== 'undefined' && labels.length) {
  // DEBUG log ke console
  console.log("labels", labels);
  console.log("rPrev", rPrev);
  console.log("tNow", tNow);
  console.log("rNow", rNow);

  // LINE CHART
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels,
      datasets: [
        { 
          label: 'Realisasi <?=$tahunLalu?>', 
          data: rPrev, 
          borderColor: '#007bff', 
          backgroundColor: 'rgba(0,123,255,.15)',
          tension: 0.4, 
          fill: true,
          spanGaps: true, // ✅ biar garis lanjut meski ada null
          pointRadius: 5,
          pointHoverRadius: 7,
        },
        { 
          label: 'Target <?=$tahunIni?>',      
          data: tNow,   
          borderColor: '#ffc107', 
          backgroundColor: 'rgba(255,193,7,.15)',
          tension: 0.4, 
          fill: false,
          spanGaps: true, // ✅
          borderDash: [5, 5],
          pointRadius: 0
        },
        { 
          label: 'Realisasi <?=$tahunIni?>',   
          data: rNow,   
          borderColor: '#28a745', 
          backgroundColor: 'rgba(40,167,69,.15)',
          tension: 0.4, 
          fill: true,
          spanGaps: true, // ✅
          pointRadius: 5,
          pointHoverRadius: 7,
        }
      ]
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: { 
          mode: 'index', 
          intersect: false,
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          padding: 10,
          cornerRadius: 8
        }
      },
      interaction: { mode: 'nearest', axis: 'x', intersect: false },
      scales: {
        y: { 
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          title: {
            display: true,
            text: 'Persentase (%)'
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
      layout: {
        padding: {
          top: 5,
          bottom: 5
        }
      }
    }
  });

  // BAR CHART
  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: { 
      labels, 
      datasets: [
        { 
          label: 'Realisasi <?=$tahunLalu?>', 
          data: rPrev, 
          backgroundColor: '#007bff', 
          borderRadius: 6,
          barPercentage: 0.6,
          categoryPercentage: 0.7
        },
        { 
          label: 'Target <?=$tahunIni?>',      
          data: tNow,  
          backgroundColor: '#ffc107', 
          borderRadius: 6,
          barPercentage: 0.6,
          categoryPercentage: 0.7
        },
        { 
          label: 'Realisasi <?=$tahunIni?>',   
          data: rNow,  
          backgroundColor: '#28a745', 
          borderRadius: 6,
          barPercentage: 0.6,
          categoryPercentage: 0.7
        }
      ]
    },
    options: { 
      responsive: true, 
      maintainAspectRatio: false,
      plugins: {
        legend: { 
          position: 'bottom',
          labels: {
            padding: 15,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: { 
          mode: 'index', 
          intersect: false,
          backgroundColor: 'rgba(0, 0, 0, 0.7)',
          padding: 10,
          cornerRadius: 8
        }
      },
      scales: {
        y: { 
          beginAtZero: true,
          grid: {
            color: 'rgba(0, 0, 0, 0.05)'
          },
          title: {
            display: true,
            text: 'Persentase (%)'
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
      layout: {
        padding: {
          top: 5,
          bottom: 5
        }
      }
    }
  });
}

// ============ MODAL FILLERS ============
const editModal = document.getElementById('editModal');
if (editModal) {
  editModal.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('edit-id').value    = b.getAttribute('data-id') ?? '';
    document.getElementById('edit-tahun').value = b.getAttribute('data-tahun') ?? '<?=date("Y")?>';
    document.getElementById('edit-unit').value  = b.getAttribute('data-unit') ?? '';
    document.getElementById('edit-bulan').value = b.getAttribute('data-bulan') ?? '';
    document.getElementById('edit-rprev').value = b.getAttribute('data-rprev') ?? '';
    document.getElementById('edit-tnow').value  = b.getAttribute('data-tnow') ?? '';
    document.getElementById('edit-rnow').value  = b.getAttribute('data-rnow') ?? '';
    document.getElementById('edit-p').value     = b.getAttribute('data-p') ?? '';
    const th = parseInt(b.getAttribute('data-tahun'),10) || (new Date()).getFullYear();
    document.getElementById('label-rprev').textContent = th-1;
    document.getElementById('label-tnow').textContent  = th;
    document.getElementById('label-rnow').textContent  = th;
  });
}

const delModal = document.getElementById('delModal');
if (delModal) {
  delModal.addEventListener('show.bs.modal', e => {
    const b = e.relatedTarget;
    document.getElementById('del-id').value    = b.getAttribute('data-id') ?? '';
    document.getElementById('del-unit').textContent  = b.getAttribute('data-unit') ?? '';
    document.getElementById('del-bulan').textContent = b.getAttribute('data-bulan') ?? '';
  });
}
</script>
</body>
</html>