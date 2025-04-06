<?php
session_start();

// // Check real authentication
// if (!isset($_SESSION['api_token']) || !isset($_SESSION['role']) || 
//     ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
//     header("Location: admin_login.php");
//     exit();
// }

// // User authenticated details from real login
// $isSuperAdmin = ($_SESSION['role'] === 'super_admin');
// $adminName = $_SESSION['name'];
// $adminEmail = $_SESSION['email'];

// // Optionally, fetch real data from Laravel API for dashboard stats
// require_once 'api_helper.php';

// // Fetching actual dashboard data from Laravel API
// $dashboardStats = callApi('GET', 'stats');

// $totalAdmins = $dashboardStats['total_admins'] ?? 0;
// $totalFarmers = $dashboardStats['total_farmers'] ?? 0;
// $totalMachines = $dashboardStats['total_machines'] ?? 0;
// $activeRentals = $dashboardStats['active_rentals'] ?? 0;
// $pendingAdmins = $dashboardStats['pending_admins'] ?? 0;
// $percentChange = $dashboardStats['admin_growth_percent'] ?? 0;

// $trendClass = ($percentChange >= 0) ? 'positive' : 'negative';
// $trendIcon = ($percentChange >= 0) ? 'fa-arrow-up' : 'fa-arrow-down';
session_start();

// MOCK AUTHENTICATION - REMOVE IN PRODUCTION
// This creates mock session data so you can see the dashboard
// In a real system, this would come from your login page
if (!isset($_GET['role']) || ($_GET['role'] != 'admin' && $_GET['role'] != 'super_admin')) {
    // Default to super_admin if no role specified
    $_GET['role'] = 'super_admin';
}

// Set up mock session data based on the role
$_SESSION['user_id'] = 1;
$_SESSION['role'] = $_GET['role'];
$_SESSION['name'] = ($_GET['role'] === 'super_admin') ? 'Super Admin User' : 'Admin User';
$_SESSION['email'] = ($_GET['role'] === 'super_admin') ? 'superadmin@example.com' : 'admin@example.com';
$_SESSION['api_token'] = 'mock_token_' . md5(time());

// Try to include connect.php if it exists, but don't break if it doesn't
if (file_exists("../connect.php")) {
    @include_once("../connect.php");
}

// Check if user is super admin
$isSuperAdmin = ($_SESSION['role'] === 'super_admin');

// Mock data for dashboard elements
$mockData = [
    'totalAdmins' => 5,
    'totalFarmers' => 48,
    'totalMachines' => 23,
    'activeRentals' => 12,
    'pendingAdmins' => $isSuperAdmin ? 4 : 0,
    'adminGrowthPercent' => 15
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriTech Pioneer - Admin Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
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
            <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="#"><i class="fas fa-exchange-alt"></i> Track Rentals</a></li>
            <li><a href="#"><i class="fas fa-tractor"></i> Manage Products</a></li>
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
            <div class="header-title">
                <h2><i class="fas fa-chart-line"></i> Dashboard</h2>
            </div>
            <div class="profile">
                <div class="notification">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </div>
                <div class="admin-profile">
                    <div class="admin-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span id="adminName"><?php echo htmlspecialchars($adminName); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </header>

        <?php if ($isSuperAdmin && $pendingAdmins > 0): ?>
            <div id="pendingAdminsAlert" class="alert alert-warning">
                <p><i class="fas fa-exclamation-triangle"></i> There are <span id="pendingAdminsCount"><?php echo $pendingAdmins; ?></span> admin account(s) pending approval. <a href="#">Review now</a></p>
            </div>
        <?php endif; ?>

        <div class="dashboard-overview">
            <section class="stats">
                <div class="card">
                    <div class="card-icon admin-icon"><i class="fas fa-user-shield"></i></div>
                    <div class="card-content">
                        <h3>Admin Accounts</h3>
                        <span class="card-value" id="totalAdmins"><?php echo $totalAdmins; ?></span>
                        <p class="card-trend <?php echo $trendClass; ?>" id="adminTrend">
                            <i class="fas <?php echo $trendIcon; ?>"></i> <?php echo abs($percentChange); ?>% from last month
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
            
            <!-- Charts Section -->
            <div class="dashboard-grid">
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Rental Performance</h3>
                        <div class="chart-controls">
                            <select id="chartType">
                                <option value="weekly">Weekly</option>
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <button class="btn" id="refreshChart"><i class="fas fa-sync-alt"></i> Refresh</button>
                        </div>
                    </div>
                    <div class="chart" id="rentalChart"></div>
                </div>
                
                <div class="recent-activities">
                    <h3>Recent Activities</h3>
                    <ul id="activities-list">
                        <li class="activity-item">
                            <div class="activity-icon rental">
                                <i class="fas fa-exchange-alt"></i>
                            </div>
                            <div class="activity-details">
                                <p>New rental request from <strong>John Smith</strong></p>
                                <span class="activity-time">Just now</span>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon user">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div class="activity-details">
                                <p>New farmer registered: <strong>Mary Johnson</strong></p>
                                <span class="activity-time">2 hours ago</span>
                            </div>
                        </li>
                        <li class="activity-item">
                            <div class="activity-icon product">
                                <i class="fas fa-tractor"></i>
                            </div>
                            <div class="activity-details">
                                <p>New machinery added: <strong>Harvester X200</strong></p>
                                <span class="activity-time">Yesterday</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Upcoming Rentals Section -->
            <div class="upcoming-rentals">
                <div class="section-header">
                    <h3>Upcoming Rentals</h3>
                    <a href="#" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Farmer</th>
                                <th>Machinery</th>
                                <th>Rental Date</th>
                                <th>Return Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="upcomingRentalsTable">
                            <tr>
                                <td>John Smith</td>
                                <td>Tractor T100</td>
                                <td>Apr 10, 2025</td>
                                <td>Apr 15, 2025</td>
                                <td><span class="status confirmed">Confirmed</span></td>
                                <td><button class="action-btn">View Details</button></td>
                            </tr>
                            <tr>
                                <td>Mary Johnson</td>
                                <td>Harvester H200</td>
                                <td>Apr 12, 2025</td>
                                <td>Apr 14, 2025</td>
                                <td><span class="status pending">Pending</span></td>
                                <td><button class="action-btn">View Details</button></td>
                            </tr>
                            <tr>
                                <td>Robert Brown</td>
                                <td>Sprayer S300</td>
                                <td>Apr 15, 2025</td>
                                <td>Apr 20, 2025</td>
                                <td><span class="status confirmed">Confirmed</span></td>
                                <td><button class="action-btn">View Details</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Role switcher for testing -->
<div class="role-switcher">
    <p>Test different roles:</p>
    <a href="?role=super_admin" class="btn">Super Admin</a>
    <a href="?role=admin" class="btn">Admin</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="js/dashboard.js"></script>
</body>
</html>