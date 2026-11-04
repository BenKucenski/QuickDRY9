$(function () {
    $('#wait_dialog').hide();
});

let WaitDialogControl = {
    _callback: null,
    Load: function (title, text, callback) {
        QuickDRY.CloseDialogIfOpen('wait_dialog');
        QuickDRY.ShowModal('wait_dialog', title);
        $('#ctl_wait_text').html(text);
        if (typeof (callback) === 'function') {
            WaitDialogControl._callback = callback;
            WaitDialogControl._DoCallback();
        }
    },
    _DoCallback: function () {
        // using a callback you can force the
        // dialog to finish opening before making an
        // ajax request
        // otherwise you may end up with the wait dialog open
        // and the error dialog from the request
        if (!QuickDRY.DialogIsOpen('wait_dialog')) {
            setTimeout('WaitDialogControl._DoCallback();', 100);
            return;
        }
        WaitDialogControl._callback();

    }
};


function WaitDialog(title, text, callback) {
    WaitDialogControl.Load(title, text, callback);
}
