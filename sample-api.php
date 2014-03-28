<?php

require_once( __DIR__ . '/AbstractAjaxApi.php' );
require_once( ABSPATH . '/templates/templates_fn.php' );
require_once( ABSPATH . '/templates/templates_apm.php' );

class ApmComApi extends AbstractAjaxApi {

	const MINUTE = 60;
	const HOUR = 3600;
	const DAY = 86400;
	const postPhotosMaxCount = 2;
	protected $cachePrefix = 'apmcom_api';

	const JAZZ_CLIENTS_PER_PAGE = 8;
	const JAZZ_RANDOM_CASE_SLOTS = 7;
	const JAZZ_RANDOM_POSTS = 6;
	const JAZZ_BLOG_POSTS_PER_PAGE = 6;

	function __construct() {
		$this->required_functions = array_merge_recursive(
			$this->required_functions,
			array(
				'Advanced Custom Fields' => array(
					'get_fields',
				),
				'NextGEN Gallery' => array(
					array( 'nggdb', 'find_gallery' ),
					array( 'nggdb', 'get_gallery' ),
				),
				'NextGEN Custom Fields' => array(
					'nggcf_hold_field_values',
				),
			)
		);
		# sdv_dbg('opcodes before',$this->opcodes);
		/**
		 * We are using non-zero 'ttl' caching only for slow raw queries.
		 * php template view generation is fast enough (templates can be changed real-time and cache is less saturated).
		 */
		$this->opcodes = array_merge_recursive(
			$this->opcodes,
			array(
				// get list of clients and all cases for each of client.
				// cases are received in compact format (no post body aka content).
				'jazz_clients_list_raw' => array(
					'ttl' => 15 * self::DAY,
					'args' => array(
						0 => array(
							'default' => array(
								'hide_empty' => false,
								# pagination
								'paged' => 1,
								'posts_per_page' => self::JAZZ_CLIENTS_PER_PAGE,
							),
						),
					),
				),
			),
			array(
				// render list of clients and all cases for each of client.
				// cases are received in compact format (no post body aka content).
				'jazz_clients_list_view' => array(
					// php template rendering is fast enough: no caching
					'ttl' => 0,
					'args' => 'jazz_clients_list_raw',
				),
			),
			array(
				// get list of services and all cases for each of service.
				// cases are received in compact format (no post body aka content).
				'jazz_services_list_raw' => array(
					'ttl' => 15 * self::DAY,
					'args' => array(
						0 => array(
							'default' => array(
								'hide_empty' => false,
								# no pagination
							),
						),
					),
				),
			),
			array(
				// render list of services and all cases for each of service.
				// cases are received in compact format (no post body aka content).
				'jazz_services_list_view' => array(
					// php template rendering is fast enough: no caching
					'ttl' => 0,
					'args' => 'jazz_services_list_raw',
				),
			),
			array(
				// get the set of case popups and their respective galleries / carousels
				'generic_cases_raw' => array(
					// cache results for specified amount of seconds
					'ttl' => 30 * self::DAY,
					'args' => array(
						0 => array(
							// Comma-separated list of case ids. No default value.
							'scalar' => 'case_ids',
						),
					),
				),
			),
			array(
				// render the set of case popups (full case views) and their respective galleries / carousels
				'generic_cases_view' => array(
					// php template rendering is fast enough: no caching
					'ttl' => 0,
					'args' => 'generic_cases_raw',
				),
			),
			array(
				// get random cases for random case portfolio
				'jazz_random_cases_raw' => array(
					// cache results for specified amount of seconds
					'ttl' => 1 * self::DAY,
					'args' => array(
						0 => array(
							'scalar' => array( 'cases_count' => self::JAZZ_RANDOM_CASE_SLOTS ),
						),
					),
				),
			),
			array(
				// render random cases for random case portfolio
				'jazz_random_cases_view' => array(
					// php template rendering is fast enough: no caching
					'ttl' => 0,
					'args' => 'jazz_random_cases_raw',
				),
			),
			array(
				// get template arg of blog post popups (full post views) and their respective galleries / carousels
				'generic_posts_raw' => array(
					'ttl' => 30 * self::DAY,
					'args' => array(
						0 => array(
							// Comma-separated list of post ids. No default value.
							'scalar' => 'post_ids',
						),
					),
				),
			),
			array(
				// get template arg and render blog post popups (full post views) and their respective galleries / carousels
				'generic_posts_view' => array(
					'ttl' => 0,
					'args' => 'generic_posts_raw',
				),
			),
			array(
				// get template arg of random posts for jazz blog header gallery
				'jazz_blog_header_gallery_raw' => array(
					'ttl' => 1 * self::DAY,
					'args' => array(
						0 => array(
							'scalar' => array( 'posts_count' => self::JAZZ_RANDOM_POSTS ),
						),
					),
				),
			),
			array(
				// get template arg and render random posts for jazz blog header gallery
				'jazz_blog_header_gallery_view' => array(
					'ttl' => 0,
					'args' => 'jazz_blog_header_gallery_raw',
				),
			),
			array(
				// get template arg of blog page with it's posts
				'jazz_blog_posts_raw' => array(
					'ttl' => 30 * self::DAY,
					'args' => array(
						0 => array(
							'default' => array(
								'post_status' => 'publish',
								'paged' => 1,
								'posts_per_page' => self::JAZZ_BLOG_POSTS_PER_PAGE,
								'nopaging' => 0,
								'order' => 'DESC',
								'orderby' => 'date',
							),
						),
					),
				),
			),
			array(
				// get template arg and render blog page with it's posts
				'jazz_blog_posts_view' => array(
					'ttl' => 0,
					'args' => 'jazz_blog_posts_raw',
				),
			),
			array(
				// render service page with it's terms
				'classic_services_view' => array(
					'ttl' => 0,
					'args' => array(),
				),
			),
			array(
				// render blog page with it's posts
				'classic_blog_posts_view' => array(
					'ttl' => 0,
					'args' => 'jazz_blog_posts_raw',
				),
			),
			array(
				// render list of terms for portfolio page
				'classic_portfolio_terms_view' => array(
					'ttl' => 0,
					'args' => array(
						0 => array(
							'scalar' => 'taxonomy',
						),
					),
				),
			),
			array(
				// get template arg of sliding gallery of images for portfolio
				'classic_portfolio_slider_raw' => array(
					'ttl' => 30 * self::DAY,
					'args' => array(
						0 => array(
							// return all cases (of all taxonomies) by default
							'scalar' => array( 'taxonomy' => 0 )
						),
						1 => array(
							// return all cases(of all taxonomy terms) by default
							'scalar' => array( 'term_id' => 0 )
						)
					),
				),
			),
			array(
				// render sliding gallery of images for portfolio
				'classic_portfolio_slider_view' => array(
					'ttl' => 0,
					'args' => 'classic_portfolio_slider_raw',
				)
			)
		);
		# sdv_dbg('opcodes after',$this->opcodes);	
		parent::__construct();
	}

