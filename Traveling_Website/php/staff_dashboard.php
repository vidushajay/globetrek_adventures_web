<?php
session_start();

// Prevent browsers from caching this page (avoids stale/sensitive content on Back button)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit();
}

$staff_id = $_SESSION['user_id'];
$errors  = [];
$notices = [];

// ---- Hotels / transport providers + per-booking arrangements ----
require 'providers_handle.php';

// ---- Handle booking status updates ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_booking') {
    $booking_id  = (int)$_POST['booking_id'];
    $new_status  = $_POST['new_status'];
    $call_notes  = mb_substr(trim($_POST['call_notes'] ?? ''), 0, 1000);
    $new_payment = $_POST['new_payment'] ?? '';
    if (!in_array($new_payment, ['unpaid', 'bank_paid'], true)) { $new_payment = ''; }

    $allowed_statuses = ['pending', 'contacted', 'confirmed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        $errors[] = "Invalid status value.";
    } else {
        $stmt = $conn->prepare("UPDATE bookings SET status = ?, confirmed_by = ?, call_notes = ?,
                                payment_status = IF(payment_status = 'paid' OR ? = '', payment_status, ?)
                                WHERE booking_id = ?");
        $stmt->bind_param("sisssi", $new_status, $staff_id, $call_notes, $new_payment, $new_payment, $booking_id);
        if ($stmt->execute()) {
            $notices[] = "Booking #$booking_id updated to '$new_status'.";
        } else {
            $errors[] = "Failed to update booking #$booking_id.";
        }
        $stmt->close();
    }
}

// ---- Handle package edits ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_package') {
    $package_id  = (int)$_POST['package_id'];
    $price       = (float)$_POST['price'];
    $description = trim($_POST['description']);

    if ($price <= 0) {
        $errors[] = "Price must be greater than 0.";
    } elseif ($description === "") {
        $errors[] = "Description cannot be empty.";
    } else {
        $stmt = $conn->prepare("UPDATE packages SET price = ?, description = ? WHERE package_id = ?");
        $stmt->bind_param("dsi", $price, $description, $package_id);
        if ($stmt->execute()) {
            $notices[] = "Package #$package_id updated.";
        } else {
            $errors[] = "Failed to update package #$package_id.";
        }
        $stmt->close();
    }
}

// ---- Handle query status updates ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_query') {
    $query_id   = (int)$_POST['query_id'];
    $new_status = $_POST['new_status'];

    $allowed_statuses = ['open', 'answered', 'closed'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        $errors[] = "Invalid status value.";
    } else {
        $stmt = $conn->prepare("UPDATE queries SET status = ?, handled_by = ? WHERE query_id = ?");
        $stmt->bind_param("sii", $new_status, $staff_id, $query_id);
        if ($stmt->execute()) {
            $notices[] = "Query #$query_id marked as '$new_status'.";
        } else {
            $errors[] = "Failed to update query #$query_id.";
        }
        $stmt->close();
    }
}

// ---- Fetch all bookings (with customer + package info) ----
$bookings = $conn->query(
    "SELECT bookings.*, users.full_name AS customer_name, packages.title
     FROM bookings
     JOIN users ON bookings.user_id = users.user_id
     JOIN packages ON bookings.package_id = packages.package_id
     ORDER BY bookings.status = 'pending' DESC, bookings.created_at DESC"
);

// ---- Fetch all packages ----
$packages = $conn->query(
    "SELECT packages.*, destinations.name AS destination_name
     FROM packages
     JOIN destinations ON packages.destination_id = destinations.destination_id
     ORDER BY destinations.name"
);

