<?php

namespace TimberMetaboxBlocks;
/**
 * Register MB Blocks and meta boxes using the file-headers found in directory/blocks/*.twig
 *
 * Please note that filesnames will be used to register the MB id's as well
 *
 * @package timber-mb-wp-blocks
 **/

add_filter( 'rwmb_meta_boxes' , __NAMESPACE__ . '\register_blocks' , 10 , 1 );

/**
 * add a filter to the default preview setting
 * if
 */
add_filter( 'timber/mb-gutenberg-blocks-preview' , __NAMESPACE__ . '\default_preview_on_empty' );

/**
 * add a filter to the default fields setting
 *
 * removing fields entirely will result in
 */
add_filter( 'timber/mb-gutenberg-blocks-fields' , __NAMESPACE__ . '\default_fields_on_empty' );

/**
 * filter callback for setting a default preview value (shown before selecting the block)
 * @param  [type] $preview [description]
 * @return [type]          [description]
 */
function default_preview_on_empty( $preview ) {

	// bail early if not empty
	if (  !empty( $preview ) ) return $preview;

	$preview = array_merge( $preview , [ [ 'hidden' => '' ] ] );

	return $preview;
}

/**
 * filter callback setting the default fields.
 * mb needs at least one parameter and
 * custom html is used to show user that there are no additional settings
 *
 * @param  [type] $fields [description]
 * @return [type]         [description]
 */
function default_fields_on_empty( $fields ) {

	// bail early if not empty
	if (  !empty( $fields ) ) return $fields;

	$fields = array_merge( $fields,
							[
								[
								 	'id' => 'hidden',
								 	'type' => 'hidden',
								 	'std'	=> 'hidden',
								],
								[
									'id' => 'hidden2',
									'type' => 'custom_html',
									'std' => 'This block has no settings',
								],
							]
						);
	return $fields;
}

/**
 * Create blocks based on templates found in Timber's "views/blocks" directory
 */
