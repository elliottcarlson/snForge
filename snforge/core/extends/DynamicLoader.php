<?php
/*~ snforge/core/extends/DynamicLoader.php
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

    class DynamicLoader
    {
        private $__loaded;
        public $__loaded_methods;
        public $__loaded_method_aliases;
        public $__obj_interface;

        public function __construct()
        {
            $this->__loaded = array();
            $this->__loaded_methods = array();
            $this->__loaded_method_aliases = array();
        }

        protected function load($class, $passed_var = null)
        {
            if (!$class) { return false; }

            $this->__obj_interface =& new $class($passed_var);
            $__obj_name = get_class($this->__obj_interface);

            $__obj_methods = get_class_methods($__obj_name);
            array_push($this->__loaded, array($__obj_name, $this->__obj_interface));

            foreach($__obj_methods as $key => $function_name)
            {
                $this->__loaded_methods[$function_name] = &$this->__obj_interface;
            }
        }

        protected function set_method_alias($alias, $original)
        {
            $this->__loaded_method_aliases[$alias] = $original;
            return true;
        }

        public function __call($method, $args = '')
        {
            if (array_key_exists($method, $this->__loaded_methods))
            {
                $args[] = $this;
                return call_user_func_array(array($this->__loaded_methods[$method], $method), $args);
            }
            elseif (array_key_exists($method, $this->__loaded_method_aliases))
            {
                $args[] = $this;
                return call_user_func_array(array($this->__loaded_methods[$this->__loaded_method_aliases[$method]], $this->__loaded_method_aliases[$method]), $args);
            }
            return false;
        }

        public function __toString()
        {
//            var_dump($this);
        }
    }
?>
