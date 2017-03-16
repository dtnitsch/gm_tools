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
$q = "select * from public.list where key='". db_prep_sql($key) ."'";
$info = db_fetch($q,"Getting list information");

$q = "
	select
		public.asset.*
		,list_asset_map.tags
	from public.asset
	join public.list_asset_map on 
		list_asset_map.asset_id = asset.id
		and list_asset_map.list_id = '". $info['id'] ."'
	order by
		asset.id
";
$assets = db_query($q,"Getting list assets");


##################################################
#   Pre-Content
##################################################
// add_css('pagination.css');
// add_js('sortlist.new.js');
$list = [];
while($row = db_fetch_row($assets)) {
	$list[] = [
		"title" => $row['title']
		,"tags" => json_decode($row['tags'])
	];
}

##################################################
#   Content
##################################################
?>
<div class='clearfix'>
	<h2 class='lists'>Lists: <?php echo $info['title']; ?></h2>
  
	<div class="mb">
		<label for="limit">
			Limit Display: <input type="input" name="limit" id="limit" value="20" class='xs'> 
		</label>

		<label for="randomize">
			<input checked type="checkbox" name="options" id="randomize" value="randomize"> Randomize
		</label>
	</div>

<?php
	$info['tags'] = json_decode($info['tags']);
	if(!empty($info['tags'])) {
		$output = '<div id="custom_filters" class="mb">';
		$cnt = 0;
		foreach($info['tags'] as $v) {
			$output .= '
			<label for="filter_'. $cnt .'">
				<input type="checkbox" id="filter_'. $cnt .'" name="filters['. $v .']" onclick="build_list()" value="'. $v .'"> '. $v .'
			</label> &nbsp; 
			';
			$cnt += 1;
		}
		$output .= '</div>';
		echo $output;
	}
?>

	<div class="mb">
		<button onclick="build_list()">Update</button>
	</div>



	<div class='listcounter' id="listcounter">
<?php
	$output = '
		<strong>'. $info['title'] .'</strong><br>
		<ol class="list_ordered" id="list_body">
	';
	$i = 0;
	if($info['tables'] == "t") {
		$i = 1;
		$output = '
		<strong>'. $info['title'] .'</strong><br>
		<table cellspacing="0" cellpadding="0" class="list_table">
			<thead>
				<tr>
					<th>'. implode('</th><th>',explode("|",$list[0]['title'])) .'</th>
				</tr>
			</thead>
			<tbody id="list_body">
		';
	}
	for($len=count($list); $i<$len; $i++) {
		$row = $list[$i];
		if($info['tables'] == "t") {
			$output .= '<tr data-filters="'. implode(' ',$row['tags']) .'">
				<td>'. implode("</td><td>",explode('|',$row['title'])) .'</td>
			</tr>';
		} else {
			$output .= '<li data-filters="'. implode(' ',$row['tags']) .'">
				'. $row['title'] .'
			</li>';
		}
	}

	if($info['tables'] == "t") {
		$output .= "</tbody></table>";
	} else {
		$output .= "</ol>";
	}
	echo $output;
	unset($output);
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
	var is_table = <?php echo ($info['tables'] == 't' ? 'true' : 'false'); ?>;
	var original_rows = [];

	function double_shuffle(id) {
		id = id || 'list_body';
		shuffle_rows(id);
		shuffle_rows(id);
	}
	function shuffle_rows(id) {
		var id = id || 'list_body';
	    var list_rows = (is_table ? $id(id).rows : $query('#list_body li'));
	    var rows = new Array();
		var row;
		for (var i=list_rows.length-1; i>=0; i--) {
		    row = list_rows[i];
		    rows.push(row);
		    row.parentNode.removeChild(row);
	    }
	    shuffle(rows);
	    for (i=0; i<rows.length; i++) {
	    	$id(id).appendChild(rows[i]);
		}
	}
	function reset_table(id) {
		var id = id || 'list_body';
	    var list_rows = (is_table ? $id(id).rows : $query('#list_body li'));
		var row;
		for (i=list_rows.length-1; i>=0; i--) {
		    row = list_rows[i];
		    row.parentNode.removeChild(row);
	    }
	    for (i=original_rows.length - 1; i >= 0; i--) {
	    	$id(id).appendChild(original_rows[i]);
		}
	}
	function set_original_rows(id) {
		var id = id || 'list_body';
	    var list_rows = (is_table ? $id(id).rows : $query('#list_body li'));
		var row;
		for (var i=list_rows.length-1; i>=0; i--) {
		    row = list_rows[i];
		    original_rows.push(row);
	    }
	}

	function get_filters() {
		var filters = $query('#custom_filters input[name^=filter]');
		var checked = [];
		for(var i=0,len=filters.length; i<len; i++) {
			if(filters[i].checked) {
				checked[checked.length] = filters[i].value;
			}
		}
		return checked;
	}

	function build_list(id) {
		var id = id || 'list_body';
	    var list_rows = (is_table ? $id(id).rows : $query('#list_body li'));

		var limit = parseInt($id('limit').value);
		var randomize = $id('randomize').checked;

		var checked;

		if(limit < 0 || limit > list_rows.length) {
			limit = list_rows.length;
		}

		if(randomize) {
			shuffle_rows();
		} else {
			reset_table();
		}

		checked = get_filters();
		r = new RegExp('(^|\\s)('+ checked.join("|") +')(\\s|$)');

		// console.log(checked)
		// console.log(limit)
		// console.log(randomize)
		// console.log(list_rows)

		cnt = 0;
		for(var i=0; i<list_rows.length; i++) {
			if(checked.length == 0) {
				list_rows[i].style.display = (cnt < limit ? "" : "none");
				cnt += 1;
			} else {
				if(r.test(list_rows[i].dataset.filters)) {
					list_rows[i].style.display = (cnt < limit ? "" : "none");
					cnt += 1;
				} else {
					list_rows[i].style.display = "none";
				}
			}
		}

		
	}

	set_original_rows();
</script>
<?php
$js = trim(ob_get_clean());
if(!empty($GLOBALS['show_js_now'])) {
	echo $js;
}
if(!empty($js)) { add_js_code($js); }

##################################################
#   Additional PHP Functions
##################################################

##################################################
#   EOF
##################################################