<?php
/*
Plugin Name: RSS-feed Canceller
Plugin URI: https://plugmize.jp/product/rss-feed-canceller
Description: This plug-in can be used to stop the RSS feed delivery in the article unit.
Version: 1.0.0
Author: PLUGMIZE
Author URI: https://plugmize.jp/
License: GPL2
*/

class RssFeedCanceller {

    function RssFeedCanceller () {
        load_plugin_textdomain('rss-feed-canceller', false, basename( dirname( __FILE__ ) ) . '/languages');
        add_action('admin_menu', array(&$this, 'rss_feed_canceller_add_post_edit_controls'));
        add_action('save_post', array(&$this, 'rss_feed_canceller_save'));
        add_action('pre_get_posts', array(&$this, 'rss_feed_canceller_posts_canceller'));
    }

    // add menu
    function rss_feed_canceller_add_post_edit_controls () {
        add_meta_box('rss_feed_canceller', __('RSS Feed Setting', 'rss-feed-canceller'), array(&$this, 'rss_feed_canceller_menu'), 'post', 'side', 'low');
    }

    // metabox body
    function rss_feed_canceller_menu () {
        global $post;

        echo '<input type="hidden" name="rss_feed_canceller_menu_nonce" value="' , wp_create_nonce(basename(__FILE__)) , '" />';

        echo '<p><label><input type="checkbox" name="rss_feed_canceller_invisible" id="rss_feed_canceller_invisible"';
        if (get_post_meta($post->ID, 'rss_feed_canceller_invisible', true)) { echo ' checked="checked"'; }
        echo ' /> '.__('Not delivered to the feed', 'rss-feed-canceller').'</label></p>';
    }

    // save metabox
    function rss_feed_canceller_save ($post_id) {
        global $post;

        // exists metabox
        if (!wp_verify_nonce($_POST['rss_feed_canceller_menu_nonce'], basename(__FILE__))) {
            return $post_id;
        }

        // check
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }


        // save custom-fields
        //  key = rss_feed_canceller_invisible
        $old = get_post_meta($post_id, 'rss_feed_canceller_invisible', true);
        $new = $_POST['rss_feed_canceller_invisible'];
        if ($new && $new != $old) {
            update_post_meta($post_id, 'rss_feed_canceller_invisible', $new);
        } elseif ('' == $new && $old) {
            delete_post_meta($post_id, 'rss_feed_canceller_invisible', $old);
        }
    }

    // Delivery stop processing (add_action pre_get_posts)
    function rss_feed_canceller_posts_canceller ($query) {

        // It does not interfere with the management screen and the main query
        if( is_admin() || ! $query->is_main_query() ){
            return;
        }

        // Exclude the delivery stop article
        if ( $query->is_feed() ) {
            $query->set('meta_query', array(
                array(
                    'key'   => 'rss_feed_canceller_invisible',
                    'compare' => 'NOT EXISTS'
                )
            ));
            return;
        }
    }

    // Uninstall
    function plugin_uninstall() {
        global $wpdb;

        // delete meta_key
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = 'rss_feed_canceller_invisible'");
    }
}

// Activation & Deactivation
register_uninstall_hook( __FILE__, array('RssFeedCanceller', 'plugin_uninstall'));

new RssFeedCanceller();
