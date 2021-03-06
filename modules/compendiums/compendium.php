<?php
##################################################
#   Document Setup and Security
##################################################
_error_debug("MODULE: ". basename(__FILE__)); 	# Debugger 

##################################################
#   Validation
##################################################

##################################################
#   DB Queries
##################################################

##################################################
#   Pre-Content
##################################################
// add_css('pagination.css');
// add_js('sortlist.new.js');

$add_button = "<button onclick='window.location.href=\"/compendiums/add/\"' class='add'>Add New Compendium</button>";

// $edit_onclick = "";
// if(!empty($security_compendium['compendiums_edit'])) {
    $edit_onclick = " onclick='window.location=\"/compendiums/edit/?key={{key}}\"'";
// }

$delete_link = "";
if(!empty($security_compendium['compendiums_delete'])) {
    $delete_link = '<a href="/compendiums/delete/?id={{id}}" title="Delete: {{path}}" class="delete"></a>';
}

##################################################
#   Content
##################################################
?>
<div class='clearfix'>
	<div class='right float_right buttons clear'>
		<?= $add_button ?>
	</div>

	<h2 class='compendiums'>Compendiums</h2>
  
</div>
<div class='content_container clear'>
<?php echo dump_messages(); ?>
	<fieldset class='filters' id='filters'>
	<form id="form_filters" method="" action="" onsubmit="return false;">
		
		<div class='inputs float_left'>
			<label for='compendium'><b>Compendium</b></label><br>
			<input type='text' name='filters[compendium]' id='compendium'>
		</div>

		<div class='inputs float_left'>
			<label for='alias'><b>Alias</b></label><br>
			<input type='text' name='filters[alias]' id='alias'>
		</div>

		<div class='inputs float_left'>
      <label>&nbsp;</label><br>
      <button onclick='filter_results()' class='filter'>Filter Results</button>
    </div>

    </form>

	<form id="export_csv" method="post" action="/export/csv/" style='display: none;'>
		<label>&nbsp;</label><br>
		<input type="submit" value="Export CSV">
		<input type="hidden" name="query_csv" id="query_csv" value="">
	</form>

	</fieldset>

	<span class='show_pagination'></span>
	<table id='asl_sort' cellpadding="0" cellspacing="0" class='asl_sort'>
		<thead>
		<tr>
			<th data-col="title">Compendium</th>
			<th data-col="alias">Alias</th>
			<th data-col="modified">Date Modified</th>
			<th class='options' style='width: 1%;'><img src='/images/options.png' /></th>
		</tr>
		</thead>
		<tbody style='display: none;'>
		<tr<?= $edit_onclick ?>>
			<td>{{title}}</td>
			<td>{{alias}}</td>
			<td>{{modified}}</td>
			<td rel='nolink' class='options'><?= $delete_link ?></td>
		</tr>
		</tbody>
	</table>
	<span class='show_pagination'></span>
</div>
<?php
##################################################
#   Javascript Functions
##################################################
ob_start();
?>

<script type="text/javascript">

	// asl_sort = sortlist().remote;
	// asl_sort.init('/ajax.php',{
	// 	id:'asl_sort'
	// 	,data: 'apid=db6d9b451b818ccc9a449383f2f0c450'
	// 	,filters: 'form_filters'
	// 	,type: "pagination"
 //        ,column: "title"
	// });

	// function filter_results() {
	// 	asl_sort.sort(asl_sort,true);
	// }
	// // True is needed to show it's a custom call
	// asl_sort.sort(asl_sort,true);

	// // Onfocus
 //    $id("compendium").focus();

</script>

<?php
$js = trim(ob_get_clean());
if(!empty($js)) { add_js_code($js); }

##################################################
#   Additional PHP Functions
##################################################

##################################################
#   EOF
##################################################