<?php
/*~ snforge/core/extends/Plugin.php
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
    $__snforge__plugins = array();

    class Plugin extends DynamicLoader
    {
        public static function class_loader($plugin)
        {
            global $__snforge__plugins;

            if (array_key_exists($plugin, $__snforge__plugins) && file_exists($__snforge__plugins[$plugin]['class']))
            {
                require_once $__snforge__plugins[$plugin]['class'];
            }
            else
            {
                eval("class $plugin { }");
                return false;
            }
        }

        public function __construct($plugin = array(), $passed_var = null)
        {
            global $__snforge__plugins;
            parent::__construct();
            
            //echo "Plugin: \$plugin <pre>".print_r($plugin)."</pre><br>";
            //echo "Plugin: \$passed_var <pre>".print_r($passed_var)."</pre><br>";

            $__snforge__plugins[$plugin['name']] = $plugin;
            $this->load($plugin['name'], $passed_var);

            if (isset($plugin['var_set']) && is_array($plugin['var_set']))
            {
                foreach ($plugin['var_set'] as $name => $value)
                {
                    $this->__set($name, $value);
                }
            }

            if (isset($plugin['pre_run']))
            {
                if (is_array($plugin['pre_run']))
                {
                    foreach ($plugin['pre_run'] as $name => $value)
                    {
                        parent::__call($name, $value);
                    }
                }
                else
                {
                    parent::__call($plugin['pre_run']);
                }
            }

            if (isset($plugin['alias']) && is_array($plugin['alias']))
            {
                foreach ($plugin['alias'] as $name => $value)
                {
                    parent::set_method_alias($name, $value);
                }
            }
        }

        public function __get($member)
        {
            if (isset($this->__obj_interface->$member))
            {
                return $this->__obj_interface->$member;
            }
            else if (is_callable(array($this->__obj_interface, '__getter')))
            {
                return call_user_func(array($this->__obj_interface, '__getter'), $member);
            }
        }

        public function __set($member, $value)
        {
            if (isset($this->__obj_interface->$member))
            {
                $this->__obj_interface->$member = $value;
            }
            else if (is_callable(array($this->__obj_interface, '__setter')))
            {
                return call_user_func(array($this->__obj_interface, '__setter'), $member, $value);
            }
        }
    }

    spl_autoload_register(array('Plugin', 'class_loader'));
?>
