<?php
session_start();
if (isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Admin</title>
</head>
<body>
    <h2>Login Admin</h2>
    <form method="POST" action="auth.php">
        <label>Username:</label><br>
        <input type="text" name="username" required><br><br>
        
        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>
        
        <button type="submit">Login</button>
    </form>
    <?php
    if (isset($_GET['error'])) {
        echo "<p style='color:red'>".$_GET['error']."</p>";
    }
    ?>
</body>
</html>
