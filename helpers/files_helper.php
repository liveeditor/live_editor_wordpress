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
 * Returns link markup for inserting link into post, but a "Processing file..." message if it's not ready.
 */
function insert_into_post_link($file, $params) {
  // If encoded and final, display link
  if ($file->final) {
    return
      '<a
        href="' . admin_url("admin-ajax.php") . '"
        class="insert-file modal-ignore"
        data-file-id="' . $file->id . '"
        data-action="editor_code"
        data-post-type="' . $params['post_type'] . '"
        data-nonce="' . wp_create_nonce('editor_code') . '"
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
 * Returns `disabled` class if there is no previous page.
 */
function prev_page_link_class($current_page) {
  return $current_page == 1 ? "disabled" : "";
}

?>