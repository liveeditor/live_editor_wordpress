<?php
require_once "api/LiveEditor.php";

class LiveEditorFileManagerPlugin {
  const VERSION        = "0.5.6";
  const MINIMUM_WP     = "3.5";
  const OPTIONS_KEY    = "live_editor_file_manager_plugin_options"; // Used as key in WP options table
  const FILES_PER_PAGE = 15;

  /**
   * Constructor.
   */
  function __construct($main_plugin_file) {
    // Activation
    register_activation_hook($main_plugin_file, array($this, "activate"));

    // Plugin settings
    add_action("admin_head", array($this, "admin_assets"));  // Load stylesheet and JavaScript needed for this plugin to run
    add_action("admin_init", array($this, "admin_init"));    // Initialize settings that are configurable through admin
    add_action("admin_menu", array($this, "settings_menu")); // Add settings menu to WP menu
    
    // User API key in admin user profile
    add_action("show_user_profile", array($this, "user_preferences"));            // Adds user API key field to user preferences
    add_action("personal_options_update", array($this, "save_personal_options")); // Saves user API key in WP database
    
    // Using Live Editor within WordPress editors
    add_action("admin_menu", array($this, "hide_media_tab"));                                 // Hide Media tab if the settings call for it
    add_action("wp_ajax_resources", array($this, "resources"));                               // Add resources page for AJAX to call
    add_action("wp_ajax_resources_new", array($this, "resources_new"));                       // Add resources/new page for AJAX to call
    add_action("wp_ajax_resources_imports_create", array($this, "resources_imports_create")); // Create resource action for AJAX to call
    add_action("wp_ajax_editor_code", array($this, "editor_code"));                           // Add editor code insertion page for AJAX to call
    add_action("media_buttons", array($this, "media_button"));                                // Adds Live Editor media button
    add_action("wp_fullscreen_buttons", array($this, "media_button_fullscreen"));             // Adds Live Editor media button to fullscreen editor
    add_action("publish_post", array($this, "create_resource_usages"));                       // Adds usage record for newly-published post
    add_action("publish_page", array($this, "create_resource_usages"));                       // Adds usage record for newly-published page
    add_action("wp_trash_post", array($this, "delete_resource_usages"));                      // Deletes usages records for trashed post or page
  }

  /**
   * Configures plugin option defaults if not yet set.
   */
  function activate() {
    $options = get_option(self::OPTIONS_KEY);

    // Initialize option defaults
    if (!$options) {
      $options["version"]                = self::VERSION;
      $options["subdomain_slug"]         = null;
      $options["hide_media_tab"]         = false;
      $options["hide_add_media_buttons"] = false;

      add_option(self::OPTIONS_KEY, $options);
    }
  }

  /**
   * Initializes option validation and saving.
   */
  function admin_init() {
    // Handles post data and validation
    register_setting("live_editor_file_manager_settings", self::OPTIONS_KEY, array($this, "validate_settings"));

    // Main settings section within the group
    add_settings_section(
      "live_editor_file_manager_main_settings_section",
      "Main Settings",
      array($this, "settings_section_callback"),
      "live_editor_file_manager_settings_section"
    );

    // Each setting field editable through the interface
    add_settings_field(
      "subdomain_slug",
      "Account Subdomain",
      array($this, "display_subdomain_slug_text_field"),
      "live_editor_file_manager_settings_section",
      "live_editor_file_manager_main_settings_section",
      array("name" => "subdomain_slug")
    );

    add_settings_field(
      "hide_media_tab",
      "Hide WordPress <em>Media</em> Section from Menu",
      array($this, "display_hide_media_tab_check_box"),
      "live_editor_file_manager_settings_section",
      "live_editor_file_manager_main_settings_section",
      array("name" => "hide_media_tab")
    );

    add_settings_field(
      "hide_add_media_buttons",
      "Hide WordPress <em>Add Media</em> Buttons",
      array($this, "display_hide_add_media_buttons_check_box"),
      "live_editor_file_manager_settings_section",
      "live_editor_file_manager_main_settings_section",
      array("name" => "hide_add_media_buttons")
    );
  }

