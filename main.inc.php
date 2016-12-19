<?php
/*
Plugin Name: custom_missing_derivatives
Version: 0.0.1
Description: This plugin adds the piwigo webservice custom_missing_derivatives.getMissingDerivativesCustom
The webservie works like pwg.getMissingDerivatives but provides the additional paramter customTypes
example 
http://piwigo/ws.php?format=json&method=custom_missing_derivatives.getMissingDerivativesCustom&customTypes[]=260x180_1_260x180&customTypes[]=520x360_1_520x360
Plugin URI: https://github.com/pommes-frites/piwigo-custom_missing_derivatives
Author: pommes-frites
Author URI: https://github.com/pommes-frites
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+
define('CUSTOM_MISSING_DERIVATIVES_ID',      basename(dirname(__FILE__)));
define('CUSTOM_MISSING_DERIVATIVES_PATH' ,   PHPWG_PLUGINS_PATH . CUSTOM_MISSING_DERIVATIVES_ID . '/');

// file containing API function
$ws_file = CUSTOM_MISSING_DERIVATIVES_PATH . 'include/ws_functions.inc.php';

// add API function
add_event_handler('ws_add_methods', 'custom_missing_derivatives_ws_add_methods', EVENT_HANDLER_PRIORITY_NEUTRAL, $ws_file);
