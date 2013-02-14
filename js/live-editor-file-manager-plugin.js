var liveEditorFileManagerPlugin = {
  insertTinyMceContent: function(data) {
    // Monkey-patched code so this can run in Internet Explorer. Otherwise, it throws a `SCRIPT5: Access is denied` error.
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

    // When `selection.getRng()` bug is fixed in TinyMCE, the monkey-patched code above this can be removed or only
    // served for old versions of WordPress
    if (tinymce.activeEditor.isHidden()) {
      jQuery("#" + window.wpActiveEditor).insertAtCaret(data);
    }
    else {
      tinymce.activeEditor.execCommand("mceInsertContent", false, data);
    }
  }
};