  /**
   * Loads stylesheet and JavaScript needed for this plugin to run.
   */
  function admin_assets() {
    $options = get_option(self::OPTIONS_KEY);
    global $post_type;

    // Our main stylesheet
  ?>
    <link rel="stylesheet" type="text/css" href="<?php echo plugins_url('stylesheets/styles.css', __FILE__) ?>" />
  <?php

    // JavaScript
    wp_enqueue_script(
      "postmessage",
      plugins_url('javascripts/jquery.ba-postmessage.js', __FILE__),
      "jquery",
      self::VERSION
    );

    wp_enqueue_script(
      "insertAtCaret",
      plugins_url('javascripts/jquery.insertAtCaret.js', __FILE__),
      "jquery",
      self::VERSION
    );

    wp_enqueue_script(
      "live-editor-file-manager-plugin",
      plugins_url('javascripts/live-editor-file-manager-plugin.js', __FILE__),
      "tinymce",
      self::VERSION
    );

    // Add settings info to body tag so JS can pick it up
    if (isset($options["subdomain_slug"]) && $options["subdomain_slug"]) {
      ?>
        <script>
          jQuery(document).ready(function() {
            var body = jQuery("body");
            body.addClass("live-editor-activated");
            body.attr("data-live-editor-activated", true);
            body.attr("data-live-editor-subdomain", "<?php echo $options['subdomain_slug'] ?>");
            body.attr("data-live-editor-admin-ajax-url", "<?php echo admin_url('admin-ajax.php') ?>");
            body.attr("data-live-editor-post-type", '<?php echo $this->post_type($post_type) ?>');
            body.attr("data-live-editor-target-domain", '<?php echo $this->url_base() ?>');
            body.attr("data-live-editor-target-url", '<?php echo $this->full_url() ?>');
            body.attr("data-live-editor-nonce-resources", "<?php echo wp_create_nonce('resources') ?>");
            body.attr("data-live-editor-nonce-resources_new", "<?php echo wp_create_nonce('resources_new') ?>");
            body.attr("data-live-editor-nonce-editor_code", "<?php echo wp_create_nonce('editor_code') ?>");

            <?php if (array_key_exists("hide_add_media_buttons", $options) && $options["hide_add_media_buttons"]) : ?>
              body.addClass("live-editor-hide-add-media-buttons");
              body.attr("data-live-editor-hide-add-media-buttons", true);
            <?php endif ?>
          });
        </script>
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
        Settings for
        <a href="http://www.liveeditorcms.com/file-manager?utm_source=WordPress+Plugin&amp;utm_medium=config+page&amp;utm_content=v<?php echo self::VERSION ?>&amp;utm_term=Live+Editor+File+Manager&amp;utm_campaign=WordPress+Plugin">Live&nbsp;Editor File Manager</a>
        integration with your WordPress system. For documentation, reference our
        <a href="http://www.liveeditorcms.com/help/wordpress-plugin?utm_source=WordPress+Plugin&amp;utm_medium=config+page&amp;utm_content=v<?php echo self::VERSION ?>&amp;WordPress+plugin+instructions&amp;utm_campaign=WordPress+Plugin">WordPress plugin instructions</a>.
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

    // Only perform this action if the plugin has an API key registered
    if (isset($current_user->live_editor_user_api_key)) {
      // Get domains so we can look for Live Editor usages
      $domains = $this->api()->get_domains();
      // Add `subdomain_slug.liveeditorcms.com` as default domain
      $subdomain = new stdClass;
      $subdomain->name = $options["subdomain_slug"] . "." . getenv("PHP_LIVE_EDITOR_API_DOMAIN");
      array_push($domains, $subdomain);

      // Get current file usages before we mess with them so we can delete unused ones later
      $external_urls = $this->api()->get_external_urls_for_url($post->guid);

      // Store file IDs for use later
      $content_file_ids = array();

      // Find Live Editor resources in content
      foreach ($domains as $domain) {
        // Match Live Editor URLs based on domains
        $search = '/(href|src)="?(https?:?\/\/|\/\/)?' . str_replace(".", "\.", $domain->name) . '\//i';

        // Post content
        $content_file_ids = $this->search_and_create_resource_usages(
          $search,
          $post->post_content,
          $post->post_title,
          $post->guid,
          $content_file_ids
        );
      }

      // Remove unused file usages
      foreach ($external_urls as $external_url) {
        foreach ($external_url->resource_usages as $file_usage) {
          if (array_search($file_usage->resource_id, $content_file_ids) === false) {
            $this->api()->delete_file_external_url($file_usage->resource_id, $file_usage->id);
          }
        }
      }
    }
  }

  /**
   *  Deletes usages records for trashed post or page.
   */
  function delete_resource_usages($post_id) {
    $options = get_option(self::OPTIONS_KEY);
    $post = get_post($post_id);
    $current_user = wp_get_current_user();

    // Only perform this action if the plugin has an API key registered
    if (isset($current_user->live_editor_user_api_key)) {
      // Get current file usages
      $external_urls = $this->api()->get_external_urls_for_url($post->guid);

      // Delete external URLs
      foreach ($external_urls as $external_url) {
        foreach($external_url->resource_usages as $file_usage) {
          $this->api()->delete_file_external_url($file_usage->resource_id, $file_usage->id);
        }
      }
    }
  }

  /**
   * Displays check box for the "hide media buttons" setting.
   */
  function display_hide_add_media_buttons_check_box($data = array()) {
    extract($data);
    $options = get_option(self::OPTIONS_KEY);
  ?>
    <input
      type="checkbox"
      name="<?php echo self::OPTIONS_KEY ?>[<?php echo $name ?>]"
      <?php if (array_key_exists("hide_add_media_buttons", $options) && $options["hide_add_media_buttons"]) : ?>
        checked="checked"
      <?php endif ?>
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
      <?php if ($options["hide_media_tab"]) : ?>
        checked="checked"
      <?php endif ?>
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
    check_ajax_referer("editor_code");

    global $params;
    $params = $this->request_params(array("resource_id"));

    // Calling `die()` stops WP from returning a `0` or `1` to indicate success with AJAX request
    die(file_get_contents($this->api_url("/resources/" . $params["resource_id"] . "/code")));
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
      // Markup for button depends on WordPress version
      switch (get_bloginfo('version')) {
        // < WordPress 3.8
        case '3.5':
        case '3.5.1':
        case '3.5.2':
        case '3.6':
        case '3.6.1':
        case '3.7':
        case '3.7.1':
        ?>
          <a
            id="live-editor-file-manager-add-media-link"
            href="<?php echo admin_url("admin-ajax.php") ?>"
            class="button insert-live-editor-media"
            title="Live Editor File Manager"
          >
            <i class="media icon"></i>
            Add Live Editor Media</a>
        <?php
        // >= WordPress 3.8
        default:
        ?>
          <a
            id="live-editor-file-manager-add-media-link"
            href="<?php echo admin_url("admin-ajax.php") ?>"
            class="button insert-live-editor-media add_media"
            title="Live Editor File Manager"
          >
            <span class="wp-media-buttons-icon le-media-buttons-icon"></span>
            Add Live Editor Media</a>
      <?php
      }
    }
  }

