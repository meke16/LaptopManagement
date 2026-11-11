<?php
declare(strict_types=1);

require_once __DIR__ . '/connect.php';
require_once __DIR__ . '/auth.php';
require_auth();

$error = '';
$success = '';
$currentUser = $conn->pdo()->prepare('SELECT id, username FROM users WHERE id = ?');
$currentUser->execute([(int)$_SESSION['user_id']]);
$user = $currentUser->fetch();

if (!$user) {
    session_unset();
    header('Location: login.php');
    exit();
}

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['username'] = $user['username'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        $error = 'Username must be 3-50 characters and use only letters, numbers, dots, underscores, or hyphens.';
    } elseif ($password !== '' && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $conn->pdo()->beginTransaction();
            if ($password === '') {
                $statement = $conn->pdo()->prepare('UPDATE users SET username = ? WHERE id = ?');
                $statement->execute([$username, $user['id']]);
            } else {
                $statement = $conn->pdo()->prepare('UPDATE users SET username = ?, password = ? WHERE id = ?');
                $statement->execute([$username, password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            if ($statement->rowCount() === 0 && $username !== $user['username']) {
                throw new RuntimeException('The current user account could not be updated.');
            }
            $conn->pdo()->commit();
            $_SESSION['username'] = $username;
            $success = 'Profile updated successfully.';
        } catch (Throwable $exception) {
            if ($conn->pdo()->inTransaction()) {
                $conn->pdo()->rollBack();
            }
            $error = $exception instanceof PDOException && strpos(strtolower($exception->getMessage()), 'unique') !== false
                ? 'That username is already in use.'
                : 'Unable to update the current profile.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - HU PC Checkup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center">
        <div class="card shadow p-4" style="max-width: 500px; width: 100%;">
            <h1 class="h3 mb-4">Edit Profile</h1>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <form method="post" autocomplete="off">
                <label class="form-label" for="username">Username</label>
                <input class="form-control mb-3" id="username" name="username" type="text" value="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>" required>
                <label class="form-label" for="password">New password</label>
                <input class="form-control mb-3" id="password" name="password" type="password" minlength="6">
                <label class="form-label" for="confirm_password">Confirm new password</label>
                <input class="form-control mb-4" id="confirm_password" name="confirm_password" type="password" minlength="6">
                <button class="btn btn-primary" type="submit">Save changes</button>
                <a class="btn btn-secondary" href="display.php">Cancel</a>
            </form>
        </div>
    </main>
</body>
</html>
