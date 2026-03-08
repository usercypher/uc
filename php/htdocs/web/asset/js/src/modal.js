(window.init = window.init || []).push(function () {
    var body = window.document.body;
    body.setAttribute("x-ref-root", "");
    body.setAttribute("x-evt-keyup.escape.window", "");

    ElX.src("modal-open", function (el) {
        var ref = el.getAttribute("data-ref");

        el.setAttribute("aria-haspopup", "dialog");
        el.setAttribute("aria-controls", "modal-content-" + ref);
        el.setAttribute("aria-expanded", "false");

        el.setAttribute("x-ref-modal-open-" + ref, "");
        el.setAttribute("x-evt-click", "");
        el.setAttribute("x-rot-modal-" + ref, "active");
        el.setAttribute("x-set-root.x-run-modal-close-" + ref, "click");
        el.setAttribute("x-set-this.aria-expanded", "true");
        el.setAttribute("x-tab", "modal-tab-start-" + ref + " " + "modal-tab-end-" + ref);
        el.setAttribute("x-focus", "modal-tab-start-" + ref);
    });

    ElX.src("modal-close", function (el) {
        var ref = el.getAttribute("data-ref");

        el.setAttribute("aria-label", "close modal");

        el.setAttribute("x-ref-modal-close-" + ref, "");
        el.setAttribute("x-evt-click", "");
        el.setAttribute("x-rot-modal-" + ref, "_");
        el.setAttribute("x-set-root.x-run-modal-close-" + ref, "null");
        el.setAttribute("x-set-modal-open-" + ref + ".aria-expanded", "false");
        el.setAttribute("x-tab", "");
        el.setAttribute("x-focus", "modal-open-" + ref);
    });

    ElX.src("modal", function (el) {
        var ref = el.getAttribute("data-ref");

        el.setAttribute("x-ref-modal-" + ref, "");
    });

    ElX.src("modal-content", function (el) {
        var ref = el.getAttribute("data-ref", "");

        el.id = "modal-content-" + ref;
        
        el.setAttribute("role", "dialog");
        el.setAttribute("aria-modal", "true");
        el.setAttribute("aria-labelledby", "modal-label-" + ref);
        el.setAttribute("aria-describedby", "modal-description-" + ref);

        el.setAttribute("x-evt-click.stop", "");
    });

    ElX.src("modal-label", function (el) {
        var ref = el.getAttribute("data-ref");

        el.id = "modal-label-" + ref;
    });

    ElX.src("modal-description", function (el) {
        var ref = el.getAttribute("data-ref");

        el.id = "modal-description-" + ref;
    });

    ElX.src("modal-tab-start", function (el) {
        var ref = el.getAttribute("data-ref");

        el.setAttribute("x-ref-modal-tab-start-" + ref, "");
    });

    ElX.src("modal-tab-end", function (el) {
        var ref = el.getAttribute("data-ref");

        el.setAttribute("x-ref-modal-tab-end-" + ref, "");
    });
});