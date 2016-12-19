<?php
defined('CUSTOM_MISSING_DERIVATIVES_PATH') or die('Hacking attempt!');

function custom_missing_derivatives_ws_add_methods($arr) {
	$service = &$arr[0];

	$service->addMethod(
		'custom_missing_derivatives.getMissingDerivativesCustom', 
		'ws_getMissingDerivativesCustom', 
		array( 
			'types' => array( 'default'=>array(), 'flags'=>WS_PARAM_FORCE_ARRAY), 
			'ids' => array( 'default'=>array(), 
			'flags'=>WS_PARAM_FORCE_ARRAY), 
			'max_urls' => array( 'default' => 200 ), 
			'prev_page' => array( 'default'=> null), 
			'f_min_rate' => array( 'default'=> null ), 
			'f_max_rate' => array( 'default'=> null ), 
			'f_min_hit' => array( 'default'=> null ), 
			'f_max_hit' => array( 'default'=> null ), 
			'f_min_date_available' => array( 'default'=> null ), 
			'f_max_date_available' => array( 'default'=> null ), 
			'f_min_date_created' => array( 'default'=> null ), 
			'f_max_date_created' => array( 'default'=> null ), 
			'f_min_ratio' => array( 'default'=> null ), 
			'f_max_ratio' => array( 'default'=> null ), 
			'f_max_level' => array( 'default'=> null ),
			'customTypes' => array( 'default'=>array(), 'flags'=>WS_PARAM_FORCE_ARRAY), 
		), 
		'retrieves a list of derivatives to build' 
	);
}

/**
 * Modified copy of piwigo function ws_getMissingDerivatives.
 * API method
 * Returns a list of missing derivatives (not generated yet)
 * @param mixed[] $params
 *    @option string types (optional)
 *    @option int[] ids
 *    @option int max_urls
 *    @option int prev_page (optional)
 */
function ws_getMissingDerivativesCustom($params, &$service) {
	global $conf;

	if (empty($params['types'])) {
		$types = array_keys(ImageStdParams::get_defined_type_map());
	} else {
		$types = array_intersect(array_keys(ImageStdParams::get_defined_type_map()), $params['types']);
		if (count($types)==0) {
			return new PwgError(WS_ERR_INVALID_PARAM, "Invalid types");
		}
	}
	if (!empty($params['customTypes'])) {
		foreach($params['customTypes'] as $customType) {
			if (!preg_match("/\b\d+x\d+_[0|1]_\d+x\d+\b/", $customType))  {
				return new PwgError(WS_ERR_INVALID_PARAM, "Invalid custom type " . $customType . ". Must match \b\d+x\d+_[0|1]_\d+x\d+\b");
			}
			$customTypeExp = explode("_", $customType);
			$customTypeExp0 = explode("x", $customTypeExp[0]);
			$customTypeExp2 = explode("x", $customTypeExp[2]);
			$types[] = ImageStdParams::get_custom($customTypeExp0[0], $customTypeExp0[1], $customTypeExp[1], $customTypeExp2[0], $customTypeExp2[1]);
		}
	}

	$max_urls = $params['max_urls'];
	$query = 'SELECT MAX(id)+1, COUNT(*) FROM '. IMAGES_TABLE .';';
	list($max_id, $image_count) = pwg_db_fetch_row(pwg_query($query));

	if (0 == $image_count) {
		return array();
	}

	$start_id = $params['prev_page'];
	if ($start_id<=0) {
		$start_id = $max_id;
	}

	$uid = '&b='.time();

	$conf['question_mark_in_urls'] = $conf['php_extension_in_urls'] = true;
	$conf['derivative_url_style'] = 2; //script

	$qlimit = min(5000, ceil(max($image_count/500, $max_urls/count($types))));
	$where_clauses = ws_std_image_sql_filter( $params, '' );
	$where_clauses[] = 'id<start_id';

	if (!empty($params['ids'])) {
		$where_clauses[] = 'id IN ('.implode(',',$params['ids']).')';
	}

	$query_model = '
	SELECT id, path, representative_ext, width, height, rotation
	FROM '. IMAGES_TABLE .'
	WHERE '. implode(' AND ', $where_clauses) .'
	ORDER BY id DESC
	LIMIT '. $qlimit .'
	;';

	$urls = array();
	do {
		$result = pwg_query(str_replace('start_id', $start_id, $query_model));
		$is_last = pwg_db_num_rows($result) < $qlimit;

		while ($row=pwg_db_fetch_assoc($result)) {
			$start_id = $row['id'];
			$src_image = new SrcImage($row);
			if ($src_image->is_mimetype()) {
				continue;
			}

			foreach($types as $type) {
				$derivative = new DerivativeImage($type, $src_image);
				if ($type != $derivative->get_type() && $derivative->get_type() != 'custom') {
					continue;
				}
				if (@filemtime($derivative->get_path())===false) {
					$urls[] = $derivative->get_url().$uid;
				}
			}

			if (count($urls)>=$max_urls and !$is_last) {
				break;
			}
		}
		if ($is_last) {
			$start_id = 0;
		}
	} while (count($urls)<$max_urls and $start_id);

	if ($start_id) {
		$ret['next_page'] = $start_id;
	}
	$ret = array();
	$ret['urls'] = $urls;
	return $ret;
}
