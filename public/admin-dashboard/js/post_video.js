document.getElementById('videoForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const title = document.getElementById('title').value;
    const video_url = document.getElementById('video_url').value;
    const description = 'Default description'; // Add input if needed

    const response = await fetch('http://172.20.10.3:8000/api/videos', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            title: title,
            url: video_url,
            description: description
        })
    });

    const result = await response.json();
    if (response.ok) {
        alert("✅ Video posted successfully!");
        // optionally clear form or reload video list
    } else {
        alert("❌ Error: " + (result.message || "Failed to post video"));
    }
});