  /**
   * Adds Live Editor media button to fullscreen editor.
   */
  function media_button_fullscreen($buttons) {
    $options = get_option(self::OPTIONS_KEY);

    $buttons["live_editor_media"] = array(
      "title"   => "Add Live Editor Media",
      "onclick" => "jQuery('a.insert-live-editor-media').trigger('click');",
      "both"    => true
    );

    // If we're removing the default WordPress media library, remove it from this array as well
    if ($options["hide_add_media_buttons"]) {
      unset($buttons["image"]);
    }

    return $buttons;
  }

  /**
   * Displays resources AJAX page.
   */
  function resources() {
    // AJAX nonce makes sure outside hackers can't get into this script
    check_ajax_referer("resources");

    // Default values for params
    global $params;
    $params = $this->request_params(array("search", "file_types", "collections", "page", "action", "import_success"));

    // We only want published files for the WordPress plugin
    $params['published'] = true;

    $api = $this->api();
    $api->get_file_types('file_types');
    $api->get_collections('collections');
    $api->get_files($params, 'files');
    $api->get_files_count($params, 'files_count');

    try {
      $results = $api->execute_requests();

      // File types are always there, so use them as a litmus test as to whether or not the API is working
      if (!count($results['file_types'])) {
        require_once "views/exceptions/unauthorized.php";
        die();
      }

      // Continue with processing
      $file_types   = $results['file_types'];
      $collections  = $results['collections'];
      $files        = $results['files'];
      $files_count  = $results['files_count'];
      $current_page = $params["page"] ? $params["page"] : 1;
      $per_page     = self::FILES_PER_PAGE;
      $total_pages  = floor($files_count / $per_page) + ($files_count % $per_page ? 1 : 0);

      // Thumbnail URLs need to be grabbed asynchronously from the Live Editor API
      $thumbnail_urls = $api->get_file_thumbnail_urls($files);
    }
    catch (Exception $e) {
      if ($e->getCode() == 401) {
        require_once "views/exceptions/unauthorized.php";
        die();
      }
      else {
        require_once "views/exceptions/error.php";
        die();
      }
    }

    require_once "views/resources/index.php";
    die();
  }

