<?php
	
	class Display_HTML_Table
	{
		
		protected $class_table;
		protected $class_td = '';
		protected $class_td_alt = 'alt';
		
		protected $display_total = TRUE;
		protected $default_css = TRUE;
		
		public function Render($report, $file = NULL)
		{
			
			$labels = $report->Labels();
			$data_sets = $report->Data();
			
			if ($this->default_css)
			{
				
				echo '
					<style>
						body { font-family: arial; }
						h1 { font-size: 18pt; color: #333333; }
						table { border: 1px solid #c0c0c0; font-size: 9pt; }
						th { background-color: #333333; color: #ffffff; }
						th, td { padding: 3px; border: 1px solid #c0c0c0; }
						td.alt { background-color: #eeeeee; }
					</style>
				';
				
			}
			
			$out = '
				<h1>'.$report->Title().'</h1>
				<table cellpadding="0" cellspacing="0" border="0">
			';
			
			$out .= '
				<tr>
					<th>&nbsp;</th>
					<th>'.implode('</th><th>', $labels).'</th>';
			
			if ($this->display_total)
			{
				$out .= '<th>Total</th>';
			}
			
			$out .= '</tr>';
			
			$alt = TRUE;
			
			foreach ($data_sets as $set)
			{
				
				$class = (($alt = !$alt) ? $this->class_td : $this->class_td_alt);
				if ($class) $class = 'class="'.$class.'"';
				
				$out .= '
					<tr>
						<td '.$class.'>'.$set['title'].'</th>
						<td '.$class.'>'.implode('</td><td '.$class.'>', $set['data']).'</td>';
						
				if ($this->display_total)
				{
					$out .= '<td '.$class.'>'.array_sum($set['data']).'</td>';
				}
						
				$out .= '</tr>';
				
			}
			
			$out .= '</table>';
			
			if ($file === NULL)
			{
				echo $out;
			}
			else
			{
				file_put_contents($file, $out);
			}
			
			return;
			
		}
		
	}
	
?>