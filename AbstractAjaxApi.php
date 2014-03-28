<?php

require_once( __DIR__ . '/SplClassLoader.php' );

class AbstractAjaxApi {

	protected static $orig_display_errors;

	protected $required_functions = array(
		'WordPress' => array(
			array( 'WP_Query', 'have_posts' ),
			'get_terms',
		),
	);

	# output buffering level
	protected $initial_ob_level;

	# Real / virtual request $_POST + $_GET variables.
	protected $requestVars;

	# caching of slow api requests
	# please define valid cache prefix string in your child class
	protected $cachePrefix;
	# instance of Jamm\Memory
	protected $mem;
	# debugging
	protected $disableMemCache = false;

	// API methods (opcodes) default arguments definitions.
	// 'varname' is $_POST+$_GET key or a virtual request key.
	protected $opcodes = array(
		# Get terms of specified taxonomy with custom fields attached to each term.
		'taxonomy_terms' => array(
			'ttl' => 0,
			'args' => array(
				// Scalar arg w/o default value.
				// Set varname 'taxonomy' to default value 'category' :
				// 'scalar' => array( 'taxonomy' => 'category' )
				0 => array(
					'scalar' => 'taxonomy',
				),
				// arg array with default values.
				1 => array(
					'default' => array(
						// 'varname' => 'defval'
						'orderby' => 'name',
						'order' => 'ASC',
						// intval 0 / 1 can be used for real web request.
						'hide_empty' => true,
						'parent' => 0,
						'hierarchical' => 0,
						'number' => '',
						'offset' => '',
						// use 'fields' => 'count' to return number of terms found.
						'fields' => 'all',
					),
				),
			),
		),
		# Get posts of specified custom post type with custom fields attached to each post.
		'taxonomy_post_terms' => array(
			'ttl' => 0,
			'args' => array(
				// arg array with default values.
				0 => array(
					'default' => array(
						// 'varname' => 'defval'
						'post_type' => null,
						// Can be array.
						'taxonomy' => null,
						'taxonomy_relation' => 'AND',
						'post_status' => 'publish',
						'paged' => 1,
						'posts_per_page' => 10,
						'nopaging' => 0,
						'order' => 'DESC',
						'orderby' => 'date',
						// @todo: add 'post__in' support.
						'name' => null,
					),
				),
			),
		),
	);

	static protected $wp_db_to_mysqli_db = array(
		'DB_NAME' => 'dbname',
		'DB_USER' => 'username',
		'DB_PASSWORD' => 'passwd',
	);

	function __construct() {
		if ( version_compare( PHP_VERSION, '5.3.3', '<' ) ) {
			# PHP 5.3.0 to 5.3.2 has some nasty bugs.
			throw new Exception( 'Minimal supported PHP version is 5.3.3' );
		}
		if ( !extension_loaded( 'apc' ) ) {
			throw new Exception(
				__CLASS__ .
				' currently requires php-apc. If you are running PHP 5.5, install redis, php-redis and switch to ' .
				'\Jamm\Memory\PhpRedisObject'
			);
		}
		# Jamm Memory lib loader.
		$jammMemoryLoader = new SplClassLoader('Jamm', __DIR__ . '/lib');
		$jammMemoryLoader->register();
		if ( !isset( $this->cachePrefix ) ) {
			throw new Exception( 'Please define ->cachePrefix in your child class.' );
		}
		$this->mem = $this->memFactory( 'MySQLObject' );
		// $this->mem = $this->memFactory( 'APCObject' );
	}

	protected function memFactory( $jammClassName ) {
		$fqnClassName = "\\Jamm\\Memory\\{$jammClassName}";
		$cb = array( $this, "memFactory_{$jammClassName}" );
		return is_callable( $cb ) ?
			call_user_func( $cb ) :
			new $fqnClassName( $this->cachePrefix );
	}

	protected function memFactory_MySQLObject() {
		$memArgs = array();
		foreach ( static::$wp_db_to_mysqli_db as $wp_const => $mysqli_key ) {
			if ( defined( $wp_const ) ) {
				$memArgs[$mysqli_key] = constant( $wp_const );
			}
		}
		if ( defined( 'DB_HOST' ) ) {
			$parts = explode( ':', DB_HOST, 2 );
			$memArgs['host'] = $parts[0];
			if ( isset( $parts[1] ) ) {
				$memArgs['port'] = $parts[1];
			}
		}
		if ( !isset( $memArgs['port'] ) ) {
			unset( $memArgs['port'] );
		}
		$memArgs['ID'] = $this->cachePrefix;
		return new \Jamm\Memory\MySQLObject( serialize( $memArgs ) );
	}

