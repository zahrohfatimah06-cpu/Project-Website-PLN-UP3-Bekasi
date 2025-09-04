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

$sql = "SELECT * FROM pi_data ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Data PI</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">

    <div class="bg-white p-6 md:p-10 rounded-2xl shadow-xl w-full max-w-6xl">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-700 flex items-center gap-2">
                <i data-lucide="layers" class="w-6 h-6 text-blue-600"></i>
                Daftar Data PI
            </h2>
            <a href="input_pi.php" class="bg-blue-600 text-white px-4 py-2 rounded-xl hover:bg-blue-700 transition flex items-center gap-2">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                Tambah Data
            </a>
        </div>

        <div class="overflow-x-auto shadow-md rounded-xl border border-gray-200">
            <table class="w-full text-sm text-left">
                <thead class="bg-blue-600 text-white">
                    <tr>
                        <th class="p-3">ID</th>
                        <th class="p-3">Bulan</th>
                        <th class="p-3">Indikator</th>
                        <th class="p-3">Target</th>
                        <th class="p-3">Realisasi</th>
                        <th class="p-3">Created At</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-blue-50 transition">
                                <td class="p-3 font-medium text-gray-700"><?= $row['id']; ?></td>
                                <td class="p-3"><?= $row['bulan']; ?></td>
                                <td class="p-3"><?= $row['indikator']; ?></td>
                                <td class="p-3"><?= $row['target']; ?></td>
                                <td class="p-3"><?= $row['realisasi']; ?></td>
                                <td class="p-3 text-gray-500"><?= $row['created_at']; ?></td>
                                <td class="p-3 flex gap-2 justify-center">
                                    <a href="edit_pi.php?id=<?= $row['id']; ?>" class="bg-yellow-400 text-white px-3 py-1 rounded-lg hover:bg-yellow-500 flex items-center gap-1">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>Edit
                                    </a>
                                    <a href="hapus_pi.php?id=<?= $row['id']; ?>" onclick="return confirm('Yakin mau hapus data ini?')" class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 flex items-center gap-1">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="p-4 text-center text-gray-500">Belum ada data</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
