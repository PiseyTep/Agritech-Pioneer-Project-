console.log('CONFIG before DOMContentLoaded:', window.CONFIG);

const apiBaseUrl = window.CONFIG?.API_BASE_URL || 'http://172.20.10.3:8000';

document.addEventListener('DOMContentLoaded', function() {
    console.log('Rental Management Script Loaded');
    console.log('CONFIG at DOMContentLoaded:', CONFIG);
    
    if (typeof CONFIG === 'undefined') {
        console.error('CONFIG is NOT defined. Check script loading!');
        document.getElementById('rentalTable').innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-danger">
                    Configuration Error: config.js not loaded correctly
                </td>
            </tr>
        `;
        return;
    }
    // Enhanced Logging Function
    function logError(message, error = null) {
        console.error('RENTAL FETCH ERROR:', message);
        if (error) console.error(error);
        
        const rentalTable = document.getElementById('rentalTable');
        if (rentalTable) {
            rentalTable.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        <div class="alert alert-danger">
                            ${message}
                            <details>
                                <summary>Technical Details</summary>
                                <pre>${error ? JSON.stringify(error, null, 2) : 'No additional error information'}</pre>
                            </details>
                            <button onclick="fetchRentals()" class="btn btn-sm btn-outline-danger mt-2">
                                Retry Fetch
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
    }

    function fetchRentals() {
        console.log('Starting fetchRentals()');
        console.log('CONFIG API Base URL:', CONFIG.API_BASE_URL);

        // Token Check with Detailed Logging
        const token = localStorage.getItem('adminToken');
        if (!token) {
            alert('No authentication token found. Please log in.');
            window.location.href = '/login';
            return;
        }

        // Comprehensive Fetch Configuration
        fetch(`${apiBaseUrl}/api/admin/tractors`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            credentials: 'include'
        })
        .then(response => {
            console.log('Fetch Response Status:', response.status);
            console.log('Response Headers:', Object.fromEntries(response.headers.entries()));

            if (response.status === 401) {
                logError('Unauthorized: Invalid or expired token');
                localStorage.removeItem('adminToken');
                window.location.href = '/login';
                throw new Error('Unauthorized');
            }

            if (response.status === 403) {
                logError('Forbidden: Insufficient permissions');
                throw new Error('Forbidden');
            }

            if (!response.ok) {
                logError(`HTTP Error: ${response.status} ${response.statusText}`);
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.json();
        })
        .then(result => {
            console.log('Fetch Result:', result);

            const rentalTable = document.getElementById('rentalTable');
            if (!rentalTable) {
                console.error('Rental table element not found');
                return;
            }

            rentalTable.innerHTML = ''; // Clear existing rows

            if (result.success && result.data && result.data.length > 0) {
                result.data.forEach(rental => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${rental.farmer_name || 'Unknown'}</td>
                        <td>${rental.product_name || 'N/A'}</td>
                        <td>${rental.rental_date}</td>
                        <td>
                            <span class="badge ${
                                rental.status === 'pending' ? 'bg-warning' :
                                rental.status === 'approved' ? 'bg-success' :
                                rental.status === 'rejected' ? 'bg-danger' : 'bg-secondary'
                            }">
                                ${rental.status}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <button onclick="approveRental(${rental.id})" 
                                    class="btn btn-sm btn-outline-success"
                                    ${rental.status !== 'pending' ? 'disabled' : ''}>
                                    Approve
                                </button>
                                <button onclick="rejectRental(${rental.id})" 
                                    class="btn btn-sm btn-outline-danger"
                                    ${rental.status !== 'pending' ? 'disabled' : ''}>
                                    Reject
                                </button>
                            </div>
                        </td>
                    `;
                    rentalTable.appendChild(row);
                });
            } else {
                rentalTable.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted">
                            No rental requests found.
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Complete Fetch Error:', error);
            logError('Failed to load rental requests', error);
        });
    }

    // Global Error Handling
    window.addEventListener('unhandledrejection', function(event) {
        console.error('Unhandled Promise Rejection:', event.reason);
        logError('Unhandled system error', event.reason);
    });

    // Initial fetch on script load
    fetchRentals();
});