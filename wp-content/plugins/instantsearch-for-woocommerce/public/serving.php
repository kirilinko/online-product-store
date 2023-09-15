<?php

include_once( plugin_dir_path( __FILE__ ) . 'wcis_product_cat_utils.php' );

abstract class ServingVersion {
    const JQUERY_V1     = 0;
    const JQUERY_V2     = 1;
    const SVELTE        = 2;
}

abstract class ServingEndpoint {
    const DEFAULTT  = "https://acp-magento.appspot.com";
    const PREMIUM  = "https://premium-dot-acp-magento.appspot.com";
    const ULTIMATE = "https://ultimate-dot-acp-magento.appspot.com";
}

class WCISServing {

    static function get_svelte_serving() {
        $category_id = WCISCategory::wcis_get_category_str();
        $uuid = get_option('wcis_site_id');
        $store_id = get_current_blog_id();
        $options = get_option( 'wcis_general_settings' );
        $serp_page = get_post($options['serp_page_id']);
        $serp_slug = $serp_page->post_name;
        $endpoint = self::get_serving_endpoint();
        $serving_config = self::get_serving_config();
        $is_logged_in = is_user_logged_in() ? 'true' : 'false';
        $user_id = get_current_user_id();

        $result = sprintf('
            <style id="fast-serp-css">%1$s</style>
    
            <script class="fast-simon-script">
                var STORE_UUID = "%2$s";
                var STORE_ID = Number("%3$s");
                var FAST_SEARCH_HANDLE = "%4$s";
                var FAST_ENDPOINT = "%5$s";
                var FAST_IS_USER_LOGGED_IN = %6$s;
                var FAST_USER_ID = %7$s;
                var FAST_CONFIG = %8$s;
            </script>',
                $serving_config['serp_css'],
                $uuid,
                $store_id,
                $serp_slug,
                $endpoint,
                $is_logged_in,
                $user_id,
                $serving_config['config']);

        if (!empty($category_id)) {
            $result .= sprintf(
                '<script>const CATEGORY_ID = "%1$d" </script>',
                $category_id
            );
        }

        $result .= '
            <div id="fast-simon-serp-app" 
                  style="display:block;
                  color: initial;
                  background: initial;
                  max-width: 100%" >
            </div>
            
            <script>
                 var script  = document.createElement("script");
                 script.src = ` https://static-grid.fastsimon.com/init.min.js`;
                 document.body.appendChild(script);
            </script>';

        return $result;
    }

    static function get_serving_config() {
        $serp_config = get_option('serp_config');
        $serp_config = json_decode($serp_config, true);

        $config = ($serp_config && $serp_config['config']) ?
            $serp_config['config'] : '';

        $serp_css = ($serp_config && $serp_config['serp_css']) ?
            $serp_config['serp_css'] : '';

        return array(
            'config' => $config,
            'serp_css' => $serp_css
        );
    }

    static function is_svelte_serp() {
        $serp_version = get_option('serp_version');
        $serving_config = get_option('serp_config');

        return ($serp_version &&
            $serp_version == ServingVersion::SVELTE &&
            $serving_config);
    }

    static function is_svelte_category() {
        $category_version = get_option('category_version');
        $serving_config = get_option('serp_config');

        return ($category_version &&
            $category_version == ServingVersion::SVELTE &&
            $serving_config);
    }

    static function get_serving_endpoint() {
        // TODO: load and update subscription of website
        $products_count = WCISPlugin::get_products_count();
        $is_cat_takeover = get_option('wcis_enable_rewrite_cats') ||
                           get_option('wcis_enable_rewrite_links');

        if ($products_count > 4500 /* || $subscription == 5*/) {
            return ServingEndpoint::ULTIMATE;
        }
        if ($products_count > 1000 || $is_cat_takeover) {
            return ServingEndpoint::PREMIUM;
        }
        return ServingEndpoint::DEFAULTT;
    }

    static function get_serving_page() {
        $is_svelte_serving = WCISServing::is_svelte_category();
        $location = dirname(__FILE__) . '/';
        $location .= $is_svelte_serving ? 'wcis_serving_page.php' : 'wcis_product_cat.php';

        return $location;
    }

    static public function set_serp_or_category_version(
            $context, $version = ServingVersion::SVELTE, $show_message = false) {
        if ($context != 'serp' && $context != 'category') { return; }

        $plugin = WCISPlugin::get_instance();

        $option_name = ($context == 'serp') ? 'serp_version' : 'category_version';
        $message = $option_name . ' was set to ';

        if ($version == ServingVersion::JQUERY_V1) {
            $plugin->isp_update_option($option_name, ServingVersion::JQUERY_V1);
            $message .= ServingVersion::JQUERY_V1;
        }
        elseif ($version == ServingVersion::JQUERY_V2) {
            $plugin->isp_update_option($option_name, ServingVersion::JQUERY_V2);
            $message .= ServingVersion::JQUERY_V2;
        }
        elseif ($version == ServingVersion::SVELTE) {
            $plugin->isp_update_option($option_name, ServingVersion::SVELTE);
            $message .= ServingVersion::SVELTE . '. ';
            if ($show_message) { echo $message; }
            self::update_serving_config($show_message);
        }

        if ($version != ServingVersion::SVELTE && $show_message) {
            echo $message;
            exit();
        }
    }

    public static function update_serving_config($show_message = false) {
        $url = 'https://dashboard.instantsearchplus.com/api/serving/magento_update_fields';
        $plugin = WCISPlugin::get_instance();
        $message = '';

        $args = array(
            'headers' => array(
                'Store-ID' => get_current_blog_id(),
                'UUID' => get_option('wcis_site_id')
            ),
            'timeout' => 15,
        );

        try {
            $resp = wp_remote_post($url, $args);
            if (is_wp_error($resp) || $resp['response']['code'] != 200) {
                $err_msg = "update_serving_config request failed";
                $plugin->send_error_report($err_msg);
                $message = 'error while sending a request to fetch config from fast simon';
            }
            else {

                $plugin->isp_update_option('serp_config', $resp['body']);
                $message = 'Serving config from fast simon were received and saved';
                //var_dump($resp);

            }

        }
        catch (Exception $e) {
            $message = 'Failed to send serving config fetch request to fast simon server';
        }

        if ($show_message) {
            echo $message;
            exit();
        }
    }

    public static function display_serving_config() {
        $serving_config = get_option('serp_config');

        if (empty($serving_config)) {
            exit();
        }
        else {
            $json_options = JSON_UNESCAPED_SLASHES;
            $config_pretty = json_encode($serving_config, $json_options);
            $config_pretty = stripslashes($config_pretty);

            exit($config_pretty);
        }
    }
}