<?php

namespace benignware\wp\agnosticon;

function determine_render_type(object $icon, array $criteria): string {
    $icon = (array) $icon;

    foreach ($criteria as $type => $attributes) {
        foreach ($attributes as $attr) {
            if (isset($icon[$attr])) {
                $value = $icon[$attr];

                // Check if the value is an array or object
                if (is_array($value)) {
                    foreach ($value as $innerValue) {
                        if (is_numeric($innerValue) && floatval($innerValue) > 0) {
                            return $type;
                        }
                    }
                } elseif (is_numeric($value) && floatval($value) > 0) {
                    return $type;
                }
            }
        }
    }

    // Default type if none match
    return 'default';
}


function render_block_list($content, $block) {
    if ($block['blockName'] !== 'core/list') {
        return $content;
    }

    $attrs = $block['attrs'];
    $icon = $attrs['icon'] ?? null;

    if (!$icon || !isset($icon['id'])) {
        return $content;
    }

    $icon_data = get_icon_meta($icon['id']);

    if (!$icon_data) {
        return $content;
    }

    $icon_data = (object) array_merge(
        [
            'char' => null,
            'font_family' => null,
            'font_weight' => null,
            'fontSize' => null,
            'color' => null,
            'backgroundColor' => null,
            'borderColor' => null,
            'borderRadius' => null,
            'padding' => null,
        ],
        (array) $icon_data,
        $icon
    );

    $render_type = determine_render_type($icon_data, [
        'grid' => ['size', 'borderColor', 'backgroundColor', 'padding', 'gap'],
    ]);

    // Parse the HTML content
    $doc = new \DOMDocument();
    @$doc->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));

    // Find the first <ul> element
    $ul = $doc->getElementsByTagName('ul')->item(0);
    
    if (!$ul) {
        return $content;
    }

    // Define attributes and styles as objects
    $attributes = [
        'class' => sprintf('agnosticon-list is-%s', $render_type),
    ];

    $char = isset($icon_data->char) ? "'" . mb_convert_encoding("&#x{$icon_data->char};", 'UTF-8', 'HTML-ENTITIES') . "'" : null;
    $code = $char;

    $styles = [
        '--agnosticon-char' => $char,
        '--agnosticon-code' => $code,
        '--agnosticon-font-family' => $icon_data->font_family ?? '',
        '--agnosticon-font-weight' => $icon_data->font_weight ?? '',
        '--agnosticon-font-size' => $icon_data->size ? "{$icon_data->size}px" : '',
        '--agnosticon-color' => $icon_data->color ?? '',
        '--agnosticon-background-color' => $icon_data->backgroundColor ?? '',
        '--agnosticon-border-color' => $icon_data->borderColor ?? '',
        // '--agnosticon-border-radius' => $icon_data->borderRadius ?? null,
        '--agnosticon-padding' => $icon_data->padding ? "{$icon_data->padding['left']}" : '',
    ];

    // Reduce attributes and styles to strings
    $existing_classes = $ul->getAttribute('class');
    $attributes['class'] = trim("$existing_classes {$attributes['class']}");

    $style_text = array_reduce(
        array_keys($styles),
        function ($carry, $key) use ($styles) {
            $carry[] = "$key: {$styles[$key]}";
            return $carry;
        },
        []
    );

    $style_text = implode('; ', $style_text);

    // Apply attributes and styles
    $ul->setAttribute('class', $attributes['class']);
    if ($style_text) {
        $existing_style = $ul->getAttribute('style');
        $ul->setAttribute('style', trim($existing_style) . '; ' . $style_text);
    }

    $items = iterator_to_array($ul->getElementsByTagName('li'));

    foreach ($items as $item) {
        // Check if the item's first child is the only child and a <div>
        if ($item->childNodes->length === 1 && $item->firstChild->nodeName === 'div') {
            continue; // Already wrapped in a <div>, skip
        }
        
        // Create a new <div> and move all child nodes into it
        $div = $doc->createElement('div');
        while ($item->firstChild) {
            $div->appendChild($item->firstChild); // Move each child into the <div>
        }
        $item->appendChild($div); // Append the new <div> to the <li>
    }

    // Serialize back to HTML
    return $doc->saveHTML($doc->documentElement);
}

add_filter('render_block', __NAMESPACE__ . '\render_block_list', 10, 2);