	/**
         * @return string
         *   path to wp-load.php file
         */
	public static function getWpLoad() {
		static::beforeWpLoad();
		$dirname = dirname( __FILE__ );
		while ( !($exists = @file_exists( "{$dirname}/wp-load.php" )) &&
				$dirname !== DIRECTORY_SEPARATOR ) {
			$dirname = dirname( $dirname );
		}
		if ( $exists ) {
			return "{$dirname}/wp-load.php";
		} else {
			throw new Exception( 'Cannot find ABSPATH'  );
		}
	}

	/**
         * Check availability of required callbacks (core, plugins dependencies).
         */
	public function init() {
		static::afterWpLoad();
		foreach ( $this->required_functions as $dependencyName => $callables ) {
			foreach ( $callables as $callable ) {
				if ( !is_callable( $callable ) ) {
					$e = new Exception( $dependencyName );
					$e->vars = array( 'callable' => $callable );
					throw $e;
				}
			}
		}
	}

	public function isIntNumeric( $val ) {
		return $val !== null && is_scalar( $val ) && preg_match( '/^-?\d{1,8}$/', $val ) === 1;
	}

	public function hasRequestVar( $name ) {
		return property_exists( $this->requestVars, $name );
	}

	public function getRequiredVar( $name ) {
		if ( !$this->hasRequestVar( $name ) ) {
			throw new Exception( "Missing request variable: '{$name}'" );
		}
		return $this->_getVar( $name );
	}

	/**
         * Request vars abstraction instead of direct access to $_GET / $_POST allows running from backend.
         */
	protected function _getVar( $name ) {
		$val = $this->requestVars->{$name};
		if ( $this->isIntNumeric( $val ) ) {
			$val = intval( $val );
		}
		return $val;
	}

	public function getOptionalVar( $name, $defaultVal = null ) {
		return $this->hasRequestVar( $name ) ?
			$this->_getVar( $name ) : $defaultVal;
	}

	/**
         * Execute api from backend.
         * @return mixed
         *   api result (usually nested structure) or instanceof Exception;
         */
	public function exec( $requestVars = null ) {
		if ( $requestVars !== null ) {
			# Backend init (virtual request).
			$this->requestVars = (object) $requestVars;
		} else {
			if ( !isset( $this->requestVars ) ) {
				# http init (http request).
				# Support both POST and GET.
				# POST has priority over GET, because POST requests usually are non-cached.
				$this->requestVars = (object) ($_POST + $_GET);
			}
		}

		$this->init();
		$opcode = $this->getRequiredVar( 'opcode' );
		if ( !is_scalar( $opcode ) || !array_key_exists( $opcode, $this->opcodes ) ) {
			$e = new Exception( 'Unknown API opcode' );
			$e->vars = array( 'opcode' => $opcode, 'valid_opcodes' => array_keys( $this->opcodes ) );
			throw $e;
		};
		$apiHandler = "api_{$opcode}";
		if ( !method_exists( $this, $apiHandler ) ) {
			throw new Exception( "Undefined opcode handler: $apiHandler" );
		}
		// Build opcode call arguments list.
		$args = array();
		$opcodeDef = &$this->opcodes[$opcode];
		// Iterate over definitions of arguments for selected opcode.
		$prevKey = -1;
		if ( is_string( $opcodeDef['args'] ) ) {
			if ( !array_key_exists( $opcodeDef['args'], $this->opcodes ) ) {
				$e = new Exception( 'Unknown original API opcode' );
				$e->vars = array( 'opcodeDef' => $opcodeDef, 'valid_opcodes' => array_keys( $this->opcodes ) );
				throw $e;
			}
			# "Clone" args of original opcode.
			$opcodeDef['args'] = $this->opcodes[$opcodeDef['args']]['args'];
		}
		# Build api method callback arguments.
		foreach ( $opcodeDef['args'] as $key => $argDefinition ) {
			if ( $key !== $prevKey + 1 ) {
				$e = new Exception( 'Opcode arg key must be consequtive integer' );
				$e->vars = array(
					'opcode' => $opcode,
					'defarg' => $argDefinition,
					'key' => $key,
				);
				throw $e;
			}
			if ( array_key_exists( 'scalar', $argDefinition ) ) {
				if ( is_array( $argDefinition['scalar'] ) ) {
					# Scalar argument with default value.
					foreach ( $argDefinition['scalar'] as $varname => $defaultVal ) {
						break;
					}
					$args[$key] = $this->getOptionalVar( $varname, $defaultVal );
				} else {
					# Scalar agrument w/o default value. 
					$args[$key] = $this->getRequiredVar( $argDefinition['scalar'] );
				}
			} elseif ( !array_key_exists( 'default', $argDefinition ) ) {
				$e = new Exception( 'Neither \'scalar\' nor \'default\' keys are specified in definition of argument.' );
				$e->vars = array( 'defarg' => $argDefinition );
				throw $e;
			} elseif ( !is_array( $argDefinition['default'] ) ) {
				$e = new Exception( '\'default\' key of definiion of argument must be an array.' );
				$e->vars = array( 'defarg' => $argDefinition );
				throw $e;
			} else {
				// Argument of associative array type with default values.
				$arg = array();
				foreach ( $argDefinition['default'] as $varname => $defaultVal ) {
					$arg[$varname] = $this->getOptionalVar( $varname, $defaultVal );
				}
				$args[$key] = $arg;
			}
			$prevKey = $key;
		}
		# Call api method with optional caching.
		sdv_dbg('apiHandler',$apiHandler);
		sdv_dbg('args',$args);
		# sdv_dbg('cacheKey',$cacheKey);
		$isCachable = array_key_exists( 'ttl', $opcodeDef ) &&
			$opcodeDef['ttl'] > 0 && !$this->disableMemCache;
		$cacheKey = serialize( array( $opcode, $args ) );
		$startedAt = microtime( true );
		$reason = '';
		if ( $isCachable ) {
			sdv_dbg('ttl',$opcodeDef['ttl']);
			$result = $this->mem->read( $cacheKey );
			$reason = 'cache hit';
			if ( $result === false ) {
				$result = call_user_func_array( array( $this, $apiHandler ), $args );
				$reason = 'cache miss';
				// @todo: use \Jamm\Memory tags.
				$this->mem->save( $cacheKey, $result, $opcodeDef['ttl'] );
			}
		} else {
			$result = call_user_func_array( array( $this, $apiHandler ), $args );
			$reason = 'non-cacheable';
			$this->mem->del( $cacheKey );
		}
		$lastRun = microtime( true ) - $startedAt;
		sdv_dbg("lastRun {$reason}",$lastRun);
		return $result;
	}

