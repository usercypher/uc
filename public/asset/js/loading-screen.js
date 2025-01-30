function LoadingScreen() {
    // Create the container for the loading screen
    this.loadingScreen = document.createElement('div');
    this.loadingScreen.classList.add('loadingScreen'); // Set ID for styling

    // Create the spinner div
    this.spinner = document.createElement('div');
    this.spinner.classList.add('spinner'); // Add a class for styling

    // Create the processing text
    this.text = document.createElement('h2');
    this.text.textContent = 'Processing...';

    // Append spinner and text to the loadingScreen
    this.loadingScreen.appendChild(this.spinner);
    this.loadingScreen.appendChild(this.text);

    // Append loadingScreen to the body
    document.body.appendChild(this.loadingScreen);

    // Set initial visibility and opacity via JavaScript
    this.loadingScreen.style.visibility = 'hidden';
    this.loadingScreen.style.opacity = '0';
}

// Method to show the loading screen
LoadingScreen.prototype.show = function() {
    this.loadingScreen.style.visibility = 'visible';
    this.loadingScreen.style.opacity = '1';
};

// Method to hide the loading screen
LoadingScreen.prototype.hide = function() {
    this.loadingScreen.style.visibility = 'hidden';
    this.loadingScreen.style.opacity = '0';
};

window.LoadingScreen = LoadingScreen;