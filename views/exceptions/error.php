<?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/views/layouts/header.php" ?>
<?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/helpers/files_helper.php" ?>

<div class="media-frame wp-core-ui hide-router">
  <div class="media-frame-menu">
    <div class="media-menu">
    </div>
  </div>

  <div class="media-frame-title">
    <h1>
      <a href="http://www.liveeditorcms.com/?utm_source=WordPress%2BPlugin&amp;utm_medium=link&amp;utm_term=header%2Bicon&amp;utm_content=v0.3&amp;utm_campaign=WordPress%2BPlugin" title="Live Editor"><i class="live-editor icon"></i></a>
      Live Editor File Manager
    </h1>
  </div>

  <div class="media-frame-content">
    <div id="files" class="attachments">
      <h1>Internal Server Error</h1>
      <p>There was an unexpected error trying to access the Live Editor service.</p>
      <pre><strong><?php echo $e->getMessage() ?></strong>

<?php echo $e->getFile() ?> (<?php echo $e->getLine() ?>)</pre>
      
      <p>
        For instructions on setting up your Live&nbsp;Editor WordPress plugin, see our
        <a href="http://www.liveeditorcms.com/help/wordpress-plugin?utm_source=WordPress%2BPlugin&amp;utm_medium=link&amp;utm_term=unauthorized&amp;utm_content=v0.3&amp;utm_campaign=WordPress%2BPlugin">
        WordPress Plugin documentation</a> or contact your account administrator.
      </p>
      <p>
        For help debugging your connection, visit the
        <a href="https://github.com/live-editor/live_editor_wordpress">Live Editor WordPress Plugin GitHub
        repository</a>.
      </p>
    </div>
  </div>

<?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/views/layouts/footer.php" ?>