	/**
	 * @todo: use \Jamm\Memory tags.
	 */
	public function getCachedOpcodes() {
		$keys = array_map( 'unserialize', $this->mem->get_keys() );
		$opcodes = array();
		foreach ( $keys as $key ) {
			$opcodes[$key[0]] = 1;
		}
		sdv_dbg('opcodes',$opcodes);
		return $opcodes;
	}

	/**
	 * Selectively / completely clear parts of memory cache based on opcode.
	 * @param $opcodesToDel mixed
	 *   null:   delete all cached opcode results;
	 *   string: delete selected opcode results;
	 *   array:  delete multiple selected opcodes results;
	 * @todo: use \Jamm\Memory tags.
	 */
	public function deleteCachedOpcodes( $opcodesToDel = null ) {
		if ( is_string( $opcodesToDel ) ) {
			$opcodesToDel = array( $opcodesToDel => 1);
		} elseif ( is_array( $opcodesToDel ) ) {
			$opcodesToDel = array_flip( $opcodesToDel );
		}
		// API agrument keys.
		$keys = array_map( 'unserialize', $this->mem->get_keys() );
		// Filter out required memory cache keys.
		$memKeys = array();
		foreach ( $keys as $key ) {
			list( $opcode, $args ) = $key;
			if ( $opcodesToDel === null || array_key_exists( $opcode, $opcodesToDel ) ) {
				$memKeys[] = serialize( $key );
			}
		}
		// Purge (flush) collected memory cache keys.
		$this->mem->del( $memKeys );
	}

	/**
	 * Execute api for ajax client.
         * @return string
         *   ajax-encoded result, when there are errors, 'error' root key element is present;
	 */
	public function execAjax() {
		return $this->ajaxResponse( $this->exec() );
	}

	/**
         * Override this method in child class to process complex custom fields contaning wordpress objects.
         * E.g., remove unneeded info, add extra fields and so on.
         */
	protected function getComplexFields( $wp_obj ) {
		return get_fields( $wp_obj );
	}

	/**
         * All api method names are prefixed via 'api_'.
         * See protected ->opcodes property for their description.
         */
	protected function api_taxonomy_terms( $taxonomies, $args ) {
		$result = get_terms( $taxonomies, $args );
		if ( $result instanceof WP_Error ) {
			throw new Exception( $result->get_error_message() );
		} elseif ( is_scalar( $result ) ) {
			// 'fields' => 'count' query
			return intval( $result );
		}
		$terms = array();
		foreach ( $result as $term ) {
			$t = clone $term;
			$t->_api_meta = $this->getComplexFields( $t );
			$terms[] = $t;
		}
		return $terms;
	}

