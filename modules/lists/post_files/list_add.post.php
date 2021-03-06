<?php
if(!empty($_POST) && !error_message()) {
	library('uuid.php');
	// library('validation.php');
	library("slug.php");

	validate([
		'title'=>'List Name'
		,'inputs' =>'Inputs'
	]);

	if(!error_message()) {

		$title = trim($_POST['title']);
		$alias = convert_to_alias($title);
		$key = create_key();
		$is_table = (strstr($_POST['inputs'],"|") !== false ? 't' : 'f');

		$markdown = trim(strip_tags($_POST['markdown']));

		$q = "
			insert into public.list (
				user_id
				,key
				,title
				,alias
				,tables
				,tags
				,filter_labels
				,filter_orders
				,description
				,created
				,modified
			) values (
				'". db_prep_sql(!empty($_SESSION['user']['id']) ? $_SESSION['user']['id'] : 1) ."'
				,'". $key ."'
				,'". db_prep_sql($title) ."'
				,'". db_prep_sql($alias) ."'
				,'". $is_table ."'
				,'". db_prep_sql(json_encode(array_keys(unique_tags()))) ."'
				,'". db_prep_sql(json_encode($_POST['filter_labels'])) ."'
				,'". db_prep_sql(json_encode($_POST['filter_order'])) ."'
				,'". db_prep_sql($markdown) ."'
				,now()
				,now()
			) returning id
		";
		$res = db_fetch($q, "Inserting List Name");
		if(empty($res['id'])) {
			error_message("List insert failed");
		}

		if(!error_message()) {
			$list_id = $res['id'];


			$pieces = explode("\n",$_POST['inputs']);
			$assets = array();
			$percentages = "";
			$map_ids = [];
			$alias_list = [];
			foreach($pieces as $v) {
				$v = trim($v);
				if($v == "") {
					continue;
				}

				$inner_pieces = explode(";",$v);
				$asset = trim($inner_pieces[0]);
				# Filters
				$filter_labels = [];
				if(!empty($inner_pieces[1])) {
					$tmp = explode(",",$inner_pieces[1]);
					foreach($tmp as $k => $v) {
						if(trim($k) == "" || trim($v) == "") {
							continue;
						}
						$filter_labels[$k] = convert_to_alias($v);
					}
				}
				$filter_labels = json_encode($filter_labels);

// echo "<pre>Asset: ";
// print_r($asset);
				$alias = convert_to_alias($asset);
				$alias = db_prep_sql($alias);
				$alias_list[$alias] = [
					"asset" => $asset
					,"filter_labels" => $filter_labels
				];
// echo "<br>Alias:";
// print_r($alias);
// echo "<br>-----------</br>";
			}
			$q = "select id,alias from public.asset where alias in ('". implode("','",array_keys($alias_list)) ."')";
			$res = db_query($q,"Checking existing assets");
			$existing = [];
			while($row = db_fetch_row($res)) {
				$existing[$row['alias']] = $row['id'];
				$alias_list[$row['alias']]['id'] = $row['id'];
			}
// echo "<pre>";
// print_r($alias_list);
			$q = '';
			foreach($alias_list as $k => $v) {
				if(empty($existing[$k])) {
					$q .= "
						(
							'". db_prep_sql($v['asset']) ."'
							,'". db_prep_sql($k) ."'
							,now()
							,now()
					),";
				}
			}
// die($q);

			if(!empty($q)) {
				$q = "
					insert into public.asset (
						title
						,alias
						,created
						,modified
					) values ". substr($q,0,-1) ."
					RETURNING id,title,alias
				";
			// die();
				$res = db_query($q,"Inserting/Selecting Asset: ". $title);
				while($row = db_fetch_row($res)) {
					$alias_list[$row['alias']]['id'] = $row['id'];
				}
			}
			foreach($alias_list as $k => $v) {
				if(!empty($v['id'])) {
					$map_ids[] = "(
						". $list_id ."
						,". $v['id'] ."
						,'". db_prep_sql($v['filter_labels']) ."'::json
						,now()
					)";
				}
			}


			$q = "
				insert into list_asset_map (
					list_id
					,asset_id
					,filters
					,created
				) values ". implode(',',$map_ids);
			$res = db_query($q,"Inserting list asset map");
			if (db_is_error($res)) {
				error_message("AN error occured trying to insert the new asset.");
			}

			if(!error_message()) {
				$redirection_path = '/lists/';
				set_post_message("You have successfully created a new record");
				set_safe_redirect($redirection_path);
			}
		} // End list_id
	}
}

function unique_tags() {
	$tags = [];
	$pieces = explode("\n",trim($_POST['inputs']));
	$output = [];
	for($i=0,$len=count($pieces); $i<$len; $i++) {
		$inner_pieces = explode(";",$pieces[$i]);
		if(!empty($inner_pieces[1])) {
			$tags = explode(',',trim($inner_pieces[1]));
			for($j=0,$lenj=count($tags); $j<$lenj; $j++) {
				$output[convert_to_alias($tags[$j])] = 1;
			}
		} else {
			continue;
		}
	}
	return $output;
}
