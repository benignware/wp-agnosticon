<?php

namespace benignware\wp\agnosticon;

class Agnosticon_Shortcode {

    public function __construct() {
        add_shortcode('agnosticon', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts) {
        // Extract shortcode attributes (default is an empty name)
        $atts = shortcode_atts([
            'name' => ''
        ], $atts, 'agnosticon');

        // echo $atts['name'];

        $html = get_icon($atts['name']);

        // Return the output for the shortcode
        return $html;
    }

    private function generate_output($name) {
        // Simple output for now, customize later
        if (empty($name)) {
            return '<p>Please provide a name.</p>';
        }

        return '<p>Hello, ' . esc_html($name) . '!</p>';
    }
}

// Initialize the shortcode
add_action('init', function() {
    new Agnosticon_Shortcode();
});
