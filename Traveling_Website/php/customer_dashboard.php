<?php
session_start();

// Prevent browsers from caching this page (avoids stale/sensitive content on Back button)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

// Route guard: only logged-in customers can view this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors  = [];
$notices = [];

// ---- Handle: cancel a booking (only allowed 10+ days before travel date) ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = (int)$_POST['booking_id'];

    $stmt = $conn->prepare("SELECT travel_date, status FROM bookings WHERE booking_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    if ($res->num_rows === 0) {
        $errors[] = "Booking not found.";
    } else {
        $bk = $res->fetch_assoc();
        $days_until = (strtotime($bk['travel_date']) - strtotime(date('Y-m-d'))) / 86400;

        if ($bk['status'] === 'cancelled') {
            $errors[] = "That booking is already cancelled.";
        } elseif ($days_until < 10) {
            $errors[] = "Bookings can only be cancelled at least 10 days before the travel date.";
        } else {
            $upd = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE booking_id = ? AND user_id = ?");
            $upd->bind_param("ii", $booking_id, $user_id);
            $upd->execute();
            $upd->close();
            $notices[] = "Booking #$booking_id has been cancelled.";
        }
    }
}

$stmt = $conn->prepare(
    "SELECT bookings.*, packages.title, packages.duration_days, destinations.name AS destination_name
     FROM bookings
     JOIN packages ON bookings.package_id = packages.package_id
     JOIN destinations ON packages.destination_id = destinations.destination_id
     WHERE bookings.user_id = ?
     ORDER BY bookings.created_at DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/packages.css">
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo"><img src="../img/logo.png" alt="logo"></div>
            <ul class="nav-links">
                <li><a href="../html/index.html" class="nav-tab-dark">Home</a></li>
                <li><a href="customer_dashboard.php" class="active">My Dashboard</a></li>
                <li><a href="destinations.php">Tour</a></li>
                <li><a href="activities.php">Activities</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </div>
</header>

<div class="container" style="padding: 3rem 0;">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>

    <h2 class="secondary-headings">My Bookings</h2>

    <?php foreach ($notices as $n): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($n); ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <?php if ($bookings->num_rows === 0): ?>
        <p>You haven't booked any tours yet. <a href="packages.php">Browse packages</a> to get started.</p>
    <?php else: ?>
        <div class="table-wrap" style="overflow-x:auto;">
        <table style="width:100%; border-collapse: collapse; min-width:900px;">
            <thead>
                <tr style="text-align:left; border-bottom: 2px solid #ddd;">
                    <th style="padding:0.6rem;">Package</th>
                    <th style="padding:0.6rem;">Destination</th>
                    <th style="padding:0.6rem;">Traveler</th>
                    <th style="padding:0.6rem;">Contact</th>
                    <th style="padding:0.6rem;">Travel Date</th>
                    <th style="padding:0.6rem;">Travelers</th>
                    <th style="padding:0.6rem;">Total</th>
                    <th style="padding:0.6rem;">Status</th>
                    <th style="padding:0.6rem;">Payment</th>
                    <th style="padding:0.6rem;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($b = $bookings->fetch_assoc()): ?>
                    <?php $days_until = (strtotime($b['travel_date']) - strtotime(date('Y-m-d'))) / 86400; ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding:0.6rem;"><?php echo htmlspecialchars($b['title']); ?></td>
                        <td style="padding:0.6rem;"><?php echo htmlspecialchars($b['destination_name']); ?></td>
                        <td style="padding:0.6rem;"><?php echo htmlspecialchars(trim($b['traveler_title'] . ' ' . $b['first_name'] . ' ' . $b['last_name'])); ?></td>
                        <td style="padding:0.6rem; font-size:0.85rem;">
                            <?php echo htmlspecialchars($b['contact_email']); ?><br>
                            <?php echo htmlspecialchars($b['contact_phone']); ?>
                        </td>
                        <td style="padding:0.6rem;"><?php echo htmlspecialchars($b['travel_date']); ?></td>
                        <td style="padding:0.6rem;"><?php echo (int)$b['num_travelers']; ?></td>
                        <td style="padding:0.6rem;">LKR <?php echo number_format($b['total_price'], 0); ?></td>
                        <td style="padding:0.6rem;">
                            <span style="text-transform:capitalize; font-weight:600;
                                color: <?php echo $b['status'] === 'confirmed' ? 'green' : ($b['status'] === 'cancelled' ? 'red' : ($b['status'] === 'contacted' ? '#0b6fa4' : '#b8860b')); ?>;">
                                <?php echo htmlspecialchars($b['status']); ?>
                            </span>
                        </td>
                        <td style="padding:0.6rem;">
                            <?php if ($b['payment_status'] === 'paid'): ?>
                                <span style="color:green; font-weight:600;">Paid</span>
                            <?php elseif ($b['payment_status'] === 'bank_paid'): ?>
                                <span style="color:green; font-weight:600;">Paid (bank transfer)</span>
                            <?php elseif ($b['status'] === 'cancelled'): ?>
                                <span style="color:#999;">&mdash;</span>
                            <?php else: ?>
                                <a href="pay.php?booking_id=<?php echo $b['booking_id']; ?>" class="small-btn" style="background-color:var(--primary-color); color:#fff; padding:0.4rem 0.9rem; border-radius:4px; text-decoration:none; font-size:0.85rem; white-space:nowrap; display:inline-block;">Pay Now</a>
                            <?php endif; ?>
                        </td>
                        <td style="padding:0.6rem;">
                            <?php if ($b['status'] === 'cancelled'): ?>
                                <span style="color:#999;">&mdash;</span>
                            <?php elseif ($days_until >= 10): ?>
                                <form method="POST" class="inline-form" onsubmit="return confirm('Cancel this booking? This cannot be undone.');">
                                    <input type="hidden" name="action" value="cancel_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                                    <button type="submit" class="small-btn" style="background:#e5484d; color:#fff; border:none; padding:0.4rem 0.9rem; border-radius:4px; font-size:0.85rem; cursor:pointer; white-space:nowrap;">Cancel Booking</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#999; font-size:0.8rem;">Cannot cancel<br>(within 10 days)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>

