<?php
// Koneksi ke database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksig
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bulan = $_POST['bulan'];
    $bobot = $_POST['bobot'];
    $skor  = $_POST['skor'];

    $sql = "INSERT INTO kpi_data (bulan, bobot, skor) VALUES ('$bulan', '$bobot', '$skor')";
    
    if ($conn->query($sql) === TRUE) {
        // Redirect ke list_kpi.php setelah sukses simpan
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
    <title>Input KPI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-6 md:p-10 rounded-2xl shadow-lg w-full max-w-md">
        <h2 class="text-xl font-bold mb-4 text-center">Input Data KPI</h2>

        <?php if (!empty($error)): ?>
            <p class="bg-red-100 text-red-700 p-2 mb-4 rounded"><?= $error; ?></p>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium">Bulan</label>
                <input type="text" name="bulan" required class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label class="block text-sm font-medium">Bobot</label>
                <input type="number" step="0.01" name="bobot" required class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <div>
                <label class="block text-sm font-medium">Skor</label>
                <input type="number" step="0.01" name="skor" required class="w-full p-2 border rounded-lg focus:ring focus:ring-blue-300">
            </div>

            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-xl hover:bg-blue-700 transition">Simpan</button>
        </form>

        <div class="mt-4 text-center">
            <a href="list_kpi.php" class="text-blue-600 hover:underline">Lihat Data KPI</a>
        </div>
    </div>

</body>
</html>
