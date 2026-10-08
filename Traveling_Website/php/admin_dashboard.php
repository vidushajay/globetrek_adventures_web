<?php
session_start();

// Prevent browsers from caching this page (avoids stale/sensitive content on Back button)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$errors  = [];
$notices = [];

// ---- Handle: create a new staff account ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'create_staff') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = $_POST['password'];
    $role      = $_POST['role']; // 'staff' or 'admin'

    if ($full_name === "" || $email === "" || $password === "") {
        $errors[] = "All fields are required to create a staff/admin account.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    } elseif (!in_array($role, ['staff', 'admin'], true)) {
        $errors[] = "Invalid role selected.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "An account with this email already exists.";
        }
        $stmt->close();

        if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $full_name, $email, $password_hash, $role);
            if ($stmt->execute()) {
                $notices[] = ucfirst($role) . " account created for $full_name.";
            } else {
                $errors[] = "Failed to create account.";
            }
            $stmt->close();
        }
    }
}

// ---- Handle: delete a user ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $target_user_id = (int)$_POST['user_id'];

    if ($target_user_id === $admin_id) {
        $errors[] = "You cannot delete your own account.";
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $target_user_id);
        if ($stmt->execute()) {
            $notices[] = "User #$target_user_id has been deleted.";
        } else {
            $errors[] = "Failed to delete user.";
        }
        $stmt->close();
    }
}

// ---- Handle: change a booking's status ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_booking') {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = $_POST['new_status'];
    $call_notes = mb_substr(trim($_POST['call_notes'] ?? ''), 0, 1000);
    $new_payment = $_POST['new_payment'] ?? '';
    if (!in_array($new_payment, ['unpaid', 'bank_paid'], true)) { $new_payment = ''; }

    $allowed_statuses = ['pending', 'contacted', 'confirmed', 'cancelled'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        $errors[] = "Invalid status value.";
    } else {
        $stmt = $conn->prepare("UPDATE bookings SET status = ?, confirmed_by = ?, call_notes = ?,
                                payment_status = IF(payment_status = 'paid' OR ? = '', payment_status, ?)
                                WHERE booking_id = ?");
        $stmt->bind_param("sisssi", $new_status, $admin_id, $call_notes, $new_payment, $new_payment, $booking_id);
        if ($stmt->execute()) {
            $notices[] = "Booking #$booking_id updated to '$new_status'.";
        } else {
            $errors[] = "Failed to update booking #$booking_id.";
        }
        $stmt->close();
    }
}

// ---- Handle: change a query's status ----
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_query') {
    $query_id   = (int)$_POST['query_id'];
    $new_status = $_POST['new_status'];

    $allowed_statuses = ['open', 'answered', 'closed'];
    if (!in_array($new_status, $allowed_statuses, true)) {
        $errors[] = "Invalid status value.";
    } else {
        $stmt = $conn->prepare("UPDATE queries SET status = ?, handled_by = ? WHERE query_id = ?");
        $stmt->bind_param("sii", $new_status, $admin_id, $query_id);
        if ($stmt->execute()) {
            $notices[] = "Query #$query_id marked as '$new_status'.";
        } else {
            $errors[] = "Failed to update query #$query_id.";
        }
        $stmt->close();
    }
}

// ---- Sales report ----
$report = $conn->query(
    "SELECT
        COUNT(*) AS total_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'contacted' THEN 1 ELSE 0 END) AS contacted_count,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
        SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) AS confirmed_revenue
     FROM bookings"
)->fetch_assoc();

$total_customers = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'")->fetch_assoc()['c'];

// ---- All users ----
$users = $conn->query("SELECT user_id, full_name, email, role, created_at FROM users ORDER BY role, full_name");

// ---- All bookings (optionally filtered by status via ?filter=) ----
$allowed_filters = ['all', 'pending', 'contacted', 'confirmed', 'cancelled'];
$booking_filter = isset($_GET['filter']) && in_array($_GET['filter'], $allowed_filters, true) ? $_GET['filter'] : 'all';

$bookings_sql = "SELECT bookings.*, users.full_name AS customer_name, packages.title
     FROM bookings
     JOIN users ON bookings.user_id = users.user_id
     JOIN packages ON bookings.package_id = packages.package_id";
if ($booking_filter !== 'all') {
    $bookings_sql .= " WHERE bookings.status = '" . $conn->real_escape_string($booking_filter) . "'";
}
$bookings_sql .= " ORDER BY bookings.created_at DESC";
$bookings = $conn->query($bookings_sql);

