<?php
/*
Template: Session Ajax Call
Description: An ajax post url for determining when a session times out from the client side
*/

include(TEMPLATES_PATH.'customer.func.php'); 
 
if(!checkSession())
{
    //expired
    echo "-1";
    session_destroy();
}
else
{
    //not expired
    echo "1";
}
?>