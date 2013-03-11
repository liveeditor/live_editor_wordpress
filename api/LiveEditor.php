<?php

class LiveEditor {  
  /**
   * Default options for cURL requests.
   *
   * @var array
   */
  protected static $CURL_OPTS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 60,
  );

  private $account_api_key;
  private $user_api_key;
  private $subdomain_slug;
  private $user_agent;

  /**
   * Constructor.
   */
  function __construct($account_api_key, $user_api_key, $subdomain_slug, $user_agent = "Live Editor API PHP Wrapper") {
    $this->account_api_key = $account_api_key;
    $this->user_api_key = $user_api_key;
    $this->subdomain_slug = $subdomain_slug;
    $this->user_agent = $user_agent;
  }

  /**
   * Posts a new file usage record as an external URL.
   */
  function create_external_url($file_id, $external_url) {
    return $this->make_request("/resources/" . $file_id . "/external-urls.json", "POST", $external_url);
  }

  /**
   * Posts new file import record.
   */
  function create_file_import($file) {
    return $this->make_request("/resources/imports.json", "POST", $file);
  }

  /**
   * Deletes a file usage record with a given ID.
   */
  function delete_file_external_url($file_id, $id) {
    return $this->make_request("/resources/" . $file_id . "/external-urls/" . $id, "DELETE");
  }

  /**
   * Returns array of collections in user's account.
   */
  function get_collections() {
    return $this->make_request("/resources/collections.json", "GET");
  }

  /**
   * Returns array of domains associated with user's account.
   */
  function get_domains() {
    return $this->make_request("/domains.json", "GET");
  }

  /**
   * Returns array of file types.
   */
  function get_file_types() {
    return $this->make_request("/resource-types.json", "GET");
  }

  /**
   * Returns URL for a given file.
   */
  function get_file_url($file_id, $style = "original") {
    return $this->make_request("/resources/" . $file_id . "/url/" . $style . ".json", "GET");
  }

  /**
   * Returns array of file usages for a given file ID.
   */
  function get_file_usages($file_id) {
    return $this->make_request("/resources/" . $file_id . "/usages.json", "GET");
  }

  /**
   * Returns array of files associated with a given URL.
   */
  function get_file_usages_for_url($url) {
    return $this->make_request("/external-urls.json?url=" . urlencode($url), "GET");
  }

  /**
   * Returns array of files based on search params.
   */
  function get_files($params = array()) {
    $params = $this->clean_file_params($params);

    return $this->make_request("/resources.json", "GET", $params);
  }

  /**
   * Returns count of files based on search params.
   */
  function get_files_count($params = array()) {
    $params = $this->clean_file_params($params);

    return $this->make_request("/resources/count.json", "GET", $params);
  }

  /**
   * Cleans up file query params.
   */
  private function clean_file_params($params) {
    if (array_key_exists("file_types", $params)) {
      $params["resource_type_ids"] = $params["file_types"];
    }

    if (array_key_exists("collections", $params)) {
      $params["collection_ids"] = $params["collections"];
    }

    return $params;
  }

  /**
   * Makes a HTTP request.
   * This method can be overriden by extending classes if required.
   *
   * @param  string $url
   * @param  string $method
   * @param  array  $params
   * @return object
   * @throws LiveEditor\Exception
   */
  protected function make_request($url, $method = 'GET', $params = array()) {
    $ch = curl_init();
    $options = self::$CURL_OPTS;
    $options[CURLOPT_URL] = $this->api_url($url);
    $options[CURLOPT_USERAGENT] = $this->user_agent;

    if ($method == 'POST' || $method == "DELETE") {
      $options[CURLOPT_POST] = true;
    }
    else if ($method == 'PUT') {
      $options[CURLOPT_PUT] = true;
    }

    if (!empty($params)) {
      switch ($method) {
        case "POST":
        case "PUT":
          $options[CURLOPT_POSTFIELDS] = $params;
          break;
        default:
          $options[CURLOPT_URL] .= '&' . http_build_query($params, null, '&');
      }
    }

    curl_setopt_array($ch, $options);

    if ($method == "DELETE") {
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($result === false) {
      throw new Exception(curl_error($ch), curl_errno($ch));
    }
    elseif ($status == 401) {
      throw new Exception("Unauthorized", 401);
    }

    $result = json_decode($result);

    if (isset($result->message)) {
      throw new Exception($result->message, $status);
    }

    return $result;
  }

  /**
   * Returns an API URL for a given path by prepending the URL base and adding API keys to end.
   */
  private function api_url($path, $escape_amp = false) {
    $amp = $escape_amp ? "&amp;" : "&";

    $url  = $this->url_base() . $path;
    $url .= strpos($path, "?") ? $amp : "?";
    $url .= $amp . "account_api_key=" . urlencode($this->account_api_key);
    $url .= $amp . "user_api_key=" . urlencode($this->user_api_key);

    return $url;
  }

  /**
   * Returns protocol and domain for Live Editor (e.g., `https://api.liveeditorcms.com`).
   */
  private function url_base() {
    return getenv('PHP_LIVE_EDITOR_API_PROTOCOL') . $this->subdomain_slug . "." . getenv('PHP_LIVE_EDITOR_API_DOMAIN') . "/api/v1";
  }
}

?>