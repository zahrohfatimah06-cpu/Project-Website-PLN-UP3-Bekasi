<?php
session_start();

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

// Koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "pln_dashboard";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$error = "";
$success = "";
$username = "";
$unit = "";
$role = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $unit     = trim($_POST['unit'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    if (empty($username) || empty($password) || empty($unit) || empty($role)) {
        $error = "Semua field wajib diisi!";
    } elseif ($password !== $confirm) {
        $error = "Password dan konfirmasi tidak sama!";
    } else {
        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $error = "Username sudah digunakan!";
        } else {
            // Hash password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $stmt = $conn->prepare("INSERT INTO users (username, password, role, unit) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hashed, $role, $unit);

            if ($stmt->execute()) {
                $success = "Registrasi berhasil! Silakan login.";
                $username = $unit = $role = "";
            } else {
                $error = "Terjadi kesalahan. Coba lagi.";
            }
        }
        $stmt->close();
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register - PLN Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { --pln-blue:#004d8e; --pln-dark-blue:#00264d; --pln-light:#f5f7fa; }
    body{background:var(--pln-light);height:100vh;display:flex;align-items:center;justify-content:center;}
    .register-container{max-width:450px;width:100%;padding:15px;}
    .card{border-radius:12px;box-shadow:0 6px 15px rgba(0,0,0,0.1);border:none;}
    .card-header{background:var(--pln-blue);color:white;border-radius:12px 12px 0 0!important;text-align:center;padding:20px;}
    .btn-primary{background:var(--pln-blue);border:none;font-weight:600;}
    .btn-primary:hover{background:var(--pln-dark-blue);}
    .input-group-text{background:white;}
  </style>
</head>
<body>
  <div class="register-container">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-user-plus fa-2x mb-2"></i>
        <h4>Register Akun</h4>
      </div>
      <div class="card-body">
        <?php if($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?> 
            <a href="login.php" class="alert-link">Login</a>
          </div>
        <?php endif; ?>

        <form method="post">
          <!-- Username -->
          <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-user"></i></span>
              <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($username) ?>" required>
            </div>
          </div>

          <!-- Password -->
          <div class="mb-3">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" name="password" class="form-control" required>
            </div>
          </div>

          <!-- Konfirmasi Password -->
          <div class="mb-3">
            <label class="form-label">Konfirmasi Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-lock"></i></span>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
          </div>

          <!-- Unit -->
          <div class="mb-3">
            <label class="form-label">Unit</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-building"></i></span>
              <input type="text" name="unit" class="form-control" value="<?= htmlspecialchars($unit) ?>" required>
            </div>
          </div>
          <!-- Role -->
<div class="mb-3">
  <label class="form-label">Role</label>
  <div class="input-group">
    <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
    <select name="role" class="form-select" required>
      <option value="user" selected>User</option>
    </select>
  </div>
  <small class="text-muted">* Hanya Superadmin yang bisa membuat akun Admin</small>
</div>

          <button type="submit" class="btn btn-primary w-100 mt-3">
            <i class="fas fa-user-plus me-2"></i>Register
          </button>
        </form>

        <div class="text-center mt-3">
          <p class="mb-0">Sudah punya akun? 
            <a href="login.php" class="text-decoration-none">Login</a>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
