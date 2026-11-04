<script type="text/javascript" src="/QuickDRY/controls/ctl_confirm.js?<?php echo JS_VERSION; ?>"></script>

<div class="modal fade" id="cc_dialog" style="display: none;" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cc_dialog_title"></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <h3><span id="cc_text"></span></h3>

            </div>
            <div class="modal-footer">
                <span style="white-space: nowrap;">

                <button type="button" class="btn btn-primary cc_action_button"
                        onclick="ConfirmDialogControl.Confirm();">Yes</button>
                <button type="button" class="btn btn-default cc_cancel_button" data-dismiss="modal">Cancel</button>
                </span>
            </div>
        </div>
    </div>
</div>