<?php

/**
 * Import contacts.  Return any notices/errors as HTML.
 * Assumes any required items are present, as will have been processed by import-checks
 */
function u3a_csv_import_contacts()
{
    // Read file, extract headings, create keyed array
    $sourcefile                               = U3A_IMPORT_FOLDER . '/contacts.csv';
    list($error_msg, $headers, $contacts_csv) = u3a_read_csv_file($sourcefile, 'contacts file');
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // run for each row, edit or create post as appropriate
    foreach ($contacts_csv as $contact) {
        //find or create post
        $id = (isset($contact['ID'])) ? (int) $contact['ID'] : '';

        $postid = u3a_find_or_create_post($id, sanitize_text_field($contact['Name']), U3A_CONTACT_CPT);
        //now update post meta values
        if (isset($contact['Membership no.'])) {
            update_post_meta($postid, 'memberid', sanitize_text_field($contact['Membership no.']));
        }
        if (isset($contact['Given'])) {
            update_post_meta($postid, 'givenname', sanitize_text_field($contact['Given']));
        }
        if (isset($contact['Family'])) {
            update_post_meta($postid, 'familyname', sanitize_text_field($contact['Family']));
        }
        if (isset($contact['Phone'])) {
            update_post_meta($postid, 'phone', sanitize_text_field($contact['Phone']));
        }
        if (isset($contact['Phone 2'])) {
            update_post_meta($postid, 'phone2', sanitize_text_field($contact['Phone 2']));
        }
        if (isset($contact['Email'])) {
            update_post_meta($postid, 'email', sanitize_email($contact['Email']));
        }
    }
    $done = count($contacts_csv);
    $html = "<p>Number of contacts processed: $done</p>\n";
    return $html;
}

/**
 * Import venues.  Return any notices/errors as HTML.
 * Assumes any required items are present, as will have been processed by import-checks
 */
function u3a_csv_import_venues()
{
    // Read file, extract headings, create keyed array
    $sourcefile                             = U3A_IMPORT_FOLDER . '/venues.csv';
    list($error_msg, $headers, $venues_csv) = u3a_read_csv_file($sourcefile, 'venues file');
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // run for each row, edit or create post as appropriate
    foreach ($venues_csv as $venue) {
        //find or create post
        $id = (isset($venue['ID'])) ? (int) $venue['ID'] : '';

        $postid = u3a_find_or_create_post($id, sanitize_text_field($venue['Name']), U3A_VENUE_CPT);
        //now update post meta values
        if (isset($venue['District'])) {
            update_post_meta($postid, 'district', sanitize_text_field($venue['District']));
        }
        if (isset($venue['Address Line 1'])) {
            update_post_meta($postid, 'address1', sanitize_text_field($venue['Address Line 1']));
        }
        if (isset($venue['Address Line 2'])) {
            update_post_meta($postid, 'address2', sanitize_text_field($venue['Address Line 2']));
        }
        if (isset($venue['Town'])) {
            update_post_meta($postid, 'town', sanitize_text_field($venue['Town']));
        }
        if (isset($venue['Postcode'])) {
            update_post_meta($postid, 'postcode', sanitize_text_field($venue['Postcode']));
        }
        if (isset($venue['Accessibility'])) {
            update_post_meta($postid, 'access', sanitize_text_field($venue['Accessibility']));
        }
        if (isset($venue['Phone'])) {
            update_post_meta($postid, 'phone', sanitize_text_field($venue['Phone']));
        }
        if (isset($venue['URL'])) {
            update_post_meta($postid, 'url', esc_url_raw($venue['URL']));
        }
    }
    $done = count($venues_csv);
    $html = "<p>Number of venues processed: $done</p>\n";
    return $html;
}

/**
 * Import groups.  Return any notices/errors as HTML.
 * Assumes any required items are present, as will have been processed by import-checks
 */
