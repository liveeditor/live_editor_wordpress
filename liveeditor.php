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
    function __construct() {
      // Activation
      register_activation_hook(__FILE__, array(&$this, "activate"));

      // Actions
      add_action("admin_menu", array(&$this, "hide_media_tab")); // Hide Media tab if the settings call for it
      add_action("admin_init", array(&$this, "admin_init"));     // Initialize settings that are configurable through admin
      add_action("admin_menu", array(&$this, "settings_menu"));  // Add settings menu to WP menu
    }

    /**
     * Configures plugin option defaults if not yet set.
     */
    function activate() {
      $options = get_option(self::OPTIONS_KEY);

      // Initialize option defaults
      if (!$options) {
        $options["version"]        = self::VERSION;
        $options["admin_api_key"]  = null;
        $options["hide_media_tab"] = false;

        add_option(self::OPTIONS_KEY, $options);
      }
    }

    /**
     * Initializes option validation and saving.
     */
    function admin_init() {
      // Handles post data and validation
      register_setting("live_editor_file_manager_settings", self::OPTIONS_KEY, array(&$this, "validate_settings"));

      // Main settings section within the group
      add_settings_section(
        "live_editor_file_manager_main_settings_section",
        "Main Settings",
        array(&$this, "settings_section_callback"),
        "live_editor_file_manager_settings_section"
      );

      // Each setting field editable through the interface
      add_settings_field(
        "admin_api_key",
        "Admin API Key",
        array(&$this, "display_admin_api_key_text_field"),
        "live_editor_file_manager_settings_section",
        "live_editor_file_manager_main_settings_section",
        array("name" => "admin_api_key")
      );

      add_settings_field(
        "hide_media_tab",
        "Hide WordPress Media Tab",
        array(&$this, "display_hide_media_tab_check_box"),
        "live_editor_file_manager_settings_section",
        "live_editor_file_manager_main_settings_section",
        array("name" => "hide_media_tab")
      );
    }

    /**
     * Displays settings page for Live Editor File Manager.
     */
    function config_page() {
    ?>
      <div id="live-editor-file-manager-general" class="wrap">
        <h2>Live Editor File Manager Settings</h2>
        <p>
          Settings for <a href="http://www.liveeditorcms.com/">Live Editor File Manager</a> integration with your
          WordPress system. For documentation, reference our
          <a href="http://www.liveeditorcms.com/help/wordpress-plugin">WordPress plugin instructions</a>.
        </p>
        <form name="live_editor_file_manager_settings" action="options.php" method="post">
          <?php echo settings_fields("live_editor_file_manager_settings") ?>
          <?php echo do_settings_sections("live_editor_file_manager_settings_section") ?>
          <p>
            <input type="submit" value="Save Changes" />
          </p>
        </form>
      </div>
    <?php
    }

    /**
     * Displays text field for "admin API key" setting.
     */
    function display_admin_api_key_text_field($data = array()) {
      extract($data);
      $options = get_option(self::OPTIONS_KEY);
    ?>
      <input
        type="text"
        name="<?php echo self::OPTIONS_KEY ?>[<?php echo $name ?>]"
        value="<?php echo $options['admin_api_key'] ?>"
      />
    <?php
    }

    /**
     * Displays check box for the "hide media tab" setting.
     */
    function display_hide_media_tab_check_box($data = array()) {
      extract($data);
      $options = get_option(self::OPTIONS_KEY);
    ?>
      <input
        type="checkbox"
        name="<?php echo self::OPTIONS_KEY ?>[<?php echo $name ?>]"
        <?php if ($options["hide_media_tab"]) { ?>
          checked="checked"
        <?php } ?>
      />
    <?php
    }

    /**
     * Hides Media menu if the setting is in place to do so.
     */
    function hide_media_tab() {
      $options = get_option(self::OPTIONS_KEY);

      if ($options["hide_media_tab"]) {
        remove_menu_page("upload.php");
      }
    }

    /**
     * Adds Live Editor settings to settings menu.
     */
    function settings_menu() {
      add_options_page(
        "Live Editor File Manager",
        "File Manager",
        "manage_options",
        "live-editor-file-manager",
        array(&$this, "config_page")
      );
    }

    /**
     * Displays text to describe the settings section.
     */
    function settings_section_callback() {
      echo '<p>These settings affect all WordPress users connecting to Live Editor for their digital media.</p>';
    }

    /**
     * Uninstall process to be run by uninstaller script.
     */
    function uninstall() {
      delete_option(self::OPTIONS_KEY);
    }

    /**
     * Validates option settings posted by admin.
     */
    function validate_settings($input) {
      $valid_settings = $this->valid_settings();
      $final_settings = get_option(self::OPTIONS_KEY);

      // Whitelist setting options so a malicious user cannot add extra keys to the associative array
      foreach ($valid_settings as $setting) {
        $id   = $setting["id"];
        $type = $setting["type"];

        switch ($type) {
          case "check_box":
            $final_settings[$id] = isset($input[$id]) && $input[$id] ? true : false;
            break;
          default:
            $final_settings[$id] = $input[$id];
            break;
        }
      }

      return $final_settings;
    }

    /**
     * Returns array of valid settings.
     */
    private function valid_settings() {
      return array(
        array("id" => "admin_api_key",  "type" => "text"),
        array("id" => "hide_media_tab", "type" => "check_box")
      );
    }
  }

  // Instantiate the plugin
  global $live_editor_file_manager_plugin;
  $live_editor_file_manager_plugin = new LiveEditorFileManagerPlugin();
}

?>