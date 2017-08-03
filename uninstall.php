<?php 
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	echo "ACCESS FORBIDDEN";
    exit();
}
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "stock_engines" );
delete_option('stock_engine_version');
delete_option('stock_engine_version_text');
?>
