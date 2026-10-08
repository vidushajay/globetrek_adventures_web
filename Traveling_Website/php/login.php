<?php
session_start();

// Prevent browsers from caching this page (avoids stale/sensitive content on Back button)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === "" || $password === "") {
        $errors[] = "Please enter both email and password.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {

                // Credentials correct — start the session
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role']      = $user['role'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        header("Location: admin_dashboard.php");
                        break;
                    case 'staff':
                        header("Location: staff_dashboard.php");
                        break;
                    default:
                        header("Location: customer_dashboard.php");
                        break;
                }
                exit();

            } else {
                $errors[] = "Incorrect email or password.";
            }
        } else {
            $errors[] = "Incorrect email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box">
        <p style="text-align:center; margin-bottom:1rem;">
            <a href="../html/index.html" style="color:var(--primary-color); font-weight:600; text-decoration:none;">&larr; Back to Home</a>
        </p>
        <h2 class="secondary-headings">Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <label for="email">Email</label>
            <input type="email" id="email" name="email"
                   value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="primary-btn">Login</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>

</body>
</html>
