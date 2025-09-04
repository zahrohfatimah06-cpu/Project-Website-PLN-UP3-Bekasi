<?php
session_start();
include 'includes/db.php'; // koneksi ke database

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $unit = trim($_POST['unit']);
    $email = trim($_POST['email']);

    // cek apakah data user ada
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND unit = ? AND email = ?");
    $stmt->bind_param("sss", $username, $unit, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // di sini logikanya bisa dibuat kirim email reset password
        // untuk contoh sederhana, kita langsung kasih pesan sukses
        $message = "Instruksi reset password telah dikirim ke email Anda.";
        $message_type = "success";
    } else {
        $message = "Data pengguna tidak ditemukan. Pastikan username, unit, dan email sesuai.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password | Dashboard PLN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background: linear-gradient(135deg, #003366, #005BAC, #0095DA);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            font-family: 'Poppins', sans-serif;
        }

        .reset-container {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }

        .reset-header {
            background: linear-gradient(to right, #005BAC, #0095DA);
            padding: 25px;
            text-align: center;
            color: #fff;
        }

        .reset-header h2 {
            margin: 0;
            font-weight: 600;
        }

        .reset-body {
            padding: 25px;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #005BAC;
        }

        .form-input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 15px;
            background: #f8fafc;
        }

        .form-input:focus {
            border-color: #0095DA;
            outline: none;
            background: #fff;
        }

        .reset-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(to right, #FDB813, #FFC107);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            color: #003366;
            transition: 0.3s;
        }

        .reset-btn:hover {
            background: linear-gradient(to right, #e6a90c, #FFC107);
        }

        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .message.error {
            background: #ffebee;
            color: #c62828;
        }

        .reset-footer {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }

        .reset-footer a {
            color: #005BAC;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h2>Lupa Password</h2>
            <p>Masukkan data Anda untuk reset password</p>
        </div>
        <div class="reset-body">
            <?php if (!empty($message)): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" name="username" class="form-input" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-building input-icon"></i>
                    <input type="text" name="unit" class="form-input" placeholder="Unit" required>
                </div>
                <div class="input-group">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" class="form-input" placeholder="Email" required>
                </div>
                <button type="submit" class="reset-btn"><i class="fas fa-key"></i> Reset Password</button>
            </form>
            <div class="reset-footer">
                <p><a href="login.php">Kembali ke Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
