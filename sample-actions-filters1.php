<?php

/**
 * @package ApmCom
 * @version 0.42
 */
/*
Plugin Name: ApmCom
Plugin URI: http://www.mediawiki.org/wiki/User:QuestPC
Description: ApmCom site customization code.
Author: Dmitriy Sintsov
Version: 0.42
Author URI: http://www.mediawiki.org/wiki/User:QuestPC
*/

require_once( dirname( __FILE__ ) . '/AbstractActionsFilters.php' );

/**
 * ApmCom filters and actions for current site.
 */

class ApmCom extends AbstractActionsFilters {

	const Version = '0.42';

	protected static $remove_menu_items = array( 5, 15 );

	protected static $taxonomies = array(
		'service' => array(
			'labels' => array(
				'name'                       => 'Типы услуг',
				'singular_name'              => 'Тип услуги',
				'search_items'               => 'Поиск типа услуг',
				'popular_items'              => 'Популярные типы услуг',
				'all_items'                  => 'Все типы услуг',
				'edit_item'                  => 'Редактировать тип услуг',
				'update_item'                => 'Обновить тип услуг',
				'add_new_item'               => 'Добавить новый тип услуг',
				'new_item_name'              => 'Название нового типа услуг',
				'separate_items_with_commas' => 'Перечислите типы услуг через запятую',
				'add_or_remove_items'        => 'Добавить или удалить типы услуг',
				'choose_from_most_used'      => 'Выбрать из наиболее представленных типов услуг',
				'not_found'                  => 'Тип услуг не найден.',
				'menu_name'                  => 'Типы услуг',
			),
			'rewrite'               => array( 'slug' => 'service' ),
		),
		'client' => array(
			'labels' => array(
				'name'                       => 'Клиенты',
				'singular_name'              => 'Клиент',
				'search_items'               => 'Поиск клиентов',
				'popular_items'              => 'Популярные клиенты',
				'all_items'                  => 'Все клиенты',
				'edit_item'                  => 'Редактировать клиента',
				'update_item'                => 'Обновить клиента',
				'add_new_item'               => 'Добавить нового клиента',
				'new_item_name'              => 'Имя нового клиента',
				'separate_items_with_commas' => 'Перечислите клиентов через запятую',
				'add_or_remove_items'        => 'Добавить или удалить клиента',
				'choose_from_most_used'      => 'Выбрать из наиболее представленных клиентов',
				'not_found'                  => 'Клиент не найден.',
				'menu_name'                  => 'Клиенты',
			),
			'rewrite'               => array( 'slug' => 'client' ),
		),
	);


	protected static $post_types = array(
		'case' => array(
			'labels' => array(
				'name'               => 'Кейсы',
				'singular_name'      => 'Кейс',
				'add_new_item'       => 'Добавить новый кейс',
				'edit_item'          => 'Редактировать кейс',
				'new_item'           => 'Новый кейс',
				'all_items'          => 'Все кейсы',
				'view_item'          => 'Просмотреть кейс',
				'search_items'       => 'Искать кейсы',
				'not_found'          => 'Кейсы не найдены',
				'not_found_in_trash' => 'Кейсы не найдены в мусорной корзине',
				'menu_name'          => 'Кейсы'
			),
			'description'        => 'Пример наших услуг',
			'rewrite'            => array( 'slug' => 'case' ),
			'taxonomies'         => array( 'service', 'client' ),
		),
	);


	protected static $basic_post_types_fields = array(
	);

	protected static $post_types_fields = array(
	);

        /**
         * Adds nextgen gallery shortcode to our custom post type.
         */
	public static function filter_the_content( $content = null ) {
		global $post;
		if ( $content === null ) {
			return '';
		}
		$our_post_types = array_keys( static::$post_types );
		if ( !in_array( $post->post_type, $our_post_types ) || !is_single() ) {
			# Unchanged.
			return $content;
		}
		# sdv_dbg('post',$post);
		if ( function_exists( 'nggShowGallery' ) ) {
			foreach ( get_field ( 'case-gallery' ) as $nextgen_gallery_id ) :
				if ( $nextgen_gallery_id['ngg_form'] == 'album' ) {
					$content .= '[ngg_images album_ids="' . $nextgen_gallery_id['ngg_id'] . '" display_type="photocrati-nextgen_basic_extended_album"]';
				} elseif ( $nextgen_gallery_id['ngg_form'] == 'gallery' ) {
					$content .= '[ngg_images gallery_ids="' . $nextgen_gallery_id['ngg_id'] . '" display_type="photocrati-nextgen_basic_thumbnails"]';
								}
			endforeach;
		}
		static::add_default_meta_fields( $post );
		return $content;
	}

