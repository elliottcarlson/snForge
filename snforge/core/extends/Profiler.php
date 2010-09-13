<?php
/*~ snforge/core/extends/Profiler.php
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

/**
 * Profiler
 *
 * Profiler is heavily based on, and in a lot of instances direct code copies of,
 * the excellent profiling system made by Ryan Campbell called PHP Quick Profiler
 * which can be found at http://particletree.com/features/php-quick-profiler/ 
 *
 * The basic idea of this codebase is to offer realtime profiling and debug
 * logging of your snForge based projects. Additionally, this class is used
 * to extend the custom error reporting to allow for quicker bug smashing.
 */

class Profiler
{
    public $output = array();
    public $console_data = array();

    public function __construct()
    {
        $this->startTime = $this->getMicroTime();
        $this->console_data = array();
        $this->getLogs();
    }

    public function log($data)
    {
        $logItem = array('data' => $data,
                         'type' => 'log'
                        );
        $this->console_data['console'][] = $logItem;
        if (in_array('logCount', $this->console_data))
        {
            $this->console_data['logCount'] += 1;
        }
        else
        {
            $this->console_data['logCount'] = 1;
        }
    }

    public function logMemory($object = false, $name = 'PHP')
    {
        $memory = memory_get_usage();
        if ($object) $memory = strlen(serialize($object));
        $logItem = array('data' => $memory,
                         'type' => 'memory',
                         'name' => $name,
                         'dataType' => gettype($object)
                        );
        $this->console_data['console'][] = $logItem;
        if (in_array('memoryCount', $this->console_data))
        {
            $this->console_data['memoryCount'] += 1;
        }
        else
        {
            $this->console_data['memoryCount'] = 1;
        }
    }

    public function logCache($type, $data)
    {
        $logItem = array('data' => $data,
                         'type' => 'cache'
                        );
        if (in_array('cacheCount', $this->console_data))
        {
            $this->console_data['cacheCount'] += 1;
        }
        else
        {
            $this->console_data['cacheCount'] = 1;
        }
    }

    public function logError($exception, $message)
    {
        $logItem = array('data' => $message,
                         'type' => 'error',
                         'file' => $exception->getFile(),
                         'line' => $exception->getLine()
                        );
        $this->console_data['console'][] = $logItem;
        $this->console_data['errorCount'] += 1;
    }

    public function logErrorRaw($message, $errfile = '', $errline = 0)
    {
        $logItem = array('data' => $message,
                         'type' => 'php-error',
                         'file' => $errfile,
                         'line' => $errline
                        );
        $this->console_data['console'][] = $logItem;
        $this->console_data['errorCount'] += 1;
    }

    public function logPHPError($err_type, $err_str, $err_file, $err_line, $backtrace = '')
    {
        $logItem = array('data' => $err_type,
                         'type' => 'php-error',
                         'err_type' => $err_type,
                         'err_str' => $err_str,
                         'err_file' => $err_file,
                         'err_line' => $err_line,
                         'backtrace' => $backtrace
                        );
        $this->console_data['console'][] = $logItem;
		if (array_key_exists('errorCount', $this->console_data))
		{
            $this->console_data['errorCount'] += 1;
		}
		else
		{
            $this->console_data['errorCount'] = 1;
		}
    }

    public function logSpeed($name = 'Point in Time')
    {
        $logItem = array('data' => $this->getMicroTime(),
                         'type' => 'speed',
                         'name' => $name
                        );
        $this->console_data['console'][] = $logItem;
        $this->console_data['speedCount'] += 1;
    }

    public function getLogs()
    {
        if (array_key_exists('memoryCount', $this->console_data) && !$this->console_data['memoryCount']) $this->console_data['memoryCount'] = 0;
        if (array_key_exists('logCount', $this->console_data) && !$this->console_data['logCount']) $this->console_data['logCount'] = 0;
        if (array_key_exists('speedCount', $this->console_data) && !$this->console_data['speedCount']) $this->console_data['speedCount'] = 0;
        if (array_key_exists('errorCount', $this->console_data) && !$this->console_data['errorCount']) $this->console_data['errorCount'] = 0;
        if (array_key_exists('cacheCount', $this->console_data) && !$this->console_data['cacheCount']) $this->console_data['cacheCount'] = 0;
        return $this->console_data;
    }

    public function gatherConsoleData()
    {
        $logs = $this->getLogs();

        foreach ($logs as $key => $log)
        {
            if ($log['type'] == 'log')
            {
                $logs[$key]['data'] = print_r($log['data'], true);
            }
            elseif ($log['type'] == 'memory')
            {
                $logs[$key]['data'] = $this->getReadableFileSize($log['data']);
            }
            elseif ($log['type'] == 'speed')
            {
                $logs[$key]['data'] = $this->getReadableTime(($log['data'] - $this->startTime) * 1000);
            }
        }
        $this->output = $logs;
    }
	
