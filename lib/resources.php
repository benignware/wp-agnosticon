<?php

namespace benignware\wp\agnosticon;

require 'prefix.php';

function _agnosticon_load_resource($url) {
  $local_directory_uris = array(
    [ get_stylesheet_directory_uri(), get_stylesheet_directory() ],
    [ get_template_directory_uri(), get_template_directory() ],
    [ plugins_url( '', '' ), WP_PLUGIN_DIR ]
  );

  $local_files = array_map(
    function($item) use($url) {
      return $item[1] . substr($url, strlen($item[0]));
    },
    array_values(
        array_filter($local_directory_uris, function($item) use ($url) {
        return (substr($url, 0, strlen($item[0])) === $item[0]);
      })
    )
  );

  if (!count($local_files)) {
    return null;
  }

  $local_file = $local_files[0];

  if ($local_file) {
    ob_start();
    include $local_file;
    $content = ob_get_contents();
    ob_end_clean();

    if ($content) {
      return $content;
    }
  }

  // Skip remote urls for now
  return null;

  $response = wp_remote_get($url, [
    'timeout' => 10
  ]);
 
  if ( is_array( $response ) && ! is_wp_error( $response ) ) {
    $content = $response['body']; // use the content

    return $content;
  }

  return null;
}

function _agnosticon_get_dependent_handles($handles) {
  global $wp_styles;

  return array_unique(
    array_reduce($handles, function($result, $handle) use ($wp_styles) {
      $item = $wp_styles->registered[$handle];

      $pattern = '~\/(wp-admin|wp-includes)~';

      $match = preg_match($pattern, $item->src);

      if ($match) {
        return $result;
      }

      if (isset($item->extra) && isset($item->extra['path'])) {
        $match = preg_match($pattern, $item->extra['path']);

        if ($match) {
          return $result;
        }
      }

      $handles = _agnosticon_get_dependent_handles($item->deps);

      return array_merge($result, [$handle], $handles);
    }, [])
  );
}

function _agnosticon_load_resources() {
  global $wp_styles;

  $handles = _agnosticon_get_dependent_handles($wp_styles->queue);

  $resources = [];

  foreach ($handles as $handle) {
    $item = $wp_styles->registered[$handle];
    $content = _agnosticon_load_resource($item->src);
    $obj = (object) array_merge(
      (array) $item,
      [
        'content' => $content
      ]
      );
    $resources[] = $obj;
  }


  return $resources;
}

function convert_unicode_to_entity($char) {
  // Trim the input character for safety
  $char = trim($char);

  // Check if the character starts with a backslash followed by hex digits
  if (preg_match('~^\\\\([0-9a-fA-F]+)$~', $char, $matches)) {
      // Extract the hex part and convert it to a character
      $hexCode = $matches[1];
      $unicodeChar = mb_convert_encoding("&#x{$hexCode};", 'UTF-8', 'HTML-ENTITIES');

      // Check if the character has a valid HTML entity equivalent
      $htmlEntity = mb_convert_encoding($unicodeChar, 'HTML-ENTITIES', 'UTF-8');
      if ($htmlEntity !== $unicodeChar) {
          return $htmlEntity;
      }

      // If no valid entity exists, return the original Unicode character
    return $unicodeChar;
  }

  // Return the original character if it doesn't match the Unicode format
  $result = htmlspecialchars($char, ENT_QUOTES | ENT_HTML5, 'UTF-8');
  $result = trim($result, "\\");

  return $result;
}


