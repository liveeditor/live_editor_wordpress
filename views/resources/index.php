<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/header.php" ?>

<div class="media-frame wp-core-ui hide-router">
    <?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/global_navigation.php" ?>

    <div class="media-frame-title">
      <h1>
        <i class="live-editor icon"></i>
        Live Editor File Manager
      </h1>
    </div>
    <div class="media-frame-content">
      <div class="attachments-browser">
        <div id="files" class="attachments">
          <?php foreach($files as $file) : ?>
            <div class="file">
              <div class="thumb">
                <span class="thumbnail">
                  <img src="<?php echo $this->api()->get_file_url($file->id, 'medium') ?>" alt="Thumbnail" /><br />
                </span>
              </div>
              <h3><?php echo $file->title ?></h3>
              <p>
                <a href="#">Insert into <?php echo $_GET["post_type"] ?></a>
              </p>
            </div>
          <?php endforeach ?>
        </div>
        <div class="media-sidebar">
          <input type="search" placeholder="Search" class="search" />

          <h3>Show Types</h3>
          <p>
            <?php foreach($file_types as $file_type) : ?>
              <label>
                <input type="checkbox" name="file_types[]" value="<?php echo $file_type->id ?>" />
                <?php echo $file_type->name ?>
              </label><br />
            <?php endforeach ?>
          </p>
        </div>
      </div>
    </div>

<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/footer.php" ?>
