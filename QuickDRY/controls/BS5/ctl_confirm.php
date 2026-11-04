<script type="text/javascript" src="/QuickDRY/controls/ctl_confirm.js?<?php echo JS_VERSION; ?>"></script>

<div class="modal fade" id="cc_dialog" tabindex="-1" aria-labelledby="cc_dialog_title" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cc_dialog_title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h3><span id="cc_text"></span></h3>
            </div>
            <div class="modal-footer">
                <span style="white-space: nowrap;">
                    <button type="button" class="btn btn-primary cc_action_button"
                            onclick="ConfirmDialogControl.Confirm();">Yes</button>
                    <button type="button" class="btn btn-secondary cc_cancel_button" data-bs-dismiss="modal">Cancel</button>
                </span>
            </div>
        </div>
    </div>
</div>