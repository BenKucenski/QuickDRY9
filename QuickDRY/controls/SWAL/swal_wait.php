<script>
    let WaitDialogControl = {
        _callback: null,
        _isOpen: false,
        Load: function (title, text, callback) {
            // Close any open swal first
            Swal.close();

            WaitDialogControl._isOpen = false;

            Swal.fire({
                title: title,
                html: `<h3>${text}</h3><img src="/QuickDRY/images/ajax-loader-lg.gif" alt=""/>`,
                showConfirmButton: false,
                showCloseButton: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    WaitDialogControl._isOpen = true;
                    if (typeof callback === 'function') {
                        WaitDialogControl._callback = callback;
                        WaitDialogControl._DoCallback();
                    }
                },
                willClose: () => {
                    WaitDialogControl._isOpen = false;
                }
            });
        },
        _DoCallback: function () {
            if (!WaitDialogControl._isOpen) {
                setTimeout(function() { WaitDialogControl._DoCallback(); }, 100);
                return;
            }
            WaitDialogControl._callback();
        }
    };

    function WaitDialog(title, text, callback) {
        WaitDialogControl.Load(title, text, callback);
    }
</script>