<?php

/**
 * Check that the CSV file looks valid
 * $filename is one of contacts, venues, groups, events
 * $sourceFilename is the basename of the uploaded file
 * $sourcefile is the full name and path of the file in the temporary upload location
 *
 * Return either empty string, or HTML text of all detected problems (lines wrapped in <p> tags)
 */

function u3a_check_csv_file($filename, $sourceFilename, $sourcefile)
{

    // UTF-8 check  it is a pity we are reading the file twice
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    $csvcontents = file_get_contents($sourcefile);
    if (!mb_check_encoding($csvcontents, 'UTF-8')) {
        $encoding = mb_detect_encoding($csvcontents, array('ASCII', 'UTF-8', 'ISO-8859-1'), true); // returns encoding string or false
        if (empty($encoding)) {
            return '<p>Your file ' . $sourceFilename . ' contains invalid characters and is possibly not Unicode (UTF-8) encoded</p>';
        } else {
            return '<p>Your file ' . $sourceFilename . " uses $encoding encoding.  Files need to be UTF-8 encoded.</p>";
        }
    }

    $html = '';

    switch ($filename) {
        case 'groups':
            $html .= u3a_check_groups_csv_file($sourcefile, $sourceFilename);
            break;
        case 'events':
            $html .= u3a_check_events_csv_file($sourcefile, $sourceFilename);
            break;
        case 'contacts':
            $html .= u3a_check_contacts_csv_file($sourcefile, $sourceFilename);
            break;
        case 'venues':
            $html .= u3a_check_venues_csv_file($sourcefile, $sourceFilename);
            break;
    }

    return $html;
}

/**
 * Read file, remove BOM if present, extract headings, create keyed array
 *
 * @param string $sourcefile full path and filename of csv file to check
 * @param string $sourceFilename is the basename of the uploaded file
 *
 * @return array [$error_msg, $headers, $csv_array]
 *         $error_msg empty string if no errors found, or HTML text of error wrapped in p tag
 *         $headers array of header names
 *         $csv_array array of rows, each row is array keyed by corresponding header name
 */
function u3a_read_csv_file($sourcefile, $sourceFilename)
{
    $csv_array = array();
    $headers   = array();
    $rows      = array_map('str_getcsv', file($sourcefile));
    $error_msg = u3a_check_rows_for_length($rows, $sourceFilename);
    // risk code errors if rows not same size
    if (empty($error_msg)) {
        $headers = array_shift($rows);
        $BOM     = "\u{FEFF}";
        //remove BOM if present at start of file, as some software may add it.
        $headers[0] = str_replace($BOM, '', $headers[0]);
        foreach ($rows as $row) {
            if (!empty(implode($row))) {    // skip empty rows
                if (count($headers) === count($row)) {
                    $csv_array[] = array_combine($headers, $row);
                }
            }
        }
    }
    return array($error_msg, $headers, $csv_array);
}
// Check headings are all recognised and that required headings are present
// Check columns that are supposed to contain controlled terms (eg Monday, Tuesday) or a Category are all valid
// Check columns that contain required data (eg Name) to make sure an entry is present

/**
 * Check contents of Groups CSV file for validity
 *
 * @param string $sourcefile full path and filename of csv file to check
 * @return string empty string if no errors found, or HTML text of all detected errors wrapped in p tags
 */

