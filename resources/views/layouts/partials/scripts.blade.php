<script>
    function confirmDeletion(event, title, body, onConfirmFun) {
        // Generic confirmation using SweetAlert2.
        // Works for forms and links. Prevents default and handles confirm flow.
        event = event || window.event;
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        var target = event && (event.target || event.srcElement);
        var currentTarget = event && event.currentTarget ? event.currentTarget : target;
        var form = currentTarget && currentTarget.closest ? currentTarget.closest('form') : null;
        var href = currentTarget && currentTarget.getAttribute ? currentTarget.getAttribute('href') : null;

        var hasSwal = typeof window !== 'undefined' && window.Swal && typeof window.Swal.fire === 'function';

        var doConfirm = function() {
            if (typeof onConfirmFun === 'function') {
                try {
                    onConfirmFun(currentTarget);
                } catch (e) {
                    /* no-op */
                }
                return false;
            }
            // Default behaviors
            if (form && typeof form.submit === 'function') {
                form.submit();
                return false;
            }
            if (href && href !== '#') {
                window.location.href = href;
                return false;
            }
            return false;
        };

        var dialogTitle = title || 'Êtes-vous sûr ?';
        var dialogText = body || "Cette action est irréversible.";

        if (hasSwal) {
            window.Swal.fire({
                title: dialogTitle,
                text: dialogText,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Confirmer',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#d33',

                reverseButtons: true,
                focusCancel: true,
            }).then(function(result) {
                if (result && result.isConfirmed) {
                    doConfirm();
                }
            });
        } else {
            // Fallback to native confirm
            if (window.confirm(dialogTitle + '\n' + dialogText)) {
                doConfirm();
            }
        }

        // Always return false to stop default submit/navigation; we'll handle it in doConfirm
        return false;
    }
</script>
