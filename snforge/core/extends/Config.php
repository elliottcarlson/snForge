<?php
/*~ snforge/core/extends/Config.php
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

    class Config
    {
        private $_iniFilename = '';
        private $_iniParsedArray = array();

        public function Config($filename)
        {
            $this->_iniFilename = $filename;
            if ($this->_iniParsedArray = parse_ini_file($filename, true))
            {
                return true;
            }
            else
            {
                return false;
            } 
        }

        public function getSection($key)
        {
            return $this->_iniParsedArray[$key];
        }

        public function getValue($section, $key)
        {
            if (!isset($this->_iniParsedArray[$section])) return false;
            return $this->_iniParsedArray[$section][$key];
        }

        public function get($section, $key = NULL)
        {
            if (is_null($key)) return $this->getSection($section);
            return $this->getValue($section, $key);
        }

        public function setSection($section, $array)
        {
            if (!is_array($array)) return false;
            return $this->_iniParsedArray[$section] = $array;
        }

        public function setValue($section, $key, $value)
        {
            if ($this->_iniParsedArray[$section][$key] = $value) return true;
        }

        public function set($section, $key, $value = NULL)
        {
            if (is_array($key) && is_null($value)) return $this->setSection($section, $key);
            return $this->setValue($section, $key, $value);
        }

        public function save($filename = NULL)
        {
            if ($filename == null) $filename = $this->_iniFilename;
            if (is_writeable($filename))
            {
                $SFfdescriptor = fopen($filename, 'w');
                foreach ($this->_iniParsedArray as $section => $array)
                {
                    fwrite($SFfdescriptor, "[".$section."]\n");
                    foreach ($array as $key => $value)
                    {
                        fwrite($SFfdescriptor, "$key = $value\n");
                    }
                    fwrite($SFfdescriptor, "\n");
                }
                fclose($SFfdescriptor);
                return true;
            }
            else
            {
                return false;
            }
        }
    }
?>
