<?php
require "modules/Rehike/Constants.php";
require "vendor/autoload.php";

// Declare and install the Rehike autoloader.
function YcRehikeAutoloader($class)
{
   $filename = str_replace("\\", "/", $class);

   if (file_exists("modules/{$filename}.php")) {
      require "modules/{$filename}.php";
   }
   else if (file_exists("modules/generated/{$filename}.php"))
   {
      require "modules/generated/{$filename}.php";
   }
   else if ("Rehike/Model/" == substr($filename, 0, 13))
   {
      $file = substr($filename, 13, strlen($filename));

      require "models/${file}.php";
   }
   else if ("Rehike/Controller" == substr($filename, 0, 17))
   {
      $file = substr($filename, 17, strlen($filename));

      require "controllers/${file}.php";
   }

   // Implement the fake magic method __initStatic
   // for automatically initialising static classses.
   if (method_exists($class, "__initStatic"))
   {
      $class::__initStatic();
   }
}
spl_autoload_register('YcRehikeAutoloader');

// Does not properly autoload (this should be fixed)
include "modules/YukisCoffee/GetPropertyAtPath.php";

use Rehike\ControllerV2\Core as ControllerV2;
use Rehike\TemplateManager;

TemplateManager::registerGlobalState($yt);

// Pass resource constants to the templater
TemplateManager::addGlobal('ytConstants', $ytConstants);
TemplateManager::addGlobal('PIXEL', $ytConstants->pixelGif);

////////////////////////////////////////////////
// Temporary Controller V1 compatibility code //
$twig = &TemplateManager::exposeTwig();
$template = &TemplateManager::exposeTemplate();
////////////////////////////////////////////////

// Controller V2 init

ControllerV2::registerStateVariable($yt);

// Player init
require "modules/playerCore.php";
$_playerCore = PlayerCore::main();
$yt->playerCore = $_playerCore;
$yt->playerBasepos = $_playerCore->basepos;

// Parse user preferences as stored by the YouTube application.
if (isset($_COOKIE["PREF"])) {
   $PREF = explode("&", $_COOKIE["PREF"]);
   $yt->PREF = (object) [];
   for ($i = 0; $i < count($PREF); $i++) {
      $option = explode("=", $PREF[$i]);
      $title = $option[0];
      $yt->PREF->$title = $option[1];
   }
} else {
   $yt->PREF = (object) [
      "f5" => "20030"
   ];
}

// Aubrey added this to include timestamp in ytGlobalJsConfig.twig,
// should be moved
$yt -> version = json_decode(file_get_contents($root . "/.version"));

// Import all template functions
foreach (glob('modules/template_functions/*.php') as $file) include $file;

// should be moved
TemplateManager::addFunction('http_response_code', function($code) {
   http_response_code($code);
});
// should be moved
TemplateManager::addFilter("base64_encode", function($a){
   return base64_encode($a);
});


// Still referenced by some legacy code, otherwise this should
// be removed asap
function findKey($array, string $key) {
   for ($i = 0, $j = count($array); $i < $j; $i++) {
      if (isset($array[$i]->{$key})) {
         return $array[$i]->{$key};
      }
   }
}