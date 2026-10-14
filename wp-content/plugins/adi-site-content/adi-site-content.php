<?php
/**
 * Plugin Name: Adi Site Content
 * Description: Portable portfolio and publication content for Adithya Kamaraj, with an optional starter-content importer.
 * Version: 1.2.1
 * Author: Adithya Kamaraj
 * License: GPL-2.0-or-later
 * Requires PHP: 8.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function adi_content_register() {
	register_post_type( 'adi_project', array(
		'labels' => array( 'name' => 'Projects', 'singular_name' => 'Project', 'add_new_item' => 'Add project', 'edit_item' => 'Edit project' ),
		'public' => true, 'show_in_rest' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'projects' ),
		'menu_icon' => 'dashicons-lightbulb', 'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes', 'custom-fields' ),
	) );
	register_post_type( 'adi_publication', array(
		'labels' => array( 'name' => 'Publications', 'singular_name' => 'Publication', 'add_new_item' => 'Add publication' ),
		'public' => true, 'show_in_rest' => true, 'has_archive' => true, 'rewrite' => array( 'slug' => 'publications' ),
		'menu_icon' => 'dashicons-welcome-learn-more', 'supports' => array( 'title', 'editor', 'excerpt', 'revisions', 'custom-fields' ),
	) );
	register_taxonomy( 'adi_space', 'adi_project', array(
		'labels' => array( 'name' => 'Spaces', 'singular_name' => 'Space' ),
		'public' => true, 'show_in_rest' => true, 'hierarchical' => true, 'rewrite' => array( 'slug' => 'spaces' ),
	) );
	foreach ( array( 'adi_year', 'adi_role', 'adi_outcome', 'adi_visual', 'adi_format', 'adi_citation' ) as $key ) {
		foreach ( array( 'adi_project', 'adi_publication' ) as $type ) {
			register_post_meta( $type, $key, array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => function() { return current_user_can( 'edit_posts' ); } ) );
		}
	}
	foreach ( array( 'adi_project', 'adi_publication' ) as $type ) {
		register_post_meta( $type, 'adi_source_url', array( 'type' => 'string', 'single' => true, 'show_in_rest' => true, 'sanitize_callback' => 'esc_url_raw', 'auth_callback' => function() { return current_user_can( 'edit_posts' ); } ) );
	}
}
add_action( 'init', 'adi_content_register' );
/** Rename the original spaces once, keeping slugs and project assignments intact. */
function adi_content_update_spaces() {
 if ('2' === get_option('adi_content_space_version')) { return; }
 $names = array('research'=>'Research', 'design-ai'=>'Technology & AI', 'startups-business'=>'Business & Innovation');
 foreach ($names as $slug=>$name) {
  $term = get_term_by('slug', $slug, 'adi_space');
  if ($term) {
   $result = wp_update_term($term->term_id, 'adi_space', array('name'=>$name));
   if (is_wp_error($result)) { return; }
  }
 }
 update_option('adi_content_space_version', '2');
}
add_action('init', 'adi_content_update_spaces', 20);

register_activation_hook( __FILE__, function() { adi_content_register(); flush_rewrite_rules(); } );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

