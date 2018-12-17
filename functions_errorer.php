<?php

class Errorer {
	//класс для вывода ошибок

	public function __construct($txt_errors = ''){
		//echo 'The class "' . __CLASS__ . '" was initiated!<br>';

		$txt_general = "

				0	SUCCESS	operation finished successfully
				";

		$fixed_errors = array(
			"api_create_task_external"=>"
				700	ERROR_URLS_NOT_ENOUGH	Not enough urls
				701	ERROR_MYSQL_INSERT_TASK	Mysql error, can't insert task
				702	ERROR_MYSQL_UPDATE_URLS	Mysql error, can't update urls
				",

			"api_public" => "
				#key problems
				200	ERROR_KEY_DOES_NOT_EXIST	Account authorization key not found in the system
				201	ERROR_KEY_IS_NOT_SENT	You have not sent Account authorization key
				202	ERROR_KEY_BAD	Account authorization key is bad

				14	ERROR_NO_SUCH_METHOD	Request to API made with method which does not exist

				301	ERROR_NO_SUCH_TASK	No task with such id
				302	ERROR_WRONG_OWNER	You are not owner of this task
				303	ERROR_NO_TASK_ID	You have not sent task id
				304	ERROR_UNKNOWN_STATUS	Unknown task status


				101	ERROR_SERVICE_UNDER_CONSTRUCTION	Service temporarily under construction
				",
			);

		if (isset($fixed_errors[$txt_errors])) {
			$txt_errors = $fixed_errors[$txt_errors];
		}
		$txt_errors = $txt_general.$txt_errors;

		$known_errors = $this->get_api_errors_from_txt($txt_errors);

		$this->known_errors = $known_errors;
	}

	function get_api_errors_from_txt($txt_errors){
		$items = clear_list($txt_errors);
		$known_errors = array();
		foreach ($items as $item){
			$parts = explode('	', $item);
			if (count($parts)!=3) continue;

			list($id, $code, $descr) = $parts;
			$id = intval($id);
			$known_errors[$code] = array(
				'errorId'=>$id,
				'errorCode'=>$code,
				'errorDescription'=>$descr,

				);
		}
		return $known_errors;
		//ea($items);
		//die();
	}

	function get_error_description($er_code='no_apikey') {
		$known_errors = $this->known_errors;
		//ea($known_errors, 0, 'all known errors');

		if ($er_code=='all') {
			return $known_errors;
		}
		else if (!isset($known_errors[$er_code])) {
			die("unknown er_code $er_code");
		}
		else {
			return $known_errors[$er_code];
		}
	}

	function api_for_old($r, $dr, $er=''){
		$r = add_defaults($r, $dr);
		if ($er=='' and isset($r['errorCode'])){
			$er = $r['errorCode'];
		}
		$r = api_res_old($r, $er);
		return $r;
		}

	function res_success_old($from_task, $compatible_with_old=1){
		//success result - add default values

		$er_success = $this->get_error_description('SUCCESS');

		$dr_success = array(
			'status'=>'success',	#error success
			'message'=>'no errors',
		);

		$from_task = add_defaults($from_task, $er_success);

		if ($compatible_with_old){
			$from_task = $this->api_for_old($from_task, $dr_success);
		}

		//если успешно - удаляем ненужные поля
		if ($from_task['errorId'] == 0) {
			$bad_keys = array(
				'errorCode',
				'errorDescription',
			);
			foreach ($bad_keys as $bad){
				if (isset($from_task[$bad])){
					unset ($from_task[$bad]);
				}
			}
		}
		return $from_task;
	}

	function res_error_old($errorCode='', $errorDetails='', $compatible_with_old=1){
		//default_result
		$dr = array(
			'status'=>'error',	#error success
			'message'=>'unknown',
		);

		$r = $this->get_error_description($errorCode);
		if ($errorDetails!=''){
			$r['errorDetails'] = $errorDetails;
		}

		//а теперь для совпадения со старым апи
		if ($compatible_with_old) {
			$r = $this->api_for_old($r, $dr);
		}
		//ea($r);

		//ea($r, $deep, 'calculated');
		return $r;

	}


	function res_success($from_task){
		//новая версия минималистичная
		return $this->res_success_old($from_task, $compatible_with_old=0);
	}

	function res_error($errorCode='', $errorDetails='') {
		//новая версия минималистичная
		return $this->res_error_old($errorCode, $errorDetails, $compatible_with_old=0);
	}

	function get_html_error_description(){
		//создаю хтмл для описания ошибок
		$errors = $this->get_error_description('all');
		$items = array();
		foreach ($errors as $key => $error){
			$errorCode = ( isset($error['errorCode']) ) ? $error['errorCode'] : '';
			$errorDescription= ( isset($error['errorDescription']) ) ? $error['errorDescription'] : '';
			$items[] = "<tr>
				<td>{$error['errorId']}</td>
				<td>$errorCode</td>
				<td>$errorDescription</td>

				</tr>";
		}
		$items = implode("\n", $items);

		$html = <<<EOT
			<table class='apihelp'>

			<tr>
				<th>ID</th>
				<th>Code</th>
				<th>Description</th>
			</tr>
			$items
			</table>
EOT;
		return $html;
	}

}
?>
