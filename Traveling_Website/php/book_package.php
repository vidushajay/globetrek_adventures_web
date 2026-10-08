<?php
session_start();

// Prevent browsers from caching this page (avoids stale/sensitive content on Back button)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require 'db_connect.php';

// ---- Route guard: must be logged in as a customer ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit();
}

$errors  = [];
$success = false;

// ---- Get the package being booked ----
$package_id = isset($_GET['package_id']) ? (int)$_GET['package_id'] : 0;
if (isset($_POST['package_id'])) {
    $package_id = (int)$_POST['package_id']; // keep the same package on form re-submit
}

$stmt = $conn->prepare(
    "SELECT packages.*, destinations.name AS destination_name
     FROM packages
     JOIN destinations ON packages.destination_id = destinations.destination_id
     WHERE packages.package_id = ?"
);
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Package not found.");
}
$package = $result->fetch_assoc();
$stmt->close();

// ---- Handle booking submission ----
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $travel_date    = trim($_POST['travel_date']);
    $num_travelers  = (int)$_POST['num_travelers'];
    $traveler_title = $_POST['traveler_title'] ?? '';
    $first_name     = trim($_POST['first_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $contact_email  = trim($_POST['contact_email'] ?? '');
    $contact_phone  = trim($_POST['contact_phone'] ?? '');
    $additional_info = trim($_POST['additional_info'] ?? '');

    $today = date('Y-m-d');

    if ($travel_date === "" || $travel_date < $today) {
        $errors[] = "Please choose a valid travel date (today or later).";
    }

    if ($num_travelers < 1) {
        $errors[] = "Number of travelers must be at least 1.";
    } elseif ($num_travelers > $package['max_travelers']) {
        $errors[] = "This package allows a maximum of " . $package['max_travelers'] . " travelers.";
    }

    if (!in_array($traveler_title, ['Mr', 'Mrs', 'Ms', 'Dr'], true)) {
        $errors[] = "Please select a title.";
    }

    if ($first_name === "" || $last_name === "") {
        $errors[] = "Please enter both first and last name.";
    }

    if ($contact_email === "" || !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid contact email.";
    }

    if ($contact_phone === "" || !preg_match('/^[0-9+\-\s]{7,20}$/', $contact_phone)) {
        $errors[] = "Please enter a valid contact phone number.";
    }

    if (empty($errors)) {
        $total_price = $package['price'] * $num_travelers;
        $user_id = $_SESSION['user_id'];

        $stmt = $conn->prepare(
            "INSERT INTO bookings (user_id, package_id, travel_date, num_travelers, total_price, status, traveler_title, first_name, last_name, contact_email, contact_phone, additional_info)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "iisidssssss",
            $user_id, $package_id, $travel_date, $num_travelers, $total_price,
            $traveler_title, $first_name, $last_name, $contact_email, $contact_phone,
            $additional_info
        );

        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Something went wrong while creating your booking. Please try again.";
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
    <title>Book <?php echo htmlspecialchars($package['title']); ?> - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/auth.css">
    <link rel="stylesheet" href="../css/packages.css">
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo"><img src="../img/logo.png" alt="logo"></div>
            <ul class="nav-links">
                <li><a href="../html/index.html" class="nav-tab-dark">Home</a></li>
                <li><a href="customer_dashboard.php">My Dashboard</a></li>
                <li><a href="destinations.php">Tour</a></li>
                <li><a href="activities.php">Activities</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </div>
</header>

<main class="packages-page">
    <div class="container">
        <div class="auth-box" style="max-width: 600px; margin: 2rem auto;">

            <?php if ($success): ?>
                <h2 class="secondary-headings">Booking Requested!</h2>
                <p class="alert alert-success">
                    Your booking for <strong><?php echo htmlspecialchars($package['title']); ?></strong>
                    is <strong>pending confirmation</strong> from our staff. You can track its status from your dashboard.
                </p>
                <a href="customer_dashboard.php" class="primary-btn" style="display:inline-block; width:auto; padding:0.6rem 1.5rem; text-decoration:none;">Go to Dashboard</a>

            <?php else: ?>

                <h2 class="secondary-headings">Book: <?php echo htmlspecialchars($package['title']); ?></h2>
                <p><?php echo htmlspecialchars($package['destination_name']); ?> &middot;
                   <?php echo (int)$package['duration_days']; ?> day(s) &middot;
                   LKR <?php echo number_format($package['price'], 0); ?> per person</p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="book_package.php" class="auth-form">
                    <input type="hidden" name="package_id" value="<?php echo $package_id; ?>">

                    <label>Traveler Details</label>
                    <div style="display:flex; gap:0.8rem; flex-wrap:wrap;">
                        <div style="flex:0 0 100px;">
                            <select name="traveler_title" required style="width:100%; padding:0.7rem; border:1px solid var(--light-gray-color); border-radius:4px; font-family:inherit; font-size:1rem;">
                                <option value="">Title</option>
                                <?php foreach (['Mr', 'Mrs', 'Ms', 'Dr'] as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo (isset($_POST['traveler_title']) && $_POST['traveler_title'] === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div style="flex:1 1 180px;">
                            <input type="text" name="first_name" placeholder="First Name"
                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                   style="width:100%; padding:0.7rem; border:1px solid var(--light-gray-color); border-radius:4px; font-family:inherit; font-size:1rem;" required>
                        </div>
                        <div style="flex:1 1 180px;">
                            <input type="text" name="last_name" placeholder="Last Name"
                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                   style="width:100%; padding:0.7rem; border:1px solid var(--light-gray-color); border-radius:4px; font-family:inherit; font-size:1rem;" required>
                        </div>
                    </div>

                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email"
                           placeholder="you@example.com"
                           value="<?php echo isset($_POST['contact_email']) ? htmlspecialchars($_POST['contact_email']) : htmlspecialchars($_SESSION['email'] ?? ''); ?>"
                           required>

                    <label for="contact_phone">Contact Phone</label>
                    <input type="tel" id="contact_phone" name="contact_phone"
                           placeholder="e.g. 0771234567"
                           value="<?php echo isset($_POST['contact_phone']) ? htmlspecialchars($_POST['contact_phone']) : ''; ?>"
                           required>

                    <label for="additional_info">Additional Info (Optional)</label>
                    <textarea id="additional_info" name="additional_info" rows="3"
                              placeholder="Any special requests or notes for your trip"
                              style="padding:0.7rem; border:1px solid var(--light-gray-color); border-radius:4px; font-family:inherit; font-size:1rem; resize:vertical;"><?php echo isset($_POST['additional_info']) ? htmlspecialchars($_POST['additional_info']) : ''; ?></textarea>

                    <label for="travel_date">Travel Date</label>
                    <input type="date" id="travel_date" name="travel_date"
                           min="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo isset($_POST['travel_date']) ? htmlspecialchars($_POST['travel_date']) : ''; ?>"
                           required>

                    <label for="num_travelers">Number of Travelers (max <?php echo (int)$package['max_travelers']; ?>)</label>
                    <input type="number" id="num_travelers" name="num_travelers" min="1"
                           max="<?php echo (int)$package['max_travelers']; ?>"
                           value="<?php echo isset($_POST['num_travelers']) ? htmlspecialchars($_POST['num_travelers']) : '1'; ?>"
                           required>

                    <button type="submit" class="primary-btn">Confirm Booking</button>
                </form>

            <?php endif; ?>

        </div>
    </div>
</main>

<script src="../js/script.js"></script>
</body>
</html>