	public static function filter_sanitize_file_name( $filename, $filename_raw ) {
		# sdv_dbg('filename',$filename);
		return $filename;
	}

	protected static function isGalleryFileUrl( $url ) {
		return strpos( $url, '/wp-content/gallery/' ) !== false;
	}

	protected static function urlencodeFileUrl( $url ) {
		# sdv_dbg('url',$url);
		$path_parts = explode( '/', $url );
		$last_path_part = array_pop( $path_parts );
		$query_parts = explode( '?', $last_path_part );
		$query_parts[0] = urlencode( $query_parts[0] );
		$last_path_part = implode( '?', $query_parts );
		array_push( $path_parts, $last_path_part );
		return htmlspecialchars( implode( '/', $path_parts ), ENT_COMPAT, 'UTF-8' );
	}

	/**
	 * @todo: Newest versions of NextGEN gallery should natively support non-western character sets in filenames.
	 * Check, whether this filter is still needed.
	 */
	public static function filter_clean_url( $good_protocol_url, $original_url, $_context ) {
		# sdv_dbg('good_protocol_url',$good_protocol_url);
		# sdv_dbg('original_url',$original_url);
		# sdv_dbg('_context',$_context);
		if ( static::isGalleryFileUrl( $original_url ) ) {
			return static::urlencodeFileUrl( $original_url );
		} else {
			return $good_protocol_url;
		}
	}

	public static function filter_attribute_escape( $safe_text, $text ) {
		# sdv_dbg('safe_text',$safe_text);
		# sdv_dbg('text',$text);
		if ( static::isGalleryFileUrl( $text ) ) {
			return static::urlencodeFileUrl( $text );
		} else {
			return $safe_text;
		}
	}

	protected static function find_M_Gallery_Display() {
		global $wp_filter, $wp_actions;
		# sdv_dbg('wp_filter',$wp_filter);
		# sdv_dbg('wp_actions',$wp_actions);
		foreach ( $wp_filter as $filter_name => $filtersForPriority ) {
			foreach ( $filtersForPriority as $priority => $filterList ) {
				foreach ( $filterList as $filterKey => $filterDef ) {
					# sdv_dbg('filterDef keys',array_keys($filterDef));
					# sdv_dbg('filterDef',$filterDef);
					if ( array_key_exists( 'function', $filterDef ) &&
						is_array( $filterDef['function'] ) && isset( $filterDef['function'][0] ) &&
						$filterDef['function'][0] instanceof M_Gallery_Display ) {
						return $filterDef['function'][0];
					}
				}
			}
		}
		throw new Exception( 'Unable to find NextGEN gallery instance object' );
	}

	protected static $ngg_mgd;
	protected static $ngg_sizeinfos = array(
		array(
			'quality'   => 100,
			'width'     => 195,
			'height'    => 160,
			'crop'      => true,
			'watermark' => false,
		),
		array(
			'quality'   => 100,
			'width'     => 260,
			'height'    => 160,
			'crop'      => true,
			'watermark' => false,
		),
		array(
			'quality'   => 100,
			'width'     => 260,
			'height'    => 320,
			'crop'      => true,
			'watermark' => false,
		),
		array(
			'quality'   => 100,
			'width'     => 780,
			'height'    => 480,
			'crop'      => false,
			'watermark' => false,
		),
	);

	public static function get_sizeinfo_key( array $sizeinfo ) {
		return 't' . $sizeinfo['width'] . 'x' . $sizeinfo['height'] . '_' . ($sizeinfo['crop'] ? 'c' : 'nc' );
	}


