<?php
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

if (isset($flash) && is_array($flash)) {
    echo '<div id="popUpBgAlert" class="pop-up-bg-alert">';
    echo '<div id="dialogContainer" class="dialog-container">';

    foreach ($flash as $i => $f) {
        $message = ($f['message']);
        $type = isset($f['type']) ? $f['type'] : 'info'; // Default to 'info' if no type is provided
        echo '<div id="customAlertDialog' . $i . '" class="alert-dialog show">';
        echo '<div id="dialog-message" class="alert-dialog-content ' . $type . '">';
        echo '<h2>' . $f['type'] . '</h2>';
        echo '<p>' . $message . '</p>';
        echo '<button class="close-btn" onclick="closeDialog(\'' . $i . '\');">×</button>';
        echo '</div>';
        echo '</div>';
    }

    echo '</div>';
    echo '</div>';
    echo '<script>setTimeout(function() { document.getElementById(\'popUpBgAlert\').classList.add(\'show\'); }, 100);</script>';
}
?>

<!-- Loading Screen -->
<div id="loadingScreen">
    <div class="spinner"></div>
    <h2>Processing...</h2>
</div>

<!-- Custom confirmation dialog -->
<div id="popUpBgConfirm" class="pop-up-bg-confirm">
    <div id="dialogContainer" class="dialog-container">
        <div id="customConfirmDialog" class="confirm-dialog">
            <div class="confirm-dialog-content">
                <p id="dialogMessage">
                    Are you sure you want to delete this book?
                </p>
                <button id="confirmYes" class="confirm-button action">Yes</button>
                <button id="confirmNo" class="confirm-button">No</button>
            </div>
        </div>
    </div>
</div>