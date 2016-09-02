<?php
/*
	just php <thisfile>

*/

class ECash_Update
{	
	protected $db;

	function __construct()
	{
		// Local
		$this->db['scn']['local']['user'] = "ecash";
		$this->db['scn']['local']['pass'] = "lacosanostra";
		$this->db['scn']['local']['host'] = "monster.tss";
		$this->db['scn']['local']['port'] = "3309";
		$this->db['scn']['local']['name'] = "ldb_generic";

		$this->db['scn']['local']['obj']  = mysqli_connect(
				$this->db['scn']['local']['host'],
				$this->db['scn']['local']['user'],
				$this->db['scn']['local']['pass'],
				$this->db['scn']['local']['name'],
				$this->db['scn']['local']['port']);

		// RC
		$this->db['scn']['rc']['user'] = "ecash";
		$this->db['scn']['rc']['pass'] = "lacosanostra";
		$this->db['scn']['rc']['host'] = "monster.tss";
		$this->db['scn']['rc']['port'] = "3309";
		$this->db['scn']['rc']['name'] = "ldb_generic";

		$this->db['scn']['rc']['obj']  = mysqli_connect(
				$this->db['scn']['rc']['host'],
				$this->db['scn']['rc']['user'],
				$this->db['scn']['rc']['pass'],
				$this->db['scn']['rc']['name'],
				$this->db['scn']['rc']['port']);


		// Live
		$this->db['scn']['live']['user'] = "ecash";
		$this->db['scn']['live']['pass'] = "lacosanostra";
		$this->db['scn']['live']['host'] = "monster.tss";
		$this->db['scn']['live']['port'] = "3309";
		$this->db['scn']['live']['name'] = "ldb_generic";

		$this->db['scn']['rc']['obj']  = mysqli_connect(
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
