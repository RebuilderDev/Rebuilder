(function (window, document) {
    'use strict';

    function restoreSavedState() {
        var fields = document.querySelectorAll('[data-rb-saved-checked]');
        var i;

        for (i = 0; i < fields.length; i++) {
            if (fields[i].getAttribute('data-rb-consent-dirty') === '1') {
                continue;
            }

            var checked = fields[i].getAttribute('data-rb-saved-checked') === '1';
            fields[i].defaultChecked = checked;
            fields[i].checked = checked;
        }
    }

    function scheduleRestore() {
        restoreSavedState();
        window.setTimeout(restoreSavedState, 0);
        window.setTimeout(restoreSavedState, 50);
        window.setTimeout(restoreSavedState, 250);
        window.setTimeout(restoreSavedState, 750);
    }

    function markChanged(event) {
        var target = event.target || event.srcElement;
        if (target && target.getAttribute && target.getAttribute('data-rb-saved-checked') !== null) {
            target.setAttribute('data-rb-consent-dirty', '1');
        }
    }

    if (document.addEventListener) {
        document.addEventListener('change', markChanged, true);
        document.addEventListener('DOMContentLoaded', scheduleRestore, false);
        window.addEventListener('load', scheduleRestore, false);
        window.addEventListener('pageshow', scheduleRestore, false);
    } else if (document.attachEvent) {
        document.attachEvent('onchange', markChanged);
        window.attachEvent('onload', scheduleRestore);
    }

    window.rbRestoreMemberConsentState = scheduleRestore;
}(window, document));
