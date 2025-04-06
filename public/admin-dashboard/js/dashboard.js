// Initialize chart when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the chart
    initializeChart();
    loadDashboardStats(); 
    // Handle logout
    document.getElementById('logoutLink').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Clear any session data (not necessary for the mock version)
        sessionStorage.clear();
        localStorage.clear();
        
        // Just reload the page for the mock version
        alert('Logged out successfully');
        window.location.href = window.location.pathname;
    });
    
    // Initialize any dropdown menus
    const adminProfile = document.querySelector('.admin-profile');
    if (adminProfile) {
        adminProfile.addEventListener('click', function() {
            // Add dropdown menu functionality here if needed
            console.log('Admin profile clicked');
        });
    }
    
    // Add event listeners to all action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            alert('Action button clicked: View Details');
        });
    });
});
// Load dashboard statistics
f// Load dashboard statistics
function loadDashboardStats() {
    fetch('load_stats.php')
    .then(response => {
        if (!response.ok) {
            throw new Error('Failed to fetch dashboard stats');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update total farmers count in the dashboard
            document.getElementById('totalFarmers').textContent = data.data.totalFarmers;
            
            // Update other stats as needed
            if (data.data.totalAdmins) {
                document.getElementById('totalAdmins').textContent = data.data.totalAdmins;
            }
            if (data.data.totalMachines) {
                document.getElementById('totalMachines').textContent = data.data.totalMachines;
            }
            if (data.data.activeRentals) {
                document.getElementById('activeRentals').textContent = data.data.activeRentals;
            }
        } else {
            console.error('Error in stats data:', data.message);
        }
    })
    .catch(error => {
        console.error('Error loading dashboard stats:', error);
        // Fallback to mock data if API fails
        document.getElementById('totalFarmers').textContent = '48';
    });
}
// Initialize the rental performance chart
function initializeChart() {
    const ctx = document.getElementById('rentalChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Rentals',
                data: [12, 19, 15, 17, 14, 23],
                backgroundColor: 'rgba(44, 94, 26, 0.7)',
                borderColor: '#2c5e1a',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Add event listener to chart type selector
    document.getElementById('chartType').addEventListener('change', function() {
        // Here you would fetch new data based on the selected time period
        // For demo purposes, we'll just update with random data
        updateChartData(chart, this.value);
    });
    
    // Add event listener to refresh button
    document.getElementById('refreshChart').addEventListener('click', function() {
        const chartType = document.getElementById('chartType').value;
        updateChartData(chart, chartType);
    });
}

// Update chart data based on selected time period
function updateChartData(chart, timePeriod) {
    let labels;
    let data;
    
    switch(timePeriod) {
        case 'weekly':
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            data = Array.from({length: 7}, () => Math.floor(Math.random() * 20) + 5);
            break;
        case 'yearly':
            labels = ['2020', '2021', '2022', '2023', '2024', '2025'];
            data = Array.from({length: 6}, () => Math.floor(Math.random() * 100) + 50);
            break;
        default: // monthly
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            data = Array.from({length: 6}, () => Math.floor(Math.random() * 30) + 10);
    }
    
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.update();
}

// API utility functions for when you connect to your real backend

// Check if user is authenticated
function checkAuth() {
    const token = localStorage.getItem('api_token');
    if (!token) {
        window.location.href = '../login.php';
        return false;
    }
    return true;
}

// Function to get data from API
async function getData(endpoint) {
    try {
        const response = await fetch(`../api/${endpoint}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error(`Failed to fetch ${endpoint}:`, error);
        return null;
    }
}

// Function to post data to API
async function postData(endpoint, data) {
    try {
        const response = await fetch(`../api/${endpoint}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error(`Failed to post to ${endpoint}:`, error);
        return null;
    }
}

// Function for actual logout (for when you connect to your real backend)
function logoutReal() {
    // Clear token and session data
    localStorage.removeItem('api_token');
    sessionStorage.clear();
    
    // Redirect to login page
    window.location.href = '../login.php';
}// Initialize chart when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the chart
    initializeChart();
    
    // Handle logout
    document.getElementById('logoutLink').addEventListener('click', function(e) {
        e.preventDefault();
        
        // Clear any session data (not necessary for the mock version)
        sessionStorage.clear();
        localStorage.clear();
        
        // Just reload the page for the mock version
        alert('Logged out successfully');
        window.location.href = window.location.pathname;
    });
    
    // Initialize any dropdown menus
    const adminProfile = document.querySelector('.admin-profile');
    if (adminProfile) {
        adminProfile.addEventListener('click', function() {
            // Add dropdown menu functionality here if needed
            console.log('Admin profile clicked');
        });
    }
    
    // Add event listeners to all action buttons
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(button => {
        button.addEventListener('click', function() {
            alert('Action button clicked: View Details');
        });
    });
});

// Initialize the rental performance chart
function initializeChart() {
    const ctx = document.getElementById('rentalChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Rentals',
                data: [12, 19, 15, 17, 14, 23],
                backgroundColor: 'rgba(44, 94, 26, 0.7)',
                borderColor: '#2c5e1a',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Add event listener to chart type selector
    document.getElementById('chartType').addEventListener('change', function() {
        // Here you would fetch new data based on the selected time period
        // For demo purposes, we'll just update with random data
        updateChartData(chart, this.value);
    });
    
    // Add event listener to refresh button
    document.getElementById('refreshChart').addEventListener('click', function() {
        const chartType = document.getElementById('chartType').value;
        updateChartData(chart, chartType);
    });
}

// Update chart data based on selected time period
function updateChartData(chart, timePeriod) {
    let labels;
    let data;
    
    switch(timePeriod) {
        case 'weekly':
            labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            data = Array.from({length: 7}, () => Math.floor(Math.random() * 20) + 5);
            break;
        case 'yearly':
            labels = ['2020', '2021', '2022', '2023', '2024', '2025'];
            data = Array.from({length: 6}, () => Math.floor(Math.random() * 100) + 50);
            break;
        default: // monthly
            labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            data = Array.from({length: 6}, () => Math.floor(Math.random() * 30) + 10);
    }
    
    chart.data.labels = labels;
    chart.data.datasets[0].data = data;
    chart.update();
}

// API utility functions for when you connect to your real backend

// Check if user is authenticated
function checkAuth() {
    const token = localStorage.getItem('api_token');
    if (!token) {
        window.location.href = '../login.php';
        return false;
    }
    return true;
}

// Function to get data from API
async function getData(endpoint) {
    try {
        const response = await fetch(`../api/${endpoint}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error(`Failed to fetch ${endpoint}:`, error);
        return null;
    }
}

// Function to post data to API
async function postData(endpoint, data) {
    try {
        const response = await fetch(`../api/${endpoint}`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        if (!response.ok) {
            throw new Error(`API Error: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error(`Failed to post to ${endpoint}:`, error);
        return null;
    }
}

// Function for actual logout (for when you connect to your real backend)
function logoutReal() {
    // Clear token and session data
    localStorage.removeItem('api_token');
    sessionStorage.clear();
    
    // Redirect to login page
    window.location.href = '../login.php';
}