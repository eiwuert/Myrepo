<?php

require_once(ECASH_COMMON_DIR . 'ecash_api/interest_calculator.class.php');
require_once(COMMON_LIB_DIR . 'pay_date_calc.3.php');
require_once(SERVER_CODE_DIR . 'module_interface.iface.php');
require_once(SQL_LIB_DIR . 'util.func.php');
require_once(SQL_LIB_DIR . 'scheduling.func.php');
require_once(LIB_DIR . 'Payment_Card.class.php');

class API_View_Card implements Module_Interface
{
	public function __construct(Server $server, $request, $module_name) 
	{
		$this->request = $request;
		$this->server = $server;
		$this->name = $module_name;
        $this->permissions = array(
            array('loan_servicing'),
            array('funding'),
            array('collections'),
            array('fraud'),
        );
	
	}
	
	public function get_permissions()
	{
		return $this->permissions; 
	}

	public function Main() 
	{
		$input = $this->request->params[0];

		switch ($input->action)
		{
			case 'view_card':
				// Log the view
				try
				{
					$card_action = ECash::getFactory()->getModel('CardAction');
					$card_action->loadBy(array('name_short' => 'view'));

					$card_action_history = ECash::getFactory()->getModel('CardActionHistory');
					$card_action_history->date_created   = time();
					$card_action_history->card_action_id = $card_action->card_action_id;
					$card_action_history->card_info_id   = $this->request->params[0]->card_id;
					$card_action_history->application_id = $this->request->params[0]->application_id;
					$card_action_history->agent_id       = ECash::getAgent()->getModel()->agent_id;
					$card_action_history->save();

					// Return the card as JSON
					// Fetch Payment Card Info.
					$card_info = ECash::getFactory()->getModel('CardInfo');

					// This is needed for decrypting each card number
					$card_info->loadBy(array('card_info_id' => $this->request->params[0]->card_id));

					$CardType = ECash::getFactory()->getModel('CardType');
					$CardType->loadBy(array('card_type_id' => $card_info->card_type_id));

					// This is used for the edit layer, because we have links for each one, it's not like
					// the rest of the screens.
					$json_card_data = array();

					// Make a variable for JSON usage in the edit layer
					$json_card_data = array(
							'card_info_id'         => $card_info->card_info_id,
							'card_number'          => Payment_Card::decrypt($card_info->card_number),
							'card_type_id'         => $card_info->card_type_id,
							'active_status'        => $card_info->active_status,
							'card_type_name_short' => $card_type->name_short,
							'card_type_name'       => $card_type->name,
							'cardholder_name'      => Payment_Card::decrypt($card_info->cardholder_name),
							'expiration_month'     => date('m', strtotime($card_info->expiration_date)),
							'expiration_year'      => date('Y', strtotime($card_info->expiration_date)),
							'formatted_number'     => Payment_Card::Format_Payment_Card(Payment_Card::decrypt($card_info->card_number), FALSE),
							'error'                => '',
							);

					$data = JSON_Encode($json_card_data);
				}
				catch (Exception $e)
				{
					$data = JSON_Encode(array('error' => 'Could not load card information'));
				}

				break;
			default:
				throw new Exception("Unknown action {$input->action}");
		}
		return $data;
	}
}

?>
