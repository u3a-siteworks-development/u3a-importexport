<?php

// Make sure we have the required folders

add_action(
    'admin_init',
    function () {
        if (!is_dir(U3A_IMPORT_FOLDER) || !is_dir(U3A_EXPORT_FOLDER)) {
            u3a_csv_importexport_install();
        }
    }
);

// Load the CSS for these admin pages

add_action(
    'admin_enqueue_scripts',
    function () {
        wp_enqueue_style('u3aimportexportstyle', plugins_url('css/u3a-importexport.css', __FILE__), array(), U3A_IMPORTEXPORT_VERSION);
    }
);

// Admin menu pages

add_action('admin_menu', 'u3a_csv_importexport_admin_menu');
function u3a_csv_importexport_admin_menu()
{
    add_menu_page(
        'u3a Import Export',
        'u3a Import Export',
        'manage_options',
        'u3a-importexport-menu',
        'u3a_show_importexport_menu',
        'dashicons-portfolio',
        50
    );
}

/**
 * Generate the Import/Export menu page
 * Any messages currently in the transient 'u3a_csv_importexport_msg' will be displayed then the transient deleted
 */

function u3a_show_importexport_menu()
{
    $transient_message = '';
    // Check if there is a message to display
    $message = get_transient('u3a_csv_importexport_msg');
    if (false !== $message) {
        delete_transient('u3a_csv_importexport_msg');
        // allow only para tags
        $message = wp_kses($message, ['p' => []]);
        // If more that 5 lines in message, put remainder into a hidden div
        // and provide link to show full message
        $msg_lines = substr_count($message, '<p>');
        if ($msg_lines > 5) {
            $ppos = 0;
            for ($c = 0; $c < 5; $c++) {
                $ppos  = strpos($message, '</p>', $ppos);
                $ppos += 4;
            }
            $rest_of_msg = substr($message, $ppos);
            $message  = substr($message, 0, $ppos); // first 5 lines
            $morelines   = $msg_lines - 5;
            $message .= <<< END
            <div id="showmsghead"><p>$morelines more lines available.  <a href="#" onclick="showrestofmsg()">View full message</a>.</p></div>
            <div id="showrestofmsg" style="display:none;">$rest_of_msg</div>
            <script>
                function showrestofmsg() {
                    var div = document.getElementById("showmsghead");
                    div.style.display = "none";
                    div = document.getElementById("showrestofmsg");
                    div.style.display = "block";
                }
            </script>
            END;
        }
        $transient_message = $message;
    }

    $nonce_code = wp_nonce_field('u3a_settings', 'u3a_nonce', true, false);

    // Assemble form components

    // Assemble the tab navigation menu

    $tabs = array(
        'Export' => '',
        'Import' => 'import',
        'Help'   => 'help',
    );
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    // Justification for ignoring nonce requirement, tab is escaped.
    $tab  = isset($_GET['tab']) ? esc_html($_GET['tab']) : ''; // current tab
    // phpcs:enable WordPress.Security.NonceVerification.Recommended

    $tab_navbar = '<nav class="nav-tab-wrapper">';
    foreach ($tabs as $tabText => $tabName) {
        $tabName     = esc_html($tabName);
        $tabText     = esc_html($tabText);
        $tab_navbar .= '<a href="?page=u3a-importexport-menu';
        $tab_navbar .= empty($tabName) ? '' : "&tab=$tabName";
        $tab_navbar .= '" class="nav-tab ';
        if ($tab === $tabName) {
            $tab_navbar .= 'nav-tab-active';
        }
        $tab_navbar .= '">';
        $tab_navbar .= $tabText;
        $tab_navbar .= '</a>';
    }
    $tab_navbar .= "</nav>\n";

    // Output common top-of-page content
    // phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
    // Justification for ignoring WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
    // $transient_message is constant apart from message, which is escaped earlier.
    // $tab_navbar is has tabName and tabText which are escaped earlier.
    print <<<END
<div class="wrap">
    <h2>u3a data Import / Export</h2>
    <div class="notice notice-error is-dismissible inline">
        $transient_message
    <div>
    $tab_navbar
END;
    // phpcs:enable

    // Output Help tab content

    switch ($tab) {
        case 'help':
            print <<< END
    <div  style="max-width:800px;">
    <h3>Exporting information</h3>
    <p>To create an up to date set of export files, select the Export tab and click the button 'Generate new export files'.
    You can then download whichever set of data your require.</p>
    <h3>Importing information</h3>
    <p><strong>It is highly recommended that you create a backup of your website before importing from your CSV files.</strong></p>
    <p>If you will be creating your CSV import files using a spreadsheet application you may find it helpful to download a copy
    of the spreadsheet import template (available on the 'Export' tab).  This contains ready-made sheets you can use.  It also contains
    a reminder of the main requirements for what information is required in import files.</p>
    <p>Importing information is a two-stage process.</p>
    <p style="margin-left: 30px;">When you select and upload a file, it will be checked to ensure all the required information
    is present and that individual items of data appear valid.  Any problems detected will be shown, and the file will not be accepted for the next stage.</p>
    <p style="margin-left: 30px;">Once a file has passed all the checks it will be shown as ready for import.  You can then proceed to import the
    file by clicking the button 'Import files into WordPress'.  Depending on how much data you have uploaded this may take some time.</p>
    <p>We recommend that if you have several files to import that you do them in the order Contacts, Venues, Groups then Events.  It is a good idea to
    check that each individual file has been imported successfully before continuing to import the next file.</p>
    </div>
END;
            break;

            // Output Import tab content

        case 'import':
            // Check transient to see if any import file contains an ID.  If so, show pop-up warning
            $id_warning_script ='';
            $CSV_has_ID = get_transient('CSV_has_ID');
            if (false !== $CSV_has_ID) {
                delete_transient('CSV_has_ID');
                $id_warning_script = <<< END
                <script>
                alert("Warning\\n\\nAn ID field was detected in one of your import files.\\n\\nThe import process will overwrite existing records where there is a match of ID.\\n\\nThis may be what you want if you originally exported the data from this website, but importing data from another website will have unpredictable results and may corrupt your website. \\n\\nOnly proceed to import this file if you are sure you understand this.");
                </script>
                END;
            }

            $upload_section    = u3a_get_upload_data();
            $import_section    = u3a_get_import_controls();
            $spinner_image     = plugins_url('css/spinner.gif', __FILE__);
            // phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
            // justification - $nonce_code is generated
            // $upload_section is built from statis data (file dates, and hardcoded names)
            // $import_section is generated from static data and hardcoded file names.

            print <<<END
        <h3>Upload files for import</h3>
        $id_warning_script

        <div class="u3a-csv-uploads">
        <form action="admin-post.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="u3a_upload_data">
        $nonce_code
        $upload_section
        </form>
        </div>

        <div class="u3a-csv-import">
        <form action="admin-post.php" method="POST">
        <input type="hidden" name="action" value="u3a_csv_import_data">
        $nonce_code
        $import_section
        </form>
        </div>

        <div id="spinnerdiv">
            <img src="$spinner_image">
        </div>
    END;
            // phpcs:enable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
            break;

            // Output Export tab content

        default:
            $download_section  = u3a_get_download_data();
            $refresh_downloads = get_submit_button('Generate new export files', 'primary large', 'regen', false, array('style' => 'margin-top:10px;'));
            // phpcs:disable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped
            // Justification download_section and nonce_code are generated from reliable data.
            print <<<END
    <h3>Export files available to download</h3>
    $download_section
    <form method="POST" action="admin-post.php">
        <input type="hidden" name="action" value="u3a_csv_export_data">
        $nonce_code
        <div style="text-align: center;">$refresh_downloads</div>
    </form>
END;
            // phpcs:enable WordPress.Security.EscapeOutput.HeredocOutputNotEscaped

    }
    print <<<END
</div><!-- wrap -->
END;
}