function u3a_csv_import_groups()
{
    // Read file, extract headings, create keyed array
    $sourcefile                             = U3A_IMPORT_FOLDER . '/groups.csv';
    list($error_msg, $headers, $groups_csv) = u3a_read_csv_file($sourcefile, 'groups file');
    if (!empty($error_msg)) {
        return $error_msg;
    }

    $day_list          = array(
        '' => '',
        1  => 'Monday',
        2  => 'Tuesday',
        3  => 'Wednesday',
        4  => 'Thursday',
        5  => 'Friday',
        6  => 'Saturday',
        7  => 'Sunday',
    );
    $status_list       = array(
        1 => 'Active, open to new members',
        2 => 'Active, not currently accepting new members',
        3 => 'Active, full but can join waiting list',
        4 => 'Temporarily inactive',
        5 => 'No longer meeting',
    );
    $status_list_short = array(
        1 => 'Active',
        2 => 'Full',
        3 => 'Waiting list',
        4 => 'Dormant',
        5 => 'Closed',
    );

    // Build lookup table of group category Slug->Name
    // This is important as we need the slug (not the name) to set the group category
    $group_categories = array();
    $terms            = get_terms(
        array(
            'taxonomy'   => U3A_GROUP_TAXONOMY,
            'hide_empty' => false,
        )
    );
    foreach ($terms as $term) {
        $group_categories[$term->slug] = html_entity_decode($term->name);
    }

    // run for each row, edit or create post as appropriate
    foreach ($groups_csv as $group) {
        //find or create post
        $id = (isset($group['ID'])) ? (int) $group['ID'] : '';

        $postid = u3a_find_or_create_post($id, sanitize_text_field($group['Name']), U3A_GROUP_CPT);

        //now check coordinators etc and venue ids
        if (isset($group['Coordinator'])) {
            if (!empty($group['Coordinator'])) {
                $coordid = u3a_id_by_match_title($group['Coordinator'], U3A_CONTACT_CPT);
                if ($coordid) { //import-checks should ensure this always succeeds
                    update_post_meta($postid, 'coordinator_ID', $coordid);
                }
            }
        }
        if (isset($group['Coordinator 2'])) {
            if (!empty($group['Coordinator 2'])) {
                $coord2id = u3a_id_by_match_title($group['Coordinator 2'], U3A_CONTACT_CPT);
                if ($coord2id) { //import-checks should ensure this always succeeds
                    update_post_meta($postid, 'coordinator2_ID', $coord2id);
                }
            }
        }
        if (isset($group['Deputy'])) {
            if (!empty($group['Deputy'])) {
                $depid = u3a_id_by_match_title($group['Deputy'], U3A_CONTACT_CPT);
                if ($depid) { //import-checks should ensure this always succeeds
                    update_post_meta($postid, 'deputy_ID', $depid);
                }
            }
        }
        if (isset($group['Tutor'])) {
            if (!empty($group['Tutor'])) {
                $tutid = u3a_id_by_match_title($group['Tutor'], U3A_CONTACT_CPT);
                if ($tutid) { //import-checks should ensure this always succeeds
                    update_post_meta($postid, 'tutor_ID', $tutid);
                }
            }
        }
        if (isset($group['Venue'])) {
            if (!empty($group['Venue'])) {
                $venid = u3a_id_by_match_title($group['Venue'], U3A_VENUE_CPT);
                if ($venid) { //import-checks should ensure this always succeeds
                    update_post_meta($postid, 'venue_ID', $venid);
                }
            }
        }
        if (isset($group['Day'])) {
            update_post_meta($postid, 'day_NUM', array_search($group['Day'], $day_list));
        }
        if (isset($group['Time'])) {
            update_post_meta($postid, 'time', sanitize_text_field($group['Time']));
        }
        if (isset($group['Frequency'])) {
            update_post_meta($postid, 'frequency', sanitize_text_field($group['Frequency']));
        }
        if (isset($group['When'])) {
            update_post_meta($postid, 'when', sanitize_text_field($group['When']));
        }
        if (isset($group['Start time'])) {
            update_post_meta($postid, 'startTime', sanitize_text_field($group['Start time']));
        }
        if (isset($group['End time'])) {
            update_post_meta($postid, 'endTime', sanitize_text_field($group['End time']));
        }
        if (isset($group['Email'])) {
            update_post_meta($postid, 'email', sanitize_email($group['Email']));
        }
        if (isset($group['Email2'])) {
            update_post_meta($postid, 'email2', sanitize_email($group['Email2']));
        }
        // 'Status' can be long form or short form.  Has been validated to use one or the other range of terms, but being defensive here.
        if (isset($group['Status'])) {
            if (in_array($group['Status'], $status_list)) {
                update_post_meta($postid, 'status_NUM', array_search($group['Status'], $status_list));
            } elseif (in_array($group['Status'], $status_list_short)) {
                update_post_meta($postid, 'status_NUM', array_search($group['Status'], $status_list_short));
            }
        }
        if (isset($group['Cost'])) {
            update_post_meta($postid, 'cost', sanitize_text_field($group['Cost']));
        }

        // Input file contains category by 'Name'. Need to get the 'slug' of the term.
        // import-checks should mean we always have a valid value to set
        // Bug 605 must use slug (not name) to set the category
        if (isset($group['Category'])) {
            $categories = explode("|", $group['Category']);
            $categories = str_replace("&#124;", "|", $categories);
            $categories = array_map('trim', $categories);
            foreach ($categories as $category) {
                $term = array_search($category, $group_categories);
                if ($term) {
                    wp_set_object_terms($postid, $term, U3A_GROUP_TAXONOMY, true);
                }
            }
        }
    }

    $done = count($groups_csv);
    $html = "<p>Number of groups processed: $done</p>\n";
    return $html;
}

