<?php
$flash = array();
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>

    <script>
        var jsonData = <?php echo json_encode($flash); ?>;
    
        if (jsonData && Array.isArray(jsonData)) {
            jsonData.forEach(function(item) {
                var messageDialog = new MessageDialog();
    
                messageDialog.setTitleText(item['type']);
                messageDialog.setDialogClass('message-dialog-' + item['type']); // Dynamic class based on type
                messageDialog.setMessageText(item['message']);
    
                messageDialog.show();
            });
        }
    
        function submitWithConfirm(event, message) {
            event.preventDefault();
            var confirmDialog = new ConfirmDialog();
    
            // Set title, message, and button text to long strings
            confirmDialog.setDialogClass('custom-confirm-dialog');
            confirmDialog.setMessageText(message);
            confirmDialog.setConfirmClass('btn-red');
            confirmDialog.setCancelClass('btn-grey');
    
            // Show the dialog with the custom long content
            confirmDialog.show(function() {
                var loadingScreen = new LoadingScreen();
                loadingScreen.show();
    
    
                // Simulate form submission after a short delay
                setTimeout(function() {
                    event.target.submit();
                }, 300);
            });
        }
    </script>
