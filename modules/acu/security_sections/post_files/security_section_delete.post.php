<?php
if(!empty($_POST) && !ERROR_MESSAGE()) {
	LIBRARY('validation.php');

	
	validate($_POST['id'],'required','ID');

	if(!ERROR_MESSAGE()) {

		$table_info = array(
			'table_name' => 'section'
			,'table_schema' => 'security'
			,'primary_key' => 'id'
			# optional
			,'audit_table' => 'security_table_log'
			,'audit_schema' => 'audits'
			,'primary_key_value' => db_prep_sql($_POST['id'])
			# Key = DB column Name, Value = Post name
			,'table_columns' => array()
		);

		$table_info['table_columns'][] = array(
			'active' => 'f'
		);

		$res = '';
		$original_values = POST_HAS_CHANGES($table_info);
		if(!empty($original_values)) {
			$table_info['original_values'] = $original_values;
			$res = POST_FUNCTIONS_UPDATE($table_info);
		} else {
			$res = 'error';
		}

		if($res != 'error') {
			AUDIT('table_update',$table_info);

			$redirection_path = '/acu/security-sections/';
			SET_POST_MESSAGE("The record has been successfully deleted");
			SET_SAFE_REDIRECT($redirection_path);

		} else {
			ERROR_MESSAGE("An error has occurred while trying to delete this record");
		}
	}
}