/**
 * Import Events.  Return and notices/errors as HTML.
 * Assumes any required items are present, as will have been processed by import-checks
 *
 * @param boolean $force_new_events - if true, always create a new post when importing events
 * @return string $html - Any notices/errors as HTML
 */
function u3a_csv_import_events($force_new_events = false)
{
    // Read file, extract headings, create keyed array
    $sourcefile                             = U3A_IMPORT_FOLDER . '/events.csv';
    list($error_msg, $headers, $events_csv) = u3a_read_csv_file($sourcefile, 'events file');
    if (!empty($error_msg)) {
        return $error_msg;
    }

    // Build lookup table of event category  Slug->Name
    // This is important as we need the slug (not the name) to set the event category
    $event_categories = array();
    $terms            = get_terms(
        array(
            'taxonomy'   => U3A_EVENT_TAXONOMY,
            'hide_empty' => false,
        )
    );
    foreach ($terms as $term) {
        $event_categories[$term->slug] = $term->name;
    }

    // run for each row, edit or create post as appropriate
    foreach ($events_csv as $event) {
        // Find a post to update by ID or title, or create a new post
        $id     = (isset($event['ID']) && (!$force_new_events)) ? (int) $event['ID'] : '';
        $postid = u3a_find_or_create_post($id, sanitize_text_field($event['Name']), U3A_EVENT_CPT, $force_new_events);

        //now check organiser and venue and group ids
        if (isset($event['Organiser'])) {
            if (!empty($event['Organiser'])) {
                $orgid = u3a_id_by_match_title($event['Organiser'], U3A_CONTACT_CPT);
                if ($orgid) { // import-checks should ensure this always suceeds
                    update_post_meta($postid, 'eventOrganiser_ID', $orgid);
                }
            }
        }
        if (isset($event['Venue'])) {
            if (!empty($event['Venue'])) {
                $venid = u3a_id_by_match_title($event['Venue'], U3A_VENUE_CPT);
                if ($venid) { // import-checks should ensure this always suceeds
                    update_post_meta($postid, 'eventVenue_ID', $venid);
                }
            }
        }
        if (isset($event['Group'])) {
            if (!empty($event['Group'])) {
                $grpid = u3a_id_by_match_title($event['Group'], U3A_GROUP_CPT);
                if ($grpid) { // import-checks should ensure this always suceeds
                    update_post_meta($postid, 'eventGroup_ID', $grpid);
                }
            }
        }

        // Input file contains category by 'Name'.
        // import-checks should mean we always have a valid value to set
        // Bug 605 must use slug (not name) to set the category
        if (isset($event['Category'])) {
            $term = array_search($event['Category'], $event_categories);
            if ($term) {
                wp_set_object_terms($postid, $term, U3A_EVENT_TAXONOMY);
            }
        }

        if (isset($event['Date'])) {
            update_post_meta($postid, 'eventDate', sanitize_text_field($event['Date']));
        }
        if (isset($event['Time'])) {
            update_post_meta($postid, 'eventTime', sanitize_text_field($event['Time']));
        }
        if (isset($event['End time'])) {
            update_post_meta($postid, 'eventEndTime', sanitize_text_field($event['End time']));
        }
        // Only set eventDays if a 'Days' value is provided
        if (isset($event['Days']) && $event['Days'] > 0) {
            update_post_meta($postid, 'eventDays', (int) $event['Days']);
        }
        // Set eventEndDate
        if (isset($event['Days']) && $event['Days'] > 1) {
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
            $eventEndDate = date('Y-m-d', strtotime($event['Date']) + 86400 * ($event['Days'] - 1));
        } else {
            $eventEndDate = $event['Date'];
        }
        update_post_meta($postid, 'eventEndDate', $eventEndDate);

        if (isset($event['Cost'])) {
            update_post_meta($postid, 'eventCost', sanitize_text_field($event['Cost']));
        }

        if (isset($event['Booking'])) {
            if (in_array($event['Booking'], array('Yes', 'Y'))) {
                update_post_meta($postid, 'eventBookingRequired', '1');
            } else {
                update_post_meta($postid, 'eventBookingRequired', '0');
            }
        }
    }
    $done = count($events_csv);
    $html = "<p>Number of events processed: $done</p>\n";
    return $html;
}


