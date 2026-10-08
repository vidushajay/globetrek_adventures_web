<?php
session_start();

$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/about.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.3.1/css/all.min.css">
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
                <li><a href="about.php" class="active">About</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if ($user_id): ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
        </nav>
    </div>
</header>

<section class="about-page">
    <div class="container">

        <h1 class="headings">About <span>GlobeTrek Adventures</span></h1>
        <p class="page-intro">
            GlobeTrek Adventures is a Sri Lanka-based travel agency connecting travellers with
            authentic, well-planned experiences across the island — from the streets of Colombo
            to the misty hills of Ella and Hatton.
        </p>

        <div class="about-grid">
            <div class="about-card">
                <h3>Our Mission</h3>
                <p>
                    To make exploring Sri Lanka simple and trustworthy, by handling the planning,
                    logistics, and local know-how so our travellers can focus on the experience itself.
                </p>
            </div>
            <div class="about-card">
                <h3>Our Story</h3>
                <p>
                    Founded by a small team of travel enthusiasts, GlobeTrek Adventures started with a
                    handful of curated tours around Colombo and Galle, and has since grown to cover five
                    of the island's most-loved destinations.
                </p>
            </div>
            <div class="about-card">
                <h3>Why Travel With Us</h3>
                <p>
                    Every package is built around real, well-reviewed local attractions and activities,
                    with transparent pricing in LKR and a support team ready to answer questions before,
                    during, and after your trip.
                </p>
            </div>
        </div>

        <h2 class="secondary-headings" style="text-align:center; margin-top:3rem;">What We Offer</h2>
        <div class="about-offer-grid">
            <div class="offer-item">
                <i class="fa-solid fa-map-location-dot"></i>
                <h4>Curated Destinations</h4>
                <p>Handpicked tour packages across Colombo, Galle, Ella, Dambulla, and Hatton.</p>
            </div>
            <div class="offer-item">
                <i class="fa-solid fa-hiking"></i>
                <h4>Local Activities</h4>
                <p>From hiking and surfing to cultural tours and cuisine, matched to your interests.</p>
            </div>
            <div class="offer-item">
                <i class="fa-solid fa-headset"></i>
                <h4>Dedicated Support</h4>
                <p>Reach our team any time through the Contact page for questions or custom requests.</p>
            </div>
            <div class="offer-item">
                <i class="fa-solid fa-credit-card"></i>
                <h4>Secure Payments</h4>
                <p>Simple, secure online booking and payment for every package you choose.</p>
            </div>
        </div>

        <div class="about-cta">
            <h2 class="secondary-headings">Ready to explore?</h2>
            <p style="color:#dce8ff; margin-bottom:1rem;">
                <i class="fa-solid fa-envelope"></i> info@globetrekadventures.lk &nbsp;&middot;&nbsp;
                <i class="fa-solid fa-phone"></i> +94 77 123 4567
            </p>
            <a href="destinations.php" class="primary-btn" style="display:inline-block; width:auto; padding:0.8rem 2rem; text-decoration:none;">
                Browse Destinations
            </a>
        </div>

    </div>
</section>

<script src="../js/script.js"></script>
</body>
</html>