function adi_content_meta_boxes() {
	foreach ( array( 'adi_project', 'adi_publication' ) as $type ) {
		add_meta_box( 'adi_details', 'Portfolio details', 'adi_content_meta_box', $type, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'adi_content_meta_boxes' );

function adi_content_meta_box( $post ) {
	wp_nonce_field( 'adi_save_details', 'adi_details_nonce' );
	$fields = array( 'adi_year' => 'Year / period', 'adi_role' => 'Your role', 'adi_outcome' => 'Key outcome or research question', 'adi_format' => 'Type (e.g. PhD research, innovation tool)', 'adi_source_url' => 'Source or publication URL', 'adi_citation' => 'Publication citation' );
	foreach ( $fields as $key => $label ) {
		echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br><input class="widefat" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" type="' . ( 'adi_source_url' === $key ? 'url' : 'text' ) . '" value="' . esc_attr( get_post_meta( $post->ID, $key, true ) ) . '"></p>';
	}
	echo '<p><label for="adi_visual"><strong>Illustration</strong></label><br><select id="adi_visual" name="adi_visual">';
	foreach ( array( 'pricing' => 'Pricing / research', 'canvas' => 'Design canvas', 'launch' => 'Startup launch', 'markets' => 'Business insights', 'circular' => 'Circular economy', 'hardware' => 'Robotics / hardware' ) as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( get_post_meta( $post->ID, 'adi_visual', true ), $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></p><p>A featured image replaces this illustration. Use the editor for the full case study and the excerpt for its card summary. The Order field controls portfolio order.</p>';
}

function adi_content_save_meta( $post_id ) {
	if ( ! isset( $_POST['adi_details_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['adi_details_nonce'] ) ), 'adi_save_details' ) || defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || ! current_user_can( 'edit_post', $post_id ) ) { return; }
	foreach ( array( 'adi_year', 'adi_role', 'adi_outcome', 'adi_visual', 'adi_format', 'adi_source_url', 'adi_citation' ) as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			if ( ! is_string( $_POST[ $key ] ) ) { continue; }
			$value = wp_unslash( $_POST[ $key ] );
			update_post_meta( $post_id, $key, 'adi_source_url' === $key ? esc_url_raw( $value ) : sanitize_text_field( $value ) );
		}
	}
}
add_action( 'save_post_adi_project', 'adi_content_save_meta' );
add_action( 'save_post_adi_publication', 'adi_content_save_meta' );

require_once __DIR__ . '/starter-content.php';
require_once __DIR__ . '/content-updates.php';

add_action( 'admin_menu', function() {
	add_management_page( 'Adi site setup', 'Adi site setup', 'manage_options', 'adi-site-setup', 'adi_content_setup_page' );
} );
function adi_content_setup_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	if ( isset( $_POST['adi_apply_messaging'] ) ) {
		check_admin_referer( 'adi_apply_messaging' );
		$result = adi_content_apply_messaging_defaults();
		if (is_wp_error($result)) {
			echo '<div class="notice notice-error"><p>'.esc_html($result->get_error_message()).'</p></div>';
		} else {
			echo '<div class="notice notice-success"><p>Updated messaging applied. Clear the Bluehost site cache, then reload your homepage.</p></div>';
		}
	}
	if ( isset( $_POST['adi_import'] ) ) {
		check_admin_referer( 'adi_import_content' );
		$result = adi_content_import();
		echo '<div class="notice notice-success"><p>Site content is ready. Created ' . esc_html( $result ) . ' entries. Existing starter entries were preserved.</p></div>';
	}
	echo '<div class="wrap"><h1>Adi site setup</h1><p>Create your Home, About and Writing pages, six portfolio cases, and two conference publication records. Three suggested essays are saved as drafts for your review.</p><p>This sets Home as the front page and Writing as the posts page. It adds missing content, preserves existing entries, and can be run again. Review the imported profile before publishing the site.</p><form method="post">';
	wp_nonce_field( 'adi_import_content' );
	submit_button( 'Create starter content', 'primary', 'adi_import' );
	echo '</form><hr><h2>Apply updated messaging</h2><p>Use the current Adi Personal theme defaults for the professional descriptor, Home headline, introduction and collaboration copy. This replaces saved Customizer values for those fields and keeps a backup. Your name, email, location and profile links are preserved.</p><form method="post">';
	wp_nonce_field('adi_apply_messaging');
	submit_button('Apply updated messaging', 'secondary', 'adi_apply_messaging');
	echo '</form></div>';
}

add_action( 'pre_get_posts', function( $query ) {
	if ( ! is_admin() && $query->is_main_query() && ( $query->is_post_type_archive( 'adi_project' ) || $query->is_tax( 'adi_space' ) ) ) {
		$query->set( 'posts_per_page', 12 );
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
} );
