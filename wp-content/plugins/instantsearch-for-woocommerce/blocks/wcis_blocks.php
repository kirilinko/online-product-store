<?php

defined( 'ABSPATH' ) || exit;

$fast_simon_blocks = new FastSimonBlocks();

if (get_option('wcis_enable_rewrite_cats')) {
    $category_block = new FastSimonCategoryBlock("category");
}

class FastSimonBlocks
{

	public function __construct()
	{
		add_filter('block_categories_all', array( $this, 'block_categories'), 10, 2 );
	}

	public function block_categories($categories, $post = null)
	{

		global $fs_text_domain;

		return array_merge(
			$categories,
			array(
				array(
					'slug' => 'fast-simon',
					'title' => __('Fast Simon', $fs_text_domain),
					//'icon' => 'search',
				),
			)
		);
	}
}

abstract class FastSimonBlock {

	private $name;

	public function __construct($block_name) {
		$this->name = $block_name;

		add_action('init', array( $this, 'register_block') );
	}

	public function register_block() {

		if (!function_exists('register_block_type')) {
			return;
		}

		wp_register_script(
			'fs-editor-script-' . $this->name,
			plugins_url($this->name . '/build/index.js', __FILE__),
			array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor', "wp-data"),
			filemtime(plugin_dir_path(__FILE__) . $this->name . '/build/index.js')        // set version as file last modified time
		);

		wp_register_style(
			'fs-editor-styles-' . $this->name,
			plugins_url($this->name . '/build/editor.css', __FILE__),
			array('wp-edit-blocks'),
			filemtime(plugin_dir_path(__FILE__) . $this->name . '/build/editor.css')
		);

		wp_register_style(
			'fs-front-end-styles-' . $this->name,
			plugins_url($this->name . '/build/style.css', __FILE__),
			array(),
			filemtime(plugin_dir_path(__FILE__) . $this->name . '/build/style.css')
		);

		//register dynamic block.
		register_block_type('fast-simon/' . $this->name, array(
			'editor_script' => 'fs-editor-script-' . $this->name,
			'editor_style' => 'fs-editor-styles-' . $this->name,
			'style' => 'fs-front-end-styles-' . $this->name,
			'render_callback' => array( $this, 'block_render_callback')
		));

	}

	/**
	 * Render the saved output from the category block.
	 *
	 * $attributes - array - Block attributes.
	 * $content - Block inner content.
	 * @param array|WP_Block $attributes Block attributes, or an instance of a WP_Block. Defaults to an empty array.
	 * @param string         $content    Block content. Default empty string.
	 */
	abstract public function block_render_callback($attributes= [], $content = '');

    static protected function get_block_classes( $attributes ) {
        $align = isset($attributes['align']) ? $attributes['align'] : 'wide';
        $classes = 'align' . $align . ' ';

        if ( isset($attributes['className']) ) {
            $classes .= $attributes['className'];
        }

        return $classes;
    }
}

class FastSimonCategoryBlock extends FastSimonBlock {

	public function block_render_callback($attributes = [], $content = '')
	{
        // if (!get_option('wcis_enable_rewrite_cats')) {
        //    return '<div>
        //                 <p>Fast Simon Collections is disabled.<br/>
        //                    Please enable Smart Collections in the dashboard in the Collections tab.
        //                 </p>
        //            </div>';
        //        }

	    global $wp_query;
		if (!$wp_query || !$wp_query->query) {
			return;
		}

        $is_svelte_serving = WCISServing::is_svelte_category();
        $block_classes = self::get_block_classes($attributes);

        if ($is_svelte_serving) {
            return self::get_svelte_category_serving($block_classes);
        }
        else {
            return self::get_jquery_category_serving($block_classes);
        }
	}

	private static function get_jquery_category_serving($block_classes)
    {
        $fulltext_script = WCISCategory::wcis_get_fulltext_obj_script();
        $loading_page_script = WCISCategory::wcis_get_serp_loading_page_script();

        return
            sprintf(
                '<div class="fast-simon-block fast-simon-category %1$s">
                    %2$s
					%3$s
				</div>',
                $block_classes,
                $fulltext_script,
                $loading_page_script
            );
    }

    private static function get_svelte_category_serving($block_classes) {
        $wcis_serving = WCISServing::get_svelte_serving();

	    return
            sprintf(
                '<div class="fast-simon-block fast-simon-category %1$s">
                    %2$s
				</div>',
                $block_classes,
                $wcis_serving
            );
    }
}
