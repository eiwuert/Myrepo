<?php
/**
 * This is a simple script to import the tokens from a document to
 * a condor database.
 * 
 * @author Brian Feaver
 * @copyright Copyright 2006 The Selling Source, Inc.
 */

include_once('template_parser.1.php');
include_once('automode.1.php');
include_once('mysqli.1.php');
include_once('mysql_pool.php');

define(FILE_NAME, '/tmp/legal_documents.html');




	class Token_Importer
	{
		const TOKEN_ID = '%%%';
		
		private $type;
		private $mode;
		private $company;
		
		private $query;
		private $tokens;
		
		public function __construct($type, $mode, $company)
		{
			$this->type = $type;
			$this->mode = $mode;
			$this->company = $company;
			
			$this->query = '';
			$this->tokens = array();
		}
		
		public function Find_Tokens()
		{
			switch($this->type)
			{
				case 'DB':
				{
					$sql = MySQL_Pool::Connect("CONDOR_{$this->mode}");
			
					$result = $sql->Query("SELECT data FROM template WHERE company_id = '{$this->company}' AND status = 'ACTIVE'");
					$count = 0;
					while($row = $result->Fetch_Array_Row())
					{
						$token_list = $this->Parse_Tokens($row['data']);
			
						$this->tokens = array_merge($this->tokens, $token_list);
					}
					
					break;
				}
				
				case 'FILE':
				{
					$base_file = file_get_contents(FILE_NAME);
					$this->tokens = $this->Parse_Tokens($base_file);
					
					break;
				}
			}
			
			$this->tokens = array_unique($this->tokens);
		}
		
		
		

		private function Parse_Tokens($data)
		{
			$token_list = array();
		
			if(!empty($data))
			{
				$template_parser = new Template_Parser($data, self::TOKEN_ID);
				$token_list = array_unique($template_parser->Get_Tokens(true));
			}
			
			return $token_list;
		}
		
		
		public function Insert_Tokens()
		{
			$count = 0;
			if(!empty($this->tokens))
			{
				$sql = MySQL_Pool::Connect('CONDOR_ADMIN');
			
				$this->query = 'REPLACE INTO tokens (token, description, date_created, company_id) VALUES ';
				
				$values = array();
				foreach($this->tokens as $token)
				{
					$values[] = "('{$token}', 'Auto Generated Token', NOW(), {$this->company})";
				}
				
				$this->query .= implode(',', $values);

				try
				{
					$sql->Query($this->query);
				}
				catch(Exception $e)
				{
					die($e->getMessage());
				}
				
				$count = $sql->Affected_Row_Count();
			}
			
			return $count;
		}
		
		
		
		public function Get_Companies()
		{
			$sql = MySQL_Pool::Connect('CONDOR_ADMIN');
			
			$result = $sql->Query("SELECT company_id, name FROM company WHERE active_status = 'active'");
			
			$companies = array();
			
			while($row = $result->Fetch_Array_Row())
			{
				$companies[] = $row;
			}
			
			return $companies;
		}
		

		public function Get_Query()
		{
			return $this->query;
		}
	}




$type = null;
$mode = 'RC';
$company = null;
//Determine whether to use command line or web interface
if(isset($argc) && $argc > 3)
{
	$type = strtoupper($argv[1]);
	$mode = strtoupper($argv[2]);
	$company = intval($argv[3]);
}
elseif(isset($_GET['type']) && isset($_GET['mode']) && isset($_GET['company']))
{
	$type = strtoupper($_GET['type']);
	$mode = strtoupper($_GET['mode']);
	$company = intval($_GET['company']);
}

$get_results = true;
if(is_null($type) || is_null($mode) || is_null($company)
	|| !in_array($type, array('DB', 'FILE')) || !in_array($mode, array('LOCAL', 'RC', 'LIVE')))
{
	$get_results = false;
}


// Database definitions
MySQL_Pool::Define('CONDOR_LOCAL', 'monster.tss', 'condor', 'andean', 'condor', 3311);
MySQL_Pool::Define('CONDOR_RC', 'db101.clkonline.com', 'condor', 'andean', 'condor', 3313);
MySQL_Pool::Define('CONDOR_LIVE', 'writer.condor2.ept.tss', 'condor', 'flyaway', 'condor', 3308);

$def = MySQL_Pool::Get_Definition('CONDOR_' . $mode);
MySQL_Pool::Define('CONDOR_ADMIN', $def['host'], $def['username'], $def['password'], 'condor_admin', $def['port']);


	$importer = new Token_Importer($type, $mode, $company);
	$companies = $importer->Get_Companies();
	
if($get_results)
{
	$importer->Find_Tokens();
	
	$count = $importer->Insert_Tokens();
	$query = $importer->Get_Query();
}
//echo "Affected Rows: $count";


function selected($a, $b)
{
	return ($a == $b) ? ' selected="selected"' : '';
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Token Importer</title>

		<style type="text/css">
		</style>
	</head>

	<body>
	
		<form action="" method="get">
			<label>Type:
				<select name="type">
					<option value="db"<?php echo selected($type, 'DB') ?>>Database</option>
					<option value="file"<?php echo selected($type, 'FILE') ?>>File</option>
				</select>
			</label>
			
			<label>Mode:
				<select name="mode">
					<option value="local"<?php echo selected($mode, 'LOCAL') ?>>Local</option>
					<option value="rc"<?php echo selected($mode, 'RC') ?>>RC</option>
					<option value="live"<?php echo selected($mode, 'LIVE') ?>>Live</option>
				</select>
			</label>
			
			<label> Company:
				<select name="company">
				<?php foreach($companies as $c): ?>
					<option value="<?php echo $c['company_id']?>"<?php echo selected($company, $c['company_id']) ?>><?php echo $c['name'] ?></option>
				<?php endforeach; ?>
				</select>
			</label>
			
			<input type="submit" />
		</form>
	
		<?php if($get_results): ?>
		<div class="results">
			<p>Affected Rows: <?php echo $count ?></p>
			<p>Query Run: <pre><?php echo str_replace('),(', ")\n(", $query) ?></pre></p>
		</div>
		<?php endif; ?>
	</body>
</html>
