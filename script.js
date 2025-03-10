// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add a timestamp to show when the page was last loaded
    const timestampElement = document.createElement('div');
    timestampElement.className = 'timestamp';
    timestampElement.textContent = 'Page loaded at: ' + new Date().toLocaleString();
    
    // Add the timestamp to the footer
    const footer = document.querySelector('footer');
    if (footer) {
        footer.appendChild(timestampElement);
    }
    
    // Add animation to success/error messages
    const statusElements = document.querySelectorAll('.status');
    statusElements.forEach(function(element) {
        element.style.opacity = '0';
        element.style.transition = 'opacity 0.5s ease-in-out';
        
        setTimeout(function() {
            element.style.opacity = '1';
        }, 300);
    });
    
    // Add event listener to the form for validation
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const messageInput = document.getElementById('message');
            if (messageInput && messageInput.value.trim() === '') {
                event.preventDefault();
                alert('Please enter a message before submitting.');
            }
        });
    }
}); 