	protected function stripUnusedNggPicInfo( array &$picInfo ) {
		if ( !array_key_exists( 'meta_data', $picInfo ) ) {
			return;
		}
		$metaData = &$picInfo['meta_data'];
		foreach ( $metaData as $key => $val ) {
			# We do not need 'ngg0dyn*' keys in API result, because these do not contain thumbnail image URLs,
			# while we have populated '_api_sizes' which has all thumbnail image URLs.
			if ( strpos( $key, 'ngg0dyn' ) === 0 ) {
				unset( $metaData[$key] ); 
			}
		}
	}

	/**
         * Reduce amount of junk in NGG image object. Otherwise, raw API result will be too huge.
         * Also adds NextGEN custom fields into image object.
         * @param $picture original, real ngg image object instance large stdClass.
         * @return stdClass
         *   object with less fields;
         *     ->_api_fields contaning NextGEN custom fields (when available);
         *     ->_api_sizes containing full set of url's for each image sizes of current image;
         */
	protected function getNggImageInfo( $picture ) {
		global $nggcf_values;
		if ( !property_exists( $picture, '_ngiw' ) ) {
			$e = new Exception( 'Cannot find ->ngiw image wrapper of current image.' );
			$e->vars = array(
				'href' => $picture->get_href_link(),
				'pid' => $picture->pid,
			);
			throw $e;
		}
		$ngiw = $picture->_ngiw;
		if ( property_exists( $ngiw, '_cache' ) ) {
			# Already an array, no need to clone.
			$picInfo = $ngiw->_cache;
		} elseif ( property_exists( $ngiw, '_orig_image' ) ) {
			$picInfo = (array) $ngiw->_orig_image;
		} else {
			$e = new Exception( 'NextGEN gallery image wrapper has neither cached nor original image available.' );
			$e->vars = array(
				'href' => $picture->get_href_link(),
				'pid' => $picture->pid,
			);
			throw $e;
		}
		$pid = $picInfo['pid'];
		nggcf_hold_field_values( $pid );
		$picInfo['_api_fields'] = $nggcf_values[$pid];
		$picInfo['_api_sizes'] = ApmCom::get_all_size_ngg_image_urls( $picture );
		$this->stripUnusedNggPicInfo( $picInfo );
		return (object) $picInfo;
	}

