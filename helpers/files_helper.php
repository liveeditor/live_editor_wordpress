<?php
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

?>