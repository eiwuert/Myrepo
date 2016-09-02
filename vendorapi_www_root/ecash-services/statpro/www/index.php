<html>
	<head><title>StatPro Services</title></head>
	<body>
		<h1>StatPro Services</h1>
		This is a site for acccessing StatPro functionality via services
		<h2>Services</h2>
		<ul>
			<li>
				<h3>SOAP</h3>
				<p>SOAP based StatPro services are provided at the following WSDLs</p>
				<ul>
					<li><a href="<?php echo getUrl("StatProSoapApi.php?wsdl")?>">StatPro SOAP API Version 1</a></li>
					<li><a href="<?php echo getUrl("StatProSoapApi.php?wsdl&v=2")?>">StatPro SOAP API Version 2</a></li>
				</ul>
			</li>
			<li>
				<h3>JSON</h3>
				These services are accessed via a non-encapsulated JSON post
				<ul>
					<li>
						<h4>Event</h4>
						<ul>
							<li><b>Description:</b> JSON Post service for registering StatPro events</li>
							<li><b>End Point:</b> <?php echo getUrl("json/event")?></li>
							<li>
								<b>Post Entities:</b><br/>
								<table border="0" cellpadding="5px">
									<tr><td><b>Entity</b></td><td><b>Description</b></td></tr>
									<tr><td>bucket</td><td>Name of the StatPro bucket to write to.  It will also use the middle section of the bucket name to determine the user (e.g. spc_cust_test)</td></tr>
									<tr><td>pageId</td><td>WebAdmin1 Page ID for the StatPro space key definition</td></tr>
									<tr><td>promoId</td><td>WebAdmin1 Promo ID for the StatPro space key definition</td></tr>
									<tr><td>subCode</td><td>Promo sub-code for the StatPro space key definition</td></tr>
									<tr><td>track</td><td>Unique track identifier for tracking stats across spaces</td></tr>
									<tr><td>event</td><td>Name of the StatPro event</td></tr>
									<tr><td>date</td><td>Unix time stamp representing the time the stat was hit</td></tr>
								</table>
							</li>
						</ul>
					</li>
					<li>
						<h4>Pixel</h4>
						<ul>
							<li><b>Description:</b> JSON Post service for firing pixles via StatPro</li>
							<li><b>End Point:</b> <?php echo getUrl("json/pixel")?></li>
							<li>
								<b>Post Entities:</b><br/>
								<table border="0" cellpadding="5px">
									<tr><td><b>Entity</b></td><td><b>Description</b></td></tr>
									<tr><td>bucket</td><td>Name of the StatPro bucket to write to.  It will also use the middle section of the bucket name to determine the user (e.g. spc_cust_test)</td></tr>
									<tr><td>pixelURL</td><td>URL at which the pixel is located</td></tr>
									<tr><td>date</td><td>Unix time stamp representing the time the stat was hit</td></tr>
								</table>
							</li>
						</ul>
					</li>
				</ul>
			</li>
			
		</ul> 
	</body>
</html>
<?php
function getUrl($uri) {
	$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
	$url = sprintf('%s://%s/%s', $protocol, $_SERVER['HTTP_HOST'], $uri);
	return $url; 
}