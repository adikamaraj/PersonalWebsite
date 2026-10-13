<?php
/** Local installation helper. Run with PHP in the WordPress container. */
if ( PHP_SAPI !== 'cli' ) { http_response_code( 403 ); exit; }
require dirname( __DIR__, 4 ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$result = activate_plugin( 'adi-site-content/adi-site-content.php' );
if ( is_wp_error( $result ) ) { fwrite( STDERR, $result->get_error_message() ); exit( 1 ); }
switch_theme( 'adi-personal' );
echo 'Created ' . adi_content_import() . " starter entries.\n";
flush_rewrite_rules();
