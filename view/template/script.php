
    <script>
        function flashMessage(jsonData) {
            if (jsonData && Array.isArray(jsonData)) {
                jsonData.forEach(function(item) {
                    var messageDialog = MessageDialog();
                
                    messageDialog.setDialogClass('custom-message-dialog-' + item['type']); // Dynamic class based on type
                    messageDialog.setTitleText(item['type']);
                    messageDialog.setTitleClass('custom-message-dialog-text');
                    messageDialog.setCancelClass('custom-message-dialog-text');
                    messageDialog.setMessageText(item['message']);
                    messageDialog.setMessageClass('custom-message-dialog-text');
                    window.onload = function() {
                        setTimeout(function() {
                            messageDialog.show();
                        }, 125);
                    };
                });
            }
        }

        function submitWithConfirm(event, message) {
            event.preventDefault();
            var confirmDialog = ConfirmDialog(function() {
                var loadingScreen = ProgressDialog();
                loadingScreen.setDialogClass('custom-progress-dialog');
                loadingScreen.setSpinnerClass('custom-progress-spinner');
                loadingScreen.show();

                setTimeout(function() {
                    event.target.submit();
                }, 300);
            });

            confirmDialog.setDialogClass('custom-confirm-dialog');
            confirmDialog.setMessageText(message || 'Are you sure?');
    
            confirmDialog.show();
        }

        flashMessage(<?php echo json_encode($data['flash']); ?>);
    </script>