/**
 * Process the uploaded csv files and save in the imports folder
 * Run validation check on uploaded files and only save if no problems found.
 * Add details of any issues found to transient
 * Upload form generated in u3a_get_upload_data()
 */

add_action('admin_post_u3a_upload_data', 'u3a_upload_data');

function u3a_upload_data()
{
    // check nonce
    if (check_admin_referer('u3a_settings', 'u3a_nonce') === false) {
        wp_die('Invalid form submission');
    }
    // verify admin user
    if (!current_user_can('manage_options')) {
        wp_die('Invalid form submission');
    }

    $upload_msg = '';

    foreach (array(
        'ctdata'  => 'contacts',
        'vendata' => 'venues',
        'gpsdata' => 'groups',
        'evtdata' => 'events',
    ) as $file_id => $filename) {

        if (!empty($_FILES[$file_id]['name'])) {

            $destfilename   = "$filename.csv";
            $sourceFilename = $_FILES[$file_id]['name'];

            // check file passes validation tests
            $check_msg = u3a_check_csv_file($filename, $sourceFilename, $_FILES[$file_id]['tmp_name']);
            if (!empty($check_msg)) {
                $upload_msg .= $check_msg;
                continue;
            }

            // move to imports folder
            if (!move_uploaded_file($_FILES[$file_id]['tmp_name'], U3A_IMPORT_FOLDER . "/$destfilename")) {
                $upload_msg .= '<p>Unexpected problem uploading ' . $sourceFilename . '.  Not uploaded</p>';
            }
        }
    }

    // Save any messages and redirect back to import/export page
    set_transient('u3a_csv_importexport_msg', $upload_msg, 60 * 5);
    wp_safe_redirect(admin_url('admin.php?page=u3a-importexport-menu&tab=import'));
}


