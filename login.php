<?php
declare(strict_types=1);

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auth.php';

if (is_authenticated()) {
    header('Location: display.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $statement = $conn->pdo()->prepare('SELECT id, email, password FROM users WHERE email = ?');
    $statement->execute([$email]);
    $user = $statement->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_email'] = $user['email'];
        header('Location: display.php');
        exit();
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HU PC Checkup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow p-4" style="max-width: 430px; width: 100%;">
            <h1 class="h3 mb-4 text-center">HU PC Checkup Login</h1>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label class="form-label" for="email">Email</label>
                <input class="form-control mb-3" id="email" name="email" type="email" required autofocus>
                <label class="form-label" for="password">Password</label>
                <input class="form-control mb-4" id="password" name="password" type="password" required>
                <button class="btn btn-primary w-100" type="submit">Log in</button>
            </form>
            <a class="btn btn-link mt-2" href="home.php">Back to home</a>
        </div>
    </main>
</body>
</html>
