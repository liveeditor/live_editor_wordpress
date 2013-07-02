var liveEditorFileManagerPlugin = {
  insertTinyMceContent: function(data) {
    // Insert content into WYSIWYG editor if it's active and visible
    if (tinymce.activeEditor && !tinymce.activeEditor.isHidden()) {
      // When `selection.getRng()` bug is fixed in TinyMCE, this monkey-patched code can be removed or only targeted at certain WordPress versions.
      // Without this monkey patch, Internet Explorer throws a `SCRIPT5: Access is denied` error for the `tinymce.activeEditor.execCommand()` call below.
      // @see http://www.tinymce.com/develop/bugtracker_view.php?id=5694
      // @see https://github.com/tinymce/tinymce/pull/122/files
      tinymce.activeEditor.selection.getRng = function(w3c) {
        var t = this, s, r, elm, doc = t.win.document;

        // Found tridentSel object then we need to use that one
        if (w3c && t.tridentSel)
          return t.tridentSel.getRangeAt(0);

        try {
          if (s = t.getSel())
            r = s.rangeCount > 0 ? s.getRangeAt(0) : (s.createRange ? s.createRange() : doc.createRange());
        } catch (ex) {
          // IE throws unspecified error here if TinyMCE is placed in a frame/iframe
        }

        try {
          // We have W3C ranges and it's IE then fake control selection since IE9 doesn't handle that correctly yet
          if (tinymce.isIE
            && r && r.setStart
            /*
             * IE throws exception "Access denied" on hidden elements
             * and sometimes if selection.type is "None"
             * @see http://msdn.microsoft.com/en-us/library/ie/hh772128(v=vs.85).aspx
             * @see http://msdn.microsoft.com/en-us/library/ie/hh801959(v=vs.85).aspx
             */
            && doc.selection && doc.selection.type !== 'None'
            && doc.selection.createRange().item
          ) {
            elm = doc.selection.createRange().item(0);
            r = doc.createRange();
            r.setStartBefore(elm);
            r.setEndAfter(elm);
          }
        } catch(e) {
          // Access denied exception
        }

        // No range found then create an empty one
        // This can occur when the editor is placed in a hidden container element on Gecko
        // Or on IE when there was an exception
        if (!r)
          r = doc.createRange ? doc.createRange() : doc.body.createTextRange();

        if (t.selectedRange && t.explicitRange) {
          if (r.compareBoundaryPoints(r.START_TO_START, t.selectedRange) === 0 && r.compareBoundaryPoints(r.END_TO_END, t.selectedRange) === 0) {
            // Safari, Opera and Chrome only ever select text which causes the range to change.
            // This lets us use the originally set range if the selection hasn't been changed by the user.
            r = t.explicitRange;
          } else {
            t.selectedRange = null;
            t.explicitRange = null;
          }
        }

        return r;
      };

      tinymce.activeEditor.execCommand("mceInsertContent", false, data);
    }
    // Insert content into raw HTML "Text" field if the Visual editor is hidden
    else {
      jQuery("#" + window.wpActiveEditor).insertAtCaret(data);
    }
  }
};

