<?php
defined('ABSPATH') || exit;

if ( defined('MWP_OBJECT_CACHE_DISABLED') && MWP_OBJECT_CACHE_DISABLED ) {
	return;
}
if ( getenv('MWP_OBJECT_CACHE_EXPERIMENT') !== 'true' ) {
	return;
}

if (defined('WP_CLI') && WP_CLI) {
	return;
}

$is_nocache = (bool)filter_input(INPUT_GET, 'nocache');
$is_sso = false;
$gd_command = filter_input(INPUT_GET, 'GD_COMMAND');
if ( !empty($gd_command) && $gd_command == 'SSO_LOGIN' ) {
	$is_sso = true;
}
// Ignore nocache on SSO login
if ( $is_nocache && !$is_sso ) {
	return;
}


function gd_cache_wrapper( Closure $cb, string $method ) {

	$report = function ( $data ) {

		error_log( substr( sprintf('Object cache error: %s', serialize( $data )),0, 2048));

		if ( defined('MWP_OBJECT_CACHE_LOG') ) {
			file_put_contents(
				MWP_OBJECT_CACHE_LOG,
				serialize( $data ),
				FILE_APPEND
			);
		}
	};

	try {
		$result = $cb();

		if (
			( is_string( $result ) && ( $result === '+OK' || $result === '+QUEUED' ) ) ||
			( is_array( $result ) && ( in_array( '+OK', $result, true ) || array_key_exists( '+OK', $result ) ) )
		) {
			$report( [
				'method'    => $method,
				'result'    => $result,
				'backtrace' => debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 16 ),
			] );

			return in_array( $method, [ 'mget', 'exec' ] )
				? []
				: false;
		}
	} catch ( Throwable $th ) {
		$report( [
			'error'     => $th->getMessage(),
			'type'      => get_class( $th ),
			'method'    => $method,
			'backtrace' => debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 16 ),
		] );
	}

	return $result ?? false;
}

(function ($config) {
    if (defined('WP_REDIS_CONFIG') || empty($config)) {
        return;
    }

    $config = json_decode((string) $config, true, 3);
    $error = json_last_error();

    if ($error !== JSON_ERROR_NONE || ! is_array($config)) {
        error_log(sprintf(
            'objectcache.warning: Unable to decode `OBJECTCACHE_CONFIG` environment variable (%s)',
            json_last_error_msg()
        ));
    }

    $config['tracer'] = 'gd_cache_wrapper';

	if ( defined( 'MWP_OBJECT_CACHE_NON_PERSISTENT_GROUPS' ) && is_array( MWP_OBJECT_CACHE_NON_PERSISTENT_GROUPS ) ) {
		$config['non_persistent_groups'] = MWP_OBJECT_CACHE_NON_PERSISTENT_GROUPS;
	}

    define('WP_REDIS_CONFIG', $config);
})(getenv('OBJECTCACHE_CONFIG'));

include WP_CONTENT_DIR . '/mu-plugins/gd-system-plugin/object-cache/object-cache-pro.php';