	/**
	 * @note: Due to slowness of NextGEN gallery please call as less as posible also consider caching.
	 */
	protected function getNggGalleryPictures( $gallery_id, nggdb $nggdb ) {
		global $ngg;
		$gallery = $nggdb->find_gallery( $gallery_id );
		# sdv_dbg('gallery',$gallery);
		# Get max. 17 of pictures of current gallery starting from zero.
		$pictureList = $nggdb->get_gallery(
			$gallery_id, $ngg->options['galSort'], $ngg->options['galSortDir'], false, 17, 0
		);
		# sdv_dbg('pictureList',$pictureList);
		$api_pictures = array();
		$firstElem = true;
		foreach ( $pictureList as $pid => $picture ) {
			$picInfo = $this->getNggImageInfo( $picture );
			# sdv_dbg('picInfo',$picInfo);
			if ( $firstElem ) {
				# Use first picture available as featured image (last resort).
				$gallery->_api_featured_image = $picInfo;
				$firstElem = false;
			} elseif ( intval( $picInfo->pid ) === intval( $picInfo->previewpic ) ) {
				# Use correct featured image when available (Dashboard / Manage Galleries / Preview image).
				$gallery->_api_featured_image = $picInfo;
			}
			$api_pictures[intval( $pid )] = $picInfo;
		}
		$gallery->_api_pictures = $api_pictures;
		return $gallery;
	}

	/**
         * Process ACF get_fields() result from parents to detect NextGEN gallery custom field, then
         * extract useful info from it and write back to the field.
	 * @note: Due to slowness of NextGEN gallery please call as less as posible also consider caching.
         */
	protected function getComplexFields( $wp_obj ) {
		$fields = parent::getComplexFields( $wp_obj );
		$nggdb = new nggdb();
		foreach ( $fields as $fieldKey => $field ) {
			if ( is_array( $field ) ) {
				# Check whether that repetitive field is a NextGEN gallery field.
				# We do not support NextGEN albums yet.
				foreach ( $field as $fieldElem ) {
					if ( is_array( $fieldElem ) &&
						array_intersect( array( 'ngg_id', 'ngg_form' ), array_keys( $fieldElem ) ) &&
							$fieldElem['ngg_form'] === 'gallery' ) {
						# Save gallery with it's pictures (and pictures custom fields) into current WordPress object's field.
						$fields[$fieldKey] = $this->getNggGalleryPictures( $fieldElem['ngg_id'], $nggdb );
						# We do not support multiple galleries per field yet.
						break;
					}
				}
			}
		}
		return $fields;
	}

