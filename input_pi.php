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

// Proses form jika disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bulan = $_POST['bulan'];
    $bobot = $_POST['bobot'];
    $skor  = $_POST['skor'];

    $sql = "INSERT INTO kpi_data (bulan, bobot, skor) VALUES ('$bulan', '$bobot', '$skor')";
    
    if ($conn->query($sql) === TRUE) {
        // Redirect ke list_kpi.php setelah sukses simpan
        header("Location: list_pi.php");
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
    <title>Input PI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white p-6 md:p-10 rounded-2xl shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold mb-4 text-center text-green-700">Input Data PI</h2>

        <?php if (!empty($error)): ?>
            <p class="bg-red-100 text-red-700 p-2 mb-4 rounded"><?= htmlspecialchars($error); ?></p>
        <?php elseif (!empty($success)): ?>
            <p class="bg-green-100 text-green-700 p-2 mb-4 rounded"><?= htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Bulan</label>
                <input type="text" name="bulan" value="<?= isset($bulan) ? htmlspecialchars($bulan) : '' ?>" required class="w-full p-2 border rounded-lg focus:ring focus:ring-green-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Bobot</label>
                <input type="number" step="0.01" name="bobot" value="<?= isset($bobot) ? htmlspecialchars($bobot) : '' ?>" required class="w-full p-2 border rounded-lg focus:ring focus:ring-green-300">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Skor</label>
                <input type="number" step="0.01" name="skor" value="<?= isset($skor) ? htmlspecialchars($skor) : '' ?>" required class="w-full p-2 border rounded-lg focus:ring focus:ring-green-300">
            </div>

            <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-xl hover:bg-green-700 transition">Simpan</button>
        </form>
        
        <div class="mt-4 text-center">
            <a href="list_pi.php" class="text-green-600 hover:underline">Lihat Data PI</a>
        </div>
    </div>

</body>
</html>
