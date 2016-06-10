<?php

/**
 * @file
 * Contains \gpx_splitter\core\File.
 */

namespace gpx_splitter\core;

use gpx_splitter\core\Logger;
use stdClass;

/**
 * File and directory handling.
 */
class File {

  /**
   * Finds all files that match a given mask in a given directory.
   *
   * @see Drupal 8's file_scan_directory() for more information.
   *      http://api.drupal.org/api/search/8/file_scan_directory
   */
  public static function file_scan_directory($dir, $mask, $options = [], $depth = 0) {
    // Merge in defaults.
    $options += [
      'callback' => 0,
      'recurse' => TRUE,
      'key' => 'uri',
      'min_depth' => 0,
    ];
    // Normalize $dir only once.
    if ($depth == 0) {
      $dir_has_slash = (substr($dir, -1) === '/');
    }

    $options['key'] = in_array($options['key'], ['uri', 'filename', 'name']) ? $options['key'] : 'uri';
    $files = [];

    if (is_file($dir)) {
      $file = new stdClass();
      $file->uri = $dir;
      $file->filename = pathinfo($dir, PATHINFO_BASENAME);
      $file->name = pathinfo($dir, PATHINFO_FILENAME);
      $key = $options['key'];
      $files[$file->$key] = $file;
      if ($options['callback']) {
        $options['callback']($dir);
      }

      return $files;
    }
    // Avoid warnings when opendir does not have the permissions to open a
    // directory.
    if (!is_dir($dir)) {
      return [];
    }
    if ($handle = @opendir($dir)) {
      while (FALSE !== ($filename = readdir($handle))) {
        // Skip this file if it matches the nomask or starts with a dot.
        if ($filename[0] != '.' && !(isset($options['nomask']) && preg_match($options['nomask'], $filename))) {
          if ($depth == 0 && $dir_has_slash) {
            $uri = "$dir$filename";
          }
          else {
            $uri = "$dir/$filename";
          }
          if ($options['recurse'] && is_dir($uri)) {
            // Give priority to files in this folder by merging them in after
            // any subdirectory files.
            $files = array_merge(static::file_scan_directory($uri, $mask, $options, $depth + 1), $files);
          }
          elseif ($depth >= $options['min_depth'] && preg_match($mask, $filename)) {
            // Always use this match over anything already set in $files with
            // the same $options['key'].
            $file = new stdClass();
            $file->uri = $uri;
            $file->filename = $filename;
            $file->name = pathinfo($filename, PATHINFO_FILENAME);
            $key = $options['key'];
            $files[$file->$key] = $file;
            if ($options['callback']) {
              $options['callback']($uri);
            }
          }
        }
      }

      closedir($handle);
    }
    else {
      Logger::error('The directory "%s" can not be opened.', $dir);
      return FALSE;
    }

    return $files;
  }

}
