$(function () {
    $('#notice_dialog').hide();
});

function NoticeDialog(title, text) {
    QuickDRY.ShowModal('notice_dialog', title);
    $('#ctl_notice_text').html(text);
}
