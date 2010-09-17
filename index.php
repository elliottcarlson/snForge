<?php
/*~ handler.php
 .---------------------------------------------------------------------------.
 |  This file is part of the snForge Framework.                              |
 |  Copyright (c), Elliott Carlson <carlson at snforge dot org>.             |
 | ------------------------------------------------------------------------- |
 |                                                                           |
 |  For the full copyright and license information, please view the LICENSE  |
 |  file that was distributed with this source code.                         |
 '---------------------------------------------------------------------------'
*/
/**
 * snForge - snForge Framework
 *
 * This file is the main entry point for the snForge framework. All requests
 * are pushed through the handler.php and handled by the core of snForge.
 * @author Elliott Carlson <carlson ar snforge dot org>
 * @version 1.0
 * @package snForge
 */

/**
 * $snforge_folder tells snforge where it's files are located. Do not
 * include a trailing slash!
 *
 * @var string contains snforge folder structure
 */
    $snforge_folder = 'snforge';

/**
 * Determine full server path
 * 
 * Determines the full server path to $snforge_folder unless it was specified
 * directly. In the event that this is running in a Windows environment, the
 * path gets converted to Unix style serpators for consistency sake.
 */
    if (strpos($snforge_folder, '/') === FALSE)
    {
        if (function_exists('realpath') AND @realpath(dirname(__FILE__)) !== FALSE)
        {
            $snforge_folder = realpath(dirname(__FILE__)).'/'.$snforge_folder;
        }
    }
    else
    {
        // Swap directory separators to Unix style for consistency
        $snforge_folder = str_replace("\\", "/", $snforge_folder); 
    }

/**#@+
 * Constants
 */
/**
 * Determine the file extension
 */
    define('EXT', '.'.pathinfo(__FILE__, PATHINFO_EXTENSION));

/**
 * The full server path to the "snforge" folder
 */
    define('BASEPATH', $snforge_folder.'/');


/**
 * Load the main snForge controller
 * 
 * Load the main controller for snForge using the discovered path.
 */
    require_once(BASEPATH.'core/snForge'.EXT);