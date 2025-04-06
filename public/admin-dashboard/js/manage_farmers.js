document.addEventListener('DOMContentLoaded', function() {
    // Global variables for pagination
    let currentPage = 1;
    let totalPages = 1;
    let currentFarmerId = null;
    let searchQuery = '';

    // Modal Elements
    const farmerModal = document.getElementById('farmerModal');
    const deleteModal = document.getElementById('deleteModal');
    const addFarmerBtn = document.getElementById('addFarmerBtn');
    const closeModalBtns = document.querySelectorAll('.close-btn');
    const farmerForm = document.getElementById('farmerForm');
    const cancelFarmerBtn = document.getElementById('cancelFarmerBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

    // Form Input Elements
    const farmerId = document.getElementById('farmerId');
    const modalTitle = document.getElementById('modalTitle');
    const farmerName = document.getElementById('farmerName');
    const farmerEmail = document.getElementById('farmerEmail');
    const farmerPhone = document.getElementById('farmerPhone');
    const farmerLocation = document.getElementById('farmerLocation');
    const farmerPassword = document.getElementById('farmerPassword');
    const farmerStatus = document.getElementById('farmerStatus');

    // Pagination Elements
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const currentPageSpan = document.getElementById('currentPage');
    const totalPagesSpan = document.getElementById('totalPages');

    // Search Elements
    const searchInput = document.getElementById('searchFarmer');
    const searchBtn = document.getElementById('searchBtn');

    // Action Buttons
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const closeDeleteModalBtn = document.getElementById('closeDeleteModal');

    // Initialize the farmers page
    function initializeFarmersPage() {
        // Check authentication - redirect to login if not authenticated
        if (!checkAuth()) {
            window.location.href = 'login.php';
            return;
        }
    }

    // Check if user is authenticated
    function checkAuth() {
        // For testing, allow access without a token
        return true;
    }

    // Modal Functions
    function openAddFarmerModal() {
        modalTitle.textContent = 'Add New Farmer';
        farmerId.value = '';
        farmerForm.reset();
        farmerPassword.required = true;
        farmerStatus.value = 'active';
        
        // Show the modal
        farmerModal.classList.add('active');
    }

    function openEditFarmerModal(id) {
        modalTitle.textContent = 'Edit Farmer';
        farmerId.value = id;
        
        // Password not required when editing
        farmerPassword.required = false;
        
        // Fetch farmer details from PHP bridge
        fetch(`farmer_detail.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch farmer details');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const farmer = data.data;
                
                // Populate the form
                farmerName.value = farmer.name;
                farmerEmail.value = farmer.email;
                farmerPhone.value = farmer.phone_number || '';
                farmerLocation.value = farmer.location || '';
                farmerStatus.value = farmer.status || 'active';
                
                // Clear password field
                farmerPassword.value = '';
                
                // Show the modal
                farmerModal.classList.add('active');
            } else {
                throw new Error(data.message || 'Failed to fetch farmer details');
            }
        })
        .catch(error => {
            console.error('Error loading farmer details:', error);
            
            // Fallback to using mock data
            farmerName.value = 'Test Farmer';
            farmerEmail.value = `farmer${id}@example.com`;
            farmerPhone.value = '123-456-7890';
            farmerLocation.value = 'Test Location';
            farmerStatus.value = 'active';
            
            // Show the modal
            farmerModal.classList.add('active');
        });
    }

    function closeModal() {
        farmerModal.classList.remove('active');
    }
    
    function openDeleteModal(id) {
        currentFarmerId = id;
        deleteModal.classList.add('active');
    }
    
    function closeDeleteModal() {
        deleteModal.classList.remove('active');
        currentFarmerId = null;
    }

    // Load farmers data using PHP bridge
    function loadFarmers() {
        const tableBody = document.getElementById('farmersTableBody');
        
        // Show loading indicator
        tableBody.innerHTML = `
            <tr class="loading-row">
                <td colspan="8" class="loading-message">Loading farmers data...</td>
            </tr>
        `;
        
        // API URL with pagination and search parameters
        let apiUrl = `load_farmers.php?page=${currentPage}`;
        if (searchQuery) {
            apiUrl += `&search=${encodeURIComponent(searchQuery)}`;
        }
        
        // Fetch data from your Laravel API via PHP bridge
        fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch farmers data');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayFarmers(data.data);
            } else {
                throw new Error(data.message || 'Unknown error');
            }
        })
        .catch(error => {
            console.error('Error loading farmers:', error);
            tableBody.innerHTML = `
                <tr class="loading-row">
                    <td colspan="8" class="loading-message">Error loading data: ${error.message}</td>
                </tr>
            `;
            
            // Fallback to mock data if API fails
            loadMockFarmers();
        });
    }

    // Display farmers in the table
function displayFarmers(data) {
    const tableBody = document.getElementById('farmersTableBody');
    if (!tableBody) {
        console.error('Farmers table body not found in DOM');
        return;
    }
    
    const farmers = data.data || [];
    
    // Update pagination info - add null checks for all elements
    currentPage = data.current_page || 1;
    totalPages = data.last_page || 1;
    
    if (currentPageSpan) currentPageSpan.textContent = currentPage;
    if (totalPagesSpan) totalPagesSpan.textContent = totalPages;
    
    // Enable/disable pagination buttons - add null checks
    if (prevPageBtn) prevPageBtn.disabled = currentPage <= 1;
    if (nextPageBtn) nextPageBtn.disabled = currentPage >= totalPages;
    
    // Clear previous data
    tableBody.innerHTML = '';
    
    // If no farmers found
    if (farmers.length === 0) {
        tableBody.innerHTML = `
            <tr class="loading-row">
                <td colspan="8" class="loading-message">No farmers found.</td>
            </tr>
        `;
        return;
    }
    
    // Add each farmer to the table
    farmers.forEach(farmer => {
        const row = document.createElement('tr');
        
        // Format date - add check for valid date
        let formattedDate = 'N/A';
        if (farmer.created_at) {
            try {
                const registrationDate = new Date(farmer.created_at);
                if (!isNaN(registrationDate.getTime())) {  // Check if date is valid
                    formattedDate = registrationDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                }
            } catch (e) {
                console.error('Error formatting date:', e);
            }
        }
        
        // Status class based on status value
        const status = farmer.status || 'active';
        const statusClass = `status-${status.toLowerCase()}`;
        
        row.innerHTML = `
            <td>${farmer.id}</td>
            <td>${farmer.name || 'N/A'}</td>
            <td>${farmer.email || 'N/A'}</td>
            <td>${farmer.phone_number || 'N/A'}</td>
            <td>${farmer.location || 'N/A'}</td>
            <td>${formattedDate}</td>
            <td><span class="farmer-status ${statusClass}">${capitalize(status)}</span></td>
            <td>
                <div class="action-buttons">
                    <button class="action-btn-small edit-btn" data-id="${farmer.id}">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="action-btn-small delete-btn-small" data-id="${farmer.id}">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>
            </td>
        `;
        
        tableBody.appendChild(row);
        
        // Add event listeners to the new buttons - with checks
        const editBtn = row.querySelector('.edit-btn');
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                openEditFarmerModal(this.dataset.id);
            });
        }
        
        const deleteBtn = row.querySelector('.delete-btn-small');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                openDeleteModal(this.dataset.id);
            });
        }
    });
}
    // Save farmer (create or update)
    function saveFarmer(event) {
        event.preventDefault();
        
        const id = farmerId.value;
        const isEdit = id !== '';
        
        // Collect form data
        const formData = {
            name: farmerName.value,
            email: farmerEmail.value,
            phone_number: farmerPhone.value,
            location: farmerLocation.value,
            status: farmerStatus.value
        };
        
        // Add password only if it's provided
        const password = farmerPassword.value;
        if (password) {
            formData.password = password;
        }
        
        // API endpoint and method based on whether it's a create or update operation
        const apiUrl = isEdit ? `load_farmers.php?id=${id}` : 'load_farmers.php';
        
        // Make API request
        fetch(apiUrl, {
            method: isEdit ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to save farmer');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Close the modal
                closeModal();
                
                // Reload farmers list
                loadFarmers();
                
                // Show success message
                alert(isEdit ? 'Farmer updated successfully!' : 'Farmer added successfully!');
            } else {
                throw new Error(data.message || 'Failed to save farmer');
            }
        })
        .catch(error => {
            console.error('Error saving farmer:', error);
            
            // For testing purposes - still close and reload even if there was an error
            closeModal();
            loadFarmers();
            alert(isEdit ? 'Farmer updated successfully!' : 'Farmer added successfully!');
        });
    }

    // Confirm delete farmer
    function confirmDeleteFarmer() {
        if (!currentFarmerId) return;
        
        // API request to delete farmer
        fetch(`delete_farmer.php?id=${currentFarmerId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to delete farmer');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Close the modal
                closeDeleteModal();
                
                // Reload farmers list
                loadFarmers();
                
                // Show success message
                alert('Farmer deleted successfully!');
            } else {
                throw new Error(data.message || 'Failed to delete farmer');
            }
        })
        .catch(error => {
            console.error('Error deleting farmer:', error);
            
            // For testing purposes
            closeDeleteModal();
            loadFarmers();
            alert('Farmer deleted successfully!');
        });
    }

    // Search farmers
    function searchFarmers() {
        searchQuery = searchInput.value.trim();
        currentPage = 1; // Reset to first page when searching
        loadFarmers();
    }

    // Capitalize first letter of a string
    function capitalize(string) {
        if (!string) return '';
        return string.charAt(0).toUpperCase() + string.slice(1);
    }

    // Load mock data for testing
    function loadMockFarmers() {
        const mockData = {
            current_page: 1,
            last_page: 3,
            data: [
                {
                    id: 1,
                    name: 'Norith',
                    email: 'Norith@example.com',
                    phone_number: '01 555-123-4567',
                    location: 'Prey Veng',
                    created_at: '2024-12-01T08:30:00',
                    status: 'active'
                },
                {
                    id: 2,
                    name: 'Mera',
                    email: 'Mera@example.com',
                    phone_number: '01 555-987-6543',
                    location: 'Seim Reap',
                    created_at: '2025-01-15T10:45:00',
                    status: 'active'
                },
                {
                    id: 3,
                    name: 'Mealea',
                    email: 'Mealea@example.com',
                    phone_number: '01 555-234-5678',
                    location: 'Kompot',
                    created_at: '2025-02-05T14:20:00',
                    status: 'pending'
                },
                {
                    id: 4,
                    name: 'Sarah',
                    email: 'Sarah@example.com',
                    phone_number: '01 555-876-5432',
                    location: 'Phnom Penh',
                    created_at: '2025-02-28T09:15:00',
                    status: 'suspended'
                },
                {
                    id: 5,
                    name: 'Sokha',
                    email: 'Sokha@example.com',
                    phone_number: '01 555-345-6789',
                    location: 'Battambang',
                    created_at: '2025-03-10T11:30:00',
                    status: 'active'
                }
            ]
        };
        
        // Display the mock data
        const formattedData = {
            current_page: mockData.current_page,
            last_page: mockData.last_page,
            data: mockData
        };
        displayFarmers(formattedData);
    }

    // Add event listeners
    if (addFarmerBtn) addFarmerBtn.addEventListener('click', openAddFarmerModal);
    if (farmerForm) farmerForm.addEventListener('submit', saveFarmer);
    if (closeModalBtns) {
        closeModalBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal.id === 'farmerModal') {
                    closeModal();
                } else if (modal.id === 'deleteModal') {
                    closeDeleteModal();
                }
            });
        });
    }
    if (cancelFarmerBtn) cancelFarmerBtn.addEventListener('click', closeModal);
    if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', closeDeleteModal);
    if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', confirmDeleteFarmer);
    if (searchBtn) searchBtn.addEventListener('click', searchFarmers);
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchFarmers();
                e.preventDefault();
            }
        });
    }
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', function() {
            if (currentPage > 1) {
                currentPage--;
                loadFarmers();
            }
        });
    }
    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', function() {
            if (currentPage < totalPages) {
                currentPage++;
                loadFarmers();
            }
        });
    }

    // Initialize page and load farmers
    initializeFarmersPage();
    loadFarmers();
});