	protected function api_taxonomy_post_terms( $args ) {
		# Do not allow to expose drafts (unpublished posts).
		$args['post_status'] = 'publish';
		if ( $args['taxonomy'] !== null ) {
			$args['tax_query'] = array();
			if ( !is_array( $args['taxonomy'] ) ) {
				$args['taxonomy'] = array( $args['taxonomy'] );
			}
			foreach ( $args['taxonomy'] as $taxonomy_name ) {
				$args['tax_query'][] = array( 'taxonomy' => $taxonomy_name );
			}
			if ( count( $args['tax_query'] ) > 1 ) {
				$args['tax_query']['relation'] = $args['taxonomy_relation'];
			}
		}
		unset( $args['taxonomy'] );
		unset( $args['taxonomy_relation'] );
		if ( $args['name'] === null ) {
			unset( $args['name'] );
		}
		# sdv_dbg('args',$args);
		$query = new WP_Query( $args );
		$posts = array();
		foreach ( $query->posts as $post ) {
			$p = clone $post;
			$p->_api_meta = $this->getComplexFields( $p );
			$posts[] = $p;
		}
		# sdv_dbg('posts',$posts);
		return $posts;
	}

	public function obStart() {
		if ( isset( $this->initial_ob_level ) ) {
			throw new Exception( 'Nested obStart() calls are currently unsupported.' );
		}
		ob_start();
		$this->initial_ob_level = ob_get_level();
	}

	public function getExceptionOb( Exception $e, $start_ob_level ) {
		if ( !property_exists( $e, 'vars' ) ) {
			$e->vars = array();
		}
		if ( !is_array( $e->vars ) ) {
			$e->vars = array( 'vars' => $e->vars );
		}
		// Capture interrupted output buffer(s), if any.
		// Otherwise invalid AJAX might be returned when exception was thrown in the middle of output.
		while ( ob_get_level() > $start_ob_level ) {
			ob_end_flush();
		}
		$e->vars['ob'] = ob_get_clean();
		if ( $e->vars['ob'] === false ) {
			unset( $e->vars['ob'] );
		}
	}

	public function outputJsLog( Exception $e ) {
		if ( isset( $this->initial_ob_level ) ) {
			$this->getExceptionOb( $e, $this->initial_ob_level );
		}
		$log = array( json_encode( $e->getMessage() ) );
		if ( property_exists( $e, 'vars' ) ) {
			$log[] = json_encode( $e->vars, true );
		}
		$log = implode( "' + '\\n' + '", $log );
?>
<script>
        console.log('<?php echo $log ?>');
        alert('<?php echo $log ?>');
</script>
<?php
	}

	/**
         * Encodes valid backend api result OR Exception previosely thrown by api into ajax string.
         * @param $result mixed
         * @return
         *   ajax string; check for 'error' root key element in case of exception error;
         */
	public function ajaxResponse( $result ) {
		if ( $result instanceof Exception ) {
			$e = $result;
   			$result = array( 'error' => $e->getMessage() );
			if ( property_exists( $e, 'vars' ) ) {
				$result = array_merge( $result, $e->vars );
			}
		}
		$json_opts = JSON_NUMERIC_CHECK;
		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
			$json_opts |= JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
		}
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Content-Type: application/json' );
		if ( function_exists( 'sdv_dbg' ) ) {
			# sdv_dbg('result',$result);
		}
		die( json_encode( $result, $json_opts ) );
	}

	protected static function beforeWpLoad() {
		# Do not allow PHP errors to interfere AJAX output.
		static::$orig_display_errors = ini_get( 'display_errors' );
		ini_set( 'display_errors', 0 );
	}

	protected static function afterWpLoad() {
		# Restore original PHP error reporting before executing AJAX API.
		ini_set( 'display_errors', static::$orig_display_errors );
	}

} /* end of AbstractAjaxApi class */

/**
 * How to initialize and use _child_ class instance.
 * This should be run in global scope due to require_once().
 */

/**
 * Backend api.
 */
/*
try {
	$aca = new AbstractAjaxApiChild();
	// return php data
	$aca->exec( array( 'opcode' => 'my_opcode' );
} catch ( Exception $e ) {
	// check $e
}
*/

/**
 * Ajax api.
 */
/*
/*
define('WP_USE_THEMES', false);
require_once( __DIR__ . '/AbstractAjaxApi.php' );

try {
	require_once( AbstractAjaxApi::getWpLoad() );
	require_once( __DIR__ . '/AbstractAjaxApiChild.php' );
	$aca = new AbstractAjaxApiChild();
	$aca->execAjax();
} catch ( Exception $e ) {
	$aca->ajaxResponse( $e );
}
*/
