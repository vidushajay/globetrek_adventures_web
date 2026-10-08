<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box" style="max-width: 480px; text-align:center;">
        <h2 class="secondary-headings">Payment Cancelled</h2>
        <p style="color:#666; margin-bottom:1.5rem;">
            No worries — your booking is still saved. You can try paying again anytime from your dashboard.
        </p>
        <?php if ($booking_id > 0): ?>
            <a href="pay.php?booking_id=<?php echo $booking_id; ?>" class="primary-btn" style="display:inline-block; width:auto; padding:0.6rem 1.5rem; text-decoration:none; margin-right:0.5rem;">Try Again</a>
        <?php endif; ?>
        <a href="customer_dashboard.php" style="color:#888;">Back to Dashboard</a>
    </div>
</div>

</body>
</html>
