<?php

namespace benignware\wp\agnosticon;

function parse_icon_query($query) {
    $variant_synonyms = get_icon_variant_synonyms();
    $valid_variants = array_merge(array_keys($variant_synonyms), ...array_values($variant_synonyms));

    // Initialize the parts
    $prefix = '';
    $name = '';
    $variant = '';

    // Split the query by ':'
    $parts = is_array($query) ? $query : explode(':', $query);

    if (count($parts) === 3) {
        // Three parts: prefix, name, and variant
        $prefix = trim($parts[0]);
        $name = trim($parts[1]);
        $variant_candidate = trim($parts[2]);

        if (in_array(strtolower($variant_candidate), $valid_variants, true)) {
            $variant = $variant_candidate;
        } else {
            $name .= ':' . $variant_candidate; // Treat it as part of the name if invalid
        }
    } elseif (count($parts) === 2) {
        // Two parts: either prefix:name or name:variant
        $first_part = trim($parts[0]);
        $second_part = trim($parts[1]);

        if (in_array(strtolower($second_part), $valid_variants, true)) {
            // name:variant
            $name = $first_part;
            $variant = $second_part;
        } else {
            // prefix:name
            $prefix = $first_part;
            $name = $second_part;
        }
    } elseif (count($parts) === 1) {
        // Single part: it's the name
        $name = trim($parts[0]);
    }

    return [
        $prefix,
        $name,
        $variant,
    ];
}


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


function get_icons($query = null, $variant = null) {
    list($prefix, $name, $query_variant) = parse_icon_query($query);
    $query = $name;
    $variant = $variant ?: $query_variant;

    $data = get_data();
    $icons = $data->icons ?? [];
    $sets = $data->sets ?? [];

    if (!$query && !$variant) {
        return $icons;
    }

    $results = [];

    // Synonym lookup tables
    $synonyms_lookup = get_icon_synonyms();
    $variant_synonyms = get_icon_variant_synonyms();

    // Resolve variant synonyms
    $variant_set = [];
    if ($variant) {
        $variant_set[] = strtolower($variant);
        if (isset($variant_synonyms[$variant])) {
            $variant_set = array_merge($variant_set, $variant_synonyms[$variant]);
        }
    }

    // Tokenize query
    if (is_string($query)) {
        $query = array_filter(preg_split("/[^\-:_A-Za-z0-9]+/", strtolower($query)));
    }

    // Filter icons
    foreach ($icons as $icon) {
        $icon_name = strtolower($icon->name);
        $icon_tokens = explode('-', $icon_name);
        $icon_variant = $icon->variant ? strtolower($icon->variant) : '';
        $set = $sets[$icon->prefix] ?? null;

        // Resolve aliases for the variant
        $aliases = $set ? get_variant_aliases($set->variants, $icon_variant) : [];

        // Skip if no variant matches
        if (count($variant_set) && !array_intersect($variant_set, $aliases)) {
            continue;
        }

        // Relevance calculation
        $relevance = 0;

        // Exact match on name
        if ($icon_name === $name) {
            $relevance = 1.0; // Top priority
        } elseif (in_array($icon_name, $query)) {
            $relevance = 0.9; // High relevance for full matches
        } else {
            // Token matching
            foreach ($query as $query_token) {
                if (in_array($query_token, $icon_tokens)) {
                    $relevance += 0.5; // Moderate score for matching tokens
                }
            }

            // Synonym matching
            foreach ($query as $query_token) {
                if (isset($synonym_lookup[$query_token])) {
                    foreach ($synonym_lookup[$query_token] as $synonym) {
                        if (in_array($synonym, $icon_tokens)) {
                            $relevance += 0.3; // Lower score for synonym matches
                        }
                    }
                }
            }
        }

        if ($relevance > 0) {
            $icon->relevance = $relevance;
            $results[] = $icon;
        }
    }

    // Sort results by relevance
    usort($results, function($a, $b) {
        return $b->relevance <=> $a->relevance;
    });

    return $results;
}
