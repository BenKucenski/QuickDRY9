<script>
    function NoticeDialog(title, text) {
        Swal.fire({
            title: title,
            html: `<h3>${text}</h3>`,
            showConfirmButton: true,
            confirmButtonText: 'Close',
            allowOutsideClick: true,
            allowEscapeKey: true,
        });
    }
</script>