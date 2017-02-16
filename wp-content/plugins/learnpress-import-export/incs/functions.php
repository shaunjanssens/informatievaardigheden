<?php
function lpie_admin_view( $name, $args = '' ) {
	if ( !preg_match( '~.php$~', $name ) ) {
		$name .= '.php';
	}
	if ( is_array( $args ) ) {
		extract( $args );
	}
	include LP_IMPORT_EXPORT_PATH . "/views/{$name}";
}

function lpie_get_export_source() {
	$source = array(
		'learnpress' => 'LearnPress'
	);
	return apply_filters( 'lpie_export_source', $source );
}

function lpie_get_exporter( $name ) {
	$provider = apply_filters( 'lpie_export_provider_class', 'LPIE_Export_' . $name, $name );
	if ( !class_exists( $provider ) ) {
		return $provider;
	}
	return new $provider();
}

function lpie_root_path( $a = 'basedir' ) {
	$upload_dir = wp_upload_dir();
	return $upload_dir[$a];
}

function lpie_import_path( $root = true ) {
	return $root ? lpie_root_path() . '/learnpress/import' : 'learnpress/import';
}

function lpie_export_path( $root = true ) {
	return $root ? lpie_root_path() . '/learnpress/export' : 'learnpress/export';
}

function lpie_root_url( $a = 'baseurl' ) {
	$upload_dir = wp_upload_dir();
	return $upload_dir[$a];
}

function lpie_filesystem() {
	if ( !function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}
	global $wp_filesystem;
	WP_Filesystem();
	return $wp_filesystem;
}

function lpie_mkdir( $dir ) {

	if ( $filesystem = lpie_filesystem() ) {
		if ( !$filesystem->is_dir( lpie_root_path() . '/' . $dir ) ) {
			$folders = explode( '/', $dir );
			$return  = '';
			for ( $i = 0, $n = sizeof( $folders ); $i < $n; $i ++ ) {
				$subdir    = join( '/', array_slice( $folders, 0, $i + 1 ) );
				$make_path = lpie_root_path() . '/' . $subdir;
				if ( $filesystem->mkdir( $make_path, true ) ) {
					$return = $subdir;
					@$filesystem->chmod( $make_path, 0755 );
				} else {
					//echo 'can not create dir [' . $make_path . "]\n";
				}
			}
			return $return;
		} else {
			return $dir;
		}
	} else {
		echo 'error with WP_Filesystem';
	}
	return $dir;
}

function lpie_put_contents( $file, $contents ) {
	if ( $filesystem = lpie_filesystem() ) {
		$file_dir = dirname( $file );
		lpie_mkdir( $file_dir );
		$filesystem->put_contents( lpie_root_path() . '/' . $file, $contents );
	} else {
	}
}

function lpie_get_contents( $file ) {
	if ( $filesystem = lpie_filesystem() ) {
		return $filesystem->get_contents( lpie_root_path() . '/' . $file );
	}
}

function lpie_delete_file( $file ) {
	if ( $filesystem = lpie_filesystem() ) {
		//$filesystem->delete( lpie_root_path() . '/' . $file );
		unlink( lpie_root_path() . '/' . $file );
	}
}

function lpie_get_export_files() {
	$files = array();
	if ( $filesystem = lpie_filesystem() ) {
		$list = $filesystem->dirlist( lpie_root_path() . '/learnpress/export' );
		if ( $list ) foreach ( $list as $file ) {
			if ( !preg_match( '!\.xml$!', $file['name'] ) ) {
				continue;
			}
			$files[] = $file;
		}
		usort( $files, '_lpie_sort_files' );
	} else {

	}
	return $files;
}

function lpie_get_import_files() {
	$files = array();
	if ( $filesystem = lpie_filesystem() ) {
		$list = $filesystem->dirlist( lpie_root_path() . '/learnpress/import' );
		if ( $list ) foreach ( $list as $file ) {
			if ( !preg_match( '!\.xml$!', $file['name'] ) ) {
				continue;
			}
			$files[] = $file;
		}
		usort( $files, '_lpie_sort_files' );

	} else {
		_e( 'FileSystem error!', 'learnpress-import-export' );
	}
	return $files;
}

function lpie_get_url( $file ) {
	return lpie_root_path( 'baseurl' ) . '/' . $file;
}

function lpie_export_header( $filename ) {
	header( 'Content-Description: File Transfer' );
	header( 'Content-Disposition: attachment; filename=' . $filename );
	header( 'Content-Type: text/xml; charset=' . get_option( 'blog_charset' ), true );
}

function lpie_get_current_tab() {
	$current_tab = !empty( $_REQUEST['tab'] ) ? $_REQUEST['tab'] : 'export-course';
	return $current_tab;
}

function lpie_get_import_instructors( $file ) {
	$xml_file = lpie_root_path() . '/learnpress/' . $file;
	if ( !file_exists( $xml_file ) ) {
		throw new Exception( sprintf( __( 'The file %s doesn\'t exists', 'learnpress-import-export' ), $xml_file ) );
	}
	if ( !class_exists( '' ) ) {
		require_once 'parsers.php';
	}
	$parser = new LPR_Export_Import_Parser();
	$data   = $parser->parse( $xml_file );
	if ( !empty( $data['authors'] ) ) {
		return $data['authors'];
	}
	return false;
}

function _lpie_sort_files( $a, $b ) {
	return $a['lastmodunix'] < $b['lastmodunix'];
}