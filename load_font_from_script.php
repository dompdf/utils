<?php
/*!
 * ===================================================
 * Example of in-script installing of fonts for pdfdom
 * ===================================================
 * (differing from load_font.php which is for command-line php use)
 * ===================================================
 * 
 * This file is designed to be included directly when font-install required
 * 
 * Tested with version 0.8.4 of pdfdom & Noto Sans:
 *    - https://github.com/dompdf/dompdf
 *    - https://fonts.google.com/specimen/Noto+Sans
 */


// 1. [Required] Point to the composer or dompdf autoloader
require_once('autoload.inc.php');

// 2. [Optional] Set the path to your font directory
//    By default dopmdf loads fonts to dompdf/lib/fonts
//    If you have modified your font directory set this
//    variable appropriately.
//$fontDir = "lib/fonts";

// 3. Call example_install_font_family() from any php, as example usage:
/*

  Example Usage:

        #} Initialise dompdf
        $dompdf = new Dompdf();

        #} Set font directory if passed
        if (isset($fontDir) && realpath($fontDir) !== false) {
          $dompdf->getOptions()->set('fontDir', $fontDir);
        }

        #} Define font files
        $fontName = 'Noto Sans';
        $normalFile = 'NotoSans-Regular.ttf';
        $boldFile = 'NotoSans-Bold.ttf';
        $italicFile = 'NotoSans-Italic.ttf';
        $boldItalicFile = 'NotoSans-BoldItalic.ttf';

        #} Install the font(s)
        example_install_font_family($dompdf, $fontName, $normalFile, $boldFile, $italicFile, $boldItalicFile);


  Example 2:

      $fontDir = '/your-path/dompdf-fonts/';
      example_install_font_family($dompdf,
          'Noto Sans', 
          $fontDir.'NotoSans-Regular.ttf', 
          $fontDir.'NotoSans-Bold.ttf', 
          $fontDir.'NotoSans-Italic.ttf', 
          $fontDir.'NotoSans-BoldItalic.ttf'
      );

*/


// *** DO NOT MODIFY BELOW THIS POINT ***

#} Use
use FontLib\Font;


  /**
   * Installs a new font family
   * This function maps a font-family name to a font.  It tries to locate the
   * bold, italic, and bold italic versions of the font as well.  Once the
   * files are located, ttf versions of the font are copied to the fonts
   * directory.  Changes to the font lookup table are saved to the cache.
   *
   * This is an an adapted version of install_font_family()
   *
   * @param Dompdf $dompdf      dompdf main object 
   * @param string $fontname    the font-family name
   * @param string $normal      the filename of the normal face font subtype
   * @param string $bold        the filename of the bold face font subtype
   * @param string $italic      the filename of the italic face font subtype
   * @param string $bold_italic the filename of the bold italic face font subtype
   * @param bool   $debug       whether or not to echo progress
   *
   * @throws Exception
   */
  function example_install_font_family($dompdf, $fontname, $normal, $bold = null, $italic = null, $bold_italic = null, $debug = false) {
    
    try {

      $fontMetrics = $dompdf->getFontMetrics();
      
      // Check if the base filename is readable
      if ( !is_readable($normal) )
        throw new Exception("Unable to read '$normal'.");

      $dir = dirname($normal);
      $basename = basename($normal);
      $last_dot = strrpos($basename, '.');
      if ($last_dot !== false) {
        $file = substr($basename, 0, $last_dot);
        $ext = strtolower(substr($basename, $last_dot));
      } else {
        $file = $basename;
        $ext = '';
      }

      if ( !in_array($ext, array(".ttf", ".otf")) ) {
        throw new Exception("Unable to process fonts of type '$ext'.");
      }

      // Try $file_Bold.$ext etc.
      $path = "$dir/$file";
      
      $patterns = array(
        "bold"        => array("_Bold", "b", "B", "bd", "BD"),
        "italic"      => array("_Italic", "i", "I"),
        "bold_italic" => array("_Bold_Italic", "bi", "BI", "ib", "IB"),
      );
      
      foreach ($patterns as $type => $_patterns) {
        if ( !isset($$type) || !is_readable($$type) ) {
          foreach($_patterns as $_pattern) {
            if ( is_readable("$path$_pattern$ext") ) {
              $$type = "$path$_pattern$ext";
              break;
            }
          }
          
          if ( is_null($$type) )
            if ($debug) echo ("Unable to find $type face file.\n");
        }
      }

      $fonts = compact("normal", "bold", "italic", "bold_italic");
      $entry = array();

      // Copy the files to the font directory.
      foreach ($fonts as $var => $src) {
        if ( is_null($src) ) {
          $entry[$var] = $dompdf->getOptions()->get('fontDir') . '/' . mb_substr(basename($normal), 0, -4);
          continue;
        }

        // Verify that the fonts exist and are readable
        if ( !is_readable($src) )
          throw new Exception("Requested font '$src' is not readable");

        $dest = $dompdf->getOptions()->get('fontDir') . '/' . basename($src);

        if ( !is_writeable(dirname($dest)) )
          throw new Exception("Unable to write to destination '$dest'.");

        if ($debug) echo "Copying $src to $dest...\n";

        if ( !copy($src, $dest) )
          throw new Exception("Unable to copy '$src' to '$dest'");
        
        $entry_name = mb_substr($dest, 0, -4);
        
        if ($debug) echo "Generating Adobe Font Metrics for $entry_name...\n";
        
        $font_obj = Font::load($dest);
        $font_obj->saveAdobeFontMetrics("$entry_name.ufm");
        $font_obj->close();

        $entry[$var] = $entry_name;

      }

      // Store the fonts in the lookup table
      $fontMetrics->setFontFamily($fontname, $entry);

      // Save the changes
      $fontMetrics->saveFontFamilies();

      // Fini
      return true;

    } catch (Exception $e){

      // nada

    }

    return false;

  }