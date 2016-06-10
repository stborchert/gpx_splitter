<?php

/**
 * @file
 * Contains \gpx_splitter\core\Logger.
 */

namespace gpx_splitter\core;

/**
 * Basic logger.
 */
class Logger {

  /**
   * Print info message.
   */
  public static function info($message, $args = []) {
    static::log([
      'type' => 'info',
      'message' => $message,
      'args' => $args,
    ]);
  }

  /**
   * Print status message.
   */
  public static function status($message, $args = []) {
    static::log([
      'type' => 'status',
      'message' => $message,
      'args' => $args,
    ]);
  }

  /**
   * Print error message.
   */
  public static function error($message, $args = []) {
    static::log([
      'type' => 'error',
      'message' => $message,
      'args' => $args,
    ]);
  }

  /**
   * Simple log function to print messages to the command line.
   *
   * @todo: log to file!
   */
  public static function log($entry) {
    $red = "\033[31;40m\033[1m[%s]\033[0m";
    $yellow = "\033[1;33;40m\033[1m[%s]\033[0m";
    $green = "\033[1;32;40m\033[1m[%s]\033[0m";

    if (!is_array($entry)) {
      $entry = [
        'message' => $entry,
      ];
    }
    $entry += ['type' => 'info', 'args' => []];
    if (!is_array($entry['args'])) {
      $entry['args'] = [$entry['args']];
    }

    $return = TRUE;
    switch ($entry['type']) {
      case 'warning' :
      case 'cancel' :
        $type_msg = sprintf($yellow, $entry['type']);
        break;
      case 'failed' :
      case 'error' :
        $type_msg = sprintf($red, $entry['type']);
        $return = FALSE;
        break;
      case 'ok' :
      case 'completed' :
      case 'success' :
      case 'status':
        $type_msg = sprintf($green, $entry['type']);
        break;
      case 'notice' :
      case 'message' :
      case 'info' :
        $type_msg = sprintf("[%s]", $entry['type']);
        break;
      default :
        $type_msg = sprintf("[%s]", $entry['type']);
        break;
    }

    $columns = 120;

    $width[1] = 11;
    // Append timer and memory values.
    $width[0] = ($columns - 11);

    $format = sprintf("%%-%ds%%%ds", $width[0], $width[1]);

    // Place the status message right aligned with the top line of the error message.
    $message = call_user_func_array('sprintf', array_merge([$entry['message']], $entry['args']));
    $message = wordwrap($message, $width[0]);
    $lines = explode("\n", $message);
    $lines[0] = sprintf($format, $lines[0], $type_msg);
    $message = implode("\n", $lines);
    // @todo: log to file.
    static::_print($message, 0, STDERR);
    return $return;
  }

  /**
   * Prints a message with optional indentation.
   *
   * @param $message
   *   The message to print.
   * @param $indent
   *    The indentation (space chars)
   * @param $handle
   *    File handle to write to.  NULL will write
   *    to standard output, STDERR will write to the standard
   *    error.  See http://php.net/manual/en/features.commandline.io-streams.php
   * @param $newline
   *    Add a "\n" to the end of the output.  Defaults to TRUE.
   */
  protected static function _print($message = '', $indent = 0, $handle = NULL, $newline = TRUE) {
    $msg = str_repeat(' ', $indent) . (string) $message;
    if ($newline) {
      $msg .= "\n";
    }
    if (isset($handle)) {
      fwrite($handle, $msg);
    }
    else {
      print $msg;
    }
  }

}
