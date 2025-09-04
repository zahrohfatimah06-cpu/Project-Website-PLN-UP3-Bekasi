<?php
// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data berdasarkan ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM kpi_data WHERE id=$id";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
}

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id    = $_POST['id'];
    $bulan = $_POST['bulan'];
    $bobot = $_POST['bobot'];
    $skor  = $_POST['skor'];

    $sql = "UPDATE kpi_data SET bulan='$bulan', bobot='$bobot', skor='$skor' WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        header("Location: list_kpi.php");
        exit;
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-6 md:p-10 rounded-2xl shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-center">Edit Data KPI</h2>

        <?php if (!empty($error)): ?>
            <p class="bg-red-100 text-red-700 p-2 mb-4 rounded"><?= $error; ?></p>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <input type="hidden" name="id" value="<?= $data['id']; ?>">

            <div>
                <label class="block text-sm font-medium">Bulan</label>
                <input type="text" name="bulan" value="<?= $data['bulan']; ?>" required class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label class="block text-sm font-medium">Bobot</label>
                <input type="number" step="0.01" name="bobot" value="<?= $data['bobot']; ?>" required class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label class="block text-sm font-medium">Skor</label>
                <input type="number" step="0.01" name="skor" value="<?= $data['skor']; ?>" required class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div class="flex justify-between">
                <a href="list_kpi.php" class="bg-gray-400 text-white px-4 py-2 rounded-xl hover:bg-gray-500 transition">Batal</a>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700 transition">Update</button>
            </div>
        </form>
    </div>

</body>
</html>
