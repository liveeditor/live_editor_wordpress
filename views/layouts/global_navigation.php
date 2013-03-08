<?php
// Logic for whether or not a nav item is active
function nav_active_class($sections) {
  global $params;

  if (gettype($sections) == "array") {
    return array_search($params["action"], $sections) !== false ? "active" : "";
  }
  else {
    return $params["action"] == $sections ? "active" : "";
  }
}
?>

<div class="media-frame-menu">
  <div class="media-menu">
    <a
      href="<?php echo admin_url("admin-ajax.php") ?>"
      class="media-menu-item <?php echo nav_active_class('resources') ?>"
      data-action="resources"
      data-nonce="<?php echo wp_create_nonce('resources') ?>"
      data-post-type="<?php echo $params['post_type'] ?>"
      data-target-url="<?php echo $params['wp_source'] ?>"
    >
      Select Existing File
    </a>

    <a
      href="<?php echo admin_url("admin-ajax.php") ?>"
      class="media-menu-item <?php echo nav_active_class(array('resources_new', 'resources_create')) ?>"
      data-action="resources_new"
      data-nonce="<?php echo wp_create_nonce('resources_new') ?>"
      data-post-type="<?php echo $params["post_type"] ?>"
      data-target-url="<?php echo $params['wp_source'] ?>"
    >
      New File
    </a>
  </div>
</div>