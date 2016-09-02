<?php
        /**
         * A cheesy little utility to decrypt passwords.
         * @author Brian Ronald <brian.ronald@sellingsource.com>
         */

        require_once(dirname(__FILE__) . "/../lib/security.php");

        if($argc < 2 || empty($argv[1]))
        {
                echo "Condor Pop Mail Password Decrypt Utility\n";
                echo "This utility will decrypt the supplied password\n";
                echo "Usage: " . $argv[0] . " [password] \n\n";
                exit;
        }

        $new_password = Security::Decrypt($argv[1]);
        echo "The decrypted password is : $new_password\n";

?>

