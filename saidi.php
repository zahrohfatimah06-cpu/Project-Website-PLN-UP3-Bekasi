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
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
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

    // ===== UPDATE =====
    if ($action === 'update') {
        $id     = intval($_POST['id'] ?? 0);
        $tahun  = intval($_POST['tahun'] ?? 0);
        $rPrev  = strlen($_POST['realisasi_prev']??'') ? floatval(str_replace(',', '.', $_POST['realisasi_prev'])) : null;
        $tNow   = strlen($_POST['target_now']??'')      ? floatval(str_replace(',', '.', $_POST['target_now']))      : null;
        $rNow   = strlen($_POST['realisasi_now']??'')   ? floatval(str_replace(',', '.', $_POST['realisasi_now']))   : null;
        $p      = strlen($_POST['persen_pencapaian']??'') ? floatval(str_replace(',', '.', $_POST['persen_pencapaian'])) : null;

        if (!$id || !$tahun) {
            $flash = "Gagal: parameter update tidak lengkap.";
        } else {
            $colPrev = "realisasi_".$tahunLalu;
            $colTgt  = "target_".$tahunIni;
            $colNow  = "realisasi_".$tahunIni;

            // Pastikan kolom ada, jika tidak, buat otomatis
            $alterNeeded = [];
            if (!hasColumn($conn, 'data_saidi', $colPrev)) {
                $alterNeeded[] = "ADD COLUMN `$colPrev` DOUBLE NULL";
            }
            if (!hasColumn($conn, 'data_saidi', $colTgt)) {
                $alterNeeded[] = "ADD COLUMN `$colTgt` DOUBLE NULL";
            }
            if (!hasColumn($conn, 'data_saidi', $colNow)) {
                $alterNeeded[] = "ADD COLUMN `$colNow` DOUBLE NULL";
            }
            if ($alterNeeded) {
                $sqlAlter = "ALTER TABLE `data_saidi` ".implode(",", $alterNeeded);
                $conn->query($sqlAlter);
            }

            // Rakit query update dinamis
            $sql = "UPDATE `data_saidi` SET `$colPrev`=?, `$colTgt`=?, `$colNow`=?, `persen_pencapaian`=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $flash = "Gagal menyiapkan query: ".$conn->error;
            } else {
                $stmt->bind_param("ddddi", $rPrev, $tNow, $rNow, $p, $id);
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
            $stmt = $conn->prepare("DELETE FROM `data_saidi` WHERE id=?");
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

// ==================== AMBIL UNIT & TAHUN UNTUK DROPDOWN ====================
$unitList = [];
$resUnit = $conn->query("SELECT DISTINCT unit FROM `data_saidi` ORDER BY unit");
if ($resUnit) {
    while ($rowU = $resUnit->fetch_assoc()) {
        $unitList[] = $rowU['unit'];
    }
}
$tahunList = range(2024, date("Y") + 5);

// ==================== QUERY DATA UNTUK TABEL/GRAFIK ====================
$data = [];
$labels_chart = [];
$data_realisasi_prev = [];
$data_target_now = [];
$data_realisasi_now = [];
$data_pencapaian = [];

$total_pencapaian = 0;
$total_rows = 0;

$colPrev = "realisasi_".$tahunLalu;
$colTgt  = "target_".$tahunIni;
$colNow  = "realisasi_".$tahunIni;

$isFiltered = !empty($selectedUnit) && !empty($selectedBulan) && !empty($selectedTahun);

if ($isFiltered) {
    $bulanFilter = array_slice($bulanList, 0, array_search(strtoupper($selectedBulan), $bulanList) + 1);
    $placeholders = implode(',', array_fill(0, count($bulanFilter), '?'));
    $sql = "SELECT * FROM `data_saidi` WHERE `unit` = ? AND `bulan` IN ($placeholders) ORDER BY FIELD(bulan, $placeholders)";
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
    }
}

if (!empty($data)) {
    foreach ($data as $row) {
        $labels_chart[]          = $row['bulan'];
        $data_realisasi_prev[]   = $row[$colPrev] ?? 0;
        $data_target_now[]       = $row[$colTgt]  ?? 0;
        $data_realisasi_now[]    = $row[$colNow]  ?? 0;
        $data_pencapaian[]       = $row['persen_pencapaian'] ?? 0;

        if (!is_null($row['persen_pencapaian'])) {
            $total_pencapaian += $row['persen_pencapaian'];
            $total_rows++;
        }
    }
}
$avg_pencapaian = ($total_rows > 0) ? $total_pencapaian / $total_rows : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard SAIDI</title>
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
.alert-info {
  background-color: #d1ecf1;
  border-color: #bee5eb;
  color: #0c5460;
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
.table-responsive {
  border-radius: var(--card-radius);
}
.table-hover tbody tr:hover {
  background-color: rgba(13, 110, 253, 0.05);
}
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
.form-select, .form-control {
  border-radius: 8px;
  padding: 0.5rem 1rem;
}
.form-label {
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #495057;
}
.badge {
  font-weight: 500;
  padding: 0.4em 0.6em;
}
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
.alert {
  animation: fadeIn 0.5s ease-in;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
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
  <div class="back-button">
    <a href="lihat_kpi.php" class="btn btn-outline-primary btn-pill">
      <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard KPI
    </a>
  </div>

  <div class="page-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
      <div>
        <h1 class="h4 mb-0"><i class="fas fa-bolt me-2"></i> Dashboard SAIDI</h1>
        <p class="mb-0 mt-1 opacity-75">Pantau dan kelola data SAIDI dengan mudah</p>
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

  <?php if ($isFiltered): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card p-3 text-center h-100">
                <h6 class="text-muted mb-1">Unit Dipilih</h6>
                <h5 class="stats-value"><?= e($selectedUnit) ?></h5>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 text-center h-100">
                <h6 class="text-muted mb-1">Rata-rata Pencapaian</h6>
                <h5 class="stats-value"><?= number_format($avg_pencapaian, 2) ?>%</h5>
            </div>
        </div>
    </div>
    
    <div class="visualization-section">
      <h5 class="visualization-title"><i class="fas fa-chart-line me-2"></i> Visualisasi Data</h5>
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">Grafik SAIDI Tahun <?= $tahunIni ?></div>
            <div class="card-body p-3">
              <div class="chart-container">
                <canvas id="saidiLineChart"></canvas>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-6 mb-4">
          <div class="card h-100">
            <div class="card-header">Perbandingan Realisasi <?= $tahunLalu ?> vs <?= $tahunIni ?></div>
            <div class="card-body p-3">
              <div class="chart-container">
                <canvas id="saidiBarChart"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

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
                <th>% Pencapaian</th>
                <th style="width:130px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($data)): ?>
                <?php $no=1; foreach($data as $row): ?>
                  <tr>
                    <td class="fw-semibold"><?=$no++?></td>
                    <td><?=e($row['unit'] ?? $selectedUnit)?></td>
                    <td><span class="badge bg-light text-dark"><?=e($row['bulan'])?></span></td>
                    <td class="fw-semibold"><?= isset($row[$colPrev]) && $row[$colPrev] !== null ? number_format($row[$colPrev], 2) : '-' ?></td>
                    <td class="fw-semibold text-success"><?= isset($row[$colTgt]) && $row[$colTgt] !== null ? number_format($row[$colTgt], 2) : '-' ?></td>
                    <td class="fw-semibold text-primary"><?= isset($row[$colNow]) && $row[$colNow] !== null ? number_format($row[$colNow], 2) : '-' ?></td>
                    <td>
                      <?php if(isset($row['persen_pencapaian']) && $row['persen_pencapaian'] !== null): ?>
                        <span class="badge bg-<?= $row['persen_pencapaian'] >= 100 ? 'success' : ($row['persen_pencapaian'] >= 80 ? 'warning' : 'danger') ?>">
                          <?=number_format($row['persen_pencapaian'], 2)?>%
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td class="action-buttons">
                      <button class="btn btn-sm btn-warning btn-pill"
                          data-bs-toggle="modal" data-bs-target="#editModal"
                          data-id="<?=e($row['id'])?>"
                          data-unit="<?=e($row['unit'])?>"
                          data-bulan="<?=e($row['bulan'])?>"
                          data-rprev="<?=isset($row[$colPrev])?number_format($row[$colPrev], 2):''?>"
                          data-tnow="<?=isset($row[$colTgt])?number_format($row[$colTgt], 2):''?>"
                          data-rnow="<?=isset($row[$colNow])?number_format($row[$colNow], 2):''?>"
                          data-p="<?=isset($row['persen_pencapaian'])?number_format($row['persen_pencapaian'], 2):''?>"
                          data-tahun="<?=$tahunIni?>">
                          <i class="fas fa-edit me-1"></i> Edit
                      </button>
                      <button class="btn btn-sm btn-danger btn-pill"
                          data-bs-toggle="modal" data-bs-target="#deleteModal"
                          data-id="<?=e($row['id'])?>"
                          data-unit="<?=e($row['unit'])?>"
                          data-bulan="<?=e($row['bulan'])?>">
                          <i class="fas fa-trash me-1"></i> Hapus
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">Tidak ada data untuk filter yang dipilih.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="card-body text-center py-5">
        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
        <h5 class="text-muted">Silakan pilih Unit, Bulan, dan Tahun untuk melihat data</h5>
        <p class="text-muted">Gunakan form filter di atas untuk memilih parameter yang diinginkan</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <input type="hidden" name="tahun" id="edit-tahun">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Data SAIDI</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Unit:</label>
          <input type="text" class="form-control" id="edit-unit" readonly>
        </div>
        <div class="mb-3">
          <label class="form-label">Bulan:</label>
          <input type="text" class="form-control" id="edit-bulan" readonly>
        </div>
        <div class="mb-3">
          <label for="edit-realisasi-prev" class="form-label">Realisasi Tahun Lalu (<?= $tahunLalu ?>)</label>
          <input type="text" name="realisasi_prev" id="edit-realisasi-prev" class="form-control">
        </div>
        <div class="mb-3">
          <label for="edit-target-now" class="form-label">Target Tahun Ini (<?= $tahunIni ?>)</label>
          <input type="text" name="target_now" id="edit-target-now" class="form-control">
        </div>
        <div class="mb-3">
          <label for="edit-realisasi-now" class="form-label">Realisasi Tahun Ini (<?= $tahunIni ?>)</label>
          <input type="text" name="realisasi_now" id="edit-realisasi-now" class="form-control">
        </div>
        <div class="mb-3">
          <label for="edit-pencapaian" class="form-label">% Pencapaian</label>
          <input type="text" name="persen_pencapaian" id="edit-pencapaian" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary btn-pill">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete-id">
      <div class="modal-header bg-danger">
        <h5 class="modal-title"><i class="fas fa-trash me-2"></i> Hapus Data</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Apakah Anda yakin ingin menghapus data ini?</p>
        <p class="fw-bold"><span id="delete-unit"></span> - <span id="delete-bulan"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger btn-pill">Ya, Hapus</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const labels = <?= json_encode($labels_chart) ?>;
  const dataRealisasiNow = <?= json_encode($data_realisasi_now) ?>;
  const dataTargetNow = <?= json_encode($data_target_now) ?>;
  const dataRealisasiPrev = <?= json_encode($data_realisasi_prev) ?>;

  // Chart Line
  const ctxLine = document.getElementById('saidiLineChart');
  if (ctxLine) {
    new Chart(ctxLine, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Realisasi <?= $tahunIni ?>',
            data: dataRealisasiNow,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.2,
            fill: true
          },
          {
            label: 'Target <?= $tahunIni ?>',
            data: dataTargetNow,
            borderColor: '#198754',
            backgroundColor: 'rgba(25, 135, 84, 0.1)',
            borderDash: [5, 5],
            tension: 0.2
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  }

  // Chart Bar
  const ctxBar = document.getElementById('saidiBarChart');
  if (ctxBar) {
    new Chart(ctxBar, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [
          {
            label: 'Realisasi <?= $tahunLalu ?>',
            data: dataRealisasiPrev,
            backgroundColor: 'rgba(255, 99, 132, 0.6)',
            borderColor: 'rgb(255, 99, 132)',
            borderWidth: 1
          },
          {
            label: 'Realisasi <?= $tahunIni ?>',
            data: dataRealisasiNow,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgb(54, 162, 235)',
            borderWidth: 1
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { stacked: false },
          y: { stacked: false, beginAtZero: true }
        }
      }
    });
  }

  // Handle modal edit
  const editModal = document.getElementById('editModal');
  if (editModal) {
    editModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      const unit = button.getAttribute('data-unit');
      const bulan = button.getAttribute('data-bulan');
      const rprev = button.getAttribute('data-rprev');
      const tnow = button.getAttribute('data-tnow');
      const rnow = button.getAttribute('data-rnow');
      const p = button.getAttribute('data-p');
      const tahun = button.getAttribute('data-tahun');

      editModal.querySelector('#edit-id').value = id;
      editModal.querySelector('#edit-unit').value = unit;
      editModal.querySelector('#edit-bulan').value = bulan;
      editModal.querySelector('#edit-realisasi-prev').value = rprev;
      editModal.querySelector('#edit-target-now').value = tnow;
      editModal.querySelector('#edit-realisasi-now').value = rnow;
      editModal.querySelector('#edit-pencapaian').value = p;
      editModal.querySelector('#edit-tahun').value = tahun;
    });
  }

  // Handle modal delete
  const deleteModal = document.getElementById('deleteModal');
  if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function(event) {
      const button = event.relatedTarget;
      const id = button.getAttribute('data-id');
      const unit = button.getAttribute('data-unit');
      const bulan = button.getAttribute('data-bulan');

      deleteModal.querySelector('#delete-id').value = id;
      deleteModal.querySelector('#delete-unit').textContent = unit;
      deleteModal.querySelector('#delete-bulan').textContent = bulan;
    });
  }
});
</script>
</body>
</html>