	public static function get_all_size_ngg_image_urls( $ngg_image ) {
		if ( !isset( static::$ngg_mgd ) ) {
			static::$ngg_mgd = static::find_M_Gallery_Display();
		}
		$mgd = static::$ngg_mgd;
		# sdv_dbg('mgd',$mgd);
		$settings = C_NextGen_Settings::get_instance();
		$imagegen = $mgd->get_registry()->get_utility('I_Dynamic_Thumbnails_Manager');
		$mapper   = $mgd->get_registry()->get_utility('I_Image_Mapper');
		$storage  = $mgd->object->get_registry()->get_utility('I_Gallery_Storage');
		# $image = $mapper->find_first();
		# sdv_dbg('imagegen',$imagegen);
		# sdv_dbg('mapper',$mapper);
		# sdv_dbg('storage',$storage);
		$url_sizes = array();
		foreach ( static::$ngg_sizeinfos as $sizeinfo ) {
			$size = $imagegen->get_size_name($sizeinfo);
			$url = $storage->get_image_url( $ngg_image, $size );
			if ( !is_string( $url ) ) {
				throw new Exception( 'Cannot resize NextGEN image id = ' . intval( $ngg_image->pid ) );
			}
			# Fix broken NextGEN cyrillic urls.
			$url = static::urlencodeFileUrl( $url );
			# Add url for current sizeinfo key.
			$url_sizes[static::get_sizeinfo_key( $sizeinfo )] = $url;
			// Commented out because I_Gallery_Storage::get_image_url() already calls next method when required
			// thumbnail size is not already available.
			// $storage->generate_image_size( $ngg_image, $size );
		}
		# sdv_dbg('url_sizes',$url_sizes);
		return $url_sizes;
	}

	public static function filter_ngg_image_object( $picture, $act_pid ) {
		# generate all image sizes for "case" custom post type
		static::get_all_size_ngg_image_urls( $picture );
		return $picture;
	}

	public static function action_init() {
		parent::action_init();
		require_once( WP_PLUGIN_DIR . '/apmcom/ApmComApi.php' );
		global $aca;
		if ( !isset( $aca ) ) {
			$aca = new ApmComApi();
		}
		/**
		 * Debug section. Do not uncomment in production.
		 */
		# $aca->getCachedOpcodes();
		# $aca->deleteCachedOpcodes( array( 'jazz_clients_list_raw', 'jazz_services_list_raw' ) );
		# $aca->getCachedOpcodes();
	}

	public static function action_admin_init() {
		parent::action_admin_init();
		$adminStylePath = plugins_url( 'admin', __FILE__ );
		wp_register_style( 'apmcom-admin', "{$adminStylePath}/admin.css", array(), self::Version );
	}

	public static function action_admin_head() {
		wp_enqueue_style( 'apmcom-admin' );
	}

	protected static $gettext_overrides = array(
		'default' => array(
			'Excerpt' => 'Текст превью',
			'Excerpts are optional hand-crafted summaries of your content that can be used in your theme. <a href="http://codex.wordpress.org/Excerpt" target="_blank">Learn more about manual excerpts.</a>' => 'Краткое содержание вашего текста (<a href="http://codex.wordpress.org/Excerpt" target="_blank">excerpt</a>), длиною не более чем 200 символов. <b>Необходимо</b> для отображения превью.',
		),
	);

	public static function action_wp_enqueue_scripts() {
		wp_register_script(
			'apmcom-google-analytics',
			plugins_url( 'client/ga.js' , __FILE__ ),
			false,
			self::Version,
			true
		);
		wp_enqueue_script( 'apmcom-google-analytics' );
	}

	protected static $flushCacheOpcodes = array(
		// @note: Currently posts are not cached in memory, however the flush won't harm
		// especially in case when caching 'ttl' might be introduced at later stage.
		'post' => array(
			'generic_posts_raw',
			'jazz_blog_header_gallery_raw',
			'jazz_blog_posts_raw',
		),
		'case' => array(
			'jazz_clients_list_raw',
			'jazz_services_list_raw',
			'generic_cases_raw',
			'jazz_random_cases_raw',
		),
		'client' => array(
			'jazz_clients_list_raw',
		),
		'service' => array(
			'jazz_services_list_raw',
		),
		'nextgen' => array(
			'jazz_clients_list_raw',
			'jazz_services_list_raw',
			'generic_cases_raw',
			'jazz_random_cases_raw',
		),
	);

