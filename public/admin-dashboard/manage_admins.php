<?php
session_start();
require_once 'connect.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin')) {
    header("Location: login.php");
    exit();
}

// For demo/testing purposes - you can remove this in production
if (isset($_GET['role']) && ($_GET['role'] === 'admin' || $_GET['role'] === 'super_admin')) {
    $_SESSION['role'] = $_GET['role'];
}

$isSuperAdmin = ($_SESSION['role'] === 'super_admin');
$adminName = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';

// Fetch farmer statistics
try {
    // Total farmers
    $totalFarmersStmt = $pdo->query("SELECT COUNT(*) as total FROM farmers");
    $totalFarmers = $totalFarmersStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Active farmers
    $activeFarmersStmt = $pdo->query("SELECT COUNT(*) as active FROM farmers WHERE status = 'active'");
    $activeFarmers = $activeFarmersStmt->fetch(PDO::FETCH_ASSOC)['active'];

    // Pending farmers
    $pendingFarmersStmt = $pdo->query("SELECT COUNT(*) as pending FROM farmers WHERE status = 'pending'");
    $pendingFarmers = $pendingFarmersStmt->fetch(PDO::FETCH_ASSOC)['pending'];
} catch (PDOException $e) {
    // Log error or handle appropriately
    $totalFarmers = 0;
    $activeFarmers = 0;
    $pendingFarmers = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Farmers - AgriTech Pioneer</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/manage_farmers.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Add SweetAlert for better notifications -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo-container">
            <div class="logo">
                <img src="assets/logo-02.jpg" alt="AgriTech Pioneer Logo" class="logo-image">
            </div>
            <h2>AgriTech Pioneer</h2>
        </div>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> <span>Dashboard</span></a></li>
            <li><a href="#"><i class="fas fa-exchange-alt"></i> <span>Track Rentals</span></a></li>
            <li><a href="#"><i class="fas fa-tractor"></i> <span>Manage Products</span></a></li>
            <li><a href="#"><i class="fas fa-video"></i> <span>Post Video</span></a></li>
            <?php if ($isSuperAdmin): ?>
                <li><a href="manage_admins.php"><i class="fas fa-user-shield"></i> <span>Manage Admins</span></a></li>
            <?php endif; ?>
            <li><a href="manage_farmers.php" class="active"><i class="fas fa-user"></i> <span>Farmer Management</span></a></li>
            <li><a href="#" id="logoutLink"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
        </ul>
        <div class="sidebar-footer">
            <p>&copy; 2025 AgriTech Pioneer</p>
        </div>
    </aside>

    <main class="dashboard-content">
        <header>
            <div class="header-title">
                <h2><i class="fas fa-users"></i> Farmer Management</h2>
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
                </div>
            </div>
        </header>

        <!-- Farmer Statistics Cards -->
        <div class="farmer-stats row">
            <div class="col-md-4">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-primary">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Total Farmers</p>
                                    <h4 class="card-title" id="totalFarmersCount"><?php echo $totalFarmers; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-success">
                                    <i class="fas fa-user-check"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Active Farmers</p>
                                    <h4 class="card-title" id="activeFarmersCount"><?php echo $activeFarmers; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-warning">
                                    <i class="fas fa-user-clock"></i>
                                </div>
                            </div>
                            <div class="col-7 col-stats">
                                <div class="numbers">
                                    <p class="card-category">Pending Farmers</p>
                                    <h4 class="card-title" id="pendingFarmersCount"><?php echo $pendingFarmers; ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="farmers-container">
            <div class="farmers-header">
                <form method="GET" class="search-form" id="searchForm">
                    <div class="input-group">
                        <input type="text" name="search" id="searchInput" 
                               placeholder="Search farmers..." 
                               class="form-control"
                               value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <div class="input-group-append">
                            <select name="status" id="statusFilter" class="form-control">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="suspended">Suspended</option>
                            </select>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
                
                <button class="btn btn-success add-farmer-btn" id="addFarmerBtn">
                    <i class="fas fa-plus"></i> Add New Farmer
                </button>
            </div>

            <div class="farmers-table-container">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Location</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody id="farmersTableBody">
                        <!-- Farmers will be dynamically loaded here -->
                        <tr class="loading-row">
                            <td colspan="8" class="text-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="pagination justify-content-center" id="farmersPagination">
                <button class="btn btn-outline-primary mr-2" id="prevPage" disabled>
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <span class="align-self-center pagination-info">
                    Page <span id="currentPage">1</span> of <span id="totalPages">1</span>
                </span>
                <button class="btn btn-outline-primary ml-2" id="nextPage" disabled>
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </main>
</div>

<!-- Add/Edit Farmer Modal -->
<div class="modal fade" id="farmerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Add New Farmer</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="farmerForm">
                <div class="modal-body">
                    <input type="hidden" id="farmerId">
                    
                    <div class="form-group">
                        <label for="farmerName">Full Name*</label>
                        <input type="text" class="form-control" id="farmerName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="farmerEmail">Email Address*</label>
                        <input type="email" class="form-control" id="farmerEmail" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="farmerPhone">Phone Number*</label>
                        <input type="tel" class="form-control" id="farmerPhone" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="farmerPassword">Password</label>
                        <input type="password" class="form-control" id="farmerPassword" 
                               placeholder="Leave blank if not changing">
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="farmerLocation">Location</label>
                        <input type="text" class="form-control" id="farmerLocation">
                    </div>
                    
                    <div class="form-group">
                        <label for="farmerStatus">Account Status</label>
                        <select class="form-control" id="farmerStatus">
                            <option value="active">Active</option>
                            <option value="suspended">Suspended</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveFarmerBtn">Save Farmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Deletion</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this farmer? This action cannot be undone.</p>
                <p class="text-danger">This will delete the farmer's account and all associated data.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Farmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Optional: Role switcher for testing -->
<?php if (isset($_GET['dev']) && $_GET['dev'] === 'true'): ?>
<div class="role-switcher">
    <p>Test different roles:</p>
    <a href="?role=super_admin" class="btn btn-primary">Super Admin</a>
    <a href="?role=admin" class="btn btn-secondary">Admin</a>
</div>
<?php endif; ?>

<!-- JavaScript Dependencies -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

<!-- Custom Scripts -->
<script src="js/dashboard.js"></script>
<script src="js/manage_farmers.js"></script>
</body>
</html>