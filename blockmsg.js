function considerChangingExpiryFocus() {
        if (!document.getElementById) {
                return;
        }
        var drop = document.getElementById('wpBlockExpiry');
        if (!drop) {
                return;
        }
        var field = document.getElementById('wpBlockOther');
        if (!field) {
                return;
        }
        var opt = drop.value;
        if (opt == 'other') {
                field.style.display = '';
        } else {
                field.style.display = 'none';
        }
}
function considerChangingReasonFocus() {
        if (!document.getElementById) {
                return;
        }
        var drop = document.getElementById('wpBlockReasonList');
        if (!drop) {
                return;
        }
        var field = document.getElementById('wpBlockReason');
        if (!field) {
                return;
        }
        var opt = drop.value;
        if (opt == 'other') {
                field.style.display = '';
        } else {
                field.style.display = 'none';
        }
}
