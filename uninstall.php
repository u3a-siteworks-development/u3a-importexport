<?php

// Remove the data folder and all its contents

require 'inc/definitions.php';

array_map( 'unlink', glob( U3A_IMPORT_FOLDER . '/*' ) );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
rmdir( U3A_IMPORT_FOLDER );
array_map( 'unlink', glob( U3A_EXPORT_FOLDER . '/*' ) );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
rmdir( U3A_EXPORT_FOLDER );
