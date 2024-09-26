<?php
namespace benignware\wp\agnosticon;

function get_icons($query = null) {
    $icons = get_data()->icons ?? []; // Fetch icons data

    if (!$query) {
        return $icons;
    }

    $exact_matches = array_filter($icons, function($icon) use ($query) {
        return $icon->id === $query;
    });

    if (count($exact_matches)) {
        return $exact_matches;
    }

    // Synonym lookup table for common icons
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
        // Add more as needed
    ];

    $results = [];

    // If query is a string, turn it into an array of tokens
    if (is_string($query)) {
        $query = explode(' ', strtolower($query));
    }

    // Filter icons based on query and synonym lookup
    $icons = array_filter($icons, function($icon) use ($query, $synonym_lookup, &$results) {
        $icon_name = strtolower($icon->id);
        $icon_tokens = explode('-', $icon_name); // Split icon name into tokens
        $total_icon_tokens = count($icon_tokens); // Total tokens in the icon name
        
        // Initialize match count and weighted relevance
        $matched_tokens = 0;
        $weighted_score = 0;

        // Create a set for the query tokens for faster lookup
        $query_set = array_flip($query); // Create a set for faster existence checking

        // Check for exact match first
        if (in_array($icon_name, $query)) {
            // If the icon name matches the query exactly
            $icon->relevance = 1; // Exact match relevance
            $results[] = $icon;
            return true;
        }

        // Check for matches between icon tokens and query tokens
        foreach ($icon_tokens as $icon_token) {
            $index = array_search($icon_token, $query);
            if ($index !== false) {
                // Weight based on the position (lower index = higher weight)
                $weighted_score += (1 / ($index + 1)); // Higher weight for earlier matches
                $matched_tokens++;
            }
        }

        // Calculate the relevance score based on weighted score
        $relevance = $total_icon_tokens > 0 ? ($weighted_score / $total_icon_tokens) : 0;

        // Check for synonyms and adjust relevance accordingly
        if ($matched_tokens === 0) {
            foreach ($query as $token) {
                if (isset($synonym_lookup[$token])) {
                    foreach ($synonym_lookup[$token] as $synonym) {
                        if (in_array($synonym, $icon_tokens)) {
                            // Synonyms get a lower relevance score
                            $relevance = max($relevance, 0.3); // Set relevance for a synonym match (0.3)
                        }
                    }
                }
            }
        }

        // If the matched token count is less than the icon's token count,
        // penalize synonyms to ensure they don't overshadow exact matches
        if ($matched_tokens < $total_icon_tokens) {
            $relevance *= ($matched_tokens / $total_icon_tokens);
        }

        // Only add to results if relevance is positive
        if ($relevance > 0) {
            $icon->relevance = $relevance;
            $results[] = $icon;
            return true;
        }

        return false;
    });

    // Sort the results by relevance in descending order
    usort($results, function($a, $b) {
        return $b->relevance <=> $a->relevance;
    });

    return $results;
}