// ---- Fetch all queries (open ones first) ----
$queries = $conn->query(
    "SELECT * FROM queries
     ORDER BY status = 'open' DESC, created_at DESC"
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css?v=2">
</head>
<body>

<div class="container dashboard">
    <h1>Staff Dashboard</h1>
    <p class="dashboard-sub">
        Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> &middot;
        <a href="../html/index.html">View Site</a> &middot;
        <a href="logout.php">Logout</a>
    </p>

    <?php foreach ($notices as $n): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($n); ?></div>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <!-- ================= BOOKINGS ================= -->
    <h2 class="secondary-headings">Manage Bookings</h2>
    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr>
                <th>#</th><th>Customer</th><th>Traveler &amp; Contact</th><th>Package</th><th>Travel Date</th>
                <th>Travelers</th><th>Total</th><th>Payment</th><th>Status</th><th>Call Notes</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($b = $bookings->fetch_assoc()): ?>
            <tr>
                <td><?php echo $b['booking_id']; ?></td>
                <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                <td style="min-width:200px; font-size:0.85rem; overflow-wrap:break-word;">
                    <strong><?php echo htmlspecialchars(trim($b['traveler_title'] . ' ' . $b['first_name'] . ' ' . $b['last_name'])); ?></strong><br>
                    <?php if ($b['contact_phone'] !== ''): ?>&#128222; <a href="tel:<?php echo htmlspecialchars($b['contact_phone']); ?>"><?php echo htmlspecialchars($b['contact_phone']); ?></a><br><?php endif; ?>
                    <?php if ($b['contact_email'] !== ''): ?>&#9993; <a href="mailto:<?php echo htmlspecialchars($b['contact_email']); ?>"><?php echo htmlspecialchars($b['contact_email']); ?></a><?php endif; ?>
                    <?php if (!empty($b['additional_info'])): ?><br><small style="color:#666;">Note: <?php echo htmlspecialchars($b['additional_info']); ?></small><?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($b['title']); ?></td>
                <td><?php echo htmlspecialchars($b['travel_date']); ?></td>
                <td><?php echo (int)$b['num_travelers']; ?></td>
                <td>LKR <?php echo number_format($b['total_price'], 0); ?></td>
                <td style="min-width:150px;">
                    <?php if ($b['payment_status'] === 'paid'): ?>
                        <span style="color:green;font-weight:600;">Paid online</span>
                    <?php else: ?>
                        <?php if ($b['payment_status'] === 'bank_paid'): ?>
                            <span style="color:green;font-weight:600;">Bank transfer - paid</span>
                        <?php else: ?>
                            <span style="color:#b8860b;font-weight:600;">Unpaid</span>
                        <?php endif; ?>
                        <br>
                        <select name="new_payment" form="booking-form-<?php echo $b['booking_id']; ?>" style="margin-top:0.3rem; font-size:0.7rem; padding:0.3rem 0; width:100%;">
                            <option value="unpaid"    <?php echo $b['payment_status'] === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="bank_paid" <?php echo $b['payment_status'] === 'bank_paid' ? 'selected' : ''; ?>>Bank transfer - paid</option>
                        </select>
                    <?php endif; ?>
                </td>
                <td><span class="status-badge status-<?php echo $b['status']; ?>"><?php echo htmlspecialchars($b['status']); ?></span></td>
                <td style="min-width:150px;">
                    <textarea name="call_notes" form="booking-form-<?php echo $b['booking_id']; ?>" rows="3" maxlength="1000" placeholder="Call notes / follow-up..." style="width:100%; font-size:0.8rem; padding:0.3rem;"><?php echo htmlspecialchars($b['call_notes'] ?? ''); ?></textarea>
                </td>
                <td>
                    <form method="POST" class="inline-form booking-action-form" id="booking-form-<?php echo $b['booking_id']; ?>">
                        <input type="hidden" name="action" value="update_booking">
                        <input type="hidden" name="booking_id" value="<?php echo $b['booking_id']; ?>">
                        <select name="new_status">
                            <option value="pending"   <?php echo $b['status'] === 'pending' ? 'selected' : ''; ?>>Pending (awaiting call)</option>
                            <option value="contacted" <?php echo $b['status'] === 'contacted' ? 'selected' : ''; ?>>Contacted</option>
                            <option value="confirmed" <?php echo $b['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $b['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="small-btn">Update</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>

    <?php require 'providers_section.php'; ?>

    <!-- ================= PACKAGES ================= -->
    <h2 class="secondary-headings">Manage Packages</h2>
    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr><th>Destination</th><th>Title</th><th>Description</th><th>Price</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($p = $packages->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($p['destination_name']); ?></td>
                <td><?php echo htmlspecialchars($p['title']); ?></td>
                <td>
                    <form method="POST" class="inline-form package-edit-form">
                        <input type="hidden" name="action" value="update_package">
                        <input type="hidden" name="package_id" value="<?php echo $p['package_id']; ?>">
                        <textarea name="description" rows="2"><?php echo htmlspecialchars($p['description']); ?></textarea>
                </td>
                <td>
                        <input type="number" step="0.01" min="0" name="price" value="<?php echo htmlspecialchars($p['price']); ?>">
                </td>
                <td>
                        <button type="submit" class="small-btn">Save</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>

    <!-- ================= QUERIES ================= -->
    <h2 class="secondary-headings">Customer Queries</h2>
    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr><th>#</th><th>From</th><th>Subject</th><th>Message</th><th>Received</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if ($queries->num_rows === 0): ?>
            <tr><td colspan="7">No queries yet.</td></tr>
        <?php endif; ?>
        <?php while ($q = $queries->fetch_assoc()): ?>
            <tr>
                <td><?php echo $q['query_id']; ?></td>
                <td>
                    <?php echo htmlspecialchars($q['name']); ?><br>
                    <small><?php echo htmlspecialchars($q['email']); ?></small>
                </td>
                <td><?php echo htmlspecialchars($q['subject']); ?></td>
                <td style="max-width:280px;"><?php echo nl2br(htmlspecialchars($q['message'])); ?></td>
                <td><?php echo htmlspecialchars($q['created_at']); ?></td>
                <td><span class="status-badge status-<?php echo $q['status'] === 'open' ? 'pending' : ($q['status'] === 'answered' ? 'confirmed' : 'cancelled'); ?>"><?php echo htmlspecialchars($q['status']); ?></span></td>
                <td>
                    <form method="POST" class="inline-form">
                        <input type="hidden" name="action" value="update_query">
                        <input type="hidden" name="query_id" value="<?php echo $q['query_id']; ?>">
                        <select name="new_status">
                            <option value="open"     <?php echo $q['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="answered" <?php echo $q['status'] === 'answered' ? 'selected' : ''; ?>>Answered</option>
                            <option value="closed"   <?php echo $q['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                        <button type="submit" class="small-btn">Update</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

</body>
</html>

