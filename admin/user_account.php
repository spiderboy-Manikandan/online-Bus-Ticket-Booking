<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?type=user");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get user details
$user_query = $conn->prepare("SELECT * FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user = $user_query->get_result()->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Please enter a valid 10-digit phone number!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address!";
    } else {
        $update = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, email = ? WHERE id = ?");
        $update->bind_param("sssi", $fullname, $phone, $email, $user_id);
        
        if ($update->execute()) {
            $_SESSION['name'] = $fullname;
            $_SESSION['phone'] = $phone;
            $_SESSION['user_email'] = $email;
            $message = "Profile updated successfully!";
            $user['fullname'] = $fullname;
            $user['phone'] = $phone;
            $user['email'] = $email;
        } else {
            $error = "Failed to update profile. Please try again.";
        }
    }
}

// Handle password change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill all password fields!";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters!";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match!";
    } else {
        $verify = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $verify->bind_param("i", $user_id);
        $verify->execute();
        $result = $verify->get_result();
        $user_data = $result->fetch_assoc();
        
        if (password_verify($current_password, $user_data['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_pass->bind_param("si", $hashed_password, $user_id);
            
            if ($update_pass->execute()) {
                $message = "Password changed successfully!";
            } else {
                $error = "Failed to change password.";
            }
        } else {
            $error = "Current password is incorrect!";
        }
    }
}

// Get booking search parameter
$booking_search = isset($_GET['booking_search']) ? mysqli_real_escape_string($conn, $_GET['booking_search']) : '';
$booking_status = isset($_GET['booking_status']) ? mysqli_real_escape_string($conn, $_GET['booking_status']) : '';

// Get user's bookings with search
$bookings_query = "SELECT b.*, bs.bus_name, bs.bus_number, bs.from_city, bs.to_city, 
           bs.departure_time, bs.arrival_time, bs.price_per_seat
    FROM bookings b 
    JOIN buses bs ON b.bus_id = bs.id 
    WHERE b.user_id = $user_id";

if ($booking_search) {
    $bookings_query .= " AND (b.booking_number LIKE '%$booking_search%' OR bs.bus_name LIKE '%$booking_search%' OR bs.from_city LIKE '%$booking_search%' OR bs.to_city LIKE '%$booking_search%')";
}
if ($booking_status) {
    $bookings_query .= " AND b.status = '$booking_status'";
}
$bookings_query .= " ORDER BY b.id DESC";
$bookings = $conn->query($bookings_query);

// Get booking statistics
$total_bookings = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id = $user_id")->fetch_assoc();
$total_spent = $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE user_id = $user_id AND status='confirmed'")->fetch_assoc();
$upcoming_trips = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE user_id = $user_id AND journey_date >= CURDATE() AND status='confirmed'")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Account - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --navbar-bg: rgba(255, 255, 255, 0.95);
            --input-border: #e0e0e0;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
            --btn-primary: linear-gradient(135deg, #FF6B35, #F7931E);
            --sidebar-bg: #f8f9fa;
            --success-bg: #d1fae5;
            --success-color: #065f46;
            --error-bg: #fee2e2;
            --error-color: #dc2626;
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #1a1a2e 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --navbar-bg: rgba(30, 30, 46, 0.95);
            --input-border: #3a3a4a;
            --shadow: 0 10px 30px rgba(0,0,0,0.3);
            --sidebar-bg: #151520;
            --success-bg: rgba(16, 185, 129, 0.2);
            --success-color: #34d399;
            --error-bg: rgba(220, 38, 38, 0.2);
            --error-color: #f87171;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            transition: all 0.3s ease;
            position: relative;
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

        /* Theme Toggle - Top Left */
        .theme-toggle {
            position: fixed;
            top: 20px;
            left: 20px;
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
            flex-wrap: wrap;
        }

        .nav-links a {
            color: var(--text-primary);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background: rgba(255, 107, 53, 0.1);
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

        /* Container */
        .container {
            position: relative;
            z-index: 10;
            max-width: 1300px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Dashboard Layout */
        .dashboard {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        /* Sidebar */
        .sidebar {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            height: fit-content;
            position: sticky;
            top: 2rem;
            transition: all 0.3s ease;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            animation: avatarPulse 2s ease-in-out infinite;
        }

        @keyframes avatarPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 107, 53, 0.4); }
            50% { box-shadow: 0 0 0 10px rgba(255, 107, 53, 0); }
        }

        .profile-avatar i {
            font-size: 3rem;
            color: white;
        }

        .profile-header h3 {
            color: var(--text-primary);
            margin-bottom: 0.3rem;
        }

        .profile-header p {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.8rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background: linear-gradient(135deg, rgba(255, 107, 53, 0.1), rgba(247, 147, 30, 0.1));
            color: #FF6B35;
        }

        .sidebar-menu a i {
            width: 20px;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card-small {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .stat-card-small:hover {
            transform: translateY(-5px);
        }

        .stat-card-small i {
            font-size: 2rem;
            color: #FF6B35;
            margin-bottom: 0.5rem;
        }

        .stat-card-small .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .stat-card-small .stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        /* Main Content */
        .main-content {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--input-border);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Search Filters */
        .search-filters {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            border: 1px solid var(--input-border);
        }

        .search-box {
            flex: 2;
            display: flex;
            gap: 0.5rem;
        }

        .search-box input {
            flex: 1;
            padding: 0.7rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text-primary);
        }

        .search-box input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .search-box button, .filter-btn {
            padding: 0.7rem 1.2rem;
            background: #FF6B35;
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-box button:hover, .filter-btn:hover {
            background: #F7931E;
            transform: translateY(-2px);
        }

        .status-filter select {
            padding: 0.7rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text-primary);
            cursor: pointer;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 2px solid var(--input-border);
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success-color);
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error-color);
            border-left: 4px solid #ef4444;
        }

        /* Bookings Table */
        .bookings-table {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--input-border);
            color: var(--text-primary);
        }

        th {
            font-weight: 600;
            background: rgba(0, 0, 0, 0.03);
        }

        body.dark th {
            background: rgba(255, 255, 255, 0.05);
        }

        .badge {
            background: #10b981;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
        }

        .badge-cancelled {
            background: #ef4444;
        }

        .badge-pending {
            background: #f59e0b;
        }

        .view-btn {
            background: linear-gradient(135deg, #FF6B35, #F7931E);
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Back to Search Button */
        .back-search-btn {
            background: rgba(255, 107, 53, 0.1);
            color: #FF6B35;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .back-search-btn:hover {
            background: #FF6B35;
            color: white;
            transform: translateX(-5px);
        }

        @media (max-width: 992px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: static;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .navbar { flex-direction: column; text-align: center; }
            .nav-links { justify-content: center; }
            .stats-cards { grid-template-columns: repeat(2, 1fr); }
            th, td { padding: 0.5rem; font-size: 0.8rem; }
            .search-filters { flex-direction: column; }
            .search-box { width: 100%; }
            .theme-toggle { top: 15px; left: 15px; padding: 5px 12px; font-size: 0.7rem; }
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

<!-- Theme Toggle - TOP LEFT -->
<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<!-- Navbar -->
<nav class="navbar">
    <div class="logo">
        <i class="fas fa-bus"></i> Great Bus
    </div>
    <div class="nav-links">
        <a href="search_bus.php" class="back-search-btn"><i class="fas fa-search"></i> Search Buses</a>
        <a href="user_account.php" style="background: rgba(255,107,53,0.1);"><i class="fas fa-user-circle"></i> My Account</a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3><?= htmlspecialchars($user['fullname']) ?></h3>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
                <p><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone'] ?? 'Not added') ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" class="menu-link active" data-section="profile"><i class="fas fa-user"></i> Profile Settings</a></li>
                <li><a href="#" class="menu-link" data-section="password"><i class="fas fa-lock"></i> Change Password</a></li>
                <li><a href="#" class="menu-link" data-section="bookings"><i class="fas fa-ticket-alt"></i> My Bookings</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <?php if ($message): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card-small">
                    <i class="fas fa-ticket-alt"></i>
                    <div class="stat-number"><?= $total_bookings['total'] ?? 0 ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card-small">
                    <i class="fas fa-rupee-sign"></i>
                    <div class="stat-number">₹<?= number_format($total_spent['total'] ?? 0, 0) ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
                <div class="stat-card-small">
                    <i class="fas fa-calendar-check"></i>
                    <div class="stat-number"><?= $upcoming_trips['total'] ?? 0 ?></div>
                    <div class="stat-label">Upcoming Trips</div>
                </div>
                <div class="stat-card-small">
                    <i class="fas fa-star"></i>
                    <div class="stat-number"><?= date('Y') ?></div>
                    <div class="stat-label">Member Since</div>
                </div>
            </div>

            <!-- Profile Section -->
            <div id="profile-section" class="section active">
                <h3 class="section-title"><i class="fas fa-user-edit"></i> Profile Settings</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" pattern="[0-9]{10}" placeholder="Enter 10-digit mobile number" maxlength="10">
                    </div>
                    <button type="submit" name="update_profile" class="btn-primary"><i class="fas fa-save"></i> Update Profile</button>
                </form>
            </div>

            <!-- Password Section -->
            <div id="password-section" class="section">
                <h3 class="section-title"><i class="fas fa-key"></i> Change Password</h3>
                <form method="post">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" id="new_password" required>
                        <small style="color: var(--text-secondary);">Minimum 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required>
                    </div>
                    <button type="submit" name="change_password" class="btn-primary"><i class="fas fa-sync-alt"></i> Change Password</button>
                </form>
            </div>

            <!-- Bookings Section with Search -->
            <div id="bookings-section" class="section">
                <h3 class="section-title"><i class="fas fa-history"></i> My Booking History</h3>
                
                <!-- Search Filters -->
                <div class="search-filters">
                    <form method="GET" class="search-box">
                        <input type="text" name="booking_search" placeholder="Search by Booking ID, Bus Name, or Route..." value="<?= htmlspecialchars($booking_search) ?>">
                        <button type="submit"><i class="fas fa-search"></i> Search</button>
                        <?php if ($booking_search || $booking_status): ?>
                            <a href="user_account.php?section=bookings" style="padding: 0.7rem 1.2rem; background: #6c757d; color: white; border-radius: 12px; text-decoration: none;">Clear</a>
                        <?php endif; ?>
                    </form>
                    <div class="status-filter">
                        <form method="GET" style="display: flex; gap: 0.5rem;">
                            <?php if ($booking_search): ?>
                                <input type="hidden" name="booking_search" value="<?= htmlspecialchars($booking_search) ?>">
                            <?php endif; ?>
                            <input type="hidden" name="section" value="bookings">
                            <select name="booking_status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="confirmed" <?= $booking_status == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                <option value="cancelled" <?= $booking_status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="pending" <?= $booking_status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="bookings-table">
                    <?php if ($bookings->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Bus</th>
                                    <th>Route</th>
                                    <th>Date</th>
                                    <th>Seats</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($booking['booking_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($booking['bus_name']) ?><br><small><?= htmlspecialchars($booking['bus_number']) ?></small></td>
                                    <td><?= htmlspecialchars($booking['from_city']) ?> → <?= htmlspecialchars($booking['to_city']) ?></td>
                                    <td><?= date('d M Y', strtotime($booking['journey_date'])) ?></td>
                                    <td><?= $booking['seats'] ?></td>
                                    <td><strong>₹<?= number_format($booking['total_amount'], 0) ?></strong></td>
                                    <td>
                                        <span class="badge <?= $booking['status'] == 'cancelled' ? 'badge-cancelled' : ($booking['status'] == 'pending' ? 'badge-pending' : '') ?>">
                                            <?= ucfirst($booking['status']) ?>
                                        </span>
                                    </td>
                                    <td><button class="view-btn" onclick="viewBooking(<?= $booking['id'] ?>)"><i class="fas fa-eye"></i> View</button></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h3>No Bookings Found</h3>
                            <p>You haven't made any bookings yet. Start your journey with Great Bus!</p>
                            <a href="search_bus.php" class="btn-primary" style="margin-top: 1rem; display: inline-block;"><i class="fas fa-search"></i> Search Buses</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Check URL parameter for section
    const urlParams = new URLSearchParams(window.location.search);
    const sectionParam = urlParams.get('section');
    
    if (sectionParam === 'bookings') {
        // Activate bookings section
        document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
        document.querySelector('.menu-link[data-section="bookings"]').classList.add('active');
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.getElementById('bookings-section').classList.add('active');
    }

    // Section navigation
    const menuLinks = document.querySelectorAll('.menu-link');
    const sections = {
        profile: document.getElementById('profile-section'),
        password: document.getElementById('password-section'),
        bookings: document.getElementById('bookings-section')
    };

    menuLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const section = link.dataset.section;
            
            // Update URL without reload
            const url = new URL(window.location.href);
            url.searchParams.set('section', section);
            window.history.pushState({}, '', url);
            
            // Update active class on menu
            menuLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
            
            // Show selected section
            Object.values(sections).forEach(s => s.classList.remove('active'));
            sections[section].classList.add('active');
        });
    });

    function viewBooking(bookingId) {
        window.location.href = `booking_details.php?booking_id=${bookingId}`;
    }

    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    
    function validatePassword() {
        if (newPassword && confirmPassword) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
    }
    
    if (newPassword && confirmPassword) {
        newPassword.addEventListener('change', validatePassword);
        confirmPassword.addEventListener('keyup', validatePassword);
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

    // Auto hide alerts after 3 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 3000);
        });
    });

    console.log("%c🚌 Great Bus | User Account Management", "color: #FF6B35; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Profile Settings | Change Password | Booking History with Search", "color: #F7931E; font-size: 12px;");
    console.log("%c✓ Dark/Light Mode Button Moved to Top Left", "color: #FFD700; font-size: 12px;");
</script>

</body>
</html>