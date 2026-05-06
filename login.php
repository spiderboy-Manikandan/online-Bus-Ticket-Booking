<?php
session_start();
include "db.php";

$error = "";
$login_type = isset($_GET['type']) ? $_GET['type'] : 'user';

// User Login
if (isset($_POST['user_login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $pass = $_POST['password'];
    
    if (empty($email) || empty($pass)) {
        $error = "Please fill all fields!";
    } else {
        $res = $conn->query("SELECT * FROM users WHERE email='$email'");
        if ($res && $res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($pass, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['fullname'];
                $_SESSION['phone'] = $user['phone'];
                $_SESSION['user_email'] = $user['email'];
                header("Location: search_bus.php");
                exit();
            } else { 
                $error = "Invalid password!"; 
            }
        } else { 
            $error = "Email not found!"; 
        }
    }
}

// Admin Login
if (isset($_POST['admin_login'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please fill all fields!";
    } else {
        $res = $conn->query("SELECT * FROM admin WHERE email='$email'");
        if ($res && $res->num_rows > 0) {
            $admin = $res->fetch_assoc();
            if ($password == $admin['password']) {
                $_SESSION['admin'] = true;
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_name'] = $admin['name'] ?? 'Admin';
                header("Location: admin/dashboard.php");
                exit();
            } else { 
                $error = "Invalid password!"; 
            }
        } else { 
            $error = "Admin email not found!"; 
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?= $login_type == 'user' ? 'User Login' : 'Admin Login' ?> - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --user-gradient: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            --admin-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --card-bg: rgba(255, 255, 255, 0.98);
            --text-primary: #333;
            --text-secondary: #666;
            --input-border: #e0e0e0;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        body.dark {
            --card-bg: rgba(30, 30, 46, 0.98);
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --input-border: #3a3a4a;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            transition: background 0.3s ease;
            position: relative;
            overflow-x: hidden;
        }

        body.user-mode {
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
        }

        body.admin-mode {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
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
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.05);
            background: rgba(0, 0, 0, 0.8);
        }

        /* Login Container */
        .login-container {
            position: relative;
            z-index: 10;
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border-radius: 48px;
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            box-shadow: var(--shadow);
            animation: slideUp 0.6s ease;
            transition: all 0.3s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        /* Toggle Buttons */
        .login-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 60px;
            padding: 0.3rem;
        }

        body.dark .login-tabs {
            background: rgba(255, 255, 255, 0.05);
        }

        .tab-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: transparent;
            color: var(--text-secondary);
        }

        .tab-btn.active {
            background: var(--active-gradient);
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .tab-btn.user-tab.active {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
        }

        .tab-btn.admin-tab.active {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
        }

        .tab-btn i {
            font-size: 1rem;
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

        /* Rotating ring for user mode */
        body.user-mode .logo-icon::after {
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

        /* Rotating ring for admin mode */
        body.admin-mode .logo-icon::after {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            right: -8px;
            bottom: -8px;
            border-radius: 50%;
            background: conic-gradient(from 0deg, #1e3c72, #2a5298, #4a6fa5, #1e3c72);
            animation: rotateRing 3s linear infinite;
            z-index: -2;
            opacity: 0.5;
        }

        @keyframes rotateRing {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .logo-icon i {
            font-size: 3.2rem;
            transition: all 0.3s ease;
            animation: iconGlow 2s ease-in-out infinite;
        }

        @keyframes iconGlow {
            0%, 100% { text-shadow: 0 0 0px rgba(0,0,0,0); }
            50% { text-shadow: 0 0 20px rgba(255,215,0,0.5); }
        }

        body.user-mode .logo-icon i {
            color: #FF6B35;
        }

        body.admin-mode .logo-icon i {
            color: #2a5298;
        }

        .logo-icon:hover i {
            transform: scale(1.1);
        }

        h2 {
            text-align: center;
            color: var(--text-primary);
            font-size: 1.6rem;
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
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            animation: subtitleFadeIn 0.6s ease;
        }

        @keyframes subtitleFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Input Groups with animation */
        .input-group {
            margin-bottom: 1.2rem;
            position: relative;
            animation: inputSlideUp 0.5s ease forwards;
            opacity: 0;
            transform: translateX(-10px);
        }

        .input-group:nth-child(1) { animation-delay: 0.1s; }
        .input-group:nth-child(2) { animation-delay: 0.2s; }

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
            font-size: 1rem;
            z-index: 1;
            transition: all 0.3s ease;
        }

        body.user-mode .input-group i {
            color: #FF6B35;
        }

        body.admin-mode .input-group i {
            color: #2a5298;
        }

        input {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 3rem;
            border: 2px solid var(--input-border);
            border-radius: 60px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            background: var(--card-bg);
            color: var(--text-primary);
        }

        input:focus {
            outline: none;
        }

        body.user-mode input:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.2);
        }

        body.admin-mode input:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.2);
        }

        input::placeholder {
            color: var(--text-secondary);
            opacity: 0.7;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            font-size: 1rem;
            z-index: 2;
            transition: color 0.3s ease;
        }

        body.user-mode .password-toggle:hover {
            color: #FF6B35;
        }

        body.admin-mode .password-toggle:hover {
            color: #2a5298;
        }

        /* Button with animation */
        .btn-login {
            width: 100%;
            padding: 0.9rem;
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
            gap: 10px;
            margin-top: 0.5rem;
            animation: buttonPulse 2s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        @keyframes buttonPulse {
            0%, 100% { box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
            50% { box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        }

        body.user-mode .btn-login {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
        }

        body.admin-mode .btn-login {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
        }

        .btn-login:hover {
            transform: translateY(-3px);
            filter: brightness(1.05);
            animation: none;
        }

        .btn-login:active {
            transform: translateY(2px);
        }

        /* Ripple effect */
        .btn-login::before {
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

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        /* Error Message */
        .error {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            padding: 0.7rem 1rem;
            border-radius: 60px;
            margin-bottom: 1.2rem;
            text-align: center;
            font-size: 0.85rem;
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
        .signup-link {
            text-align: center;
            margin-top: 1.2rem;
            color: var(--text-secondary);
            font-size: 0.85rem;
            animation: linkFadeIn 0.7s ease;
        }

        @keyframes linkFadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .signup-link a {
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        body.user-mode .signup-link a {
            color: #FF6B35;
        }

        body.admin-mode .signup-link a {
            color: #2a5298;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 1rem;
        }

        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        body.user-mode .back-home a:hover {
            color: #FF6B35;
        }

        body.admin-mode .back-home a:hover {
            color: #2a5298;
        }

        .demo-hint {
            margin-top: 1.2rem;
            padding: 0.7rem;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 12px;
            text-align: center;
            font-size: 0.7rem;
            color: var(--text-secondary);
            animation: hintFadeIn 0.8s ease;
        }

        @keyframes hintFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @media (max-width: 550px) {
            .login-container { padding: 1.5rem; margin: 1rem; }
            h2 { font-size: 1.3rem; }
            .logo-icon { width: 65px; height: 65px; }
            .logo-icon i { font-size: 2.5rem; }
            .tab-btn { padding: 0.6rem; font-size: 0.8rem; }
            
            /* Mobile animation adjustments */
            @keyframes logoFloat {
                0%, 100% { transform: translateY(0px); }
                50% { transform: translateY(-5px); }
            }
        }

        /* Tablet Responsive */
        @media (min-width: 551px) and (max-width: 768px) {
            .logo-icon { width: 75px; height: 75px; }
            .logo-icon i { font-size: 2.8rem; }
        }
    </style>
</head>
<body class="<?= $login_type == 'user' ? 'user-mode' : 'admin-mode' ?>">

<!-- Animated Background -->
<div class="animated-bg">
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
    <div class="floating-bus"><i class="fas fa-bus-simple"></i></div>
    <div class="floating-bus"><i class="fas fa-bus"></i></div>
</div>

<!-- Theme Toggle -->
<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<div class="login-container">
    <!-- Tab Toggle -->
    <div class="login-tabs">
        <button class="tab-btn user-tab <?= $login_type == 'user' ? 'active' : '' ?>" onclick="switchLoginType('user')">
            <i class="fas fa-user"></i> User Login
        </button>
        <button class="tab-btn admin-tab <?= $login_type == 'admin' ? 'active' : '' ?>" onclick="switchLoginType('admin')">
            <i class="fas fa-shield-alt"></i> Admin Login
        </button>
    </div>

    <!-- ENHANCED LOGO SECTION -->
    <div class="logo-section">
        <div class="logo-icon">
            <i class="<?= $login_type == 'user' ? 'fas fa-bus' : 'fas fa-shield-alt' ?>"></i>
        </div>
        <h2><?= $login_type == 'user' ? 'Welcome Back!' : 'Admin Panel' ?></h2>
        <p class="subtitle">
            <?= $login_type == 'user' ? 'Login to continue your journey' : 'Secure Administrator Access' ?>
        </p>
    </div>

    <?php if ($error): ?>
        <div class="error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- User Login Form -->
    <form method="post" id="userForm" style="display: <?= $login_type == 'user' ? 'block' : 'none' ?>;">
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Email Address" required autocomplete="email">
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="userPassword" placeholder="Password" required autocomplete="current-password">
            <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('userPassword', this)"></i>
        </div>
        <button type="submit" name="user_login" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login as User
        </button>
    </form>

    <!-- Admin Login Form -->
    <form method="post" id="adminForm" style="display: <?= $login_type == 'admin' ? 'block' : 'none' ?>;">
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Admin Email" required autocomplete="email" value="admin@greatbus.com">
        </div>
        <div class="input-group">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="adminPassword" placeholder="Password" required autocomplete="current-password" value="admin123">
            <i class="fas fa-eye-slash password-toggle" onclick="togglePassword('adminPassword', this)"></i>
        </div>
        <button type="submit" name="admin_login" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Login as Admin
        </button>
    </form>

    <div class="signup-link" id="signupLink" style="display: <?= $login_type == 'user' ? 'block' : 'none' ?>;">
        Don't have an account? <a href="signup.php">Sign Up <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="back-home">
        <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
    </div>

    <div class="demo-hint">
        <i class="fas fa-info-circle"></i> 
        <?php if ($login_type == 'user'): ?>
            Demo: user@example.com / password123
        <?php else: ?>
            Demo: admin@greatbus.com / admin123
        <?php endif; ?>
    </div>
</div>

<script>
    function switchLoginType(type) {
        window.location.href = 'login.php?type=' + type;
    }

    function togglePassword(inputId, toggleIcon) {
        const passwordInput = document.getElementById(inputId);
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        }
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

    // Add ripple effect to login button
    document.querySelectorAll('.btn-login').forEach(btn => {
        btn.addEventListener('click', function(e) {
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
    });

    console.log("%c🚌 Great Bus | Enhanced Login Page", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Animated Logo | Floating Icon | Rotating Ring", "color: #F7931E; font-size: 12px;");
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