function register_blocks( $meta_boxes ) {
	// Get an array of directories containing blocks.
	$directories = timber_block_directory_getter();

	// Check whether ACF exists before continuing.
	foreach ( $directories as $dir ) {
		// Sanity check whether the directory we're iterating over exists first.
		if ( ! file_exists( \locate_template( $dir ) ) ) {
			return;
		}
		// Iterate over the directories provided and look for templates.
		$template_directory = new \DirectoryIterator( \locate_template( $dir ) );
		foreach ( $template_directory as $template ) {

			if ( ! $template->isDot() && ! $template->isDir() ) {
				$file_parts = pathinfo( $template->getFilename() );

				if ( 'twig' !== $file_parts['extension'] ) {
					continue;
				}

				// Strip the file extension to get the slug.
				$slug = $file_parts['filename'];

				// Get header info from the found template file(s).
				$file_path    = locate_template( $dir . "/${slug}.twig" );
				$file_headers = get_file_data(
					$file_path,
					array(
						'title'                      => 'Title',
						'description'                => 'Description',
						'category'                   => 'Category',
						'icon'                       => 'Icon',
						'context'					 => 'Context',
						'keywords'                   => 'Keywords',
						'mode'                       => 'Mode',
						'align'                      => 'Align',
						'post_types'                 => 'PostTypes',
						'supports_align'             => 'SupportsAlign',
						'supports_mode'              => 'SupportsMode',
						'supports_multiple'          => 'SupportsMultiple',
						'supports_anchor'            => 'SupportsAnchor',
						'enqueue_style'              => 'EnqueueStyle',
						'enqueue_script'             => 'EnqueueScript',
						'enqueue_assets'             => 'EnqueueAssets',
						'supports_custom_class_name' => 'SupportsCustomClassName',
						'supports_reusable'          => 'SupportsReusable',
						'preview'                    => 'Preview',
						'fields'                     => 'Fields',
						'supports_jsx'               => 'SupportsJSX',
					)
				);

				if ( empty( $file_headers['title'] ) ) {
					continue;
				}
				if ( empty( $file_headers['category'] ) ) {
					continue;
				}

				// Keywords exploding with quotes.
				$keywords = str_getcsv( $file_headers['keywords'], ' ', '"' );

				// Set up block data for registration.
				$data = array(
					'id'                       => $slug,
					'title'                      => $file_headers['title'],
					'description'                => $file_headers['description'],
					'type'						=> 'block',
					'context'					=> $file_headers[ 'context' ],
					'icon'                       => $file_headers['icon'],
					'category'                   => $file_headers['category'],
					'keywords'                   => $keywords,
					'mode'                       => $file_headers['mode'],
					'align'                      => $file_headers['align'],
					'render_callback'            => __NAMESPACE__ . '\timber_blocks_callback',
					'enqueue_assets'             => $file_headers['enqueue_assets'],
					'supports_custom_class_name' => 'SupportsCustomClassName',
					'supports_reusable'          => 'SupportsReusable',
				);
				// If the PostTypes header is set in the template, restrict this block
				// to those types.
				if ( ! empty( $file_headers['post_types'] ) ) {
					$data['post_types'] = explode( ' ', $file_headers['post_types'] );
				}
				// If the SupportsAlign header is set in the template, restrict this block
				// to those aligns.
				if ( ! empty( $file_headers['supports_align'] ) ) {
					$data['supports']['align'] = in_array( $file_headers['supports_align'], array( 'true', 'false' ), true ) ?
						filter_var( $file_headers['supports_align'], FILTER_VALIDATE_BOOLEAN ) :
						explode( ' ', $file_headers['supports_align'] );
				}
				// If the SupportsMode header is set in the template, restrict this block
				// mode feature.
				if ( ! empty( $file_headers['supports_mode'] ) ) {
					$data['supports']['mode'] = 'true' === $file_headers['supports_mode'] ? true : false;
				}
				// If the SupportsMultiple header is set in the template, restrict this block
				// multiple feature.
				if ( ! empty( $file_headers['supports_multiple'] ) ) {
					$data['supports']['multiple'] = 'true' === $file_headers['supports_multiple'] ? true : false;
				}
				// If the SupportsAnchor header is set in the template, restrict this block
				// anchor feature.
				if ( ! empty( $file_headers['supports_anchor'] ) ) {
					$data['supports']['anchor'] = 'true' === $file_headers['supports_anchor'] ? true : false;
				}

				// If the SupportsCustomClassName is set to false hides the possibilty to
				// add custom class name.
				if ( ! empty( $file_headers['supports_custom_class_name'] ) ) {
					$data['supports']['customClassName'] = 'true' === $file_headers['supports_custom_class_name'] ? true : false;
				}

				// If the SupportsReusable is set in the templates it adds a posibility to
				// make this block reusable.
				if ( ! empty( $file_headers['supports_reusable'] ) ) {
					$data['supports']['reusable'] = 'true' === $file_headers['supports_reusable'] ? true : false;
				}

				// Gives a possibility to enqueue style. If not an absoulte URL than adds
				// theme directory.
				if ( ! empty( $file_headers['enqueue_style'] ) ) {
					if ( ! filter_var( $file_headers['enqueue_style'], FILTER_VALIDATE_URL ) ) {
						$data['enqueue_style'] = get_template_directory_uri() . '/' . $file_headers['enqueue_style'];
					} else {
						$data['enqueue_style'] = $file_headers['enqueue_style'];
					}
				}

				// Gives a possibility to enqueue script. If not an absoulte URL than adds
				// theme directory.
				if ( ! empty( $file_headers['enqueue_script'] ) ) {
					if ( ! filter_var( $file_headers['enqueue_script'], FILTER_VALIDATE_URL ) ) {
						$data['enqueue_script'] = get_template_directory_uri() . '/' . $file_headers['enqueue_script'];
					} else {
						$data['enqueue_script'] = $file_headers['enqueue_script'];
					}
				}
				// Support for experimantal JSX.
				if ( ! empty( $file_headers['supports_jsx'] ) ) {
					$data['supports']['__experimental_jsx'] = 'true' === $file_headers['supports_jsx'] ? true : false;
				}

				// Support for "preview"
				if ( ! empty( $file_headers['preview'] ) ) {
					$json                       = json_decode( $file_headers['preview'], true );
					$preview_data               = ( null !== $json ) ? $json : [];
					$data['preview']            = apply_filters( 'timber/mb-gutenberg-blocks-preview' , (!empty( $preview_data ) ? $preview_data[0] : [] ) );
				} else {
					$data[ 'preview' ]			= apply_filters( 'timber/mb-gutenberg-blocks-preview' , [] );
				}

				// Support for "fields"
				if ( ! empty( $file_headers['fields'] ) ) {
					$json                       = json_decode( $file_headers['fields'] , true );
					$fields               = ( null !== $json ) ? $json : [];

					// extend the fields with filters
					$data['fields']            = apply_filters( 'timber/mb-gutenberg-blocks-fields' , $fields );
				} else {
					// extend the fields with filters
					$data[ 'fields' ]			= apply_filters( 'timber/mb-gutenberg-blocks-fields' , [] );

				}

				// Register the block with ACF.
				$meta_boxes[ $slug ] = $data ;
			}
		}
	}

	return $meta_boxes;
}


