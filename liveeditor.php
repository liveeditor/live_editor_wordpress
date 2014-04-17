<?php
/*
Plugin Name: Live Editor File Manager
Plugin URI: http://www.liveeditorcms.com/wordpress?utm_source=WordPress%2BPlugin%2BBrowser&utm_medium=link&utm_content=v0.5.7&utm_term=Plugin%2BHomepage&utm_campaign=WordPress%2BPlugin
Description: Allows you to add Files from your Live Editor account into WordPress posts and pages.
Version: 0.5.7
Author: Live Editor
Author URI: http://www.liveeditorcms.com/file-manager?utm_source=WordPress%2BPlugin%2BBrowser&utm_medium=link&utm_content=v0.5.7&utm_term=Author%2BURI&utm_campaign=WordPress%2BPlugin

Copyright (C) 2013 Minimal Orange, LLC. All rights reserved.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

// Loads the `LiveEditorFileManager` plugin class if we're in the WP admin area
if (is_admin() && !class_exists('LiveEditorFileManagerPlugin')) {
  require_once plugin_dir_path(__FILE__) . ".env.php";
  require_once plugin_dir_path(__FILE__) . "LiveEditorFileManagerPlugin.php";

  // Instantiate the plugin class
  global $live_editor_file_manager_plugin;
  $live_editor_file_manager_plugin = new LiveEditorFileManagerPlugin(__FILE__);
}

?>