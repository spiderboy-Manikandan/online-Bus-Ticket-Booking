<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?type=user");
    exit();
}

if (!isset($_SESSION['passenger_details']) || !isset($_SESSION['selected_seats'])) {
    echo "<script>alert('Session expired! Please try again.'); window.location.href='search_bus.php';</script>";
    exit();
}

// Verify bus exists in database
$bus_id = $_SESSION['bus_id'];
$bus_check = $conn->query("SELECT * FROM buses WHERE id='$bus_id'");
if ($bus_check->num_rows == 0) {
    echo "<script>alert('Bus not found! Please search again.'); window.location.href='search_bus.php';</script>";
    exit();
}

$bus = $_SESSION['bus_details'];
$selected_seats = $_SESSION['selected_seats'];
$total_amount = count($selected_seats) * $bus['price_per_seat'];
$error = "";
$show_card_form = false;
$show_qr = false;
$payment_method = "";
$upi_id = "";

// Handle payment submission
if (isset($_POST['pay'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    
    if ($payment_method == 'card') {
        // Card payment validation
        $card_name = $_POST['card_name'] ?? '';
        $card_number = $_POST['card_number'] ?? '';
        $card_expiry = $_POST['card_expiry'] ?? '';
        $card_cvv = $_POST['card_cvv'] ?? '';
        
        // Basic card validation
        if (empty($card_name) || empty($card_number) || empty($card_expiry) || empty($card_cvv)) {
            $error = "Please fill all card details";
        } elseif (!preg_match('/^[0-9]{16}$/', str_replace(' ', '', $card_number))) {
            $error = "Please enter a valid 16-digit card number";
        } elseif (!preg_match('/^[0-9]{3}$/', $card_cvv)) {
            $error = "Please enter a valid 3-digit CVV";
        } else {
            // Process payment and create booking
            processBooking($conn);
        }
    } 
    elseif ($payment_method == 'gpay' || $payment_method == 'phonepe' || $payment_method == 'paytm' || $payment_method == 'online') {
        // Show QR code for UPI payment
        $show_qr = true;
        $payment_method = $payment_method;
        $upi_id = getUPIId($payment_method);
        $_SESSION['pending_payment'] = [
            'method' => $payment_method,
            'amount' => $total_amount,
            'upi_id' => $upi_id
        ];
    }
}
elseif (isset($_POST['verify_payment'])) {
    // Verify payment and complete booking
    if (isset($_SESSION['pending_payment'])) {
        processBooking($conn);
        unset($_SESSION['pending_payment']);
    } else {
        $error = "Payment session expired. Please try again.";
    }
}
elseif (isset($_POST['cancel'])) {
    unset($_SESSION['pending_payment']);
    $show_qr = false;
}

function getUPIId($method) {
    switch($method) {
        case 'gpay':
            return 'greatbus@okhdfcbank';
        case 'phonepe':
            return 'greatbus@ybl';
        case 'paytm':
            return 'greatbus@paytm';
        case 'online':
            return 'greatbus@okhdfcbank';
        default:
            return 'greatbus@okhdfcbank';
    }
}

function processBooking($conn) {
    global $bus, $selected_seats, $total_amount;
    
    $booking_number = "GB" . date('Ymd') . rand(1000, 9999);
    $user_id = $_SESSION['user_id'];
    $bus_id = $_SESSION['bus_id'];
    $seats = implode(',', $selected_seats);
    $journey_date = $_SESSION['travel_date'];
    $passenger_details = json_encode($_SESSION['passenger_details']);
    
    $insert = $conn->prepare("INSERT INTO bookings (booking_number, user_id, bus_id, seats, passenger_details, total_amount, journey_date, payment_status, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', 'confirmed')");
    $insert->bind_param("siissds", $booking_number, $user_id, $bus_id, $seats, $passenger_details, $total_amount, $journey_date);
    
    if ($insert->execute()) {
        $booking_id = $insert->insert_id;
        
        // Try to store individual seat bookings (skip if table doesn't exist)
        try {
            $seat_stmt = $conn->prepare("INSERT INTO booked_seats (booking_id, seat_number) VALUES (?, ?)");
            if ($seat_stmt) {
                foreach ($selected_seats as $seat) {
                    $seat_stmt->bind_param("is", $booking_id, $seat);
                    $seat_stmt->execute();
                }
            }
        } catch (Exception $e) {
            // Table doesn't exist - that's fine, we already have seats in the bookings table
            error_log("booked_seats table not found: " . $e->getMessage());
        }
        
        $_SESSION['booking_number'] = $booking_number;
        header("Location: ticket.php");
        exit();
    } else {
        $error = "Booking failed: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Payment - Great Bus</title>
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
            --summary-bg: #f8f9fa;
            --payment-option-bg: #ffffff;
            --payment-option-hover: #f8f9ff;
            --btn-pay: linear-gradient(135deg, #10b981, #059669);
            --error-bg: #fee2e2;
            --error-color: #dc2626;
            --shadow: 0 20px 60px rgba(0,0,0,0.3);
            --success-bg: #d1fae5;
            --success-color: #065f46;
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --border-color: #3a3a4a;
            --summary-bg: #2a2a3a;
            --payment-option-bg: #2a2a3a;
            --payment-option-hover: #3a3a4a;
            --btn-pay: linear-gradient(135deg, #10b981, #059669);
            --error-bg: rgba(220, 38, 38, 0.2);
            --error-color: #f87171;
            --shadow: 0 20px 60px rgba(0,0,0,0.5);
            --success-bg: rgba(16, 185, 129, 0.2);
            --success-color: #34d399;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            padding: 2rem;
            transition: background 0.3s ease;
            position: relative;
        }

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
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .payment-container {
            position: relative;
            z-index: 10;
            max-width: 600px;
            margin: 0 auto;
        }

        .payment-card {
            background: var(--card-bg);
            border-radius: 48px;
            padding: 2rem;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease;
            transition: all 0.3s ease;
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
            padding: 0.5rem 1rem;
            border-radius: 50px;
            background: rgba(0,0,0,0.05);
            color: var(--text-secondary);
            font-size: 0.8rem;
        }

        .step.active {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
        }

        .step.completed {
            background: #10b981;
            color: white;
        }

        .step i {
            font-size: 0.9rem;
        }

        .step-line {
            width: 30px;
            height: 2px;
            background: var(--border-color);
        }

        h2 {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.6rem;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .booking-summary {
            background: var(--summary-bg);
            border-radius: 20px;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.7rem;
            color: var(--text-primary);
        }

        .summary-item span:first-child {
            color: var(--text-secondary);
        }

        .total {
            border-top: 2px solid var(--border-color);
            padding-top: 0.8rem;
            margin-top: 0.8rem;
            font-size: 1.1rem;
        }

        .total span:last-child {
            color: #10b981;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .payment-options {
            margin: 1rem 0;
            animation: fadeInUp 0.6s ease;
        }

        .payment-method {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 20px;
            margin-bottom: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: var(--payment-option-bg);
        }

        .payment-method:hover {
            border-color: #FF6B35;
            transform: translateX(5px);
        }

        .payment-method input {
            margin-right: 1rem;
            transform: scale(1.2);
            accent-color: #FF6B35;
        }

        .payment-method i {
            font-size: 1.8rem;
            margin-right: 1rem;
        }

        .payment-method .fa-google-pay { color: #3b82f6; }
        .payment-method .fa-mobile-alt { color: #5f27cd; }
        .payment-method .fa-rupee-sign { color: #00b3f0; }
        .payment-method .fa-credit-card { color: #FF6B35; }
        .payment-method .fa-qrcode { color: #FF6B35; }

        /* Card Form */
        .card-form {
            background: var(--summary-bg);
            border-radius: 20px;
            padding: 1.5rem;
            margin-top: 1rem;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
            font-size: 0.85rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .card-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* QR Code Section */
        .qr-section {
            text-align: center;
            padding: 1rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .qr-code-container {
            background: white;
            padding: 1.5rem;
            border-radius: 20px;
            display: inline-block;
            margin: 1rem 0;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        #qrcode {
            display: flex;
            justify-content: center;
        }

        #qrcode canvas, #qrcode img {
            width: 200px !important;
            height: 200px !important;
        }

        .upi-id {
            background: var(--summary-bg);
            padding: 0.8rem;
            border-radius: 12px;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 1.1rem;
            color: var(--text-primary);
        }

        .copy-btn {
            background: #FF6B35;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }

        .btn-pay, .btn-verify {
            width: 100%;
            padding: 1rem;
            background: var(--btn-pay);
            color: white;
            border: none;
            border-radius: 60px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-pay::before, .btn-verify::before {
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

        .btn-pay:hover::before, .btn-verify:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-pay:hover, .btn-verify:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-back {
            background: #6c757d;
            margin-top: 0.5rem;
        }

        .error {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 0.8rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            text-align: center;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .secure-badge {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        @media (max-width: 550px) {
            body { padding: 0.5rem; }
            .payment-card { padding: 1.5rem; margin: 1rem; }
            h2 { font-size: 1.3rem; }
            .step { padding: 0.3rem 0.8rem; font-size: 0.7rem; }
            .step-line { width: 15px; }
            .logo-icon { width: 55px; height: 55px; }
            .progress-steps { gap: 0.3rem; }
        }
    </style>
</head>
<body>

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

<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<div class="payment-container">
    <div class="payment-card">
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
            <div class="step completed"><i class="fas fa-check"></i> Passenger Details</div>
            <div class="step-line"></div>
            <div class="step active"><i class="fas fa-credit-card"></i> Payment</div>
        </div>
        
        <h2>Secure Payment</h2>
        <p class="subtitle">Complete your booking securely</p>
        
        <?php if ($error): ?>
            <div class="error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>
        
        <div class="booking-summary">
            <div class="summary-item">
                <span><i class="fas fa-bus"></i> Bus:</span>
                <span><strong><?= htmlspecialchars($bus['bus_name']) ?></strong></span>
            </div>
            <div class="summary-item">
                <span><i class="fas fa-map-marker-alt"></i> Route:</span>
                <span><?= htmlspecialchars($bus['from_city']) ?> → <?= htmlspecialchars($bus['to_city']) ?></span>
            </div>
            <div class="summary-item">
                <span><i class="fas fa-chair"></i> Seats:</span>
                <span><?= implode(', ', $selected_seats) ?></span>
            </div>
            <div class="summary-item">
                <span><i class="fas fa-users"></i> Passengers:</span>
                <span><?= count($selected_seats) ?></span>
            </div>
            <div class="summary-item total">
                <span><i class="fas fa-rupee-sign"></i> Total Amount:</span>
                <span><strong>₹<?= number_format($total_amount, 0) ?></strong></span>
            </div>
        </div>

        <?php if (!$show_qr): ?>
        <form method="post" id="paymentForm">
            <div class="payment-options">
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="gpay" required>
                    <i class="fab fa-google-pay"></i>
                    <span>Google Pay</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="phonepe">
                    <i class="fas fa-mobile-alt"></i>
                    <span>PhonePe</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="paytm">
                    <i class="fas fa-rupee-sign"></i>
                    <span>Paytm</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="online">
                    <i class="fas fa-qrcode"></i>
                    <span>UPI QR Code (Any UPI App)</span>
                </label>
                <label class="payment-method">
                    <input type="radio" name="payment_method" value="card">
                    <i class="fas fa-credit-card"></i>
                    <span>Credit/Debit Card</span>
                </label>
            </div>

            <div id="cardDetails" style="display: none;">
                <div class="card-form">
                    <h4 style="margin-bottom: 1rem; color: var(--text-primary);"><i class="fas fa-credit-card"></i> Card Details</h4>
                    <div class="form-group">
                        <label>Cardholder Name</label>
                        <input type="text" name="card_name" placeholder="John Doe" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Card Number</label>
                        <input type="text" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" autocomplete="off">
                    </div>
                    <div class="card-row">
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="text" name="card_expiry" placeholder="MM/YY" maxlength="5" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>CVV</label>
                            <input type="password" name="card_cvv" placeholder="123" maxlength="3" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" name="pay" class="btn-pay" id="payBtn">
                <i class="fas fa-lock"></i> Pay ₹<?= number_format($total_amount, 0) ?> & Confirm
            </button>
        </form>
        <?php endif; ?>

        <?php if ($show_qr && isset($_SESSION['pending_payment'])): 
            $pending = $_SESSION['pending_payment'];
            
            // Create QR data with UPI intent URL
            $upi_id = $pending['upi_id'];
            $amount = $pending['amount'];
            $name = "Great Bus Booking";
            $note = "Bus Ticket Booking";
            
            // Create UPI Intent URL for QR code
            $upi_url = "payto://pay?pa=" . urlencode($upi_id) . 
                       "&pn=" . urlencode($name) . 
                       "&am=" . $amount . 
                       "&cu=INR" .
                       "&tn=" . urlencode($note);
        ?>
        <div class="qr-section">
            <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">
                <i class="fas fa-qrcode"></i> Scan & Pay
            </h3>
            <p class="subtitle">Scan this QR code with any UPI app</p>
            
            <div class="qr-code-container">
                <div id="qrcode"></div>
            </div>
            
            <div class="upi-id">
                <strong>UPI ID:</strong> <?= $upi_id ?>
                <button class="copy-btn" onclick="copyUPI()"><i class="fas fa-copy"></i> Copy</button>
            </div>
            
            <div class="booking-summary" style="margin: 1rem 0;">
                <div class="summary-item">
                    <span>Amount to Pay:</span>
                    <span><strong style="color: #10b981; font-size: 1.3rem;">₹<?= number_format($amount, 0) ?></strong></span>
                </div>
            </div>
            
            <div class="secure-badge" style="margin-top: 0;">
                <i class="fas fa-clock"></i> Complete payment within 15 minutes
            </div>
            
            <form method="post">
                <button type="submit" name="verify_payment" class="btn-verify" onclick="return confirmPayment()">
                    <i class="fas fa-check-circle"></i> I have completed the payment
                </button>
            </form>
            
            <form method="post" style="margin-top: 0.5rem;">
                <button type="submit" name="cancel" class="btn-pay btn-back">
                    <i class="fas fa-arrow-left"></i> Back to Payment Options
                </button>
            </form>
        </div>
        <?php endif; ?>

        <div class="secure-badge">
            <i class="fas fa-shield-alt"></i> 100% Secure Payment | SSL Encrypted
        </div>
    </div>
</div>

<script>
    <?php if ($show_qr && isset($_SESSION['pending_payment'])): ?>
    // Generate QR Code when QR section is visible
    window.onload = function() {
        setTimeout(function() {
            const qrText = <?= json_encode($upi_url) ?>;
            const qrDiv = document.getElementById('qrcode');
            if (qrDiv && typeof QRCode !== 'undefined') {
                qrDiv.innerHTML = '';
                new QRCode(qrDiv, {
                    text: qrText,
                    width: 200,
                    height: 200,
                    colorDark: "#FF6B35",
                    colorLight: "#ffffff"
                });
            } else {
                // Fallback if QR library not loaded
                qrDiv.innerHTML = '<div style="width:200px;height:200px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;border-radius:10px;"><i class="fas fa-qrcode" style="font-size:80px;color:#FF6B35;"></i></div>';
            }
        }, 100);
    };
    <?php endif; ?>

    function copyUPI() {
        const upiText = document.querySelector('.upi-id strong').innerText;
        navigator.clipboard.writeText(upiText).then(() => {
            alert('UPI ID copied: ' + upiText + '\n\nPlease make payment of ₹<?= $total_amount ?> using this UPI ID');
        }).catch(() => {
            alert('UPI ID: ' + upiText + '\n\nPlease make payment of ₹<?= $total_amount ?>');
        });
    }

    function confirmPayment() {
        return confirm('Have you completed the payment?\n\nClick OK to confirm and get your ticket.');
    }

    // Card Number Formatting
    const cardInput = document.querySelector('input[name="card_number"]');
    if (cardInput) {
        cardInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\s/g, '');
            if (value.length > 16) value = value.slice(0, 16);
            let formatted = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            this.value = formatted;
        });
    }

    // Expiry Date Formatting
    const expiryInput = document.querySelector('input[name="card_expiry"]');
    if (expiryInput) {
        expiryInput.addEventListener('input', function(e) {
            let value = this.value.replace(/\//g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2,4);
            }
            this.value = value;
        });
    }

    // Show/Hide Card Form
    const radioInputs = document.querySelectorAll('input[name="payment_method"]');
    const cardDetails = document.getElementById('cardDetails');

    radioInputs.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
    });

    // Form validation
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                e.preventDefault();
                alert('Please select a payment method');
                return false;
            }
            
            if (selectedMethod.value === 'card') {
                const cardName = document.querySelector('input[name="card_name"]').value;
                const cardNumber = document.querySelector('input[name="card_number"]').value;
                const cardExpiry = document.querySelector('input[name="card_expiry"]').value;
                const cardCvv = document.querySelector('input[name="card_cvv"]').value;
                
                if (!cardName || !cardNumber || !cardExpiry || !cardCvv) {
                    e.preventDefault();
                    alert('Please fill all card details');
                    return false;
                }
                
                const cleanNumber = cardNumber.replace(/\s/g, '');
                if (cleanNumber.length !== 16) {
                    e.preventDefault();
                    alert('Please enter a valid 16-digit card number');
                    return false;
                }
                
                if (cardCvv.length !== 3) {
                    e.preventDefault();
                    alert('Please enter a valid 3-digit CVV');
                    return false;
                }
                
                // Show processing message
                const btn = document.getElementById('payBtn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing Payment...';
                btn.disabled = true;
                return true;
            }
        });
    }

    // Dark Mode Toggle
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

    console.log("%c🚌 Great Bus | Payment Page", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Enhanced Logo | Progress Steps | Secure Payment", "color: #F7931E; font-size: 12px;");
</script>
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