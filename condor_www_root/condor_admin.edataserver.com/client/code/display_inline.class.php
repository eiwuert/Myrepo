<?php

require_once(DIR_LIB . "data_format.1.php");
require_once(COMMON_LIB_ALT_DIR . "filter.manager.php");
require_once(CLIENT_CODE_DIR . "display.iface.php");

class Display_Inline implements Display
{
	public function Do_Display(Transport $transport)
	{
		$next_level = $transport->Get_Next_Level();
		
		$data = $transport->Get_Data();
		
		switch($next_level)
		{
			case "templates_preview":
				if (!isset($data->preview_pdf))
				{
					require_once(SERVER_CODE_DIR . "condor_template_query.class.php");
					$s	 = new Server();
					$ctq = new Condor_Template_Query($s);

					$tokens = $ctq->Fetch_Token_Test_Data();
					$temp_array = explode('%%%', $data->template->data);

					for ($i=1; $i < count($temp_array); $i++)
					{
						if ($tokens["%%%{$temp_array[$i]}%%%"]->test_data_type === 'image')
						{
							$temp_array[$i] = "<img src=\"" .$tokens["%%%{$temp_array[$i]}%%%"]->test_data. "\" alt=\"condor_image\" />";
						}
						else if (($tokens["%%%{$temp_array[$i]}%%%"]->test_data_type === 'text')
							&& (!empty($tokens["%%%{$temp_array[$i]}%%%"]->test_data)))
						{
							$temp_array[$i] = $tokens["%%%{$temp_array[$i]}%%%"]->test_data;
						}
						else
						{
							$temp_array[$i] = "%%%{$temp_array[$i]}%%%";
						}
						
						$i++;
					}

					$html = implode($temp_array);
				}
				else
				{
					header("Content-Type: application/pdf");
					$filter = new Filter_Manager();
					$data->template->data = "<html><body>{$data->template->data}</body></html>";
					$html = $filter->Transform(
						$data->template->data,
						Filter_Manager::INPUT_HTML,
						Filter_Manager::OUTPUT_PDF
					);
				}
				break;
			case "doc_view":
				if($data->document_obj->content_type == "application/pdf")
				{
                	$data_length = strlen($data->document_obj->data);
                	header( "Accept-Ranges: bytes\n");
                	header( "Content-Length: $data_length\n");					
					header('Content-Type: ' . $data->document_obj->content_type);
					$html = $data->document_obj->data;						
				}
				else
				{
					header('Content-Type: ' . $data->document_obj->content_type);
					$html = $data->document_obj->data;							
				}

				break;
			default:
				break;
		}
		
		echo $html;
	}
}
?>