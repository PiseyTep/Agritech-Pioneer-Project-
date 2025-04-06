<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriTech Pioneer - Manage Tractors</title>
    <link rel="stylesheet" href="css/manage_product.css">
    <link rel="stylesheet" href="/LoginFarmer/Laravel-capstone/public/admin-dashboard/css/dashboard.css">
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
                <div class="header-title">
                    <h2><i class="fas fa-tractor"></i> Manage Tractors</h2>
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
                        <span id="adminName">Admin User</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <section class="form-section">
                <h3>Add New Tractor</h3>
                <form id="tractorForm">
                    <div class="form-row">
                        <div class="form-col">
                            <input type="text" id="tractorName" name="name" placeholder="Tractor Name" required>
                        </div>
                        <div class="form-col">
                            <input type="text" id="tractorBrand" name="brand" placeholder="Brand">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <input type="text" id="tractorType" name="type" placeholder="Type (e.g., Walking, Harvesting)" required>
                        </div>
                        <div class="form-col">
                            <input type="number" id="horsePower" name="horse_power" placeholder="Horse Power" min="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <input type="number" id="pricePerAcre" name="price_per_acre" placeholder="Price per Acre" step="0.01" min="0" required>
                        </div>
                        <div class="form-col">
                            <input type="number" id="stock" name="stock" placeholder="Available Stock" min="0" required>
                        </div>
                    </div>
                    
                    <textarea id="tractorDescription" name="description" placeholder="Description" required></textarea>
                    
                    <!-- Image upload field -->
                    <div class="image-upload-container">
                        <div class="file-input-wrapper">
                            <span class="file-input-button"><i class="fas fa-upload"></i> Choose Tractor Image</span>
                            <input type="file" id="tractorImage" name="image" accept="image/*">
                        </div>
                        <span class="file-name" id="fileName">No file chosen</span>
                        <img id="imagePreview" class="image-preview" src="" alt="Image Preview">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-col">
                            <label class="checkbox-container">
                                <input type="checkbox" id="isAvailable" name="is_available" checked>
                                <span class="checkmark"></span>
                                Available for Booking
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit">Add Tractor</button>
                </form>
            </section>

            <section class="table-section">
                <h3>Tractor Inventory</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Brand</th>
                            <th>HP</th>
                            <th>Price/Acre</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tractorTable">
                        <!-- Tractor rows will be inserted here -->
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    
    <script src="/js/config.js"></script>
    <script src="js/tractor-management.js"></script>
</body>
</html>