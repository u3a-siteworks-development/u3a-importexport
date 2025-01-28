<?php // phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

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

// Definitions copied from core to avoid PHPCS errors

if (!defined('U3A_GROUP_CPT')) {
    define('U3A_GROUP_CPT', 'u3a_group');
}
if (!defined('U3A_GROUP_TAXONOMY')) {
    define('U3A_GROUP_TAXONOMY', 'u3a_group_category');
}
if (!defined('U3A_EVENT_CPT')) {
    define('U3A_EVENT_CPT', 'u3a_event');
}
if (!defined('U3A_EVENT_TAXONOMY')) {
    define('U3A_EVENT_TAXONOMY', 'u3a_event_category');
}
if (!defined('U3A_VENUE_CPT')) {
    define('U3A_VENUE_CPT', 'u3a_venue');
}
if (!defined('U3A_CONTACT_CPT')) {
    define('U3A_CONTACT_CPT', 'u3a_contact');
}
