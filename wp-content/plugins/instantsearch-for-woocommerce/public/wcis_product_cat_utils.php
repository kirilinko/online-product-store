<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WCISCategory {

    static function wcis_get_fulltext_obj_script() {
        $uuid = get_option('wcis_site_id');
        $store_id = get_current_blog_id();

        return sprintf(
            '<script>
					var __isp_fulltext_search_obj = { uuid: "%1$s", store_id: "%2$s"}
				</script>',
            $uuid,
            $store_id
        );
    }

    static function wcis_get_category_str() {
        global $wp_query;

        if (!isset($wp_query->query['product_cat'])) {
            return '';
        }

        $cat_str = '';

        $full_cat_str = $wp_query->query['product_cat'];
        $full_cat_arr = explode('/', $full_cat_str);

        if (count($full_cat_arr) > 0) {
            $cat = get_term_by('slug', $full_cat_arr[count($full_cat_arr) - 1], 'product_cat');
            $cat_str = $cat->term_id;
        }

        return $cat_str;
    }

    static function wcis_get_serp_loading_page_script() {
        $uuid = get_option('wcis_site_id');
        $store_id = get_current_blog_id();
        $category = self::wcis_get_category_str();

        return sprintf(
            '<script data-no-optimize="1" ' .
                    'src="https://woo.instantsearchplus.com/js/search_result_loading_page.js?' .
                    'smart_navigation=1&' .
                    'isp_platform=woocommerce&' .
                    'UUID=%1$s&' .
                    'store_id=%2$s&' .
                    'category_id=%3$s" >' .
            '</script>',
            $uuid,
            $store_id,
            $category
        );
    }

    static public function is_category_page() {
        if (!is_product_category()) { return false; }

        $term = get_queried_object();
        $term_options = get_term_meta($term->term_id);
        $is_display_type_exist = array_key_exists('display_type', $term_options);

        if (!$is_display_type_exist) {
            return true;
        }
        else if ($term_options['display_type'][0] != 'subcategories') {
            return true;
        }

        return false;
    }

}