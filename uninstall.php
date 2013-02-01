<?php
require_once "liveeditor.php";

// Check that this script was called from WP so others can't just instantiate this URL from outside the WP admin
if (defined("WP_UNINSTALL_PLUGIN")) {
  $live_editor_file_manager_plugin = new LiveEditorFileManagerPlugin();
  $live_editor_file_manager_plugin->uninstall();
}
else {
  exit;
}

?>