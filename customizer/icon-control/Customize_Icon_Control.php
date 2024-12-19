<?php

namespace benignware\wp\agnosticon;

use WP_Customize_Control;

add_action('customize_register', function () {
    if (!class_exists('WP_Customize_Control')) {
        return;
    }

    // Define the Customize_Icon_Control class
    class Customize_Icon_Control extends WP_Customize_Control {
        public $type = 'icon';

        /**
         * Enqueue scripts and styles for the control.
         */
        public function enqueue() {
            $plugin_dir_url = plugin_dir_url(__FILE__);

            // Enqueue JavaScript
            wp_enqueue_script(
                'customize-icon-control-js',
                $plugin_dir_url . 'Customize_Icon_Control.js',
                [],
                null,
                true
            );

            // Enqueue CSS
            wp_enqueue_style(
                'customize-icon-control-css',
                $plugin_dir_url . 'Customize_Icon_Control.css',
                [],
                null
            );

            // Register and enqueue the style specifically for the block editor
            wp_register_style(
                'agnosticon',
                admin_url('admin-ajax.php') . '?action=agnosticon_css',
                [],
                null
            );
            wp_enqueue_style('agnosticon');

            wp_enqueue_style(
                'agnosticon-main',
                plugins_url('dist/agnosticon-main.css', __DIR__ . '/../../agnosticon.php'),
                []
            );
        }

        /**
         * Render the control's content.
         */
        public function render_content() {
            // Retrieve the current icon value
            $current_icon_id = $this->value();
            $icon_preview_html = '';
            

            if ($current_icon_id) {
                $icon_data = get_icon_meta($current_icon_id);

                // Prepare icon preview if data exists
                if ($icon_data) {
                    $icon_preview_html = $this->get_icon_html($icon_data);
                }
            }
            ?>
            <label>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
                <div class="icon-selector-container">
                    <input 
                        type="text" 
                        class="icon-search <?= $current_icon_id ? 'has-icon' : ''; ?>" 
                        placeholder="<?php esc_attr_e('Search for an icon...', 'your-textdomain'); ?>" 
                        value="<?php echo esc_attr($current_icon_id); ?>" 
                        data-hidden-input-id="<?php echo esc_attr($this->id); ?>" 
                    />
                    <span class="icon-preview"><?php echo $icon_preview_html; ?></span>
                    <span class="reset-icon dashicons dashicons-no"></span> <!-- Close button using Dashicons -->
                    <div class="icon-results" aria-live="polite"></div>
                </div>
            </label>
            <?php
        }

        /**
         * Generate HTML for the icon based on its data.
         *
         * @param array $icon_data
         * @return string
         */
        public function get_icon_html($icon_data) {
            $code = htmlspecialchars_decode('&#x' . $icon_data->char . ';');

            return sprintf('<i style="%s" data-agnosticon-code="%s"></i>', esc_attr($icon_data->style), esc_html($code));
        }
    }
});
