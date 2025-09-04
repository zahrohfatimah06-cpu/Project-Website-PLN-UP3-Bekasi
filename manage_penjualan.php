<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Hanya admin yang boleh akses
if ($_SESSION['role'] !== 'admin') {
    die("Akses ditolak! Halaman ini hanya untuk admin.");
}

$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Tambah data
if (isset($_POST['tambah'])) {
    $unit = $conn->real_escape_string($_POST['unit']);
    $bulan = $conn->real_escape_string($_POST['bulan']);
    $realisasi2024 = floatval($_POST['realisasi_2024']);
    $target2025 = floatval($_POST['target_2025']);
    $realisasi2025 = floatval($_POST['realisasi_2025']);
    $persen = $target2025 > 0 ? ($realisasi2025 / $target2025) * 100 : 0;

    $sql = "INSERT INTO penjualan_tenaga_listrik (unit, bulan, realisasi_2024, target_2025, realisasi_2025, persen_pencapaian)
            VALUES ('$unit','$bulan','$realisasi2024','$target2025','$realisasi2025','$persen')";
    $conn->query($sql);
}

// Update data
if (isset($_POST['update'])) {
    $id = intval($_POST['id']);
    $unit = $conn->real_escape_string($_POST['unit']);
    $bulan = $conn->real_escape_string($_POST['bulan']);
    $realisasi2024 = floatval($_POST['realisasi_2024']);
    $target2025 = floatval($_POST['target_2025']);
    $realisasi2025 = floatval($_POST['realisasi_2025']);
    $persen = $target2025 > 0 ? ($realisasi2025 / $target2025) * 100 : 0;

    $sql = "UPDATE penjualan_tenaga_listrik 
            SET unit='$unit', bulan='$bulan', realisasi_2024='$realisasi2024',
                target_2025='$target2025', realisasi_2025='$realisasi2025',
                persen_pencapaian='$persen'
            WHERE id=$id";
    $conn->query($sql);
}

// Hapus data
if (isset($_GET['hapus'])) {
    $id = intval($_GET['hapus']);
    $conn->query("DELETE FROM penjualan_tenaga_listrik WHERE id=$id");
}

// Ambil data
$result = $conn->query("SELECT * FROM penjualan_tenaga_listrik ORDER BY 
    FIELD(bulan,'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'), unit");
$data = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Admin - Kelola Data Penjualan</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; background:#f4f6f9; margin:20px; }
h2 { color:#0A4C95; }
form { margin-bottom:20px; background:#fff; padding:15px; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1);}
input, select { padding:8px; margin:5px; }
button { padding:8px 15px; border:none; border-radius:5px; cursor:pointer; }
button.add { background:#28a745; color:white; }
button.update { background:#0A4C95; color:white; }
a.delete { color:red; text-decoration:none; }
table { width:100%; border-collapse:collapse; margin-top:15px; background:#fff; }
th,td { border:1px solid #ddd; padding:10px; text-align:center; }
th { background:#0A4C95; color:white; }
tr:nth-child(even){ background:#f9f9f9; }
</style>
</head>
<body>

<h2>Kelola Data Penjualan Tenaga Listrik</h2>

<!-- Form Tambah -->
<form method="post">
    <h3>Tambah Data Baru</h3>
    <select name="unit" required>
        <option value="">-- Pilih Unit --</option>
        <option value="UP3 BEKASI">UP3 BEKASI</option>
        <option value="ULP BEKASI KOTA">ULP BEKASI KOTA</option>
        <!-- tambahkan sesuai kebutuhan -->
    </select>
    <select name="bulan" required>
        <option value="">-- Pilih Bulan --</option>
        <?php 
        $bulanArr = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
        foreach($bulanArr as $b){ echo "<option value='$b'>$b</option>"; }
        ?>
    </select>
    <input type="number" step="0.01" name="realisasi_2024" placeholder="Realisasi 2024" required>
    <input type="number" step="0.01" name="target_2025" placeholder="Target 2025" required>
    <input type="number" step="0.01" name="realisasi_2025" placeholder="Realisasi 2025" required>
    <button type="submit" name="tambah" class="add"><i class="fas fa-plus"></i> Tambah</button>
</form>

<!-- Tabel Data -->
<table>
    <thead>
        <tr>
            <th>Unit</th>
            <th>Bulan</th>
            <th>Realisasi 2024</th>
            <th>Target 2025</th>
            <th>Realisasi 2025</th>
            <th>% Pencapaian</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php if($data): foreach($data as $row): ?>
        <tr>
            <form method="post">
                <td><input type="text" name="unit" value="<?= htmlspecialchars($row['unit']) ?>"></td>
                <td>
                    <select name="bulan">
                        <?php foreach($bulanArr as $b){ ?>
                            <option value="<?= $b ?>" <?= $row['bulan']==$b?'selected':''?>><?= $b ?></option>
                        <?php } ?>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="realisasi_2024" value="<?= $row['realisasi_2024'] ?>"></td>
                <td><input type="number" step="0.01" name="target_2025" value="<?= $row['target_2025'] ?>"></td>
                <td><input type="number" step="0.01" name="realisasi_2025" value="<?= $row['realisasi_2025'] ?>"></td>
                <td><?= number_format($row['persen_pencapaian'],2,',','.') ?>%</td>
                <td>
                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                    <button type="submit" name="update" class="update"><i class="fas fa-save"></i></button>
                    <a href="?hapus=<?= $row['id'] ?>" class="delete" onclick="return confirm('Yakin hapus data ini?')"><i class="fas fa-trash"></i></a>
                </td>
            </form>
        </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="7">Belum ada data</td></tr>
    <?php endif; ?>
    </tbody>
</table>

</body>
</html>
