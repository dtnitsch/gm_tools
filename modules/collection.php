<?php
##################################################
#   Document Setup and Security
##################################################
_error_debug("MODULE: ". basename(__FILE__)); 	# Debugger 

// if(!logged_in()) { safe_redirect("/login/"); }
// $security_check_list = ['lists_list','lists_add','lists_edit','lists_delete'];
// $security_list = has_access(implode(",",$security_check_list)); 
// if(empty($security_list['lists_list'])) { back_redirect(); }

##################################################
#   Validation
##################################################
$pieces = explode('/',$GLOBALS['project_info']['path_data']['path']);
$key = trim($pieces[2]);


##################################################
#   DB Queries
##################################################
$q = "select * from public.collection where key='". db_prep_sql($key) ."'";
$info = db_fetch($q,"Getting collection information");

$q = "
	select
		public.asset.id
		,public.asset.title as asset
		,collection_list_map.id as collection_list_map_id
		,collection_list_map.list_id
		,collection_list_map.collection_id
		,collection_list_map.label
		,collection_list_map.randomize
		,collection_list_map.display_limit
		,list_asset_map.tags
		,list.title as list_title
		,list.tables as tables
	from public.asset
	join public.list_asset_map on 
		list_asset_map.asset_id = asset.id
	join public.collection_list_map on
		collection_list_map.list_id = list_asset_map.list_id
		and collection_list_map.collection_id = '". $info['id'] ."'
	join public.list on
		list.id = collection_list_map.list_id
	order by
		collection_list_map.id
		,asset.id
";
$assets_res = db_query($q,"Getting collection assets");

$assets = array();
while($row = db_fetch_row($assets_res)) {
	$id = $row['list_id'] ."-". $row['collection_list_map_id'];
	if(empty($assets[$id])) {
		$assets[$id] = [
			"list_title" => $row['list_title']
			,"list_label" => $row['label']
			,"randomize" => ($row['randomize'] == "t" ? 1 : 0)
			,"display_limit" => $row['display_limit']
			,"list_id" => $row['list_id']
			,"tables" => $row['tables']
			,"assets" => []
			,"tags" => []
		];
	}
	$assets[$id]['assets'][] = $row['asset'];
	$assets[$id]['tags'][] = $row['tags'];
}

##################################################
#   Pre-Content
##################################################
// add_css('pagination.css');
// add_js('sortlist.new.js');
add_js("list_functions.js",10);
$split_on_count = 3;

##################################################
#   Content
##################################################
?>
<div class='clearfix'>
	<h2 class='lists'>Lists: <?php echo $info['title']; ?></h2>
  
	<!--div class="mb">
		<label for="limit">
			Expand at: <input type="input" name="split_on_count" id="split_on_count" value="<?php echo $split_on_count; ?>"> 
		</label>
	</div-->

	<div class="mb">
		<button onclick="build_all_lists()">Randomize List</button>
	</div>

		<div class='listcounter' id="listcounter" style='display:;'>
<?php
foreach($assets as $k => $list) {
	$l = $list['display_limit'];
	$r = $list['randomize'];
	$title = (!empty($list['list_label']) ? $list['list_label'] : $list['list_title']);
	shuffle_assoc($list['assets']);
	shuffle_assoc($list['tags']);

	$output = '<strong>'. $title .'</strong>';

	// if($list['display_limit'] < $split_on_count) {
	// 	$output .= ': <span id="list_body_'. $k .'" data-limit="'. $l .'" data-randomize="'. $r .'">';	

	// } else 
	if($list['tables'] == "t") {
		$output .= '
		<br>
		<table cellspacing="0" cellpadding="0" class="list_table">
			<thead>
				<tr>
					<th>'. implode('</th><th>',explode("|",$list['assets'][0])) .'</th>
				</tr>
			</thead>
			<tbody id="list_body_'. $k .'" data-limit="'. $l .'" data-randomize="'. $r .'">
		';
	} else {
		$output .= '
			<br>
			<ol class="list_ordered" id="list_body_'. $k .'" data-limit="'. $l .'" data-randomize="'. $r .'">
		';

	}
	$cnt = 0;
	// for($len=count($list['assets']); $i<$len; $i++) {
	foreach($list['assets'] as $i => $v) {
		$t = json_decode($list['tags'][$i]);
		$display = ($cnt < $list['display_limit'] ? '' : " style='display:none;'");

		// if($list['display_limit'] < $split_on_count) {
		// 	// $output .= '<span data-filters="'. implode(' ',$t) .'"'. $display .'>'. $v .', </span>';
		// 	$output .= '<span'. $display .'>'. $v .'</span>';
		// 	$output .= ($cnt < ($list['display_limit'] - 1) ? ", " : '');

		// } else 
		if($list['tables'] == "t") {
			$output .= '<tr data-filters="'. implode(' ',$t) .'"'. $display .'>
				<td>'. implode("</td><td>",explode('|',$v)) .'</td>
			</tr>';

		} else {
			$output .= '<li data-filters="'. implode(' ',$t) .'"'. $display .'>
				'. $v .'
			</li>';
		}
		$cnt += 1;
	}

	// if($list['display_limit'] < $split_on_count) {
	// 	$output = substr($output,0,-2) ."</span>";
	// } else 
	if($list['tables'] == "t") {
		$output .= "</tbody></table>";
	} else {
		$output .= "</ol>";
	}
	echo $output;
	unset($output);
}
?>
	</div>

	<div class="clear"></div>
</div>
<?php
##################################################
#   Javascript Functions
##################################################
ob_start();
?>
<script type="text/javascript">
	var original_rows = {};
	var list_keys = ['<?php echo implode("','",array_keys($assets)); ?>'];

	set_original_rows();
</script>
<?php
$js = trim(ob_get_clean());
if(!empty($js)) { add_js_code($js); }

##################################################
#   Additional PHP Functions
##################################################
function shuffle_assoc(&$arr) {
	$keys = array_keys($arr);

	shuffle($keys);

	foreach($keys as $key) {
		$new[$key] = $arr[$key];
	}

	$arr = $new;

	return true;
}


##################################################
#   EOF
##################################################