/**
 * Callback to register blocks
 *
 * @param array  $block stores all the data from ACF.
 * @param string $content content passed to block.
 * @param bool   $is_preview checks if block is in preview mode.
 * @param int    $post_id Post ID.
 */
function timber_blocks_callback( $block, $is_preview = false, $post_id = 0 ) {
	// Set up the slug to be useful.
	$context = \Timber::get_context();

	$slug    = $block['name'];

	$context['block']      = $block;
	$context['post_id']    = $post_id;
	$context['slug']       = $slug;
	$context['is_preview'] = $is_preview;

	$fields = array_keys( isset( $block[ 'data' ] ) ? $block[ 'data' ] : [] );

	foreach ($fields as $field ) {
		$context[ 'block' ][ $field ] = mb_get_block_field( $field );
	}

	$classes               = array_merge(
		array( $slug ),
		isset( $block['className'] ) ? array( $block['className'] ) : array(),
		$is_preview ? array( 'is-preview' ) : array(),
		isset( $block['align'] ) ? array( 'align' . $block['align'] ) : array(),
	);

	$context['block']['classes'] = implode( ' ', $classes );

	$is_example = false;

	$context = apply_filters( 'timber/mb-gutenberg-blocks-data', $context );
	$context = apply_filters( 'timber/mb-gutenberg-blocks-data/' . $slug, $context );
	$context = apply_filters( 'timber/mb-gutenberg-blocks-data/' . $block['id'], $context );

	$paths = timber_acf_path_render( $slug, $is_preview, $is_example );

	\Timber::render( $paths, $context );
}

/**
 * Generates array with paths and slugs
 *
 * @param string $slug File slug.
 * @param bool   $is_preview Checks if preview.
 * @param bool   $is_example Checks if example.
 */
function timber_acf_path_render( $slug, $is_preview, $is_example ) {

	$directories = timber_block_directory_getter();

	$ret = array();

	foreach ( $directories as $directory ) {
		if ( $is_example ) {
			$ret[] = $directory . "/{$slug}-example.twig";
		}
		if ( $is_preview ) {
			$ret[] = $directory . "/{$slug}-preview.twig";
		}
		$ret[] = $directory . "/{$slug}.twig";
	}

	return $ret;
}

/**
 * Generates the list of subfolders based on current directories
 *
 * @param array $directories File path array.
 */
function timber_blocks_subdirectories( $directories ) {
	$ret = array();

	foreach ( $directories as $base_directory ) {
		$template_directory = new \RecursiveDirectoryIterator( \locate_template( $base_directory ), \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_SELF );

		if ( $template_directory ) {
			foreach ( $template_directory as $directory ) {
				if ( $directory->isDir() && ! $directory->isDot() ) {
					$ret[] = $base_directory . '/' . $directory->getFilename();
				}
			}
		}
	}

	return $ret;
}

/**
 * Universal function to handle getting folders and subfolders
 */
function timber_block_directory_getter() {
	// Get an array of directories containing blocks.
	$directories = apply_filters( 'timber/mb-gutenberg-blocks-templates', array( 'views/blocks' ) );

	// Check subfolders.
	$subdirectories = timber_blocks_subdirectories( $directories );

	if ( ! empty( $subdirectories ) ) {
		$directories = array_merge( $directories, $subdirectories );
	}

	return $directories;
}
