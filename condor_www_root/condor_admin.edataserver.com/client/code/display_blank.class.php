<?
require_once(CLIENT_CODE_DIR . "display_parent.abst.php");

class Display_View extends Display_Parent
{
	public function __construct(Transport $transport)
	{
		$this->send_display_data = FALSE;
		
		$this->transport = $transport;
	
		$data = $this->transport->Get_Data();
		
    	print($data['data']);
    	exit(); 
	}
}

?>