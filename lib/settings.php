<?php
namespace benignware\wp\agnosticon;

add_action( 'admin_menu', __NAMESPACE__ . '\\register_icons_page' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_icons_scripts' );
add_action( 'wp_ajax_agnosticon_search', __NAMESPACE__ . '\\handle_icon_search' );

function register_icons_page() {
    add_theme_page(
        __( 'Icons', 'agnosticon' ),
        __( 'Icons', 'agnosticon' ),
        'manage_options',
        'agnosticon-icons',
        __NAMESPACE__ . '\\render_icons_page'
    );
}

function render_icons_page() {
    // Fetch all icons server-side
    $icons = get_icons('');
    ?>
    <div class="wrap agnosticon-settings">
        <h1><?php esc_html_e( 'Available Icons', 'agnosticon' ); ?></h1>
        <input type="text" id="agnosticon-settings-icon-search" placeholder="<?php esc_attr_e( 'Search icons...', 'agnosticon' ); ?>" />
        <div class="agnosticon-search-header">
          <div class="agnosticon-settings-spinner">
            <span class="dashicons dashicons-update"></span>
            <div class="agnosticon-settings-loading-message">Loading...</div>
          </div>
        </div>
        <div class="agnosticon-settings-result-count"></div>
        <div id="agnosticon-settings-results"></div>
    </div>
    <?php
}

function enqueue_icons_scripts( $hook ) {
  if ( $hook !== 'appearance_page_agnosticon-icons' ) {
      return;
  }

  wp_enqueue_style('dashicons');

  // Enqueue the JavaScript and CSS files
  wp_enqueue_script( 'agnosticon-settings', plugin_dir_url( __FILE__ ) . 'settings.js', array(), '1.0', true );

  // Localize script to pass data to the JS
  wp_localize_script('agnosticon-settings', 'AgnosticonSettings', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'spinnerUrl' => admin_url('images/spinner.gif'), // URL to the WordPress spinner image
  ));

  // Localize script to provide the AJAX URL
  wp_localize_script( 'agnosticon-settings', 'AgnosticonSettings', array(
      'ajaxUrl' => admin_url('admin-ajax.php'),
  ));

  // Enqueue CSS
  wp_enqueue_style( 'agnosticon-settings', plugin_dir_url( __FILE__ ) . 'settings.css' );


}