    public function gatherCacheData()
    {
        global $snforge;

        $logs = array();

        if ($snforge->cache)
        {
            $stats = $snforge->cache->getExtendedStats();

            foreach ($stats as $server => $status)
            {
                $this->console_data['cacheCount'] += 1;
                $cache_logs[] = array('type' => 'status', 'data' => 'Memcached stats for: <strong>'.$server.'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Memcache Server version: <strong>'.$status['version'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Process id of this server process: <strong>'.$status['pid'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of seconds this server has been running: <strong>'.$status['uptime'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Accumulated user time for this process: <strong>'.$status['rusage_user'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Accumulated system time for this process: <strong>'.$status['rusage_system'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Total number of items stored by this server ever since it started: <strong>'.$status['total_items'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of open connections: <strong>'.$status['curr_connections'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Total number of connections opened since the server started running: <strong>'.$status['total_connections'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of connection structures allocated by the server: <strong>'.$status['connection_structures'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Cumulative number of retrieval requests: <strong>'.$status['cmd_get'].'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Cumulative number of storage requests: <strong>'.$status['cmd_set'].'</strong>');

                $percCacheHit = ((real)$status['get_hits'] / (real)$status['cmd_get'] * 100);
                $percCacheHit = round($percCacheHit, 3);
                $percCacheMiss = 100 - $percCacheHit;

                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of keys that have been requested and found present: <strong>'.$status['get_hits'].' ('.$percCacheHit.'%)</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of items that have been requested and not found: <strong>'.$status['get_misses'].' ('.$percCacheMiss.'%)</strong>');

                $MBRead = (real)$status['bytes_read'] / (1024 * 1024);
                $MBWrite = (real)$status['bytes_written'] / (1024 * 1024);
                $MBSize = (real)$status['limit_maxbytes'] / (1024 * 1024);
                $MBSizeUsed = (real)$status['bytes'] / (1024 * 1024);
                $MBSizeUsedPerc = round($MBSizeUsed / $MBSize * 100, 3);


                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Total number of bytes read by this server from network: <strong>'.$MBRead.'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Total number of bytes sent by this server to network: <strong>'.$MBWrite.'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of bytes this server is allowed to use for storage: <strong>'.$MBSize.'</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of bytes this server is currently using for storage: <strong>'.$MBSizeUsed.' ('.$MBSizeUsedPerc.'%)</strong>');
                $cache_logs[] = array('type' => 'status', 'data' => '&nbsp; &#8226; Number of valid items removed from cache to free memory for new items: <strong>'.$status['evictions'].'</strong>');
            }
        }
        $this->output['cache'] = $cache_logs;
    }
	
    public function gatherFileData()
    {
        $files = get_included_files();
        $fileList = array();
        $fileTotals = array('count' => count($files),
                            'size' => 0,
                            'largest' => 0,
                           );

        foreach ($files as $key => $file)
        {
            $size = filesize($file);
            $fileList[] = array('name' => $file,
                                'size' => $this->getReadableFileSize($size)
                               );
            $fileTotals['size'] += $size;
            if ($size > $fileTotals['largest']) $fileTotals['largest'] = $size;
        }

        $fileTotals['size'] = $this->getReadableFileSize($fileTotals['size']);
        $fileTotals['largest'] = $this->getReadableFileSize($fileTotals['largest']);
        $this->output['files'] = $fileList;
        $this->output['fileTotals'] = $fileTotals;
    }

    public function gatherMemoryData()
    {
        $memoryTotals = array();
        $memoryTotals['used'] = $this->getReadableFileSize(memory_get_peak_usage());
        $memoryTotals['total'] = ini_get("memory_limit");
        $this->output['memoryTotals'] = $memoryTotals;
    }

    public function gatherQueryData()
    {
        $queryTotals = array();
        $queryTotals['count'] = 0;
        $queryTotals['time'] = 0;
        $queries = array();

        if ($this->db != '')
        {
            $queryTotals['count'] += $this->db->queryCount;
            foreach ($this->db->queries as $key => $query)
            {
                $query = $this->attemptToExplainQuery($query);
                $queryTotals['time'] += $query['time'];
                $query['time'] = $this->getReadableTime($query['time']);
                $queries[] = $query;
            }
        }
        $queryTotals['time'] = $this->getReadableTime($queryTotals['time']);
        $this->output['queries'] = $queries;
        $this->output['queryTotals'] = $queryTotals;
    }

    private function attemptToExplainQuery($query)
    {
        try
        {
            $sql = 'EXPLAIN '.$query['sql'];
            $rs = $this->db->query($sql);
        }
        catch(Exception $e) {}
        if($rs)
        {
            $row = mysql_fetch_array($rs, MYSQL_ASSOC);
            $query['explain'] = $row;
        }
        return $query;
    }

    public function gatherSpeedData()
    {
        $speedTotals = array();
        $speedTotals['total'] = $this->getReadableTime(($this->getMicroTime() - $this->startTime) * 1000);
        $speedTotals['allowed'] = ini_get('max_execution_time');
        $this->output['speedTotals'] = $speedTotals;
    }

    function getMicroTime()
    {
        $time = microtime();
        $time = explode(' ', $time);
        return $time[1] + $time[0];
    }

    public function getReadableFileSize($size, $retstring = null)
    {
        // adapted from code at http://aidanlister.com/repos/v/function.size_readable.php
        $sizes = array('bytes', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        if ($retstring === null) { $retstring = '%01.2f %s'; }
        $lastsizestring = end($sizes);

        foreach ($sizes as $sizestring)
        {
            if ($size < 1024) { break; }
            if ($sizestring != $lastsizestring) { $size /= 1024; }
        }
        if ($sizestring == $sizes[0]) { $retstring = '%01d %s'; } // Bytes aren't normally fractional
        return sprintf($retstring, $size, $sizestring);
    }
	
    public function getReadableTime($time)
    {
        $ret = $time;
        $formatter = 0;
        $formats = array('ms', 's', 'm');
        if ($time >= 1000 && $time < 60000)
        {
            $formatter = 1;
            $ret = ($time / 1000);
        }
        if ($time >= 60000)
        {
            $formatter = 2;
            $ret = ($time / 1000) / 60;
        }
        $ret = number_format($ret, 3, '.', '').' '.$formats[$formatter];
        return $ret;
    }

    public function display($db = '', $master_db = '')
    {
        $this->db = $db;
        $this->master_db = $master_db;

        $this->gatherConsoleData();
        $logCount = count($this->output['console']);
        $this->gatherFileData();
        $fileCount = count($this->output['files']);
        $this->gatherMemoryData();
        $memoryUsed = $this->output['memoryTotals']['used'];
        $this->gatherQueryData();
        $queryCount = $this->output['queryTotals']['count'];
        $this->gatherCacheData();
        $cacheCount = $this->console_data['cacheCount'];
        $this->gatherSpeedData();
        $speedTotal = $this->output['speedTotals']['total'];

        // Display Javascript + CSS
        print "<script type=\"text/javascript\">var PQP_DETAILS=true;var PQP_HEIGHT=\"short\";addEvent(window,'load',loadCSS);function changeTab(tab){var pQp=document.getElementById('pQp');hideAllTabs();addClassName(pQp,tab,true);}function hideAllTabs(){var pQp=document.getElementById('pQp');removeClassName(pQp,'console');removeClassName(pQp,'speed');removeClassName(pQp,'queries');removeClassName(pQp,'cache');removeClassName(pQp,'memory');removeClassName(pQp,'files');}function toggleDetails(){var container=document.getElementById('pqp-container');if(PQP_DETAILS){addClassName(container,'hideDetails',true);PQP_DETAILS=false;}else{removeClassName(container,'hideDetails');PQP_DETAILS=true;}}function toggleHeight(){var container=document.getElementById('pqp-container');if(PQP_HEIGHT==\"short\"){addClassName(container,'tallDetails',true);PQP_HEIGHT=\"tall\";}else{removeClassName(container,'tallDetails');PQP_HEIGHT=\"short\";}}function loadCSS(){var sheet=document.createElement(\"style\");sheet.setAttribute(\"type\",\"text/css\");if(sheet.styleSheet){sheet.styleSheet.cssText=\".pQp{width:100%;text-align:center;position:fixed;bottom:0;}*html .pQp{position:absolute;}.pQp*{margin:0;padding:0;border:none;}#pQp{margin:0 auto;width:85%;min-width:960px;background-color:#222;border:12px solid #000;border-bottom:none;font-family:\\\"Lucida Grande\\\",Tahoma,Arial,sans-serif;-webkit-border-top-left-radius:15px;-webkit-border-top-right-radius:15px;-moz-border-radius-topleft:15px;-moz-border-radius-topright:15px;}#pQp .pqp-box h3{font-weight:normal;line-height:200px;padding:0 15px;color:#fff;}.pQp,.pQp td{color:#444;}#pqp-metrics{background:#000;width:100%;}#pqp-console,#pqp-speed,#pqp-queries,#pqp-cache,#pqp-memory,#pqp-files{background:url(../images/overlay.gif);border-top:1px solid #ccc;height:200px;overflow:auto;}.pQp .green{color:#588E13!important;}.pQp .blue{color:#3769A0!important;}.pQp .purple{color:#953FA1!important;}.pQp .orange{color:#D28C00!important;}.pQp .red{color:#B72F09!important;}#pQp,#pqp-console,#pqp-speed,#pqp-queries,#pqp-cache,#pqp-memory,#pqp-files{display:none;}.pQp .console,.pQp .speed,.pQp .queries,.pQp .cache,.pQp .memory,.pQp .files{display:block!important;}.pQp .console #pqp-console,.pQp .speed #pqp-speed,.pQp .queries #pqp-queries,.pQp .cache #pqp-cache,.pQp .memory #pqp-memory,.pQp .files #pqp-files{display:block;}.console td.green,.speed td.blue,.queries td.purple,.memory td.orange,.files td.red{background:#222!important;border-bottom:6px solid #fff!important;cursor:default!important;}.tallDetails #pQp .pqp-box{height:500px;}.tallDetails #pQp .pqp-box h3{line-height:500px;}.hideDetails #pQp .pqp-box{display:none!important;}.hideDetails #pqp-footer{border-top:1px dotted #444;}.hideDetails #pQp #pqp-metrics td{height:50px;background:#000!important;border-bottom:none!important;cursor:default!important;}.hideDetails #pQp var{font-size:18px;margin:0 0 2px 0;}.hideDetails #pQp h4{font-size:10px;}.hideDetails .heightToggle{visibility:hidden;}#pqp-metrics td{height:80px;width:20%;text-align:center;cursor:pointer;border:1px solid #000;border-bottom:6px solid #444;-webkit-border-top-left-radius:10px;-moz-border-radius-topleft:10px;-webkit-border-top-right-radius:10px;-moz-border-radius-topright:10px;}#pqp-metrics td:hover{background:#222;border-bottom:6px solid #777;}#pqp-metrics .green{border-left:none;}#pqp-metrics .red{border-right:none;}#pqp-metrics h4{text-shadow:#000 1px 1px 1px;}.side var{text-shadow:#444 1px 1px 1px;}.pQp var{font-size:23px;font-weight:bold;font-style:normal;margin:0 0 3px 0;display:block;}.pQp h4{font-size:12px;color:#fff;margin:0 0 4px 0;}.pQp .main{width:80%;}*+html .pQp .main{width:78%;}*html .pQp .main{width:77%;}.pQp .main td{padding:7px 15px;text-align:left;background:#151515;border-left:1px solid #333;border-right:1px solid #333;border-bottom:1px dotted #323232;color:#FFF;}.pQp .main td,pre{font-family:Monaco,\\\"Consolas\\\",\\\"Lucida Console\\\",\\\"Courier New\\\",monospace;font-size:11px;}.pQp .main td.alt{background:#111;}.pQp .main tr.alt td{background:#2E2E2E;border-top:1px dotted #4E4E4E;}.pQp .main tr.alt td.alt{background:#333;}.pQp .main td b{float:right;font-weight:normal;color:#E6F387;}.pQp .main td:hover{background:#2E2E2E;}.pQp .side{float:left;width:20%;background:#000;color:#fff;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;text-align:center;}.pQp .side td{padding:10px 0 5px 0;background:url(../images/side.png)repeat-y right;}.pQp .side var{color:#fff;font-size:15px;}.pQp .side h4{font-weight:normal;color:#F4FCCA;font-size:11px;}#pqp-console .side td{padding:12px 0;}#pqp-console .side td.alt1{background:#588E13;width:51%;}#pqp-console .side td.alt2{background-color:#B72F09;}#pqp-console .side td.alt3{background:#D28C00;border-bottom:1px solid #9C6800;border-left:1px solid #9C6800;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;}#pqp-console .side td.alt4{background-color:#3769A0;border-bottom:1px solid #274B74;}#pqp-console .main table{width:100%;}#pqp-console td div{width:695px;overflow:auto;}#pqp-console td.type{font-family:\\\"Lucida Grande\\\",Tahoma,Arial,sans-serif;text-align:center;text-transform: uppercase;font-size:9px;padding-top:9px;color:#F4FCCA;vertical-align:top;width:40px;}#pqp-cache .main table{width:100%;}#pqp-cache td div{width:695px;overflow:auto;}#pqp-cache td.type{font-family:\\\"Lucida Grande\\\",Tahoma,Arial,sans-serif;text-align:center;text-transform: uppercase;font-size:9px;padding-top:9px;color:#F4FCCA;vertical-align:top;width:40px;}.pQp .log-log td.type{background:#47740D!important;}.pQp .log-error td.type{background:#9B2700!important;}.pQp .log-php-error td.type{background:#FF0000!important;}.pQp .log-memory td.type{background:#D28C00!important;}.pQp .log-speed td.type{background:#2B5481!important;}.pQp .log-log pre{color:#999;}.pQp .log-log td:hover pre{color:#fff;}.pQp .log-memory em,.pQp .log-speed em{float:left;font-style:normal;display:block;color:#fff;}.pQp .log-memory pre,.pQp .log-speed pre{float:right;white-space: normal;display:block;color:#FFFD70;}#pqp-speed .side td{padding:12px 0;}#pqp-speed .side{background-color:#3769A0;}#pqp-speed .side td.alt{background-color:#2B5481;border-bottom:1px solid #1E3C5C;border-left:1px solid #1E3C5C;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;}#pqp-queries .side{background-color:#953FA1;border-bottom:1px solid #662A6E;border-left:1px solid #662A6E;}#pqp-queries .side td.alt{background-color:#7B3384;}#pqp-queries .main b{float:none;}#pqp-cache .side{background-color:#953FA1;border-bottom:1px solid #662A6E;border-left:1px solid #662A6E;}#pqp-cache .side td.alt{background-color:#7B3384;}#pqp-queries .main b{float:none;}#pqp-queries .main em{display:block;padding:2px 0 0 0;font-style:normal;color:#aaa;}#pqp-memory .side td{padding:12px 0;}#pqp-memory .side{background-color:#C48200;}#pqp-memory .side td.alt{background-color:#AC7200;border-bottom:1px solid #865900;border-left:1px solid #865900;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;}#pqp-files .side{background-color:#B72F09;border-bottom:1px solid #7C1F00;border-left:1px solid #7C1F00;}#pqp-files .side td.alt{background-color:#9B2700;}#pqp-footer{width:100%;background:#000;font-size:11px;border-top:1px solid #ccc;}#pqp-footer td{padding:0!important;border:none!important;}#pqp-footer strong{color:#fff;}#pqp-footer a{color:#999;padding:5px 10px;text-decoration:none;}#pqp-footer .credit{width:40%;text-align:left;}#pqp-footer .actions{width:80%;text-align:right;}#pqp-footer .actions a{float:right;width:auto;}#pqp-footer a:hover,#pqp-footer a:hover strong,#pqp-footer a:hover b{background:#fff;color:blue!important;text-decoration:underline;}#pqp-footer a:active,#pqp-footer a:active strong,#pqp-footer a:active b{background:#ECF488;color:green!important;}\";}else{sheet.textContent=\".pQp{width:100%;text-align:center;position:fixed;bottom:0;}*html .pQp{position:absolute;}.pQp*{margin:0;padding:0;border:none;}#pQp{margin:0 auto;width:85%;min-width:960px;background-color:#222;border:12px solid #000;border-bottom:none;font-family:\\\"Lucida Grande\\\",Tahoma,Arial,sans-serif;-webkit-border-top-left-radius:15px;-webkit-border-top-right-radius:15px;-moz-border-radius-topleft:15px;-moz-border-radius-topright:15px;}#pQp .pqp-box h3{font-weight:normal;line-height:200px;padding:0 15px;color:#fff;}.pQp,.pQp td{color:#444;}#pqp-metrics{background:#000;width:100%;}#pqp-console,#pqp-speed,#pqp-queries,#pqp-cache,#pqp-memory,#pqp-files{background:url(../images/overlay.gif);border-top:1px solid #ccc;height:200px;overflow:auto;}.pQp .green{color:#588E13!important;}.pQp .blue{color:#3769A0!important;}.pQp .purple{color:#953FA1!important;}.pQp .orange{color:#D28C00!important;}.pQp .red{color:#B72F09!important;}#pQp,#pqp-console,#pqp-speed,#pqp-queries,#pqp-cache,#pqp-memory,#pqp-files{display:none;}.pQp .console,.pQp .speed,.pQp .queries,.pQp .cache,.pQp .memory,.pQp .files{display:block!important;}.pQp .console #pqp-console,.pQp .speed #pqp-speed,.pQp .queries #pqp-queries,.pQp .cache #pqp-cache,.pQp .memory #pqp-memory,.pQp .files #pqp-files{display:block;}.console td.green,.speed td.blue,.queries td.purple,.memory td.orange,.files td.red{background:#222!important;border-bottom:6px solid #fff!important;cursor:default!important;}.tallDetails #pQp .pqp-box{height:500px;}.tallDetails #pQp .pqp-box h3{line-height:500px;}.hideDetails #pQp .pqp-box{display:none!important;}.hideDetails #pqp-footer{border-top:1px dotted #444;}.hideDetails #pQp #pqp-metrics td{height:50px;background:#000!important;border-bottom:none!important;cursor:default!important;}.hideDetails #pQp var{font-size:18px;margin:0 0 2px 0;}.hideDetails #pQp h4{font-size:10px;}.hideDetails .heightToggle{visibility:hidden;}#pqp-metrics td{height:80px;width:20%;text-align:center;cursor:pointer;border:1px solid #000;border-bottom:6px solid #444;-webkit-border-top-left-radius:10px;-moz-border-radius-topleft:10px;-webkit-border-top-right-radius:10px;-moz-border-radius-topright:10px;}#pqp-metrics td:hover{background:#222;border-bottom:6px solid #777;}#pqp-metrics .green{border-left:none;}#pqp-metrics .red{border-right:none;}#pqp-metrics h4{text-shadow:#000 1px 1px 1px;}.side var{text-shadow:#444 1px 1px 1px;}.pQp var{font-size:23px;font-weight:bold;font-style:normal;margin:0 0 3px 0;display:block;}.pQp h4{font-size:12px;color:#fff;margin:0 0 4px 0;}.pQp .main{width:80%;}*+html .pQp .main{width:78%;}*html .pQp .main{width:77%;}.pQp .main td{padding:7px 15px;text-align:left;background:#151515;border-left:1px solid #333;border-right:1px solid #333;border-bottom:1px dotted #323232;color:#FFF;}.pQp .main td,pre{font-family:Monaco,\\\"Consolas\\\",\\\"Lucida Console\\\",\\\"Courier New\\\",monospace;font-size:11px;}.pQp .main td.alt{background:#111;}.pQp .main tr.alt td{background:#2E2E2E;border-top:1px dotted #4E4E4E;}.pQp .main tr.alt td.alt{background:#333;}.pQp .main td b{float:right;font-weight:normal;color:#E6F387;}.pQp .main td:hover{background:#2E2E2E;}.pQp .side{float:left;width:20%;background:#000;color:#fff;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;text-align:center;}.pQp .side td{padding:10px 0 5px 0;background:url(../images/side.png)repeat-y right;}.pQp .side var{color:#fff;font-size:15px;}.pQp .side h4{font-weight:normal;color:#F4FCCA;font-size:11px;}#pqp-console .side td{padding:12px 0;}#pqp-console .side td.alt1{background:#588E13;width:51%;}#pqp-console .side td.alt2{background-color:#B72F09;}#pqp-console .side td.alt3{background:#D28C00;border-bottom:1px solid #9C6800;border-left:1px solid #9C6800;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;}#pqp-console .side td.alt4{background-color:#3769A0;border-bottom:1px solid #274B74;}#pqp-console .main table{width:100%;}#pqp-console td div{width:740px;overflow:auto;}#pqp-console td.type{font-family:\\\"Lucida Grande\\\",Tahoma,Arial,sans-serif;text-align:center;text-transform: uppercase;font-size:9px;padding-top:9px;color:#F4FCCA;vertical-align:top;width:40px;}#pqp-cache .main table{width:100%;}#pqp-cache td div{width:740px;overflow:auto;}#pqp-cache td.type{font-family:\\\"Lucida Grande\\\",Tahoma,Arial,sans-serif;text-align:center;text-transform: uppercase;font-size:9px;padding-top:9px;color:#F4FCCA;vertical-align:top;width:40px;}.pQp .log-log td.type{background:#47740D!important;}.pQp .log-error td.type{background:#9B2700!important;}.pQp .log-php-error td.type{background:#FF0000!important;}.pQp .log-memory td.type{background:#D28C00!important;}.pQp .log-speed td.type{background:#2B5481!important;}.pQp .log-log pre{color:#999;}.pQp .log-log td:hover pre{color:#fff;}.pQp .log-memory em,.pQp .log-speed em{float:left;font-style:normal;display:block;color:#fff;}.pQp .log-memory pre,.pQp .log-speed pre{float:right;white-space: normal;display:block;color:#FFFD70;}#pqp-speed .side td{padding:12px 0;}#pqp-speed .side{background-color:#3769A0;}#pqp-speed .side td.alt{background-color:#2B5481;border-bottom:1px solid #1E3C5C;border-left:1px solid #1E3C5C;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;}#pqp-queries .side{background-color:#953FA1;border-bottom:1px solid #662A6E;border-left:1px solid #662A6E;}#pqp-queries .side td.alt{background-color:#7B3384;}#pqp-cache .side{background-color:#953FA1;border-bottom:1px solid #662A6E;border-left:1px solid #662A6E;}#pqp-cache .side td.alt{background-color:#7B3384;}#pqp-queries .main b{float:none;}#pqp-queries .main b{float:none;}#pqp-queries .main em{display:block;padding:2px 0 0 0;font-style:normal;color:#aaa;}#pqp-memory .side td{padding:12px 0;}#pqp-memory .side{background-color:#C48200;}#pqp-memory .side td.alt{background-color:#AC7200;border-bottom:1px solid #865900;border-left:1px solid #865900;-webkit-border-bottom-left-radius:30px;-moz-border-radius-bottomleft:30px;}#pqp-files .side{background-color:#B72F09;border-bottom:1px solid #7C1F00;border-left:1px solid #7C1F00;}#pqp-files .side td.alt{background-color:#9B2700;}#pqp-footer{width:100%;background:#000;font-size:11px;border-top:1px solid #ccc;}#pqp-footer td{padding:0!important;border:none!important;}#pqp-footer strong{color:#fff;}#pqp-footer a{color:#999;padding:5px 10px;text-decoration:none;}#pqp-footer .credit{width:40%;text-align:left;}#pqp-footer .actions{width:80%;text-align:right;}#pqp-footer .actions a{float:right;width:auto;}#pqp-footer a:hover,#pqp-footer a:hover strong,#pqp-footer a:hover b{background:#fff;color:blue!important;text-decoration:underline;}#pqp-footer a:active,#pqp-footer a:active strong,#pqp-footer a:active b{background:#ECF488;color:green!important;}\";}document.getElementsByTagName(\"head\")[0].appendChild(sheet);setTimeout(function(){document.getElementById(\"pqp-container\").style.display=\"block\"},10);}function addClassName(objElement,strClass,blnMayAlreadyExist){if(objElement.className){var arrList=objElement.className.split(' ');if(blnMayAlreadyExist){var strClassUpper=strClass.toUpperCase();for(var i=0;i<arrList.length;i++){if(arrList[i].toUpperCase()==strClassUpper){arrList.splice(i,1);i--;}}}arrList[arrList.length]=strClass;objElement.className=arrList.join(' ');}else{objElement.className=strClass;}}function removeClassName(objElement,strClass){if(objElement.className){var arrList=objElement.className.split(' ');var strClassUpper=strClass.toUpperCase();for(var i=0;i<arrList.length;i++){if(arrList[i].toUpperCase()==strClassUpper){arrList.splice(i,1);i--;}}objElement.className=arrList.join(' ');}}function addEvent(obj,type,fn){if(obj.attachEvent){obj[\"e\"+type+fn]=fn;obj[type+fn]=function(){obj[\"e\"+type+fn](window.event)};obj.attachEvent(\"on\"+type,obj[type+fn]);}else{obj.addEventListener(type,fn,false);}}</script>\n";
        print "<div id=\"pqp-container\" class=\"pQp\" style=\"display: none\">";

        // Display Tabs
        print "<div id=\"pQp\" class=\"console\"><table id=\"pqp-metrics\" cellspacing=\"0\"><tr>";
        print "<td class=\"green\" onclick=\"changeTab('console');\"><var>$logCount</var><h4>Console</h4></td>";
        print "<td class=\"blue\" onclick=\"changeTab('speed');\"><var>$speedTotal</var><h4>Load Time</h4></td>";
        if ($queryCount)        
            print "<td class=\"purple\" onclick=\"changeTab('queries');\"><var>$queryCount Queries</var><h4>Database</h4></td>";
        if ($cacheCount)
            print "<td class=\"purple\" onclick=\"changeTab('cache');\"><var>$cacheCount</var><h4>Memcached Servers</h4></td>";
        print "<td class=\"orange\" onclick=\"changeTab('memory');\"><var>$memoryUsed</var><h4>Memory Used</h4></td>";
        print "<td class=\"red\" onclick=\"changeTab('files');\"><var>{$fileCount} Files</var><h4>Included</h4></td>";
        print "</tr></table>\n";

        // Display Console Tab
        print "<div id=\"pqp-console\" class=\"pqp-box\">";
        if ($logCount ==  0)
        {
	    print "<h3>This panel has no log items.</h3>";
        }
        else
        {
	    print "<table class=\"side\" cellspacing=\"0\"><tr><td class=\"alt1\"><var>".$this->output['logCount']."</var><h4>Logs</h4></td><td class=\"alt2\"><var>".$this->output['errorCount']."</var><h4>Errors</h4></td></tr><tr><td class=\"alt3\"><var>".$this->output['memoryCount']."</var><h4>Memory</h4></td><td class=\"alt4\"><var>".$this->output['speedCount']."</var><h4>Speed</h4></td></tr></table><table class=\"main\" cellspacing=\"0\">";
            $class = '';

            foreach ($this->output['console'] as $log)
            {
                if ($log['type'] == 'php-error')
                {
                    print "<tr class=\"log-error\"><td class=\"type\">".$log['err_type']."</td><td class=\"".$class."\">";
                }
                else
                {
                    print "<tr class=\"log-".$log['type']."\"><td class=\"type\">".$log['type']."</td><td class=\"".$class."\">";
                }
                switch($log['type'])
                {
                    case 'log':
                        print "<div><pre>".$log['data']."</pre></div>";
                        break;
                    case 'memory':
                        print "<div><pre>".$log['data']."</pre> <em>".$log['dataType']."</em>: ".$log['name']."</div>";
                        break;
                    case 'speed':
                        print "<div><pre>".$log['data']."</pre> <em>".$log['name']."</em></div>";
                        break;
                    case 'error':
                        print "<div><em>Line ".$log['line']."</em> : ".$log['data']." <pre>".$log['file']."</pre></div>";
                        break;
                    case 'php-error':
                        print "<div><em>".$log['err_str']."</em> in <strong>".$log['err_file']."</strong> on line <strong>".$log['err_line']."</strong><pre>".$log['backtrace']."</pre></div>";
                        break;
                }
                print "</td></tr>";
                $class = ($class == '') ? 'alt' : '';
            }
            print "</table>";
        }
        print "</div>";

        // Display Speed Tab
        print "<div id=\"pqp-speed\" class=\"pqp-box\">";
        if ($this->output['speedCount'] ==  0)
        {
	    print "<h3>This panel has no log items.</h3>";
        }
        else
        {
            print "<table class=\"side\" cellspacing=\"0\"><tr><td><var>".$this->output['speedTotals']['total']."</var><h4>Load Time</h4></td></tr><tr><td class=\"alt\"><var>".$this->output['speedTotals']['allowed']."</var> <h4>Max Execution Time</h4></td></tr></table><table class=\"main\" cellspacing=\"0\">";
            $class = '';

            foreach($this->output['console'] as $log)
            {
                if ($log['type'] == 'speed')
                {
                    print "<tr class=\"log-".$log['type']."\"><td class=\"".$class."\"><div><pre>".$log['data']."</pre> <em>".$log['name']."</em></div></td></tr>";
                    $class = ($class == '') ? 'alt' : '';
                }
            }
            print "</table>";
        }
        print "</div>";

        // Display Queries Tab
        print "<div id=\"pqp-queries\" class=\"pqp-box\">";
        if ($this->output['queryTotals']['count'] ==  0)
        {
	    print "<h3>This panel has no log items.</h3>";
        }
        else
        {
            print "<table class=\"side\" cellspacing=\"0\"><tr><td><var>".$this->output['queryTotals']['count']."</var><h4>Total Queries</h4></td></tr><tr><td class=\"alt\"><var>".$this->output['queryTotals']['time']."</var><h4>Total Time</h4></td></tr><tr><td><var>0</var><h4>Duplicates</h4></td></tr></table>";
            print "<table class=\"main\" cellspacing=\"0\">";

            $class = '';
            foreach ($this->output['queries'] as $query)
            {
                print "<tr><td class=\"".$class."\">".$query['sql'];
                if ($query['explain'])
                {
                    print "<em>Possible keys: <b>".$query['explain']['possible_keys']."</b> &middot; Key Used: <b>".$query['explain']['key']."</b> &middot; Type: <b>".$query['explain']['type']."</b> &middot; Rows: <b>".$query['explain']['rows']."</b> &middot; Speed: <b>".$query['time']."</b></em>";
                }
                print "</td></tr>";
                $class = ($class == '') ? 'alt' : '';
            }
            print "</table>";
        }
        print "</div>";

        // Display Memcached Tab
        print "<div id=\"pqp-cache\" class=\"pqp-box\">";
        if ($cacheCount ==  0)
        {
	    print "<h3>This panel has no log items.</h3>";
        }
        else
        {
            print "<table class=\"side\" cellspacing=\"0\"><tr><td><var>".$this->console_data['cacheCount']."</var><h4>Memcached Servers</h4></td></tr></table>";
            print "<table class=\"main\" cellspacing=\"0\">";

            $class = '';
            foreach ($this->output['cache'] as $cache_id => $cache_item)
            {
                print "<tr class=\"log-".$cache_item['type']."\"><td class=\"type\">".$cache_item['type']."</td><td class=\"".$class."\">";
                print "<div><pre>".$cache_item['data']."</pre></div>";
                print "</td></tr>";
                $class = ($class == '') ? 'alt' : '';
            }
            print "</table>";
        }
        print "</div>";

        // Display Memory Tab
        print "<div id=\"pqp-memory\" class=\"pqp-box\">";
        if ($this->output['memoryCount'] ==  0)
        {
	    print "<h3>This panel has no log items.</h3>";
        }
        else
        {
            print "<table class=\"side\" cellspacing=\"0\"><tr><td><var>".$this->output['memoryTotals']['used']."</var><h4>Used Memory</h4></td></tr><tr><td class=\"alt\"><var>".$this->output['memoryTotals']['total']."</var><h4>Total Available</h4></td></tr></table>";
            print "<table class=\"main\" cellspacing=\"0\">";

            $class = '';
            foreach ($this->output['console'] as $log)
            {
                if ($log['type'] == 'memory')
                {
                    print "<tr class=\"log-".$log['type']."\"><td class=\"".$class."\"><b>".$log['data']."</b> <em>".$log['dataType']."</em>: ".$log['name']."</td></tr>";
                    $class = ($class == '') ? 'alt' : '';
                }
            }
            print "</table>";
        }
        print "</div>";

        // Display Files Tab
        print "<div id=\"pqp-files\" class=\"pqp-box\">";
        if ($this->output['fileTotals']['count'] ==  0)
        {
	    print "<h3>This panel has no log items.</h3>";
        }
        else
        {
            print "<table class=\"side\" cellspacing=\"0\"><tr><td><var>".$this->output['fileTotals']['count']."</var><h4>Total Files</h4></td></tr><tr><td class=\"alt\"><var>".$this->output['fileTotals']['size']."</var><h4>Total Size</h4></td></tr><tr><td><var>".$this->output['fileTotals']['largest']."</var><h4>Largest</h4></td></tr></table>";
            print "<table class=\"main\" cellspacing=\"0\">";

            $class ='';
            foreach ($this->output['files'] as $file)
            {
                print "<tr><td class=\"".$class."\"><b>".$file['size']."</b> ".$file['name']."</td></tr>";
                $class = ($class == '') ? 'alt' : '';
            }
            print "</table>";
        }
        print "</div>";

        // Display Footer
        print "<table id=\"pqp-footer\" cellspacing=\"0\"><tr><td class=\"credit\">Modified and integrated version of  PhpQuickProfiler</td><td class=\"actions\"><a href=\"#\" onclick=\"toggleDetails();return false\">Details</a><a class=\"heightToggle\" href=\"#\" onclick=\"toggleHeight();return false\">Height</a></td></tr></table>";
        print "</div></div>";
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        global $config;

        $errno = $errno & error_reporting();
        if ($errno == 0) return;
        if (!defined('E_STRICT'))            define('E_STRICT', 2048);
        if (!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

        $err_type = '';
        switch($errno)
        {
            case E_ERROR:               $err_type = "Error";                  break;
            case E_WARNING:             $err_type = "Warning";                break;
            case E_PARSE:               $err_type = "Parse Error";            break;
            case E_NOTICE:              $err_type = "Notice";                 break;
            case E_CORE_ERROR:          $err_type = "Core Error";             break;
            case E_CORE_WARNING:        $err_type = "Core Warning";           break;
            case E_COMPILE_ERROR:       $err_type = "Compile Error";          break;
            case E_COMPILE_WARNING:     $err_type = "Compile Warning";        break;
            case E_USER_ERROR:          $err_type = "User Error";             break;
            case E_USER_WARNING:        $err_type = "User Warning";           break;
            case E_USER_NOTICE:         $err_type = "User Notice";            break;
            case E_STRICT:              $err_type = "Strict Notice";          break;
            case E_RECOVERABLE_ERROR:   $err_type = "Recoverable Error";      break;
            default:                    $err_type = "Unknown error ($errno)"; break;
        }

        $backtrace_data = '';

        if (function_exists('debug_backtrace') && $config->get('debug', 'enable_backtrace'))
        {
            $backtrace = debug_backtrace();
            array_shift($backtrace);
            foreach ($backtrace as $i => $l)
            {
                if (!array_key_exists('class', $l)) { $l['class'] = ''; }
                if (!array_key_exists('type', $l)) { $l['type'] = ''; }
                if (!array_key_exists('function', $l)) { $l['function'] = ''; }
                if (!array_key_exists('file', $l)) { $l['file'] = ''; }
                if (!array_key_exists('line', $l)) { $l['line'] = ''; }

                $backtrace_data.= "[$i] in function <strong>{$l['class']}{$l['type']}{$l['function']}</strong>";
                if ($l['file']) $backtrace_data.= " in <strong>{$l['file']}</strong>";
                if ($l['line']) $backtrace_data.= " on line <strong>{$l['line']}</strong>";
                $backtrace_data.= "\n";
            }
        }

        $this->logPHPError($err_type, $errstr, $errfile, $errline, $backtrace_data);

        switch($errno)
        {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
                $this->display();
                die;
        }
    }

    function __destruct()
    {
        global $config;

        if ($config && $config->get('debug', 'show_profiler'))
        {
            $this->display();
        }
    }  
}

if (!function_exists('memory_get_peak_usage'))
{
    function memory_get_peak_usage($real_usage = false)
    {
        return memory_get_usage($real_usage);
    }
} 
?>
