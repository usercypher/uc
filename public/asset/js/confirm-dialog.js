function ConfirmDialog() {
    if (!this.dialogActivity) {
        // Create the dialog container and structure programmatically only once
        this.dialogActivity = document.createElement('div');
        this.dialogActivity.className = 'confirm-dialog-activity';

        this.dialog = document.createElement('div');
        this.dialog.className = 'confirm-dialog';

        this.dialogHeader = document.createElement('div');
        this.dialogHeader.className = 'confirm-dialog-header';
        this.title = document.createElement('h1');
        this.title.className = 'confirm-dialog-title';
        this.title.textContent = 'Confirm';
        this.dialogHeader.appendChild(this.title);

        this.message = document.createElement('p');
        this.message.className = 'confirm-dialog-message';
        this.message.textContent = 'Are you sure?';

        this.buttonContainer = document.createElement('div');
        this.buttonContainer.className = 'confirm-dialog-button-container';

        this.confirmButton = document.createElement('button');
        this.confirmButton.className = 'confirm-dialog-yes btn';
        this.confirmButton.textContent = 'YES';
        this.buttonContainer.appendChild(this.confirmButton);

        this.cancelButton = document.createElement('button');
        this.cancelButton.className = 'confirm-dialog-no btn';
        this.cancelButton.textContent = 'NO';
        this.buttonContainer.appendChild(this.cancelButton);

        this.dialog.appendChild(this.dialogHeader);
        this.dialog.appendChild(this.message);
        this.dialog.appendChild(this.buttonContainer);
        this.dialogActivity.appendChild(this.dialog);

        // Append dialog to the body
        document.body.appendChild(this.dialogActivity);

        // Bind default button actions
        var self = this;
        this.confirmButton.onclick = function() {
            self.close();
        };
        this.cancelButton.onclick = function() {
            self.close();
        };
    }
}

// Setters for dialog properties
ConfirmDialog.prototype.setDialogClass = function(dialogClass) {
    if (dialogClass) {
        this.dialog.className = 'confirm-dialog ' + dialogClass;
    }
};

ConfirmDialog.prototype.setTitleText = function(title) {
    this.title.textContent = title || 'Confirm';
};

ConfirmDialog.prototype.setTitleClass = function(titleClass) {
    if (titleClass) {
        this.title.className = 'confirm-dialog-tite ' + titleClass;
    }
};

ConfirmDialog.prototype.setMessageText = function(message) {
    this.message.textContent = message || 'Are you sure?';
};

ConfirmDialog.prototype.setMessageClass = function(messageClass) {
    if (messageClass) {
        this.message.className = 'confirm-dialog-message ' + messageClass;
    }
};

ConfirmDialog.prototype.setConfirmText = function(confirmText) {
    this.confirmButton.textContent = confirmText || 'YES';
};

ConfirmDialog.prototype.setConfirmClass = function(confirmClass) {
    if (confirmClass) {
        this.confirmButton.className = 'confirm-dialog-yes btn ' + confirmClass;
    }
};

ConfirmDialog.prototype.setCancelText = function(cancelText) {
    this.cancelButton.textContent = cancelText || 'NO';
};

ConfirmDialog.prototype.setCancelClass = function(cancelClass) {
    if (cancelClass) {
        this.cancelButton.className = 'confirm-dialog-no btn ' + cancelClass;
    }
};

// Show the dialog
ConfirmDialog.prototype.show = function(onConfirm) {
    var self = this;

    waitForDocumentReady(function() {
        // Ensure the dialog is hidden before showing it
        self.dialogActivity.classList.remove('show');

        // Use setTimeout to allow the browser to render the initial state
        setTimeout(function() {
            self.dialogActivity.classList.add('show');

            // Set confirmation action
            self.confirmButton.onclick = function() {
                if (onConfirm) {
                    onConfirm();
                }
                self.close();
            };
        },
            0);
    });
};

// Close the dialog
ConfirmDialog.prototype.close = function() {
    this.dialogActivity.classList.remove('show');
};

// Expose the constructor to the global scope
window.ConfirmDialog = ConfirmDialog;

function waitForDocumentReady(callback) {
    if (document.readyState === "complete") {
        callback();
    } else {
        var checkReadyState = function() {
            if (document.readyState === "complete") {
                callback();
            } else {
                setTimeout(checkReadyState, 100);
            }
        };

        checkReadyState();
    }
}