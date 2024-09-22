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

function _agnosticon_parse_resources($resources) {
  $icons = [];
  $icon_resources = [];

  foreach ($resources as $resource) {
    $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $resource->content); 

    preg_match_all('~\.([\w,:-]+:before)\s*\{\s*content:\s*["\'](\\\[^["\']+)~si', $content, $icon_matches, PREG_SET_ORDER);
    
    foreach ($icon_matches as $icon_match) {
      $selectors = array_map('trim', explode(',', $icon_match[1]));
      $char = $icon_match[2];
      $entity = preg_replace('~^\\\~', '&#x', $char);

      foreach ($selectors as $selector) {
        $has_before = substr_compare($selector, ':before', -strlen(':before')) === 0;

        if (!$has_before) {
          continue;
        }

        $id = trim(substr($selector, 0, strlen($selector) - 7));
        $id = trim($id, ':');
        $icons[$id] = (object) [
          'id' => $id,
          'char' => $char,
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

  $icons = array_reduce($icon_ids, function($result, $id) use ($prefixes, $icons) {
    $icon = $icons[$id];

    $prefix_matches = array_values(array_filter($prefixes, function($prefix) use ($id) {
      return strpos($id, $prefix) === 0;
    }));

    if (count($prefix_matches) > 0) {
      $icon_prefix = $prefix_matches[0];
      $icon_name = trim(substr($id, strlen($icon_prefix)), '-');

      $result[$id] = (object) array_merge(
        (array) $icon,
        [
          'prefix' => $icon_prefix,
          'name' => $icon_name
        ]
      );
    }

    return $result;
  }, []);

  $icon_sets = array_reduce($prefixes, function($result, $prefix) {
    $result[$prefix] = (object) [
      'id' => $prefix,
      'fonts' => [],
      'selectors' => []
    ];

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
      $selectors = array_reduce($selectors, function($result, $selector) use ($font_family, $font_weight) {
        $result[$selector] = isset($result[$selector]) ? $result[$selector] : (object) [
          'selector' => $selector,
        ];

        if ($font_family) {
          $result[$selector]->family = $font_family;
        }

        if ($font_weight) {
          $result[$selector]->weight = $font_weight;
        }

        return $result;
      }, $icon_sets[$prefix]->selectors);

      $icon_sets[$prefix]->selectors = $selectors;

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
      $font_weight = $font_weight_is_match ? $font_weight_match[1] : 'normal';
      

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

  foreach ($icons as $icon) {
    if (!isset($icon_sets[$icon->prefix])) {
      continue;
    }

    $icon_set = $icon_sets[$icon->prefix];
  
    $icon_class = $icon->id;
    $icon_classes = [];
    $icon_classes[] = $icon_class;
    $icon_classes = apply_filters('agnosticon_class', $icon_classes, $icon->id);
    $icon_classes = array_unique(array_reduce($icon_classes, function($result, $class) {
      $classes = explode(' ', $class);

      return array_merge($result, $classes);
    }, []));

    $props = array_reduce($icon_classes, function($result, $class) use ($icon_set) {
      $item = isset($icon_set->selectors['.' . $class]) ? $icon_set->selectors['.' . $class] : null;
      
      if (!$item) {
        $item = isset($icon_set->selectors['.' . $class . ':before']) ? $icon_set->selectors['.' . $class . ':before'] : null;
      }

      if ($item) {
        if (isset($item)) {
          $result = array_merge(
            $result,
            isset($item->weight) ? [
              'font-weight' => $item->weight
            ] : [],
            isset($item->family) ? [
              'font-family' => "'" . $item->family . "'"
            ] : [],
          );
        }
      }

      return $result;
    }, []);

    if (!isset($props['font-family'])) {
      $font_selectors = array_filter(array_keys($icon_set->selectors), function($selector) use ($icon_set, $props) {
        $item = $icon_set->selectors[$selector];

        if (!isset($item->family)) {
          return false;
        }

        if (isset($props['font-weight']) && isset($item->weight)) {
          return $item->weight === $props['font-weight'];
        }

        return true;
      });

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

      if ($font_selector && isset($icon_set->selectors[$font_selector])) {
        $item = $icon_set->selectors[$font_selector];
        $props['font-family'] = "'" . $item->family . "'";

        if (!isset($props['font-weight']) && isset($item->weight)) {
          $props['font-weight'] = $item->weight;
        }
      }
    }

    $icon_class = implode(' ', array_unique($icon_classes));
    
    $icon->class = $icon_class;
    $icon->font_family = $props['font-family'];
    $icon->font_weight = $props['font-weight'];

    $icon->style = implode('; ', array_map(function($key, $value) {
      return "$key: $value";
    }, array_keys($props), array_values($props)));
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
  // if ( wp_doing_ajax() ) {
  //   return;
  // }

  // Check if the 'action' parameter is set and matches
  if ( isset( $_GET['action'] ) && $_GET['action'] === '_agnosticon_data' ) {
    return;
  }

  $url = admin_url( 'admin-ajax.php' ) . '?action=_agnosticon_data';
  // $url = preg_replace("~(https?)://localhost(\:\d*)?~", "$1://127.0.0.1", $url);

  // if (!is_admin()) {
  //   echo 'frontend';
  //   $resources = _agnosticon_load_resources();
  //   $data = _agnosticon_parse_resources($resources);
  // } else {
    $url = admin_url( 'admin-ajax.php' ) . '?action=_agnosticon_data';
    $url = preg_replace("~(https?)://localhost(\:\d*)?~", "$1://127.0.0.1", $url);

    // $url = 'http://127.0.0.1/wp-admin/admin-ajax.php?action=theme_resources';

    $response = wp_remote_get($url, [
      'timeout' => 10
    ]);

  
    if ( is_array( $response ) && ! is_wp_error( $response ) ) {
      $content = $response['body']; // use the content
    } else {
      echo 'ERROR ' . $url;
      print_r($response);
      exit;
    }

    if ($content) {
      $data = json_decode($content);
    } else {
      $data = null;
    }
  // }

  // print_r($data->icons);

  $__agnosticon__ = (object) [
    'icons' => (array) $data->icons,
    'sets' => (array) $data->sets,
  ];
}
