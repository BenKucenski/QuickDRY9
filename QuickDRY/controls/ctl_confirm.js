let ConfirmDialogControl = {
    _cc_vars: null,
    _cc_callback: null,
    Load: function (title, text, action, action_callback, vars) {
        QuickDRY.ShowModal('cc_dialog', title);

        this._cc_callback = action_callback;
        this._cc_vars = vars;

        $('#cc_text').html(text);
        $('.cc_action_button').html(action);
        $('.cc_cancel_button').html('Do Not ' + action);
        $('.cc_cancel_button').focus();

    },
    Confirm: function () {
        QuickDRY.CloseDialogIfOpen('cc_dialog');
        if (typeof (this._cc_callback) === "function") {
            this._cc_callback(this._cc_vars);
        }
    }
};

function ConfirmDialog(title, text, action, action_callback, vars) {
    ConfirmDialogControl.Load(title, text, action, action_callback, vars);
}

