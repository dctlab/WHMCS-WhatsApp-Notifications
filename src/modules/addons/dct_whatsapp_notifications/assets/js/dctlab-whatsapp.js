/**
 * DCTLAB WhatsApp - UI Foundation JS (Phase 1)
 *
 * Scope deliberately limited to Section 13 of the Phase 1 brief:
 * loading-button state helper, copy-to-clipboard helper, dismissible
 * alerts. No page-specific AJAX, messaging, or API logic here - that
 * belongs to each page's own phase.
 *
 * WHMCS admin already loads jQuery globally (confirmed via this module's
 * existing templates using $(...) elsewhere) and Bootstrap 3's own JS
 * already handles .dropdown-toggle/.navbar-toggle - this file does not
 * duplicate or replace either.
 *
 * @since 5.2.0 (Phase 1 - UI Foundation)
 */
(function ($) {
    "use strict";

    if (typeof $ === "undefined") {
        return;
    }

    /**
     * Loading button helper.
     * Usage: $('#myButton').dctButtonLoading(true|false)
     */
    $.fn.dctButtonLoading = function (isLoading) {
        return this.each(function () {
            var $btn = $(this);

            if (isLoading) {
                if (!$btn.data("dct-original-disabled")) {
                    $btn.data("dct-original-disabled", $btn.prop("disabled") ? "1" : "0");
                }
                $btn.addClass("dct-button-loading").prop("disabled", true);
            } else {
                $btn.removeClass("dct-button-loading");
                $btn.prop("disabled", $btn.data("dct-original-disabled") === "1");
            }
        });
    };

    /**
     * Copy-to-clipboard helper for any element with [data-dct-copy="text"].
     * Shows a brief "Copied" state on the trigger element itself.
     */
    $(document).on("click", "[data-dct-copy]", function (e) {
        e.preventDefault();

        var $el = $(this);
        var text = $el.attr("data-dct-copy") || "";

        if (!text || !navigator.clipboard) {
            return;
        }

        navigator.clipboard.writeText(text).then(function () {
            var original = $el.attr("data-dct-copy-label") || $el.text();

            $el.text("Copied");

            setTimeout(function () {
                $el.text(original);
            }, 1200);
        });
    });

    /**
     * Dismissible alerts: any .dct-alert with a [data-dct-dismiss] child
     * fades out and removes itself on click.
     */
    $(document).on("click", "[data-dct-dismiss]", function () {
        $(this).closest(".dct-alert").fadeOut(150, function () {
            $(this).remove();
        });
    });
})(typeof jQuery !== "undefined" ? jQuery : undefined);
