<?php

namespace benignware\wp\agnosticon;

use RuntimeException;

function get_fa_metadata($name = null) {
    static $metadata = null;

    if ($metadata === null) {
        $path = __DIR__ . '/../../dist/fontawesome-metadata.json'; // Adjust path if needed.

        if (!file_exists($path)) {
            throw new RuntimeException("Font Awesome metadata file not found at $path");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Failed to read Font Awesome metadata file at $path");
        }

        $metadata = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON in Font Awesome metadata file: " . json_last_error_msg());
        }
    }

    // If a specific name is requested, return its metadata if available.
    if ($name !== null) {
        return $metadata[$name] ?? null;
    }

    // Otherwise, return the full metadata.
    return $metadata;
}

add_filter('agnosticon_variants', function($variants, $icon) {
    // Extract the icon name without the prefix
    $name = $icon->name;

    // Get metadata for the specific icon
    $iconData = get_fa_metadata($name);

    if ($iconData !== null) {
        // Check if it is a brand
        if (!empty($iconData['styles']) && in_array('brands', $iconData['styles'], true)) {
            return ['brands'];
        }

        // Add "regular" if available
        if (!empty($iconData['styles']) && in_array('regular', $iconData['styles'], true)) {
            $variants[] = 'regular';
        }
    }

    return $variants;
}, 10, 2);