jQuery(function() {
  //-----------------------------------------------------------------
  // Functions/variables

  // Reusable functions
  var
    toggleMediaRouterContent = function() {
      jQuery("#cboxLoadedContent div.media-router a").each(function() {
        var $this = jQuery(this);

        if ($this.hasClass("active")) {
          jQuery("#cboxLoadedContent " + $this.attr("href")).show();
        }
        else {
          jQuery("#cboxLoadedContent " + $this.attr("href")).hide();
        }
      });
    },

    // Reusable variables/DOM queries
    media_button = jQuery("a.insert-live-editor-media"),
    media_link = jQuery("#live-editor-file-manager-add-media-link"),
    body = jQuery("body");


  //-----------------------------------------------------------------
  // Modal window

  media_button.colorbox({
    href: media_button.attr("href") + "?action=resources&_ajax_nonce=" + body.attr("data-live-editor-nonce-resources") + "&post_type=" + body.attr("data-live-editor-post-type") + "&wp_source=" + encodeURIComponent(body.attr("data-live-editor-target-url")),
    fixed: true,
    height: "93%",
    width: "95%"
  });

  // Clicking links within lightbox
  jQuery(document).on("click", "#cboxLoadedContent a:not(.modal-ignore)", function(e) {
    var $this = jQuery(this);

    jQuery.ajax({
      url: $this.attr("href") + "&_ajax_nonce=" + body.attr("data-live-editor-nonce-" + $this.attr("data-nonce-name")) + "&post_type=" + body.attr("data-live-editor-post-type") + "&wp_source=" + encodeURIComponent(body.attr("data-live-editor-target-url")) + ($this.attr("data-previewable") ? "&previewable=" + $this.attr("data-previewable") : ""),
      type: "get",
      cache: false,
      success: function(data, status, xhr) {
        var loaded_content = jQuery("#cboxLoadedContent");

        loaded_content.html(jQuery(data));
        loaded_content.scrollTop(0);
        jQuery(document).trigger({
          type: "cbox_complete"
        });
      },
      error: function(jqXHR, textStatus, errorThrown) {
        alert("There was an error loading the link you clicked. Please try reloading the page and try again.");
      }
    });

    e.preventDefault();
  });

  // Submitting forms within lightbox
  jQuery(document).on("submit", "#cboxLoadedContent form:not(.modal-ignore)", function(e) {
    var $this = jQuery(this);

    jQuery.ajax({
      url: $this.attr("action"),
      type: "post",
      data: $this.serialize(),
      cache: false,
      success: function(data, status, xhr) {
        var loaded_content = jQuery("#cboxLoadedContent");

        loaded_content.html(jQuery(data));
        loaded_content.scrollTop(0);
        jQuery(document).trigger({
          type: "cbox_complete"
        });
      },
      error: function(jqXHR, textStatus, errorThrown) {
        alert("There was an error loading the form submission. Please try reloading the page and try again.");
      }
    });

    e.preventDefault();
  });

  // Attachment browser pagination
  jQuery(document).on("click", "#cboxLoadedContent div.attachments-browser span.pagination-links a.first-page", function(e) {
    jQuery("#current-page").val(1);
    jQuery("#files-form").submit();
    e.preventDefault();
  });

  jQuery(document).on("click", "#cboxLoadedContent div.attachments-browser span.pagination-links a.prev-page", function(e) {
    var $this = jQuery(this),
        current_page = jQuery("#current-page");

    if (current_page.val() > 1) {
      current_page.val(parseInt(current_page.val()) - 1);
    }

    jQuery("#files-form").submit();
    
    e.preventDefault();
  });

  jQuery(document).on("click", "#cboxLoadedContent div.attachments-browser span.pagination-links a.next-page", function(e) {
    var $this = jQuery(this),
        current_page = jQuery("#current-page"),
        total_pages = jQuery("#total-pages").val();

    if (current_page.val() < total_pages) {
      current_page.val(parseInt(current_page.val()) + 1);
    }

    jQuery("#files-form").submit();

    e.preventDefault();
  });

  jQuery(document).on("click", "#cboxLoadedContent div.attachments-browser span.pagination-links a.last-page", function(e) {
    var $this = jQuery(this),
        current_page = jQuery("#current-page"),
        total_pages = jQuery("#total-pages").val();

    current_page.val(total_pages);
    jQuery("#files-form").submit();

    e.preventDefault();
  });

  // Insert into post link
  jQuery(document).on("click", "#cboxLoadedContent a.insert-file", function(e) {
    var $this = jQuery(this),
        file_id = $this.attr("data-file-id");

    jQuery.ajax({
      type: "post",
      url: $this.attr("href"),
      data: {
        action: "editor_code",
        resource_id: $this.attr("data-file-id"),
        _ajax_nonce: body.attr("data-live-editor-nonce-editor_code"),
        post_type: body.attr("data-live-editor-post-type")
      },
      success: function(data, textStatus, jqXHR) {
        if (typeof tinymce === "undefined") {
          jQuery("#content").insertAtCaret(String(data));
        }
        else {
          var my_data = String(data);
          window.liveEditorFileManagerPlugin.insertTinyMceContent(my_data);
        }
        jQuery("#cboxClose").click();
      },
      error: function(jqXHR, textStatus, errorThrown) {
        alert("There was an error retrieving the code to add to your content.");
      }
    });

    e.preventDefault();
  });


  //-----------------------------------------------------------------
  // Receive resource requests from Live Editor domain

  if (media_link.length) {
    // e.data passed to this closure will contain ID of resource selected
    jQuery.receiveMessage(
      function(e) {
        // Response will be an ID for the Live Editor resource
        var id = e.data;

        jQuery.ajax({
          type: "post",
          url: media_link.attr("href"),
          data: {
            action: "editor_code",
            resource_id: id,
            _ajax_nonce: body.attr("data-live-editor-nonce-editor_code")
          },
          success: function(data, textStatus, jqXHR) {
            var code = String(data);

            if (typeof tinymce === "undefined") {
              jQuery("#content").insertAtCaret(code);
            }
            else {
              window.liveEditorFileManagerPlugin.insertTinyMceContent(code);
            }
            
            jQuery("#cboxClose").click();
          },
          error: function(jqXHR, textStatus, errorThrown) {
            alert("There was an error retrieving the code to add to your content.");
          }
        });
      },
      body.attr("data-live-editor-target-domain")
    );
  }


  //-----------------------------------------------------------------
  // File uploader tabs

  // Init file uploader tabs
  jQuery(document).bind('cbox_complete', function() {
    if (jQuery("#cboxLoadedContent div.media-router").length) {
      toggleMediaRouterContent();
    }
  });

  // Click file uploader tabs
  jQuery(document).on("click", "#cboxLoadedContent div.media-router a", function(e) {
    // Handle tabs
    jQuery("#cboxLoadedContent div.media-router a").removeClass("active");
    jQuery(this).addClass("active");
    toggleMediaRouterContent();

    e.preventDefault();
  });
});
