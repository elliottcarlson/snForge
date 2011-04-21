<?php
/*~ snforge/core/extends/URI.php
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

    if (!defined('BASEPATH')) exit('No direct script access allowed');

    class URI
    {
        // Default names for unspecified controller and functions.
        private $default_controller = 'main';
        private $default_function = 'main';

        // Initialize variables
        private $request_uri = '';
        private $query_string = '';
        public $controller = '';
        public $function = '';
        public $vars = array();

        public function __construct($request_uri = '')
        {
            // Determine the REQUEST_URI to use and return false if none were available
            $this->request_uri = ($request_uri != '') ? $request_uri : (($_SERVER['REQUEST_URI'] != '') ? $_SERVER['REQUEST_URI'] : '');
            if ($this->request_uri == '') return false;

            // Start the URI breakdown process
            $this->parse_request_uri();

            // Determine what controller or view to load
            // This function is the default methodology within snForge and assumes
            // Smarty is the display controller. If this is not the case, make
            // any appropriate changes within this function.
            $this->execute();
        }

        private function parse_request_uri()
        {
            // Remove the physical path to handler.php to allow for deep folder deployment of snForge
            $this->request_uri = preg_replace('/^'.preg_quote(dirname($_SERVER['SCRIPT_NAME']),"/").'/', '', $this->request_uri);

            // Seperate the query string (if any) from the Request URI
            if (strpos($this->request_uri, '?'))
            {
                list($this->request_uri, $this->query_string) = explode('?', $this->request_uri, 2);
            }

            // Remove leading and trailing slash
            $this->request_uri = (substr($this->request_uri, -1) == '/') ? substr($this->request_uri, 0, -1) : $this->request_uri;
            $this->request_uri = (substr($this->request_uri, 0, 1) == '/') ? substr($this->request_uri, 1) : $this->request_uri;

            // Break the request URI in to an array
            $request_uri_items = explode('/', $this->request_uri);

            // Determine the controller
            $this->controller = ($request_uri_items[0]) ? $request_uri_items[0] : $this->default_controller;
            $this->controller = preg_replace('/-/', '_', $this->controller);

            // Determine the function
            $this->function = (array_key_exists(1, $request_uri_items) && $request_uri_items[1]) ? $request_uri_items[1] : $this->default_function;
            $this->function = preg_replace('/-/', '_', $this->function);

            // Parse the Query String data to the vars array
            parse_str($this->query_string, $this->vars);

            // Add additional URI elements as parameters in the vars array
            for ($x = 2; $x < count($request_uri_items); $x++)
            {
                $this->vars['param'.($x-1)] = $request_uri_items[$x];
            }

            // Parse the _POST data and add to vars array
            // Additionally, a reserved keyword __snforge__POSTED is set to true
            foreach ($_POST as $key => $value)
            {
                $this->vars[$key] = $value;
                $this->vars['__snforge__POSTED'] = true;
            }

            // Add the controller and function as reserved keywords to vars array,
            // This is added at the end of all other routines to prevent overwriting
            $this->vars['__snforge__controller'] = $this->controller;
            $this->vars['__snforge__function']   = $this->function;
        }

        private function execute()
        {
            global $snforge, $config;

            // First attempt to locate the controller...
            if (file_exists(BASEPATH.'controller/'.$this->controller.EXT))
            {
                // Attempt to load the controller as a Plugin
                $sn_controller = new Plugin(array('name' => $this->controller,
                                                  'class' => BASEPATH.'controller/'.$this->controller.EXT),
                                                  $this->vars);

                // Determine if the specified function exists and run it
                if ($this->function && $this->function != $this->controller)
                {
                    if (in_array($this->function, array_keys($sn_controller->__loaded_methods)))
                    {
                        $use_function = $this->function;
                        $sn_controller->$use_function($this->vars);
                    }
                    else if (in_array($this->default_function, array_keys($sn_controller->__loaded_methods)))
                    {
                        $use_function = $this->default_function;
                        $sn_controller->$use_function($this->vars);
                    }
                }
                exit;
            }
            // If the specified controller does not exist, attempt to load the
            // template file based on the same name
            else if (isset($snforge->template) && file_exists(BASEPATH.'view/'.$this->controller.'.tpl'))
            {
                // Assign all the assigned variables to the template file
                foreach ($this->vars as $key => $value)
                {
                    $snforge->template->assign($key, $value);
                }
                $snforge->template->display($this->controller.'.tpl');
            }
            // If the controller does not exist, and if there is no template engine
            // assigned, but a template file does exist; then simply display it.
            else if (file_exists(BASEPATH.'view/'.$this->controller.'.tpl'))
            {
                readfile(BASEPATH.'view/'.$this->controller.'.tpl');
                exit;
            }
            // As a fallback, if the controller and template could not be loaded,
            // attempt to load the default controller.
            else if (file_exists(BASEPATH.'controller/'.$this->default_controller.EXT))
            {
                $sn_controller = new Plugin(array('name' => $this->default_controller,
                                                  'class' => BASEPATH.'controller/'.$this->default_controller.EXT),
                                                  $this->vars);
            }
            // Fallback #2 consists of attempting to load the default template view.
            else if (file_exists(BASEPATH.'view/'.$this->default_controller.'.tpl'))
            {
                // If a template engine has been defined then use it:
                if (isset($snforge->template))
                {
                    // Assign all the assigned variables to the template file
                    foreach ($this->vars as $key => $value)
                    {
                        $snforge->template->assign($key, $value);
                    }
                    $snforge->template->display($this->default_controller.'.tpl');
                }
                else
                {
                    readfile(BASEPATH.'view/'.$this->default_controller.'.tpl');
                    exit;
                }
            }
        }
    }