/**
 * Process request to either import currently uploaded files,
 * or clear currently uploaded files
 *
 * @return void
 */

add_action('admin_post_u3a_csv_import_data', 'u3a_csv_import_data');

function u3a_csv_import_data()
{
    // var_dump($_POST); exit;

    // check nonce
    if (check_admin_referer('u3a_settings', 'u3a_nonce') === false) {
        wp_die('Invalid form submission');
    }
    // verify admin user
    if (!current_user_can('manage_options')) {
        wp_die('Invalid form submission');
    }

    // Check option for handling events
    $force_new_events = (isset($_POST['forcenewevents'])) ? true : false;

    $import_message = '';

    if (isset($_POST['import'])) {

        // look for files to import
        if (file_exists(U3A_IMPORT_FOLDER . '/contacts.csv')) {
            $import_message .= u3a_csv_import_contacts();
        }
        if (file_exists(U3A_IMPORT_FOLDER . '/venues.csv')) {
            $import_message .= u3a_csv_import_venues();
        }
        if (file_exists(U3A_IMPORT_FOLDER . '/groups.csv')) {
            $import_message .= u3a_csv_import_groups();
        }
        if (file_exists(U3A_IMPORT_FOLDER . '/events.csv')) {
            $import_message .= u3a_csv_import_events($force_new_events);
        }
    }

    if (isset($_POST['clearuploads']) || isset($_POST['removeafterimport'])) {
        foreach (array('contacts', 'venues', 'groups', 'events') as $download) {
            $csv_file = U3A_IMPORT_FOLDER . "/$download.csv";
            if (file_exists($csv_file)) {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                if (!unlink($csv_file)) {
                    $import_message .= "<p>Failed to delete $download.csv</p>";
                }
            }
        }
    }

    // Save any messages and redirect back to import/export page
    if (!empty($import_message)) {
        set_transient('u3a_csv_importexport_msg', $import_message, 60 * 5);
    }
    wp_safe_redirect(admin_url('admin.php?page=u3a-importexport-menu&tab=import'));
}


/**
 * Generate the HTML to trigger the import process, or clear current uploads
 */

function u3a_get_import_controls()
{

    // Check we have some files to import!
    $found         = false;
    $event_options = '';
    foreach (array('contacts', 'venues', 'groups', 'events') as $file) {
        if (file_exists(U3A_IMPORT_FOLDER . "/$file.csv")) {
            $found = true;
            break;
        }
    }
    if (file_exists(U3A_IMPORT_FOLDER . '/events.csv')) {
        $event_options = '<p><label for="forcenewevents">Always create new posts when importing events?</label>
        <input type="checkbox" id="forcenewevents" name="forcenewevents" value="1" checked></p>';
    }
    if ($found) {
        $html = <<< END
    $event_options
    <p><label for="removeafterimport">Clear uploaded files after importing?</label>
    <input type="checkbox" id="removeafterimport" name="removeafterimport" value="1" checked></p>
    <input type="submit" class="button button-primary button-large" value="Import files into WordPress" name="import"
    onClick="return startImportCheck()">
    <input type="submit" class="button button-secondary button-large" value="Clear existing uploaded files" name="clearuploads"
    onClick="return confirm('Delete files that are currently uploaded?');">
<script>
function startImportCheck() {
    if (confirm('Do you really want to import these files?') == false) {
        return false;
    }
    document.getElementById("spinnerdiv").classList.add("show");
    return true;
}
</script>
END;
    } else {
        $html = '<p>No files available for import.</p>';
    }

    return $html;
}



/**
 * Get list of uploaded files with current dates
 * plus form to upload replacements
 *
 * @return void
 */
