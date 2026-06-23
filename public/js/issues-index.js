/*
 * Issues index — debounced AJAX search + filtering.
 *
 * Progressive enhancement over the server-rendered GET filter form: typing in
 * the search box (or changing a filter) fetches the results partial and swaps
 * it in place, with no full-page reload. Without JavaScript the form still
 * submits and reloads the page normally.
 *
 * CSP-safe: external same-origin file, no inline handlers. The swapped-in HTML
 * is our own Blade-rendered, auto-escaped partial; it is parsed and inserted as
 * nodes (never assigned through innerHTML) and contains no scripts.
 */
(function () {
    "use strict";

    var form = document.querySelector("[data-issues-filters]");
    var results = document.querySelector("[data-issues-results]");

    if (!form || !results) {
        return;
    }

    var searchInput = form.querySelector("[data-issues-search]");
    var clearButton = form.querySelector("[data-issues-clear]");
    var loading = document.querySelector("[data-issues-loading]");
    var errorRegion = document.querySelector("[data-issues-error]");

    var DEBOUNCE_MS = 300;

    function debounce(fn, delay) {
        var timer = null;

        return function () {
            var args = arguments;

            if (timer) {
                window.clearTimeout(timer);
            }

            timer = window.setTimeout(function () {
                fn.apply(null, args);
            }, delay);
        };
    }

    function toggleClearButton() {
        if (clearButton && searchInput) {
            clearButton.hidden = searchInput.value.trim() === "";
        }
    }

    function setBusy(isBusy) {
        if (loading) {
            loading.hidden = !isBusy;
        }
        results.setAttribute("aria-busy", isBusy ? "true" : "false");
    }

    function setError(visible) {
        if (errorRegion) {
            errorRegion.hidden = !visible;
        }
    }

    function buildUrl() {
        var raw = new URLSearchParams(new FormData(form));
        var params = new URLSearchParams();

        // Drop empty values so the URL (and the request) stays clean.
        raw.forEach(function (value, key) {
            if (value !== "") {
                params.append(key, value);
            }
        });

        var query = params.toString();

        return form.getAttribute("action") + (query ? "?" + query : "");
    }

    function render(html) {
        var parsed = new DOMParser().parseFromString(html, "text/html");
        var nodes = Array.prototype.slice.call(parsed.body.childNodes);

        results.replaceChildren.apply(results, nodes);
    }

    function load(url) {
        setError(false);
        setBusy(true);

        window
            .fetch(url, {
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    Accept: "text/html"
                },
                credentials: "same-origin"
            })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Request failed");
                }

                return response.text();
            })
            .then(function (html) {
                render(html);
                // Keep the address bar in sync so the result is shareable and
                // the back button restores the same query server-side.
                window.history.replaceState({}, "", url);
            })
            .catch(function () {
                // Leave the current results untouched and surface the failure.
                setError(true);
            })
            .finally(function () {
                setBusy(false);
            });
    }

    var debouncedLoad = debounce(function () {
        load(buildUrl());
    }, DEBOUNCE_MS);

    if (searchInput) {
        searchInput.addEventListener("input", function () {
            toggleClearButton();
            debouncedLoad();
        });
    }

    // Filter selects apply immediately — a discrete pick needs no debounce.
    Array.prototype.forEach.call(form.querySelectorAll("select"), function (select) {
        select.addEventListener("change", function () {
            load(buildUrl());
        });
    });

    if (clearButton && searchInput) {
        clearButton.addEventListener("click", function () {
            searchInput.value = "";
            toggleClearButton();
            load(buildUrl());
            searchInput.focus();
        });
    }

    // Enter in the search box searches in place rather than reloading the page.
    form.addEventListener("submit", function (event) {
        event.preventDefault();
        load(buildUrl());
    });

    // Pagination links inside the swapped results fetch in place too (event
    // delegation, because the links are replaced on every load).
    results.addEventListener("click", function (event) {
        var link = event.target.closest("a.pagination__link");

        if (link && link.getAttribute("href")) {
            event.preventDefault();
            load(link.getAttribute("href"));
        }
    });

    toggleClearButton();
})();
