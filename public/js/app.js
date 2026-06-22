/*
 * Mini Issue Tracker — progressive enhancement.
 *
 * Served as a static same-origin asset and compatible with the strict
 * Content-Security-Policy (script-src 'self'); there are no inline handlers.
 *
 * Adds a confirmation prompt to destructive forms. If JavaScript is disabled
 * the form still submits normally, so the feature degrades gracefully.
 */
(function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {
        var forms = document.querySelectorAll("form[data-confirm]");

        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                var message = form.getAttribute("data-confirm");

                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    });
})();
