<?php
session_start();
require 'db_connect.php';

$errors  = [];
$success = false;

// Pre-fill name/email if the visitor is logged in
$default_name  = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';
$user_id       = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name    = trim($_POST['name']);
    $email   = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if ($name === "" || $email === "" || $message === "") {
        $errors[] = "Name, email, and message are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (strlen($message) < 10) {
        $errors[] = "Please provide a bit more detail in your message (at least 10 characters).";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO queries (user_id, name, email, subject, message, status)
             VALUES (?, ?, ?, ?, ?, 'open')"
        );
        $stmt->bind_param("issss", $user_id, $name, $email, $subject, $message);

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong while sending your message. Please try again.";
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
    <title>Contact Us - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo"><img src="../img/logo.png" alt="logo"></div>
            <ul class="nav-links">
                <li><a href="../html/index.html" class="nav-tab-dark">Home</a></li>
                <?php if ($user_id): ?>
                    <li><a href="<?php echo $_SESSION['role']; ?>_dashboard.php">My Dashboard</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-tab-dark">Login</a></li>
                <?php endif; ?>
                <li><a href="destinations.php">Tour</a></li>
                <li><a href="activities.php">Activities</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php" class="active">Contact</a></li>
                <?php if ($user_id): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </div>
</header>

<div class="auth-container">
    <div class="auth-box" style="max-width: 560px;">
        <h2 class="secondary-headings">Get in Touch</h2>
        <p style="text-align:center; color:#666; margin-bottom:1rem;">
            Have a question about a tour, or need a custom itinerary? Send us a message and our team will get back to you.
        </p>

        <?php if ($success): ?>
            <p class="alert alert-success">
                Thanks<?php echo $name ? ', ' . htmlspecialchars($name) : ''; ?>! Your message has been sent.
                We'll get back to you at <?php echo htmlspecialchars($email); ?> soon.
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

            <form method="POST" action="contact.php" class="auth-form">
                <label for="name">Your Name</label>
                <input type="text" id="name" name="name"
                       value="<?php echo htmlspecialchars(isset($name) ? $name : $default_name); ?>" required>

                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject"
                       placeholder="e.g. Question about Ella Hills Adventure"
                       value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">

                <label for="message">Message</label>
                <textarea id="message" name="message" rows="5" required
                          style="padding:0.7rem; border:1px solid var(--light-gray-color); border-radius:4px; font-family:inherit; font-size:1rem; resize:vertical;"><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>

                <button type="submit" class="primary-btn">Send Message</button>
            </form>

        <?php endif; ?>
    </div>
</div>

<script src="../js/script.js"></script>
</body>
</html>
