<?

##
## $data array expects the following fields
##
## name_first
## name_last
## email
## gender
## phone_home
## phone_work
## phone_work_ext
## phone_mobile
## address_1
## address_2
## city
## state
## zip
## ip_address
## url
## lead_date
## promo_id
## promo_sub_code
##

class aroi_post {
	
	function post_data($mode, $data) {
	
		switch( $mode ) {
			case MODE_LIVE:
			case "LIVE":
				$url = "http://datafeed.lpdataserver.com";
				break;
				
			default:
				$url = "http://rc.datafeed.lpdataserver.com";
				break;
		}
		
		$post_data = "process_page=remote";
		foreach($data as $key => $val) {
			$post_data .= "&".$key."=".$val;
		}
			
		$curl = curl_init();
		curl_setopt( $curl, CURLOPT_URL, $url );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $curl, CURLOPT_POST, 1 );
		curl_setopt( $curl, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 30 );
		$result = curl_exec( $curl );
		
		$response['post_url'] = $url;
		$response['post_data'] = $post_data;
		$response['response'] = $result;
		return $response;
		
	}
	
}


?>