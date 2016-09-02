<?
require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

class Display_View extends Display_Parent
{
	public function __construct(Transport $transport)
	{
		$this->send_display_data = FALSE;
		
		$this->transport = $transport;
	
		$data = $this->transport->Get_Data();
		
		if ($data['file_data'])
		{
			header('Content-type: text/plain');
    		header('Content-Disposition: attachment; filename="'.$data['file_name'].'"');
    		print($data['file_data']);
    		exit(); 
		}
		else 
		{
			$this->transport->Add_Notice('There was no file data retreived for download.');	
		}
		
	}
}

?>