<?php
// --- Koneksi Database ---
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// --- Ambil Parameter ---
$tabel = $_GET['tabel'] ?? '';
$id    = $_GET['id'] ?? 0;

if (!$tabel || !$id) {
    die("Parameter tidak lengkap.");
}

// --- Ambil Struktur Kolom ---
$cols_res = $conn->query("SHOW COLUMNS FROM `$tabel`");
$kolom = [];
while ($c = $cols_res->fetch_assoc()) {
    $kolom[] = $c['Field'];
}

// --- Ambil Data Lama ---
$sql = "SELECT * FROM `$tabel` WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Data tidak ditemukan.");
}

// --- Proses Update ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_cols = [];
    $update_vals = [];
    $types = "";

    foreach ($kolom as $k) {
        if ($k == "id") continue; // jangan ubah id
        if (isset($_POST[$k])) {
            $update_cols[] = "`$k`=?";
            $update_vals[] = $_POST[$k];
            $types .= "s"; // semua jadi string, biar aman
        }
    }

    if (!empty($update_cols)) {
        $sql_update = "UPDATE `$tabel` SET " . implode(",", $update_cols) . " WHERE id=?";
        $stmt2 = $conn->prepare($sql_update);
        $types .= "i";
        $update_vals[] = $id;
        $stmt2->bind_param($types, ...$update_vals);

        if ($stmt2->execute()) {
            echo "<div class='alert alert-success'>Data berhasil diupdate!</div>";
            echo "<meta http-equiv='refresh' content='1;url={$tabel}.php'>";
            exit;
        } else {
            echo "<div class='alert alert-danger'>Gagal update: ".$stmt2->error."</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Data <?= htmlspecialchars($tabel) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <h3 class="mb-4">Edit Data (Tabel: <?= htmlspecialchars($tabel) ?>)</h3>
    <form method="post" class="card p-4 shadow-sm">
        <?php foreach ($kolom as $k): if ($k == "id") continue; ?>
            <div class="mb-3">
                <label class="form-label"><?= ucfirst(str_replace("_"," ",$k)) ?></label>
                <input type="text" name="<?= $k ?>" value="<?= htmlspecialchars($data[$k]) ?>" class="form-control">
            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary">Simpan</button>
        <a href="<?= $tabel ?>.php" class="btn btn-secondary">Batal</a>
    </form>
</div>
</body>
</html>