function u3a_check_groups_csv_file($sourcefile, $sourceFilename)
{
    global $wpdb;

    $validation_msg = '';

    // Read file, extract headings, create keyed array
    list($error_msg, $headers, $groups_csv) = u3a_read_csv_file($sourcefile, $sourceFilename);
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // check each header field is valid
    $valid_cols = array(
        'ID',
        'Name',
        'Status',
        'Category',
        'Day',
        'Time',
        'Frequency',
        'When',
        'Venue ID',
        'Venue',
        'Coordinator ID',
        'Coordinator',
        'Coordinator 2 ID',
        'Coordinator 2',
        'Deputy ID',
        'Deputy',
        'Tutor ID',
        'Tutor',
        'Email',
        'Email2',
        'Cost',
    );

    // Which optional fields are not in use from u3a Settings
    $not_in_use = array();
    if (1 != get_option('field_coord2', 1)) {
        $not_in_use[] = 'Coordinator 2';
        $not_in_use[] = 'Coordinator 2 ID';
    }
    if (1 != get_option('field_deputy', 1)) {
        $not_in_use[] = 'Deputy';
        $not_in_use[] = 'Deputy ID';
    }
    if (1 != get_option('field_tutor', 1)) {
        $not_in_use[] = 'Tutor';
        $not_in_use[] = 'Tutor ID';
    }
    if (1 != get_option('field_groupemail', 1)) {
        $not_in_use[] = 'Email';
    }
    if (1 != get_option('field_groupemail2', 1)) {
        $not_in_use[] = 'Email2';
    }
    if (1 != get_option('field_cost', 1)) {
        $not_in_use[] = 'Cost';
    }
    foreach ($headers as $col) {
        if (!in_array($col, $valid_cols)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - The column heading '" . sanitize_text_field($col) . "' is not valid</p>";
        }
        if (in_array($col, $not_in_use)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - Before trying to import a file with the '$col' column the option setting to show the corresponding field must be set.</p>";
        }
    }

    // These columns are required: Name, Status, Category
    foreach (array('Name', 'Status', 'Category') as $title) {
        if (!in_array($title, $headers)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - A column headed '$title' is required</p>";
        }
    }

    // The Name column can not have empty entries
    if (in_array('Name', $headers)) {
        $column = array_column($groups_csv, 'Name');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (empty($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row is missing a group name</p>";
            }
        }
    }

    // Check Status column for valid entries if present.  Empty entries not allowed.
    if (in_array('Status', $headers)) {
        $validation_msg .= u3a_check_csv_column(
            $sourceFilename,
            $groups_csv,
            'Status',
            array(
                'Active, open to new members',
                'Active, not currently accepting new members',
                'Active, full but can join waiting list',
                'Temporarily inactive',
                'No longer meeting',
                'Active',
                'Full',
                'Wait list only',
                'Suspended',
                'Closed',
            ),
            true
        );
    }

    // The Category column entries must match an existing Group category.  Empty entries not allowed.
    $categories = array();
    $terms      = get_terms(
        array(
            'taxonomy'   => U3A_GROUP_TAXONOMY,
            'hide_empty' => false,
        )
    );
    foreach ($terms as $term) {
        $categories[] = $term->name;
    }
    $validation_msg .= u3a_check_csv_column($sourceFilename, $groups_csv, 'Category', $categories, true);

    // Check Day column for valid entries if present.  Empty entries allowed.

    if (in_array('Day', $headers)) {
        $validation_msg .= u3a_check_csv_column($sourceFilename, $groups_csv, 'Day', array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'));
    }

    // Check Time column for valid entries if present.  Empty entries allowed.
    if (in_array('Time', $headers)) {
        $validation_msg .= u3a_check_csv_column($sourceFilename, $groups_csv, 'Time', array('Morning', 'Afternoon', 'Evening', 'All Day'));
    }

    // Check Frequency column for valid entries if present.  Empty entries allowed.
    if (in_array('Frequency', $headers)) {
        $validation_msg .= u3a_check_csv_column($sourceFilename, $groups_csv, 'Frequency', array('Weekly', 'Fortnightly', 'Monthly'));
    }

    // Check that an ID column only has integers or is empty
    foreach (array('ID', 'Venue ID', 'Coordinator ID', 'Coordinator 2 ID', 'Deputy ID', 'Tutor ID') as $col_heading) {
        if (in_array($col_heading, $headers)) {
            $column = array_column($groups_csv, $col_heading);
            $row    = 1;
            foreach ($column as $entry) {
                ++$row;
                if (!empty($entry) && !is_numeric($entry)) {
                    $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid ID in column $col_heading</p>";
                }
            }
        }
    }

    // Check that Email and/or Email2 columns contain either valid email address or are empty
    foreach (array('Email', 'Email2') as $col_heading) {
        if (in_array($col_heading, $headers)) {
            $column = array_column($groups_csv, $col_heading);
            $row    = 1;
            foreach ($column as $entry) {
                ++$row;
                if (!empty($entry) && !filter_var($entry, FILTER_VALIDATE_EMAIL)) {
                    $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid email address in column $col_heading</p>";
                }
            }
        }
    }

    // Check that if a contact name is given that the name is present in the Contacts list (case insensitive)
    // TODO - Perhaps make this an optional check and allow auto-create non-existing contacts?
    $contacts = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_title FROM $wpdb->posts WHERE post_type = %s AND post_status=%s",
            array(U3A_CONTACT_CPT, 'publish')
        )
    );

    foreach (array('Coordinator', 'Coordinator 2', 'Deputy', 'Tutor') as $col_heading) {
        // don't check columns which are not in use
        if (in_array($col_heading, $headers) && !(in_array($col_heading, $not_in_use))) {
            // use sanitize_title'd values in comparison
            $validation_msg .= u3a_check_csv_column($sourceFilename, $groups_csv, $col_heading, $contacts, false, true);
        }
    }

    // Check that if a venue name is given that the name is present in the Venues list
    // TODO - Perhaps make this an optional check and allow auto-create non-existing venues?

    $venues = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_title FROM $wpdb->posts WHERE post_type = %s AND post_status=%s",
            array(U3A_VENUE_CPT, 'publish')
        )
    );
    //$venues = $wpdb->get_col("SELECT post_title FROM $wpdb->posts WHERE post_type = '" . U3A_VENUE_CPT . "' AND post_status = 'publish'");
    // use sanitize_title'd values in comparison
    $validation_msg .= u3a_check_csv_column($sourceFilename, $groups_csv, 'Venue', $venues, false, true);

    return $validation_msg;
}