function u3a_get_upload_data()
{

    foreach (array('contacts', 'venues', 'groups', 'events') as $file) {
        $source = U3A_IMPORT_FOLDER . "/$file.csv";
        if (file_exists($source)) {
            $UTCtime = new DateTime();
            $UTCtime->setTimestamp(filemtime($source));
            $UTCtime->setTimezone(new DateTimeZone('Europe/London'));
            $ftime = $UTCtime->format('H:ia F jS');
            $$file = "<strong>$file file ready for import</strong>, uploaded $ftime";
        } else {
            $$file = '';
        }
    }

    $html = <<< END

<label for="ctdata" class="button">Select contacts file </label>
<input type="file" name="ctdata" id="ctdata" accept="text/csv">$contacts<br>
<label for="vendata" class="button">Select venues file </label>
<input type="file" name="vendata" id="vendata" accept="text/csv">$venues<br>
<label for="gpsdata" class="button">Select groups file </label>
<input type="file" name="gpsdata" id="gpsdata" accept="text/csv">$groups<br>
<label for="evtdata" class="button">Select events file </label>
<input type="file" name="evtdata" id="evtdata" accept="text/csv">$events<br>
<input type="submit" class="button button-primary button-large" value="Upload selected files" name="upload">
<input type="reset" class="button" value="Reset upload form">
END;
    return $html;
}



// Add function to regenerate the CSV export files

add_action('admin_post_u3a_csv_export_data', 'u3a_csv_export_data');

function u3a_csv_export_data()
{
    // check nonce
    if (check_admin_referer('u3a_settings', 'u3a_nonce') === false) {
        wp_die('Invalid form submission');
    }
    // verify admin user
    if (!current_user_can('manage_options')) {
        wp_die('Invalid form submission');
    }

    u3a_csv_export_contacts();
    u3a_csv_export_groups();
    u3a_csv_export_venues();
    u3a_csv_export_events();

    // Save any messages and redirect back to import/export page
    // set_transient('u3a_csv_importexport_msg', '<p>Export files finished</p>', 60 * 5);
    wp_safe_redirect(admin_url('admin.php?page=u3a-importexport-menu'));
}

// Return links to the admin script that will do the actual downloading
// Check the files are present, and provide file date if they are

function u3a_get_download_data()
{
    $html = '<div class="u3a-csv-downloads">';

    $u3a_csv_export_link = admin_url('/admin-post.php?action=u3a_download_export_file&fileref=');
    foreach (array('contacts', 'venues', 'groups', 'events') as $download) {
        $csv_file = U3A_EXPORT_FOLDER . "/$download.csv";
        if (file_exists($csv_file)) {
            $UTCtime = new DateTime();
            $UTCtime->setTimestamp(filemtime($csv_file));
            $UTCtime->setTimezone(new DateTimeZone('Europe/London'));
            $ftime = $UTCtime->format('H:ia F jS');
            $url   = $u3a_csv_export_link . $download;
            $html .= "<p><span class=\"flink\"><a href=\"$url\" class=\"button\">Download $download</a></span> <span class=\"ftime\"> Generated $ftime</span></p>\n";
        }
    }
    $html .= '<p><a href="' . $u3a_csv_export_link . "template\" class=\"button\">Download spreadsheet import template</a></p>\n";
    $html .= "</div><!-- u3a-csv-downloads -->\n";
    return $html;
}

// This is the bit that handles download requests
// If we have valid data and an admin user, process the download
// Otherwise send failure response code to the browser

add_action('admin_post_u3a_download_export_file', 'u3a_download_export_file');

function u3a_download_export_file()
{
    $protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');
    // phpcs:disable
    // Justification for ignoring nonce requirement, this is just taking if fileref is set.
    if (!current_user_can('manage_options') || !isset($_GET['fileref'])) {
        header($protocol . ' 401 Unauthorized');
        exit;
    }

    $filepath = '';
    // phpcs:disable
    // Justification for ignoring nonce requirement, fileref is sanitized.
    $fileref  = sanitize_text_field($_GET['fileref']);
    if (in_array($fileref, array('contacts', 'venues', 'groups', 'events'))) {
        $filepath = U3A_EXPORT_FOLDER . "/$fileref.csv";
        $filename = "$fileref.csv";
    }
    if ($fileref === 'template') {
        $filepath = plugin_dir_path(__FILE__) . 'assets/importtemplate.xlsx';
        $filename = 'importtemplate.xlsx';
    }
    if (!empty($filepath) && file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        readfile($filepath);
    } else {
        header($protocol . ' 404 Not Found');
        exit;
    }
}
