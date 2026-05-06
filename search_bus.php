<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?type=user");
    exit();
}

$buses = [];
$from_city = "";
$to_city = "";
$travel_date = "";
$searched = false;

// Get active ads for display
$today = date('Y-m-d');
$ads_query = $conn->query("SELECT * FROM ads WHERE status='active' AND (start_date <= '$today' OR start_date IS NULL) AND (end_date >= '$today' OR end_date IS NULL) ORDER BY id DESC");
$ads = [];
if ($ads_query && $ads_query->num_rows > 0) {
    while ($row = $ads_query->fetch_assoc()) {
        $ads[] = $row;
    }
}

// Get all unique cities for dropdown
$cities_query = $conn->query("SELECT DISTINCT from_city FROM buses UNION SELECT DISTINCT to_city FROM buses");
$cities = [];
if ($cities_query) {
    while ($row = $cities_query->fetch_assoc()) {
        $cities[] = $row['from_city'];
    }
}

if (isset($_POST['search'])) {
    $from_city = mysqli_real_escape_string($conn, $_POST['from_city']);
    $to_city = mysqli_real_escape_string($conn, $_POST['to_city']);
    $travel_date = mysqli_real_escape_string($conn, $_POST['travel_date']);
    $searched = true;
    
    $query = "SELECT * FROM buses WHERE from_city='$from_city' AND to_city='$to_city' AND travel_date='$travel_date' AND status='active'";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $buses[] = $row;
        }
    }
} else {
    // Show all active buses by default
    $result = $conn->query("SELECT * FROM buses WHERE status='active' ORDER BY id DESC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $buses[] = $row;
        }
    }
}

