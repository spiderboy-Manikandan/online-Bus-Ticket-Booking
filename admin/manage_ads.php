<?php
session_start();
include "../db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: ../login.php?type=admin");
    exit();
}

// Handle add ad
if (isset($_POST['add_ad'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $image_url = mysqli_real_escape_string($conn, $_POST['image_url']);
    $link_url = mysqli_real_escape_string($conn, $_POST['link_url']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $insert = $conn->prepare("INSERT INTO ads (title, description, image_url, link_url, position, status) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssss", $title, $description, $image_url, $link_url, $position, $status);
    
    if ($insert->execute()) {
        $_SESSION['message'] = "Ad added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to add ad!";
        $_SESSION['message_type'] = "error";
    }
    header("Location: manage_ads.php");
    exit();
}

// Get all ads
$ads = $conn->query("SELECT * FROM ads ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Ads - Great Bus Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 30px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { color: #2a5298; margin-bottom: 1rem; }
        .btn-add { background: #10b981; color: white; padding: 0.5rem 1rem; border-radius: 25px; text-decoration: none; display: inline-block; margin-bottom: 1rem; border: none; cursor: pointer; }
        .btn-add:hover { opacity: 0.9; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn-edit { background: #667eea; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; text-decoration: none; font-size: 0.8rem; }
        .btn-delete { background: #ff4757; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; text-decoration: none; font-size: 0.8rem; }
        .badge-active { background: #10b981; color: white; padding: 0.2rem 0.5rem; border-radius: 20px; font-size: 0.7rem; }
        .badge-inactive { background: #ff4757; color: white; padding: 0.2rem 0.5rem; border-radius: 20px; font-size: 0.7rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: 0.3rem; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 8px; }
        .back-link { display: inline-block; margin-top: 1rem; color: #2a5298; text-decoration: none; }
        @media (max-width: 768px) { .container { padding: 1rem; } table { font-size: 0.8rem; } th, td { padding: 0.5rem; } }
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-ad"></i> Manage Advertisements</h1>
    
    <form method="post" style="background: #f8f9fa; padding: 1rem; border-radius: 15px; margin-bottom: 1rem;">
        <h3>Add New Ad</h3>
        <div class="form-group"><label>Title</label><input type="text" name="title" required></div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        <div class="form-group"><label>Image URL (optional)</label><input type="text" name="image_url" placeholder="https://..."></div>
        <div class="form-group"><label>Link URL</label><input type="text" name="link_url" placeholder="https://..."></div>
        <div class="form-group"><label>Position</label><select name="position"><option value="top">Top Banner</option><option value="sidebar">Sidebar</option><option value="bottom">Bottom Banner</option></select></div>
        <div class="form-group"><label>Status</label><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        <button type="submit" name="add_ad" class="btn-add"><i class="fas fa-plus"></i> Add Ad</button>
    </form>
    
    <table>
        <thead><tr><th>ID</th><th>Title</th><th>Position</th><th>Status</th><th>Clicks</th><th>Actions</th></tr></thead>
        <tbody>
            <?php while($ad = $ads->fetch_assoc()): ?>
            <tr><td>#<?= $ad['id'] ?></td><td><?= htmlspecialchars($ad['title']) ?></td><td><?= ucfirst($ad['position']) ?></td><td><span class="badge-<?= $ad['status'] ?>"><?= ucfirst($ad['status']) ?></span></td><td><?= $ad['clicks'] ?></td><td><a href="edit_ad.php?id=<?= $ad['id'] ?>" class="btn-edit">Edit</a> <a href="delete_ad.php?id=<?= $ad['id'] ?>" class="btn-delete" onclick="return confirm('Delete?')">Delete</a></td></tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>