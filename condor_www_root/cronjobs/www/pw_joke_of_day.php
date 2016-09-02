<?php
	// Partner Weekly joke of day cronjob
	include_once ("/virtualhosts/lib/mysql.3.php");
	include_once ("/virtualhosts/lib/prpc/client.php");
	/*
	*/

	$joke = new stdClass ();
	$joke->day = date ("Y-m-d");

	$db_host = "selsds001";
	$db = "pw_visitor";
	$joke_db = "jokes";
	$site_url = "http://www.thegameplayer.com";
	$sql = new MySQL_3 ();
	$sql->Connect ('', $db_host, 'sellingsource', 'password');

	// We've been getting an ugodly number of bounced emails.  This
	// query checks the master_remove_email table in oledirect2
	// for the email address and sets the joke of the day flag to
	// "N" if it appears.  (Bouncebacks go into that table as well
	// as "Do Not Call"s)

	$query  = "UPDATE pw_visitor.thegameplayer, oledirect2.master_remove_email ";
	$query .= "SET pw_visitor.thegameplayer.joke_of_the_day = \"N\" ";
	$query .= "WHERE pw_visitor.thegameplayer.joke_of_the_day = \"Y\" AND ";
	$query .= "pw_visitor.thegameplayer.email = oledirect2.master_remove_email.mre_email";
	$result = $sql->Query ($db, $query);

	// The script is bogging down due to the sheer number of queries.
	// We're splitting them into four sections to be run once an
	// hour starting at 1:15 AM, with a cutoff time of 1:00 AM
	// to prevent miscalculations because of incoming records.
	$cutoff_time = "created_date < '".date ("Ymd")."010001'";
	$query = "SELECT COUNT(*) as num_recips FROM thegameplayer WHERE joke_of_the_day = \"Y\" AND ".$cutoff_time;
	$result = $sql->Query ($db, $query);
	$data_chunk = "";
	if ($row = $sql->Fetch_Object_Row ($result))
	{
		$boundary = floor ($row->num_recips / 4);
		$data_chunk = " AND ".$cutoff_time;
		switch (date ("G"))
		{
			case "1":
				$data_chunk .= " LIMIT ".$boundary;
				break;
			case "2":
				$data_chunk .= " LIMIT ".$boundary.", ".$boundary;
				break;
			case "3":
				$data_chunk .= " LIMIT ".($boundary*2).", ".$boundary;
				break;
			case "4":
			default:
				$data_chunk .= " LIMIT ".($boundary*3).", ".($boundary + 10); // insurance
				break;
		}
	}

	$query = "SELECT first_name, last_name, email FROM thegameplayer WHERE joke_of_the_day = \"Y\"".$data_chunk;
	$result = $sql->Query ($db, $query);

	$joker = array ();

	while ($obj = $sql->Fetch_Object_Row ($result))
	{
		$joker[] = $obj;
	}

	if (count ($joker) > 0)
	{
		$query = "SELECT id, joketitle, firstline, joke FROM jokes WHERE jokedate = '".$joke->day."' LIMIT 1";
		$result = $sql->Query ($joke_db, $query);
		if ($sql->Row_Count ($result) > 0)
		{
			// YAY!  Use it!
			$row = $sql->Fetch_Object_Row ($result);
			$joke->id = $row->id;
			$joke->title = $row->joketitle;
			$joke->firstline = $row->firstline;
			$joke->joke = $row->joke;
		}
		else
		{
			// Damn.  Have to find a joke now.  This block should only run once per day.
			$joke_id_array = array ();
			// figure out which joke categories aren't going to cause heart attacks
			$safe_categories = array ();
			$query = "SELECT category1 AS safe_cat FROM jokes WHERE rating IN('G','PG') AND category1 != 'christmas' GROUP BY category1";
			$result = $sql->Query ($joke_db, $query);
			while ($row = $sql->Fetch_Object_Row ($result))
			{
				$safe_categories[] = $row->safe_cat;
			}
			shuffle ($safe_categories);
			// See if it's the holiday season (Nov. 25 - Dec. 25), and if so, limit the category to 'christmas'.
			// Otherwise, grab four of the safe categories at random and use them.
			$santa_clause =
					(date ("m") == "11" && intval (date ("d")) >= 25 )
				||
					(date ("m") == "12" && intval (date ("d") <= 25) )
				?
					" category1 = 'christmas' AND"
				:
					" category1 IN ('".$safe_categories[0]."','".$safe_categories[1]."','".$safe_categories[2]."','".$safe_categories[3]."') AND";
			// Limit the query to family-friendly-ish jokes that have not yet run this year.
			$query = "SELECT id FROM jokes
						WHERE".$santa_clause."
							rating IN ('G', 'PG')
						AND
							(ISNULL(jokedate) OR jokedate
							NOT BETWEEN '".date ("Y-m-d", strtotime ("-1 year"))."'
								AND '".date ("Y-m-d", strtotime ("-1 day"))."')";
			$result = $sql->Query ($joke_db, $query);
			// get a list of candidate jokes
			while ($row = $sql->Fetch_Object_Row ($result))
			{
				$joke_id_array[] = $row->id;
			}
			if (count ($joke_id_array) > 0)
			{
				// choose one of the candidate jokes at random
				shuffle ($joke_id_array);
				$query = "SELECT id, joketitle, firstline, joke FROM jokes WHERE id=".$joke_id_array[0];
				$result = $sql->Query ($joke_db, $query);
				if ($sql->Row_Count ($result) > 0)
				{
					$row = $sql->Fetch_Object_Row ($result);
					$joke->id = $row->id;
					$joke->title = $row->joketitle;
					$joke->firstline = $row->firstline;
					$joke->joke = $row->joke;
					// set the joke's date flag to today.
					$query = "UPDATE jokes SET jokedate='".$joke->day."' WHERE id=".$joke_id_array[0];
					$result = $sql->Query ($joke_db, $query);
				}
			}
		}

		foreach ($joker as $joke_recipient)
		{
			$data = array ();

			// required data fields:
			$data["email_primary"] = $joke_recipient->email;
			$data["email_primary_name"] = $joke_recipient->first_name." ".$joke_recipient->last_name;
			$data["site_name"] = "TheGamePlayer.com";

			// our custom data fields:
			$data["joke"] = $joke->joke;
			$data["date_str"] = date ("l F j, Y");

			$mail = new prpc_client ("prpc://smtp.3.soapdataserver.com/ole_smtp.1.php");
			$mail->setPrpcDieToFalse ();
			// second argument is the ole site id, which is found by viewing source in
			// ole add event dropdown list.  Yikes.
			$mailing_id = $mail->Ole_Send_Mail ("Joke", 35459, $data);
		}
	}

?>