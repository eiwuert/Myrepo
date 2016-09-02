<?php

	function buildMenuItem($row)
	{
		$unmovable = '';
		if ($row['page_id'] == 1)
			$unmovable = 'class="unmovable no-nest"';
			
		$item = '<li id="page_'.$row['page_id'].'"'.$unmovable.'><div>'.$row['name'].'</div>';
		if (count($row['children']) > 0)
		{
		    $item .= '<ol>';
		    foreach ($row['children'] as $child)
			$item .= buildMenuItem($child);
		    $item .= '</ol>';
		}
		
		$item .= '</li>';
		return $item;
	}

	if (lum_requirePermission('Pages\Edit')) :
	?>
		<div id="plugin-header">
			<h1>Site Structure</h1>

		<p>To change the structure of the site simply drag and drop pages where you want them. Then click the 'Save' button to commit the changes<br/><br/>
		<input type="button" name="save" value="Save" class="save_structure"/></p>

		<hr/>
		<div style="padding: 10px; border: 1px solid #bdc4cb; margin: 20px;">
			<p><b>Pages Not Menu</b></p>
			<ol id="not_on_menu" class="sortable connectedSortable">
		<?php
			$not_on_menu = lum_call('Pages', 'getMenu', array('page_id'=>1, 'include_root'=>true, 'show_on_menu'=>'0'));
			if ($not_on_menu)
			{
				$html = "";
				foreach ($not_on_menu as $row)
				{
					$html .= buildMenuItem($row);
				}
				echo $html;
			}
		?>
			</ol>
		<div style="clear: both;"></div>
		</div>
		<div style="padding: 10px; border: 1px solid #bdc4cb; margin: 20px;">
		<p><b>Pages on Menu</b></p>
		<ol id="structure" class="sortable connectedSortable">
	<?php
		echo '';

		
		$menu = lum_call('Pages', 'getMenu', array('page_id'=>1, 'include_root'=>true, 'show_on_menu'=>'1'));
		if ($menu)
		{
			$html = "";
	
			foreach ($menu as $row)
			{
				$html .= buildMenuItem($row);
			}
			echo $html;
		}
		?>	
		</ol><br/>
		</div>
		</div>
		<?php
	  
	endif;

?>