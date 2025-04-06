document.getElementById('adminLoginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    const response = await fetch('admin_auth_handler.php', {
        method: 'POST',
        body: formData
    });

    const result = await response.json();

    if (result.success) {
        window.location.href = 'index.php';
    } else {
        document.getElementById('error-message').textContent = result.message;
    }
});