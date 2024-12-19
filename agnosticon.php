<?php
/**
 * Plugin Name: Agnosticon
 * Plugin URI: http://github.com/benignware/wp-agnosticon
 * Description: Universal icons for WordPress
 * Version: 0.0.16
 * Author: Rafael Nowrotek, Benignware
 * Author URI: http://benignware.com
 * License: MIT
*/

namespace benignware\wp\agnosticon;

require 'int/font-awesome/font-awesome.php';
require 'lib/lookup.php';
require 'lib/resources.php';


require 'lib/actions.php';
require 'lib/shortcode.php';
require 'lib/search.php';
require 'lib/settings.php';
require 'lib/blocks.php';
require 'lib/functions.php';

require 'customizer/customizer.php';


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
