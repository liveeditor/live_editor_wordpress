<?php

// Check for dev environment file
if (file_exists(plugin_dir_path(__FILE__) . ".env.dev.php")) {
  require_once plugin_dir_path(__FILE__) . ".env.dev.php";
}
// Production settings
else {
  // Must prefix with `PHP_` to accommodate environments with stricter environment variable settings.
  // http://php.net/manual/en/function.putenv.php
  putenv("PHP_LIVE_EDITOR_API_PROTOCOL=https://");
  putenv("PHP_LIVE_EDITOR_API_DOMAIN=liveeditorcms.com");
}

?>