<?php
/**
 * Returns `active` class if `$tab` matches `$active_tab`.
 */
function active_tab_class($tab, $active_tab) {
  return $tab == $active_tab ? "active" : "";
}

/**
 * Renders nested set collections data as a nested unordered list structure.
 * http://stackoverflow.com/a/1790201/175981
 */
function collections_nested_set($collections, $selected_collections) {
  $current_depth = 0;
  $counter = 0;

  $result = '<ul>';

  foreach ($collections as $node){
    $node_depth = $node->depth;
    $node_name  = $node->name;
    $node_id    = $node->id;

    if ($node_depth == $current_depth) {
      if ($counter > 0) {
        $result .= '</li>';
      }
    }
    elseif ($node_depth > $current_depth) {
      $result .= '<ul>';
      $current_depth = $current_depth + ($node_depth - $current_depth);
    }
    elseif ($node_depth < $current_depth) {
      $result .= str_repeat('</li></ul>',$current_depth - $node_depth) . '</li>';
      $current_depth = $current_depth - ($current_depth - $node_depth);
    }

    $result .= '<li id="collection-' . $node_id . '">'
            .    '<label>'
            .      '<input '
            .        'type="checkbox" '
            .        'name="collections[]" '
            .        'value="' . $node_id . '" ';
                     if (!count($selected_collections) || array_search($node_id, $selected_collections) !== false) {
                       $result .= 'checked="checked" ';
                     }
    $result .=     '/> '
            .      $node_name
            .    '</label>';
    $counter++;
  }
  
  $result .= str_repeat('</li></ul>', $node_depth) . '</li>';
  $result .= '</ul>';

  return $result;
}

/**
 * Returns link markup for embedding file into post, but a "Processing file..." message if it's not ready.
 * This is for a generic "Insert into post" link for any file.
 */
function insert_into_post_link($file, $params) {
  // If encoded and final, display link
  if ($file->final) {
    return
      '<a
        href="' . admin_url("admin-ajax.php") . '?action=editor_code"
        class="insert-file modal-ignore"
        data-file-id="' . $file->id . '"
        data-nonce-name="editor_code"
      >
        Insert into ' . $params["post_type"] . '
      </a>';
  }
  else {
    return '<em>Processing file&hellip; Please wait.</em>';
  }
}

/**
 * Returns `disabled` class if there is no next page.
 */
function next_page_link_class($current_page, $total_pages) {
  return $current_page == $total_pages ? "disabled" : "";
}

/**
 * Returns hidden fields for `post_format` and `previewable` if their params are present.
 */
function post_format_hidden_form_fields($params) {
  $fields = "";

  if (array_key_exists("post_format", $params) && strlen($params["post_format"])) {
    $fields = '<input type="hidden" name="post_format" value="' . $params["post_format"] . '" />';
  }

  if (array_key_exists("previewable", $params) && strlen($params["previewable"])) {
    $fields .= '<input type="hidden" name="previewable" value="' . $params["previewable"] . '" />';
  }

  return $fields;
}

/**
 * Returns `data-post-format` and `data-previewable` attributes if their params are present.
 */
function post_format_link_attributes($params) {
  $attributes = "";

  if (array_key_exists("post_format", $params) && strlen($params["post_format"])) {
    $attributes = ' data-post-format="' . $params["post_format"] . '"';
  }

  if (array_key_exists("previewable", $params) && strlen($params["previewable"])) {
    $attributes .= ' data-previewable="' . $params["previewable"] . '"';
  }

  return $attributes;
}

/**
 * Returns URL params for `post_format` and `previewable` if their params are present.
 */
function post_format_url_params($params) {
  $url_params = "";

  if (array_key_exists("post_format", $params) && strlen($params["post_format"])) {
    $url_params = '&amp;post_format=' . $params["post_format"];
  }

  if (array_key_exists("previewable", $params) && strlen($params["previewable"])) {
    $url_params .= '&amp;previewable=' . $params["previewable"];
  }

  return $url_params;
}

/**
 * Returns `disabled` class if there is no previous page.
 */
function prev_page_link_class($current_page) {
  return $current_page == 1 ? "disabled" : "";
}

/**
 * Returns link markup for embedding file into post, but a "Processing file..." message if it's not ready.
 * This is a "Select [Post Type]" link for a specific post format like image, audio, video, etc.
 */
function select_post_format_link($file, $params) {
  // If encoded and final, display link
  if ($file->final) {
    return
      '<a
        href="' . admin_url("admin-ajax.php") . '?action=editor_code"
        class="select-file modal-ignore"
        data-file-id="' . $file->id . '"
        data-post-format="' . $params["post_format"] . '"
        data-nonce-name="editor_code"
      >
        Select ' . $params["post_format"] . '
      </a>';
  }
  else {
    return '<em>Processing file&hellip; Please wait.</em>';
  }
}

?>