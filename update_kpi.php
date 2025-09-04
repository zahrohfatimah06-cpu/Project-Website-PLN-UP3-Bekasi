<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "pln_dashboard");
if ($conn->connect_error) { die("Koneksi gagal: " . $conn->connect_error); }

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bulan = $_POST['bulan'];
    $bobot = $_POST['bobot'];
    $skor  = $_POST['skor'];

    $stmt = $conn->prepare("INSERT INTO kpi_data (bulan, bobot, skor) VALUES (?, ?, ?)");
    $stmt->bind_param("sdd", $bulan, $bobot, $skor);

    if ($stmt->execute()) {
        $success = "✅ Data KPI berhasil disimpan!";
    } else {
        $error = "❌ Gagal menyimpan data: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Update KPI</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-blue-50 to-blue-100 flex items-center justify-center min-h-screen font-sans">

  <div class="bg-white p-8 rounded-2xl shadow-lg w-[420px] transition transform hover:scale-[1.01] duration-300">
    
    <h2 class="text-2xl font-bold mb-6 text-[#0A4C95] text-center">Update KPI</h2>

    <!-- Notifikasi -->
    <?php if ($error): ?>
      <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4">
        <?= $error; ?>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4">
        <?= $success; ?>
      </div>
    <?php endif; ?>
    <form method="post" class="space-y-5">
  <div>
    <label class="block text-gray-700 font-medium mb-1">📌 KPI</label>
    <select name="nama_kpi" class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-[#0A4C95]" required>
      <option value="Penjualan Tenaga Listrik">Penjualan Tenaga Listrik</option>
      <option value="Peningkatan Kehandalan Jaringan">Peningkatan Kehandalan Jaringan</option>
      <option value="Efisiensi Jaringan Distribusi">Efisiensi Jaringan Distribusi</option>
    </select>
  </div>

  <div>
    <label class="block text-gray-700 font-medium mb-1">📅 Bulan</label>
    <input type="text" name="bulan" placeholder="Contoh: Januari 2025"
           class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-[#0A4C95]" required>
  </div>

  <div>
    <label class="block text-gray-700 font-medium mb-1">⚖️ Bobot (%)</label>
    <input type="number" name="bobot" step="0.01"
           class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-[#0A4C95]" required>
  </div>

  <div>
    <label class="block text-gray-700 font-medium mb-1">📊 Skor</label>
    <input type="number" name="skor" step="0.01"
           class="w-full border border-gray-300 p-3 rounded-lg focus:ring-2 focus:ring-[#0A4C95]" required>
  </div>

  <button type="submit" class="w-full bg-[#0A4C95] text-white py-3 rounded-lg font-semibold hover:bg-[#073b76]">
    💾 Simpan Data
  </button>
</form>

      <!-- Tombol kembali -->
      <a href="dashboard.php" 
         class="block text-center w-full mt-3 bg-gray-200 text-gray-700 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
        ⬅️ Kembali ke Dashboard
      </a>
    </form>
  </div>

</body>
</html>
