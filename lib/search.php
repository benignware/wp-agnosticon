<?php

namespace benignware\wp\agnosticon;

function get_variant_aliases($variants, $variant = null) {
    // Step 1: Identify the default variant
    $default_variant_key = null;
    $default_variant = null;
    foreach ($variants as $key => $variant_data) {
        if (!empty($variant_data->default)) {
            $default_variant_key = $key;
            $default_variant = $variant_data;
            break;
        }
    }

    if (!$default_variant) {
        return [];
    }

    // Step 2: Merge each variant with the default variant
    $merged_variants = [];
    foreach ($variants as $key => $variant_data) {
        $merged_variants[$key] = (object) array_merge((array) $default_variant, (array) $variant_data);
    }

    // Step 3: Calculate aliases for each variant
    foreach ($merged_variants as $key => $variant_data) {
        $aliases = [];
        foreach ($merged_variants as $alias_key => $alias_data) {
            $font_family_matches = $alias_data->font_family === $variant_data->font_family;
            $font_weight_matches = $alias_data->font_weight === $variant_data->font_weight;

            if ($font_family_matches && $font_weight_matches) {
                $aliases[] = strtolower($alias_key); // Normalize to lowercase for consistency
            }
        }
        $merged_variants[$key]->aliases = $aliases; // Add aliases as a field
    }
    // Step 4: Determine the target variant
    $target_variant_key = $variant ?? $default_variant_key; // Use default if $variant is null/empty
    $target_variant = $merged_variants[$target_variant_key] ?? $default_variant;

    return $target_variant->aliases ?? [];
}


function get_icons($query = null, $variant = '') {
    $data = get_data();
    $icons = $data->icons ?? [];
    $sets = $data->sets ?? [];

    if (!$query && !$variant) {
        return $icons;
    }

    $results = [];

    // Synonym lookup tables
    $synonym_lookup = [
        'fullscreen' => ['expand', 'maximize', 'up-right-and-down-left-from-center'],
        'expand' => ['fullscreen', 'maximize', 'chevron-down'],
        'cart' => ['basket', 'shopping-cart'],
        'delete' => ['trash', 'remove', 'bin'],
        'home' => ['house', 'main'],
        'search' => ['find', 'magnifying-glass', 'lookup'],
        'settings' => ['gear', 'preferences', 'configure'],
        'user' => ['person', 'account', 'profile'],
        'edit' => ['pencil', 'modify', 'update'],
        'save' => ['disk', 'store', 'archive'],
        'arrow' => ['chevron', 'caret', 'triangle'],
        'close' => ['cross', 'x', 'cancel'],
        'play' => ['start', 'go', 'media-play'],
        'pause' => ['stop', 'media-pause', 'break'],
        'file' => ['document', 'paper', 'page'],
    ];

    $variant_synonyms = [
        'solid' => ['fill', 'bold'],
        'regular' => ['outline', 'normal'],
        'light' => ['thin'],
        'duotone' => ['two-tone'],
    ];

    // If `variant` is provided, resolve synonyms
    $variant_set = [];
    if ($variant) {
        $variant_set[] = strtolower($variant);
        if (isset($variant_synonyms[$variant])) {
            $variant_set = array_merge($variant_set, $variant_synonyms[$variant]);
        }
    }

    // If query is a string, turn it into an array of tokens
    if (is_string($query)) {
        $query = array_filter(explode(' ', strtolower($query)));
    }

    // Filter icons based on query and variant
    $icons = array_filter($icons, function($icon) use ($sets, $query, $variant_set, $synonym_lookup, &$results) {
        $icon_name = strtolower($icon->id);
        $icon_tokens = explode('-', $icon_name); // Split icon name into tokens
        $icon_variant = $icon->variant ? strtolower($icon->variant) : '';
        $set = $sets[$icon->prefix] ?? null;
        // Get aliases for the current set's variants
        $aliases = $set ? get_variant_aliases($set->variants, $icon_variant) : [];

        // Check if any alias matches the variant set
        if (count($variant_set) && !array_intersect($variant_set, $aliases)) {
            return false; // Skip this icon if no alias matches
        }

        if (!count($query)) {
            $results[] = $icon;
            return true;
        }

        // Check for exact match
        if (in_array($icon_name, $query)) {
            $icon->relevance = 1; // Exact match relevance
            $results[] = $icon;
            return true;
        }

        // Query token matching and synonym checking
        $matched_tokens = 0;
        $weighted_score = 0;
        foreach ($icon_tokens as $icon_token) {
            $index = array_search($icon_token, $query);
            if ($index !== false) {
                $weighted_score += (1 / ($index + 1)); // Higher weight for earlier matches
                $matched_tokens++;
            }
        }

        // Check for synonyms if no direct match
        if ($matched_tokens === 0 && $query) {
            foreach ($query as $token) {
                if (isset($synonym_lookup[$token])) {
                    foreach ($synonym_lookup[$token] as $synonym) {
                        if (in_array($synonym, $icon_tokens)) {
                            $weighted_score = max($weighted_score, 0.3); // Lower weight for synonym match
                        }
                    }
                }
            }
        }

        // Calculate relevance
        $total_icon_tokens = count($icon_tokens);
        $relevance = $total_icon_tokens > 0 ? ($weighted_score / $total_icon_tokens) : 0;

        if ($relevance > 0) {
            $icon->relevance = $relevance;
            $results[] = $icon;
            return true;
        }

        return false;
    });

    // Sort results by relevance
    usort($results, function($a, $b) {
        return $b->relevance <=> $a->relevance;
    });

    return $results;
}
