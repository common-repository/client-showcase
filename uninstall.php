<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

delete_option( 'client_showcase_list' );
delete_option( 'client_showcase_list_display' );

global $wpdb;
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'client_showcase' );" );
$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
$wpdb->query( "DELETE meta FROM {$wpdb->usermeta} meta WHERE meta_key = 'client_showcase_ignore_notice';" );
