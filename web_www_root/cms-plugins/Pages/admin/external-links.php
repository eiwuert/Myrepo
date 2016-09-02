<?php
	ob_end_clean();
	header("content-type: application/x-javascript");
	
	global $lumRegistry;
	$sql = "select * from lum_pages lp left join lum_pages_localized lpl on lpl.page_id = lp.page_id where is_include = 0 and status = 1 order by name asc";
	$rows = $lumRegistry->db->getRows($sql, null, true);
	$pages = array();
	foreach ($rows as $row)
	{
		$pages[] = array('name'=>$row['name'], 'url'=>lum_call('Pages', 'getPermalink', $row));
	}
	
	if (count($pages) > 0)
	{
		echo 'var tinyMCELinkList = new Array(';
		$c = 0;
		foreach ($pages as $page)
		{
			if ($c > 0)
				echo ',';
			echo '["'.$page['name'].'", "'.$page['url'].'"]';
			
			$c++;
		}
		echo ');';
	}
?>

	

<?php
	exit;
?>