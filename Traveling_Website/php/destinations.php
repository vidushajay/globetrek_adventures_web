<?php
session_start();
require 'db_connect.php';

$destinations = $conn->query("SELECT * FROM destinations ORDER BY destination_id");

// Real image filenames per destination, gathered from the img/ folders
$destination_images = [
    'Colombo'  => ['Independence-Square-800-x-500.jpg', 'Gangaramaya-Temple.jpg', 'Colombo-National-Museum.jpg', 'Nelum-Tower.jpg'],
    'Galle'    => ['Dutch_fort.jpg', 'Fort-Lighthouse-1.jpg', 'Kanneliya-Forest.jpg'],
    'Ella'     => ['Mini-Adams-peak-800x550-1.jpg', 'Demodara-Bridge.jpg', 'Ravana-Ella-falls.jpg'],
    'Dambulla' => ['Dambulla-Cave-Temple-800-x-500.jpg', 'Rose-quartz-mountain.jpg', 'Hot-Air-Balloon-Ride.jpg'],
    'Hatton'   => ['Adams_peak_sunrise.jpg', 'Kayaking_on_Castlereagh.jpg', 'Warleigh_Church.jpg'],
];

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/destinations.css">
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
                <li><a href="destinations.php" class="active">Tour</a></li>
                <li><a href="activities.php">Activities</a></li>
                <li><a href="about.php">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if ($user_id): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </div>
</header>

<main class="destinations-page">
    <div class="container">
        <h1 class="headings">Explore Sri <span>Lanka</span></h1>
        <p class="page-intro">
            From ancient temples to misty tea hills, discover the destinations behind every GlobeTrek tour package.
        </p>

        <?php while ($d = $destinations->fetch_assoc()):
            $name   = $d['name'];
            $images = isset($destination_images[$name]) ? $destination_images[$name] : [];
        ?>
            <section class="destination-block">
                <div class="destination-gallery">
                    <?php foreach ($images as $i => $img): ?>
                        <div class="gallery-img gallery-img-<?php echo $i + 1; ?>">
                            <img src="../img/<?php echo htmlspecialchars($name) . '/' . htmlspecialchars($img); ?>"
                                 alt="<?php echo htmlspecialchars($name); ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="destination-info">
                    <h2><?php echo htmlspecialchars($name); ?></h2>
                    <p><?php echo htmlspecialchars($d['description']); ?></p>
                    <a href="packages.php?destination=<?php echo $d['destination_id']; ?>" class="primary-btn view-btn">
                        View Tours in <?php echo htmlspecialchars($name); ?>
                    </a>
                </div>
            </section>
        <?php endwhile; ?>

    </div>
</main>

<script src="../js/script.js"></script>
</body>
</html>