	/**
	 * @todo: Clear selective cache entries via $post_id.
	 */
	protected static function flushCache( $slug ) {
		global $aca;
		if ( array_key_exists( $slug, static::$flushCacheOpcodes ) ) {
			sdv_dbg('slug',$slug);
			$aca->deleteCachedOpcodes( static::$flushCacheOpcodes[$slug] );
			sdv_dbg('flushed',static::$flushCacheOpcodes[$slug]);
		}
	}

	public static function action_edit_post( $post_id, $post ) {
		# sdv_dbg('post',$post);
		static::flushCache( $post->post_type );
	}

	public static function action_delete_post( $post_id ) {
		$query = new WP_Query( array( 'post__in' => $post_id ) );
		foreach ( $query->posts as $post ) {
			break;
		}
		# sdv_dbg('post',$post);
		static::flushCache( $post->post_type );
	}

	public static function action_create_term( $term_id, $tt_id, $taxonomy ) {
		# sdv_dbg('args',func_get_args());
		static::flushCache( $taxonomy );
	}

	public static function action_edit_terms( $term_id, $taxonomy ) {
		# sdv_dbg('args',func_get_args());
		static::flushCache( $taxonomy );
	}

	public static function action_delete_term( $term, $tt_id, $taxonomy, $deleted_term ) {
		# sdv_dbg('args',func_get_args());
		static::flushCache( $taxonomy );
	}

	public static function action_ngg_update_gallery( $gid, $post ) {
		static::flushCache( 'nextgen' );
	}

	public static function action_ngg_created_new_gallery( $gid ) {
		# sdv_dbg('nggcf_fields before',$_POST["conf"]['nggcf_fields']);
		if ( !array_key_exists( 'conf', $_POST ) ) {
			$_POST['conf'] = array();
		}
		if ( !array_key_exists( 'nggcf_fields', $_POST['conf'] ) ) {
			$_POST['conf']['nggcf_fields'] = array();
		}
		$iList = nggcf_get_field_list(NGGCF_IMAGES);
		# sdv_dbg('iList',$iList);
		foreach ( $iList as $fieldDef ) {
			$id = intval( $fieldDef->id );
			if ( !array_key_exists( $id, $_POST['conf']['nggcf_fields'] ) ) {
				$_POST['conf']['nggcf_fields'][$id] = $fieldDef->field_name;
			}
		}
		# sdv_dbg('nggcf_fields after',$_POST["conf"]['nggcf_fields']);
	}

	public static function init() {
		parent::init();
		static::add_hooks( array(
			# commented out, because actually NextGEN gallery does not call this filter.
			# array( 'filter', 'sanitize_file_name' ),
			# Add NextGEN gallery shortcode to post content.
			array( 'filter', 'the_content' ),
			# Next filters are used to fix improperly escaped cyrillic urls in NextGEN code.
			array( 'filter', 'clean_url', 10, 3 ),
			array( 'filter', 'attribute_escape', 10, 2 ),
			# Generate multiple size thumbnails for front-end ui.
			array( 'filter', 'ngg_image_object', 10, 2 ),
			# Hide unsued postboxes from back-end ui.
			array( 'action', 'admin_head' ),
			# Insert google analytics script to front-end ui.
			array( 'action', 'wp_enqueue_scripts' ),
			# Clear part of API cache when post is created / updated.
			array( 'action', 'edit_post', 10, 2 ),
			# Clear part of API cache when post is deleted.
			array( 'action', 'delete_post' ),
			# Clear part of API cache when taxonomy term is created.
			array( 'action', 'create_term', 10, 3 ),
			# Clear part of API cache when taxonomy term is edited.
			array( 'action', 'edit_terms', 10, 2 ),
			# Clear part of API cache when taxonomy term is deleted.
			array( 'action', 'delete_term', 10, 4 ),
			# Clear part of API cache when NextGEN gallery is updated.
			array( 'action', 'ngg_update_gallery', 10, 2 ),
			# Link newly created NextGEN gallery to it's image custom fields by default.
			array( 'action', 'ngg_created_new_gallery', 1 ),
		) );
		// add_theme_support( 'post-thumbnails' );
		// set_post_thumbnail_size( 780, 480, false );
		add_image_size( 'apmcom-blog-header', 65, 65, true );
		add_image_size( 'apmcom-blog-thumb', 140, 175, false );
		add_image_size( 'apmcom-popup', 780, 480, false );
	}

} /* end of ApmCom class */

ApmCom::init();