  /**
   * Creates a resource from URL.
   */
  function resources_imports_create() {
    // AJAX nonce makes sure outside hackers can't get into this script
    check_ajax_referer("resources_imports_create");

    // Default values for params
    global $params;
    $params = $this->request_params(array("action", "url"));

    // Validate URL
    $file = $this->api()->create_file_import(array("resource[url]" => $params["url"]));

    if (array_key_exists("errors", $file) === false) {
      $index_params = array(
        "action"         => "resources",
        "post_type"      => $params["post_type"],
        "_ajax_nonce"    => wp_create_nonce("resources"),
        "wp_source"      => $params["wp_source"],
        "import_success" => true
      );

      header("Location: " . admin_url("admin-ajax.php") . "?" . http_build_query($index_params));
    }
    // Validation fails, show form with error
    else {
      $flash["error"] = "There was an error opening the URL. Please try again.";
      global $active_tab;
      $active_tab = "url-form";
      require_once "views/resources/new.php";
    }

    die();
  }

  /**
   * Displays resources/new AJAX page.
   */
  function resources_new() {
    // AJAX nonce makes sure outside hackers can't get into this script
    check_ajax_referer("resources_new");

    // Default values for params
    global $params;
    $params = $this->request_params(array("action", "url"));

    global $active_tab;
    $active_tab = "upload-form";
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
      array($this, "config_page")
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
      $current_user->live_editor_user_api_key,
      $options["subdomain_slug"],
      "Live Editor WordPress Plugin"
    );
  }

  /**
   * Returns an API URL for a given path by prepending the URL base and adding API keys to end.
   */
  private function api_url($path, $escape_amp = false, $path_base = "/api/v1") {
    $options = get_option(self::OPTIONS_KEY);
    $amp = $escape_amp ? "&amp;" : "&";
    $user = wp_get_current_user();

    $url  = $this->url_base() . $path_base . $path;
    $url .= strpos($path, "?") ? $amp : "?";

    if (isset($user->live_editor_user_api_key)) {
      $url .= "user_api_key=" . urlencode($user->live_editor_user_api_key);
    }

    return $url;
  }

  /**
   * Returns URL for retrieving static assets from Live Editor.
   */
  private function asset_url($path) {
    $options = get_option(self::OPTIONS_KEY);
    return getenv('PHP_LIVE_EDITOR_API_PROTOCOL') . $options["subdomain_slug"] . "." . getenv('PHP_LIVE_EDITOR_API_DOMAIN') . "/assets" . $path;
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
   * Helps search `$_GET` and `$_POST` arrays for a given param.
   */
  private function resolve_request_param($param) {
    if (isset($_POST[$param])) {
      return $_POST[$param];
    }
    elseif (isset($_GET[$param])) {
      return $_GET[$param];
    }
    else {
      return null;
    }
  }

  /**
   * Searches `$_GET` and `$_POST` arrays for given params. Automatically takes care of `post_type` and `wp_source`
   * parameters.
   */
  private function request_params($params = array()) {
    $request_params = array();

    $request_params["post_type"] = $this->resolve_request_param("post_type");
    $request_params["wp_source"] = $this->resolve_request_param("wp_source");

    foreach ($params as $param) {
      $request_params[$param] = $this->resolve_request_param($param);
    }

    return $request_params;
  }

  /**
   * Searches `$content` for Live Editor URLs and creates resource usages for them. Appends IDs of Live Editor
   * resources to `$ids` and returns it.
   */
  private function search_and_create_resource_usages($search, $content, $title, $guid, $ids) {
    // Search in post content
    if (preg_match_all($search, $content, $matches, PREG_OFFSET_CAPTURE)) {
      foreach ($matches[0] as $match) {
        // Grab file ID from match
        $file_id = substr($content, $match[1] + strlen($match[0]) + strlen("files/resources/"));
        $file_id = substr($file_id, 0, strpos($file_id, "/"));
        
        // Hang onto this one for later
        array_push($ids, $file_id);
        
        // See if this post is already associated with the file as a file usage
        $file_usages = $this->api()->get_file_usages($file_id);

        $usage_recorded = false;
        foreach ($file_usages as $file_usage) {
          if ($file_usage->usage->url == $guid) {
            $usage_recorded = true;
          }
        }

        // Add usage if it's not already recorded
        if (!$usage_recorded) {
          $file_usage = array(
            "resource_usage[title]" => strlen($title) ? $title : "WordPress Post",
            "resource_usage[usage_attributes][url]" => $guid,
            "resource_usage[notes]" => "WordPress site."
          );

          $this->api()->create_file_external_url($file_id, $file_usage);
        }
      }
    }

    return $ids;
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
      array("id" => "subdomain_slug",         "type" => "text"),
      array("id" => "hide_media_tab",         "type" => "check_box"),
      array("id" => "hide_add_media_buttons", "type" => "check_box")
    );
  }
}

?>