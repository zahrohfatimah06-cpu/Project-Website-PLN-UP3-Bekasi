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
$username = "";
$unit = "";
$role = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $unit     = trim($_POST['unit'] ?? '');
    $role     = trim($_POST['role'] ?? '');
    
    // Validasi input
    if (empty($username) || empty($password) || empty($unit) || empty($role)) {
        $error = "Semua field harus diisi!";
    } else {
        // Siapkan query (cek sesuai username, unit, dan role)
        $stmt = $conn->prepare("SELECT id, username, password, role, unit 
                                FROM users 
                                WHERE username = ? AND unit = ? AND role = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("sss", $username, $unit, $role);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows > 0) {
                $row = $res->fetch_assoc();

                // Verifikasi password hash
                if (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);

                    $_SESSION['user'] = [
                        'id'       => $row['id'],
                        'username' => $row['username'],
                        'role'     => $row['role'],
                        'unit'     => $row['unit']
                    ];

                    // Redirect sesuai role
                    if ($row['role'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Password salah!";
                }
            } else {
                $error = "Username/Unit/Role tidak cocok!";
            }
            $stmt->close();
        } else {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Login - PLN Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { --pln-blue:#004d8e; --pln-dark-blue:#00264d; --pln-light:#f5f7fa; }
    body{background:var(--pln-light);height:100vh;display:flex;align-items:center;justify-content:center;}
    .login-container{max-width:400px;width:100%;padding:15px;}
    .card{border-radius:12px;box-shadow:0 6px 15px rgba(0,0,0,0.1);border:none;}
    .card-header{background:var(--pln-blue);color:white;border-radius:12px 12px 0 0!important;text-align:center;padding:20px;}
    .btn-primary{background:var(--pln-blue);border:none;font-weight:600;}
    .btn-primary:hover{background:var(--pln-dark-blue);}
    .input-group-text{background:white;}
    .password-toggle{cursor:pointer;}
  </style>
</head>
<body>
  <div class="login-container">
    <div class="card">
      <div class="card-header">
        <i class="fas fa-bolt fa-2x mb-2"></i>
        <h4>PLN Dashboard</h4>
      </div>
      <div class="card-body">
        <?php if($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="post" id="loginForm">
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
              <input type="password" id="password" name="password" class="form-control" required>
              <span class="input-group-text password-toggle" id="passwordToggle"><i class="fas fa-eye"></i></span>
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
                <option value="">-- Pilih Role --</option>
                <option value="admin" <?= $role==='admin'?'selected':'' ?>>Admin</option>
                <option value="user" <?= $role==='user'?'selected':'' ?>>User</option>
              </select>
            </div>
          </div>
          
          <button type="submit" class="btn btn-primary w-100 mt-3">
            <i class="fas fa-sign-in-alt me-2"></i>Login
          </button>
        </form>
        <div class="text-center mt-3">
  <p class="mb-0">Belum punya akun? 
    <a href="register.php" class="text-decoration-none">Register</a>
  </p>
</div>
      </div>
    </div>
  </div>

  <script>
    document.getElementById('passwordToggle').addEventListener('click', function() {
      const input = document.getElementById('password');
      const icon = this.querySelector('i');
      if(input.type==='password'){input.type='text';icon.classList.replace('fa-eye','fa-eye-slash');}
      else{input.type='password';icon.classList.replace('fa-eye-slash','fa-eye');}
    });
  </script>
</body>
</html>
