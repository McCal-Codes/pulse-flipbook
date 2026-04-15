(function (blocks, element, i18n, blockEditor, components) {
  const registerBlockType = blocks.registerBlockType;
  const createElement = element.createElement;
  const __ = i18n.__;
  const useBlockProps = blockEditor.useBlockProps;
  const InspectorControls = blockEditor.InspectorControls;
  const PanelBody = components.PanelBody;
  const RangeControl = components.RangeControl;
  const ToggleControl = components.ToggleControl;
  const Notice = components.Notice;

  registerBlockType("pulse/issue-flipbook", {
    title: __("Issue Flipbook", "pulse-flipbook"),
    description: __("Render the issue PDF reader for the current Issue.", "pulse-flipbook"),
    icon: "book",
    category: "widgets",
    usesContext: ["postId", "postType"],
    supports: {
      html: false,
    },
    attributes: {
      issueId: {
        type: "number",
        default: 0,
      },
      viewerHeight: {
        type: "number",
        default: 760,
      },
      showDownload: {
        type: "boolean",
        default: true,
      },
    },
    edit: function (props) {
      const attrs = props.attributes;
      const setAttributes = props.setAttributes;
      const blockProps = useBlockProps({
        className: "pulse-flipbook-editor-placeholder",
      });
      const contextPostType = props.context && props.context.postType ? props.context.postType : "";
      const contextPostId = props.context && props.context.postId ? props.context.postId : 0;
      const issueId = attrs.issueId || contextPostId;

      return createElement(
        "div",
        blockProps,
        createElement(
          InspectorControls,
          null,
          createElement(
            PanelBody,
            {
              title: __("Flipbook settings", "pulse-flipbook"),
              initialOpen: true,
            },
            createElement(RangeControl, {
              label: __("Viewer height (px)", "pulse-flipbook"),
              min: 480,
              max: 1200,
              step: 10,
              value: attrs.viewerHeight || 760,
              onChange: function (value) {
                setAttributes({ viewerHeight: value || 760 });
              },
            }),
            createElement(ToggleControl, {
              label: __("Show download button", "pulse-flipbook"),
              checked: !!attrs.showDownload,
              onChange: function (value) {
                setAttributes({ showDownload: !!value });
              },
            })
          )
        ),
        contextPostType !== "issue"
          ? createElement(Notice, { status: "warning", isDismissible: false }, __("Place this block on an Issue template or Issue post.", "pulse-flipbook"))
          : null,
        createElement("p", null, __("Issue Flipbook block", "pulse-flipbook")),
        createElement(
          "p",
          null,
          issueId
            ? __("This block will render the PDF flipbook on the front end using the Issue PDF field.", "pulse-flipbook")
            : __("No issue context detected yet. Save and view on the front end.", "pulse-flipbook")
        )
      );
    },
    save: function () {
      return null;
    },
  });
})(window.wp.blocks, window.wp.element, window.wp.i18n, window.wp.blockEditor, window.wp.components);
