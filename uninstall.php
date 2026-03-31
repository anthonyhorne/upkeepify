<?php
/**
 * Uninstall Upkeepify
 *
 * Removes all plugin data when the plugin is deleted from WordPress.
 *
 * @package Upkeepify
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ─── Options ─────────────────────────────────────────────────────────────────

$options = array(
	'upkeepify_settings',
	'upkeepify_db_version',
	'upkeepify_migration_history',
	'upkeepify_migration_log',
	'upkeepify_backup_history',
	'upkeepify_notifications',
	'upkeepify_sample_data_inserted',
);

foreach ( $options as $option ) {
	delete_option( $option );
}

// ─── Posts ────────────────────────────────────────────────────────────────────

$post_types = array( 'maintenance_tasks', 'upkeepify_responses', 'provider_responses' );

foreach ( $post_types as $post_type ) {
	$posts = get_posts( array(
		'post_type'      => $post_type,
		'numberposts'    => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	) );
	foreach ( $posts as $post_id ) {
		// Delete the featured image attachment (task photo) before removing the post.
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			wp_delete_attachment( $thumbnail_id, true );
		}
		wp_delete_post( $post_id, true );
	}
}

// ─── Taxonomy terms ───────────────────────────────────────────────────────────

$taxonomies = array( 'service_provider', 'task_category', 'task_type', 'task_status', 'unit' );

foreach ( $taxonomies as $taxonomy ) {
	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'fields'     => 'ids',
	) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, $taxonomy );
		}
	}
}
