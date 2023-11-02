<?php

// Create folders used for upload/download and copy the spreadsheet template file

function u3a_csv_importexport_install()
{
    if (!is_dir(U3A_IMPORT_FOLDER)) {
		// phpcs:ignore
		mkdir(U3A_IMPORT_FOLDER);
    }
    if (!is_dir(U3A_EXPORT_FOLDER)) {
		// phpcs:ignore
		mkdir(U3A_EXPORT_FOLDER); // phpcs:ignore 
    }

    $htaccess = U3A_IMPORT_FOLDER . '/.htaccess';
    if (!file_exists($htaccess)) {
		// phpcs:ignore 
		file_put_contents($htaccess, "Require all denied\n");
    }
    $htaccess = U3A_EXPORT_FOLDER . '/.htaccess';
    if (!file_exists($htaccess)) {
		// phpcs:ignore
		file_put_contents($htaccess, "Require all denied\n");
    }
}
