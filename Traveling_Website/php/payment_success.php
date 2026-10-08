<?php
session_start();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

$stmt = $conn->prepare(
    "SELECT bookings.*, packages.title
     FROM bookings
     JOIN packages ON bookings.package_id = packages.package_id
     WHERE bookings.booking_id = ? AND bookings.user_id = ?"
);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box" style="max-width: 480px; text-align:center;">
        <?php if ($booking && $booking['payment_status'] === 'paid'): ?>
            <h2 class="secondary-headings">Payment Successful</h2>
            <p class="alert alert-success">
                Your payment for <strong><?php echo htmlspecialchars($booking['title']); ?></strong> was received.
            </p>
        <?php else: ?>
            <h2 class="secondary-headings">Payment Received</h2>
            <p class="alert alert-success">
                Thanks! We're confirming your payment with PayHere now — this usually only takes a few seconds.
                Check your dashboard shortly; if it still shows unpaid after a minute, the confirmation call may not have reached the server (common on localhost without a public URL — see notes).
            </p>
        <?php endif; ?>
        <a href="customer_dashboard.php" class="primary-btn" style="display:inline-block; width:auto; padding:0.6rem 1.5rem; text-decoration:none; margin-top:1rem;">Go to Dashboard</a>
    </div>
</div>

</body>
</html>