function _agnosticon_parse_resources($resources) {
  $default_variant_name = '';
  $icons = [];
  $icon_resources = [];

  foreach ($resources as $resource) {
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $resource->content); 

    preg_match_all('~\.([\w,:-]+:before)\s*\{\s*content:\s*["\'](\\\[^["\']+)~si', $content, $icon_matches, PREG_SET_ORDER);
    
    foreach ($icon_matches as $icon_match) {
      $selectors = array_map('trim', explode(',', $icon_match[1]));
      $char = trim($icon_match[2]);
      $entity = convert_unicode_to_entity($char);

      foreach ($selectors as $selector) {
        $has_before = substr_compare($selector, ':before', - strlen(':before')) === 0;

        if (!$has_before) {
          continue;
        }

        $id = trim(substr($selector, 0, strlen($selector) - 7));
        $id = trim($id, ':');
        
        $icons[$id] = (object) [
          'id' => $id,
          'char' => stripslashes($char),
          'code' => '0x' . stripslashes($char),
          'entity' => $entity,
        ];
      }
    }

    if (count($icon_matches)) {
      $icon_resources[] = $resource;
    }
  }

  $icon_ids = array_keys($icons);
  $prefixes = get_prefixes($icon_ids);

  $icon_sets = array_reduce($prefixes, function($result, $prefix) {
    $result[$prefix] = (object) [
      'id' => $prefix,
      'fonts' => [],
      'variants' => []
    ];

    return $result;
  }, []);

  $icons = array_reduce($icon_ids, function($result, $id) use ($prefixes, $icons, $default_variant_name) {
    $icon = $icons[$id];

    $prefix_matches = array_values(array_filter($prefixes, function($prefix) use ($id) {
      return strpos($id, $prefix) === 0;
    }));

    if (count($prefix_matches) > 0) {
      $icon_prefix = $prefix_matches[0];
      $icon_name = trim(substr($id, strlen($icon_prefix)), '-');
      $icon_id = $icon_prefix . ':' . $icon_name;

      $result[$icon_id] = (object) array_merge(
        (array) $icon,
        [
          'id' => $icon_id,
          'prefix' => $icon_prefix,
          'name' => $icon_name
        ]
      );
    }

    return $result;
  }, []);

  $pattern_prefix = implode('|', array_map(function($prefix) {
    return preg_quote($prefix, '~');
  }, $prefixes));

  $pattern_a = "\[class[\^]=['\"]\s*($pattern_prefix)\-[^{]*";
  $pattern_b = "\.($pattern_prefix)(?:[-:][^{]*)?";
  $pattern = "~(?:$pattern_a|$pattern_b)\s*\{([^\}]+)~";

  foreach ($icon_resources as $resource) {
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $resource->content); 
    
    preg_match_all($pattern, $content, $prefix_matches, PREG_SET_ORDER);

    foreach ($prefix_matches as $prefix_match) {
      $prefix = $prefix_match[1] ?: $prefix_match[2];
      $body = $prefix_match[3];

      $font_family_is_match = preg_match("~[^\}]*font(?:-family)?\s*:\s*?([^;}]+)~", $body, $font_family_match);

      $font_value =  $font_family_is_match ? $font_family_match[1] : null;
      // Strip !important from font value
      $font_value = preg_replace('~\s*!important\s*~', '', $font_value);

      $font_value = $font_value ? preg_replace('~\b(?:normal|italic|bold|\d+(?:px|rem)/\d+|var\(\s*--[\w-]+\s*,\s*)~', '', $font_value) : null;
      $font_family = $font_value ? trim($font_value, '\'") ') : null;


      $font_weight_is_match = preg_match("~[^\}]*font-weight?\s*:\s*?([^;}]+)~", $body, $font_weight_match);
      $font_weight = $font_weight_is_match ? $font_weight_match[1] : '';
      $font_weight = preg_replace('~(?:var\(\s*--[\w-]+\s*,\s*)~', '', $font_weight);
      $font_weight = trim($font_weight, '\'") ');

      if (!$font_family && !$font_weight) {
        continue;
      }

      $selector = preg_replace('~\s*\{(.*)~si', '', $prefix_match[0]);
      $selectors = array_map('trim', explode(',', $selector));

      $variants = array_reduce($selectors, function($result, $selector) use ($icons, $prefix, $font_family, $font_weight, $default_variant_name) {
        $selector = trim($selector);
        $selector = preg_replace('~\s*::?before~', '', $selector);
        $name = preg_replace('~^\.' . preg_quote($prefix, '~') . '[-]?~', '', $selector) ?: '';
        
        if (isset($icons[$prefix . ':' . $name])) {
          return $result;
        }
        
        $class = preg_replace('~^\.~', '', $selector);
        $is_default = $name === '';
        $name = $is_default ? $default_variant_name : $name;

        $result[$name] = isset($result[$name]) ? $result[$name] : (object) [
          'selector' => $selector,
          'name' => $name,
          'class' => $class,
          'default' => $is_default
        ];

        if ($font_family) {
          $result[$name]->font_family = $font_family;
        }

        if ($font_weight) {
          $result[$name]->font_weight = $font_weight;
        }

        return $result;
      }, $icon_sets[$prefix]->variants);

      $icon_sets[$prefix]->variants = $variants;

      if ($font_family && !isset($icon_sets[$prefix]->fonts[$font_family])) {
        $icon_sets[$prefix]->fonts[$font_family] = (object) [
          'name' => $font_family,
          'weights' => [],
          'selectors' => [],
          'css' => ''
        ];
      }
    }
  }

  $font_faces = [];

  foreach ($icon_resources as $resource) {
    // Parse fonts
    preg_match_all("~\s*@font-face\s*\{([^}]*)\s*\}~", $resource->content, $font_face_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

    $url_dir = dirname($resource->src);

    foreach ($font_face_matches as $font_face_match) {
      $font_family_is_match = preg_match("~font-family:\s*['\"]?([^'\";}]+)~", $font_face_match[1][0], $font_family_match);

      if (!$font_family_is_match) {
        continue;
      }

      $font_family = $font_family_match[1];

      $font_weight_is_match = preg_match("~font-weight:\s*([^;}]+)~", $font_face_match[1][0], $font_weight_match);
      $font_weight = $font_weight_is_match ? $font_weight_match[1] : $default_variant_name;

      $css = $font_face_match[1][0];
      // $css = preg_replace('~url\([\'"]?((?!https?://|/)[^\'")]+)[\'"]~si', "url('$url_dir/$1'", $css);
      $css = preg_replace('~url\([\'"]?((?!https?://|/)[^\'")]+)[\'"]?~si', "url('$url_dir/$1'", $css);
      $css = trim($css);

      $css = "@font-face {\n" . $css . "\n}\n";

      $before_font_face = substr($resource->content, 0, $font_face_match[0][1]);
      $has_media_match = preg_match('~(@media\s+screen\s+and\s*\(-webkit-min-device-pixel-ratio\:\s*\d+\)\s*\{)\s*[^}]*$~', $before_font_face, $media_match);
      
      if ($has_media_match) {
        $css = $media_match[1] . "\n\t" . $css . "\n}\n";
      }

      foreach ($icon_sets as $id => $icon_set) {
        if (isset($icon_set->fonts[$font_family])) {
          $icon_set->fonts[$font_family]->css.= $css;
          
          if (!in_array($font_weight, $icon_set->fonts[$font_family]->weights)) {
            $icon_set->fonts[$font_family]->weights[] = $font_weight;
          }
        }
      }
    }
  }

  $icons = array_reduce($icons, function($result, $icon) use ($icon_sets) {
    $icon_set = $icon_sets[$icon->prefix];
    $variants = $icon_set->variants;
    $default_variant = array_values(array_filter($variants, function($variant) {
      return $variant->default;
    }))[0] ?? null;

    $default_variant = $default_variant ?: (count($variants) > 0 ? $variants[0] : null);

    // if (!$default_variant) {
    //   return $result;
    // }
    
    $variant_names = [$default_variant->name];
    $variant_names = apply_filters('agnosticon_variants', $variant_names, $icon, $icon_set);
    
    foreach ($variant_names as $name) {
      $is_default = $name === $default_variant->name || count($variant_names) === 1;
      $variant_id = $icon->id . (($is_default || !$name) ? '' : ':' . $name);

      $result[$variant_id] = (object) array_merge((array) $icon, [
        'id' => $variant_id,
        'variant' => $name
      ]);
    }

    return $result;
  }, []);

  foreach ($icons as $icon) {
    if (!isset($icon_sets[$icon->prefix])) {
      continue;
    }

    $icon_set = $icon_sets[$icon->prefix];
    $variant = $icon_set->variants[$icon->variant] ?? null;
    $variant_class = $variant ? $variant->class : '';
  
    $icon_class = $icon->prefix ? $icon->prefix . '-' . $icon->name : $icon->name;
    $icon_classes = array_filter([ $variant_class, $icon_class ]);
    $icon_classes = array_unique(array_reduce($icon_classes, function($result, $class) {
      $classes = explode(' ', $class);

      return array_merge($result, $classes);
    }, []));

    $props = array_reduce($icon_classes, function($result, $class) use ($icon_set) {
      $variant_name = array_values(array_filter(array_keys($icon_set->variants), function($variant) use ($icon_set, $class) {
        $variant = $icon_set->variants[$variant];
        return $variant->class == $class;
      }))[0] ?? null;
      $variant = $variant_name !== null ? $icon_set->variants[$variant_name] : null;
      
      if ($variant) {
        $result = array_merge(
          $result,
          isset($variant->font_weight) ? [
            'font-weight' => $variant->font_weight
          ] : [],
          isset($variant->font_family) ? [
            'font-family' => $variant->font_family
          ] : [],
        );
      }

      return $result;
    }, []);

    if (!isset($props['font-family'])) {
      $variant_names = array_filter(array_keys($icon_set->variants), function($variant_name) use ($icon_set, $props) {
        $variant = $icon_set->variants[$variant_name];

        if (!isset($variant->font_family)) {
          return false;
        }

        if (isset($props['font-weight']) && isset($variant->font_weight)) {
          return $variant->font_weight === $props['font-weight'];
        }

        return true;
      });

      $font_selectors = array_map(function($variant_name) use ($icon_set) {
        return $icon_set->variants[$variant_name]->selector;
      }, $variant_names);

      $font_classes = array_map(function($selector) {
        $class = preg_replace('~^\.~', '', $selector);
        $class = preg_replace('~\:before$~', '', $class);

        return $class;
      }, array_filter($font_selectors, function($selector) {
        return strpos($selector, '.') === 0;
      }));

      $font_class = count($font_classes) ? $font_classes[0] : '';

      array_splice($icon_classes, 0, 0, $font_class);

      $font_selector = count($font_selectors) ? $font_selectors[0] : null;

      $variant_name = array_values(array_filter(array_keys($icon_set->variants), function($key) use ($icon_set, $font_selector) {
        $variant = $icon_set->variants[$key];
        return $variant->selector === $font_selector;
      }))[0] ?? null;

      $variant = $variant_name ? $icon_set->variants[$variant_name] : null;

      if ($variant) {
        $props['font-family'] = $variant->font_family;

        if (!isset($props['font-weight']) && isset($variant->font_weight)) {
          $props['font-weight'] = $variant->font_weight;
        }
      }
    }

    $default_font = array_values($icon_set->fonts)[0];
    $font_family = $props['font-family'] ?: ($default_font ? $default_font->name : null);
    $font_weight = $props['font-weight'] ?: $default_font->weights[0] ?? null;

    $icon_class = trim(implode(' ', array_unique($icon_classes)));
    
    $icon->char = stripslashes($icon->char);
    $icon->class = $icon_class;
    $icon->font_family = $font_family;
    $icon->font_weight = $font_weight;
    $icon->style = sprintf("font-family: '%s'; font-weight: %s", $icon->font_family, $icon->font_weight);

    $icons[$icon->id] = $icon;
  }

  $data = (object) [
    'icons' => $icons,
    'sets' => $icon_sets,
  ];

  return $data;
};