// Track ad view
if (isset($_GET['ad_click']) && is_numeric($_GET['ad_click'])) {
    $ad_id = $_GET['ad_click'];
    $conn->query("UPDATE ads SET clicks = clicks + 1 WHERE id = $ad_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Search Buses - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Variables for Light/Dark Mode - ORANGE THEME */
        :root {
            --bg-gradient: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --navbar-bg: rgba(255, 255, 255, 0.95);
            --input-border: #e0e0e0;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --btn-book: linear-gradient(135deg, #10b981, #059669);
            --footer-bg: rgba(0,0,0,0.5);
            --ad-card-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --ad-card-hover: linear-gradient(135deg, #5a67d8 0%, #6b46a0 100%);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --navbar-bg: rgba(30, 30, 46, 0.95);
            --input-border: #3a3a4a;
            --shadow: 0 10px 30px rgba(0,0,0,0.3);
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --btn-book: linear-gradient(135deg, #10b981, #059669);
            --footer-bg: rgba(0,0,0,0.7);
            --ad-card-bg: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            --ad-card-hover: linear-gradient(135deg, #5a67d8 0%, #6b46a0 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            transition: background 0.3s ease;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-bus {
            position: absolute;
            font-size: 2.5rem;
            color: rgba(255, 255, 255, 0.08);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% { transform: translateX(-10%) translateY(0vh); opacity: 0; }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% { transform: translateX(110vw) translateY(-10vh); opacity: 0; }
        }

        .floating-bus:nth-child(1) { top: 10%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 30%; left: -8%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 50%; left: -3%; animation-duration: 22s; animation-delay: 6s; }
        .floating-bus:nth-child(4) { top: 70%; left: -10%; animation-duration: 28s; animation-delay: 2s; }
        .floating-bus:nth-child(5) { top: 85%; left: -6%; animation-duration: 20s; animation-delay: 8s; }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 50%;
            animation: floatParticle 15s infinite linear;
        }

        @keyframes floatParticle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% {
                transform: translateY(-10vh) translateX(50px);
                opacity: 0;
            }
        }

        /* Navbar */
        .navbar {
            position: relative;
            z-index: 10;
            background: var(--navbar-bg);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .logo i {
            color: #FF6B35;
            margin-right: 10px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255,107,53,0.1);
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info span {
            color: var(--text-primary);
            font-weight: 500;
        }

        .logout-btn {
            background: #ff4757;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff6b81;
            transform: translateY(-2px);
        }

        /* Theme Toggle */
        .theme-toggle {
            background: rgba(0,0,0,0.2);
            border: none;
            border-radius: 50px;
            padding: 8px 16px;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            background: rgba(0,0,0,0.3);
        }

        /* Container */
        .container {
            position: relative;
            z-index: 10;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Search Card */
        .search-card {
            background: var(--card-bg);
            border-radius: 30px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .search-title {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .search-form input {
            padding: 0.8rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 15px;
            font-size: 1rem;
            background: var(--card-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .search-form input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .search-form button {
            background: var(--btn-primary);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-form button:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        /* Advertisement Banner - TOP */
        .ad-banner {
            background: var(--ad-card-bg);
            border-radius: 25px;
            padding: 1rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: slideInDown 0.5s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ad-banner:hover {
            transform: translateY(-3px);
            background: var(--ad-card-hover);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .ad-content {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .ad-content i {
            font-size: 2.5rem;
            color: #FFD700;
        }

        .ad-text h4 {
            color: white;
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .ad-text p {
            color: rgba(255,255,255,0.9);
            font-size: 0.85rem;
        }

        .ad-badge {
            background: rgba(255,255,255,0.2);
            padding: 0.3rem 1rem;
            border-radius: 50px;
            color: white;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Sidebar Layout for buses with ads */
        .content-with-sidebar {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 2rem;
        }

        /* Sidebar Ads */
        .sidebar-ads {
            position: sticky;
            top: 2rem;
            height: fit-content;
        }

        .sidebar-ad-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: var(--shadow);
            animation: fadeInRight 0.5s ease;
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .sidebar-ad-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .sidebar-ad-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .sidebar-ad-card h4 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }

        .sidebar-ad-card p {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .sidebar-ad-btn {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            cursor: pointer;
            width: 100%;
        }

        /* Section Title */
        .section-title {
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
        }

        /* Bus Grid */
        .bus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .bus-card {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            animation: fadeInUp 0.5s ease;
        }

        .bus-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bus-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .bus-icon {
            font-size: 2rem;
            color: #FF6B35;
        }

        .bus-price-badge {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 700;
        }

        .bus-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.3rem;
        }

        .bus-number {
            color: var(--text-secondary);
            font-size: 0.8rem;
            margin-bottom: 1rem;
        }

        .route-info {
            background: rgba(255, 107, 53, 0.1);
            padding: 0.8rem;
            border-radius: 15px;
            margin: 1rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .route-city {
            font-weight: 600;
            color: var(--text-primary);
        }

        .route-arrow {
            color: #FF6B35;
        }

        .bus-details {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin: 1rem 0;
            padding: 0.8rem 0;
            border-top: 1px solid var(--input-border);
            border-bottom: 1px solid var(--input-border);
        }

        .bus-details span {
            display: flex;
            align-items: center;
            gap: 5px;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .amenity {
            background: rgba(255, 107, 53, 0.15);
            color: #FF6B35;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .book-btn {
            background: var(--btn-book);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 25px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .book-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        .no-buses {
            text-align: center;
            padding: 3rem;
            background: rgba(255,255,255,0.1);
            border-radius: 25px;
            color: white;
        }

        /* Bottom Banner Ad */
        .bottom-ad {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            border-radius: 25px;
            padding: 1.5rem;
            margin-top: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: fadeInUp 0.5s ease;
        }

        .bottom-ad:hover {
            transform: scale(1.02);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .bottom-ad h3 {
            color: white;
            margin-bottom: 0.5rem;
        }

        .bottom-ad p {
            color: rgba(255,255,255,0.9);
        }

        /* Footer */
        footer {
            position: relative;
            z-index: 10;
            background: var(--footer-bg);
            backdrop-filter: blur(10px);
            text-align: center;
            padding: 1rem;
            color: white;
            margin-top: 2rem;
            font-size: 0.85rem;
        }

        @media (max-width: 992px) {
            .content-with-sidebar {
                grid-template-columns: 1fr;
            }
            .sidebar-ads {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
                position: static;
            }
        }

        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .bus-grid { grid-template-columns: 1fr; }
            .navbar { flex-direction: column; text-align: center; }
            .search-form { grid-template-columns: 1fr; }
            .ad-banner { flex-direction: column; text-align: center; }
            .ad-content { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<!-- Animated Background -->
<div class="animated-bg">
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-van-shuttle"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
</div>

<script>
    // Generate floating particles
    (function() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.width = Math.random() * 5 + 2 + 'px';
            particle.style.height = particle.style.width;
            particle.style.animationDuration = Math.random() * 20 + 10 + 's';
            particle.style.animationDelay = Math.random() * 10 + 's';
            bg.appendChild(particle);
        }
    })();
</script>

<nav class="navbar">
    <div class="logo">
        <i class="fas fa-bus"></i> Great Bus
    </div>
    <div class="nav-links">
        <a href="user_account.php"><i class="fas fa-user-circle"></i> My Account</a>
        <button class="theme-toggle" id="themeToggle">
            <i class="fas fa-moon"></i> <span>Dark Mode</span>
        </button>
        <div class="user-info">
            <span><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['name']) ?></span>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Search Card -->
    <div class="search-card">
        <h2 class="search-title"><i class="fas fa-search"></i> Find Your Bus</h2>
        <form method="post" class="search-form">
            <input type="text" name="from_city" placeholder="From City" value="<?= htmlspecialchars($from_city) ?>" list="cities" required>
            <input type="text" name="to_city" placeholder="To City" value="<?= htmlspecialchars($to_city) ?>" list="cities" required>
            <datalist id="cities">
                <?php foreach ($cities as $city): ?>
                    <option value="<?= htmlspecialchars($city) ?>">
                <?php endforeach; ?>
            </datalist>
            <input type="date" name="travel_date" value="<?= htmlspecialchars($travel_date) ?>" required>
            <button type="submit" name="search"><i class="fas fa-search"></i> Search Buses</button>
        </form>
    </div>

    <!-- TOP Banner Ad -->
    <?php foreach ($ads as $ad): ?>
        <?php if ($ad['position'] == 'top'): ?>
            <div class="ad-banner" onclick="window.location.href='?ad_click=<?= $ad['id'] ?>&redirect=<?= urlencode($ad['link_url']) ?>'">
                <div class="ad-content">
                    <?php if ($ad['image_url']): ?>
                        <img src="<?= $ad['image_url'] ?>" alt="ad" width="50" height="50">
                    <?php else: ?>
                        <i class="fas fa-gift"></i>
                    <?php endif; ?>
                    <div class="ad-text">
                        <h4><?= htmlspecialchars($ad['title']) ?></h4>
                        <p><?= htmlspecialchars($ad['description']) ?></p>
                    </div>
                </div>
                <div class="ad-badge">
                    <i class="fas fa-fire"></i> Limited Offer
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Main Content with Sidebar -->
    <div class="content-with-sidebar">
        <!-- Buses Grid -->
        <div>
            <?php if (!empty($buses)): ?>
                <h3 class="section-title">
                    <i class="fas fa-bus"></i> 
                    <?= $searched ? "Search Results (" . count($buses) . " buses found)" : "All Available Buses" ?>
                </h3>
                <div class="bus-grid">
                    <?php foreach ($buses as $bus): ?>
                        <div class="bus-card">
                            <div class="bus-header">
                                <div class="bus-icon"><i class="fas fa-bus"></i></div>
                                <div class="bus-price-badge">₹<?= number_format($bus['price_per_seat'], 0) ?></div>
                            </div>
                            <div class="bus-name"><?= htmlspecialchars($bus['bus_name']) ?></div>
                            <div class="bus-number"><i class="fas fa-id-card"></i> <?= htmlspecialchars($bus['bus_number']) ?></div>
                            
                            <div class="route-info">
                                <span class="route-city"><?= htmlspecialchars($bus['from_city']) ?></span>
                                <span class="route-arrow"><i class="fas fa-arrow-right"></i></span>
                                <span class="route-city"><?= htmlspecialchars($bus['to_city']) ?></span>
                            </div>
                            
                            <div class="bus-details">
                                <span><i class="fas fa-clock"></i> Dep: <?= date('h:i A', strtotime($bus['departure_time'])) ?></span>
                                <span><i class="fas fa-clock"></i> Arr: <?= date('h:i A', strtotime($bus['arrival_time'])) ?></span>
                                <span><i class="fas fa-chair"></i> <?= $bus['total_seats'] ?> seats</span>
                                <span><i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($bus['travel_date'])) ?></span>
                            </div>
                            
                            <div class="amenities">
                                <?php 
                                $amenities_list = explode(',', $bus['amenities']);
                                foreach ($amenities_list as $amenity): 
                                ?>
                                    <span class="amenity"><i class="fas fa-check-circle"></i> <?= trim($amenity) ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <button class="book-btn" onclick="bookBus(<?= $bus['id'] ?>, '<?= $bus['travel_date'] ?>')">
                                <i class="fas fa-ticket-alt"></i> Select Seats
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($searched): ?>
                <div class="no-buses">
                    <i class="fas fa-bus" style="font-size: 4rem; margin-bottom: 1rem;"></i>
                    <h3>No Buses Found</h3>
                    <p>No buses available for this route on selected date. Please try different dates or cities.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar Ads -->
        <div class="sidebar-ads">
            <?php foreach ($ads as $ad): ?>
                <?php if ($ad['position'] == 'sidebar'): ?>
                    <div class="sidebar-ad-card" onclick="window.location.href='?ad_click=<?= $ad['id'] ?>&redirect=<?= urlencode($ad['link_url']) ?>'">
                        <div class="sidebar-ad-icon">
                            <?php if ($ad['image_url']): ?>
                                <img src="<?= $ad['image_url'] ?>" alt="ad" width="60" height="60">
                            <?php else: ?>
                                <i class="fas fa-tag"></i>
                            <?php endif; ?>
                        </div>
                        <h4><?= htmlspecialchars($ad['title']) ?></h4>
                        <p><?= htmlspecialchars($ad['description']) ?></p>
                        <button class="sidebar-ad-btn">Learn More <i class="fas fa-arrow-right"></i></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <!-- Quick Stats Card -->
            <div class="sidebar-ad-card">
                <div class="sidebar-ad-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h4>Your Travel Stats</h4>
                <p>Total Bookings: <?= rand(1, 20) ?></p>
                <p>Total Spent: ₹<?= rand(500, 5000) ?></p>
                <button class="sidebar-ad-btn" onclick="window.location.href='user_account.php'">View Details</button>
            </div>
        </div>
    </div>

    <!-- Bottom Banner Ad -->
    <?php foreach ($ads as $ad): ?>
        <?php if ($ad['position'] == 'bottom'): ?>
            <div class="bottom-ad" onclick="window.location.href='?ad_click=<?= $ad['id'] ?>&redirect=<?= urlencode($ad['link_url']) ?>'">
                <h3><i class="fas fa-star"></i> <?= htmlspecialchars($ad['title']) ?></h3>
                <p><?= htmlspecialchars($ad['description']) ?></p>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<footer>
    <i class="fas fa-bus"></i> Great Bus Booking System | <i class="fas fa-map-marker-alt"></i> India's Most Trusted Travel Partner | <i class="fas fa-headset"></i> 24/7 Support
</footer>

<script>
    function bookBus(busId, travelDate) {
        window.location.href = `select_seats.php?bus_id=${busId}&date=${travelDate}`;
    }

    // Dark/Light Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');
    const text = themeToggle.querySelector('span');

    const savedTheme = localStorage.getItem('greatbus-theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark');
        icon.className = 'fas fa-sun';
        text.textContent = 'Light Mode';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        if (body.classList.contains('dark')) {
            localStorage.setItem('greatbus-theme', 'dark');
            icon.className = 'fas fa-sun';
            text.textContent = 'Light Mode';
        } else {
            localStorage.setItem('greatbus-theme', 'light');
            icon.className = 'fas fa-moon';
            text.textContent = 'Dark Mode';
        }
    });

    console.log("%c🚌 Great Bus | Search Buses Page with Ads", "color: #FF6B35; font-size: 14px; font-weight: bold;");
</script>
<!-- Floating Chatbot Button -->
<style>
    .chatbot-float {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
    }
    
    .chatbot-btn {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #FF6B35, #F7931E);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        transition: all 0.3s ease;
        animation: float 2s ease-in-out infinite;
        text-decoration: none;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }
    
    .chatbot-btn:hover {
        transform: scale(1.1);
    }
    
    .chatbot-btn i {
        font-size: 1.8rem;
        color: white;
    }
    
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ff4757;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: pulse 1s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    
    @media (max-width: 550px) {
        .chatbot-btn {
            width: 50px;
            height: 50px;
        }
        .chatbot-btn i {
            font-size: 1.5rem;
        }
        .chatbot-float {
            bottom: 20px;
            right: 20px;
        }
    }
</style>

<!-- Floating Chatbot Button -->
<div class="chatbot-float">
    <a href="chatbot.php" class="chatbot-btn">
        <i class="fas fa-robot"></i>
        <div class="notification-badge">
            <i class="fas fa-comment"></i>
        </div>
    </a>
</div>

</body>
</html>