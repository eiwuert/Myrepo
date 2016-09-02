<?php
/**
 * This class retrieves the city, country and coordination for a given IP address
 *
 * @author Mel Leonard
 * @version 1.0.0	
 *
 *e.g. 
 *$get_ip_location = new IP_Location("24.244.159.34");
 *$city = $get_ip_location->parse_city();
 *$country = $get_ip_location->parse_country();
 *$coordinates = $get_ip_location->parse_coordinates();
 *
 */

class IP_Location 
{
	public $ip;
	public $content;
	private $url;
	private $ip_location;
	
	
	function __construct($ip)
	{
		$this->ip = $ip;
		$this->url = "http://api.hostip.info";	
		$this->content = "ip=" . $this->ip;
		$this->ip_location = $this->http_get();
	}

	public function parse_city() 
	{
		$contents = $this->get_tag_contents($this->ip_location, "Hostip" );
		$city = trim($this->get_tag_contents($contents, "gml:name"));

		// If the city couldn't be found, it'll contain the word "private".
		if( stristr($city, "private")) 
		{
			$city = "not found";
		}
	
		return $city;
	}

	public function parse_country() 
	{
		$contents = $this->get_tag_contents($this->ip_location, "Hostip" );
		$country = trim($this->get_tag_contents($contents, "countryAbbrev"));
	
		// If the country couldn't be found, return "not found"
		if(stristr($country, "xx")) 
		{
			$country = "not found";
		}
	
		return $country;
	}

	public function parse_coordinates() 
	{
		$contents = $this->get_tag_contents($this->ip_location, "ipLocation" );
		$coordinates = trim($this->get_tag_contents($contents, "gml:coordinates"));
	
		return $coordinates;
	}
	
	private function get_tag_contents($xml, $tag)
	{
		$tag_contents = "";
  	$start_tag = "<$tag>";
  	$start_offs = strpos($xml, $start_tag);

  	// If we found a starting offset, then look for the end-tag.
		if($start_offs >= 0) 
		{
			$end_tag = "</$tag>";
			$end_offs = strpos($xml, $end_tag, $start_offs);
	
			// If we have both tags, then dig out the contents.
			//
			if( $end_offs >= 0 ) 
			{
				$start = $start_offs + strlen($start_tag);
				$length = $end_offs - $start_offs - strlen($end_tag) + 1;
				$tag_contents = substr($xml, $start, $length);
			}
		}
  	return $tag_contents;
	}

	private function http_get() 
	{
		//get the xml package that contains the IP information
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 3);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->content);
		$result = curl_exec($ch);
		/*
		$request = fopen($this->url, "rb");
		$result = "";
	
		while(!feof($request)) 
		{
			$result .= fread($request, 8192);
		}
	
		fclose($request);
		*/
	
		return $result;
	}
}

?>