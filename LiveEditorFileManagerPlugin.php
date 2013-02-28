<?php
require_once "api/LiveEditor.php";

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

    // Plugin settings
    add_action("admin_head", array(&$this, "admin_assets"));  // Load stylesheet and JavaScript needed for this plugin to run
    add_action("admin_init", array(&$this, "admin_init"));    // Initialize settings that are configurable through admin
    add_action("admin_menu", array(&$this, "settings_menu")); // Add settings menu to WP menu
    
    // User API key in admin user profile
    add_action("show_user_profile", array(&$this, "user_preferences"));            // Adds user API key field to user preferences
    add_action("personal_options_update", array(&$this, "save_personal_options")); // Saves user API key in WP database
    
    // Using Live Editor within WordPress editors
    add_action("admin_menu", array(&$this, "hide_media_tab"));           // Hide Media tab if the settings call for it
    add_action("wp_ajax_resources", array(&$this, "resources"));         // Add resources page for AJAX to call
    add_action("wp_ajax_resources_new", array(&$this, "resources_new")); // Add resources/new page for AJAX to call
    add_action("wp_ajax_editor_code", array(&$this, "editor_code"));     // Add editor code insertion page for AJAX to call
    add_action("media_buttons", array(&$this, "media_button"));          // Adds Live Editor media button
    add_action("publish_post", array(&$this, "create_resource_usages")); // Adds usage record for newly-published post
    add_action("publish_page", array(&$this, "create_resource_usages")); // Adds usage record for newly-published page
  }

  /**
   * Configures plugin option defaults if not yet set.
   */
  function activate() {
    $options = get_option(self::OPTIONS_KEY);

    // Initialize option defaults
    if (!$options) {
      $options["version"]          = self::VERSION;
      $options["subdomain_slug"]   = null;
      $options["account_api_key"]  = null;
      $options["hide_media_tab"]   = false;

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
   * Loads stylesheet and JavaScript needed for this plugin to run.
   */
  function admin_assets() {
    $options = get_option(self::OPTIONS_KEY);

    // Our main stylesheet
  ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->url_base() ?>/assets/wordpress_plugin.css" />
    <link rel="stylesheet" type="text/css" href="<?php echo plugins_url('stylesheets/styles.css', __FILE__) ?>" />
  <?php

    // If user has opted to hide the media tab, hide the default "Add Media" button.
    if ($options["hide_media_tab"]) {
    ?>
      <style type="text/css">
        .insert-media.add_media.button {
          display: none;
        }
      </style>
    <?php
    }

    // JavaScript
    wp_enqueue_script(
      "live-editor-file-manager-plugin",
      plugins_url('javascripts/live-editor-file-manager-plugin.js', __FILE__),
      "tinymce",
      self::VERSION
    );
    ?>
      <script src="<?php echo $this->url_base() ?>/assets/wordpress_plugin.js"></script>
    <?php
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
   * Creates resource usage records for newly-published page or post.
   */
  function create_resource_usages($post_id) {
    $options = get_option(self::OPTIONS_KEY);
    $post = get_post($post_id);
    $current_user = wp_get_current_user();

    // Only perform this action if the plugin has an API key registered for the account and user
    if (isset($options["account_api_key"]) && $options["account_api_key"] && isset($current_user->live_editor_user_api_key)) {
      // Get domains so we can look for Live Editor usages
      $domains = $this->api()->get_domains();
      // Add `subdomain_slug.liveeditorcms.com` as default domain
      $subdomain = new stdClass;
      $subdomain->name = $options["subdomain_slug"] . "." . getenv("PHP_LIVE_EDITOR_API_DOMAIN");
      array_push($domains, $subdomain);

      // Get current file usages before we mess with them so we can delete unused ones later
      $external_urls = $this->api()->get_file_usages_for_url($post->guid);

      // Store file IDs for use later
      $content_file_ids = array();

      // Find Live Editor resources in content
      foreach ($domains as $domain) {
        // Match Live Editor URLs
        $search = '/(href|src)="?(https?:?\/\/|\/\/)?' . str_replace(".", "\.", $domain->name) . '\//i';
        if (preg_match_all($search, $post->post_content, &$matches, PREG_OFFSET_CAPTURE)) {
          foreach ($matches[0] as $match) {
            // Grab file ID from match
            $file_id = substr($post->post_content, $match[1] + strlen($match[0]) + strlen("files/resources/"));
            $file_id = substr($file_id, 0, strpos($file_id, "/"));
            
            // Hang onto this one for later
            array_push($content_file_ids, $file_id);
            
            // See if this post is already associated with the file as a file usage
            $file_usages = $this->api()->get_file_usages($file_id);

            $usage_recorded = false;
            foreach ($file_usages as $file_usage) {
              if ($file_usage->usage->url == $post->guid) {
                $usage_recorded = true;
              }
            }

            // Add usage if it's not already recorded
            if (!$usage_recorded) {
              $external_url = array(
                "external_url[title]" => strlen($post->post_title) ? $post->post_title : "WordPress Post",
                "external_url[url]" => $post->guid,
                "external_url[notes]" => "WordPress site."
              );

              $this->api()->create_external_url($file_id, $external_url);
            }
          }
        }
      }

      // Remove unused file usages
      foreach ($external_urls as $external_url) {
        $file_id = $external_url->resource_usage->resource_id;
        $external_url_id = $external_url->id;

        if (array_search($file_id, $content_file_ids) === false) {
          $this->api()->delete_file_external_url($file_id, $external_url_id);
        }
      }
    }
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
   * Editor code insert page output. See `editor_code_insertion()` method.
   */
  function editor_code() {
    // AJAX nonce makes sure outside hackers can't get into this script
    check_ajax_referer("media_button");

    // Calling `die()` stops WP from returning a 0 or 1 to indicate success with AJAX request
    die(file_get_contents($this->api_url("/wp/v1/admin/resources/" . $_POST["resource_id"] . "/code")));
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
    $options = get_option(self::OPTIONS_KEY);
    global $post_type;

    if (isset($options["subdomain_slug"]) && $options["subdomain_slug"]) {
    ?>
      <a
        id="live-editor-file-manager-add-media-link"
        href="<?php echo admin_url("admin-ajax.php") ?>"
        class="button insert-live-editor-media"
        title="Live Editor File Manager"
        data-target-domain="<?php echo $this->url_base() ?>"
        data-nonce="<?php echo wp_create_nonce('resources') ?>"
        data-post-type="<?php echo $this->post_type($post_type) ?>"
      >
        <i class="media icon"></i>
        Add Media</a>
    <?php
    }
  }

  /**
   * Displays resources AJAX page.
   */
  function resources() {
    // AJAX nonce makes sure outside hackers can't get into this script
    check_ajax_referer("resources");

    $files = $this->api()->get_files();

    require_once "views/resources/index.php";
    die();
  }

  /**
   * Displays resources/new AJAX page.
   */
  function resources_new() {
    // AJAX nonce makes sure outside hackers can't get into this script
    check_ajax_referer("resources_new");

    require_once "views/resources/new.php";
    die();
  }

  /**
   * Saves user API key to WP database.
   */
  function save_personal_options($user_id) {
    update_user_meta($user_id, "live_editor_user_api_key", $_POST["live_editor_user_api_key"]);
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
   * Fields to add to user preferences screen.
   */
  function user_preferences($user) {
  ?>
    <h3>Live Editor File Manager</h3>
    <table class="form-table">
      <tbody>
        <tr>
          <th>
            <label for="live-editor-user-api-key">
              User <abbr title="Application Programming Interface">API</abbr> Key
            </label>
          </th>
          <td>
            <input
              type="text"
              name="live_editor_user_api_key"
              id="live-editor-user-api-key"
              class="regular-text code"
              <?php
              if (isset($user->live_editor_user_api_key)) {
              ?>
                value="<?php echo htmlspecialchars($user->live_editor_user_api_key) ?>"
              <?php
              }
              ?>
            />
            <span class="description">
              View your profile in Live Editor to obtain your user
              <abbr title="Application Programming Interface">API</abbr> key.
            </span>
          </td>
        </tr>
      </tbody>
    </table>
  <?php
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
   * Returns instance of API object.
   */
  private function api() {
    $options = get_option(self::OPTIONS_KEY);
    $current_user = wp_get_current_user();

    return new LiveEditor(
      $options["account_api_key"],
      $current_user->live_editor_user_api_key,
      $options["subdomain_slug"],
      "Live Editor WordPress Plugin"
    );
  }

  /**
   * Returns an API URL for a given path by prepending the URL base and adding API keys to end.
   */
  private function api_url($path, $escape_amp = false) {
    $options = get_option(self::OPTIONS_KEY);
    $amp = $escape_amp ? "&amp;" : "&";
    $user = wp_get_current_user();

    $url  = $this->url_base() . $path;
    $url .= strpos($path, "?") ? $amp : "?";
    $url .= $amp . "account_api_key=" . urlencode($options["account_api_key"]);

    if (isset($user->live_editor_user_api_key)) {
      $url .= $amp . "user_api_key=" . urlencode($user->live_editor_user_api_key);
    }

    $url .= $amp . "wp_source=" . urlencode($this->full_url());

    return $url;
  }

  /**
   * Returns full URL of current page.
   * http://stackoverflow.com/questions/6768793/php-get-the-full-url
   */
  private function full_url() {
    $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $sp = strtolower($_SERVER["SERVER_PROTOCOL"]);
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
  }

  /**
   * Returns value for global `$post_type`.
   */
  private function post_type($post_type) {
    return isset($post_type) && strlen($post_type) ? $post_type : "post";
  }

  /**
   * Returns protocol and domain for Live Editor (e.g., `https://wp.liveeditorcms.com`).
   */
  private function url_base() {
    $options = get_option(self::OPTIONS_KEY);

    return getenv('PHP_LIVE_EDITOR_API_PROTOCOL') . $options["subdomain_slug"] . "." . getenv('PHP_LIVE_EDITOR_API_DOMAIN');
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