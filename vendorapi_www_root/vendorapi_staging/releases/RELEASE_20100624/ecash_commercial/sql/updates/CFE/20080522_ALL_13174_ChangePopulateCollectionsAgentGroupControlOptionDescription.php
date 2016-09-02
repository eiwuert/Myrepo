<?php
/*
	just php <thisfile>
	
	This applies this change to ALL CFE companies (Including Agean)

Company: generic
Checking whether local DB has been updated:                     [Already Done]
Checking whether rc DB has been updated:                        [Already Done]
Checking whether live DB has been updated:                      [Not Yet]
*/

class ECash_Update
{	
	protected $db;

	function __construct()
	{
		// Some Company Name
		$this->db['scn']['local']['user'] = "username";
		$this->db['scn']['local']['pass'] = "password";
		$this->db['scn']['local']['host'] = "servername";
		$this->db['scn']['local']['port'] = "3309";
		$this->db['scn']['local']['name'] = "datbase";

		$this->db['scn']['local']['obj']  = mysqli_connect(
				$this->db['scn']['local']['host'],
				$this->db['scn']['local']['user'],
				$this->db['scn']['local']['pass'],
				$this->db['scn']['local']['name'],
				$this->db['scn']['local']['port']);

		// RC
		$this->db['scn']['rc']['user'] = "username";
		$this->db['scn']['rc']['pass'] = "password";
		$this->db['scn']['rc']['host'] = "servername";
		$this->db['scn']['rc']['port'] = "3309";
		$this->db['scn']['rc']['name'] = "datbase";

		$this->db['scn']['rc']['obj']  = mysqli_connect(
				$this->db['scn']['rc']['host'],
				$this->db['scn']['rc']['user'],
				$this->db['scn']['rc']['pass'],
				$this->db['scn']['rc']['name'],
				$this->db['scn']['rc']['port']);

		// Live
 		$this->db['scn']['live']['user'] = "username";
		$this->db['scn']['live']['pass'] = "password";
		$this->db['scn']['live']['host'] = "servername";
		$this->db['scn']['live']['port'] = "3309";
		$this->db['scn']['live']['name'] = "datbase";

		$this->db['scn']['live']['obj']  = mysqli_connect(
				$this->db['scn']['live']['host'],
				$this->db['scn']['live']['user'],
				$this->db['scn']['live']['pass'],
				$this->db['scn']['live']['name'],
				$this->db['scn']['live']['port']);
	}

	function checkConnections($return_on_failure = FALSE)
	{
		echo "\nChecking Connections\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				echo "Checking whether {$rc_lvl} DB connection succeeded:";

				if ($this->db[$company][$rc_lvl]['obj'] != FALSE)
					echo "\t\t\t[Connected to {$this->db[$company][$rc_lvl]['name']}]\n";
				else
				{
					echo "\t\t\t[Failed]\n";

					if ($return_on_error)
						return FALSE;
				}
			}
		}

		return TRUE;
	}

	function checkApplied($return_if_applied = FALSE)
	{
		echo "\nChecking if updates have already been applied\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				echo "Checking whether {$rc_lvl} DB has been updated:";

				$mysqli = $var_array['obj'];

//////////////////////////////////////////////////////////////////////////////////////////////////
				$query = "SELECT * FROM control_option WHERE name='Populate Collections Agent' AND description='The agents associated with this feature will be visible as a collections agent in reports which list collections agents.'";

				$res = $mysqli->query($query);

				// This should be equal to 2 when the update is applied
				if ($res->num_rows == 1)
				{
					echo "\t\t\t[Already Done]\n";
					$this->db[$company][$rc_lvl]['applied'] = true;

					if ($return_if_applied)
						return FALSE;
				}
				else
				{
					echo "\t\t\t[Not Yet]\n";
					$this->db[$company][$rc_lvl]['applied'] = false;
				}
//////////////////////////////////////////////////////////////////////////////////////////////////
			}
		}

		return TRUE;
	}

	function applyChanges($return_on_error = FALSE)
	{
		echo "\nApplying Updates\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				if ($this->db[$company][$rc_lvl]['applied'] == true)
					continue; // Skip already applied updates

				echo "Applying update to {$rc_lvl}:";

				$mysqli = $var_array['obj'];

//////////////////////////////////////////////////////////////////////////////////////////////////
				// Get Parent IDs
				$query = "UPDATE control_option SET description='The agents associated with this feature will be visible as a collections agent in reports which list collections agents.' WHERE name='Populate Collections Agent';";

				$res = $mysqli->query($query);

				if ($mysqli->affected_rows == 1)
				{
					$this->db[$company][$rc_lvl]['applied'] = true;
					echo "\t\t\t[Success]\n";
					continue;
				}
				else
				{
					$this->db[$company][$rc_lvl]['applied'] = false;
					echo "\t\t\t[FAILURE]\n";
					continue;
				}

			}
		}

		return TRUE;

	}


	function rollbackChanges($return_on_error = FALSE)
	{
		echo "\nRolling Back Updates\n\n";

		foreach ($this->db as $company => $rc_lvl_array)
		{
			echo "Company: $company\n";

			foreach ($rc_lvl_array as $rc_lvl => $var_array)
			{
				if ($var_array['applied'] != true)
					continue;

				$mysqli = $var_array['obj'];

				echo "Rolling back update to {$rc_lvl}:";

//////////////////////////////////////////////////////////////////////////////////////////////////
				$query = "UPDATE control_option SET description='The agents associated with this feature will be visible as a collections agent.' WHERE name='Populate Collections Agent';";

				$res = $mysqli->query($query);

				if ($mysqli->affected_rows == 1)
				{
					$this->db[$company][$rc_lvl]['applied'] = true;
					echo "\t\t\t[Success]\n";
					continue;
				}
				else
				{
					$this->db[$company][$rc_lvl]['applied'] = false;
					echo "\t\t\t[FAILURE]\n";
					continue;
				}
//////////////////////////////////////////////////////////////////////////////////////////////////
			}
		}
	}

}

$update = new ECash_Update();

$update->checkConnections(TRUE);
$update->checkApplied(FALSE);
$update->applyChanges(TRUE);
//$update->rollbackChanges();
?>
