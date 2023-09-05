<?php

abstract class AutocompleteVersion {
    const JQUERY     = 1;
    const SVELTE     = 2;
}

class WCISAutocomplete
{
    static function is_svelte_version() {
        $autocomplete_version = get_option('autocomplete_version');

        return ($autocomplete_version &&
                $autocomplete_version == AutocompleteVersion::SVELTE);
    }

    static function get_autocomplete_script_url()
    {
        if (!self::is_svelte_version()) {
            return 'https://acp-magento.appspot.com/js/acp-magento.js';
        } else {
            return 'https://static-autocomplete.fastsimon.com/fast-simon-autocomplete-init.umd.js?';
        }
    }

    static function get_autocomplete_script_params($products_per_page) {
        global $product;
        $args = "mode=woocommerce&";
        $args = $args . "UUID=" . get_option('wcis_site_id') ."&";
        $args = $args . "store=" . get_current_blog_id() ."&";

        //the following params added from here are for jquery autocomplete,
        //for now send these params also to svelte serving

        $plugin = WCISPlugin::get_instance();

        try{
            if ($plugin->is_woocommerce_installed_and_supported()
                && function_exists('WC')){
                if(isset(WC()->session)) {
                    $user_session = WC()->session->get_session_cookie();   // $user_session = [$customer_id, $session_expiration, $session_expiring, $cookie_hash]
                    if ($user_session && count($user_session) > 3){
                        $args = $args . "st=" . $user_session[0] ."&";
                        $args = $args . "cart_token=" . $user_session[3] ."&";
                    }
                }

            }
        } catch (Exception $e) {}

        if (is_admin_bar_showing()){
            $is_admin_bar_showing = "is_admin_bar_showing=1&";
        } else {
            $is_admin_bar_showing = "is_admin_bar_showing=0&";
        }
        $args .= $is_admin_bar_showing;

        if (is_user_logged_in()){
            $args .= 'is_user_logged_in=1&';
        } else {
            $args .= 'is_user_logged_in=0&';
        }

        if ($products_per_page){
            $products_per_page = $products_per_page;
        } else {
            $products_per_page = get_option('posts_per_page');
        }
        $args .= "products_per_page=" . (string)$products_per_page . "&";
        if ($product){
            $args .= 'product_url=' . get_permalink() .'&';
            $args .= 'product_id=' . get_the_ID() .'&';
        }

        $options = get_option( 'wcis_general_settings' );

        if ($options
            && array_key_exists('serp_page_id', $options)
            && array_key_exists('is_serp_enabled', $options)
            && $options['is_serp_enabled']){
            $home_url = function_exists('icl_object_id') ? site_url() : home_url();
            $args .= 'serp_path=' . esc_url(str_replace($home_url, "", get_permalink($options['serp_page_id'])));
        }

        return $args;
    }

    static public function set_autocomplete_version(
            $version = AutocompleteVersion::SVELTE, $show_message = false) {
        $plugin = WCISPlugin::get_instance();
        $message = 'Autocomplete version was set to ';

        if ($version == AutocompleteVersion::JQUERY) {
            $plugin->isp_update_option('autocomplete_version',
                AutocompleteVersion::JQUERY);
            $message .= AutocompleteVersion::JQUERY;
        }
        elseif ($version == AutocompleteVersion::SVELTE) {
            $plugin->isp_update_option('autocomplete_version',
                AutocompleteVersion::SVELTE);
            $message .= AutocompleteVersion::SVELTE;
        }
        else {
            $message = 'Invalid autocomplete version!';
        }

        if ($version == AutocompleteVersion::JQUERY ||
            $version == AutocompleteVersion::SVELTE) {
            try {
                wp_cache_flush();
            }
            catch (Exception $e) {}
        }

        if ($show_message) {
            echo $message;
            exit();
        }
    }

    static public function get_script_id() {
        $plugin = WCISPlugin::get_instance();

        if (!self::is_svelte_version()) {
            return $plugin->plugin_slug . '-inject3';
        }
        else {
            return 'autocomplete-initilizer';
        }
    }

    static public function enqueue_script($products_per_page) {
        $script = self::get_autocomplete_script_url();
        $args = self::get_autocomplete_script_params($products_per_page);
        $script_id = self::get_script_id();
        $dependencies = self::is_svelte_version() ? [] : array('jquery' );

        wp_enqueue_script($script_id, $script . '?' . $args, $dependencies, false, true);
    }
}