	/**
         * Return cases view (list of cases for templates) for term object specified.
         * @param $wp_term
         *   wordpress term object (instance of 'client' / 'service');
	 * @modifies $case_ids array
	 *   keys: id's of returned cases (unique, can be accumulated on consequtive calls);
         * @return array
         *   cases view (0..n) for selected term;
         */
	protected function getCasesViewByTerm( $wp_term, array &$case_ids ) {
		# sdv_dbg('wp_term',$wp_term);
		$args = array(
			'posts_per_page' => -1,
			'post_type' => 'case',
			'tax_query' => array(
				array(
					'taxonomy' => $wp_term->taxonomy,
					'field' => 'id',
					'terms' => $wp_term->term_id,
				),
			),
		);
		# sdv_dbg('args',$args);
		$wp_cases = new WP_Query( $args );
		# sdv_dbg('wp_cases',$wp_cases->posts);
		$cases = array();
		foreach ( $wp_cases->posts as $wp_case ) {
			$case = array(
				'uniq' => "{$wp_case->post_type}_{$wp_case->ID}",
			);
			$case_ids[$wp_case->ID] = true;
			if ( count( $wp_cases->posts ) === 1 ) {
				$wp_case_fields = $this->getComplexFields( $wp_case );
				# sdv_dbg('wp_case_fields',$wp_case_fields);
				if ( array_key_exists( 'case-gallery', $wp_case_fields ) &&
						property_exists( $wp_case_fields['case-gallery'], '_api_featured_image' ) ) {
					$case['featuredImage'] = $wp_case_fields['case-gallery']->_api_featured_image->_api_sizes;
				}
			}
			$case['title'] = trim( $wp_case->post_title );
			$case['excerpt'] = trim( $wp_case->post_excerpt );
			$cases[] = $case;
		};
		return $cases;
	}

	protected function api_jazz_services_list_raw( $args ) {
		$args['opcode'] = 'taxonomy_terms';
		$args['taxonomy'] = 'service';
		$args['posts_per_page'] = -1;
		$wp_services = $this->exec( $args );
		# sdv_dbg('wp_services',$wp_services);
		/* Convert $wp_services to template argument $services */
		$services = array();
		$case_ids = array();
		foreach ( $wp_services as $wp_service ) {
			$service = array(
				'uniq' => "{$wp_service->taxonomy}_{$wp_service->term_id}",
				'title' => trim( $wp_service->name ),
				'content' => trim( $wp_service->description ),
				'bgrColor' => $wp_service->_api_meta['service-bgr-color'],
				'gradColor1' => $wp_service->_api_meta['service-grad-color1'],
				'gradColor2' => $wp_service->_api_meta['service-grad-color2'],
				'cases' => $this->getCasesViewByTerm( $wp_service, $case_ids ),
			);
			$services[] = $service;
		}
		# sdv_dbg('services',$services);
		$result = array(
			'services' => $services,
			'case_ids' => implode( ',', array_keys( $case_ids ) ),
		);
		return $result;
	}

