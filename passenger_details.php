<?php
session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?type=user");
    exit();
}

// Check if required session data exists
if (!isset($_SESSION['selected_seats']) || empty($_SESSION['selected_seats'])) {
    echo "<script>alert('No seats selected! Please select seats first.'); window.location.href='search_bus.php';</script>";
    exit();
}

if (!isset($_SESSION['bus_details']) || empty($_SESSION['bus_details'])) {
    echo "<script>alert('Bus details missing! Please search again.'); window.location.href='search_bus.php';</script>";
    exit();
}

$selected_seats = $_SESSION['selected_seats'];
$bus = $_SESSION['bus_details'];
$total_amount = count($selected_seats) * $bus['price_per_seat'];
$error = "";

// Process form submission
if (isset($_POST['confirm_booking'])) {
    // Validate form data
    $valid = true;
    
    if (!isset($_POST['passenger_name']) || !isset($_POST['passenger_age']) || 
        !isset($_POST['passenger_gender']) || !isset($_POST['passenger_phone'])) {
        $valid = false;
        $error = "Please fill all passenger details!";
    }
    
    if ($valid) {
        for ($i = 0; $i < count($selected_seats); $i++) {
            if (empty($_POST['passenger_name'][$i]) || 
                empty($_POST['passenger_age'][$i]) || 
                empty($_POST['passenger_gender'][$i]) || 
                empty($_POST['passenger_phone'][$i])) {
                $valid = false;
                $error = "Please fill all details for passenger " . ($i + 1);
                break;
            }
            
            if (!preg_match('/^[0-9]{10}$/', $_POST['passenger_phone'][$i])) {
                $valid = false;
                $error = "Please enter valid 10-digit phone number for passenger " . ($i + 1);
                break;
            }
            
            if ($_POST['passenger_age'][$i] < 1 || $_POST['passenger_age'][$i] > 120) {
                $valid = false;
                $error = "Please enter valid age (1-120) for passenger " . ($i + 1);
                break;
            }
        }
    }
    
    if ($valid) {
        $passenger_details = [];
        for ($i = 0; $i < count($selected_seats); $i++) {
            $passenger_details[] = [
                'name' => htmlspecialchars($_POST['passenger_name'][$i]),
                'age' => (int)$_POST['passenger_age'][$i],
                'gender' => $_POST['passenger_gender'][$i],
                'phone' => $_POST['passenger_phone'][$i]
            ];
        }
        
        $_SESSION['passenger_details'] = $passenger_details;
        
        header("Location: payment.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Passenger Details - Great Bus</title>
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
            --input-border: #e0e0e0;
            --input-focus: #FF6B35;
            --summary-bg: linear-gradient(135deg, #FF6B35, #F7931E);
            --btn-primary: linear-gradient(135deg, #10b981, #059669);
            --passenger-card-bg: #f8f9fa;
            --error-bg: #fee2e2;
            --error-color: #dc2626;
            --shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --border-color: #3a3a4a;
            --input-border: #3a3a4a;
            --input-focus: #FF8C42;
            --summary-bg: linear-gradient(135deg, #FF6B35, #F7931E);
            --btn-primary: linear-gradient(135deg, #10b981, #059669);
            --passenger-card-bg: #2a2a3a;
            --error-bg: rgba(220, 38, 38, 0.2);
            --error-color: #f87171;
            --shadow: 0 20px 60px rgba(0,0,0,0.5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
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
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 40px;
            padding: 2rem;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 107, 53, 0.3);
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

        h2 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
            text-align: center;
        }

        .page-subtitle {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .booking-summary {
            background: var(--summary-bg);
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

        .passenger-card {
            background: var(--passenger-card-bg);
            border-radius: 20px;
            padding: 1.2rem;
            margin-bottom: 1rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            animation: cardFadeIn 0.4s ease forwards;
            opacity: 0;
            transform: translateX(-20px);
        }

        .passenger-card:nth-child(1) { animation-delay: 0.1s; }
        .passenger-card:nth-child(2) { animation-delay: 0.2s; }
        .passenger-card:nth-child(3) { animation-delay: 0.3s; }
        .passenger-card:nth-child(4) { animation-delay: 0.4s; }
        .passenger-card:nth-child(5) { animation-delay: 0.5s; }
        .passenger-card:nth-child(6) { animation-delay: 0.6s; }

        @keyframes cardFadeIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .passenger-card:hover {
            transform: translateX(5px);
            border-color: #FF6B35;
        }

        .passenger-title {
            font-weight: 600;
            color: #FF6B35;
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        input, select {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            background: var(--card-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
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

        .btn-confirm {
            width: 100%;
            padding: 1rem;
            background: var(--btn-primary);
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
            gap: 10px;
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-confirm::before {
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

        .btn-confirm:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .error-message {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 0.8rem 1rem;
            border-radius: 60px;
            margin-bottom: 1rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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
        }

        @media (max-width: 768px) {
            body { padding: 0.5rem; }
            .container { padding: 1rem; margin: 0.5rem; }
            .booking-summary { flex-direction: column; text-align: center; }
            .form-row { grid-template-columns: 1fr; }
            h2 { font-size: 1.4rem; }
            .progress-steps { gap: 0.3rem; }
            .step { padding: 0.3rem 0.8rem; font-size: 0.7rem; }
            .step-line { width: 15px; }
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

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step completed"><i class="fas fa-check"></i> Select Bus</div>
        <div class="step-line"></div>
        <div class="step completed"><i class="fas fa-check"></i> Select Seats</div>
        <div class="step-line"></div>
        <div class="step active"><i class="fas fa-users"></i> Passenger Details</div>
        <div class="step-line"></div>
        <div class="step"><i class="fas fa-credit-card"></i> Payment</div>
    </div>

    <h2><i class="fas fa-users"></i> Passenger Details</h2>
    <p class="page-subtitle">Please provide details for each passenger</p>
    
    <div class="booking-summary">
        <div>
            <p><strong><i class="fas fa-bus"></i> <?= htmlspecialchars($bus['bus_name']) ?></strong></p>
            <p><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($bus['from_city']) ?> → <?= htmlspecialchars($bus['to_city']) ?></p>
        </div>
        <div>
            <p><i class="fas fa-chair"></i> Seats: <strong><?= implode(', ', $selected_seats) ?></strong></p>
            <p><i class="fas fa-tag"></i> Total: <strong>₹<?= number_format($total_amount, 0) ?></strong></p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>

    <form method="post" id="passengerForm">
        <?php foreach ($selected_seats as $index => $seat): ?>
            <div class="passenger-card">
                <div class="passenger-title">
                    <i class="fas fa-user-circle"></i> Passenger for Seat <?= $seat ?>
                </div>
                <div class="form-row">
                    <input type="text" name="passenger_name[]" placeholder="Full Name" required>
                    <input type="number" name="passenger_age[]" placeholder="Age" min="1" max="120" required>
                    <select name="passenger_gender[]" required>
                        <option value="">Select Gender</option>
                        <option value="Male">👨 Male</option>
                        <option value="Female">👩 Female</option>
                        <option value="Other">👤 Other</option>
                    </select>
                    <input type="tel" name="passenger_phone[]" placeholder="Phone Number (10 digits)" pattern="[0-9]{10}" maxlength="10" required>
                </div>
            </div>
        <?php endforeach; ?>
        
        <button type="submit" name="confirm_booking" class="btn-confirm">
            <i class="fas fa-credit-card"></i> Proceed to Payment
        </button>
    </form>
    
    <a href="select_seats.php?bus_id=<?= $_SESSION['bus_id'] ?>&date=<?= $_SESSION['travel_date'] ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Seats
    </a>
</div>

<script>
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

    // Phone number validation
    document.querySelectorAll('input[type="tel"]').forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    });
    
    // Form validation
    const passengerForm = document.getElementById('passengerForm');
    if (passengerForm) {
        passengerForm.addEventListener('submit', function(e) {
            const phones = document.querySelectorAll('input[type="tel"]');
            for (let phone of phones) {
                if (phone.value.length !== 10) {
                    e.preventDefault();
                    alert('Please enter valid 10-digit phone number for all passengers');
                    phone.focus();
                    return false;
                }
            }
            
            const ages = document.querySelectorAll('input[type="number"]');
            for (let age of ages) {
                if (age.value < 1 || age.value > 120) {
                    e.preventDefault();
                    alert('Please enter valid age (1-120) for all passengers');
                    age.focus();
                    return false;
                }
            }
            
            // Add ripple effect on button
            const btn = document.querySelector('.btn-confirm');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            return true;
        });
    }

    console.log("%c🚌 Great Bus | Passenger Details Page", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Enhanced Logo | Progress Steps | Mobile Optimized", "color: #F7931E; font-size: 12px;");
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