function _agnosticon_load() {
  global $__agnosticon__;

  if (isset($__agnosticon__)) {
      return;
  }

  // Check if this is an AJAX request to prevent infinite loop
  if (isset($_GET['action']) && $_GET['action'] === '_agnosticon_data') {
      return;
  }

  // Attempt to retrieve cached data
  $cached_data = get_transient('agnosticon_data');
  if ($cached_data !== false) {
      $__agnosticon__ = (object) [
          'icons' => (array) $cached_data->icons,
          'sets' => (array) $cached_data->sets,
      ];
      return;
  }

  // Prepare the request URL
  $url = admin_url('admin-ajax.php') . '?action=_agnosticon_data';
  $url = preg_replace("~(https?)://localhost(\:\d*)?~", "$1://127.0.0.1", $url);

  // Retrieve HTTP Basic Auth credentials from server variables
  $username = $_SERVER['PHP_AUTH_USER'] ?? '';
  $password = $_SERVER['PHP_AUTH_PW'] ?? '';

  $headers = $username && $password
      ? ['Authorization' => 'Basic ' . base64_encode("{$username}:{$password}")]
      : [];

  // Make the HTTP request
  $response = wp_remote_get($url, [
      'timeout' => 5,
      'headers' => $headers,
  ]);

  // Handle the response
  if (is_array($response) && !is_wp_error($response)) {
      $content = $response['body'];
  } else {
      echo 'ERROR ' . esc_url($url);
      print_r($response);
      exit;
  }

  // Parse and cache the data
  $data = $content ? json_decode($content) : null;

  if ($data) {
      // Cache the data in a transient
      set_transient('agnosticon_data', $data, 0); // No expiration
      $__agnosticon__ = (object) [
          'icons' => (array) $data->icons,
          'sets' => (array) $data->sets,
      ];
  } else {
      $__agnosticon__ = null;
  }
}
