// Show loading animation before form submission
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.submit-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            const loadingScreen = document.getElementById('loadingScreen');
            loadingScreen.style.display = 'flex';

            setTimeout(() => {
                form.submit();
            }, 300);
            event.preventDefault();
        });
    });
});

// for confirm dialog
function confirm(event, message) {
    event.preventDefault();

    const popUpBg = document.getElementById('popUpBgConfirm');
    const dialog = document.getElementById('customConfirmDialog');
    const confirmYes = document.getElementById('confirmYes');
    const confirmNo = document.getElementById('confirmNo');
    const loadingScreen = document.getElementById('loadingScreen');
    const dialogMessage = document.getElementById('dialogMessage');

    dialogMessage.textContent = message;

    popUpBg.classList.add('show');
    dialog.classList.add('show');

    confirmYes.onclick = function () {
        popUpBg.classList.remove('show');
        dialog.classList.remove('show');
        loadingScreen.style.display = 'flex';

        setTimeout(() => {
            event.target.closest('form').submit();
        }, 300); 
    };

    confirmNo.onclick = function () {
        popUpBg.classList.remove('show');
        dialog.classList.remove('show');
    };
}

// for alert dialog
function closeDialog(dialogId) {
    document.getElementById('customAlertDialog' + dialogId).classList.remove('show');
    setTimeout(() => {
        document.getElementById('customAlertDialog' + dialogId).style.display = 'none';
        var allDialogs = document.querySelectorAll('.alert-dialog');
        var allClosed = Array.from(allDialogs).every(function(dialog) {
            return dialog.style.display === 'none';
        });

        if (allClosed) {
            document.getElementById('popUpBgAlert').classList.remove('show');
        }
    }, 100); 
}