/**
 * Find post by either ID or title.
 * If ID provided, check there is a post with this ID.  If not, treat as no ID.
 * If ID provided, retrieve post and check the title.  If title doesn't match, update the title and return the ID.
 * If no ID, try and match an existing post by title.  Success?  Return the ID.  Fail? Create new post with this title and default content and return ID.
 *
 * @param int $id Either ID of existing post or empty
 * @param string $title Post title
 * @param string $type Post type
 * @param boolean $force_new - If true, always create a new post
 * @return int Post ID
 */

function u3a_find_or_create_post($id, $title, $type, $force_new = false)
{
    global $wpdb;

    // Is there an existing post with this ID?  If not, ignore and treat as a new post
    if (!$force_new) {
        $id = trim($id);
        if (is_numeric($id)) {
            $found = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $wpdb->posts WHERE ID = %s AND post_type=%s",
                    array('$id', '$type')
                )
            );
            if ($found == 0) {
                $id = '';
            }
        }
    }

    // Can we get the ID of an existing post by matching the title?
    if (empty($id)) {
        if (!$force_new) {
            $postid = u3a_id_by_match_title($title, $type);
            if ($postid) {
                return $postid;
            }
        }

        // No match on title or new post forced. so create new post with default content
        $content = '';
        if ($type === U3A_VENUE_CPT) {
            $content = '<!-- wp:u3a/venuedata /--> <!-- wp:paragraph --> <p></p> <!-- /wp:paragraph -->';
        }
        if ($type === U3A_EVENT_CPT) {
            $content = '<!-- wp:u3a/eventdata /--> <!-- wp:paragraph --> <p></p> <!-- /wp:paragraph -->';
        }
        if ($type === U3A_GROUP_CPT) {
            $content = '<!-- wp:u3a/groupdata /--> <!-- wp:paragraph --> <p></p> <!-- /wp:paragraph --> <!-- wp:u3a/eventlist /-->';
        }
        $postid = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_type'    => $type,
                'post_content' => $content,
                'post_status'  => 'publish',
            )
        );
        return $postid;
    }

    // Dealing with existing post
    // Get title and check if it matches
    $current_title = get_post($id)->post_title;
    if (sanitize_title($current_title) !== sanitize_title($title)) {
        // Post title doesn't match, so update it
        wp_update_post(
            array(
                'ID'         => $id,
                'post_title' => $title,
            )
        );
    }
    return $id;
}
// description
function u3a_id_by_match_title($title, $type)
{
    global $wpdb;
    $san_title = sanitize_title($title);
    $rows      = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_type = %s AND post_status = 'publish'", $type));
    foreach ($rows as $post) {
        if ($san_title === sanitize_title($post->post_title)) {
            return $post->ID;
        }
    }
    return false;
}