/**
 * Check contents of Events CSV file for validity
 *
 * @param string $sourcefile full path and filename of csv file to check
 * @return string empty string if no errors found, or HTML text of all detected errors wrapped in p tags
 */

function u3a_check_events_csv_file($sourcefile, $sourceFilename)
{
    global $wpdb;

    $validation_msg = '';

    // Read file, extract headings, create keyed array
    list($error_msg, $headers, $events_csv) = u3a_read_csv_file($sourcefile, $sourceFilename);
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // check each header field is valid
    $valid_cols = array('ID', 'Name', 'Category', 'Date', 'Time', 'Days', 'Group ID', 'Group', 'Venue ID', 'Venue', 'Organiser ID', 'Organiser', 'Cost', 'Booking');
    foreach ($headers as $col) {
        if (!in_array($col, $valid_cols)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - The column heading '" . sanitize_text_field($col) . "' is not valid</p>";
        }
    }

    // These columns are required: Name, Category, Date.  Other columns are all optional.
    foreach (array('Name', 'Category', 'Date') as $title) {
        if (!in_array($title, $headers)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - A column headed '$title' is required</p>";
        }
    }

    // The Name column can not have empty entries
    if (in_array('Name', $headers)) {
        $column = array_column($events_csv, 'Name');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (empty($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row is missing an event Name</p>";
            }
        }
    }

    // The Category column entries must match an existing Event category
    $categories = array();
    $terms      = get_terms(
        array(
            'taxonomy'   => U3A_EVENT_TAXONOMY,
            'hide_empty' => false,
        )
    );
    foreach ($terms as $term) {
        $categories[] = $term->name;
    }
    $validation_msg .= u3a_check_csv_column($sourceFilename, $events_csv, 'Category', $categories, true);

    // Check Date column for valid date format YYYY-MM-DD
    if (in_array('Date', $headers)) {
        $column = array_column($events_csv, 'Date');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (empty($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row is missing a Date</p>";
                continue;
            }
            $checkdate = date_create_from_format('Y-m-d', $entry);
            if ($checkdate === false || $checkdate->format('Y-m-d') !== $entry) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row - invalid Date '" . sanitize_text_field($entry) . "'</p>";
            }
        }
    }

    // Check Time column for valid time format HH:MM (Time is optional entry so can be empty)
    if (in_array('Time', $headers)) {
        $column = array_column($events_csv, 'Time');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (!empty($entry)) {
                $checktime = date_create_from_format('H:i', $entry);
                if ($checktime === false || $checktime->format('H:i') !== $entry) {
                    $validation_msg .= '<p> ' . $sourceFilename . " - Row $row - invalid Time '" . sanitize_text_field($entry) . "'</p>";
                }
            }
        }
    }

    // Check that the Days column or any ID column only has integers or is empty
    foreach (array('ID', 'Days', 'Group ID', 'Venue ID', 'Organiser ID') as $col_heading) {
        if (in_array($col_heading, $headers)) {
            $column = array_column($events_csv, $col_heading);
            $row    = 1;
            foreach ($column as $entry) {
                ++$row;
                if (!empty($entry) && !is_numeric($entry)) {
                    $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid $col_heading</p>";
                }
            }
        }
    }

    // Check that if a Group name is given that the name is present in the Groups list
    if (in_array('Group', $headers)) {
        $groups = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_title FROM $wpdb->posts WHERE post_type = %s AND post_status=%s",
                array(U3A_GROUP_CPT, 'publish')
            )
        );
        //$groups = $wpdb->get_col("SELECT post_title FROM $wpdb->posts WHERE post_type = '" . U3A_GROUP_CPT . "' AND post_status = 'publish'");
        // use sanitize_title'd values in comparison
        $validation_msg .= u3a_check_csv_column($sourceFilename, $events_csv, 'Group', $groups, false, true);
    }

    // Check that if a Venue name is given that the name is present in the Venues list

    if (in_array('Venue', $headers)) {
        $venues = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_title FROM $wpdb->posts WHERE post_type = %s AND post_status=%s",
                array(U3A_VENUE_CPT, 'publish')
            )
        );
        //$venues = $wpdb->get_col("SELECT post_title FROM $wpdb->posts WHERE post_type = '" . U3A_VENUE_CPT . "' AND post_status = 'publish'");
        // use sanitize_title'd values in comparison
        $validation_msg .= u3a_check_csv_column($sourceFilename, $events_csv, 'Venue', $venues, false, true);
    }

    // Check that if an Organiser name is given that the name is present in the Contacts list
    if (in_array('Organiser', $headers)) {
        $contacts = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_title FROM $wpdb->posts WHERE post_type = %s AND post_status=%s",
                array(U3A_CONTACT_CPT, 'publish')
            )
        );
        //$contacts = $wpdb->get_col("SELECT post_title FROM $wpdb->posts WHERE post_type = '" . U3A_CONTACT_CPT . "' AND post_status = 'publish'");
        // use sanitize_title'd values in comparison
        $validation_msg .= u3a_check_csv_column($sourceFilename, $events_csv, 'Organiser', $contacts, false, true);
    }

    // Check that if the Booking field is given that it is an expected entry. Empty entries allowed.
    if (in_array('Booking', $headers)) {
        $validation_msg .= u3a_check_csv_column($sourceFilename, $events_csv, 'Booking', array('Yes', 'Y', 'No', 'N'));
    }

    return $validation_msg;
}

