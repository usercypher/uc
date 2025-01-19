function confirm(event, message) {
    event.preventDefault(); // Prevent form submission

    const dialog = document.getElementById('customConfirmDialog');
    const confirmYes = document.getElementById('confirmYes');
    const confirmNo = document.getElementById('confirmNo');
    const loadingScreen = document.getElementById('loadingScreen');
    const dialogMessage = document.getElementById('dialogMessage');

    // Set the custom message
    dialogMessage.textContent = message;

    // Show the dialog with a smooth animation
    dialog.classList.add('show');

    // Set up event listeners for the buttons
    confirmYes.onclick = function () {
        dialog.classList.remove('show'); // Hide dialog smoothly
        loadingScreen.style.display = 'flex'; // Show loading screen

        // Submit the form after confirmation
        setTimeout(() => {
            event.target.closest('form').submit();
        }, 300); // Allow the animation to complete
    };

    confirmNo.onclick = function () {
        dialog.classList.remove('show'); // Hide the dialog smoothly
    };
}
