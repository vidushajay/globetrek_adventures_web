<?php
session_start();

// Prevent browsers from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';
require 'payhere_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id    = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

// Fetch the booking - must belong to this customer
$stmt = $conn->prepare(
    "SELECT bookings.*, packages.title, users.full_name, users.email
     FROM bookings
     JOIN packages ON bookings.package_id = packages.package_id
     JOIN users ON bookings.user_id = users.user_id
     WHERE bookings.booking_id = ? AND bookings.user_id = ?"
);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}
$booking = $result->fetch_assoc();
$stmt->close();

if ($booking['payment_status'] === 'paid' || $booking['payment_status'] === 'bank_paid') {
    header("Location: customer_dashboard.php");
    exit();
}

if ($booking['status'] === 'cancelled') {
    die("This booking has been cancelled and can no longer be paid for.");
}

// Generate a fresh unique order id for this payment attempt
$order_id = "BOOK" . $booking_id . "-" . time();
$stmt = $conn->prepare("UPDATE bookings SET payhere_order_id = ? WHERE booking_id = ?");
$stmt->bind_param("si", $order_id, $booking_id);
$stmt->execute();
$stmt->close();

// ---- PayHere required fields ----
$amount   = number_format((float)$booking['total_price'], 2, '.', '');
$currency = "LKR";

// PayHere's required hash — proves the request wasn't tampered with
$hashed_secret = strtoupper(md5($payhere_merchant_secret));
$hash = strtoupper(
    md5($payhere_merchant_id . $order_id . $amount . $currency . $hashed_secret)
);

// Split full name for PayHere's first/last name fields
$name_parts = explode(" ", $booking['full_name'], 2);
$first_name = $name_parts[0];
$last_name  = isset($name_parts[1]) ? $name_parts[1] : "";

// These return/cancel/notify URLs must be full, absolute URLs for PayHere to redirect/call correctly.
$base_url    = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$return_url  = $base_url . "/payment_success.php?booking_id=" . $booking_id;
$cancel_url  = $base_url . "/payment_cancel.php?booking_id=" . $booking_id;
$notify_url  = $base_url . "/payment_notify.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay for Booking - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<div class="auth-container">
    <div class="auth-box" style="max-width: 480px;">
        <h2 class="secondary-headings">Confirm Payment</h2>
        <p style="text-align:center; color:#555; margin-bottom:1.5rem;">
            <?php echo htmlspecialchars($booking['title']); ?><br>
            Travel date: <?php echo htmlspecialchars($booking['travel_date']); ?><br>
            Travelers: <?php echo (int)$booking['num_travelers']; ?>
        </p>
        <p style="text-align:center; font-size:1.6rem; font-weight:700; color:var(--blue-color); margin-bottom:1.5rem;">
            LKR <?php echo number_format($booking['total_price'], 0); ?>
        </p>

        <form method="POST" action="<?php echo htmlspecialchars($payhere_checkout_url); ?>">
            <input type="hidden" name="merchant_id" value="<?php echo htmlspecialchars($payhere_merchant_id); ?>">
            <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($return_url); ?>">
            <input type="hidden" name="cancel_url" value="<?php echo htmlspecialchars($cancel_url); ?>">
            <input type="hidden" name="notify_url" value="<?php echo htmlspecialchars($notify_url); ?>">

            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
            <input type="hidden" name="items" value="<?php echo htmlspecialchars($booking['title']); ?>">
            <input type="hidden" name="currency" value="<?php echo $currency; ?>">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">

            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($booking['email']); ?>">
            <input type="hidden" name="phone" value="0770000000">
            <input type="hidden" name="address" value="No address provided">
            <input type="hidden" name="city" value="Colombo">
            <input type="hidden" name="country" value="Sri Lanka">

            <input type="hidden" name="hash" value="<?php echo $hash; ?>">

            <button type="submit" class="primary-btn">Pay Securely with PayHere</button>
        </form>
        <p style="text-align:center; margin-top:1rem;">
            <a href="customer_dashboard.php" style="color:#888;">Cancel and go back</a>
        </p>
    </div>
</div>

</body>
</html>
