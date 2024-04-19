<?php
/** 
Plugin Name: u3a SiteWorks Import Export
Description: Provides facility to import and export CSV data files
Version: 1.6.1
Author: u3a SiteWorks team
Author URI: https://siteworks.u3a.org.uk/
Plugin URI: https://siteworks.u3a.org.uk/
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH')) {
    exit;
}

define('U3A_IMPORTEXPORT_VERSION', '1.6.1');

if (!is_admin()) return; // Plugin only relevant on admin pages.

// Check SiteWorks core plugin is active (needed for some definitions)
if (!is_plugin_active('u3a-siteworks-core/u3a-siteworks-core.php')) {
    return;
}

// Use the plugin update service on SiteWorks update server

require 'inc/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$u3aImpExpUpdateChecker = PucFactory::buildUpdateChecker(
    'https://siteworks.u3a.org.uk/wp-update-server/?action=get_metadata&slug=u3a-importexport', //Metadata URL
    __FILE__, //Full path to the main plugin file or functions.php.
    'u3a-importexport'
);



require 'inc/definitions.php';

require "u3a-importexport-activate.php";
require "u3a-importexport-admin.php";
require "u3a-import-checks.php";
require "u3a-import.php";
require "u3a-export.php";

register_activation_hook(__FILE__, 'u3a_csv_importexport_install');
