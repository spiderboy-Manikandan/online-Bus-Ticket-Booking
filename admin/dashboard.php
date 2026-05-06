<?php
session_start();
include "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php?type=admin");
    exit();
}

// Get statistics
$users = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc();
$buses = $conn->query("SELECT COUNT(*) as total FROM buses")->fetch_assoc();
$bookings = $conn->query("SELECT COUNT(*) as total FROM bookings")->fetch_assoc();

// Get ad statistics
$ads_total = $conn->query("SELECT COUNT(*) as total FROM ads")->fetch_assoc();
$ads_active = $conn->query("SELECT COUNT(*) as total FROM ads WHERE status='active'")->fetch_assoc();
$ads_total_clicks = $conn->query("SELECT SUM(clicks) as total FROM ads")->fetch_assoc();

// Get recent bookings
$recent_bookings = $conn->query("SELECT b.*, u.fullname, bs.bus_name FROM bookings b JOIN users u ON b.user_id = u.id JOIN buses bs ON b.bus_id = bs.id ORDER BY b.id DESC LIMIT 10");

// Get recent ads
$recent_ads = $conn->query("SELECT * FROM ads ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin Dashboard - Great Bus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* CSS Variables for Dark Blue Theme */
        :root {
            --bg-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --card-bg: #ffffff;
            --text-primary: #333;
            --text-secondary: #666;
            --border-color: #e0e0e0;
            --stat-icon-bg: linear-gradient(135deg, #1e3c72, #2a5298);
            --admin-btn: #2a5298;
            --admin-btn-hover: #1e3c72;
            --shadow: 0 2px 10px rgba(0,0,0,0.1);
            --ad-card-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        body.dark {
            --bg-gradient: linear-gradient(135deg, #0a0a1a 0%, #0f0f1a 100%);
            --card-bg: #1e1e2e;
            --text-primary: #f0f0f0;
            --text-secondary: #a0a0a0;
            --border-color: #3a3a4a;
            --stat-icon-bg: linear-gradient(135deg, #2a5298, #1e3c72);
            --admin-btn: #4a6fa5;
            --admin-btn-hover: #2a5298;
            --shadow: 0 2px 10px rgba(0,0,0,0.3);
            --ad-card-bg: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            transition: background 0.3s ease;
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
            color: rgba(255, 255, 255, 0.05);
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

        /* Theme Toggle - Right Side */
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

        /* Enhanced Logo Section */
        .logo-section {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        body.dark .logo-section {
            background: rgba(30, 30, 46, 0.95);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        .logo i {
            color: #2a5298;
            margin-right: 10px;
        }

        body.dark .logo i {
            color: #4a6fa5;
        }

        .logo-icon-small {
            width: 40px;
            height: 40px;
            display: inline-block;
            vertical-align: middle;
        }

        .logout-btn {
            background: #ff4757;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #ff6b81;
            transform: translateY(-2px);
        }

        .container {
            position: relative;
            z-index: 10;
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        h2 {
            color: white;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            cursor: pointer;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: var(--stat-icon-bg);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: white;
        }

        .stat-info h3 {
            font-size: 0.85rem;
            color: var(--text-secondary);
            margin-bottom: 0.3rem;
        }

        .stat-info .number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Admin Actions */
        .admin-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .admin-btn {
            background: var(--card-bg);
            padding: 0.8rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            color: var(--admin-btn);
            font-weight: 600;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .admin-btn:hover {
            background: var(--admin-btn);
            color: white;
            transform: translateY(-2px);
        }

        /* Ad Management Section */
        .ad-section {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .ad-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .ad-header h3 {
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .add-ad-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .add-ad-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        .ad-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .ad-stat-card {
            background: rgba(255,107,53,0.1);
            border-radius: 20px;
            padding: 1rem;
            text-align: center;
        }

        .ad-stat-card i {
            font-size: 2rem;
            color: #FF6B35;
            margin-bottom: 0.5rem;
        }

        .ad-stat-card .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .ad-stat-card .stat-label {
            font-size: 0.7rem;
            color: var(--text-secondary);
        }

        .ad-table-container {
            overflow-x: auto;
        }

        .ad-table {
            width: 100%;
            border-collapse: collapse;
        }

        .ad-table th, .ad-table td {
            padding: 0.8rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .ad-table th {
            background: rgba(0, 0, 0, 0.03);
            font-weight: 600;
        }

        body.dark .ad-table th {
            background: rgba(255, 255, 255, 0.05);
        }

        .status-badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        .ad-actions {
            display: flex;
            gap: 0.5rem;
        }

        .ad-edit, .ad-delete, .ad-toggle {
            padding: 0.3rem 0.6rem;
            border-radius: 15px;
            text-decoration: none;
            font-size: 0.7rem;
            transition: all 0.3s ease;
        }

        .ad-edit {
            background: rgba(42, 82, 152, 0.1);
            color: #2a5298;
        }

        .ad-edit:hover {
            background: #2a5298;
            color: white;
        }

        .ad-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .ad-delete:hover {
            background: #ef4444;
            color: white;
        }

        .ad-toggle {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .ad-toggle:hover {
            background: #f59e0b;
            color: white;
        }

        /* Section Title */
        .section-title {
            color: white;
            margin: 1rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 1rem;
            overflow-x: auto;
            box-shadow: var(--shadow);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        th {
            background: rgba(0, 0, 0, 0.03);
            font-weight: 600;
            color: var(--text-primary);
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
        }

        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            .stats-grid { gap: 1rem; }
            .admin-actions { justify-content: center; }
            .logo-section { flex-direction: column; gap: 0.5rem; text-align: center; }
            .ad-header { flex-direction: column; text-align: center; }
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

<!-- Theme Toggle - Right Side -->
<button class="theme-toggle" id="themeToggle">
    <i class="fas fa-moon"></i> <span>Dark Mode</span>
</button>

<!-- Enhanced Navbar with Logo -->
<div class="logo-section">
    <div class="logo">
        <i class="fas fa-bus"></i> Great Bus Admin
    </div>
    <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <h2><i class="fas fa-chart-line"></i> Dashboard Overview</h2>
    
    <div class="stats-grid">
        <div class="stat-card" onclick="window.location.href='view_users.php'">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <h3>Total Users</h3>
                <div class="number"><?= $users['total'] ?></div>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='manage_buses.php'">
            <div class="stat-icon"><i class="fas fa-bus"></i></div>
            <div class="stat-info">
                <h3>Total Buses</h3>
                <div class="number"><?= $buses['total'] ?></div>
            </div>
        </div>
        <div class="stat-card" onclick="window.location.href='view_bookings.php'">
            <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-info">
                <h3>Total Bookings</h3>
                <div class="number"><?= $bookings['total'] ?></div>
            </div>
        </div>
    </div>

    <div class="admin-actions">
        <a href="add_bus.php" class="admin-btn"><i class="fas fa-plus-circle"></i> Add Bus</a>
        <a href="manage_buses.php" class="admin-btn"><i class="fas fa-list"></i> Manage Buses</a>
        <a href="view_users.php" class="admin-btn"><i class="fas fa-users"></i> View Users</a>
        <a href="view_bookings.php" class="admin-btn"><i class="fas fa-ticket-alt"></i> View Bookings</a>
    </div>

    <!-- Ad Management Section -->
    <div class="ad-section">
        <div class="ad-header">
            <h3><i class="fas fa-ad"></i> Advertisement Management</h3>
            <a href="manage_ads.php" class="add-ad-btn"><i class="fas fa-plus"></i> Manage Ads</a>
        </div>

        <div class="ad-stats">
            <div class="ad-stat-card">
                <i class="fas fa-bullhorn"></i>
                <div class="stat-number"><?= $ads_total['total'] ?? 0 ?></div>
                <div class="stat-label">Total Ads</div>
            </div>
            <div class="ad-stat-card">
                <i class="fas fa-check-circle"></i>
                <div class="stat-number"><?= $ads_active['total'] ?? 0 ?></div>
                <div class="stat-label">Active Ads</div>
            </div>
            <div class="ad-stat-card">
                <i class="fas fa-mouse-pointer"></i>
                <div class="stat-number"><?= $ads_total_clicks['total'] ?? 0 ?></div>
                <div class="stat-label">Total Clicks</div>
            </div>
            <div class="ad-stat-card">
                <i class="fas fa-chart-line"></i>
                <div class="stat-number"><?= $ads_total['total'] > 0 ? round(($ads_total_clicks['total'] ?? 0) / $ads_total['total']) : 0 ?></div>
                <div class="stat-label">Avg CTR</div>
            </div>
        </div>

        <div class="ad-table-container">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Position</th>
                        <th>Clicks</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_ads && $recent_ads->num_rows > 0): ?>
                        <?php while ($ad = $recent_ads->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= $ad['id'] ?></td>
                                <td><?= htmlspecialchars(substr($ad['title'], 0, 30)) ?></td>
                                <td><span class="status-badge" style="background: rgba(255,107,53,0.2); color:#FF6B35;"><?= ucfirst($ad['position']) ?></span></td>
                                <td><?= $ad['clicks'] ?? 0 ?></td>
                                <td><span class="status-badge status-<?= $ad['status'] ?>"><?= ucfirst($ad['status']) ?></span></td>
                                <td class="ad-actions">
                                    <a href="edit_ad.php?id=<?= $ad['id'] ?>" class="ad-edit"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="toggle_ad.php?id=<?= $ad['id'] ?>" class="ad-toggle" onclick="return confirm('Toggle ad status?')"><i class="fas fa-power-off"></i> Toggle</a>
                                    <a href="delete_ad.php?id=<?= $ad['id'] ?>" class="ad-delete" onclick="return confirm('Delete this ad?')"><i class="fas fa-trash"></i> Del</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem;">
                                <i class="fas fa-ad" style="font-size: 2rem; opacity: 0.5;"></i>
                                <p>No ads found. <a href="manage_ads.php" style="color: #FF6B35;">Create your first ad</a></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <h3 class="section-title"><i class="fas fa-clock"></i> Recent Bookings</h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Bus</th>
                    <th>Seats</th>
                    <th>Journey Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $recent_bookings->fetch_assoc()): ?>
                <tr>
                    <td>#<?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['fullname']) ?></td>
                    <td><?= htmlspecialchars($row['bus_name']) ?></td>
                    <td><?= $row['seats'] ?></td>
                    <td><?= date('d M Y', strtotime($row['journey_date'])) ?></td>
                    <td>₹<?= number_format($row['total_amount'], 0) ?></td>
                    <td><span class="badge"><i class="fas fa-check-circle"></i> Confirmed</span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
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

    console.log("%c🚌 Great Bus | Admin Dashboard with Ad Management", "color: #2a5298; font-size: 14px; font-weight: bold;");
    console.log("%c✓ Ad Statistics | Recent Ads | Quick Actions", "color: #F7931E; font-size: 12px;");
</script>

</body>
</html>