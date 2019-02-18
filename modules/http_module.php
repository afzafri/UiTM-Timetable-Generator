<?php

namespace http;

function http_request($arrLinks, $cookies = null, $post = null, $nThread = 1) {

	$originalCount = count($arrLinks);

	# if not an array, then convert it to be an array 
	if (is_array($arrLinks) === false) {
		$arrLinks = [$arrLinks];
	}
  
	# split links into multiple chunks
	$arrLinks = array_chunk($arrLinks, $nThread);

	$collect_data = []; # array to store curl result

	foreach ($arrLinks as $chunk) {

		$curl_instances = []; # to store curl handle
		$result = []; # to store curl result for each loop

		$mh = curl_multi_init();

		# create instance of curl for each links
		foreach ($chunk as $i => $link) {

			$curl_instances[$i] = curl_init();
			curl_setopt($curl_instances[$i], CURLOPT_URL, $link);
			curl_setopt($curl_instances[$i], CURLOPT_HEADER, true);

			if ($cookies)
				curl_setopt($curl_instances[$i], CURLOPT_COOKIE, $cookies);
			
			if ($post) {
				curl_setopt($curl_instances[$i], CURLOPT_POST, 1);
				curl_setopt($curl_instances[$i], CURLOPT_POSTFIELDS, $post);
			}

			curl_setopt($curl_instances[$i], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_instances[$i], CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl_instances[$i], CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl_instances[$i], CURLOPT_TIMEOUT, 30);
			curl_setopt($curl_instances[$i], CURLOPT_CONNECTTIMEOUT, 20);
			curl_multi_add_handle ($mh, $curl_instances[$i]);

		}

		$running = null;

		# execute all operations
		# this loop will exit after all links have been fetched
		do {
			curl_multi_exec ($mh, $running);
		}
		while ($running > 0);

		# get fetched data
		foreach ($curl_instances as $i => $instance) {
			$result[$i] = curl_multi_getcontent ($instance);
			curl_multi_remove_handle ($mh, $instance);
		}

		curl_multi_close ($mh);

		# merge the output into $collect_data
		$collect_data = array_merge($collect_data, $result);
	}

	# return scanned result
	return $originalCount === 1 ? $collect_data[0] : $collect_data;
}


?>
