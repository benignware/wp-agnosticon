<?php

namespace benignware\wp\agnosticon;

function get_data() {
  global $__agnosticon__;

  if (!isset($__agnosticon__)) {
    _agnosticon_load();
  }

  return $__agnosticon__;
}

function get_icon_meta($query = "", $variant = "") {
  $icons = get_icons($query, $variant);

  if (count($icons) > 0 && isset($icons[0])) {
    return $icons[0];
  }

  return null;
}

function get_icon($query, $attrs = []) {
  [$search, $variant] = preg_split('/\:/', $query);
  $icon = get_icon_meta($search, $variant);

  if (!$icon) {
    return '';
  }

  $attrs['class'] = isset($attrs['class']) ? $attrs['class'] . ' ' . $icon->class : $icon->class;

  $attrs_str = implode(' ', array_map(function($key, $value) {
    return "$key=\"$value\"";
  }, array_keys($attrs), array_values($attrs)));

  return "<i $attrs_str> </i>";
}

function get_agnosticon_data($id) {
  global $__agnosticon__;

  _agnosticon_load();

  if (!isset($__agnosticon__)) {
    return '';
  }

  $icons = $__agnosticon__->icons;

  if (isset($icons[$id])) {
    $icon = $icons[$id];

    return $icon;
  }

  return null;
}

function get_agnosticon($id, $attrs = []) {
  $icon = get_agnosticon_data($id);

  if (!$icon) {
    return null;
  }

  $is_admin = is_admin();

  if ($is_admin) {
    $attrs = array_merge($attrs, [
      'style' => isset($attrs['style']) ? $attrs['style'] . '; ' . $icon->style : $icon->style,
    ]);
  } else {
    $attrs = array_merge($attrs, [
      'class' => isset($attrs['class']) ? $attrs['class'] . ' ' . $icon->class : $icon->class,
    ]);
  }

  $attrs_str = implode(' ', array_map(function($key, $value) {
    return "$key=\"$value\"";
  }, array_keys($attrs), array_values($attrs)));

  if ($is_admin) {
    return "<span $attrs_str>{$icon->entity}</span>";
  } else {
    return "<i $attrs_str> </i>";
  }

  return '';
}