// ---- All queries ----
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
    <title>Admin Dashboard - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css?v=2">
</head>
<body>

<div class="container dashboard">
    <h1>Admin Dashboard</h1>
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

    <!-- ================= SALES REPORT ================= -->
    <h2 class="secondary-headings">Sales Report</h2>
    <div class="report-grid">
        <a href="admin_dashboard.php?filter=confirmed#bookings-section" class="report-card">
            <span class="report-value">LKR <?php echo number_format($report['confirmed_revenue'] ?? 0, 0); ?></span>
            <span class="report-label">Confirmed Revenue</span>
        </a>
        <a href="admin_dashboard.php?filter=all#bookings-section" class="report-card">
            <span class="report-value"><?php echo (int)$report['total_bookings']; ?></span>
            <span class="report-label">Total Bookings</span>
        </a>
        <a href="admin_dashboard.php?filter=confirmed#bookings-section" class="report-card">
            <span class="report-value"><?php echo (int)$report['confirmed_count']; ?></span>
            <span class="report-label">Confirmed</span>
        </a>
        <a href="admin_dashboard.php?filter=pending#bookings-section" class="report-card">
            <span class="report-value"><?php echo (int)$report['pending_count']; ?></span>
            <span class="report-label">Pending (awaiting call)</span>
        </a>
        <a href="admin_dashboard.php?filter=contacted#bookings-section" class="report-card">
            <span class="report-value"><?php echo (int)$report['contacted_count']; ?></span>
            <span class="report-label">Contacted</span>
        </a>
        <a href="admin_dashboard.php?filter=cancelled#bookings-section" class="report-card">
            <span class="report-value"><?php echo (int)$report['cancelled_count']; ?></span>
            <span class="report-label">Cancelled</span>
        </a>
        <a href="#users-section" class="report-card">
            <span class="report-value"><?php echo (int)$total_customers; ?></span>
            <span class="report-label">Registered Customers</span>
        </a>
    </div>

    <!-- ================= CREATE STAFF/ADMIN ================= -->
    <h2 class="secondary-headings">Create Staff / Admin Account</h2>
    <form method="POST" class="dashboard-form">
        <input type="hidden" name="action" value="create_staff">

        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Temporary Password</label>
        <input type="password" id="password" name="password" required>

        <label for="role">Role</label>
        <select id="role" name="role">
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit" class="primary-btn small-btn" style="font-size:1rem; padding:0.7rem 1.8rem;">Create Account</button>
    </form>

    <!-- ================= MANAGE USERS ================= -->
    <h2 class="secondary-headings" id="users-section">All Users</h2>
    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo $u['user_id']; ?></td>
                <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><span class="status-badge status-<?php echo $u['role'] === 'admin' ? 'confirmed' : ($u['role'] === 'staff' ? 'pending' : ''); ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                <td>
                    <?php if ($u['user_id'] == $admin_id): ?>
                        <em>(you)</em>
                    <?php else: ?>
                        <form method="POST" class="inline-form" onsubmit="return confirm('Delete <?php echo htmlspecialchars(addslashes($u['full_name'])); ?>? This will also remove their bookings. This cannot be undone.');">
                            <input type="hidden" name="action" value="delete_user">
                            <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                            <button type="submit" class="small-btn" style="background:#e5484d;">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>

    <!-- ================= ALL BOOKINGS ================= -->
    <h2 class="secondary-headings" id="bookings-section">All Bookings</h2>
    <?php if ($booking_filter !== 'all'): ?>
        <p class="filter-indicator">
            Showing: <strong><?php echo htmlspecialchars(ucfirst($booking_filter)); ?></strong> bookings &middot;
            <a href="admin_dashboard.php?filter=all#bookings-section">Clear filter</a>
        </p>
    <?php endif; ?>
    <div class="table-wrap">
    <table class="dash-table">
        <thead>
            <tr><th>#</th><th>Customer</th><th>Traveler &amp; Contact</th><th>Package</th><th>Travel Date</th><th>Travelers</th><th>Total</th><th>Payment</th><th>Status</th><th>Call Notes</th><th>Action</th></tr>
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
                    <form method="POST" class="inline-form booking-action-form" id="booking-form-<?php echo $b['booking_id']; ?>" action="admin_dashboard.php?filter=<?php echo urlencode($booking_filter); ?>#bookings-section">
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
