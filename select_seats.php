<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?type=user");
    exit();
}

$bus_id = isset($_GET['bus_id']) ? (int)$_GET['bus_id'] : 0;
$travel_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get bus details
$bus_query = $conn->query("SELECT * FROM buses WHERE id='$bus_id'");
$bus = $bus_query->fetch_assoc();

if (!$bus) {
    header("Location: search_bus.php");
    exit();
}

// Get already booked seats
$booked_seats = [];
$booking_query = $conn->query("SELECT seats FROM bookings WHERE bus_id='$bus_id' AND journey_date='$travel_date' AND status='confirmed'");
if ($booking_query) {
    while ($row = $booking_query->fetch_assoc()) {
        $seats = explode(',', $row['seats']);
        $booked_seats = array_merge($booked_seats, $seats);
    }
}

$error = "";

if (isset($_POST['proceed'])) {
    if (isset($_POST['seats']) && !empty($_POST['seats'])) {
        $selected_seats = $_POST['seats'];
        
        $_SESSION['selected_seats'] = $selected_seats;
        $_SESSION['bus_id'] = $bus_id;
        $_SESSION['travel_date'] = $travel_date;
        $_SESSION['bus_details'] = $bus;
        
        header("Location: passenger_details.php");
        exit();
    } else {
        $error = "Please select at least one seat!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Select Seats - Great Bus</title>
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
            --border-color: #e0e0e0;
            --seat-available: #e0e4e8;
            --seat-selected: linear-gradient(135deg, #10b981, #059669);
            --seat-booked: #ff4757;
            --info-bg: #fff3e8;
            --info-color: #FF6B35;
            --legend-text: #666;
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --border-color: #3a3a4a;
            --seat-available: #3a3a4a;
            --info-bg: #2a2a3a;
            --info-color: #FF8C42;
            --legend-text: #a0a0a0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 1.5rem;
            transition: background 0.3s ease;
            position: relative;
            overflow-x: hidden;
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
            font-size: 2rem;
            color: rgba(255, 255, 255, 0.08);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% { transform: translateX(-10%) translateY(10vh); opacity: 0; }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% { transform: translateX(110vw) translateY(-10vh); opacity: 0; }
        }

        .floating-bus:nth-child(1) { top: 20%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 50%; left: -10%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 70%; left: -3%; animation-duration: 22s; animation-delay: 6s; }

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

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 100;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 50px;
            padding: 8px 18px;
            color: white;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.8);
        }

        .container {
            position: relative;
            z-index: 10;
            max-width: 1100px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 40px;
            padding: 2rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease;
            transition: background 0.3s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Enhanced Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 70px;
            height: 70px;
            margin: 0 auto;
            position: relative;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .logo-icon::before {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,107,53,0.2), transparent);
            animation: pulseRing 2s ease-out infinite;
            z-index: -1;
        }

        @keyframes pulseRing {
            0% { transform: scale(0.8); opacity: 0.5; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        .logo-icon::after {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #FF6B35, #F7931E, #FFD700, #FF6B35);
            animation: rotateRing 3s linear infinite;
            z-index: -2;
            opacity: 0.5;
        }

        @keyframes rotateRing {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo-icon svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
            animation: iconGlow 2s ease-in-out infinite;
        }

        @keyframes iconGlow {
            0%, 100% { filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2)); }
            50% { filter: drop-shadow(0 0 20px rgba(255,107,53,0.5)); }
        }

        /* Header Section */
        .header-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        h2 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-top: 0.5rem;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Bus Info Card */
        .bus-info-card {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 20px;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Bus Layout */
        .bus-layout {
            background: var(--card-bg);
            border: 2px solid #FF6B35;
            border-radius: 20px;
            overflow: hidden;
            animation: fadeInUp 0.6s ease;
        }

        .bus-header {
            background: #FF6B35;
            color: white;
            padding: 0.8rem;
            text-align: center;
            font-weight: 600;
        }

        /* Seat Container */
        .seat-container {
            display: flex;
            padding: 1.5rem;
            gap: 2rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .left-side, .right-side {
            flex: 1;
            min-width: 250px;
        }

        .side-title {
            text-align: center;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #FF6B35;
            display: inline-block;
            width: 100%;
        }

        .center-aisle {
            width: 40px;
            background: repeating-linear-gradient(45deg, #FF6B35, #FF6B35 10px, #F7931E 10px, #F7931E 20px);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }

        .seats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.8rem;
        }

        .seat {
            aspect-ratio: 1;
            cursor: pointer;
            position: relative;
            transition: transform 0.2s ease;
        }

        .seat:active {
            transform: scale(0.95);
        }

        .seat-inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            background: var(--seat-available);
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .seat-inner i {
            font-size: 0.7rem;
            margin-top: 3px;
            color: #666;
        }

        body.dark .seat-inner i {
            color: #aaa;
        }

        .seat.selected .seat-inner {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
        }

        .seat.selected .seat-inner i {
            color: white;
        }

        .seat.booked {
            cursor: not-allowed;
        }

        .seat.booked .seat-inner {
            background: var(--seat-booked);
            color: white;
            cursor: not-allowed;
        }

        .seat:not(.booked):hover .seat-inner {
            transform: scale(1.05);
            background: #cdd1d8;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        body.dark .seat:not(.booked):hover .seat-inner {
            background: #4a4a5a;
        }

        .seat-checkbox {
            display: none;
        }

        /* Legend */
        .legend {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 1.5rem 0;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .legend-box {
            width: 35px;
            height: 35px;
            border-radius: 10px;
        }

        .legend-box.available {
            background: var(--seat-available);
            border: 1px solid var(--border-color);
        }

        .legend-box.selected {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .legend-box.booked {
            background: #ff4757;
        }

        /* Selection info */
        .selected-info {
            background: var(--info-bg);
            padding: 0.8rem 1rem;
            border-radius: 50px;
            text-align: center;
            margin: 1rem 0;
            color: var(--info-color);
            font-weight: 500;
            transition: all 0.3s ease;
            animation: pulseInfo 2s ease-in-out infinite;
        }

        @keyframes pulseInfo {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.9; }
        }

        /* Quick Stats Card */
        .quick-stats {
            background: var(--info-bg);
            border-radius: 20px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #FF6B35;
        }

        .stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 0.8rem 1rem;
            border-radius: 50px;
            text-align: center;
            margin: 1rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .btn-proceed {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .btn-proceed::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-proceed:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-proceed:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .back-link {
            display: inline-block;
            margin-top: 1rem;
            color: #FF6B35;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
            color: #F7931E;
        }

        @media (max-width: 768px) {
            body { padding: 0.5rem; }
            .container { padding: 1rem; margin: 0.5rem; }
            .seat-container { flex-direction: column; align-items: center; gap: 1rem; }
            .center-aisle { width: 100%; height: 40px; writing-mode: horizontal-tb; margin: 0.5rem 0; }
            .seats-grid { gap: 0.5rem; }
            .bus-info-card { flex-direction: column; text-align: center; }
            h2 { font-size: 1.3rem; }
            .logo-icon { width: 55px; height: 55px; }
        }
    </style>
</head>
<body>

<!-- Animated Background -->
<div class="animated-bg">
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
</div>

<script>
    // Generate floating particles
    (function() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 40; i++) {
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

<!-- Theme Toggle -->
<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<div class="container">
    <!-- Enhanced Logo Section -->
    <div class="logo-section">
        <div class="logo-icon">
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="50" cy="50" r="48" stroke="url(#grad)" stroke-width="2" fill="none"/>
                <defs>
                    <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#FF6B35;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#F7931E;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <rect x="25" y="35" width="50" height="35" rx="5" fill="#FF6B35"/>
                <rect x="20" y="30" width="60" height="8" rx="3" fill="#F7931E"/>
                <circle cx="35" cy="70" r="8" fill="#fff" stroke="#FF6B35" stroke-width="2"/>
                <circle cx="65" cy="70" r="8" fill="#fff" stroke="#FF6B35" stroke-width="2"/>
                <rect x="45" y="42" width="10" height="20" rx="2" fill="#fff"/>
                <text x="50" y="62" text-anchor="middle" fill="#1a1a2e" font-size="12" font-weight="bold">GB</text>
            </svg>
        </div>
    </div>

    <div class="header-section">
        <h2><i class="fas fa-chair"></i> Select Your Seats</h2>
        <p class="subtitle"><i class="fas fa-fingerprint"></i> Click on any seat to select/deselect</p>
    </div>

    <!-- Bus Info -->
    <div class="bus-info-card">
        <div>
            <strong><i class="fas fa-bus"></i> <?= htmlspecialchars($bus['bus_name']) ?></strong><br>
            <small><?= htmlspecialchars($bus['bus_number']) ?></small>
        </div>
        <div>
            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($bus['from_city']) ?> → <?= htmlspecialchars($bus['to_city']) ?>
        </div>
        <div>
            <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($travel_date)) ?>
        </div>
        <div>
            <i class="fas fa-tag"></i> ₹<?= number_format($bus['price_per_seat'], 0) ?>/seat
        </div>
    </div>

    <form method="post" id="seatForm">
        <div class="bus-layout">
            <div class="bus-header">
                <i class="fas fa-bus"></i> GREAT BUS - SEAT LAYOUT <i class="fas fa-arrow-left"></i> FRONT <i class="fas fa-arrow-right"></i>
            </div>
            
            <div class="seat-container">
                <!-- Left Side Seats 1-20 -->
                <div class="left-side">
                    <div class="side-title"><i class="fas fa-window-maximize"></i> LEFT SIDE (Window)</div>
                    <div class="seats-grid" id="leftSeats">
                        <?php for ($i = 1; $i <= 20; $i++): 
                            $is_booked = in_array($i, $booked_seats);
                        ?>
                            <div class="seat <?= $is_booked ? 'booked' : '' ?>" data-seat="<?= $i ?>" onclick="toggleSeat(this, <?= $i ?>, <?= $is_booked ? 'true' : 'false' ?>)">
                                <div class="seat-inner">
                                    <?= $i ?>
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="checkbox" name="seats[]" value="<?= $i ?>" id="seat_<?= $i ?>" class="seat-checkbox" <?= $is_booked ? 'disabled' : '' ?>>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Center Aisle -->
                <div class="center-aisle">
                    <i class="fas fa-grip-lines-vertical"></i> AISLE <i class="fas fa-grip-lines-vertical"></i>
                </div>

                <!-- Right Side Seats 21-40 -->
                <div class="right-side">
                    <div class="side-title"><i class="fas fa-window-maximize"></i> RIGHT SIDE (Window)</div>
                    <div class="seats-grid" id="rightSeats">
                        <?php for ($i = 21; $i <= 40; $i++): 
                            $is_booked = in_array($i, $booked_seats);
                        ?>
                            <div class="seat <?= $is_booked ? 'booked' : '' ?>" data-seat="<?= $i ?>" onclick="toggleSeat(this, <?= $i ?>, <?= $is_booked ? 'true' : 'false' ?>)">
                                <div class="seat-inner">
                                    <?= $i ?>
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="checkbox" name="seats[]" value="<?= $i ?>" id="seat_<?= $i ?>" class="seat-checkbox" <?= $is_booked ? 'disabled' : '' ?>>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-box available"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-box selected"></div>
                <span>Selected</span>
            </div>
            <div class="legend-item">
                <div class="legend-box booked"></div>
                <span>Booked</span>
            </div>
        </div>

        <div class="selected-info" id="selectedInfo">
            <i class="fas fa-info-circle"></i> Click on seats to select
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message" id="errorMessage">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <script>
                setTimeout(() => {
                    const err = document.getElementById('errorMessage');
                    if (err) err.style.display = 'none';
                }, 3000);
            </script>
        <?php endif; ?>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-value" id="selectedCount">0</div>
                <div class="stat-label">Selected Seats</div>
            </div>
            <div class="stat-item">
                <div class="stat-value" id="totalPrice">₹0</div>
                <div class="stat-label">Total Amount</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?= $bus['total_seats'] ?></div>
                <div class="stat-label">Total Seats</div>
            </div>
        </div>

        <button type="submit" name="proceed" class="btn-proceed" id="submitBtn">
            <i class="fas fa-arrow-right"></i> Proceed to Passenger Details
        </button>
    </form>
    
    <a href="search_bus.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Search</a>
</div>

<script>
    // Dark/Light Mode Toggle
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const toggleIcon = themeToggle.querySelector('i');
    const toggleText = themeToggle.querySelector('span');

    const savedTheme = localStorage.getItem('greatbus-theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark');
        toggleIcon.className = 'fas fa-sun';
        toggleText.textContent = 'Light Mode';
    } else {
        toggleIcon.className = 'fas fa-moon';
        toggleText.textContent = 'Dark Mode';
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark');
        if (body.classList.contains('dark')) {
            localStorage.setItem('greatbus-theme', 'dark');
            toggleIcon.className = 'fas fa-sun';
            toggleText.textContent = 'Light Mode';
        } else {
            localStorage.setItem('greatbus-theme', 'light');
            toggleIcon.className = 'fas fa-moon';
            toggleText.textContent = 'Dark Mode';
        }
    });

    // Seat Selection Functions
    function toggleSeat(seatElement, seatNum, isBooked) {
        if (isBooked) return;
        const checkbox = document.getElementById('seat_' + seatNum);
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            seatElement.classList.add('selected');
        } else {
            seatElement.classList.remove('selected');
        }
        updateSelectedSeatsDisplay();
        updateTotalPrice();
        updateQuickStats();
    }
    
    function updateSelectedSeatsDisplay() {
        const selectedCheckboxes = document.querySelectorAll('input[name="seats[]"]:checked');
        const count = selectedCheckboxes.length;
        const totalPrice = count * <?= $bus['price_per_seat'] ?>;
        const selectedInfo = document.getElementById('selectedInfo');
        
        if (count === 0) {
            selectedInfo.innerHTML = '<i class="fas fa-info-circle"></i> Click on seats to select';
            selectedInfo.style.background = '#f0f2ff';
            selectedInfo.style.color = '#FF6B35';
        } else {
            const seatNumbers = [];
            selectedCheckboxes.forEach(cb => seatNumbers.push(cb.value));
            selectedInfo.innerHTML = `<i class="fas fa-check-circle"></i> ✅ ${count} seat(s) selected: ${seatNumbers.join(', ')} | 💰 Total: ₹${totalPrice.toLocaleString()}`;
            selectedInfo.style.background = '#d4edda';
            selectedInfo.style.color = '#155724';
        }
    }

    function updateTotalPrice() {
        const selectedCheckboxes = document.querySelectorAll('input[name="seats[]"]:checked');
        const count = selectedCheckboxes.length;
        const totalPrice = count * <?= $bus['price_per_seat'] ?>;
        const submitBtn = document.getElementById('submitBtn');
        
        if (count > 0) {
            submitBtn.innerHTML = `<i class="fas fa-arrow-right"></i> Proceed (${count} seats - ₹${totalPrice.toLocaleString()})`;
        } else {
            submitBtn.innerHTML = '<i class="fas fa-arrow-right"></i> Proceed to Passenger Details';
        }
    }

    function updateQuickStats() {
        const selectedCheckboxes = document.querySelectorAll('input[name="seats[]"]:checked');
        const count = selectedCheckboxes.length;
        const totalPrice = count * <?= $bus['price_per_seat'] ?>;
        
        document.getElementById('selectedCount').textContent = count;
        document.getElementById('totalPrice').innerHTML = `₹${totalPrice.toLocaleString()}`;
    }
    
    // Form validation
    const form = document.getElementById('seatForm');
    
    form.addEventListener('submit', function(e) {
        if (document.querySelectorAll('input[name="seats[]"]:checked').length === 0) {
            e.preventDefault();
            let errorDiv = document.querySelector('.error-message');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ⚠️ Please select at least one seat before proceeding!';
                document.querySelector('.legend').insertAdjacentElement('afterend', errorDiv);
                setTimeout(() => errorDiv.remove(), 3000);
            }
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.style.transform = 'translateX(5px)';
            setTimeout(() => submitBtn.style.transform = '', 300);
        }
    });
    
    updateSelectedSeatsDisplay();
    updateTotalPrice();
    updateQuickStats();
    
    console.log("%c🚌 Great Bus | Seat Selection Page", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Left Side: Seats 1-20 | Right Side: Seats 21-40", "color: #F7931E; font-size: 12px;");
    console.log("%c✓ Enhanced Logo | Quick Stats | Mobile Optimized", "color: #FFD700; font-size: 12px;");
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