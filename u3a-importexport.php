<?php
/** 
Plugin Name: u3a SiteWorks Import Export
Description: Provides facility to import and export CSV data files
Version: 1.7.2
Author: u3a SiteWorks team
Author URI: https://siteworks.u3a.org.uk/
Plugin URI: https://siteworks.u3a.org.uk/
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: u3a-siteworks-core, u3a-siteworks-configuration

*/

if (!defined('ABSPATH')) {
    exit;
}

define('U3A_IMPORTEXPORT_VERSION', '1.7.2');

if (!is_admin()) return; // Plugin only relevant on admin pages.

// Check SiteWorks core plugin is active (needed for some definitions)
// Check retained for WordPress installations before 6.5 that do not support "Requires Plugins"
require_once(ABSPATH.'wp-admin/includes/plugin.php');
if (!is_plugin_active('u3a-siteworks-core/u3a-siteworks-core.php')) {
    return;
}


// Use the plugin update service provided in the Configuration plugin

add_action(
    'plugins_loaded',
    function () {
        if (function_exists('u3a_plugin_update_setup')) {
            u3a_plugin_update_setup('u3a-importexport', __FILE__);
        } else {
            add_action(
                'admin_notices',
                function () {
                    print '<div class="error"><p>SiteWorks Import-Export plugin unable to check for updates as the SiteWorks Configuration plugin is not active.</p></div>';
                }
            );
        }
    }
);


require 'inc/definitions.php';

require "u3a-importexport-activate.php";
require "u3a-importexport-admin.php";
require "u3a-import-checks.php";
require "u3a-import.php";
require "u3a-export.php";

register_activation_hook(__FILE__, 'u3a_csv_importexport_install');
