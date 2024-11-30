<?php
/**
 * Plugin Name: Agnosticon
 * Plugin URI: http://github.com/benignware/wp-agnosticon
 * Description: Universal icons for WordPress
 * Version: 0.0.12
 * Author: Rafael Nowrotek, Benignware
 * Author URI: http://benignware.com
 * License: MIT
*/

namespace benignware\wp\agnosticon;

require 'int/font-awesome/font-awesome.php';
require 'lib/resources.php';
require 'lib/actions.php';
require 'lib/shortcode.php';
require 'lib/search.php';
require 'lib/settings.php';
require 'lib/blocks.php';

function get_data() {
  global $__agnosticon__;

  if (!isset($__agnosticon__)) {
    _agnosticon_load();
  }

  return $__agnosticon__;
}


function get_icon_meta($query = "", $variant = "") {
  $icons = get_icons($query, $variant);

  if (count($icons) > 0 && isset($icons[0])) {
    return $icons[0];
  }

  return null;
}


function get_icon($query, $attrs = []) {
  [$search, $variant] = preg_split('/\:/', $query);
  $icon = get_icon_meta($search, $variant);

  if (!$icon) {
    return '';
  }

  $attrs['class'] = isset($attrs['class']) ? $attrs['class'] . ' ' . $icon->class : $icon->class;

  $attrs_str = implode(' ', array_map(function($key, $value) {
    return "$key=\"$value\"";
  }, array_keys($attrs), array_values($attrs)));

  return "<i $attrs_str> </i>";
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
  $script_handle = 'agnosticon-block-editor-assets';
  
  // Enqueue the block editor script
  wp_enqueue_script(
      $script_handle,
      plugin_dir_url(__FILE__) . 'dist/agnosticon-editor.js',
      ['wp-blocks', 'wp-rich-text', 'wp-element', 'wp-editor', 'wp-components'],
      filemtime(plugin_dir_path(__FILE__) . 'dist/agnosticon-editor.js'), // Version based on file modification time
      true // Load in footer
  );

  // Localize script with icon data
  $icon_data = get_data();
  wp_localize_script(
      $script_handle,
      'AgnosticonData',
      $icon_data 
  );

  // Register and enqueue the style specifically for the block editor
  wp_register_style(
      'agnosticon',
      admin_url('admin-ajax.php') . '?action=agnosticon_css',
      [],
      null // No specific version
  );
  wp_enqueue_style('agnosticon');

  wp_enqueue_style( 
    'agnosticon-editor',
    plugin_dir_url(__FILE__) . 'dist/agnosticon-editor.css',
    ['wp-edit-blocks'],
    null // No specific version
  );
}
add_action('enqueue_block_editor_assets', 'benignware\wp\agnosticon\enqueue_block_editor_assets');


function enqueue_agnosticon_styles() {
  wp_enqueue_style(
    'agnosticon-dynamic',
    admin_url('admin-ajax.php') . '?action=agnosticon_css',
    [],
  );
  wp_enqueue_style(
    'agnosticon-main',
    plugins_url('dist/agnosticon-main.css', __FILE__),
    [],
  );
}

add_action('enqueue_block_assets', 'benignware\wp\agnosticon\enqueue_agnosticon_styles');
add_action('admin_enqueue_scripts', 'benignware\wp\agnosticon\enqueue_agnosticon_styles');
