<?php
/*~ snforge/core/snForge.php
 .---------------------------------------------------------------------------.
 |  Software: snForge                                                        |
 |   Version: 1.0                                                            |
 |   Contact: http://www.snforge.com/                                        |
 | ------------------------------------------------------------------------- |
 |    Author: Elliott Carlson <carlson at snforge dot com>                   |
 | Copyright (c) 2009-2014, Elliott Carlson. All Rights Reserved.            |
 | ------------------------------------------------------------------------- |
 |   License: Distributed under the Lesser General Public License (LGPL)     |
 |            http://www.gnu.org/copyleft/lesser.html                        |
 |                                                                           |
 |   THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS     |
 |   "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT       |
 |   LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR   |
 |   A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT    |
 |   OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,   |
 |   SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT        |
 |   LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,   |
 |   DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY   |
 |   THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT     |
 |   (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE   |
 |   OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.    |
 '---------------------------------------------------------------------------'
*/

/**
 * Prevent direct call via URI
 */
    if (!defined('BASEPATH')) header("Location: /");

/**
 * Load required files
 *
 * Load our additional files that contain needed functionality for snForge to
 * run properly.
 */
    require(BASEPATH.'core/extends/DynamicLoader'.EXT);
    require(BASEPATH.'core/extends/Plugin'.EXT);

/**
 * Disable magic quotes at runtime
 *
 * To prevent escaped characters within data, we disable magic quotes at runtime.
 * Due to the recent deprecation of this function in PHP > 5.3.0, we add the
 * function_exists test.
 */
    if (function_exists('set_magic_quotes_runtime'))
    {
        @set_magic_quotes_runtime(0);
    }

/**
 * Load Profiler/Debugger
 *
 * If the config indicates that we are running in debug mode, then load the Profiler.
 */
    $snforge->profiler = new Plugin(array('name' => 'Profiler', 'class' => BASEPATH.'core/extends/Profiler.php'));

/**
 * Manage error reporting levels and define our custom error handler.
 */
    @error_reporting(E_ALL);
    set_error_handler(array($snforge->profiler, 'errorHandler'));

/**
 * Load configuration data
 *
 * Loads the configuration data from the config.ini file located within the config
 * folder. Returns an object the allows retrieval of configartion settings.
 */
    $config = new Plugin(array('name' => 'Config', 'class' => BASEPATH.'core/extends/Config.php'), BASEPATH.'config/config.ini');

/**
 * Check status of the cache directory
 *
 * Check for the existance, and the properties of the cache directory. If it does
 * not exist, attempt to create it. If it has incorrect parameters, attempt to chmod
 * the directory. If all fails, display an error.
 */
    if (!is_dir(BASEPATH.'/cache'))
    {
        // Directory does NOT exists - attempt to create
        if (!@mkdir(BASEPATH.'cache', 0700))
        {
            // Directory creation failed - display error
            include(BASEPATH.'core/helper/error_create_cache.php');
            exit;
        }
    }
    else
    {
        // The file exists - ensure it's writable by PHP
        if (!is_writable(BASEPATH.'cache'))
        {
            // The directory is not writable, attempt to change the permissions
            $orig_umask = umask(0);
            if (!chmod(BASEPATH.'cache', 0760))
            {
                // Cache directory is still not writable. Error out.
                include(BASEPATH.'core/helper/error_not_writable.php');
                exit;
            }
            umask($orig_umask);
            clearstatcache();

            // See if our chmod() worked...
            if (!is_writable(BASEPATH.'cache'))
            {
                // Cache directory is still not writable. Error out.
                include(BASEPATH.'core/helper/error_not_writable.php');
                exit;
            }
        }
    }

/**
 * Autoload components
 * 
 * If there are any components set to be autoloaded, do so now.
 * This step is the glue that holds everything together. While
 * some "Plugins" are simply additions that you want autoloaded,
 * others are ciritcal for the system to function correctly.
 * These critical components are located in the core/extends
 * directory, and are built this way to allow any tweaking
 * desired.
 */
    foreach ($config->getSection('autoload') as $loader => $component)
    {
        // Build a "Plugin" array as used by the Plugin/DynamicLoader
        // component loading system.
        $component_data = array();
        $component_data['name'] = $config->get('plugin.'.$component, 'name');
        $component_data['class'] = preg_replace_callback('/\%([A-Z]+)\%/', 'replace_with_constant', $config->get('plugin.'.$component, 'class'));
        $snforge->profiler->log('Loading class '.$component_data['name'].' from '.$component_data['class']);
        foreach ($config->getSection('plugin.'.$component) as $key => $value)
        {
            // Some components can pre-set variables prior to
            // constructing the object.
            if (preg_match('/^var\.(.*)/', $key, $matches))
            {
                $component_data['var_set'][$matches[1]] = preg_replace_callback('/\%([A-Z]+)\%/', 'replace_with_constant', $value);
            }
            // Some components require an immediate call - this
            // acts as the constructor for the component.
            if (preg_match('/^call\.(.*)/', $key, $matches))
            {
                $component_data['pre_run'][$matches[1]] = $value;
            }
        }
        // Build the object as a new snForge plugin.
        $snforge->$loader = new Plugin($component_data);
    }

    function replace_with_constant($constant)
    {
        return constant($constant[1]);
    }

/**
 * Load URI handler
 *
 * Loads in the default URI handler. This can be overridden with your own, however out of the
 * box it should be sufficient in most scenarios.
 */
    $snforge->URI = new Plugin(array('name' => 'URI', 'class' => BASEPATH.'core/extends/URI.php'));


/**
 * Fin.
 *
 * At this point there is nothing left to do as all other functionality
 * should have been farmed out to the necessary components.
 */
