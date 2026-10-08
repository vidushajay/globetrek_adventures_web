<?php
// Prevent browsers from caching this page (avoids stale/sensitive content on Back button)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

$errors = [];
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $confirm   = $_POST['confirm_password'];

    // ---- Validation ----
    if ($full_name === "" || $email === "" || $password === "") {
        $errors[] = "Name, email, and password are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email is already registered
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        }
        $stmt->close();
    }

    // ---- Insert new customer ----
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, 'customer')"
        );
        $stmt->bind_param("ssss", $full_name, $email, $password_hash, $phone);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong. Please try again.";
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
    <title>Register - GlobeTrek Adventures</title>
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
        <h2 class="secondary-headings">Create an Account</h2>

        <?php if ($success): ?>
            <p class="alert alert-success">
                Registration successful! You can now
                <a href="login.php">log in</a>.
            </p>
        <?php else: ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php" class="auth-form">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

                <label for="phone">Phone</label>
                <input type="text" id="phone" name="phone"
                       value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>

                <button type="submit" class="primary-btn">Register</button>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>

        <?php endif; ?>
    </div>
</div>

</body>
</html>
