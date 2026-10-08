<?php
session_start();
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$activities = [
    [
        'title' => 'Cycling in Sri Lanka',
        'image' => 'Cycling_in_Sri_Lanka.jpg',
        'description' => 'Pedal past waterfalls and through misty hill country on guided cycling routes suited to every fitness level.'
    ],
    [
        'title' => 'History & Culture',
        'image' => 'History_and_Culture.jpg',
        'description' => 'Step into centuries of heritage at ancient temples, ruins, and sacred sites carved into the island\'s rock and legend.'
    ],
    [
        'title' => 'Signature Music & Dance',
        'image' => 'Signature_Music.jpg',
        'description' => 'Experience the rhythm of Sri Lanka with beachside drumming, folk dance, and fire performances at sunset.'
    ],
    [
        'title' => 'Staple Dishes & Curries',
        'image' => 'Staple_Dishes___Curries.jpg',
        'description' => 'Taste the island\'s famous rice and curry spread, bursting with coconut, spice, and generations of home-cooked tradition.'
    ],
    [
        'title' => 'Surfing',
        'image' => 'Surfing.jpg',
        'description' => 'Catch world-class waves along the southern coast, with breaks suited to first-timers and seasoned surfers alike.'
    ],
    [
        'title' => 'Yoga & Meditation',
        'image' => 'Yoga_and_meditation.jpg',
        'description' => 'Unwind with sunrise yoga sessions and guided meditation set against calm lakes and quiet, natural surroundings.'
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activities - GlobeTrek Adventures</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/responsive.css">
    <link rel="stylesheet" href="../css/activities.css">
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
                <li><a href="activities.php" class="active">Activities</a></li>
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

<main class="activities-page">
    <div class="container">
        <h1 class="headings">Things to <span>Experience</span></h1>
        <p class="page-intro">
            Beyond the sights, Sri Lanka is a feeling — here's a taste of what you can do while you're here.
        </p>

        <div class="activities-grid">
            <?php foreach ($activities as $a): ?>
                <div class="activity-card">
                    <div class="activity-img">
                        <img src="../img/Activities/<?php echo htmlspecialchars($a['image']); ?>"
                             alt="<?php echo htmlspecialchars($a['title']); ?>">
                    </div>
                    <div class="activity-overlay">
                        <h3><?php echo htmlspecialchars($a['title']); ?></h3>
                        <p><?php echo htmlspecialchars($a['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    </div>
</main>

<script src="../js/script.js"></script>
</body>
</html>
