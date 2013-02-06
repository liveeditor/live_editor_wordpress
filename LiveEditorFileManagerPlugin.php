<?php

class LiveEditorFileManagerPlugin {
  const VERSION      = "0.1";
  const MINIMUM_WP   = "3.5";
  const OPTIONS_KEY  = "live_editor_file_manager_plugin_options"; // Used as key in WP options table

  /**
   * Constructor.
   */
  function __construct() {
    // Activation
    register_activation_hook(__FILE__, array(&$this, "activate"));

    // Actions
    add_action("admin_menu", array(&$this, "hide_media_tab"));  // Hide Media tab if the settings call for it
    add_action("admin_head", array(&$this, "admin_styles"));    // Load stylesheet needed for this plugin to run
    add_action("admin_init", array(&$this, "admin_init"));      // Initialize settings that are configurable through admin
    add_action("admin_menu", array(&$this, "settings_menu"));   // Add settings menu to WP menu
    add_action("media_buttons", array(&$this, "media_button")); // Adds Live Editor media button
  }

  /**
   * Configures plugin option defaults if not yet set.
   */
  function activate() {
    $options = get_option(self::OPTIONS_KEY);

    // Initialize option defaults
    if (!$options) {
      $options["version"]        = self::VERSION;
      $options["subdomain_slug"] = null;
      $options["account_api_key"]  = null;
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
      "subdomain_slug",
      "Account Subdomain",
      array(&$this, "display_subdomain_slug_text_field"),
      "live_editor_file_manager_settings_section",
      "live_editor_file_manager_main_settings_section",
      array("name" => "subdomain_slug")
    );

    add_settings_field(
      "account_api_key",
      "Account API Key",
      array(&$this, "display_account_api_key_text_field"),
      "live_editor_file_manager_settings_section",
      "live_editor_file_manager_main_settings_section",
      array("name" => "account_api_key")
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
   * Loads styles needed for this plugin to run.
   */
  function admin_styles() {
    $options = get_option(self::OPTIONS_KEY);
  ?>
    <link
      rel="stylesheet"
      type="text/css"
      href="<?php echo getenv('PHP_LIVE_EDITOR_API_PROTOCOL') ?>www.<?php echo getenv('PHP_LIVE_EDITOR_API_DOMAIN') ?>/assets/wordpress_plugin.css"
    />
  <?php
    if ($options["hide_media_tab"]) {
    ?>
      <style type="text/css">
        .insert-media.add_media.button {
          display: none;
        }
      </style>
    <?php
    }
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
        <p class="submit">
          <input type="submit" value="Save Changes" class="button button-primary" />
        </p>
      </form>
    </div>
  <?php
  }

  /**
   * Displays text field for "admin API key" setting.
   */
  function display_account_api_key_text_field($data = array()) {
    extract($data);
    $options = get_option(self::OPTIONS_KEY);
  ?>
    <input
      type="text"
      name="<?php echo self::OPTIONS_KEY ?>[<?php echo $name ?>]"
      value="<?php echo $options['account_api_key'] ?>"
      class="regular-text"
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
   * Displays text field for the "account subdomain" setting.
   */
  function display_subdomain_slug_text_field($data = array()) {
    extract($data);
    $options = get_option(self::OPTIONS_KEY);
  ?>
    <input
      type="text"
      name="<?php echo self::OPTIONS_KEY ?>[<?php echo $name ?>]"
      value="<?php echo $options['subdomain_slug'] ?>"
      class="regular-text"
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
   * Adds Live Editor media button to post and page editors.
   */
  function media_button() {
  ?>
    <a href="#" class="button insert-live-editor-media" title="Add Media from Live Editor File Manager">
      <i class="media icon"></i>
      Add Media</a>
  <?php
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
      array("id" => "subdomain_slug", "type" => "text"),
      array("id" => "account_api_key",  "type" => "text"),
      array("id" => "hide_media_tab", "type" => "check_box")
    );
  }
}

?>