<?php

function checkvar($v){ return (isset($v) && !empty($v)); }
function response($data, $code = 200, $status = "OK"){
	http_response_code($code);
	$data = ["status" => $status, "data" => $data];
	header("Content-Type: application/json");
	die(json_encode($data));
}

if(checkvar($_POST['message'])){
	$file = "messages.json";

	$data = NULL;
	if(file_exists($file)){ $data = file_get_contents($file); }
	if(!empty($data)){ $data = json_decode($data, TRUE); }
	else{ $data = array(); }

	$data[] = $_POST['message'];
	if(count($data) > 10){
		/* for($i = 10; $i < count($data); $i++){
			unset($data[$i]);
		} */
		unset($data[0]);
	}

	file_put_contents($file, json_encode($data));

	response($data);
}

?>