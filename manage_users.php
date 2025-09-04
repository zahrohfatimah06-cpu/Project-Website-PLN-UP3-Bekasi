<?php
session_start();

// Redirect jika pengguna tidak login atau bukan admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$message = '';
$conn = null;

try {
    // Koneksi ke database
    $conn = new mysqli("localhost", "root", "", "pln_dashboard");
    if ($conn->connect_error) {
        throw new Exception("Koneksi gagal: " . $conn->connect_error);
    }

    // --- LOGIKA CRUD ---

    // Menambah/Mengubah Pengguna
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $username = htmlspecialchars($_POST['username']);
        $unit = htmlspecialchars($_POST['unit']);
        $role = htmlspecialchars($_POST['role']);
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        // Cek username duplikat
        if ($_POST['action'] === 'add' || ($_POST['action'] === 'edit' && $id > 0)) {
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt_check->bind_param("si", $username, $id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Username sudah ada!</div>";
                goto skip_db_ops;
            }
        }

        if ($_POST['action'] === 'add') {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, unit, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $password, $unit, $role);
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pengguna berhasil ditambahkan!</div>";
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Gagal menambah pengguna: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } elseif ($_POST['action'] === 'edit' && $id > 0) {
            $sql = "UPDATE users SET username = ?, unit = ?, role = ? WHERE id = ?";
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE users SET username = ?, password = ?, unit = ?, role = ? WHERE id = ?";
            }
            
            $stmt = $conn->prepare($sql);
            if (!empty($_POST['password'])) {
                $stmt->bind_param("ssssi", $username, $password, $unit, $role, $id);
            } else {
                $stmt->bind_param("sssi", $username, $unit, $role, $id);
            }
            
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pengguna berhasil diperbarui!</div>";
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Gagal memperbarui pengguna: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }

    // Menghapus Pengguna
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "<div class='bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4' role='alert'>Pengguna berhasil dihapus!</div>";
            } else {
                $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Gagal menghapus pengguna: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }

    skip_db_ops:

    // Ambil semua data pengguna untuk ditampilkan
    $result = $conn->query("SELECT id, username, unit, role FROM users ORDER BY id ASC");
    $users = $result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Manage users error: " . $e->getMessage());
    $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>Terjadi kesalahan: " . $e->getMessage() . "</div>";
    $users = [];
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manajemen User - PLN Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f6f9; color: #2c3e50; }
        .pln-blue { background-color: #0A4C95; }
        .btn-edit { background-color: #f39c12; }
        .btn-delete { background-color: #e74c3c; }
        .btn-green { background-color: #2ecc71; }
    </style>
</head>
<body class="min-h-screen antialiased">

    <header class="pln-blue text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto px-4 py-4 flex flex-col md:flex-row items-center justify-between">
            <div class="flex items-center gap-4 mb-4 md:mb-0">
                <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-md">
                    <i class="fas fa-bolt text-2xl text-[#0A4C95]"></i>
                </div>
                <div class="text-center md:text-left">
                    <div class="text-xl font-bold tracking-wide">PLN Admin Dashboard</div>
                    <div class="text-sm opacity-90">Manajemen Pengguna</div>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="bg-white text-[#0A4C95] font-semibold py-2 px-6 rounded-full shadow-md hover:bg-gray-100 transition-all duration-300">
                    <i class="fas fa-tachometer-alt mr-2"></i>Kembali ke Dashboard
                </a>
                <a href="logout.php" class="bg-white text-[#0A4C95] font-semibold py-2 px-6 rounded-full shadow-md hover:bg-gray-100 transition-all duration-300">
                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                </a>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-4 md:p-8">
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-xl">
            <h1 class="text-2xl md:text-3xl font-bold mb-6 text-[#073b76]">Manajemen Pengguna Sistem</h1>
            <?= $message; ?>

            <div id="user-form-container" class="mb-8">
                <h2 id="form-title" class="text-xl font-semibold mb-4 text-[#0A4C95]">Tambah Pengguna Baru</h2>
                <form id="user-form" action="manage_users.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="hidden" name="id" id="user-id">
                    <input type="hidden" name="action" id="form-action" value="add">
                    <div>
                        <label for="username" class="block text-gray-700 font-medium mb-1">Username</label>
                        <input type="text" id="username" name="username" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="password" class="block text-gray-700 font-medium mb-1">Password</label>
                        <input type="password" id="password" name="password" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="unit" class="block text-gray-700 font-medium mb-1">Unit</label>
                        <input type="text" id="unit" name="unit" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                    </div>
                    <div>
                        <label for="role" class="block text-gray-700 font-medium mb-1">Role</label>
                        <select id="role" name="role" required class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 p-2">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-span-1 md:col-span-2 flex justify-start gap-4 mt-2">
                        <button type="submit" class="bg-[#0A4C95] text-white font-bold py-2 px-6 rounded-full shadow-md hover:bg-[#073b76] transition-all duration-300"><i class="fas fa-save mr-2"></i>Simpan</button>
                        <button type="button" id="btn-cancel" class="bg-gray-500 text-white font-bold py-2 px-6 rounded-full shadow-md hover:bg-gray-600 transition-all duration-300 hidden">Batal</button>
                    </div>
                </form>
            </div>

            <hr class="my-8">

            <h2 class="text-xl font-semibold mb-4 text-[#0A4C95]">Daftar Pengguna</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200 rounded-lg shadow-sm">
                    <thead>
                        <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">ID</th>
                            <th class="py-3 px-6 text-left">Username</th>
                            <th class="py-3 px-6 text-left">Unit</th>
                            <th class="py-3 px-6 text-left">Role</th>
                            <th class="py-3 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm font-light">
                        <?php if (!empty($users)): ?>
                            <?php foreach ($users as $user): ?>
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="py-3 px-6 text-left whitespace-nowrap"><?= $user['id']; ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($user['username']); ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($user['unit']); ?></td>
                                    <td class="py-3 px-6 text-left"><?= htmlspecialchars($user['role']); ?></td>
                                    <td class="py-3 px-6 text-center">
                                        <div class="flex item-center justify-center space-x-2">
                                            <button onclick="editUser(<?= $user['id']; ?>, '<?= htmlspecialchars($user['username']); ?>', '<?= htmlspecialchars($user['unit']); ?>', '<?= htmlspecialchars($user['role']); ?>')" class="btn-edit text-white py-1 px-3 rounded-md text-sm transition-all duration-300 hover:bg-yellow-600">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="manage_users.php?action=delete&id=<?= $user['id']; ?>" onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?')" class="btn-delete text-white py-1 px-3 rounded-md text-sm transition-all duration-300 hover:bg-red-600">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="py-4 text-center text-gray-500">Tidak ada data pengguna.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer class="mt-8 py-6 border-t border-gray-200 text-center text-gray-500">
        <p class="text-sm">PT PLN (Persero) - Sistem Manajemen Kinerja 2025</p>
    </footer>

    <script>
        function editUser(id, username, unit, role) {
            document.getElementById('form-title').innerText = 'Edit Pengguna';
            document.getElementById('form-action').value = 'edit';
            document.getElementById('user-id').value = id;
            document.getElementById('username').value = username;
            document.getElementById('unit').value = unit;
            document.getElementById('role').value = role;
            document.getElementById('password').required = false; // Tidak wajib saat edit
            document.getElementById('btn-cancel').classList.remove('hidden');
        }

        document.getElementById('btn-cancel').addEventListener('click', function() {
            document.getElementById('form-title').innerText = 'Tambah Pengguna Baru';
            document.getElementById('user-form').reset();
            document.getElementById('form-action').value = 'add';
            document.getElementById('user-id').value = '';
            document.getElementById('password').required = true;
            document.getElementById('btn-cancel').classList.add('hidden');
        });
    </script>
</body>
</html>