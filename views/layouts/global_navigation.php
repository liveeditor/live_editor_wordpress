<?php
// Logic for whether or not a nav item is active
function nav_active_class($section) {
  global $params;
  return $params["action"] == $section ? "active" : "";
}
?>

<div class="media-frame-menu">
  <div class="media-menu">
    <a
      href="<?php echo admin_url("admin-ajax.php") ?>"
      class="media-menu-item <?php echo nav_active_class('resources') ?>"
      data-action="resources"
      data-nonce="<?php echo wp_create_nonce('resources') ?>"
      data-post-type="<?php echo $params["post_type"] ?>"
    >
      Select Existing File
    </a>

    <a
      href="<?php echo admin_url("admin-ajax.php") ?>"
      class="media-menu-item <?php echo nav_active_class('resources_new') ?>"
      data-action="resources_new"
      data-nonce="<?php echo wp_create_nonce('resources_new') ?>"
      data-post-type="<?php echo $params["post_type"] ?>"
    >
      New File
    </a>
  </div>
</div>