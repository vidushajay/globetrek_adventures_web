<?php
session_start();
require '../php/db_connect.php';

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// ---- Read search inputs ----
$search_destination = isset($_GET['destination']) ? (int)$_GET['destination'] : 0;

// ---- Get all destinations for the filter dropdown ----
$destinations = $conn->query("SELECT destination_id, name FROM destinations ORDER BY name");

// ---- Build the packages query dynamically but safely (prepared statement) ----
$sql = "SELECT packages.*, destinations.name AS destination_name
        FROM packages
        JOIN destinations ON packages.destination_id = destinations.destination_id
        WHERE 1=1";

$params = [];
$types  = "";

if ($search_destination > 0) {
    $sql .= " AND packages.destination_id = ?";
    $params[] = $search_destination;
    $types   .= "i";
}

$sql .= " ORDER BY packages.destination_id, packages.price";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$packages = $stmt->get_result();

// A representative thumbnail image for each destination folder
$destination_thumbnails = [
    'Colombo'  => 'Independence-Square-800-x-500.jpg',
    'Galle'    => 'Dutch_fort.jpg',
    'Ella'     => 'Ravana-Ella-falls.jpg',
    'Dambulla' => 'Dambulla-Cave-Temple-800-x-500.jpg',
    'Hatton'   => 'Adams_peak_sunrise.jpg',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/packages.css">
</head>
<body>

<header>
    <div class="container">
        <nav>
            <div class="logo">
                <img src="../img/logo.png" alt="logo">
            </div>
            <ul class="nav-links">
                <li><a href="../html/index.html" class="nav-tab-dark">Home</a></li>
                <?php if ($user_id): ?>
                    <li><a href="<?php echo $_SESSION['role']; ?>_dashboard.php">My Dashboard</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-tab-dark">Login</a></li>
                <?php endif; ?>
                <li><a href="destinations.php" class="active">Tour</a></li>
                <li><a href="activities.php">Activities</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if ($user_id): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger" id="hamburger">
                <span></span><span></span><span></span>
            </div>
        </nav>
    </div>
</header>

<main class="packages-page">
    <div class="container">

        <h1 class="headings">Our Tour <span>Packages</span></h1>

        <form method="GET" action="packages.php" class="search-form">

            <div class="search-field">
                <label for="destination">Destination</label>
                <select id="destination" name="destination">
                    <option value="0">All Destinations</option>
                    <?php while ($d = $destinations->fetch_assoc()): ?>
                        <option value="<?php echo $d['destination_id']; ?>"
                            <?php echo ($search_destination == $d['destination_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($d['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <button type="submit" class="primary-btn search-btn">Search</button>
        </form>

        <div class="packages-grid">
            <?php if ($packages->num_rows === 0): ?>
                <p class="no-results">No packages match your search. Try different filters.</p>
            <?php else: ?>
                <?php while ($pkg = $packages->fetch_assoc()): ?>
                    <div class="package-card">
                        <div class="package-img">
                            <?php
                                $folder = $pkg['destination_name'];
                                $file   = isset($destination_thumbnails[$folder]) ? $destination_thumbnails[$folder] : '';
                            ?>
                            <img src="../img/<?php echo htmlspecialchars($folder) . '/' . htmlspecialchars($file); ?>"
                                 alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                        </div>
                        <div class="package-info">
                            <span class="package-destination"><?php echo htmlspecialchars($pkg['destination_name']); ?></span>
                            <h3><?php echo htmlspecialchars($pkg['title']); ?></h3>
                            <p><?php echo htmlspecialchars($pkg['description']); ?></p>
                            <div class="package-meta">
                                <span><i class="fa-solid fa-clock"></i> <?php echo (int)$pkg['duration_days']; ?> day(s)</span>
                                <span><i class="fa-solid fa-users"></i> Max <?php echo (int)$pkg['max_travelers']; ?></span>
                            </div>
                            <div class="package-footer">
                                <span class="package-price-wrap">
                                    <span class="package-price">LKR <?php echo number_format($pkg['price'], 0); ?></span>
                                    <span class="package-price-unit">per person</span>
                                </span>
                                <a href="book_package.php?package_id=<?php echo $pkg['package_id']; ?>" class="primary-btn book-btn">Book Now</a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<script src="../js/script.js"></script>
</body>
</html>
