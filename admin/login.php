<?php
require_once '../auth/session.php';
require_once '../config/database.php';

// Kalau sudah login, langsung ke dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama_lengkap'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login Admin</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: system-ui, sans-serif;
        background: linear-gradient(135deg, #2c3e50, #3498db);
        min-height: 100vh; display: flex;
        align-items: center; justify-content: center;
    }
    .login-box {
        background: white; padding: 40px; border-radius: 12px;
        width: 380px; box-shadow: 0 10px 40px rgba(0,0,0,.3);
    }
    h1 { margin-bottom: 25px; color: #2c3e50; text-align: center; }
    .form-group { margin-bottom: 18px; }
    label { display: block; font-size: 14px; margin-bottom: 6px; color: #555; }
    input {
        width: 100%; padding: 12px; font-size: 15px;
        border: 1px solid #ddd; border-radius: 8px;
    }
    input:focus { outline: none; border-color: #3498db; }
    button {
        width: 100%; padding: 13px; background: #3498db; color: white;
        border: none; border-radius: 8px; font-size: 16px;
        font-weight: bold; cursor: pointer; margin-top: 10px;
    }
    button:hover { background: #2980b9; }
    .error {
        background: #ffe6e6; color: #c0392b; padding: 10px;
        border-radius: 6px; font-size: 14px; margin-bottom: 15px;
    }
</style>
</head>
<body>
    <form method="POST" class="login-box">
        <h1>🔐 Login Admin</h1>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" autofocus required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit">Masuk</button>
    </form>
</body>
</html>