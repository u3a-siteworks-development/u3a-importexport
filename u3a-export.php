<?php
/*This file contains the functions that export the data from the contacts, venues, groups and events custom post types.*/

function u3a_csv_export_contacts()
{
    //create array in $contactlist to contain data with headings to match post meta data
    $contactlist[] = array('ID', 'Name', 'Membership no.', 'Given', 'Family', 'Phone', 'Phone 2', 'Email');

    //get all the posts of type 'u3a_contact' as array $results
    $args    = array(
        'numberposts' => -1,
        'post_type'   => 'u3a_contact',
        'orderby'     => 'post_title',
        'order'       => 'ASC',
    );
    $results = get_posts($args);
    //loop through array picking up an array of metadata for each post
    //and pushing it onto $contactlist
    foreach ($results as $cont) {
        $id     = html_entity_decode($cont->ID);
        $name   = html_entity_decode($cont->post_title);
        $memID  = get_post_meta($cont->ID, 'memberid', true);
        $given  = html_entity_decode(get_post_meta($cont->ID, 'givenname', true));
        $family = html_entity_decode(get_post_meta($cont->ID, 'familyname', true));
        $phone  = get_post_meta($cont->ID, 'phone', true);
        $phone2 = get_post_meta($cont->ID, 'phone2', true);
        $email  = get_post_meta($cont->ID, 'email', true);

        $contactlist[] = array($id, $name, $memID, $given, $family, $phone, $phone2, $email);
    }
    //open the csv file and use fputcsv to add $contactlist array line
    //by line
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $f = fopen(U3A_EXPORT_FOLDER . '/contacts.csv', 'w');
    if (false === $f) {
        die('Unable to open file!');
    }
    foreach ($contactlist as $line) {
        fputcsv($f, $line);
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($f);
}
function u3a_csv_export_groups()
{
    //set the $day_list and $status_list arrays as in u3a-group class (possibly quicker than accessing the class)
    $day_list    = array(
        '' => '',
        0  => '',
        1  => 'Monday',
        2  => 'Tuesday',
        3  => 'Wednesday',
        4  => 'Thursday',
        5  => 'Friday',
        6  => 'Saturday',
        7  => 'Sunday',
    );
    $status_list = array(
        1 => 'Active, open to new members',
        2 => 'Active, not currently accepting new members',
        3 => 'Active, full but can join waiting list',
        4 => 'Temporarily inactive',
        5 => 'No longer meeting',
    );

    // Check which optional fields are required from u3a Settings
    $inc_coord2  = get_option('field_coord2', 1) == 1 ? true : false;
    $inc_deputy  = get_option('field_deputy', 1) == 1 ? true : false;
    $inc_tutor   = get_option('field_tutor', 1) == 1 ? true : false;
    $inc_gemail  = get_option('field_groupemail', 1) == 1 ? true : false;
    $inc_gemail2 = get_option('field_groupemail2', 1) == 1 ? true : false;
    $inc_cost    = get_option('field_cost', 1) == 1 ? true : false;

    // Create array of column headings in form data entry sequence
    $grouplist = array();
    $heading   = array('ID', 'Name', 'Status', 'Category', 'Day', 'Time', 'Frequency', 'When', 'Start time', 'End time', 'Venue ID', 'Venue', 'Coordinator ID', 'Coordinator');
    if ($inc_coord2) {
        $heading[] = 'Coordinator 2 ID';
        $heading[] = 'Coordinator 2';
    }
    if ($inc_deputy) {
        $heading[] = 'Deputy ID';
        $heading[] = 'Deputy';
    }
    if ($inc_tutor) {
        $heading[] = 'Tutor ID';
        $heading[] = 'Tutor';
    }
    if ($inc_gemail) {
        $heading[] = 'Email';
    }
    if ($inc_gemail2) {
        $heading[] = 'Email2';
    }
    if ($inc_cost) {
        $heading[] = 'Cost';
    }

    //print_r($heading);exit;
    $grouplist[] = $heading;

    //get all the posts of type 'u3a_group' as array $results
    $args    = array(
        'numberposts' => -1,
        'post_type'   => 'u3a_group',
        'orderby'     => 'post_title',
        'order'       => 'ASC',
    );
    $results = get_posts($args);
    // loop through posts array picking up metadata for each post, adding required data to export row
    // add completed row to $grouplist

    foreach ($results as $gp) {
        // ID
        $row = array($gp->ID);
        // Group name
        $row[] = html_entity_decode($gp->post_title);
        // Status
        $row[] = $status_list[get_post_meta($gp->ID, 'status_NUM', true)];
        // Category
        $terms = get_the_terms($gp->ID, U3A_GROUP_TAXONOMY);
        if ((false !== $terms) && !is_wp_error($terms)) {
            $names = array_map('trim', wp_list_pluck($terms, "name"));
            $names = array_map('html_entity_decode', $names);
            $names = str_replace("|", "&#124;", $names);
            $row[] = implode("|", $names);
        } else {
            // Shouldn't happen, but output an empty value
            $row[] = '';
        }
        // Day
        $row[] = $day_list[get_post_meta($gp->ID, 'day_NUM', true)];
        // Time
        $row[] = get_post_meta($gp->ID, 'time', true);
        // Frequency
        $row[] = get_post_meta($gp->ID, 'frequency', true);
        // When
        $row[] = html_entity_decode(get_post_meta($gp->ID, 'when', true));
        // Start time
        $row[] = html_entity_decode(get_post_meta($gp->ID, 'startTime', true));
        //End time
        $row[] = html_entity_decode(get_post_meta($gp->ID, 'endTime', true));
        // Venue ID and title
        list($id, $title) = id_and_title_of_metafield($gp, 'venue_ID');
        $row[]            = $id;
        $row[]            = $title;
        // Coordinator ID and title
        list($id, $title) = id_and_title_of_metafield($gp, 'coordinator_ID');
        $row[]            = $id;
        $row[]            = $title;
        // 2nd Coordinator ID and title
        if ($inc_coord2) {
            list($id, $title) = id_and_title_of_metafield($gp, 'coordinator2_ID');
            $row[]            = $id;
            $row[]            = $title;
        }
        // Deputy ID and title
        if ($inc_deputy) {
            list($id, $title) = id_and_title_of_metafield($gp, 'deputy_ID');
            $row[]            = $id;
            $row[]            = $title;
        }
        // Tutor ID and title
        if ($inc_tutor) {
            list($id, $title) = id_and_title_of_metafield($gp, 'tutor_ID');
            $row[]            = $id;
            $row[]            = $title;
        }
        // Group email
        if ($inc_gemail) {
            $row[] = get_post_meta($gp->ID, 'email', true);
        }
        // Secondary group email
        if ($inc_gemail2) {
            $row[] = get_post_meta($gp->ID, 'email2', true);
        }
        // Cost
        if ($inc_cost) {
            $row[] = html_entity_decode(get_post_meta($gp->ID, 'cost', true));
        }

        $grouplist[] = $row;
    }
    //open the csv file and use fputcsv to add $grouplist array line
    //by line
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $f = fopen(U3A_EXPORT_FOLDER . '/groups.csv', 'w');
    if (false === $f) {
        die('Unable to open file!');
    }
    foreach ($grouplist as $line) {
        fputcsv($f, $line);
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($f);
}
function u3a_csv_export_venues()
{
    //create array to contain data with headings to match post meta data

    $include_district = (get_option('field_v_district', 1) == 1) ? true : false;

    $venuelist = array();
    $heading   = array('ID', 'Name', 'District', 'Address Line 1', 'Address Line 2', 'Town', 'Postcode', 'Accessibility', 'Phone', 'URL');
    if (!$include_district) {
        unset($heading[2]);
    }
    array_push($venuelist, $heading);

    //get all the posts of type 'u3a_venue' as array $results
    $args    = array(
        'numberposts' => -1,
        'post_type'   => 'u3a_venue',
        'orderby'     => 'post_title',
        'order'       => 'ASC',
    );
    $results = get_posts($args);
    //loop through array picking up an array of metadata for each post
    //and pushing it onto $venuelist
    foreach ($results as $ven) {
        $id       = $ven->ID;
        $name     = html_entity_decode($ven->post_title);
        $district = html_entity_decode(get_post_meta($ven->ID, 'district', true));
        $addr1    = html_entity_decode(get_post_meta($ven->ID, 'address1', true));
        $addr2    = html_entity_decode(get_post_meta($ven->ID, 'address2', true));
        $town     = html_entity_decode(get_post_meta($ven->ID, 'town', true));
        $pcode    = html_entity_decode(get_post_meta($ven->ID, 'postcode', true));
        $access   = html_entity_decode(get_post_meta($ven->ID, 'access', true));
        $phone    = get_post_meta($ven->ID, 'phone', true);
        $url      = get_post_meta($ven->ID, 'url', true);
        $venue    = array($id, $name, $district, $addr1, $addr2, $town, $pcode, $access, $phone, $url);
        if (!$include_district) {
            unset($venue[2]);
        }
        array_push($venuelist, $venue);
    }
    //open the csv file and use fputcsv to add $contactlist array line
    //by line
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $f = fopen(U3A_EXPORT_FOLDER . '/venues.csv', 'w');
    if (false === $f) {
        die('Unable to open file!');
    }
    foreach ($venuelist as $line) {
        fputcsv($f, $line);
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($f);
}

function u3a_csv_export_events()
{
    //create array to contain data with headings to match post meta data
    $eventlist   = array();
    $heading     = array('ID', 'Name', 'Category', 'Date', 'Time', 'End time', 'Days', 'Group ID', 'Group', 'Venue ID', 'Venue', 'Organiser ID', 'Organiser', 'Cost', 'Booking');
    $eventlist[] = $heading;

    //get all the posts of type 'u3a_event' as array $results
    $args    = array(
        'numberposts' => -1,
        'post_type'   => 'u3a_event',
        'orderby'     => 'post_title',
        'order'       => 'ASC',
    );
    $results = get_posts($args);
    //loop through array picking up an array of metadata for each post
    //and pushing it onto $eventlist
    foreach ($results as $evt) {
        $id    = $evt->ID;
        $name  = html_entity_decode($evt->post_title);
        $terms = get_the_terms($evt->ID, U3A_EVENT_TAXONOMY);
        if ((false !== $terms) && !is_wp_error($terms)) {
            $cat = html_entity_decode($terms[0]->name);
        } else {
            // Shouldn't happen, but output an empty value
            $cat = '';
        }
        // Note: get_post_meta returns empty string if value not set.
        $date                = get_post_meta($evt->ID, 'eventDate', true);
        $time                = get_post_meta($evt->ID, 'eventTime', true);
        $endtime             = get_post_meta($evt->ID, 'eventEndTime', true);
        $days                = get_post_meta($evt->ID, 'eventDays', true);
        list($grpid, $group) = id_and_title_of_metafield($evt, 'eventGroup_ID');
        list($venid, $venue) = id_and_title_of_metafield($evt, 'eventVenue_ID');
        list($orgid, $org)   = id_and_title_of_metafield($evt, 'eventOrganiser_ID');
        $cost                = get_post_meta($evt->ID, 'eventCost', true);
        $booking             = get_post_meta($evt->ID, 'eventBookingRequired', true) === 1 ? 'Yes' : 'No';

        $event = array($id, $name, $cat, $date, $time, $endtime, $days, $grpid, $group, $venid, $venue, $orgid, $org, $cost, $booking);

        $eventlist[] = $event;
    }
    //open the csv file and use fputcsv to add $contactlist array line by line
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
    $f = fopen(U3A_EXPORT_FOLDER . '/events.csv', 'w');
    if (false === $f) {
        die('Unable to open file!');
    }
    foreach ($eventlist as $line) {
        fputcsv($f, $line);
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
    fclose($f);
}

/**
 * Get the id and title related to some metafield of a post.
 *
 * @param post object $post
 * @param str $metafield the name of the meta field containing some post's id
 *
 * @return array [id, plain text of title]
 */
function id_and_title_of_metafield($post, $metafield)
{
    $id    = get_post_meta($post->ID, $metafield, true);
    $title = '';
    if ($id) {
        $metapost = get_post($id);
        if ($metapost) {
            $title = html_entity_decode($metapost->post_title);
        }
    }
    return array($id, $title);
}
