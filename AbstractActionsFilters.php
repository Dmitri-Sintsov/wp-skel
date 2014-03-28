<?php

if ( !function_exists( 'add_action' ) ) {
	die( 'It is not a valid entry point.' );
}

abstract class AbstractActionsFilters {

	/**
	 * Begin of configuration variables.
	 * Override these in non-abstract child.
	 */

	# In actual child define next line to disable themes menu in Dashboard.
	# protected static $remove_menu_items = array( 5, 15 );
	protected static $remove_menu_items = array();

	# A skeleton (prototype) for static::$taxonomies[] entry.
	protected static $basic_taxonomy = array(
		'labels' => array(
			'parent_item'                => null,
			'parent_item_colon'          => null,
		),
		'hierarchical'          => false,
		'public'                => true,
		'show_admin_column'     => true,
		'update_count_callback' => '_update_post_term_count',
		'query_var'             => true,
		'sort'                  => false,
		'rewrite'               => array( 'hierarchical' => false ),
	);

	# Override in child to add custom taxonomies.
	# Will take defaults from static::$basic_taxonomy.
	protected static $taxonomies = array();

	# A skeleton (prototype) for static::$post_types[] entry.
	protected static $basic_post_type = array(
		'labels' => array(
			'add_new'            => 'Добавить новый',
			'parent_item_colon'  => '',
		),
		'exclude_from_search'=> false,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_nav_menus'  => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'capability_type'    => 'page',
		'has_archive'        => false,
		'hierarchical'       => false,
		'map_meta_cap'       => true,
		'menu_position'      => null,
		'menu_icon'          => null,
		'rewrite'            => array( 'feeds' => true ),
		'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments' ),
		'taxonomies'         => array( 'category' ),
	);

	# Override in child to add custom post types.
	# Will take defaults from static::$basic_post_type.
	protected static $post_types = array();

	protected static $basic_post_types_fields = array();
	protected static $post_types_fields = array();

	# Override in child to change BUILT-IN (not custom) post type post labels, including menus.
	protected static $post_type_labels = array();

	# Override in child to change translation or translate untranslated messages.
	protected static $gettext_overrides = array();

	/**
	 * End of configuration variables.
	 */

	public static function action_init() {
		static::inherit_post_types();
		static::create_taxonomies();
		static::create_post_types();
	}

	protected static function inherit_post_types() {
		foreach ( array_keys( static::$post_types_fields ) as $post_type ) {
			static::$post_types_fields[$post_type] = array_merge(
				static::$post_types_fields[$post_type],
				static::$basic_post_types_fields
			);
		}
	}

	protected static function create_taxonomies() {
		foreach ( static::$taxonomies as $taxonomy_name => $taxonomy ) {
			$args = array_merge_recursive( $taxonomy, static::$basic_taxonomy );
			# sdv_dbg('args',$args);
			$result = register_taxonomy( $taxonomy_name, null, $args );
			/*
			if ( is_wp_error( $result ) ) {
				sdv_dbg('error',$result);
			} else {
				sdv_dbg('result',$result);
			}
			*/
		}
	}

	protected static function create_post_types() {
		foreach ( static::$post_types as $post_type_name => $post_type ) {
			$args = array_merge_recursive( $post_type, static::$basic_post_type );
			# sdv_dbg('args',$args);
			$result = register_post_type( $post_type_name, $args );
			/*
			if ( is_wp_error( $result ) ) {
				sdv_dbg('error',$result);
			} else {
				sdv_dbg('result',$result);
			}
			*/
		}
	}

	protected static function add_default_meta_fields( $post ) {
		if ( !array_key_exists( $post->post_type, static::$post_types_fields ) ) {
			return;
		}
		$custom_fields = get_post_custom( $post->ID );
		# sdv_dbg('custom_fields',$custom_fields);
		$unset_custom_fields = array_diff_key( array_flip( static::$post_types_fields[$post->post_type] ), $custom_fields );
		# sdv_dbg('unset default custom fields',$unset_custom_fields);
		foreach ( $unset_custom_fields as $custom_field_name => $idx ) {
			add_post_meta( $post->ID, $custom_field_name, '', true );
		}
	}

	/**
	 * Add custom meta fields when new post is created in dashboard.
	 * @todo: Seems to doesn't call properly. Figure out why.
	 */
	public static function filter_default_content( $post_content, $post ) {
		# sdv_dbg('post',$post);
		static::add_default_meta_fields( $post );
		return $post_content;
	}

	/**
	 * Used by kids theme in custom post type single pages.
	 */
	protected static function output_post_custom_fields( $post_type, $post_id ) {
		$output = '';
		if ( !array_key_exists( $post_type, static::$post_types_fields ) ) {
			wp_die( __METHOD__ . ' unknown post_type=' . htmlspecialchars( $post_type ) );
		}
		$post_custom_keys = array_flip( get_post_custom_keys( $post_id ) );
		# sdv_dbg('post_custom_keys',$post_custom_keys);
		foreach ( static::$post_types_fields[$post_type] as $field_name ) {
			if ( array_key_exists( $field_name, $post_custom_keys ) ) {
				$values = get_post_custom_values( $field_name, $post_id );
				foreach ( $values as $key => $value ) {
					if ( trim( $value ) === '' ) {
						unset( $values[$key] );
					}
				}
				if ( count( $values ) > 0 ) {
					$values = implode( ', ', $values );
					$output .= "<li><span class='post-meta-key'>{$field_name}:</span> {$values}</li>\n";
				}
			}
		}
		return $output;
	}

	/**
	 * Remove custom post type slug from taxonomy permalink, so there will be no non-sense
	 * /avtokresla/proizvoditel/aprica permalinks with incorrect post types included
	 * (alas, WordPress incorrectly creates them).
	 *
	 * Filters the output of static::output_post_taxonomy().
	 */
	public static function filter_term_link( $termlink, $term, $taxonomy_name  ) {
		if ( array_key_exists( $taxonomy_name, static::$taxonomies ) ) {
			# sdv_dbg('taxonomy_name',$taxonomy_name);
			$taxonomy_slug = static::$taxonomies[$taxonomy_name]['rewrite']['slug'];
			$parsed_url = (object) parse_url( $termlink );
			if ( property_exists( $parsed_url, 'path' ) ) {
				# sdv_dbg('source_url',$parsed_url);
				# Ugly hack, but better than nothing.
				# Does not use http_build_url() because it is not universally available.
				$taxonomy_slug_pos = strpos( $parsed_url->path, "/{$taxonomy_slug}/" );
				if ( $taxonomy_slug_pos !== false ) {
					$parsed_url->path = substr( $parsed_url->path, $taxonomy_slug_pos );
				}
				# sdv_dbg('rewritten_url',$parsed_url);
				$termlink = "{$parsed_url->scheme}://{$parsed_url->host}{$parsed_url->path}";
			}
		}
		return $termlink;
	}

	/**
	 * Currently is unused (deprecated).
	 */
	protected static function output_post_taxonomy_with_post_type( $post_type, $post_id ) {
		$output = '';
		if ( !array_key_exists( $post_type, static::$post_types ) ) {
			wp_die( __METHOD__ . ' unknown post_type=' . htmlspecialchars( $post_type ) );
		}
		foreach ( static::$post_types[$post_type]['taxonomies'] as $taxonomy_name ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy_name );
			if ( is_wp_error( $terms ) ) {
				wp_die( __METHOD__ . ' error getting terms for taxonomy ' . htmlspecialchars( $taxonomy_name ) );
			}
			# sdv_dbg('post_id,taxonomy_name,terms',array($post_id,taxonomy_name,$terms));
			$term_list = get_the_term_list(
				$post_id,
				$taxonomy_name,
				static::$taxonomies[$taxonomy_name]['labels']['singular_name'],
				', ',
				''
			);
			# sdv_dbg('term_list1',$term_list);
			$link_pos = strpos( $term_list, '<a ' );
			if ( $link_pos !== false ) {
				# Rewrite text nodes inside taxonomy link text with span for custom theme css.
				if ( $link_pos === 0 ) {
					$term_list = (array) $term_list;
				} else {
					$term_list = str_split( $term_list, $link_pos );
				}
				$term_list[0] = "<span class='post-meta-key'>{$term_list[0]}</span>";
				$term_list = implode( '', $term_list );
			}
			$output .= "<li>{$term_list}</li>\n";
			# sdv_dbg('term_list2',$term_list);
		}
		return $output;
	}

	/**
	 * Used by kids theme in custom post type single pages.
	 */
	protected static function output_post_taxonomy( $post_type, $post_id ) {
		$output = '';
		if ( !array_key_exists( $post_type, static::$post_types ) ) {
			wp_die( __METHOD__ . ' unknown post_type=' . htmlspecialchars( $post_type ) );
		}
		# get post type taxonomies (keys will be taxonomy names)
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );
		foreach ( static::$post_types[$post_type]['taxonomies'] as $taxonomy_name ) {
			if ( !array_key_exists( $taxonomy_name, $taxonomies ) ) {
				wp_die( __METHOD__ . ' unknown taxonomy name', htmlspecialchars( $taxonomy_name ) );
			}
			$taxonomy = $taxonomies[$taxonomy_name];
			$terms = get_the_terms( $post_id, $taxonomy_name );
			if ( !empty( $terms ) ) {
				# sdv_dbg('taxonomy',$taxonomy);
				$output .= "<span class='post-meta-key'>" .
					htmlspecialchars( static::$taxonomies[$taxonomy_name]['labels']['singular_name'] ) .
					"</span>";
				foreach ( $terms as $term ) {
					$output .= "<li><a href='" .
						get_term_link( $term->slug, $taxonomy_name ) . 
						"'>{$term->name}</a></li>\n";
				}
			}
		}
		return $output;
	}

	public static function output_post_custom_info( $post_type, $post_id ) {
		if ( ctype_digit( $post_id ) ) {
			$post_id = intval( $post_id );
		}
		if ( !is_int( $post_id ) ) {
			# sdv_dbg('post_id',$post_id);
			wp_die( __METHOD__ . ' invalid post_id=' . htmlspecialchars( $post_id ) );
		}
		# Combine taxonomies and custom fields together.
		$output = static::output_post_taxonomy( $post_type, $post_id ) .
			static::output_post_custom_fields( $post_type, $post_id );
		if ( $output !== '' ) {
			$output = "<ul class='post-meta'>{$output}</ul>";
		}
		return $output;
	}

	/**
	 * Add our custom post types to category archive queries.
	 */
	public static function action_pre_get_posts( $query = null ) {
		if ( $query === null ) {
			return;
		}
		# sdv_dbg('query category_name',$query->get( 'category_name' ));
		/*
		if ( !$query->get( 'category_name' ) === 'category-kolyaski' ) {
			return;
		}
		if ( is_preview() || is_admin() || is_singular() || is_404() ) {
			return;
		}
		$curr_post_type = get_query_var( 'post_type' );
		if ( empty( $curr_post_type ) ) {
			$post_types = array_keys( static::$post_types );
			$post_types[] = 'post';
			$query->set( 'post_type', $post_types );
			# sdv_dbg('post_type',$post_types);
		} */
	}

	# Stores original post type labels for static::action_admin_menu().
	protected static $post_type_labels_original = array();

	/**
	 * Store original built-in post type labels to be used in static::action_admin_menu().
	 * Set new labels for built-in post type(s) when available from static::$post_type_labels.
	 */
	public static function action_registered_post_type( $post_type, $args ) {
		global $wp_post_types;
		if ( !array_key_exists( $post_type, static::$post_type_labels ) ) {
			return;
		}
		$labels = $wp_post_types[$post_type]->labels;
		static::$post_type_labels_original[$post_type] = (array) $labels;
		# sdv_dbg('$post_type_labels_original',static::$post_type_labels_original[$post_type]);
		foreach ( $labels as $key => $value ) {
			if ( array_key_exists( $key, static::$post_type_labels[$post_type] ) ) {
				$labels->$key = static::$post_type_labels[$post_type][$key];
			}
		}
	}

	/**
	 * Optionally hides themes menu.
	 * Optionally alters names / titles of built-in post type.
	 */
	public static function action_admin_menu() {
		global $menu;
		global $submenu;
		global $wp_post_types;
		# Hide themes menu.
		foreach ( static::$remove_menu_items as $menu_idx ) {
			unset( $submenu['themes.php'][$menu_idx] );
		}
		# Alter names / titles of 'post' built-in post type.
		# sdv_dbg('menu',$menu);
		# sdv_dbg('submenu',$submenu);
		foreach ( $menu as &$menuDef ) {
			foreach ( static::$post_type_labels_original as $post_type => $origLabels ) {
				if ( $menuDef[2] === 'edit.php' &&
						$menuDef[0] === $origLabels['menu_name'] ) {
					# sdv_dbg('menuDef',$menuDef);
					$menuDef[0] = static::$post_type_labels[$post_type]['menu_name'];
				}
			}
		}
		foreach ( $submenu as $action => &$submenuDef ) {
			if ( strpos( $action, 'edit.php' ) === 0 ) {
				foreach ( $submenuDef as $key => $val ) {
					break;
				}
				foreach ( static::$post_type_labels_original as $post_type => $origLabels ) {
					if ( $submenuDef[$key][0] === $origLabels['all_items'] ) {
						# sdv_dbg('submenuDef',$submenuDef[$key]);
						$submenuDef[$key][0] = static::$post_type_labels[$post_type]['all_items'];
					}
				}
			}
		}
	}

	public static function filter_gettext( $translated_text, $untranslated_text, $domain ) {
		# sdv_dbg('untranslated_text',$untranslated_text);
		# sdv_dbg('domain',$domain);
		return (array_key_exists( $domain, static::$gettext_overrides ) &&
				array_key_exists( $untranslated_text, static::$gettext_overrides[$domain] )) ?
			static::$gettext_overrides[$domain][$untranslated_text] : $translated_text;
	}

	public static function add_hooks( array $hooksList ) {
		static $registered_filters = array();
		static $registered_actions = array();
		foreach ( $hooksList as $args ) {
			$hookType = $args[0];
			$hookName = $args[1];
			$priority = isset( $args[2] ) ? $args[2] : 10;
			$argsCount = isset( $args[3] ) ? $args[3] : 1;
			$callback = array( get_called_class(), "{$hookType}_{$hookName}" );
			if ( !is_callable( $callback ) ) {
				$e = new Exception( 'Uncallable hook' );
				$e->vars = array( 'callback' => $callback );
				throw $e;
			}
			if ( $hookType === 'filter' ) {
				if ( array_key_exists( $hookName, $registered_filters ) ) {
					$e = new Exception( 'Filter already was registered. Use OOP inheritance instead.' );
					$e->vars = array(
						'orig' => $registered_filters[$hookName],
						'new' => $callback,
					);
					throw $e;
				}
				add_filter( $hookName, $callback, $priority, $argsCount );
				$registered_filters[$hookName] = $callback;
			} elseif ( $hookType === 'action' ) {
				if ( array_key_exists( $hookName, $registered_actions ) ) {
					$e = new Exception( 'Action already was registered. Use OOP inheritance instead.' );
					$e->vars = array(
						'orig' => $registered_actions[$hookName],
						'new' => $callback,
					);
					throw $e;
				}
				add_action(  $hookName, $callback, $priority, $argsCount );
				$registered_actions[$hookName] = $callback;
			} else {
				$e = new Exception( 'Unknown hook type' );
				$e->vars = array( 'hookType' => $hookType );
				throw $e;
			}
		}
	}

	public static function init() {
		static::add_hooks( array(
			array( 'action', 'init' ),
			# Alter dashboard menu (remove items or change built-in post type menu labels).
			array( 'action', 'admin_menu' ),
			# Custom post type links correction.
			array( 'filter', 'term_link', 10, 3 ),
			// Next filter is commented out, because it does not work.
			// array( 'filter', 'default_content', 10, 2 ),
			array( 'action', 'pre_get_posts' ),
			# Alter names / titles of 'post' built-in post type.
			array( 'action', 'registered_post_type', 2, 2 ),
			# Alter some misleading translations.
			array( 'filter', 'gettext', 20, 3 ),
		) );
	}

} /* end of AbstractActionsFilters class */

