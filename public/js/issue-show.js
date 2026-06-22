/*
 * Mini Issue Tracker — issue detail page interactions.
 *
 * Loaded only on the issue show page. Provides two AJAX features with no full
 * page reload:
 *   1. Attach / detach tags (POST / DELETE /issues/{issue}/tags/{tag}).
 *   2. Paginated comments thread + comment creation
 *      (GET / POST /issues/{issue}/comments).
 *
 * CSP-safe: same-origin fetch (connect-src falls back to default-src 'self'),
 * no inline handlers. All server-provided text is written with textContent —
 * never innerHTML — so a comment body or tag name cannot inject markup
 * (OWASP A03/XSS, cheatsheetseries.owasp.org). No console output.
 */
(function () {
    "use strict";

    var meta = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = meta ? meta.getAttribute("content") : "";

    /**
     * Same-origin JSON fetch with CSRF + AJAX signalling headers. Resolves to
     * { ok, status, data } where data is the parsed body (or null).
     */
    function jsonFetch(url, method, body) {
        var headers = {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": csrfToken
        };
        var options = { method: method, headers: headers, credentials: "same-origin" };

        if (body !== undefined) {
            headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(body);
        }

        return fetch(url, options).then(function (response) {
            return response
                .json()
                .catch(function () {
                    return null;
                })
                .then(function (data) {
                    return { ok: response.ok, status: response.status, data: data };
                });
        });
    }

    function show(element) {
        if (element) {
            element.hidden = false;
        }
    }

    function hide(element) {
        if (element) {
            element.hidden = true;
        }
    }

    function setText(element, text) {
        if (element) {
            element.textContent = text;
        }
    }

    /* --------------------------------------------------------------------- *
     * Tags: attach / detach
     * --------------------------------------------------------------------- */

    function initTags() {
        var manager = document.querySelector("[data-tag-manager]");
        if (!manager) {
            return;
        }

        var attachedList = manager.querySelector("[data-attached-list]");
        var availableList = manager.querySelector("[data-available-list]");
        var errorRegion = document.querySelector("[data-tag-error]");

        function removeEmptyPlaceholder(list) {
            var placeholder = list.querySelector("[data-empty]");
            if (placeholder) {
                placeholder.remove();
            }
        }

        function ensureEmptyPlaceholder(list, text) {
            if (list.querySelector(".tag-manager__item")) {
                return;
            }
            if (list.querySelector("[data-empty]")) {
                return;
            }
            var li = document.createElement("li");
            li.className = "muted";
            li.setAttribute("data-empty", "");
            li.textContent = text;
            list.appendChild(li);
        }

        function buildTagItem(tag, url, action) {
            var li = document.createElement("li");
            li.className = "tag-manager__item";
            li.setAttribute("data-tag-id", String(tag.id));
            li.setAttribute("data-tag-name", tag.name);
            li.setAttribute("data-tag-color", tag.color || "");
            li.setAttribute("data-tag-url", url);

            var chip = document.createElement("span");
            chip.className = "tag-chip";
            if (tag.color) {
                var dot = document.createElement("span");
                dot.className = "tag-chip__dot";
                dot.setAttribute("data-tag-color", tag.color);
                dot.setAttribute("aria-hidden", "true");
                chip.appendChild(dot);
            }
            chip.appendChild(document.createTextNode(" " + tag.name));

            var button = document.createElement("button");
            button.type = "button";
            button.className = "button button--small tag-manager__action";
            button.setAttribute("data-tag-action", action);
            button.textContent = action === "detach" ? "Detach" : "Attach";

            li.appendChild(chip);
            li.appendChild(button);

            if (window.MiniIssueTracker && window.MiniIssueTracker.paintTagColors) {
                window.MiniIssueTracker.paintTagColors(li);
            }

            return li;
        }

        manager.addEventListener("click", function (event) {
            var button = event.target.closest("[data-tag-action]");
            if (!button) {
                return;
            }

            var item = button.closest(".tag-manager__item");
            if (!item) {
                return;
            }

            var action = button.getAttribute("data-tag-action");
            var url = item.getAttribute("data-tag-url");
            var tag = {
                id: parseInt(item.getAttribute("data-tag-id"), 10),
                name: item.getAttribute("data-tag-name"),
                color: item.getAttribute("data-tag-color") || null
            };
            var method = action === "detach" ? "DELETE" : "POST";

            hide(errorRegion);
            button.disabled = true;

            jsonFetch(url, method)
                .then(function (result) {
                    if (!result.ok) {
                        button.disabled = false;
                        setText(errorRegion, "Could not update tags. Please try again.");
                        show(errorRegion);
                        return;
                    }

                    item.remove();

                    if (action === "attach") {
                        removeEmptyPlaceholder(attachedList);
                        attachedList.appendChild(buildTagItem(tag, url, "detach"));
                        ensureEmptyPlaceholder(availableList, "All tags are attached.");
                    } else {
                        removeEmptyPlaceholder(availableList);
                        availableList.appendChild(buildTagItem(tag, url, "attach"));
                        ensureEmptyPlaceholder(attachedList, "No tags attached.");
                    }
                })
                .catch(function () {
                    button.disabled = false;
                    setText(errorRegion, "Network error. Please check your connection and try again.");
                    show(errorRegion);
                });
        });
    }

    /* --------------------------------------------------------------------- *
     * Comments: paginated load + create
     * --------------------------------------------------------------------- */

    function initComments() {
        var section = document.querySelector("[data-comments-section]");
        if (!section) {
            return;
        }

        var commentsUrl = section.getAttribute("data-comments-url");
        var container = section.querySelector("[data-comments]");
        var loading = section.querySelector("[data-comments-loading]");
        var list = section.querySelector("[data-comments-list]");
        var emptyMessage = section.querySelector("[data-comments-empty]");
        var listError = section.querySelector("[data-comments-error]");

        var pagination = section.querySelector("[data-comments-pagination]");
        var prevButton = section.querySelector("[data-comments-prev]");
        var nextButton = section.querySelector("[data-comments-next]");
        var status = section.querySelector("[data-comments-status]");

        var form = section.querySelector("[data-comment-form]");
        var submitButton = section.querySelector("[data-comment-submit]");
        var successMessage = section.querySelector("[data-comment-success]");
        var formError = section.querySelector("[data-comment-form-error]");

        var currentPage = 1;
        var submitting = false;

        function buildCommentElement(comment) {
            var li = document.createElement("li");
            li.className = "comments__item";

            var metaRow = document.createElement("div");
            metaRow.className = "comments__meta";

            var author = document.createElement("span");
            author.className = "comments__author";
            author.textContent = comment.author_name;

            var time = document.createElement("time");
            time.className = "comments__time";
            if (comment.created_at) {
                time.setAttribute("datetime", comment.created_at);
            }
            time.textContent = comment.created_at_for_humans || "";

            metaRow.appendChild(author);
            metaRow.appendChild(time);

            var body = document.createElement("p");
            body.className = "comments__body";
            body.textContent = comment.body;

            li.appendChild(metaRow);
            li.appendChild(body);

            return li;
        }

        function renderList(comments) {
            list.textContent = "";
            comments.forEach(function (comment) {
                list.appendChild(buildCommentElement(comment));
            });
        }

        function updatePagination(meta) {
            if (!meta || meta.last_page <= 1) {
                hide(pagination);
                return;
            }

            show(pagination);
            setText(status, "Page " + meta.current_page + " of " + meta.last_page);
            prevButton.disabled = meta.current_page <= 1;
            nextButton.disabled = meta.current_page >= meta.last_page;
        }

        function loadComments(page) {
            var url = page > 1 ? commentsUrl + "?page=" + page : commentsUrl;

            container.setAttribute("aria-busy", "true");
            show(loading);
            hide(listError);

            jsonFetch(url, "GET")
                .then(function (result) {
                    hide(loading);
                    container.setAttribute("aria-busy", "false");

                    if (!result.ok || !result.data) {
                        setText(listError, "Could not load comments. Please try again.");
                        show(listError);
                        return;
                    }

                    var comments = result.data.data || [];
                    var meta = result.data.meta || {};

                    currentPage = meta.current_page || page;
                    renderList(comments);

                    if (comments.length === 0 && currentPage === 1) {
                        show(emptyMessage);
                    } else {
                        hide(emptyMessage);
                    }

                    updatePagination(meta);
                })
                .catch(function () {
                    hide(loading);
                    container.setAttribute("aria-busy", "false");
                    setText(listError, "Network error while loading comments.");
                    show(listError);
                });
        }

        function clearFieldErrors() {
            section.querySelectorAll("[data-error-for]").forEach(function (element) {
                setText(element, "");
                hide(element);
            });
            hide(formError);
        }

        function showFieldErrors(errors) {
            Object.keys(errors).forEach(function (field) {
                var element = section.querySelector('[data-error-for="' + field + '"]');
                if (element) {
                    setText(element, errors[field][0]);
                    show(element);
                }
            });
        }

        if (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();

                if (submitting) {
                    return;
                }

                clearFieldErrors();
                hide(successMessage);

                // Client-side guard for empty input. This is a UX nicety that
                // also avoids an unnecessary 422 round-trip (and the browser's
                // network error log) for the common empty-form case. It is NOT
                // a security control: the server still validates and returns
                // 422 JSON, which is handled inline below for any case that
                // reaches it. Trimming mirrors Laravel's TrimStrings + required.
                var clientErrors = {};
                if (form.elements.author_name.value.trim() === "") {
                    clientErrors.author_name = ["Please enter your name."];
                }
                if (form.elements.body.value.trim() === "") {
                    clientErrors.body = ["Please enter a comment."];
                }
                if (Object.keys(clientErrors).length > 0) {
                    showFieldErrors(clientErrors);
                    return;
                }

                submitting = true;
                submitButton.disabled = true;

                var payload = {
                    author_name: form.elements.author_name.value,
                    body: form.elements.body.value
                };

                jsonFetch(commentsUrl, "POST", payload)
                    .then(function (result) {
                        if (result.status === 201 && result.data && result.data.data) {
                            hide(emptyMessage);
                            list.insertBefore(buildCommentElement(result.data.data), list.firstChild);
                            form.reset();
                            setText(successMessage, "Comment added.");
                            show(successMessage);
                        } else if (result.status === 422 && result.data && result.data.errors) {
                            showFieldErrors(result.data.errors);
                        } else {
                            setText(formError, "Could not post your comment. Please try again.");
                            show(formError);
                        }
                    })
                    .catch(function () {
                        setText(formError, "Network error. Please check your connection and try again.");
                        show(formError);
                    })
                    .finally(function () {
                        submitting = false;
                        submitButton.disabled = false;
                    });
            });
        }

        if (prevButton) {
            prevButton.addEventListener("click", function () {
                if (currentPage > 1) {
                    loadComments(currentPage - 1);
                }
            });
        }

        if (nextButton) {
            nextButton.addEventListener("click", function () {
                loadComments(currentPage + 1);
            });
        }

        // Comments load automatically when the issue detail page opens.
        loadComments(1);
    }

    document.addEventListener("DOMContentLoaded", function () {
        initTags();
        initComments();
    });
})();
