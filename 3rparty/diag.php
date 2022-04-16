<?php

function somfyRequest($_url, $data = null) {
	if (strpos($_url, '?') !== false) {
		$url = 'http://127.0.0.1:9000/' . trim($_url, '/') . '&access_token=' . jeedom::getApiKey('somfy');
	} else {
		$url = 'http://127.0.0.1:9000/' . trim($_url, '/') . '?access_token=' . jeedom::getApiKey('somfy');
	}
	log::add('somfy', 'debug', "url: " . $url);

    //Initialize cURL.
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);

    //Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($data !== null) {
        log::add('somfy', 'debug', "data: " . json_encode($data));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }

    //Execute the request.
    $response = curl_exec($ch);
	if (curl_errno($ch)) {
		$curl_error = curl_error($ch);
		curl_close($ch);
		throw new Exception(__('Echec de la requête http : ', __FILE__) . $url . ' Curl error : ' . $curl_error, 404);
	}
    //Close the cURL handle.
    curl_close($ch);

    log::add('somfy', 'debug', "response: " . $response);
    return is_json($response, $response);
}

?>
