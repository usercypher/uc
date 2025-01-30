function MessageDialog() {
    if (!this.dialogActivity) {
        // Create the dialog container and structure programmatically only once
        this.dialogActivity = document.createElement('div');
        this.dialogActivity.className = 'message-dialog-activity';

        this.dialog = document.createElement('div');
        this.dialog.className = 'message-dialog';

        this.dialogHeader = document.createElement('div');
        this.dialogHeader.className = 'message-dialog-header';
        this.title = document.createElement('h1');
        this.title.className = 'message-dialog-title';
        this.dialogHeader.appendChild(this.title);

        this.message = document.createElement('p');
        this.message.className = 'message-dialog-message';

        this.buttonContainer = document.createElement('div');
        this.buttonContainer.className = 'message-dialog-button-container';

        // Only the Cancel button
        this.cancelButton = document.createElement('button');
        this.cancelButton.className = 'message-dialog-cancel';
        this.cancelButton.textContent = 'x';
        this.buttonContainer.appendChild(this.cancelButton);

        this.dialog.appendChild(this.dialogHeader);
        this.dialog.appendChild(this.message);
        this.dialog.appendChild(this.buttonContainer);
        this.dialogActivity.appendChild(this.dialog);

        // Append dialog to the body
        document.body.appendChild(this.dialogActivity);

        // Bind default cancel button action
        var self = this;
        this.cancelButton.onclick = function() {
            self.close();
        };
    }
}

// Setters for dialog properties
MessageDialog.prototype.setDialogClass = function(dialogClass) {
    if (dialogClass) {
        this.dialog.className = 'message-dialog ' + dialogClass;
    }
};

MessageDialog.prototype.setTitleText = function(title) {
    this.title.textContent = title;
};

MessageDialog.prototype.setTitleClass = function(titleClass) {
    if (titleClass) {
        this.title.className = 'message-dialog-title ' + titleClass;
    }
};

MessageDialog.prototype.setMessageText = function(message) {
    this.message.textContent = message;
};

MessageDialog.prototype.setMessageClass = function(messageClass) {
    if (messageClass) {
        this.message.className = 'message-dialog-message ' + messageClass;
    }
};

MessageDialog.prototype.setCancelText = function(cancelText) {
    this.cancelButton.textContent = cancelText;
};

MessageDialog.prototype.setCancelClass = function(cancelClass) {
    if (cancelClass) {
        this.cancelButton.className = 'message-dialog-cancel ' + cancelClass;
    }
};


// Show the dialog
MessageDialog.prototype.show = function() {
    var self = this;

    waitForDocumentReady(function() {
        self.dialogActivity.classList.remove('show');
        setTimeout(function() {
            self.dialogActivity.classList.add('show');
        }, 0);
    });
};


MessageDialog.prototype.close = function() {
    this.dialogActivity.classList.remove('show');
};

window.MessageDialog = MessageDialog;

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