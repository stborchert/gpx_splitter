# gpxSplitter

Ever wanted to split a gpx file so each track can be used seperately?

## Installation

Download or checkout the files to a directory of your choice and run
<code>composer install</code>.

## Usage

Simply execute <code>./splitter.php --path="\path\to\your_export.gpx"</code> to
split a single gpx-file based on the recorded tracks.

To process multiple files, point "--path" to a directory with your exports.

## Available options:

  <code>-p</code>, <code>--path</code>: Path to a single gpx file or path to a directory of gpx files. The path may be either relative to current directory or an absolute path.

  <code>-m</code>, <code>--file-mask</code>: Regular expression to use as file mask for files to be included. Defaults to <code>"/.*\.gpx$/i"</code>.

  <code>-f</code>, <code>--filename-base</code>: Base filename for the track files. Defaults to the name of the input file.

  <code>-r</code>, <code>--recurse</code>: Scan subdirectories. Defaults to "TRUE".

  <code>-v</code>, <code>--verbose</code>: Enable extended logging.

  <code>-h</code>, <code>--help</code>: Print help.
