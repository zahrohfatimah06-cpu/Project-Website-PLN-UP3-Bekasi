<?php
session_start();

// Cek login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Ambil data user dari session
$user_data = $_SESSION['user'];
$username = htmlspecialchars($user_data['username'] ?? 'Guest');
$unit     = htmlspecialchars($user_data['unit'] ?? 'Tidak diketahui');
$role     = htmlspecialchars($user_data['role'] ?? 'user');

// Sertakan file koneksi database
include 'config.php';

// Tabel yang diizinkan untuk dikelola
$allowed_tables = [
    'penjualan_tenaga_listrik' => 'Penjualan Tenaga Listrik',
    'saifi_data'               => 'SAIFI',
    'data_saidi'               => 'SAIDI'
];

// Ambil tabel yang dipilih dari parameter URL, default ke 'penjualan_tenaga_listrik'
$table = $_GET['table'] ?? 'penjualan_tenaga_listrik';
if (!array_key_exists($table, $allowed_tables)) {
    die("Tabel tidak valid.");
}

// Ambil data dari tabel yang dipilih
$result = $conn->query("SELECT * FROM $table ORDER BY id ASC");
if (!$result) {
    die("Error query: " . $conn->error);
}

// Fungsi untuk mendapatkan nama kolom dari tabel
function getTableColumns($conn, $table) {
    $columns = [];
    $query = "SHOW COLUMNS FROM `$table`";
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

$columns = getTableColumns($conn, $table);

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data - PLN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        .pln-blue-gradient {
            background-color: #0A4C95;
            background-image: linear-gradient(135deg, #0A4C95 0%, #1765B6 100%);
        }
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            color: white;
            opacity: 0.8;
            transition: all 0.2s;
        }
        .sidebar-link:hover {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.2);
        }
        .sidebar-link.active {
            opacity: 1;
            background-color: rgba(255, 255, 255, 0.3);
            font-weight: 600;
        }
    </style>
</head>
<body class="min-h-screen flex bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200">

    <aside class="pln-blue-gradient text-white w-64 fixed md:static inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50 flex flex-col" id="sidebar">
        <div class="p-6 flex items-center gap-3 border-b border-white/20">
            <i class="fas fa-bolt text-3xl"></i>
            <div>
                <h1 class="text-lg font-bold">PLN Dashboard</h1>
                <p class="text-xs opacity-80">Manajemen Data</p>
            </div>
        </div>
        <nav class="flex-1 p-4 space-y-2">
            <a href="dashboard.php" class="sidebar-link">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="manage_data.php" class="sidebar-link active">
                <i class="fas fa-database"></i> Kelola Data
            </a>
            <?php if ($role === 'admin'): ?>
                <a href="manage_users.php" class="sidebar-link">
                    <i class="fas fa-users-cog"></i> User
                </a>
            <?php endif; ?>
        </nav>
        <div class="p-4 border-t border-white/20">
            <div class="flex items-center gap-2 text-sm">
                <i class="fas fa-user-circle text-xl"></i>
                <div>
                    <div class="font-medium"><?= $username; ?></div>
                    <div class="text-xs opacity-80"><?= $role; ?> &middot; <?= $unit; ?></div>
                </div>
            </div>
            <a href="logout.php" class="block mt-3 text-center bg-white text-[#0A4C95] font-semibold py-2 px-6 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>
    </aside>

    <main class="flex-1 p-6">
        <div class="md:hidden mb-4">
            <button id="menu-toggle" class="text-gray-700 dark:text-gray-300 focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>

        <h1 class="text-2xl font-bold text-[#0A4C95] mb-6 dark:text-blue-400">Kelola Data: <?= htmlspecialchars($allowed_tables[$table]) ?></h1>

        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
            <form method="get" class="flex items-center gap-2">
                <label for="table" class="font-medium text-gray-700 dark:text-gray-300">Pilih Tabel:</label>
                <select name="table" id="table" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring focus:border-blue-300">
                    <?php foreach ($allowed_tables as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $key == $table ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <a href="tambah_data.php?table=<?= $table ?>" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-plus mr-2"></i>Tambah Data
            </a>
        </div>

        <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-xl shadow-lg">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-100 dark:bg-gray-700">
                    <tr>
                        <?php foreach ($columns as $col): ?>
                            <th class="border border-gray-200 dark:border-gray-700 px-4 py-3 text-left font-semibold">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $col))) ?>
                            </th>
                        <?php endforeach; ?>
                        <th class="border border-gray-200 dark:border-gray-700 px-4 py-3 text-center font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <?php foreach ($row as $val): ?>
                                    <td class="border border-gray-200 dark:border-gray-700 px-4 py-3"><?= htmlspecialchars($val) ?></td>
                                <?php endforeach; ?>
                                <td class="border border-gray-200 dark:border-gray-700 px-4 py-3 text-center space-x-2">
                                    <a href="edit_data.php?table=<?= $table ?>&id=<?= $row['id'] ?>" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-600 transition">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="hapus_data.php?table=<?= $table ?>&id=<?= $row['id'] ?>" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-600 transition" onclick="return confirm('Yakin hapus data ini?')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= count($columns) + 1 ?>" class="border px-4 py-3 text-center text-gray-500 dark:text-gray-400">Tidak ada data ditemukan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        const sidebar = document.getElementById('sidebar');
        const menuToggle = document.getElementById('menu-toggle');
        
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    </script>
</body>
</html>