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

  private $user_api_key;
  private $subdomain_slug;
  private $user_agent;
  private $request_queue;

  /**
   * Constructor.
   */
  function __construct($user_api_key, $subdomain_slug, $user_agent = "Live Editor API PHP Wrapper") {
    $this->user_api_key = $user_api_key;
    $this->subdomain_slug = $subdomain_slug;
    $this->user_agent = $user_agent;
    $this->request_queue = array();
  }

  /**
   * Posts a new file usage record as an external URL.
   */
  function create_file_external_url($file_id, $file_usage, $enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/" . $file_id . "/external-urls.json", "POST", $file_usage);
    }
    else {
      return $this->make_request("/resources/" . $file_id . "/external-urls.json", "POST", $file_usage);
    }
  }

  /**
   * Posts new file import record.
   */
  function create_file_import($file, $enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/imports.json", "POST", $file);
    }
    else {
      return $this->make_request("/resources/imports.json", "POST", $file);
    }
  }

  /**
   * Deletes a file usage record with a given ID.
   */
  function delete_file_external_url($file_id, $id, $enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/" . $file_id . "/external-urls/" . $id, "DELETE");
    }
    else {
      return $this->make_request("/resources/" . $file_id . "/external-urls/" . $id, "DELETE");
    }
  }

  /**
   * Executes requests stored in request queue.
   * http://kahthong.com/2012/04/php-parallel-curl-request-curlmultiexec-and-curlmultigetcontent
   */
  function execute_requests() {
    $results = array();
    $mh = curl_multi_init();

    foreach ($this->request_queue as $key => $val) {
      curl_multi_add_handle($mh, $val);
    }

    $running = null;
    do {
      curl_multi_exec($mh, $running);
    }
    while ($running > 0);

    // Get content and remove handles
    foreach ($this->request_queue as $key => $val) {
      $result   = curl_multi_getcontent($val);
      $status   = curl_getinfo($val, CURLINFO_HTTP_CODE);
      $error    = curl_error($val);
      $error_no = curl_errno($val);

      if ($val === false) {
        throw new Exception($error, $error_no);
      }
      elseif ($status == 401) {
        throw new Exception("Unauthorized", 401);
      }

      if (isset($result->message)) {
        throw new Exception($result->message, $status);
      }

      $results[$key] = json_decode($result);
      curl_multi_remove_handle($mh, $val);
    }

    curl_multi_close($mh);
    $this->request_queue = array();
    return $results;
  }

  /**
   * Returns array of collections in user's account.
   */
  function get_collections($enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/collections.json", "GET");
    }
    else {
      return $this->make_request("/resources/collections.json", "GET");
    }
  }

  /**
   * Returns array of domains associated with user's account.
   */
  function get_domains($enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/domains.json", "GET");
    }
    else {
      return $this->make_request("/domains.json", "GET");
    }
  }

  /**
   * Returns array of files associated with a given URL.
   */
  function get_external_urls_for_url($url, $enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/external-urls.json?url=" . urlencode($url), "GET");
    }
    else {
      return $this->make_request("/external-urls.json?url=" . urlencode($url), "GET");
    }
  }

  /**
   * Returns array of file types.
   */
  function get_file_types($enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resource-types.json", "GET");
    }
    else {
      return $this->make_request("/resource-types.json", "GET");
    }
  }

  /**
   * Returns URL for a given file.
   */
  function get_file_url($file_id, $style = "original", $enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/" . $file_id . "/url/" . $style . ".json", "GET");
    }
    else {
      return $this->make_request("/resources/" . $file_id . "/url/" . $style . ".json", "GET");
    }
  }

  /**
   * Fetches URLs for thumbnails for `$files`. Returns associative array with key = file ID and value = URL for file.
   */
  function get_file_thumbnail_urls($files, $style = "medium") {
    foreach ($files as $file) {
      $this->get_file_url($file->id, $style, $file->id);
    }

    return $this->execute_requests();
  }

  /**
   * Returns array of file usages for a given file ID.
   */
  function get_file_usages($file_id, $enqueue_key = null) {
    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/" . $file_id . "/usages.json", "GET");
    }
    else {
      return $this->make_request("/resources/" . $file_id . "/usages.json", "GET");
    }
  }

  /**
   * Returns array of files based on search params.
   */
  function get_files($params = array(), $enqueue_key = null) {
    $params = $this->clean_file_params($params);

    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources.json", "GET", $params);
    }
    else {
      return $this->make_request("/resources.json", "GET", $params);
    }
  }

  /**
   * Returns count of files based on search params.
   */
  function get_files_count($params = array(), $enqueue_key = null) {
    $params = $this->clean_file_params($params);

    if ($enqueue_key) {
      $this->enqueue_request($enqueue_key, "/resources/count.json", "GET", $params);
    }
    else {
      return $this->make_request("/resources/count.json", "GET", $params);
    }
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
   * Returns cURL options array for a given URL, method, and params.
   */
  private function curl_opts($url, $method, $params) {
    $options = self::$CURL_OPTS;
    $options[CURLOPT_URL] = $this->api_url($url);
    $options[CURLOPT_USERAGENT] = $this->user_agent;
    $options[CURLOPT_SSL_VERIFYPEER] = false; // TODO: Figure out a better fix for this

    switch ($method) {
      case "POST":
        $options[CURLOPT_POST] = true;
        break;
      case "PUT":
        $options[CURLOPT_PUT] = true;
        break;
      case "DELETE":
        $options[CURLOPT_CUSTOMREQUEST] = "DELETE";
        break;
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

    return $options;
  }

  /**
   * Enqueues a URL, HTTP verb, and data for processing. Call `execute_requests` to run enqueued requests in parallel
   * and return their results.
   */
  private function enqueue_request($enqueue_key, $url, $method = 'GET', $params = array()) {
    $request = curl_init();
    curl_setopt_array($request, $this->curl_opts($url, $method, $params));
    $this->request_queue[$enqueue_key] = $request;
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
    curl_setopt_array($ch, $this->curl_opts($url, $method, $params));

    $result   = curl_exec($ch);
    $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    $error_no = curl_errno($ch);
    curl_close($ch);

    if ($result === false) {
      throw new Exception($error, $error_no);
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
   * Returns an API URL for a given path by prepending the URL base and adding API key to end.
   */
  private function api_url($path, $escape_amp = false) {
    $amp = $escape_amp ? "&amp;" : "&";

    $url  = $this->url_base() . $path;
    $url .= strpos($path, "?") ? $amp : "?";
    $url .= "user_api_key=" . urlencode($this->user_api_key);

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