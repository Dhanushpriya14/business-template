<?php
/*
Plugin Name: Simple SEO Plugin
Plugin URI: http://example.com
Description: A simple SEO plugin to manage meta tags, sitemaps, and robots.txt.
Version: 1.0
Author: Your Name
Author URI: http://example.com
License: GPL2
*/

// Ensure direct access is not allowed
if (!defined('ABSPATH')) {
    exit;
}

// Add Meta Box for SEO Fields
add_action('add_meta_boxes', 'ssp_add_meta_box');
function ssp_add_meta_box() {
    add_meta_box(
        'ssp_meta_box',
        'SEO Meta Data',
        'ssp_meta_box_callback',
        'post',
        'side',
        'high'
    );
}

function ssp_meta_box_callback($post) {
    wp_nonce_field('ssp_save_meta_box_data', 'ssp_meta_box_nonce');

    $meta_title = get_post_meta($post->ID, '_ssp_meta_title', true);
    $meta_description = get_post_meta($post->ID, '_ssp_meta_description', true);

    echo '<label for="ssp_meta_title">Meta Title</label>';
    echo '<input type="text" id="ssp_meta_title" name="ssp_meta_title" value="' . esc_attr($meta_title) . '" size="25" />';
    echo '<br><br>';
    echo '<label for="ssp_meta_description">Meta Description</label>';
    echo '<textarea id="ssp_meta_description" name="ssp_meta_description" rows="4" cols="27">' . esc_textarea($meta_description) . '</textarea>';
}

// Save Meta Data
add_action('save_post', 'ssp_save_meta_box_data');
function ssp_save_meta_box_data($post_id) {
    if (!isset($_POST['ssp_meta_box_nonce']) || !wp_verify_nonce($_POST['ssp_meta_box_nonce'], 'ssp_save_meta_box_data')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (isset($_POST['ssp_meta_title'])) {
        $meta_title = sanitize_text_field($_POST['ssp_meta_title']);
        update_post_meta($post_id, '_ssp_meta_title', $meta_title);
    }

    if (isset($_POST['ssp_meta_description'])) {
        $meta_description = sanitize_textarea_field($_POST['ssp_meta_description']);
        update_post_meta($post_id, '_ssp_meta_description', $meta_description);
    }
}

// Output Meta Tags in the Header
add_action('wp_head', 'ssp_output_meta_tags');
function ssp_output_meta_tags() {
    if (is_single()) {
        global $post;
        $meta_title = get_post_meta($post->ID, '_ssp_meta_title', true);
        $meta_description = get_post_meta($post->ID, '_ssp_meta_description', true);

        if ($meta_title) {
            echo '<meta name="title" content="' . esc_attr($meta_title) . '">' . "\n";
        }
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
    }
}

// Generate XML Sitemap
add_action('init', 'ssp_generate_sitemap');
function ssp_generate_sitemap() {
    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

    $args = array('post_type' => 'post', 'posts_per_page' => -1);
    $posts = get_posts($args);

    foreach ($posts as $post) {
        setup_postdata($post);
        $sitemap .= '<url>';
        $sitemap .= '<loc>' . get_permalink($post->ID) . '</loc>';
        $sitemap .= '<lastmod>' . get_the_modified_time('c', $post->ID) . '</lastmod>';
        $sitemap .= '</url>';
    }

    $sitemap .= '</urlset>';
    file_put_contents(ABSPATH . 'sitemap.xml', $sitemap);
}

// Robots.txt Editor
add_action('admin_menu', 'ssp_robots_menu');
function ssp_robots_menu() {
    add_submenu_page(
        'options-general.php',
        'Robots.txt Editor',
        'Robots.txt',
        'manage_options',
        'ssp-robots-txt',
        'ssp_robots_page'
    );
}

function ssp_robots_page() {
    if (isset($_POST['ssp_save_robots'])) {
        update_option('ssp_robots_txt', stripslashes($_POST['ssp_robots_txt']));
    }

    $robots_txt = get_option('ssp_robots_txt', "User-agent: *\nDisallow: /wp-admin/\n");

    echo '<div class="wrap">';
    echo '<h1>Robots.txt Editor</h1>';
    echo '<form method="post">';
    echo '<textarea name="ssp_robots_txt" rows="10" cols="50" class="large-text">' . esc_textarea($robots_txt) . '</textarea>';
    echo '<br>';
    echo '<input type="submit" name="ssp_save_robots" class="button-primary" value="Save Changes">';
    echo '</form>';
    echo '</div>';
}

add_filter('robots_txt', 'ssp_custom_robots_txt');
function ssp_custom_robots_txt($output) {
    return get_option('ssp_robots_txt', $output);
}
