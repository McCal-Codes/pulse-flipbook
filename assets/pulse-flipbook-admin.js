(function ($) {
  $(function () {
    var $frame;
    var $input = $("#pulse_issue_pdf_attachment_id");
    var $label = $("#pulse-issue-pdf-filename");
    var $btn = $("#pulse-issue-pdf-select");
    var $clear = $("#pulse-issue-pdf-clear");

    $btn.on("click", function (e) {
      e.preventDefault();
      if ($frame) {
        $frame.open();
        return;
      }

      $frame = wp.media({
        title: pulseFlipbookAdmin.i18nTitle,
        button: { text: pulseFlipbookAdmin.i18nButton },
        multiple: false,
        library: { type: "application/pdf" },
      });

      $frame.on("select", function () {
        var attachment = $frame.state().get("selection").first().toJSON();
        if (!attachment || !attachment.id) {
          return;
        }
        var mime = attachment.mime || "";
        var filename = (attachment.filename || attachment.title || "").toLowerCase();
        var looksLikePdf =
          mime === "application/pdf" ||
          mime.endsWith("/pdf") ||
          mime.indexOf("pdf") !== -1 ||
          filename.endsWith(".pdf");
        if (!looksLikePdf) {
          window.alert(pulseFlipbookAdmin.i18nNotPdf);
          return;
        }
        $input.val(String(attachment.id));
        $label.text(attachment.filename || attachment.title || attachment.url);
      });

      $frame.open();
    });

    $clear.on("click", function (e) {
      e.preventDefault();
      $input.val("0");
      $label.text(pulseFlipbookAdmin.i18nNone);
    });

    if (typeof wp !== "undefined" && wp.media && wp.media.view && wp.media.view.settings && wp.media.view.settings.post) {
      var canUpload = !!wp.media.view.settings.post.nonce;
      if (!canUpload) {
        $btn.after('<p style="margin-top:8px;color:#b32d2e;">' + pulseFlipbookAdmin.i18nBadUploadPerms + "</p>");
      }
    }
  });
})(jQuery);
