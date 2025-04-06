
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Video</title>
    <link rel="stylesheet" href="/LoginFarmer/Laravel-capstone/public/admin-dashboard/css/dashboard.css">
    <link rel="stylesheet" href="/LoginFarmer/Laravel-capstone/public/admin-dashboard/css/manage_video.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-container">
                <img src="assets/logo-02.jpg" alt="AgriTech Pioneer Logo" class="logo-image">
                <h2>AgriTech Pioneer</h2>
            </div>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="rentals.php"><i class="fas fa-exchange-alt"></i> Track Rentals</a></li>
                <li><a href="tractors.php" class="active"><i class="fas fa-tractor"></i> Manage Tractors</a></li>
               
                <li><a href="#"><i class="fas fa-video"></i> Post Video</a></li>
                <?php if ($isSuperAdmin): ?>
                    <li><a href="#"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
                <?php endif; ?>
                <li><a href="manage_farmers.php"><i class="fas fa-user"></i> Farmer Management</a></li>
                <li><a href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
            <div class="sidebar-footer">
                <p>&copy; 2025 AgriTech Pioneer</p>
            </div>
        </aside>

        <main class="dashboard-content">
            <header>
                <h2>Post Videos</h2>
            </header>

            <section class="form-section">
                <h3>Add New Video</h3>
                <form id="videoForm">
                    <label for="title">Title:</label>
                    <input type="text" id="title" required>

                    <label for="video_url">Video URL:</label>
                    <input type="text" id="video_url" required>

                    <button type="submit">Post Video</button>
                </form>
            </section>

            <section class="table-section">
                <h3>Manage Videos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Video URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="videoTable">
                        <!-- Videos will be inserted here -->
                    </tbody>
                </table>
            </section>
        </main>
    </div>

<!-- Role switcher for testing -->
<div class="role-switcher">
    <p>Test different roles:</p>
    <a href="?role=super_admin" class="btn">Super Admin</a>
    <a href="?role=admin" class="btn">Admin</a>
</div>
<script src="/js/config.js"></script>
<script src="js/post_video.js"></script>

    <script src="/LoginFarmer/Laravel-capstone/public/admin-dashboard/js/script.js"></script>
</body>
</html>