/**
 * Check contents of Contacts CSV file for validity
 *
 * @param string $sourcefile full path and filename of csv file to check
 * @return string empty string if no errors found, or HTML text of all detected errors wrapped in p tags
 */

function u3a_check_contacts_csv_file($sourcefile, $sourceFilename)
{
    $validation_msg = '';

    // Read file, extract headings, create keyed array
    list($error_msg, $headers, $contacts_csv) = u3a_read_csv_file($sourcefile, $sourceFilename);
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // check each header field is valid
    $valid_cols = array('ID', 'Name', 'Membership no.', 'Given', 'Family', 'Phone', 'Phone 2', 'Email');
    foreach ($headers as $col) {
        if (!in_array($col, $valid_cols)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - The column heading '" . sanitize_text_field($col) . "' is not valid</p>";
        }
    }

    // Check that the ID column only has integers or is empty
    if (in_array('ID', $headers)) {
        $column = array_column($contacts_csv, 'ID');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (!empty($entry) && !is_numeric($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid ID</p>";
            }
        }
    }

    // The 'Name' column is required.  Other columns are all optional.
    if (!in_array('Name', $headers)) {
        $validation_msg .= '<p> ' . $sourceFilename . " - A column headed 'Name' is required</p>";
    }

    // The Name column can not have empty entries
    if (in_array('Name', $headers)) {
        $column = array_column($contacts_csv, 'Name');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (empty($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row is missing a Contact Name</p>";
            }
        }
    }

    // Can't really validate Given name, Family Name, Phone or Phone 2 as all can hold mixed text without problem.

    // Check that Email column contains either valid email address or is empty
    if (in_array('Email', $headers)) {
        $column = array_column($contacts_csv, 'Email');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (!empty($entry) && !filter_var($entry, FILTER_VALIDATE_EMAIL)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid email address in column Email</p>";
            }
        }
    }

    return $validation_msg;
}


/**
 * Check contents of Venues CSV file for validity
 *
 * @param string $sourcefile full path and filename of csv file to check
 * @return string empty string if no errors found, or HTML text of all detected errors wrapped in p tags
 */

