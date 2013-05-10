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
      href="<?php echo admin_url("admin-ajax.php") ?>?action=resources"
      class="media-menu-item <?php echo nav_active_class('resources') ?>"
      data-nonce-name="resources"
      <?php echo post_format_link_attributes($params) ?>
    >
      Select Existing File
    </a>

    <a
      href="<?php echo admin_url("admin-ajax.php") ?>?action=resources_new"
      class="media-menu-item <?php echo nav_active_class(array('resources_new', 'resources_create')) ?>"
      data-nonce-name="resources_new"
      <?php echo post_format_link_attributes($params) ?>
    >
      New File
    </a>
  </div>
</div>