	protected function api_jazz_services_list_view( $args ) {
		$args['opcode'] = 'jazz_services_list_raw';
		// Get cached result.
		$result = $this->exec( $args );
		/* Render html using $services. */
		ob_start();
		jazz_portfolio__content__item_service( a2o( $result['services'] ), $result['case_ids'] );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	protected function api_jazz_clients_list_raw( $args ) {
		/**
		 * Translate fake WP_Query-like $args into 'taxonomy_terms' $taxonomy_terms_args get_terms(),
		 * to make 'jazz_clients_list_raw' call orthogonal to 'jazz_blog_posts_raw' api call.
		 *
		 * That is required because both 'jazz_clients_list_view' and 'jazz_blog_posts_view' api calls
		 * are used by client-side AJAX pagination code.
		 */
		$total_clients = $this->exec( array(
			'opcode' => 'taxonomy_terms',
			'taxonomy' => 'client',
			'hide_empty' => $args['hide_empty'],
			'fields' => 'count',
		) );
		$taxonomy_terms_args = array(
			'opcode' => 'taxonomy_terms',
			'taxonomy' => 'client',
			'hide_empty' => $args['hide_empty'],
			// Translate 'paged' and 'posts_per_page' into 'offset' and 'number'.
			'number' => $args['posts_per_page'],
			'offset' => ( $args['paged'] - 1 ) * $args['posts_per_page'],
		);
		$wp_clients = $this->exec( $taxonomy_terms_args );
		# sdv_dbg('wp_clients',$wp_clients);
		/* Convert $wp_clients to template argument $clients */
		$clients = array();
		$case_ids = array();
		foreach ( $wp_clients as $wp_client ) {
			$client = array(
				'uniq' => "{$wp_client->taxonomy}_{$wp_client->term_id}",
				'logo' => $wp_client->_api_meta['client-logo']['sizes']['apmcom-popup'],
				'bgrColor' => $wp_client->_api_meta['client-bgr-color'],
				'gradColor1' => $wp_client->_api_meta['client-grad-color1'],
				'gradColor2' => $wp_client->_api_meta['client-grad-color2'],
				'cases' => $this->getCasesViewByTerm( $wp_client, $case_ids ),
			);
			$clients[] = $client;
		}
		$result = array(
			'total_clients' => $total_clients,
			'clients' => $clients,
			'case_ids' => implode( ',', array_keys( $case_ids ) ),
			'total_pages' => intval( ceil( $total_clients / $taxonomy_terms_args['number'] ) ),
		);
		$result['total_pages'] = intval( ceil( $result['total_clients'] / $taxonomy_terms_args['number'] ) );
		return $result;
	}

	protected function api_jazz_clients_list_view( $args ) {
		$args['opcode'] = 'jazz_clients_list_raw';
		// Get cached result.
		$result = $this->exec( $args );
		/* Render html using $clients. */
		ob_start();
		jazz_clients__list__item( a2o( $result['clients'] ), $result['case_ids'] );
		// current page class is "pagination__item__link pagination__item__link_current" (generated via template call)
		pagination( 'jazz', 'jazz-clients__pagination', $args['paged'], $result['total_pages'] );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	/**
	 * Converts wordpress post object of case type into full case view template argument.
	 * @param $wp_case
	 *   WP_Post->post_type === 'case';
	 * @return array
	 *   view template argument;
	 */
	protected function getCaseView( $wp_case ) {
		# sdv_dbg('wp_case',$wp_case);
		$wp_case_fields = $this->getComplexFields( $wp_case );
		# sdv_dbg('wp_case_fields',$wp_case_fields);
		/* Convert $wp_case and $wp_case_fields to template argument $case */
		$case = array(
			'uniq' => "{$wp_case->post_type}_{$wp_case->ID}",
			'title' => trim( $wp_case->post_title ),
			'title_of_title' => trim( $wp_case_fields['case-title-of-title'] ),
			'bubble_title' => trim( $wp_case_fields['case-bubble-title'] ),
			'excerpt' => trim( $wp_case->post_excerpt ),
			'content' => trim( $wp_case->post_content ),
			'bgrColor' => $wp_case_fields['case-bgr-color'],
			'gradColor1' => $wp_case_fields['case-grad-color1'],
			'gradColor2' => $wp_case_fields['case-grad-color2'],
		);
		if ( !array_key_exists( 'case-gallery', $wp_case_fields ) ) {
			$e = new Exception( 'Post of type case have no NextGEN gallery attached' );
			$e->vars = $case;
			throw $e;
		}
		$case['gallery_name'] = 'nextgen_' . $wp_case_fields['case-gallery']->gid;
		if ( property_exists( $wp_case_fields['case-gallery'], '_api_featured_image' ) ) {
			$case['featuredImage'] = $wp_case_fields['case-gallery']->_api_featured_image->_api_sizes;
		}
		$images = array();
		foreach ( $wp_case_fields['case-gallery']->_api_pictures as $picInfo ) {
			$image = $picInfo->_api_sizes;
			$image['desc'] = ($picInfo->alttext === $picInfo->image_slug) ? '' : $picInfo->alttext;
			if ( property_exists( $picInfo, 'ngg_custom_fields' ) && is_array( $picInfo->ngg_custom_fields ) ) {
				foreach ( $picInfo->ngg_custom_fields as $key => $field ) {
					if ( array_key_exists( $key, $image ) ) {
						$e = new Exception( 'ngg_custom_fields key conflict' );
						$e->vars = array( 'image' => $image, 'ngg_custom_fields' => $picInfo->ngg_custom_fields );
						throw $e;
					}
					$image[$key] = $field;
				}
				$image = array_merge( $image, $picInfo->ngg_custom_fields );
			}
			$images[] = $image;
		}
		$case['images'] = $images;
		return $case;
	}

	/**
         * Get list of cases (->post_type === 'case') by their post_ids.
         * @param $case_ids_str string
         *   comma-separated list of case ids;
         * @return array
         *   api result (cases view as template argument);
         */
	protected function api_generic_cases_raw( $case_ids_str ) {
		$case_ids = array_map( 'intval', explode( ',', $case_ids_str ) );
		$cases = array_flip( $case_ids );
		# sdv_dbg('case_ids',$case_ids);
		# sdv_dbg('cases',$cases);
		$args = array(
			'post_type' => 'case',
			'post__in' => $case_ids,
		);
		$wp_cases = new WP_Query( $args );
		# sdv_dbg('wp_cases',$wp_cases);
		foreach ( $wp_cases->posts as $wp_case ) {
			$cases[intval( $wp_case->ID )] = $this->getCaseView( $wp_case );
		}
		foreach ( $cases as $case_id => $case ) {
			if ( !is_array( $case ) ) {
				$e = new Exception( 'Cannot find one or more cases' );
				$e->vars = $cases;
				throw $e;
			}
		}
		$result = array( 'cases' => $cases );
		return $result;
	}

	/**
         * Render list of cases (->post_type === 'case') by their post_ids.
         * @param $case_ids_str string
         *   comma-separated list of case ids;
         * @return array
         *   api result (cases view both as template argument and as html);
         */
	protected function api_generic_cases_view( $case_ids_str ) {
		// Get cached result.
		$result = $this->exec( array(
			'opcode' => 'generic_cases_raw',
			'case_ids' => $case_ids_str
		) );
		/* Render html using $result['cases'] */
		ob_start();
		foreach ( $result['cases'] as $case ) {
			apm_case( a2o( $case ) );
		}
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	protected function api_jazz_random_cases_raw( $cases_count ) {
		$args = array(
			'post_type' => 'case',
			'orderby' => 'rand',
			'posts_per_page' => $cases_count,
		);
		$wp_cases = new WP_Query( $args );
		$cases = array();
		$case_ids = array();
		foreach ( $wp_cases->posts as $wp_case ) {
			$case_ids[intval( $wp_case->ID )] = true;
			$cases[] = $this->getCaseView( $wp_case );
		}
		$result = array(
			'cases' => $cases,
			'case_ids' => implode( ',', array_keys( $case_ids ) ),
		);
		return $result;
	}

	protected function api_jazz_random_cases_view( $max_cases_count ) {
		// Get cached result.
		$result = $this->exec( array(
			'opcode' => 'jazz_random_cases_raw',
			'cases_count' => $max_cases_count,
		) );
		/* Render html using $cases */
		ob_start();
		jazz_chaos__slots( $max_cases_count, a2o( $result['cases'] ) );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	/**
	 * Converts wordpress post object of post type into full case view template argument.
	 * @param $wp_post
	 *   WP_Post->post_type === 'post';
	 * @return array
	 *   view template argument;
	 */
	protected function getPostView( $wp_post ) {
		# sdv_dbg('wp_post',$wp_post);
		$wp_post_fields = $this->getComplexFields( $wp_post );
		# sdv_dbg('wp_post_fields',$wp_post_fields);
		/* Convert $wp_post and $wp_post_fields to template argument $post */
		$post = array(
			'uniq' => "{$wp_post->post_type}_{$wp_post->ID}",
			'date' => $wp_post->post_date,
			'title' => trim( $wp_post->post_title ),
			'excerpt' => trim( $wp_post->post_excerpt ),
			'content' => trim( $wp_post->post_content ),
			'bgrColor' => $wp_post_fields['blog-bgr-color'],
			'gradColor1' => $wp_post_fields['blog-grad-color1'],
			'gradColor2' => $wp_post_fields['blog-grad-color2'],
		);
		$images = array();
		for ( $photoIdx = 1; $photoIdx <= self::postPhotosMaxCount; $photoIdx++ ) {
			$key = sprintf( "%02d", $photoIdx );
			if ( array_key_exists( "blog-thumb-{$key}", $wp_post_fields ) &&
					array_key_exists( "blog-photo-{$key}", $wp_post_fields ) ) {
				$thumb = $wp_post_fields["blog-thumb-{$key}"];
				$large = $wp_post_fields["blog-photo-{$key}"];
				if ( is_array( $thumb ) && is_array( $large ) ) {
					$images[] = array(
						'desc' => $large['description'],
						't65x65_c' => $large['sizes']['apmcom-blog-header'],
						't140x175_nc' => $thumb['sizes']['apmcom-blog-thumb'],
						't780x480_nc' => $large['sizes']['apmcom-popup'],
					);
				}
			}
		}
		if ( count( $images ) < 1 ) {
			$e = new Exception( 'Each post should have at least one pair of thumbnail and large image attached.' );
			$e->vars = array( 'post' => $post );
			throw $e;
		}
		$post['images'] = $images;
		# sdv_dbg('post',$post);
		return $post;
	}

	protected function api_jazz_blog_header_gallery_raw( $posts_count ) {
		$args = array(
			'post_type' => 'post',
			'orderby' => 'rand',
			'posts_per_page' => $posts_count,
		);
		$wp_posts = new WP_Query( $args );
		$posts = array();
		$post_ids = array();
		foreach ( $wp_posts->posts as $wp_post ) {
			$post_ids[intval( $wp_post->ID )] = true;
			$posts[] = $this->getPostView( $wp_post );
		}
		$result = array(
			'posts' => $posts,
			'post_ids' => implode( ',', array_keys( $post_ids ) ),
		);
		return $result;
	}

	protected function api_jazz_blog_header_gallery_view( $posts_count ) {
		$result = $this->exec( array(
			'opcode' => 'jazz_blog_header_gallery_raw',
			'posts_count' => $posts_count,
		) );
		/* Render html using $posts */
		ob_start();
		jazz_blog__header_gallery( a2o( $result['posts'] ) );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	/**
         * Get list of blog posts (->post_type === 'post') by their post_ids.
         * @param $post_ids_str string
         *   comma-separated list of post ids;
         * @return array
         *   api result (posts view both as template argument and as html);
         */
	protected function api_generic_posts_raw( $post_ids_str ) {
		$post_ids = array_map( 'intval', explode( ',', $post_ids_str ) );
		$posts = array_flip( $post_ids );
		# sdv_dbg('post_ids',$post_ids);
		# sdv_dbg('posts',$posts);
		$args = array(
			'post_type' => 'post',
			'post__in' => $post_ids,
		);
		$wp_posts = new WP_Query( $args );
		foreach ( $wp_posts->posts as $wp_post ) {
			$posts[intval( $wp_post->ID )] = $this->getPostView( $wp_post );
		}
		foreach ( $posts as $post_id => $post ) {
			if ( !is_array( $post ) ) {
				$e = new Exception( 'Cannot find one or more posts' );
				$e->vars = $posts;
				throw $e;
			}
		}
		return array(
			'posts' => $posts
		);
	}

	/**
         * Get list of blog posts (->post_type === 'post') by their post_ids.
         * @param $post_ids_str string
         *   comma-separated list of post ids;
         * @return array
         *   api result (posts view both as template argument and as html);
         */
	protected function api_generic_posts_view( $post_ids_str ) {
		$result = $this->exec( array(
			'opcode' => 'generic_posts_raw',
			'post_ids' => $post_ids_str
		) );
		/* Render html using $post */
		ob_start();
		foreach ( $result['posts'] as $post ) {
			blog__article( a2o( $post ) );
		}
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	/**
	 * Get the page of blog (template arg of compact view of posts).
	 */
	protected function api_jazz_blog_posts_raw( $args ) {
		$wp_posts = new WP_Query( $args );
		# sdv_dbg('wp_posts',$wp_posts);
		$posts = array();
		$post_ids = array();
		foreach ( $wp_posts->posts as $wp_post ) {
			$post_ids[intval( $wp_post->ID )] = true;
			$posts[] = $this->getPostView( $wp_post );
		}
		$result = array(
			'posts' => $posts,
			'post_ids' => implode( ',', array_keys( $post_ids ) ),
			'blog_num_pages' => intval( $wp_posts->max_num_pages ),
		);
		if ( $result['blog_num_pages'] < 1 ) {
			$e = new Exception( 'Blog cannot have zero posts' );
			$e->vars = $args;
			throw $e;
		}
		return $result;
	}

	/**
	 * Get the page of blog (template arg and rendered html of compact view of posts).
	 */
	protected function api_jazz_blog_posts_view( $args ) {
		$args['opcode'] = 'jazz_blog_posts_raw';
		$result = $this->exec( $args );
		/* Render html using $posts */
		$a2o_posts = a2o( $result['posts'] );
		ob_start();
		jazz_blog__feed( $a2o_posts, $result['post_ids'] );
		pagination( 'jazz', 'jazz-blog__pagination', $args['paged'], $result['blog_num_pages'] );
		blog__style( $a2o_posts );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	/**********************************
	 * Begin of 'classic' skin views. *
	 **********************************/

	protected function api_classic_services_view() {
		$services = $this->exec( array(
			'opcode' => 'taxonomy_terms',
			'taxonomy' => 'service',
			'hide_empty' => false,
		) );
		# sdv_dbg('services',$services);
		/* Render html using $services */
		ob_start();
		classic_services( $services );
		/* end of html rendering */
		$result = array( 'html' => ob_get_clean() );
		return $result;
	}

	/**
	 * Get the page of blog (template arg and rendered html of compact view of posts).
	 */
	protected function api_classic_blog_posts_view( $args ) {
		$args['opcode'] = 'jazz_blog_posts_raw';
		$result = $this->exec( $args );
		/* Render html using $posts */
		$a2o_posts = a2o( $result['posts'] );
		ob_start();
		classic_blog__feed( $a2o_posts, $result['post_ids'] );
		pagination( 'classic', 'classic-blog__pagination', $args['paged'], $result['blog_num_pages'] );
		blog__style( $a2o_posts );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	protected function api_classic_portfolio_terms_view( $taxonomy ) {
		$terms = $this->exec( array(
			'opcode' => 'taxonomy_terms',
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
			'posts_per_page' => -1,
		) );
		# sdv_dbg('terms',$terms);
		/* Render html using $terms */
		ob_start();
		classic_portfolio_terms_list( a2o( $terms ) );
		/* end of html rendering */
		$result = array( 'html' => ob_get_clean() );
		return $result;
	}

	protected function api_classic_portfolio_slider_raw( $taxonomy, $term_id ) {
		// Get all cases by default.
		$args = array(
			'post_type' => 'case',
			'posts_per_page' => -1,
		);
		$tax_query0 = array();
		if ( strval( $taxonomy ) !== '0' ) {
			// Get only cases related to selected taxonomy.
			$tax_query0['taxonomy'] = $taxonomy;
		}
		if ( strval( $term_id ) !== '0' ) {
			// Get only cases related to selected taxonomy term slug.
			$tax_query0['field'] = 'id';
			$tax_query0['terms'] = intval( $term_id );
		}
		if ( count( $tax_query0 ) > 0 ) {
			$args['tax_query'] = array( $tax_query0 );
		}
		# sdv_dbg('args',$args);
		$wp_cases = new WP_Query( $args );
		# sdv_dbg('wp_cases',$wp_cases);
		$cases = array();
		$case_ids = array();
		foreach ( $wp_cases->posts as $wp_case ) {
			$cases[] = $this->getCaseView( $wp_case );
			$case_ids[intval( $wp_case->ID )] = true;
		}
		$result = array(
			'cases' => $cases,
			'case_ids' => implode( ',', array_keys( $case_ids ) ),
		);
		return $result;
	}

	protected function api_classic_portfolio_slider_view( $taxonomy, $term_id ) {
		$result = $this->exec( array(
			'opcode' => 'classic_portfolio_slider_raw',
			'taxonomy' => $taxonomy,
			'term_id' => $term_id,
		) );
		# sdv_dbg('result',$result);
		/* Render html using $result['cases'] */
		ob_start();
		classic_portfolio_slider( a2o( $result['cases'] ), $result['case_ids'] );
		/* end of html rendering */
		$result['html'] = ob_get_clean();
		return $result;
	}

	public function multiSpaceToNBSP( $s ) {
		return preg_replace_callback(
			'/\x20{2,}/',
			function( $matches ) {
				return str_replace( ' ', '&nbsp;', $matches[0] );
			},
			$s
		);
	}

} /* end of ApmComApi class */

