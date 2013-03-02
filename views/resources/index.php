<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/header.php" ?>
<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/helpers/files_helper.php" ?>

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
        <?php if (count($files)) : ?>
          <?php $resource_counter = 0 ?>
          <?php foreach($files as $file) : ?>
            <?php if ($resource_counter == 0) : ?>
              <div class="row">
            <?php endif ?>

            <div class="file">
              <div class="thumb">
                <span class="thumbnail">
                  <img src="<?php echo $this->api()->get_file_url($file->id, 'medium') ?>" alt="Thumbnail" /><br />
                </span>
              </div>
              <h3><?php echo $file->title ?></h3>
              <p>
                <a href="#">Insert into <?php echo $params["post_type"] ?></a>
              </p>
            </div>

            <?php if ($resource_counter == 2) : ?>
              </div>
            <?php endif ?>

            <?php
              if ($resource_counter == 2) {
                $resource_counter = 0;
              }
              else {
                $resource_counter++;
              }
            ?>
          <?php endforeach ?>
          <?php if ($resource_counter > 0) : ?>
            </div>
          <?php endif ?>
        <?php else : ?>
          <p>
            There are no files for the specified filters.
          </p>
        <?php endif ?>
      </div>

      <div class="media-sidebar">
        <form action="<?php echo admin_url("admin-ajax.php") ?>" method="get">
          <input type="hidden" name="action" value="resources" />
          <input type="hidden" name="post_type" value="<?php echo $params['post_type'] ?>" />
          <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce('resources') ?>" />

          <p>
            <input type="search" name="search" placeholder="Search" value="<?php echo $params['search'] ?>" class="search" />
          </p>

          <h3>Show Types</h3>
          <p>
            <?php if (count($file_types)) : ?>
              <?php foreach($file_types as $file_type) : ?>
                <label>
                  <input
                    type="checkbox"
                    name="file_types[]"
                    value="<?php echo $file_type->id ?>"
                    <?php if (!count($params["file_types"]) || array_search($file_type->id, $params["file_types"]) !== false) : ?>
                      checked="checked"
                    <?php endif ?>
                  />
                  <?php echo $file_type->name ?>
                </label><br />
              <?php endforeach ?>
            <?php else : ?>
              <p>There was an error loading file types. Please try again later.</p>
            <?php endif ?>
          </p>

          <h3>Show Collections</h3>
          <?php if (count($collections)) : ?>
            <div id="collections">
              <?php echo collections_nested_set($collections, $params["collections"]) ?>
            </div>
          <?php else : ?>
            <p>There are no collections in the system.</p>
          <?php endif ?>

          <p>
            <input type="submit" value="Update Filters" class="button media-button" />
          </p>
        </form>
      </div>
    </div>
  </div>

<?php require_once ABSPATH . "wp-content/plugins/live_editor_files_wordpress/views/layouts/footer.php" ?>
