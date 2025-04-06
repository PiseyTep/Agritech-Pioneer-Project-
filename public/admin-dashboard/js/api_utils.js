/**
 * Utility functions for making API calls using the authentication token
 */

// Function to make authenticated API requests
async function apiRequest(endpoint, method = 'GET', data = null) {
    const token = localStorage.getItem('api_token');
    
    if (!token) {
      console.error('No authentication token found');
     // window.location.href = '/admin-dashboard/login.php';
      return;
    }
    
    const options = {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      }
    };
    
    if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
      options.body = JSON.stringify(data);
    }
    
    try {
      const response = await fetch(`/api/${endpoint}`, options);
      
      if (response.status === 401) {
        // Unauthorized - redirect to login
        localStorage.removeItem('api_token');
       // window.location.href = '/admin-dashboard/login.php';
        return;
      }
      
      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }
      
      return await response.json();
    } catch (error) {
      console.error(`Error ${method} ${endpoint}:`, error);
      throw error;
    }
  }
  
  // Helper functions for common API operations
  async function getData(endpoint) {
    return await apiRequest(endpoint);
  }
  
  async function createData(endpoint, data) {
    return await apiRequest(endpoint, 'POST', data);
  }
  
  async function updateData(endpoint, id, data) {
    return await apiRequest(`${endpoint}/${id}`, 'PUT', data);
  }
  
  async function deleteData(endpoint, id) {
    return await apiRequest(`${endpoint}/${id}`, 'DELETE');
  }
  
  // Verify user authentication status
  function checkAuth() {
    const token = localStorage.getItem('api_token');
    if (!token) {
      //window.location.href = '/admin-dashboard/login.php';
      return false;
    }
    return true;
  }
  
  // Logout function
  function logout() {
    localStorage.removeItem('api_token');
    sessionStorage.removeItem('email');
    sessionStorage.removeItem('user_role');
   // window.location.href = '/admin-dashboard/login.php';
  }