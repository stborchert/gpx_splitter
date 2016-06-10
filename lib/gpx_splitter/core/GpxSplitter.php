<?php

/**
 * @file
 * Contains \gpx_splitter\core\GpxSplitter.
 */

namespace gpx_splitter\core;

use DOMDocument;
use gpx_splitter\core\Logger;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Basic GpxSplitter.
 */
class GpxSplitter {

  /**
   * Command line options for the parser.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Command line arguments.
   *
   * @var array
   */
  protected $arguments = [];

  /**
   * List of files to parse.
   *
   * @var array
   */
  protected $files = [];

  /**
   * Constructs a GpxSplitter object.
   */
  public function __construct() {
    $this->options = [];
    $this->_prepareArguments();
  }

  /**
   * Check parser requirements.
   *
   * @return boolean
   */
  public function checkRequirements() {
    $arguments = $this->_getArguments();

    if (isset($arguments['h']) || isset($arguments['help'])) {
      // Documentation request. No further arguments needed.
      return TRUE;
    }
    if (empty($this->arguments['path'])) {
      Logger::error('Path to input file or source directory is missing. Use -h to display information about the usage of this script.');
      return FALSE;
    }
    if (!is_dir($this->arguments['path']) && !is_file($this->arguments['path'])) {
      Logger::error('The given path "%s" is not accessible.', $this->arguments['path']);
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Executes the parser.
   */
  public function execute() {
    $arguments = $this->arguments;

    if (isset($arguments['h']) || isset($arguments['help'])) {
      // Documentation request. No further arguments needed.
      print static::_getHelp();
      return TRUE;
    }

    // Retrieve the list of files to parse.
    $this->files = File::file_scan_directory($this->getPath(), $this->getFileMask(), ['key' => 'name', 'recurse' => $this->arguments['recurse']]);
    Logger::info('Splitting %d files ...', count($this->files));

    // Temporary disable error reporting.
    libxml_use_internal_errors(TRUE);

    // Initiate new crawler.
    $crawler = new Crawler();

    foreach ($this->files as $file) {
      if ($this->logExtended()) {
        Logger::info(' process file "%s"', [$file->name]);
      }
      $crawler->clear();
      $crawler->addXmlContent(file_get_contents($file->uri));

      // Extract main information from file.
      $time = $crawler->filter('time');

      // Create base filename.
      $filename_base = $file->name;

      // Extract tracks.
      $tracks = $this->extractTracks($crawler);
      foreach ($tracks as $index => $track) {
        try {
          $track_name = $track->getElementsByTagName('name')[0]->nodeValue;
          if ($this->logExtended()) {
            Logger::info('  extract track "%s"', [$track_name]);
          }
          $document = new DOMDocument();
          $document->loadXml('<gpx version="1.0" creator="gpxSplitter" xmlns="http://www.topografix.com/GPX/1/0"></gpx>');
          $document->formatOutput = TRUE;
          $root = $document->firstChild;
          if ($time->count()) {
            // Add time information from base file.
            $node = $document->importNode($time->getNode(0), TRUE);
            $root->appendChild($node);
          }
          // Add track to new document.
          $node = $document->importNode($track, TRUE);
          $root->appendChild($node);

          // Build filename.
          $filename = sprintf('%s.%02d.gpx', $filename_base, $index);
          $destination = dirname($file->uri);
          if ($this->logExtended()) {
            Logger::info('  write track to "%s"', [$filename]);
          }

          // Save the track.
          $document->save($destination . '/' . $filename);
        }
        catch (Exception $ex) {
          Logger::error('Failed to extract track from "%s".', [$file->name]);
          Logger::error('Exception: "%s".', [$ex->getMessage()]);
        }
      }
    }
    libxml_clear_errors();

    return TRUE;
  }

  /**
   * Extract tracks from a crawler object.
   *
   * @param Crawler $crawler
   *   The DomCrawler to extract the tracks from.
   *
   * @return Crawler
   *   List of tracks.
   */
  protected function extractTracks(Crawler $crawler) {
    return $crawler->filter('trk');
  }

  /**
   * Return the directory path to scan.
   *
   * @return string
   *   Path of directory to scan for parsable files.
   */
  public function getPath() {
    return $this->arguments['path'];
  }

  /**
   * Get the regular expression to filter files.
   *
   * @return string
   *   Regular expression for file filtering.
   */
  public function getFileMask() {
    return $this->arguments['file-mask'];
  }

  /**
   * Get the base filename to use for individual track files.
   *
   * @return string|NULL
   *   Basic filename for track files. If <code>NULL</code>, the name of the
   *   input file will be used.
   */
  public function getFilenameBase() {
    return $this->arguments['filename-base'];
  }

  /**
   * Check, whether extended logging is enabled.
   *
   * @return boolean
   *   TRUE if extended logging is enabled, otherwise FALSE.
   */
  public function logExtended() {
    return !empty($this->arguments['verbose']);
  }

  /**
   * Helper function to prepare command line arguments.
   */
  private function _prepareArguments() {
    $arguments = $this->_getArguments();

    // Prepare arguments.
    $this->arguments = [
      'path' => NULL,
      'file-mask' => '/.*\.gpx$/i',
      'filename-base' => NULL,
      'recurse' => TRUE,
      'verbose' => FALSE,
    ];
    $opts = [
      'path' => ['p', 'path'],
      'file-mask' => ['m', 'file-mask'],
      'filename-base' => ['f', 'filename-base'],
      'recurse' => ['r', 'recurse'],
      'verbose' => ['v', 'verbose'],
    ];
    foreach ($opts as $opt => $alternatives) {
      foreach ($alternatives as $alternative) {
        if (isset($arguments[$alternative])) {
          $this->arguments[$opt] = is_bool($arguments[$alternative]) ? TRUE : $arguments[$alternative];
          break;
        }
      }
    }
  }

  /**
   * Helper function to get script options from command line.
   *
   * Available options:
   *   -p (--path): Base path to gpx files. The path needs to be absolute.
   *   -m (--file-mask): Regular expression to use as file mask for files to be
   *      included. Defaults to "*.gpx".
   *   -r (--recurse): Scan subdirectories. Defaults to "TRUE".
   *   -v (--verbose): Enable extended logging.
   *   -h (--help): Print this help.
   *
   * @return array
   *   List of script arguments from the command line.
   */
  private function _getArguments() {
    $opts = 'p:m::f::r::v::h';
    $longopts = [
      'path:',
      'file-mask::',
      'filename-base::',
      'recurse::',
      'verbose::',
      'help'
    ];

    return getopt($opts, $longopts);
  }

  /**
   * Return the help text.
   */
  protected static function _getHelp() {
    $help = 'Split gpx files in a given directory based on its recorded tracks.' . "\n\n";
    $help .= 'Available options:' . "\n";
    $help .= ' -p (--path): Path to a single gpx file or a path to a directory of gpx files. The path may be either relative to current directory or an absolute path.' . "\n";
    $help .= ' -m (--file-mask): Regular expression to use as file mask for files to be included. Defaults to "/.*\.gpx$/i".' . "\n";
    $help .= ' -f (--filename-base): Base filename for the track files. Defaults to the name of the input file.' . "\n";
    $help .= ' -r (--recurse): Scan subdirectories. Defaults to "TRUE".' . "\n";
    $help .= ' -v (--verbose): Enable extended logging.' . "\n";
    $help .= ' -h (--help): This help.' . "\n\n";
    $help .= 'Example:' . "\n";
    $help .= './splitter.php --path="\path\to\gpx" --mask="/export-.*\.gpx$/i"' . "\n";

    return $help;
  }

}