function u3a_check_venues_csv_file($sourcefile, $sourceFilename)
{
    $validation_msg = '';

    // Which optional fields are not in use from u3a Settings
    $not_in_use = array();
    if (1 != get_option('field_v_district', 1)) {
        $not_in_use[] = 'District';
    }

    // Read file, extract headings, create keyed array
    list($error_msg, $headers, $venues_csv) = u3a_read_csv_file($sourcefile, $sourceFilename);
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // check each header field is valid
    $valid_cols = array('ID', 'Name', 'District', 'Address Line 1', 'Address Line 2', 'Town', 'Postcode', 'Accessibility', 'Phone', 'URL');
    foreach ($headers as $col) {
        if (!in_array($col, $valid_cols)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - The column heading '" . sanitize_text_field($col) . "' is not valid</p>";
        }
        if (in_array($col, $not_in_use)) {
            $validation_msg .= '<p> ' . $sourceFilename . " - Before trying to import a file with the '$col' column the option setting to show the corresponding field must be set.</p>";
        }
    }

    // The 'Name' column is required.  Other columns are all optional.
    if (!in_array('Name', $headers)) {
        $validation_msg .= '<p> ' . $sourceFilename . " - A column headed 'Name' is required</p>";
    }

    // The Name column can not have empty entries
    if (in_array('Name', $headers)) {
        $column = array_column($venues_csv, 'Name');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (empty($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row is missing a Venue Name</p>";
            }
        }
    }

    // Check that the ID column if present only has integers or is empty
    if (in_array('ID', $headers)) {
        $column = array_column($venues_csv, 'ID');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (!empty($entry) && !is_numeric($entry)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid ID</p>";
            }
        }
    }

    // Can't really validate Address fields, Phone or Accessibility as all can hold mixed text without problem and all are optional.

    // Check that URL column contains either valid URL or is empty
    if (in_array('URL', $headers)) {
        $column = array_column($venues_csv, 'URL');
        $row    = 1;
        foreach ($column as $entry) {
            ++$row;
            if (!empty($entry) && !filter_var($entry, FILTER_VALIDATE_URL)) {
                $validation_msg .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid URL</p>";
            }
        }
    }

    return $validation_msg;
}



/**
 * Check that the specified column only contains valid text strings
 * from the array of valid strings provided.
 *
 * @param string $sourceFilename is the basename of the uploaded file, eg mygroups.csv
 * @param array $csvdata the CSV keyed array
 * @param string $heading the column heading
 * @param array $valid_entries string array of valid column entries
 * @param boolean $required if true consider empty cells as an error
 * @param boolean $sanitize if true do comparison on sanitized values
 * @return string empty string if no errors found, or HTML text of all detected errors in p tags
 */
function u3a_check_csv_column($sourceFilename, &$csvdata, $heading, $valid_entries, $required = false, $sanitize = false)
{
    $html = '';
    if ($sanitize) {
        $valid_entries = array_map('sanitize_title', $valid_entries);
    }
    $column = array_column($csvdata, $heading);
    $row    = 1;
    foreach ($column as $entry) {
        ++$row;
        $test_value = ($sanitize) ? sanitize_title($entry) : $entry;
        if (!empty($entry) && !in_array($test_value, $valid_entries)) {
            $html .= '<p> ' . $sourceFilename . " - Row $row '" . sanitize_text_field($entry) . "' is not a valid $heading</p>";
        } elseif (empty($entry) && $required) {
            $html .= '<p> ' . $sourceFilename . " - Row $row missing required entry for $heading</p>";
        }
    }
    return $html;
}

/**
 * Check that all rows in the array have the same number of elements as the first row
 *
 * @param array $rows - read from CSV file
 * @param string $sourceFilename is the basename of the uploaded file
 * @return HTML for any errors encountered or null string
 */

function u3a_check_rows_for_length(&$rows, $sourceFilename)
{
    $html        = '';
    $headercount = count($rows[0]);
    $size        = count($rows);
    for ($c = 1; $c < $size; $c++) {
        if (count($rows[$c]) !== $headercount) {
            $html .= '<p>' . $sourceFilename . " - Row $c missing data.  Expected $headercount entries but found " . count($rows[$c]) . '</p>';
        }
    }
    return $html;
}
