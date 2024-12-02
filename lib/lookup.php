<?php

namespace benignware\wp\agnosticon;

function get_icon_synonyms() {
  return [
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
}

function get_icon_variant_synonyms() {
  return [
    'solid' => ['fill', 'bold'],
    'regular' => ['outline', 'normal'],
    'light' => ['thin'],
    'duotone' => ['two-tone'],
  ];
}
