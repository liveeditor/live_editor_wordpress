<?php
/*
Plugin Name: Live Editor File Manager
Plugin URI: http://www.liveeditorcms.com/wordpress
Description: Allows you to add Files from your Live Editor account into WordPress posts and pages.
Version: 0.1
Author: Live Editor
Author URI: http://www.liveeditorcms.com/

Copyright (C) 2013 Clear Crystal Media, LLC. All rights reserved.

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

/**
 * Loads the `LiveEditorFileManager` plugin.
 */
if (!class_exists('LiveEditorFileManagerPlugin')) {
  class LiveEditorFileManagerPlugin {
    const VERSION     = "0.1";
    const MINIMUM_WP  = "3.5";
    const OPTIONS_KEY = "live_editor_file_manager_plugin_options"; // Used as key in WP options table

    /**
     * Constructor.
     */
    function LiveEditorFileManagerPlugin() {
      // Activation
      register_activation_hook(__FILE__, array(&$this, "activate"));
    }

    function uninstall() {
      delete_option(self::OPTIONS_KEY);
    }

    /**
     * Configures plugin option defaults if not yet set.
     */
    function activate() {
      $options = get_option(self::OPTIONS_KEY);

      // Initialize option defaults
      if (!$options) {
        $options["version"] = self::VERSION;
        $options["api_key"] = null;

        add_option(self::OPTIONS_KEY, $options);
      }
    }
  }

  // Instantiate the plugin
  global $live_editor_file_manager_plugin;
  $live_editor_file_manager_plugin = new LiveEditorFileManagerPlugin();
}

?>