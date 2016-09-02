<?php

        /*****************************************************/
        // Cronjob for Landing Pages
        // runs every hour, sends data to teleweb from short-form (A)
        // subscribers who did not complete the long_form (B)
        // - matt piper (matt.piper@thesellingsource.com), 2-29-2005
	//
        // - david bryant (david.bryant@thesellingsource.com), 02-18-2005
        // based on code written by
        // - myya perez(myya.perez@thesellingsource.com), 02-02-2005
        /*****************************************************/

        define ("TEST_MODE", true);

        // Create function teleweb_followup
        /*****************************************************/

        function teleweb_followup ($landing_page, $landing_page_id, $tw_promo_id="10000", $tw_promo_sub_code="")
        {
                // Includes/Defines
                include_once('/virtualhosts/lib/mysql.3.php');

                // Initialize
                $start = date("Y-m-d H:i:s", strtotime("-15 minutes"));
                $end = date("Y-m-d H:i:s", strtotime("-0 minutes"));

                $lp_db = TEST_MODE ? "rc_lp" : "lp";
                $tw_db = TEST_MODE ? "rc_teleweb" : "teleweb";

                $sql    =       new MySQL_3();

                //Grab Data
                $sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

                $query = "
                        SELECT
                                session_id,
                                first_name,
                                last_name,
                                email,
                                home_phone
                        FROM
                                visitors
                        WHERE
                                created_date BETWEEN '$start' AND '$end'
                                AND last_page_completed = 'A'
				AND teleweb = 'N'
                                AND landing_page_id = ".$landing_page_id."
                        ";
		//echo $query;
                $lp_result = $sql->Query($lp_db, $query, Debug_1::Trace_Code(__FILE__,__LINE__));


                if ($sql->Row_Count ($lp_result) > 0)
                {
                        $tw_sql = new MySQL_3();
                        $tw_sql->Connect("BOTH", "selsds001", "sellingsource", "%selling\$_db", Debug_1::Trace_Code(__FILE__,__LINE__));

                        // send the data off to the teleweb database
                        while ($row = $sql->Fetch_Object_Row($lp_result))
                        {
                                $first_name = $row->first_name;
                                $last_name = $row->last_name;
                                $email = $row->email;
                                $home_phone = $row->home_phone;


                                $tw_query = "
                                        SELECT
                                                landing_page_id as tw_landing_page_id
                                        FROM
                                                landing_page
                                        WHERE
                                                lp_map_id = ".$landing_page_id."
                                ";
                                $tw_result = $tw_sql->Query($tw_db, $tw_query, Debug_1::Trace_Code(__FILE__,__LINE__));

                                $tw_row = $sql->Fetch_Object_Row($tw_result);

                                if ($tw_row)
                                {
                                  $tw_landing_page_id = $tw_row->tw_landing_page_id;
                                }

                                $tw_query  = "INSERT INTO customer SET ";
                                // this will probably change:
                                $tw_query .= "project_id=24, ";
                                $tw_query .= "landing_page_id=" . $tw_landing_page_id . ", ";
                                $tw_query .= "company_customer_id='".$row->session_id."', ";
                                $tw_query .= "created_date=NOW(), ";
                                $tw_query .= "url='".$landing_page."', ";
                                $tw_query .= "first_name='".$row->first_name."', ";
                                $tw_query .= "last_name='".$row->last_name."', ";
                                $tw_query .= "email='".$row->email."', ";
                                $tw_query .= "home_phone='".$row->home_phone."'";

                                $tw_result = $tw_sql->Query($tw_db, $tw_query, Debug_1::Trace_Code(__FILE__,__LINE__));
                                //echo $tw_query . "\n\n";
                        }
                }

		$query_update = "
			UPDATE
				visitors
			SET
				teleweb = 'Y'
			WHERE
				created_date BETWEEN '$start' AND '$end'
                                AND last_page_completed = 'A'
				AND teleweb = 'N'
                                AND landing_page_id = ".$landing_page_id."
			";
		$result = $sql->Query($lp_db, $query_update, Debug_1::Trace_Code(__FILE__,__LINE__));

        }


        // Call function teleweb_followup for each Landing Page
        /*****************************************************/

        teleweb_followup ("https://greatweboffers.com/trimpatch", 2);

        teleweb_followup ("https://familyhs.com", 6);
        teleweb_followup ("https://roadsideassurance.com", 7);
        teleweb_followup ("https://greatweboffers.com/boostex", 8);
        teleweb_followup ("https://greatweboffers.com/thevpatch", 9);


?>
