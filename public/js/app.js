/*
 * Mini Issue Tracker — global progressive enhancement.
 *
 * Served as a static same-origin asset and compatible with the strict
 * Content-Security-Policy (script-src 'self'); there are no inline handlers.
 *
 * Two enhancements, both degrading gracefully when JavaScript is disabled:
 *   1. A confirmation prompt on destructive forms (form[data-confirm]).
 *   2. Tag colour swatches/dots are painted from their data-tag-color value.
 *      Colours are applied through the CSSOM (element.style), which — unlike
 *      inline style attributes — is not subject to the style-src CSP directive,
 *      so the strict policy holds with no console errors.
 */
(function () {
    "use strict";

    function enableDeleteConfirmations() {
        document.querySelectorAll("form[data-confirm]").forEach(function (form) {
            form.addEventListener("submit", function (event) {
                var message = form.getAttribute("data-confirm");

                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    }

    function paintTagColors(root) {
        var scope = root || document;

        scope.querySelectorAll("[data-tag-color]").forEach(function (element) {
            var color = element.getAttribute("data-tag-color");

            if (color) {
                // Property-scoped assignment: an invalid or hostile value is
                // simply ignored by the CSS parser; it cannot inject further
                // declarations.
                element.style.background = color;
            }
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        enableDeleteConfirmations();
        paintTagColors(document);
    });

    // Exposed so issue-show.js can repaint colours on tag chips it injects.
    window.MiniIssueTracker = window.MiniIssueTracker || {};
    window.MiniIssueTracker.paintTagColors = paintTagColors;
})();
