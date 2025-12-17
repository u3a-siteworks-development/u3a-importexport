=== u3a-importexport ===
Requires at least: 5.9
Tested up to: 6.9
Stable tag: 5.9
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides facility to import/export CSV data for groups, contacts, events and venues

== Description ==

This plugin is part of the SiteWorks project.  It provides facilities to export or import u3a groups 
and events and related contact and venue information.

For more information please refer to the [SiteWorks website](https://siteworks.u3a.org.uk/)

Version 2.0.0 includes a change in the way event categories are handled.
In version 1.x.x only a single category per event was supported.  Version 2 allows multiple event categories.
Where multiple categories exist for an event the Category field is now a vertical bar separated string of all the categories.
For example  "Outing|Social|West Region"


== Frequently Asked Questions ==

Please refer to the documentation on the [SiteWorks website](https://siteworks.u3a.org.uk/u3a-siteworks-training/)

== Changelog ==
= 2.0.0 =
* Feature 1156 Provide support for multiple event categories (introduced in core plugin 2.0.0)
= 1.7.1 =
* Bug 1143: Import/Export plugin doesn't show groups with 'Unspecified' days when sorted.
= 1.7.0 =
* Tested with WordPress 6.8
* Code refactored to access plugin update service via configuration plugin
= 1.6.3 =
* Feature 1094 Change short text form of group status to "Waiting list"
* Bug 1104 Fix fatal error if plugin.php is not loaded when plugin initialises.
* Tested up to WordPress 6.7
* Add "Requires Plugins" header field to specify dependency on SiteWorks core plugin
= 1.6.2 =
* Import and export now handle core v1.1.0 metadata fields and multiple group categories
* Feature 1032 - Alter group status short term from "Suspended" to "Dormant"
= 1.6.1 =
* Bug 1033 - only set eventDays if a numeric value provided in event import file
* Tested up to WordPress 6.5
= 1.6.0 =
* First production code release
* Tested up to WordPress 6.4
= 1.5.100 = 
* Add warning after uploading CSV with ID fields present
= 1.5.99 =
* Bugs 928, 930 Fix issue importing Groups and Venues
= 1.5.98 =
* Release candidate 1
* Update plugin update checker library to v5p2
= 1.5.12 =
* Fix Ensure import of unspecified group Meeting Day sets value correctly
= 1.5.11 =
* Bug 815 Include actual csv filename rather than generic name in error messages
* Bug 815 Fix failure to import Given name in Contacts
= 1.5.10 =
* Bug 805 Add test to identify file encoding error
= 1.5.9 =
* Bug 805 (1) Reject column District if this optional field is not in use
* Bug 805 (3,4) Categories not imported for Group or Event
* Correct import of Event group, organiser and venue where these are set
= 1.5.8 =
* Bugs including 757, 761 Add check to ensure that import file is Unicode (UTF-8)
= 1.5.7 =
* Security: Check admin user when processing all form submissions
* Provide option to always create a new event when importing (to allow any number of events titled 'May Meeting' to be created)
= 1.5.6 =
* Allow any valid csv file to be uploaded instead of requiring a specific file name
* Updated spreadsheet template
* Additional checks on imported data
* Correct file time displays
= 1.5.5 =
* Bug 690 Fix error creating duplicate entries where name contains an apostrophe
= 1.5.4 =
* Fix error generating export files if day_NUM stored value 0
= 1.5.3 =
* Add pre-import check to ensure all CSV lines have same number of data entries
* Add list of group status codes to downloadable spreadsheet template file
= 1.5.2 =
* Add 'Help' tab content to plugin admin page
= 1.5.1 = 
* Handle additional fields of 'Cost' and 'Booking Required' for venues
* Correct handling of Group and Event categories when importing
* Add eventEndDate to metadata for events running for more than one day
* Remove checks in the import code that duplicate checks in the pre-import validation checking
* Rename plugin display name for consistency with other plugins
= 1.4.9 =
* Bug 668 'District' column name now accepted as a valid column heading in venues.csv
= 1.4.8 =
* Bug 662 Ignore letter case of uploaded filenames
= 1.4.7 =
* Correct references to *_CPT constants in some SQL queries
= 1.4.5 =
* Add check for Venue to import validation for groups.csv file
= 1.4.4 =
* Add separate check to validate each CSV file at the time it is uploaded, displaying messages about all errors found.  If an error is found do not accept the upload.
= 1.4.3 =
* csv export files now use same field order as data entry pages
* Add option to remove csv files after importing from them
* minor changes to importtemplate.xls
= 1.4.2 =
* Redesigned admin page with tabbed layout
* Use nonce checks on all form submissions
* Check filenames before adding to uploads folder
* Updated the downloadble template file
* Refactor u3a_find_or_create_post() mechanism for matching post titles
* Add handling for empty email fields
* Set sort order of export files by title
= 1.x series =
* Intial development code
