<?php

namespace benignware\wp\agnosticon;

// returns the position of the first differing character between
// $left and $right, or -1 if either is empty
function strcmppos($left, $right) {
    if (empty($left) || empty($right)) {
        return -1;
    }

    $i = 0;
    while ($left[$i] && $left[$i] == $right[$i]) {
        $i++;
    }

    return $i - 1;
}

// assert(strcmppos('', '') === -1);
// assert(strcmppos('a', '') === -1);
// assert(strcmppos('a', 'a') === 0);
// assert(strcmppos('foo-bar-baz', 'foo-bar-zab') === 7);

// returns the part of the string preceding (but not including) the
// final directory delimiter, or empty if none are found
function truncate_to_last_dir($str) {
  return (string)substr($str, 0, strrpos($str, '-'));
}

// assert(truncate_to_last_dir('') === '');
// assert(truncate_to_last_dir('foo') === '');
// assert(truncate_to_last_dir('foo-') === 'foo');
// assert(truncate_to_last_dir('foo-bar') === 'foo');

function flatten_array($array) {
    $merged = array();
    foreach ($array as $arr) {
        $merged = array_merge($merged, $arr);
    }

    return $merged;
}

function group_array_by_directory_prefix($strings) {
    $groups = array();
    $numStrings = count($strings);
    for ($i = 0; $i < $numStrings; $i++) {
        for ($j = $i + 1; $j < $numStrings; $j++) {
            $pos = strcmppos($strings[$i], $strings[$j]);
            $prefix = truncate_to_last_dir(substr($strings[$i], 0, $pos + 1));

            // append to grouping for this prefix. include both strings - this
            // gives duplicates which we'll merge later
            $groups[$prefix][] = array($strings[$i], $strings[$j]);
        }
    }

    foreach ($groups as &$group) {
        // to remove duplicates introduced above
        $group = array_unique(flatten_array($group));
    }

    return $groups;
}

function unique_group_by_directory_prefix($strings) {
    $groups = group_array_by_directory_prefix($strings);

    // sort descending on key length
    uksort($groups, function($left, $right) {
        return strlen($right) - strlen($left);
    });

    // starting from the top, perform the set difference to remove duplication
    // across groups
    $current = reset($groups);
    $unique_groups = array(key($groups) => $current);
    $next = next($groups);

    while ($next) {
        $diff = array_diff($next, $current);
        $unique_groups[key($groups)] = $diff;

        $current = $next;
        $next = next($groups);
    }

    return $unique_groups;
}

function get_prefixes($strings) {
  $first_segments = array_unique(array_reduce($strings, function($result, $string) {
    $segments = explode('-', $string);

    if (count($segments) && !in_array($segments[0], $result)) {
      $result[] = $segments[0];
    }

    return $result;
  }, []));

  $sample = [];

  foreach ($first_segments as $first_segment) {
    $first_seg_strings = array_filter($strings, function($string) use ($first_segment) {
      return strpos($string, $first_segment) === 0;
    });

    usort($first_seg_strings, function($a, $b) {
      $ca = count(explode('-', $a));
      $cb = count(explode('-', $b));

      if ($ca > $cb) {
        return -1;
      } else if ($ca < $cb) {
        return 1;
      }

      return 0;
    });

    $sample = array_merge(
      $sample,
      array_slice($first_seg_strings, 0, 50)
    );
  }
  $strings = $sample;

  $prefixes_data = unique_group_by_directory_prefix($strings);

  unset($prefixes_data['']);
  uasort($prefixes_data, function($a, $b) {
    if (count($a) > count($b)) {
      return -1;
    } elseif (count($a) < count($b)) {
      return 1;
    }
    return 0;
  });

  $prefixes = array_keys($prefixes_data);

  foreach ($prefixes_data as $prefix => $values) {
    foreach ($prefixes as $other_prefix) {
      if ($other_prefix !== $prefix && strpos($other_prefix, $prefix) === 0) {
        $prefixes_data[$other_prefix] = [];
      }
    }
  }

  $prefixes_data = array_filter($prefixes_data, function($values) {
    return count($values);
  });

  $prefixes = array_keys($prefixes_data);

  return $prefixes;
}
