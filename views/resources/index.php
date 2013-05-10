<?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/views/layouts/header.php" ?>
<?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/helpers/files_helper.php" ?>

<div class="media-frame wp-core-ui hide-router">
  <?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/views/layouts/global_navigation.php" ?>

  <div class="media-frame-title">
    <h1>
      <a href="http://www.liveeditorcms.com/file-manager?utm_source=WordPress%2BPlugin&amp;utm_medium=link&amp;utm_term=header%2Bicon&amp;utm_content=v<?php echo self::VERSION ?>&amp;utm_campaign=WordPress%2BPlugin" title="Live Editor"><i class="live-editor icon"></i></a>
      Live Editor File Manager
    </h1>
  </div>
  <form id="files-form" action="<?php echo admin_url("admin-ajax.php") ?>" method="get">
    <input type="hidden" name="action" value="resources" />
    <input type="hidden" name="file_type_id" value="<?php echo $params['file_type_id'] ?>" />
    <input type="hidden" name="previewable" value="<?php echo $params['previewable'] ?>" />
    <input type="hidden" name="post_type" value="<?php echo $params['post_type'] ?>" />
    <input type="hidden" name="_ajax_nonce" value="<?php echo wp_create_nonce('resources') ?>" />
    <input type="hidden" name="wp_source" value="<?php $params['wp_source'] ?>" />
    <input type="hidden" id="total-pages" value="<?php echo $total_pages ?>" />

    <div class="media-frame-content">
      <div class="attachments-browser">
        <div class="media-toolbar">
          <div class="tablenav top">
            <div class="tablenav-pages">
              <span class="displaying-num">
                <?php echo $files_count ?> items
              </span>
              <span class="pagination-links">
                <a href="#" class="first-page <?php echo prev_page_link_class($current_page) ?> modal-ignore" title="Go to the first page">
                  &laquo;</a>
                <a href="#" class="prev-page <?php echo prev_page_link_class($current_page) ?> modal-ignore" title="Go to the previous page">
                  &lsaquo;</a>
                <span class="paging-input">
                  <input id="current-page" class="current-page" title="Current page" type="text" name="page" value="<?php echo $current_page ?>" size="1" />
                  of
                  <span class="total-pages">
                    <?php echo $total_pages ?>
                  </span>
                </span>
                <a href="#" class="next-page <?php echo next_page_link_class($current_page, $total_pages) ?> modal-ignore" title="Go to the next page">
                  &rsaquo;</a>
                <a href="#" class="last-page <?php echo next_page_link_class($current_page, $total_pages) ?> modal-ignore" title="Go to the last page">
                  &raquo;</a>
              </span>
            </div>
          </div>
        </div>

        <div id="files" class="attachments">
          <?php if ($params["import_success"]) : ?>
            <div class="updated">
              <p>
                The file was queued for processing. It may take a few minutes for it to appear in your list of files.
              </p>
            </div>
          <?php endif ?>

          <?php if (count($files)) : ?>
            <?php $resource_counter = 0 ?>
            <?php foreach($files as $file) : ?>
              <?php if ($resource_counter == 0) : ?>
                <div class="row">
              <?php endif ?>

              <div class="file">
                <div class="thumb">
                  <span class="thumbnail">
                    <img src="<?php echo $this->api()->get_file_url($file->id, 'medium') ?>" alt="Thumbnail" />
                  </span>
                </div>
                <h3><?php echo $file->title ?></h3>
                <p>
                  <?php if (array_key_exists("post_format", $params) && strlen($params["post_format"])) : ?>
                    <?php echo select_post_format_link($file, $params) ?>
                  <?php else : ?>
                    <?php echo insert_into_post_link($file, $params) ?>
                  <?php endif ?>
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
          <p>
            <input type="search" name="search" placeholder="Search" value="<?php echo $params['search'] ?>" class="search" />
          </p>

          <?php if (!array_key_exists("post_format", $params) || !strlen($params["post_format"])) : ?>
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
          <?php endif ?>

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
        </div>
      </div>
    </div>
  </form>

<?php require_once ABSPATH . "wp-content/plugins/live-editor-file-manager/views/layouts/footer.php" ?>
