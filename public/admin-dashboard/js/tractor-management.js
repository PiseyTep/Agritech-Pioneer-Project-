// Tractor Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const apiBaseUrl = 'http://172.20.10.3:8000';
    const tractorForm = document.getElementById('tractorForm');
    const tractorTable = document.getElementById('tractorTable');
    const imagePreview = document.getElementById('imagePreview');
    const fileNameSpan = document.getElementById('fileName');

    // Image preview functionality
    document.getElementById('tractorImage').addEventListener('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            fileNameSpan.textContent = file.name;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            fileNameSpan.textContent = 'No file chosen';
            imagePreview.style.display = 'none';
        }
    });

    function fetchTractors() {
        console.log('Fetching tractors from:', `${apiBaseUrl}/admin/tractors`);
        
        fetch(`${apiBaseUrl}/admin/tractors`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            console.log('Response Status:', response.status);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return response.json();
        })
        .then(result => {
            console.log('API Response:', result);
            
            // Process tractors
            tractorTable.innerHTML = ''; 
            if (result.success && result.data) {
                result.data.forEach(tractor => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>
                            <img src="${tractor.image_url || 'placeholder.jpg'}" 
                                 alt="${tractor.name}" 
                                 class="tractor-image-thumbnail"
                                 onerror="this.src='placeholder.jpg'">
                        </td>
                        <td>${tractor.name}</td>
                        <td>${tractor.type}</td>
                        <td>${tractor.brand || '-'}</td>
                        <td>${tractor.horse_power || '0'} HP</td>
                        <td>$${tractor.price_per_acre}/acre</td>
                        <td>${tractor.stock}</td>
                        <td>${tractor.is_available ? 'Available' : 'Unavailable'}</td>
                        <td>
                            <button class="edit-btn" onclick="editTractor(${tractor.id})">Edit</button>
                            <button class="delete-btn" onclick="deleteTractor(${tractor.id})">Delete</button>
                        </td>
                    `;
                    tractorTable.appendChild(row);
                });
            } else {
                console.error('Failed to fetch tractors:', result.message);
                tractorTable.innerHTML = '<tr><td colspan="9">No tractors found</td></tr>';
            }
        })
        .catch(error => {
            console.error('Error fetching tractors:', error);
            tractorTable.innerHTML = `<tr><td colspan="9">Error loading tractors: ${error.message}</td></tr>`;
        });
    }

    // Add tractor functionality
    function addTractor(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);

        fetch(`${apiBaseUrl}/admin/tractors`, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Tractor added successfully');
                fetchTractors(); // Refresh tractor list
                event.target.reset(); // Clear form
                imagePreview.style.display = 'none';
                fileNameSpan.textContent = 'No file chosen';
            } else {
                const errorMessage = data.errors 
                    ? Object.values(data.errors).flat().join('\n')
                    : (data.message || 'Failed to add tractor');
                alert(`Error: ${errorMessage}`);
            }
        })
        .catch(error => {
            console.error('Error adding tractor:', error);
            alert('Failed to add tractor');
        });
    }

    // Edit tractor functionality
    window.editTractor = function(tractorId) {
        fetch(`${apiBaseUrl}/admin/tractors/${tractorId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                const tractor = result.data;
                
                // Populate form with tractor details
                document.getElementById('tractorName').value = tractor.name;
                document.getElementById('tractorType').value = tractor.type;
                document.getElementById('tractorBrand').value = tractor.brand || '';
                document.getElementById('horsePower').value = tractor.horse_power || '';
                document.getElementById('pricePerAcre').value = tractor.price_per_acre;
                document.getElementById('stock').value = tractor.stock;
                document.getElementById('tractorDescription').value = tractor.description || '';
                
                // Set availability checkbox if it exists
                const availableCheckbox = document.getElementById('isAvailable');
                if (availableCheckbox) {
                    availableCheckbox.checked = tractor.is_available;
                }
                
                // Change form to update mode
                tractorForm.dataset.mode = 'update';
                tractorForm.dataset.tractorId = tractorId;
                
                // Update submit button text
                const submitBtn = tractorForm.querySelector('button[type="submit"]');
                submitBtn.textContent = 'Update Tractor';
            } else {
                alert('Failed to load tractor details: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error fetching tractors:', error);
            logError('Failed to load tractors', error.message);
        });
        
    }

    // Update tractor functionality
    function updateTractor(event) {
        event.preventDefault();
        
        const tractorId = event.target.dataset.tractorId;
        const formData = new FormData(event.target);
        formData.append('_method', 'PUT'); // Laravel method spoofing

        fetch(`${apiBaseUrl}/admin/tractors/${tractorId}`, {
            method: 'POST', // Use POST with method spoofing
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchTractors(); // Refresh tractor list
                event.target.reset(); // Clear form
                
                // Reset form mode
                event.target.dataset.mode = 'add';
                delete event.target.dataset.tractorId;
                
                // Reset submit button
                const submitBtn = event.target.querySelector('button[type="submit"]');
                submitBtn.textContent = 'Add Tractor';
                
                // Reset image preview
                imagePreview.style.display = 'none';
                fileNameSpan.textContent = 'No file chosen';
                
                alert('Tractor updated successfully');
            } else {
                const errorMessage = data.errors 
                    ? Object.values(data.errors).flat().join('\n')
                    : (data.message || 'Failed to update tractor');
                alert(errorMessage);
            }
        })
        .catch(error => {
            console.error('Error updating tractor:', error);
            alert('Error updating tractor');
        });
    }

    // Delete tractor functionality
    window.deleteTractor = function(tractorId) {
        if (!confirm('Are you sure you want to delete this tractor?')) return;

        fetch(`${apiBaseUrl}/admin/tractors/${tractorId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchTractors(); // Refresh tractor list
                alert('Tractor deleted successfully');
            } else {
                alert(data.message || 'Failed to delete tractor');
            }
        })
        .catch(error => {
            console.error('Error deleting tractor:', error);
            alert('Error deleting tractor');
        });
    }

    // Form submission handler
    tractorForm.addEventListener('submit', function(event) {
        // Check if in update mode
        if (this.dataset.mode === 'update') {
            updateTractor(event);
        } else {
            addTractor(event);
        }
    });

    // Initial fetch of tractors
    fetchTractors();
});