let QuickDRY = {
    _Post: function (url, vars, callback, dialog) {
        let modalClass = null;
        if (dialog) {
            modalClass = dialog.replace('_dialog', '');
        }

        $.ajax({
            type: "POST",
            url: url,
            data: vars,
            success: function (data) {
                QuickDRY.CloseDialogIfOpen('wait_dialog');
                if (data && data.error) {
                    HTTP.Notice('Error', data.error);
                    if (modalClass && window[modalClass]) {
                        window[modalClass]._active = true;
                    }
                } else {
                    if (data && data.success && $.n) {
                        $.n.success(data.success);
                    }
                    if (dialog) {
                        QuickDRY.CloseDialogIfOpen(dialog);
                    }
                    if (typeof (callback) === "function") {
                        callback(data);
                    }
                }
            },
            error: function (XMLHttpRequest) {
                let data = XMLHttpRequest.responseJSON;
                QuickDRY.CloseDialogIfOpen('wait_dialog');
                HTTP.Notice('Error', (data && data.error) || ('An unknown error occurred (HTTP ' + XMLHttpRequest.status + ').'));
                if (modalClass && window[modalClass]) {
                    window[modalClass]._active = true;
                }
            }
        });

    },
    Create: function (type, vars, callback, dialog) {
        if (!QuickDRY.DialogIsOpen('wait_dialog')) {
            HTTP.Wait('Please Wait', 'Saving...');
        }

        vars.verb = 'PUT';
        QuickDRY._Post('/json/' + type, vars, callback, dialog)
    },

    Read: function (type, vars, callback) {
        vars.verb = 'GET';
        QuickDRY._Post('/json/' + type, vars, callback)
    },

    Update: function (type, vars, callback, dialog) {
        if (!QuickDRY.DialogIsOpen('wait_dialog')) {
            HTTP.Wait('Please Wait', 'Saving...');
        }

        vars.verb = 'POST';
        QuickDRY._Post('/json/' + type, vars, callback, dialog)
    },

    ConfirmDelete: function (object_type, object_name, vars, document_number, callback, dialog) {
        let msg = 'You are about to delete ' + object_name;
        if (document_number) {
            msg += ' ' + document_number;
        }
        msg += '. Are you sure?';

        ConfirmDialogControl.Load('Delete ' + object_name, msg,
            'Delete', QuickDRY.ConfirmDeleteCallback, {
                object_type: object_type,
                vars: vars,
                callback: callback,
                dialog: dialog
            });
    },

    ConfirmDeleteCallback: function (data) {
        QuickDRY.Delete(data.object_type, data.vars, data.callback, true, data.dialog);
    },

    /**
     *
     * @param type
     * @param vars
     * @param callback
     * @param confirmed
     * @param dialog
     * @returns {boolean}
     * @constructor
     */
    Delete: function (type, vars, callback, confirmed, dialog) {
        if (confirmed !== true) {
            if (typeof ConfirmDialog === 'function') {
                ConfirmDialog('Delete', 'Are you sure?', 'Delete', function () {
                    QuickDRY.Delete(type, vars, callback, true, dialog);
                });
                return false;
            }
            if (!confirm('Are you sure?')) {
                return false;
            }
        }

        HTTP.Wait('Please Wait', 'Deleting...', function () {
            vars.verb = 'DELETE';
            HTTP.Post('/json/' + type, vars, callback, null, dialog);
        });
    },

    LoadForm: function (data, elem_id) {
        for (let i in data.data) {
            let elem = $('#' + elem_id + '_' + i);
            let elem_cur = $('#' + elem_id + '_' + i + '_cur');
            let elem_hidden = $('#' + elem_id + '_' + i + '_hidden');

            if (typeof (elem) == 'object') {
                if (elem.prop("type") === 'checkbox') {
                    if (parseInt(data.data[i]) === 1) {
                        elem.prop('checked', true);
                    } else {
                        elem.prop('checked', false);
                    }
                    if (typeof (elem_hidden) == 'object') {
                        elem_hidden.val(data.data[i]);
                    }

                    if (typeof (elem_cur) == 'object') {
                        if (parseInt(data.data[i]) === 1) {
                            elem_cur.html('Yes');
                        } else {
                            elem_cur.html('No');
                        }
                    }
                } else {
                    if (elem.hasClass('date-picker') || elem.attr('type') === 'date') {
                        if (data.data[i])
                            if (data.data[i].length > 7) {
                                let t = data.data[i];
                                t = t.split(' ');
                                t = t[0];
                                t = t.split('-');
                                if (t[1] === undefined) {
                                    elem.val(t);
                                } else {
                                    if (elem.attr('type') !== 'date') { // date-picker user mm/dd/yyyy
                                        elem.val(t[1] + '/' + t[2] + '/' + t[0]);
                                    } else { // date form type uses yyyy-mm-dd
                                        elem.val(data.data[i]);
                                    }
                                }
                                if (typeof (elem_cur) == 'object') {
                                    elem_cur.html(t[1] + '/' + t[2] + '/' + t[0]);
                                }
                            }
                    } else {
                        elem.val(data.data[i]);
                        if (typeof (elem_cur) == 'object') {
                            elem_cur.html(data.data[i]);
                        }
                    }
                }
            }
        }
    },

    ClearForm: function (form_id, clear_hidden) {
        $('#' + form_id).each(function () {
            this.reset();
        });
        $('#' + form_id + ' input[type=checkbox]').each(function () {
            this.checked = false;
        });

        // reset doesn't clear out hidden fields, so this has to be done separately
        if (typeof (clear_hidden) === "undefined" || clear_hidden)
            $('#' + form_id + ' input[type=hidden]').each(function () {
                $(this).val('');
            });
    },
    /**
     * The SweetAlert wait popup (controls/SWAL/swal_wait.php) has no #wait_dialog element,
     * so 'wait_dialog' means that popup when the page has no element by that id.
     */
    _SwalWaitIsOpen: function () {
        if (!window.Swal || !Swal.isVisible()) {
            return false;
        }
        const popup = Swal.getPopup();
        if (popup && popup.classList.contains('qd-swal-wait')) {
            return true;
        }
        // copies of swal_wait.php published before the qd-swal-wait class was added
        const html = Swal.getHtmlContainer();
        return !!(html && html.querySelector('img[src*="ajax-loader"]'));
    },
    _IsSwalWait: function (dialog_id) {
        return dialog_id === 'wait_dialog' && !document.getElementById('wait_dialog');
    },
    CloseDialogIfOpen: function (dialog_id) {
        if (QuickDRY._IsSwalWait(dialog_id)) {
            // only ever close the wait popup, never a notice or confirm that replaced it
            if (QuickDRY._SwalWaitIsOpen()) {
                Swal.close();
            }
            return;
        }
        if (QuickDRY.DialogIsOpen(dialog_id)) {
            if ($("#" + dialog_id).hasClass("ui-dialog-content")) {
                $('#' + dialog_id).dialog('close');
            } else {
                $('#' + dialog_id).modal('hide');
            }
        }
    },
    /**
     *
     * @param dialog_id
     * @returns {*}
     * @constructor
     */
    DialogIsOpen: function (dialog_id) {
        if (QuickDRY._IsSwalWait(dialog_id)) {
            return QuickDRY._SwalWaitIsOpen();
        }

        const elem = $("#" + dialog_id);

        // Bootstrap 5: modal is open if it has `.show` and is visible
        if (elem.hasClass('show') && elem.is(':visible')) {
            return true;
        }

        // jQuery UI dialog support (if you still use it somewhere else)
        if (elem.hasClass("ui-dialog-content") && typeof elem.dialog === "function") {
            return elem.dialog("isOpen") === true;
        }

        return false;
    },

    ShowModal: function (elem_id, title) {
        // Update the modal title
        $('#' + elem_id + '_title').html(title);

        // Get the raw DOM element from jQuery
        const modalEl = $('#' + elem_id)[0];
        if (!modalEl) return;

        // Use Bootstrap 5's native API
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    },

    AutoComplete: function (elem_id, form_id, source_url, select_function) {
        let _elem_id = $('#' + elem_id);

        _elem_id.autocomplete({
            source: source_url,
            minLength: 1,
            html: true,
            select: select_function
        });
        if (form_id) {
            _elem_id.autocomplete("option", "appendTo", "#" + form_id);
        }
    },


    LookupObject: function (url, input_elem_id, output_elem_id, form_id) {
        let _input_elem_id = $('#' + input_elem_id);

        _input_elem_id.autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: url,
                    dataType: 'json',
                    data: {term: request.term, verb: 'FIND'},
                    success: function (data) {
                        response(data);
                    }
                });
            },
            select: function (event, ui) {
                $('#' + output_elem_id).val(ui.item.id);
                $(this).val(ui.item.display);
                return false;
            }
        }).data("ui-autocomplete")._renderItem = function (ul, item) {
            return $("<li>")
                .data("ui-autocomplete-item", item)
                .append("" + item.display + "")
                .appendTo(ul);
        };
        if (form_id) {
            _input_elem_id.autocomplete("option", "appendTo", "#" + form_id);
        }
    },
    LoadHTML: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error !== undefined)
                HTTP.Notice('Error', data.error);
            else {
                $('#' + elem_id).html(data.html);
                if (typeof (callback) === "function") {
                    callback(data);
                }
            }
            data = null;
            QuickDRY.CloseDialogIfOpen('wait_dialog');
        }, "json");
    },

    ReplaceHTML: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error !== undefined)
                HTTP.Notice('Error', data.error);
            else {
                $('#' + elem_id).replaceWith(data.html);
                if (typeof (callback) === "function") {
                    callback(data);
                }
            }
        }, "json");
    },

    AppendHTML: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error !== undefined)
                HTTP.Notice('Error', data.error);
            else {
                $('#' + elem_id).append(data.html);
                if (typeof (callback) === "function") {
                    callback(data);
                }
            }
        }, "json");
    },

    AddTableRow: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error !== undefined)
                HTTP.Notice('Error', data.error);
            else {
                $('#' + elem_id + ' > tbody:last').append(data.html);
                if (typeof (callback) === "function") {
                    callback(data);
                }
            }
        }, "json");
    }
};

// Fix for multiple modals hiding scrollbars
$(document).on('hidden.bs.modal', '.modal', function () {
    $('.modal:visible').length && $(document.body).addClass('modal-open');
});

// http://stackoverflow.com/questions/19305821/multiple-modals-overlay
$(document).on('show.bs.modal', '.modal', function () {
    let zIndex = 1040 + (10 * $('.modal:visible').length);
    $(this).css('z-index', zIndex);
    setTimeout(function () {
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    }, 0);
});

// SweetAlert popups (controls/SWAL) must sit above Bootstrap modals, which the code above stacks
// from z-index 1040 upward.
$(function () {
    $('<style>.swal2-container { z-index: 2000 !important; }</style>').appendTo('head');
});

// A Bootstrap modal traps focus: when a SweetAlert opens over a form, Bootstrap pulls focus back
// into the form, so Enter/Escape act on the form instead of the popup. Keep focus events inside
// SweetAlert from reaching Bootstrap's document-level focus trap.
window.addEventListener('focusin', function (e) {
    if (e.target && e.target.closest && e.target.closest('.swal2-container')) {
        e.stopPropagation();
    }
}, true);
