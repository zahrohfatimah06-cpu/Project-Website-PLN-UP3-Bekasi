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

function e($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$status_message = '';
$status_class = '';
$is_success = false;
$bulanList = ["JANUARI","FEBRUARI","MARET","APRIL","MEI","JUNI","JULI","AGUSTUS","SEPTEMBER","OKTOBER","NOVEMBER","DESEMBER"];
$tahunList = range(2024, date("Y") + 5);

// ==================== PROSES SUBMIT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipe_data = $_POST['tipe_data'] ?? '';
    $unit = $_POST['unit'] ?? '';
    $bulan = $_POST['bulan'] ?? '';
    $tahun = intval($_POST['tahun'] ?? date('Y'));
    $realisasi_sebelum = $_POST['realisasi_sebelum'] !== '' ? floatval($_POST['realisasi_sebelum']) : null;
    $target_ini = $_POST['target_ini'] !== '' ? floatval($_POST['target_ini']) : null;
    $realisasi_ini = $_POST['realisasi_ini'] !== '' ? floatval($_POST['realisasi_ini']) : null;
    $nilai_tambahan = $_POST['nilai_tambahan'] !== '' ? floatval($_POST['nilai_tambahan']) : null;

    if (empty($tipe_data) || empty($unit) || empty($bulan) || empty($tahun)) {
        $status_message = "⚠️ Tipe data, Unit, Bulan, dan Tahun wajib diisi.";
        $status_class = "alert-danger";
    } else {
        $status_message = "✅ Data baru berhasil ditambahkan."; // (sementara tanpa query DB agar fokus UI)
        $status_class = "alert-success";
        $is_success = true;
    }
    $conn->close();
    if ($is_success) header("refresh:2;url=index.php?tahun={$tahun}");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Data</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .card { border-radius: 12px; }
        .btn-success { background: #28a745; border: none; }
        .btn-success:hover { background: #218838; }
        .form-label { font-weight: 600; }
        .header-title { font-size: 1.5rem; font-weight: bold; color: #007bff; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="header-title"><i class="fa-solid fa-plus-circle me-2"></i>Tambah Data Baru</h2>
        <a href="lihat_pi.php" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Kembali
        </a>
    </div>

    <?php if ($status_message): ?>
        <div class="alert <?= $status_class ?> shadow-sm">
            <?= e($status_message) ?>
        </div>
        <?php if ($is_success): ?>
            <p class="text-muted">Anda akan dialihkan kembali ke halaman utama dalam 2 detik...</p>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post" action="tambah_data.php">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="tipe_data" class="form-label">📊 Tipe Data</label>
                        <select name="tipe_data" id="tipe_data" class="form-select" required>
                            <option value="">-- Pilih --</option>
                            <option value="susut_distribusi">Susut Distribusi</option>
                            <option value="saifi">SAIFI</option>
                            <option value="saidi">SAIDI</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="unit" class="form-label">🏢 Unit</label>
                        <input type="text" class="form-control" id="unit" name="unit" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="bulan" class="form-label">📅 Bulan</label>
                        <select name="bulan" id="bulan" class="form-select" required>
                            <option value="">-- Pilih Bulan --</option>
                            <?php foreach ($bulanList as $b): ?>
                                <option value="<?= e($b) ?>"><?= e($b) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tahun" class="form-label">🗓 Tahun</label>
                        <select name="tahun" id="tahun" class="form-select" required>
                            <?php foreach ($tahunList as $t): ?>
                                <option value="<?= $t ?>" <?= ($t == date('Y') ? 'selected' : '') ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="realisasi_sebelum" class="form-label">📌 Realisasi Tahun Lalu</label>
                        <input type="number" step="0.01" class="form-control" id="realisasi_sebelum" name="realisasi_sebelum">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="target_ini" class="form-label">🎯 Target Tahun Ini</label>
                        <input type="number" step="0.01" class="form-control" id="target_ini" name="target_ini">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="realisasi_ini" class="form-label">📌 Realisasi Tahun Ini</label>
                        <input type="number" step="0.01" class="form-control" id="realisasi_ini" name="realisasi_ini">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="nilai_tambahan" class="form-label" id="label-nilai-tambahan">Persentase Pencapaian (%)</label>
                    <input type="number" step="0.01" class="form-control" id="nilai_tambahan" name="nilai_tambahan">
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success px-4">
                        <i class="fa-solid fa-save me-2"></i>Simpan
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipeDataSelect = document.getElementById('tipe_data');
    const labelTambahan = document.getElementById('label-nilai-tambahan');
    tipeDataSelect.addEventListener('change', function() {
        if (this.value === 'susut_distribusi') labelTambahan.textContent = 'Persentase Pencapaian (%)';
        else if (this.value === 'saifi') labelTambahan.textContent = 'SAIFI';
        else if (this.value === 'saidi') labelTambahan.textContent = 'SAIDI';
        else labelTambahan.textContent = 'Nilai Tambahan';
    });
});
</script>

</body>
</html>
