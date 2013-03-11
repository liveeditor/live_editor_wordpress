<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/header.php" ?>
<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/helpers/files_helper.php" ?>

<div class="media-frame wp-core-ui">
  <?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/global_navigation.php" ?>
  <div class="media-frame-title">
    <h1>
      <a href="http://www.liveeditorcms.com/?utm_source=WordPress%2BPlugin&amp;utm_medium=link&amp;utm_term=header%2Bicon&amp;utm_content=v0.1&amp;utm_campaign=WordPress%2BPlugin" title="Live Editor"><i class="live-editor icon"></i></a>
      New File
    </h1>
  </div>
  <div class="media-frame-router">
    <div class="media-router">
      <a href="#upload-form" class="media-menu-item modal-ignore <?php echo active_tab_class('upload-form', $active_tab) ?>">Upload Files</a>
      <a href="#url-form" class="media-menu-item modal-ignore <?php echo active_tab_class('url-form', $active_tab) ?>">
        Open from <abbr title="Uniform Resource Locator">URL</abbr></a>
    </div>
  </div>
  <div class="media-frame-content">
    <div id="upload-form">
      <iframe
        src="<?php echo $this->api_url("/admin/resources/uploads/new?post_type=" . $params['post_type'] . "&amp;wp_source=" . urlencode($params['wp_source']) . "&amp;wp_nonce=" . urlencode(wp_create_nonce('editor_code')), true, "/wp/v1") ?>"
        width="100%"
        height="300"
      ></iframe>
    </div>

    <div id="url-form">
      <form action="<?php echo admin_url("admin-ajax.php") ?>" method="post">
        <input type="hidden" name="action" value="resources_imports_create" />
        <input type="hidden" name="post_type" value="<?php echo $params['post_type'] ?>" />
        <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce('resources_imports_create') ?>" />
        <input type="hidden" name="wp_source" value="<?php $params['wp_source'] ?>" />

        <?php if (isset($flash["error"])) : ?>
          <div class="error">
            <p>
              <?php echo htmlspecialchars($flash["error"]) ?>
            </p>
          </div>
        <?php endif ?>

        <p>
          <label>
            <abbr title="Uniform Resource Locator">URL</abbr><br />
            <input type="text" name="url" value="<?php echo $params['url'] ?>" class="regular-text" />
          </label>
          <p class="description">
            Examples: <kbd>http://www.youtube.com/watch?v=gsMqakE7noE</kbd> or <kbd>http://vimeo.com/52302939</kbd>
          </p>
        </p>
        <p>
          <input type="submit" value="Open File" class="button button-primary" />
        </p>
      </form>
    </div>
  </div>

<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/footer.php" ?>
