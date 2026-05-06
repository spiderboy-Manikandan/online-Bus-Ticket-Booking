<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?type=user");
    exit();
}

if (!isset($_SESSION['booking_number'])) {
    header("Location: search_bus.php");
    exit();
}

$booking_number = $_SESSION['booking_number'];

$query = $conn->prepare("SELECT b.*, u.fullname, u.phone, u.email, 
                                bs.bus_name, bs.bus_number, bs.from_city, bs.to_city, 
                                bs.departure_time, bs.arrival_time
                         FROM bookings b
                         JOIN users u ON b.user_id = u.id
                         JOIN buses bs ON b.bus_id = bs.id
                         WHERE b.booking_number = ?");
$query->bind_param("s", $booking_number);
$query->execute();
$result = $query->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "Invalid booking!";
    exit();
}

// Safe decode
$passenger_details = json_decode($booking['passenger_details'], true) ?? [];

// Clean QR text (short = better scan)
$qr_text = "Booking: {$booking['booking_number']}\n";
$qr_text .= "Name: {$booking['fullname']}\n";
$qr_text .= "Phone: {$booking['phone']}\n";
$qr_text .= "Bus: {$booking['bus_name']}\n";
$qr_text .= "Route: {$booking['from_city']} to {$booking['to_city']}\n";
$qr_text .= "Seats: {$booking['seats']}\n";
$qr_text .= "Date: {$booking['journey_date']}\n";
$qr_text .= "Total: ₹{$booking['total_amount']}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Ticket Confirmed - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bg-gradient: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --border-color: #3a3a4a;
            --shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
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
            0% { transform: translateY(100vh) translateX(0); opacity: 0; }
            10% { opacity: 0.5; }
            90% { opacity: 0.5; }
            100% { transform: translateY(-10vh) translateX(50px); opacity: 0; }
        }

        /* Celebration Overlay */
        .celebration-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.85);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeOut 2s ease forwards;
            pointer-events: none;
        }

        @keyframes fadeOut {
            0% { opacity: 1; visibility: visible; }
            70% { opacity: 1; }
            100% { opacity: 0; visibility: hidden; }
        }

        .celebration-content {
            text-align: center;
            animation: celebrate 0.8s ease;
        }

        @keyframes celebrate {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        .celebration-content i {
            font-size: 5rem;
            color: #FFD700;
            margin-bottom: 1rem;
            animation: starPop 0.5s ease infinite;
        }

        @keyframes starPop {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        .celebration-content h3 {
            color: white;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .celebration-content p {
            color: #FFD700;
            font-size: 1rem;
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

        .main-content {
            opacity: 0;
            animation: showContent 0.5s ease forwards;
            animation-delay: 2s;
        }

        @keyframes showContent {
            to { opacity: 1; }
        }

        /* Enhanced Logo Section */
        .logo-section {
            text-align: center;
            margin-bottom: 1rem;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto;
            position: relative;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
        }

        .logo-icon svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2));
        }

        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 1.5rem;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
            background: rgba(0,0,0,0.05);
            color: var(--text-secondary);
            font-size: 0.7rem;
        }

        .step.completed {
            background: #10b981;
            color: white;
        }

        .step i {
            font-size: 0.8rem;
        }

        .step-line {
            width: 20px;
            height: 2px;
            background: var(--border-color);
        }

        .ticket-box {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            border-radius: 30px;
            padding: 2rem;
            width: 100%;
            max-width: 650px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 107, 53, 0.3);
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .ticket-box:hover {
            transform: translateY(-5px);
        }

        .ticket-header {
            text-align: center;
            border-bottom: 2px dashed var(--border-color);
            padding-bottom: 1rem;
            margin-bottom: 1rem;
        }

        .ticket-header h3 {
            color: #FF6B35;
            font-size: 1.3rem;
        }

        h2 {
            text-align: center;
            color: #10b981;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }

        /* Ticket Details Grid */
        .ticket-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .row-full {
            grid-column: span 2;
        }

        .row .label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .row .value {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .passenger-list {
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(255, 107, 53, 0.1);
            border-radius: 15px;
        }

        .passenger-list h4 {
            color: #FF6B35;
            margin-bottom: 0.8rem;
        }

        .passenger-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            font-size: 0.8rem;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .qr {
            text-align: center;
            margin: 1.5rem 0;
            padding: 1rem;
            background: white;
            border-radius: 15px;
            animation: qrPulse 2s ease-in-out infinite;
        }

        @keyframes qrPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255,107,53,0.4); }
            50% { box-shadow: 0 0 0 10px rgba(255,107,53,0); }
        }

        #qrcode {
            display: flex;
            justify-content: center;
            margin-bottom: 0.5rem;
        }

        .scan-text {
            font-size: 0.7rem;
            color: #FF6B35;
            margin-top: 0.5rem;
            text-align: center;
        }

        button {
            padding: 12px;
            border: none;
            background: var(--btn-primary);
            color: white;
            border-radius: 50px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        button::before {
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

        button:hover::before {
            width: 300px;
            height: 300px;
        }

        button:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-group a {
            flex: 1;
            text-decoration: none;
        }

        .home-btn {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
        }

        /* Download Ticket Section */
        .download-section {
            margin-top: 1rem;
            text-align: center;
        }

        .share-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1rem;
        }

        .share-btn {
            width: auto;
            padding: 0.5rem 1rem;
            margin-top: 0;
            font-size: 0.85rem;
        }

        .share-btn.whatsapp {
            background: #25D366;
        }

        .share-btn.email {
            background: #EA4335;
        }

        @media (max-width: 550px) {
            body { padding: 0.5rem; }
            .ticket-box { padding: 1rem; margin: 0.5rem; }
            .ticket-details { grid-template-columns: 1fr; }
            .row-full { grid-column: span 1; }
            .row { flex-direction: column; gap: 0.2rem; }
            .btn-group { flex-direction: column; }
            .passenger-item { flex-direction: column; }
            .progress-steps { gap: 0.2rem; }
            .step { padding: 0.2rem 0.5rem; font-size: 0.6rem; }
            .step-line { width: 10px; }
            .logo-icon { width: 50px; height: 50px; }
        }

        @media print {
            .theme-toggle, .animated-bg, .celebration-overlay, .btn-group, .qr button, .download-section, .share-buttons, .progress-steps {
                display: none;
            }
            .ticket-box {
                box-shadow: none;
                padding: 0;
                background: white;
            }
            body {
                background: white;
                padding: 0;
            }
            .ticket-box:hover {
                transform: none;
            }
        }
    </style>
</head>
<body>

<!-- Celebration Overlay -->
<div class="celebration-overlay" id="celebration">
    <div class="celebration-content">
        <i class="fas fa-trophy"></i>
        <h3>🎉 Booking Confirmed! 🎉</h3>
        <p>Your ticket has been successfully booked</p>
        <p style="font-size: 0.8rem; margin-top: 1rem;">✨ Get ready for your journey ✨</p>
    </div>
</div>

<script>
    // Auto hide celebration after 2 seconds
    setTimeout(() => {
        const celebration = document.getElementById('celebration');
        if (celebration) {
            celebration.style.display = 'none';
        }
    }, 2000);
</script>

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

<div class="main-content">
    <div class="ticket-box">
        <!-- Enhanced Logo Section -->
        <div class="logo-section">
            <div class="logo-icon">
                <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="48" stroke="url(#grad)" stroke-width="2" fill="none"/>
                    <defs>
                        <linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#FF6B35"/>
                            <stop offset="100%" style="stop-color:#F7931E"/>
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

        <!-- Progress Steps -->
        <div class="progress-steps">
            <div class="step completed"><i class="fas fa-check"></i> Select Bus</div>
            <div class="step-line"></div>
            <div class="step completed"><i class="fas fa-check"></i> Select Seats</div>
            <div class="step-line"></div>
            <div class="step completed"><i class="fas fa-check"></i> Passenger</div>
            <div class="step-line"></div>
            <div class="step completed"><i class="fas fa-check"></i> Payment</div>
            <div class="step-line"></div>
            <div class="step completed"><i class="fas fa-ticket-alt"></i> Ticket</div>
        </div>

        <div class="ticket-header">
            <i class="fas fa-bus" style="font-size: 1.8rem; color: #FF6B35;"></i>
            <h3>Great Bus Travels</h3>
            <span style="font-size: 0.7rem; color: var(--text-secondary);">E-Ticket</span>
        </div>

        <h2><i class="fas fa-check-circle"></i> Booking Confirmed!</h2>

        <div class="ticket-details">
            <div class="row">
                <span class="label"><i class="fas fa-qrcode"></i> Booking ID:</span>
                <span class="value">#<?= htmlspecialchars($booking['booking_number']) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-user"></i> Name:</span>
                <span class="value"><?= htmlspecialchars($booking['fullname']) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-phone"></i> Phone:</span>
                <span class="value"><?= htmlspecialchars($booking['phone']) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-envelope"></i> Email:</span>
                <span class="value"><?= htmlspecialchars($booking['email']) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-bus"></i> Bus:</span>
                <span class="value"><?= htmlspecialchars($booking['bus_name']) ?> (<?= htmlspecialchars($booking['bus_number']) ?>)</span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-map-marker-alt"></i> Route:</span>
                <span class="value"><?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-calendar"></i> Journey Date:</span>
                <span class="value"><?= date('l, d M Y', strtotime($booking['journey_date'])) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-clock"></i> Departure:</span>
                <span class="value"><?= date('h:i A', strtotime($booking['departure_time'])) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-clock"></i> Arrival:</span>
                <span class="value"><?= date('h:i A', strtotime($booking['arrival_time'])) ?></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-chair"></i> Seats:</span>
                <span class="value"><strong><?= htmlspecialchars($booking['seats']) ?></strong></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-rupee-sign"></i> Total Amount:</span>
                <span class="value"><strong style="color: #10b981;">₹<?= number_format($booking['total_amount'], 0) ?></strong></span>
            </div>
            <div class="row">
                <span class="label"><i class="fas fa-tag"></i> Payment Status:</span>
                <span class="value"><span style="background: #10b981; color: white; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.7rem;">Completed ✓</span></span>
            </div>
        </div>

        <div class="passenger-list">
            <h4><i class="fas fa-users"></i> Passenger Details</h4>
            <?php foreach ($passenger_details as $index => $p): ?>
                <div class="passenger-item">
                    <span><i class="fas fa-user-circle"></i> <strong><?= htmlspecialchars($p['name']) ?></strong></span>
                    <span><i class="fas fa-calendar-alt"></i> Age: <?= $p['age'] ?></span>
                    <span><i class="fas fa-venus-mars"></i> <?= $p['gender'] ?></span>
                    <span><i class="fas fa-phone"></i> <?= $p['phone'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="qr">
            <div id="qrcode"></div>
            <div class="scan-text">
                <i class="fas fa-mobile-alt"></i> Scan this QR code at boarding
            </div>
            <button onclick="downloadQR()">
                <i class="fas fa-download"></i> Download QR Code
            </button>
        </div>

        <div class="btn-group">
            <button onclick="window.print()">
                <i class="fas fa-print"></i> Print Ticket
            </button>
            <a href="search_bus.php">
                <button class="home-btn">
                    <i class="fas fa-home"></i> Book More
                </button>
            </a>
        </div>

        <!-- Share Options -->
        <div class="download-section">
            <div class="share-buttons">
                <button class="share-btn whatsapp" onclick="shareViaWhatsApp()">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </button>
                <button class="share-btn email" onclick="shareViaEmail()">
                    <i class="fas fa-envelope"></i> Email
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // SAFE DATA FROM PHP
    const qrText = <?= json_encode($qr_text) ?>;
    const bookingNumber = <?= json_encode($booking['booking_number']) ?>;

    // GENERATE QR
    window.onload = function() {
        const qrcodeDiv = document.getElementById("qrcode");
        if (qrcodeDiv) {
            qrcodeDiv.innerHTML = "";
            new QRCode(qrcodeDiv, {
                text: qrText,
                width: 160,
                height: 160,
                colorDark: "#FF6B35",
                colorLight: "#ffffff"
            });
        }
    };

    // DOWNLOAD QR
    function downloadQR() {
        const canvas = document.querySelector("#qrcode canvas");
        if (!canvas) {
            alert("QR not ready yet. Please wait a moment.");
            return;
        }
        const link = document.createElement("a");
        link.download = "GreatBus_Ticket_" + bookingNumber + ".png";
        link.href = canvas.toDataURL();
        link.click();
    }

    // Share via WhatsApp
    function shareViaWhatsApp() {
        const text = encodeURIComponent("🎫 Great Bus Ticket Confirmed!\n\nBooking ID: " + bookingNumber + "\nBus: <?= htmlspecialchars($booking['bus_name']) ?>\nRoute: <?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?>\nDate: <?= date('d M Y', strtotime($booking['journey_date'])) ?>\nSeats: <?= htmlspecialchars($booking['seats']) ?>\n\nThank you for choosing Great Bus! 🚌");
        window.open("https://wa.me/?text=" + text, "_blank");
    }

    // Share via Email
    function shareViaEmail() {
        const subject = encodeURIComponent("Great Bus Ticket Confirmed - " + bookingNumber);
        const body = encodeURIComponent("Your ticket has been confirmed!\n\nBooking ID: " + bookingNumber + "\nBus: <?= htmlspecialchars($booking['bus_name']) ?>\nRoute: <?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?>\nDate: <?= date('d M Y', strtotime($booking['journey_date'])) ?>\nSeats: <?= htmlspecialchars($booking['seats']) ?>\nTotal: ₹<?= number_format($booking['total_amount'], 0) ?>\n\nThank you for choosing Great Bus!");
        window.location.href = "mailto:?subject=" + subject + "&body=" + body;
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

    console.log("%c🚌 Great Bus | Ticket Confirmed", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Booking ID: <?= $booking['booking_number'] ?>", "color: #F7931E; font-size: 12px;");
    console.log("%c✓ Share via WhatsApp | Email | Download QR", "color: #10b981; font-size: 12px;");
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