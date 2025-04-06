<?php
session_start();
require_once 'connect.php';
if (file_exists('FirebaseAuthHelper.php')) {
    require_once 'FirebaseAuthHelper.php'; // Include the Firebase helper class if it exists
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize Firebase Authentication Helper if available
$firebaseHelper = null;
try {
    if (class_exists('FirebaseAuthHelper')) {
        $firebaseHelper = new FirebaseAuthHelper($pdo);
    }
} catch (Exception $e) {
    error_log("Firebase initialization failed: " . $e->getMessage());
    // Continue without Firebase
}

// Authentication Check
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'super_admin'])) {
    // For development, create mock admin session
    if (!isset($_GET['role']) || ($_GET['role'] != 'admin' && $_GET['role'] != 'super_admin')) {
        $_GET['role'] = 'super_admin'; // Default to super_admin if no role specified
    }

    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = $_GET['role'];
    $_SESSION['name'] = ($_GET['role'] === 'super_admin') ? 'Super Admin User' : 'Admin User';
    $_SESSION['email'] = ($_GET['role'] === 'super_admin') ? 'superadmin@example.com' : 'admin@example.com';
}

// Set variables for template
$isSuperAdmin = ($_SESSION['role'] === 'super_admin');
$adminName = $_SESSION['name'] ?? $_SESSION['email'] ?? 'Admin';

// Pagination and Search Setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

try {
    // Base query for farmers
    $query = "SELECT * FROM users WHERE role = 'farmer'";
    $countQuery = "SELECT COUNT(*) as total FROM users WHERE role = 'farmer'";
    $params = [];

    // Apply search and filter conditions
    if (!empty($search)) {
        $query .= " AND (name LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
        $countQuery .= " AND (name LIKE ? OR email LIKE ? OR phone_number LIKE ?)";
        $searchParam = "%{$search}%";
        $params = [$searchParam, $searchParam, $searchParam];
    }

    if (!empty($status)) {
        $query .= " AND status = ?";
        $countQuery .= " AND status = ?";
        $params[] = $status;
    }

    // Fetch total farmers
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalResult = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalFarmers = $totalResult['total'];
    $totalPages = ceil($totalFarmers / $limit);

    // Add pagination
    $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Fetch farmers
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format the farmers data for display
    foreach ($farmers as &$farmer) {
        // Ensure status field exists
        if (!isset($farmer['status'])) {
            $farmer['status'] = 'active'; // Default status
        }
        
        // Format created_at date
        if (isset($farmer['created_at'])) {
            $date = new DateTime($farmer['created_at']);
            $farmer['formatted_date'] = $date->format('M j, Y');
        } else {
            $farmer['formatted_date'] = 'N/A';
        }
        
        // Ensure all necessary fields exist
        $farmer['location'] = $farmer['location'] ?? 'N/A';
        $farmer['phone_number'] = $farmer['phone_number'] ?? 'N/A';
    }

} catch (PDOException $e) {
    error_log("Farmers retrieval error: " . $e->getMessage());
    $farmers = [];
    $totalFarmers = 0;
    $totalPages = 1;
    // Continue with empty data rather than failing completely
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

        <div class="farmers-container">
            <div class="farmers-header">
                <div class="search-container">
                    <form method="get" action="manage_farmers.php">
                        <input type="text" id="searchFarmer" name="search" placeholder="Search farmers..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" id="searchBtn"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                <button class="btn add-farmer-btn" id="addFarmerBtn"><i class="fas fa-plus"></i> Add New Farmer</button>
            </div>

            <div class="farmers-table-container">
                <table class="farmers-table">
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
                        <?php if (empty($farmers)): ?>
                            <tr class="loading-row">
                                <td colspan="8" class="loading-message">No farmers found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($farmers as $farmer): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($farmer['id']); ?></td>
                                    <td><?php echo htmlspecialchars($farmer['name']); ?></td>
                                    <td><?php echo htmlspecialchars($farmer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($farmer['phone_number']); ?></td>
                                    <td><?php echo htmlspecialchars($farmer['location']); ?></td>
                                    <td><?php echo htmlspecialchars($farmer['formatted_date']); ?></td>
                                    <td>
                                        <span class="farmer-status status-<?php echo strtolower(htmlspecialchars($farmer['status'])); ?>">
                                            <?php echo ucfirst(htmlspecialchars($farmer['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="action-btn-small edit-btn" data-id="<?php echo $farmer['id']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="action-btn-small delete-btn-small" data-id="<?php echo $farmer['id']; ?>">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination" id="farmersPagination">
                <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                    <i class="fas fa-chevron-left"></i>
                </a>
                <span class="pagination-info">Page <span id="currentPage"><?php echo $page; ?></span> of <span id="totalPages"><?php echo $totalPages; ?></span></span>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>" class="pagination-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </main>
</div>

<!-- Add/Edit Farmer Modal -->
<div class="modal" id="farmerModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Add New Farmer</h3>
            <button class="close-btn" id="closeModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <form id="farmerForm" method="post" action="save_farmer.php">
                <input type="hidden" id="farmerId" name="id">
                
                <div class="form-group">
                    <label for="farmerName">Full Name*</label>
                    <input type="text" id="farmerName" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="farmerEmail">Email Address*</label>
                    <input type="email" id="farmerEmail" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="farmerPhone">Phone Number*</label>
                    <input type="tel" id="farmerPhone" name="phone_number" required>
                </div>
                
                <div class="form-group">
                    <label for="farmerPassword">Password</label>
                    <input type="password" id="farmerPassword" name="password" placeholder="Leave blank if not changing">
                    <small>Minimum 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="farmerLocation">Location</label>
                    <input type="text" id="farmerLocation" name="location">
                </div>
                
                <div class="form-group">
                    <label for="farmerStatus">Account Status</label>
                    <select id="farmerStatus" name="status">
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn cancel-btn" id="cancelFarmerBtn">Cancel</button>
                    <button type="submit" class="btn save-btn" id="saveFarmerBtn">Save Farmer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal" id="deleteModal">
    <div class="modal-content delete-modal">
        <div class="modal-header">
            <h3>Confirm Deletion</h3>
            <button class="close-btn" id="closeDeleteModal"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete this farmer? This action cannot be undone.</p>
            <p class="warning-text">This will delete the farmer's account and all associated data.</p>
            
            <div class="form-actions">
                <button type="button" class="btn cancel-btn" id="cancelDeleteBtn">Cancel</button>
                <button type="button" class="btn delete-btn" id="confirmDeleteBtn">Delete Farmer</button>
            </div>
        </div>
    </div>
</div>

<!-- Role switcher for testing -->
<div class="role-switcher">
    <p>Test different roles:</p>
    <a href="?role=super_admin" class="btn">Super Admin</a>
    <a href="?role=admin" class="btn">Admin</a>
</div>
<script src="/js/config.js"></script>
<script src="js/dashboard.js"></script>
<script src="js/manage_farmers.js"></script>
</body>
</html>