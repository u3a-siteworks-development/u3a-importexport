<?php
if (!defined('ABSPATH')) {
    exit;
}
// Definitions, to allow for renaming if required

define('U3A_IMPORT_FOLDER', WP_CONTENT_DIR . '/uploads/importdata');
define('U3A_EXPORT_FOLDER', WP_CONTENT_DIR . '/uploads/exportdata');
define('U3A_EXPORT_URL', content_url() . '/uploads/exportdata');
define('U3A_IMPORT_URL', content_url() . '/uploads/importdata');

// date_default_timezone_set("Europe/London");
// disabled because WordPress regards this as a site health issue
