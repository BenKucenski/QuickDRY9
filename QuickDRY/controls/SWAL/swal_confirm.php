<script>
    let ConfirmDialogControl = {
        _cc_vars: null,
        _cc_callback: null,
        Load: function (title, text, action, action_callback, vars) {
            this._cc_callback = action_callback;
            this._cc_vars = vars;

            Swal.fire({
                title: title,
                html: `<h3>${text}</h3>`,
                showConfirmButton: true,
                confirmButtonText: action,
                showCancelButton: true,
                cancelButtonText: 'Do Not ' + action,
                focusCancel: true,
                allowOutsideClick: false,
                allowEscapeKey: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    ConfirmDialogControl.Confirm();
                }
            });
        },
        Confirm: function () {
            Swal.close();
            if (typeof (this._cc_callback) === 'function') {
                this._cc_callback(this._cc_vars);
            }
        }
    };

    function ConfirmDialog(title, text, action, action_callback, vars) {
        ConfirmDialogControl.Load(title, text, action, action_callback, vars);
    }
</script>