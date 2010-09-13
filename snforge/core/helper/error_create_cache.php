<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>snForge :: Error</title>
  <style type="text/css">
   body
   {
       padding: 20px;
       background-color: #FFF;
       font: 100.01% "Trebuchet MS",Verdana,Arial,sans-serif;
   }

   h1, h2, p
   {
       margin: 0 10px;
   }

   h1
   {
       font-size: 250%;
       color: #FFF;
   }

   h2
   {
       font-size: 200%;
       color: #f0f0f0;
   }

   p
   {
       padding-bottom: 1em;
   }

   h2
   {
       padding-top: 0.3em;
   }

   #content
   {
       margin: 0 10%;
       background: #9BD1FA;
   }

   b.rtop, b.rbottom
   {
       display: block;
       background: #FFF;
   }

   b.rtop b, b.rbottom b
   {
       display: block;
       height: 1px;
       overflow: hidden;
       background: #9BD1FA;
   }

   b.r1
   {
       margin: 0 5px;
   }

   b.r2
   {
       margin: 0 3px;
   }

   b.r3
   {
       margin: 0 2px;
   }

   b.rtop b.r4, b.rbottom b.r4
   {
       margin: 0 1px;
       height: 2px;
   }
  </style>
 </head>
 <body>
  <div id="content">
   <b class="rtop"><b class="r1"></b><b class="r2"></b><b class="r3"></b><b class="r4"></b></b>
   <h1>Error</h1>
   <p>
    snForge uses (in default installs) a caching directory to cache and compile all template files. This directory
    must exist and be writable via PHP (which is usually running under the web user). snForge has attempted to create
    this directory on it's own, however due to restricted permissions it was unable to do so. Using your preferred 
    method of FTP,  telnet, SSH or SCP please create a directory named 'cache' under the following path:
    <nobr>'<?=BASEPATH?>'.</nobr> Please ensure that the newly created directory has proper write permissions 
    assigned to it.
   </p>
   <p>
    If you do not understand the above instructions, or have trouble creating the directory please contact your 
    webhosts technical support. If you continue to see this error, please visit <a href="http://support.snforge.com/">
    snForge Support</a> for further assistance.
   </p>
   <b class="rbottom"><b class="r4"></b><b class="r3"></b><b class="r2"></b><b class="r1"></b></b>
  </div>
 </body>
</html>
