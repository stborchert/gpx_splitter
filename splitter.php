#!/usr/bin/env php

<?php

/**
 * @file
 * Split gpx files based on tracks.
 */
use gpx_splitter\core\GpxSplitter;

require_once __DIR__ . '/vendor/autoload.php';

exit(main());

/**
 * Main function that runs all tasks.
 */
function main() {
  $splitter = new GpxSplitter();
  if (!$splitter->checkRequirements()) {
    return 0;
  }
  if ($splitter->execute()) {
    return 1;
  }
  // Something went wrong ... :(.
  return 0;
}
