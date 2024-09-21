<?php

namespace benignware\wp\agnosticon;

function _agnosticon_data_action() {

  global $wp_styles;



  \WP_Screen::get('front')->set_current_screen();

  ob_start();
  wp_head();
  ob_end_clean();

  $resources = _agnosticon_load_resources();


  $data = _agnosticon_parse_resources($resources);
  $output = json_encode($data, JSON_PRETTY_PRINT);

  header('Content-Type: application/json');

  echo $output;

  wp_die();
}

add_action('wp_ajax_nopriv__agnosticon_data', 'benignware\wp\agnosticon\_agnosticon_data_action');
add_action('wp_ajax__agnosticon_data', 'benignware\wp\agnosticon\_agnosticon_data_action');


function agnosticon_css() {
  global $__agnosticon__;

  _agnosticon_load();

  if (!isset($__agnosticon__)) {
    return '';
  }

  $icon_sets = $__agnosticon__->sets;
  $css = array_reduce(array_values($icon_sets), function($result, $icon_set) {
    foreach ($icon_set->fonts as $font) {
      $result.= $font->css;
    };

    return $result;
  }, '');

  header('Content-Type: text/css');

  echo $css;

  die();
}

add_action( 'wp_ajax_agnosticon_css',        'benignware\wp\agnosticon\agnosticon_css' );
add_action( 'wp_ajax_nopriv_agnosticon_css', 'benignware\wp\agnosticon\agnosticon_css' );




function _agnosticon_search_action() {
  global $__agnosticon__;

  _agnosticon_load();

  $s = stripslashes( $_POST['search'] );

  $icons = isset($__agnosticon__) ? $__agnosticon__->icons : [];

  $items = [];

  foreach($icons as $icon) {
    if (preg_match('~' . preg_quote($s, '~') . '~', $icon->id)) {
      $items[] = (object) array_merge(
        (array) $icon,
        [
          'label' => $icon->name,
          'value' => $icon->id,
        ]
        );
    }
  }

	wp_send_json_success( $items );
}

add_action( 'wp_ajax_agnosticon_search',        'benignware\wp\agnosticon\_agnosticon_search_action' );
add_action( 'wp_ajax_nopriv_agnosticon_search', 'benignware\wp\agnosticon\_agnosticon_search_action' );
