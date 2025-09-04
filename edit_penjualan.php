<?php
session_start();

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

// Cek login & role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Ambil id
$id = $_GET['id'] ?? 0;
$id = intval($id);

// Ambil data lama
$sql = "SELECT * FROM penjualan_tenaga_listrik WHERE id=$id";
$res = $conn->query($sql);
if ($res->num_rows == 0) {
    die("Data tidak ditemukan!");
}
$data = $res->fetch_assoc();

// Update data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $unit       = $conn->real_escape_string($_POST['unit']);
    $bulan      = $conn->real_escape_string($_POST['bulan']);
    $realisasi2024 = floatval($_POST['realisasi_2024']);
    $target2025    = floatval($_POST['target_2025']);
    $realisasi2025 = floatval($_POST['realisasi_2025']);
    $persen = $target2025 > 0 ? ($realisasi2025 / $target2025) * 100 : 0;

    $update = $conn->query("UPDATE penjualan_tenaga_listrik 
                            SET unit='$unit',
                                bulan='$bulan',
                                realisasi_2024=$realisasi2024,
                                target_2025=$target2025,
                                realisasi_2025=$realisasi2025,
                                persen_pencapaian=$persen
                            WHERE id=$id");
    if ($update) {
        header("Location: penjualan_tenaga_listrik.php?success=1");
        exit();
    } else {
        $error = "Gagal mengupdate data: " . $conn->error;
    }
}

// Bulan array
$bulan_arr = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
              'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Edit Data Penjualan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-4">
    <div class="card shadow-lg">
      <div class="card-header bg-primary text-white">
        <h4><i class="fas fa-edit"></i> Edit Data Penjualan</h4>
      </div>
      <div class="card-body">
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?= $error; ?></div>
        <?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label">Unit</label>
            <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($data['unit']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-select" required>
              <?php foreach($bulan_arr as $b): ?>
                <option value="<?= $b ?>" <?= ($data['bulan']==$b?'selected':''); ?>><?= $b ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Realisasi 2024</label>
            <input type="number" step="0.01" name="realisasi_2024" class="form-control" value="<?= $data['realisasi_2024']; ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Target 2025</label>
            <input type="number" step="0.01" name="target_2025" class="form-control" value="<?= $data['target_2025']; ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Realisasi 2025</label>
            <input type="number" step="0.01" name="realisasi_2025" class="form-control" value="<?= $data['realisasi_2025']; ?>" required>
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-save"></i> Simpan Perubahan
          </button>
          <a href="penjualan_tenaga_listrik.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
          </a>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
