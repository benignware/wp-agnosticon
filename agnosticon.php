<?php
/**
 * Plugin Name: Agnosticon
 * Plugin URI: http://github.com/benignware/wp-agnosticon
 * Description: Universal icons for WordPress
 * Version: 0.0.4
 * Author: Rafael Nowrotek, Benignware
 * Author URI: http://benignware.com
 * License: MIT
*/

namespace benignware\wp\agnosticon;

require 'int/font-awesome.php';
require 'lib/resources.php';
require 'lib/actions.php';
require 'lib/shortcode.php';


function get_data() {
  global $__agnosticon__;

  if (!isset($__agnosticon__)) {
    _agnosticon_load();
  }

  return $__agnosticon__;
}

function get_icons($query = null) {
  $data = get_data();

  if (!$data) {
    return [];
  }

  $icons = $data->icons;

  // Look-up table for related icons
  $related_terms = [
    'cart' => ['basket', 'shopping-cart'],
    'shopping' => ['commerce', 'shop'],
    // Add more related terms as needed
  ];

  // If there's a query, tokenize and search
  if ($query) {
    $tokens = explode(' ', strtolower($query)); // Tokenize the query string

    $icons = array_filter($icons, function($icon) use ($tokens, $related_terms) {
      $relevance = 0;
      $icon_name = strtolower($icon->id);
      
      foreach ($tokens as $token) {
        // Exact match gets a high score
        if (strpos($icon_name, $token) !== false) {
          $relevance += 2; 
        }

        // Check related terms for fuzzy matches
        if (isset($related_terms[$token])) {
          foreach ($related_terms[$token] as $related) {
            if (strpos($icon_name, $related) !== false) {
              $relevance += 1; // Fuzzy match gets a lower score
            }
          }
        }
      }

      // Assign relevance score to the icon (without mutating the original)
      if ($relevance > 0) {
        $icon->relevance = $relevance;
        return true;
      }

      return false; // Exclude icons that have zero relevance
    });

    // Sort the icons by relevance in descending order
    usort($icons, function($a, $b) {
      return $b->relevance <=> $a->relevance;
    });
  }

  return $icons;
}

function get_icon_meta($query) {
  $icons = get_icons($query);

  if (count($icons)) {
    return $icons[0];
  }

  return null;
}


function get_icon($query) {
  $icon = get_icon_meta($query);

  if (!$icon) {
    return '';
  }

  return '<i class="' . $icon->class . '"></i>';
}

function get_agnosticon_data($id) {
  global $__agnosticon__;

  _agnosticon_load();

  if (!isset($__agnosticon__)) {
    return '';
  }

  $icons = $__agnosticon__->icons;

  if (isset($icons[$id])) {
    $icon = $icons[$id];

    return $icon;
  }

  return null;
}

function get_agnosticon($id, $attrs = []) {
  $icon = get_agnosticon_data($id);

  if (!$icon) {
    return null;
  }

  $is_admin = is_admin();

  if ($is_admin) {
    $attrs = array_merge($attrs, [
      'style' => isset($attrs['style']) ? $attrs['style'] . '; ' . $icon->style : $icon->style,
    ]);
  } else {
    $attrs = array_merge($attrs, [
      'class' => isset($attrs['class']) ? $attrs['class'] . ' ' . $icon->class : $icon->class,
    ]);
  }

  $attrs_str = implode(' ', array_map(function($key, $value) {
    return "$key=\"$value\"";
  }, array_keys($attrs), array_values($attrs)));

  if ($is_admin) {
    return "<span $attrs_str>{$icon->entity}</span>";
  } else {
    return "<i $attrs_str> </i>";
  }

  return '';
}


function enqueue_scripts() {
  wp_register_style(
		'agnosticon',
		admin_url( 'admin-ajax.php' ) . '?action=agnosticon_css',
		array(),
	);
}

add_action( 'admin_enqueue_scripts', 'benignware\wp\agnosticon\enqueue_scripts', 0);



function enqueue_block_editor_assets() {
  wp_enqueue_script(
      'agnosticon-block-editor-assets',
      plugin_dir_url( __FILE__ ) . 'dist/agnosticon-editor.js',
      [ 'wp-blocks', 'wp-rich-text', 'wp-element', 'wp-editor', 'wp-components' ],
      filemtime( plugin_dir_path( __FILE__ ) . 'dist/agnosticon-editor.js' ), // Version based on file modification time
      true // Load in footer
  );

    wp_register_style(
      'agnosticon',
      admin_url( 'admin-ajax.php' ) . '?action=agnosticon_css',
      array(),
      null // You can specify a version if needed
  );

  // Enqueue the style specifically for the block editor
  wp_enqueue_style('agnosticon');
}
add_action( 'enqueue_block_editor_assets', 'benignware\wp\agnosticon\enqueue_block_editor_assets' );




function enqueue_agnosticon_styles() {
    wp_register_style(
        'agnosticon',
        admin_url('admin-ajax.php') . '?action=agnosticon_css',
        array(),
        null // You can specify a version if needed
    );

    wp_enqueue_style('agnosticon');
}

add_action('enqueue_block_assets', 'benignware\wp\agnosticon\enqueue_agnosticon_styles');
