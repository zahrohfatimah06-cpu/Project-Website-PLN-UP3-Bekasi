<?php
session_start();
$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM riwayat_penjualan ORDER BY deleted_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Riwayat Penghapusan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h3>Riwayat Penghapusan</h3>
  <table class="table table-bordered table-striped">
    <thead>
      <tr>
        <th>Unit</th><th>Bulan</th><th>Realisasi 2024</th>
        <th>Target 2025</th><th>Realisasi 2025</th>
        <th>% Pencapaian</th><th>Dihapus pada</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= $row['unit']; ?></td>
          <td><?= $row['bulan']; ?></td>
          <td><?= $row['realisasi_2024']; ?></td>
          <td><?= $row['target_2025']; ?></td>
          <td><?= $row['realisasi_2025']; ?></td>
          <td><?= $row['persen_pencapaian']; ?>%</td>
          <td><?= $row['deleted_at']; ?></td>
          <td>
            <a href="restore_penjualan.php?id=<?= $row['id']; ?>" class="btn btn-success btn-sm"
               onclick="return confirm('Kembalikan data ini?');">Kembalikan</a>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
  <a href="penjualan_tenaga_listrik.php" class="btn btn-secondary">Kembali</a>
</body>
</html>
