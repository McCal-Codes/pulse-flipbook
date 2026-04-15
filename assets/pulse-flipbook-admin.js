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
        if (mime !== "application/pdf") {
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
  });
})(jQuery);
