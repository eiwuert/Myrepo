<?php

// Rpc_1 will follow redirects with a 307 status code
header('HTTP/1.1 307');

header('Location: server.php');

?>
