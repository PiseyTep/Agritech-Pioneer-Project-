<?php
// session_start();
// require_once("../connect.php");

// // // Check if user is logged in
// // if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
// //     ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
// //     //header("Location: ../login.php");
// //     exit();
// // }

// // Store token in JavaScript for API calls
// if (!isset($_SESSION['role'])) {
//     $_SESSION['role'] = 'super_admin';
//     $_SESSION['api_token'] = 'dummy_token';
//     $_SESSION['name'] = 'Test Admin';
//     $_SESSION['email'] = 'testadmin@example.com';
// }
// $api_token = $_SESSION['api_token'] ?? '';

// // Check if user is super admin
// $isSuperAdmin = ($_SESSION['role'] === 'super_admin');

// // Default values in case API fails
// $totalAdmins = 0;
// $percentChange = 0;
// $pendingAdmins = 0;
// $totalFarmers = 0;
// $totalMachines = 0;
// $activeRentals = 0;
// $adminName = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';
// $trendClass = 'positive';
// $trendIcon = 'fa-arrow-up';
?>

<!-- <!DOCTYPE html>
<html lang="en">
<head> -->
    <!-- <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriTech Pioneer - Admin Dashboard</title>
    <!-- <link rel="stylesheet" href="../css/dashboard.css"> -->
    <!-- <link rel="stylesheet" href="./css/dashboard.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body> --> -->
<!-- <div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo-container">
            <i class="fas fa-seedling"></i>
            <h2>AgriTech Pioneer</h2>
        </div>
        <ul> -->
            <!-- <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <!-- <li><a href="../rentals.html"><i class="fas fa-exchange-alt"></i> Track Rentals</a></li>
            <li><a href="../products.html"><i class="fas fa-tractor"></i> Manage Products</a></li>
            <li><a href="../post_video.html"><i class="fas fa-video"></i> Post Video</a></li>
            <?php if ($isSuperAdmin): ?>
                <li><a href="../manage_admins.php"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <?php endif; ?> -->
            <!-- <li><a href="../manage_farmers.php"><i class="fas fa-user"></i> Farmer Management</a></li>
            <li><a href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
        <div class="sidebar-footer">
            <p>&copy; 2025 AgriTech Pioneer</p>
        </div>
    </aside> --> -->

    <!-- <main class="dashboard-content">
        <header>
            <div class="header-title">
                <h2><i class="fas fa-chart-line"></i> Dashboard</h2>
            </div>
            <div class="profile">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">0</span>
                </div>
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span id="adminName"><?php echo htmlspecialchars($adminName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div> -->
            </div>
        </header>

        <div id="pendingAdminsAlert" class="alert alert-warning" style="display: none;">
            <p><i class="fas fa-exclamation-triangle"></i> There are <span id="pendingAdminsCount">0</span> admin account(s) pending approval. <a href="../manage_admins.php">Review now</a></p>
        </div>

        <div class="dashboard-overview">
            <section class="stats">
                <div class="card">
                    <div class="card-icon admin-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="card-content">
                        <h3>Admin Account</h3>
                        <span class="card-value" id="totalAdmins"><?php echo $totalAdmins; ?></span>
                        <p class="card-trend" id="adminTrend">
                            <i class="fas fa-arrow-up"></i> 0% from last month
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon farmers-icon"><i class="fas fa-users"></i></div>
                    <div class="card-content">
                        <h3>Total Farmers</h3>
                        <span class="card-value" id="totalFarmers"><?php echo $totalFarmers; ?></span>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon machines-icon"><i class="fas fa-tractor"></i></div>
                    <div class="card-content">
                        <h3>Total Machinery</h3>
                        <span class="card-value" id="totalMachines"><?php echo $totalMachines; ?></span>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon rentals-icon"><i class="fas fa-handshake"></i></div>
                    <div class="card-content">
                        <h3>Active Rentals</h3>
                        <span class="card-value" id="activeRentals"><?php echo $activeRentals; ?></span>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<!-- Store the API token for JavaScript use -->
<script>
    // Store token in localStorage if not already set
    if (!localStorage.getItem('api_token') && '<?php echo $api_token; ?>') {
        localStorage.setItem('api_token', '<?php echo $api_token; ?>');
    }
    
    // Store user information for later access
    sessionStorage.setItem('email', '<?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>');
    sessionStorage.setItem('user_role', '<?php echo htmlspecialchars($_SESSION['role'] ?? ''); ?>');
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Add the API utilities script -->
<script src="../js/api_utils.js?v=2"></script>

<script src="../js/dashboard-charts.js"></script>
<script>
    // Load dashboard data via API
    document.addEventListener('DOMContentLoaded', async function() {
        try {
            // Check authentication
            if (!checkAuth()) return;
            
            // Fetch dashboard statistics
            const stats = await getData('admin/stats');
            if (stats && stats.data) {
                const data = stats.data;
                
                // Update dashboard metrics
                document.getElementById('totalAdmins').textContent = data.totalAdmins || 0;
                document.getElementById('totalFarmers').textContent = data.totalFarmers || 0;
                document.getElementById('totalMachines').textContent = data.totalMachines || 0;
                document.getElementById('activeRentals').textContent = data.activeRentals || 0;
                
                // Update admin trend
                const percentChange = data.adminGrowthPercent || 0;
                const trendElement = document.getElementById('adminTrend');
                trendElement.innerHTML = `
                    <i class="fas ${percentChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down'}"></i> 
                    ${Math.abs(percentChange)}% from last month
                `;
                trendElement.className = `card-trend ${percentChange >= 0 ? 'positive' : 'negative'}`;
                
                // Show pending admins alert for super admins
                if (sessionStorage.getItem('user_role') === 'super_admin' && data.pendingAdmins > 0) {
                    document.getElementById('pendingAdminsCount').textContent = data.pendingAdmins;
                    document.getElementById('pendingAdminsAlert').style.display = 'block';
                }
                
                // Fetch user profile data
                const userData = await getData('user');
                if (userData && userData.user) {
                    document.getElementById('adminName').textContent = userData.user.name || 'Admin';
                }
            }
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    });
    
    // Handle logout
    document.getElementById('logoutLink').addEventListener('click', function(e) {
        e.preventDefault();
        logout();
    });
</script>
</body>
</html>