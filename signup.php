<?php
session_start();
include "db.php";

$error = "";
$success = "";

if (isset($_POST['signup'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($phone) || empty($pass)) {
        $error = "Please fill all fields!";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Please enter a valid 10-digit phone number!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } elseif ($pass !== $confirm_pass) {
        $error = "Passwords do not match!";
    } elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters!";
    } else {
        $check = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($check && $check->num_rows > 0) {
            $error = "Email already registered! Please login.";
        } else {
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (fullname, email, phone, password) VALUES ('$name', '$email', '$phone', '$hashed_pass')");
            $success = "Account created successfully! Redirecting to login...";
            echo "<meta http-equiv='refresh' content='2;url=login.php?type=user'>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Sign Up - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Variables for Orange Theme */
        :root {
            --bg-gradient: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-primary: #333;
            --text-secondary: #666;
            --input-border: #e0e0e0;
            --input-focus: #FF6B35;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --success-bg: #d4edda;
            --success-color: #155724;
            --error-bg: #fee2e2;
            --error-color: #dc2626;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: rgba(30, 30, 46, 0.98);
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --input-border: #3a3a4a;
            --input-focus: #FF8C42;
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --success-bg: rgba(40, 167, 69, 0.2);
            --success-color: #2ecc71;
            --error-bg: rgba(220, 38, 38, 0.2);
            --error-color: #f87171;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
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
            color: rgba(255, 255, 255, 0.1);
            animation: floatBus 20s infinite linear;
        }

        @keyframes floatBus {
            0% {
                transform: translateX(-10%) translateY(10vh);
                opacity: 0;
            }
            10% { opacity: 0.1; }
            90% { opacity: 0.1; }
            100% {
                transform: translateX(110vw) translateY(-10vh);
                opacity: 0;
            }
        }

        .floating-bus:nth-child(1) { top: 15%; left: -5%; animation-duration: 18s; }
        .floating-bus:nth-child(2) { top: 45%; left: -8%; animation-duration: 25s; animation-delay: 3s; }
        .floating-bus:nth-child(3) { top: 70%; left: -3%; animation-duration: 22s; animation-delay: 6s; }
        .floating-bus:nth-child(4) { top: 85%; left: -10%; animation-duration: 28s; animation-delay: 9s; }

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
            10% { opacity: 0.6; }
            90% { opacity: 0.6; }
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

        /* Signup Container */
        .signup-container {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 2.5rem;
            width: 100%;
            max-width: 550px;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease;
            border: 1px solid rgba(255, 107, 53, 0.3);
            transition: all 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .signup-container:hover {
            transform: translateY(-5px);
        }

        /* Logo Section - ENHANCED ANIMATION */
        .logo-section {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 0.5rem;
            position: relative;
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Pulsing ring behind logo */
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
            0% {
                transform: scale(0.8);
                opacity: 0.5;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }

        /* Rotating ring around logo */
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
            transition: all 0.3s ease;
            animation: iconGlow 2s ease-in-out infinite;
        }

        @keyframes iconGlow {
            0%, 100% { filter: drop-shadow(0 4px 10px rgba(0,0,0,0.2)); }
            50% { filter: drop-shadow(0 0 20px rgba(255,107,53,0.5)); }
        }

        .logo-icon:hover svg {
            transform: scale(1.05);
        }

        h2 {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.8rem;
            margin-top: 0.5rem;
            font-weight: 700;
            animation: titleSlideIn 0.5s ease;
        }

        @keyframes titleSlideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            font-size: 0.9rem;
            animation: subtitleFadeIn 0.6s ease;
        }

        @keyframes subtitleFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Input Groups with animation */
        .input-group {
            margin-bottom: 1.5rem;
            position: relative;
            animation: inputSlideUp 0.5s ease forwards;
            opacity: 0;
            transform: translateX(-10px);
        }

        .input-group:nth-child(1) { animation-delay: 0.1s; }
        .input-group:nth-child(2) { animation-delay: 0.15s; }
        .input-group:nth-child(3) { animation-delay: 0.2s; }
        .input-group:nth-child(4) { animation-delay: 0.25s; }
        .input-group:nth-child(5) { animation-delay: 0.3s; }

        @keyframes inputSlideUp {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .input-group i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #FF6B35;
            font-size: 1.1rem;
            z-index: 1;
            transition: all 0.3s ease;
        }

        input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--input-border);
            border-radius: 60px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: var(--card-bg);
            color: var(--text-primary);
        }

        input:focus {
            outline: none;
            border-color: var(--input-focus);
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
        }

        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 1.1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #FF6B35;
        }

        /* Password Strength Indicator */
        .strength-container {
            margin-top: -0.5rem;
            margin-bottom: 1rem;
            margin-left: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: strengthFadeIn 0.3s ease;
        }

        @keyframes strengthFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .strength-bar {
            flex: 1;
            height: 5px;
            background: rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .strength-fill {
            width: 0%;
            height: 100%;
            transition: width 0.3s ease;
        }

        .strength-text {
            font-size: 0.7rem;
            color: var(--text-secondary);
            min-width: 60px;
        }

        /* Button with animation */
        .btn-signup {
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
            margin-top: 0.5rem;
            animation: buttonPulse 2s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes buttonPulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            50% { box-shadow: 0 8px 25px rgba(255,107,53,0.4); }
        }

        .btn-signup::before {
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

        .btn-signup:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-signup:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
            animation: none;
        }

        /* Messages */
        .success {
            background: var(--success-bg);
            color: var(--success-color);
            padding: 0.8rem 1rem;
            border-radius: 60px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            animation: successSlideIn 0.4s ease;
        }

        @keyframes successSlideIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error {
            background: var(--error-bg);
            color: var(--error-color);
            padding: 0.8rem 1rem;
            border-radius: 60px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
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

        /* Links */
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
            animation: linkFadeIn 0.7s ease;
        }

        @keyframes linkFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-link a {
            color: #FF6B35;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        /* Terms */
        .terms {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 550px) {
            .signup-container { padding: 1.8rem; margin: 1rem; }
            h2 { font-size: 1.5rem; }
            .logo-icon { width: 65px; height: 65px; }
            .theme-toggle { padding: 6px 12px; font-size: 0.8rem; top: 15px; right: 15px; }
            
            /* Mobile animation adjustments */
            @keyframes logoFloat {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-5px); }
            }
        }

        /* Tablet Responsive */
        @media (min-width: 551px) and (max-width: 768px) {
            .logo-icon { width: 75px; height: 75px; }
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
</div>

<script>
    // Generate floating particles
    (function() {
        const bg = document.querySelector('.animated-bg');
        for (let i = 0; i < 60; i++) {
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

<div class="signup-container">
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
        <h2>Create Account</h2>
        <p class="subtitle">Join Great Bus family today!</p>
    </div>

    <?php if ($success): ?>
        <div class="success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="post" id="signupForm">
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="name" placeholder="Full Name" required autocomplete="name">
        </div>
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Email Address" required autocomplete="email">
        </div>
        <div class="input-group">
            <i class="fas fa-phone"></i>
            <input type="tel" name="phone" id="phone" placeholder="Phone Number (10 digits)" maxlength="10" required autocomplete="tel">
        </div>
        <div class="input-group" id="passwordGroup">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="password" placeholder="Password (min 6 characters)" required>
            <i class="fas fa-eye-slash password-toggle" id="togglePassword"></i>
        </div>
        <div class="strength-container" id="strengthContainer" style="display: none;">
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <span class="strength-text" id="strengthText">Weak</span>
        </div>
        <div class="input-group" id="confirmGroup">
            <i class="fas fa-check-circle"></i>
            <input type="password" name="confirm_password" id="confirm" placeholder="Confirm Password" required>
            <i class="fas fa-eye-slash password-toggle" id="toggleConfirm"></i>
        </div>
        <button type="submit" name="signup" class="btn-signup">
            <i class="fas fa-user-plus"></i> Sign Up
        </button>
    </form>

    <div class="login-link">
        Already have an account? <a href="login.php?type=user">Login <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="terms">
        By signing up, you agree to our Terms & Conditions and Privacy Policy.
    </div>
</div>

<script>
    // Phone number validation (only numbers, max 10 digits)
    const phoneInput = document.getElementById('phone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });
    }

    // Password Strength Checker
    const password = document.getElementById('password');
    const strengthContainer = document.getElementById('strengthContainer');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');

    function checkPasswordStrength(pwd) {
        if (pwd.length === 0) {
            strengthContainer.style.display = 'none';
            return;
        }
        strengthContainer.style.display = 'flex';
        
        let score = 0;
        if (pwd.length >= 6) score++;
        if (pwd.length >= 10) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[^A-Za-z0-9]/.test(pwd)) score++;
        
        let percentage = 0, label = '', color = '';
        if (score <= 2) { percentage = 28; label = 'Weak'; color = '#dc2626'; }
        else if (score === 3) { percentage = 50; label = 'Fair'; color = '#f59e0b'; }
        else if (score === 4) { percentage = 75; label = 'Good'; color = '#10b981'; }
        else { percentage = 100; label = 'Strong'; color = '#2ecc71'; }
        
        strengthFill.style.width = percentage + '%';
        strengthFill.style.backgroundColor = color;
        strengthText.textContent = label;
        strengthText.style.color = color;
    }

    if (password) {
        password.addEventListener('input', function() {
            checkPasswordStrength(this.value);
            validateConfirmPassword();
        });
    }

    // Password visibility toggles
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirm = document.getElementById('toggleConfirm');
    const confirm = document.getElementById('confirm');

    if (togglePassword && password) {
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    if (toggleConfirm && confirm) {
        toggleConfirm.addEventListener('click', function() {
            const type = confirm.getAttribute('type') === 'password' ? 'text' : 'password';
            confirm.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }

    // Confirm password validation
    function validateConfirmPassword() {
        if (password && confirm) {
            if (password.value !== confirm.value) {
                confirm.setCustomValidity("Passwords don't match");
            } else {
                confirm.setCustomValidity('');
            }
        }
    }

    if (confirm) {
        confirm.addEventListener('keyup', validateConfirmPassword);
        confirm.addEventListener('change', validateConfirmPassword);
    }

    // Ripple effect on signup button
    const signupBtn = document.querySelector('.btn-signup');
    if (signupBtn) {
        signupBtn.addEventListener('click', function(e) {
            let ripple = document.createElement('span');
            ripple.style.position = 'absolute';
            ripple.style.borderRadius = '50%';
            ripple.style.backgroundColor = 'rgba(255,255,255,0.5)';
            ripple.style.width = '20px';
            ripple.style.height = '20px';
            ripple.style.transform = 'scale(1)';
            ripple.style.opacity = '1';
            ripple.style.transition = 'transform 0.4s, opacity 0.4s';
            ripple.style.pointerEvents = 'none';
            
            const rect = this.getBoundingClientRect();
            ripple.style.left = `${e.clientX - rect.left - 10}px`;
            ripple.style.top = `${e.clientY - rect.top - 10}px`;
            ripple.style.position = 'absolute';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.style.transform = 'scale(30)';
                ripple.style.opacity = '0';
                setTimeout(() => ripple.remove(), 400);
            }, 10);
        });
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

    // Console greeting
    console.log("%c🚌 Great Bus | Sign Up Page", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Enhanced Logo Animation | Rotating Ring | Floating Effect", "color: #F7931E; font-size: 12px;");
    console.log("%c✓ Mobile Optimized | Smooth Animations", "color: #FFD700; font-size: 12px;");
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