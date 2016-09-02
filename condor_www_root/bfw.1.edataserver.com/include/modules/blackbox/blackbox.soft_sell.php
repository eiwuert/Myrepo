<?php

	require_once(OLP_DIR . 'payroll.php');
	require_once('pay_date_calc.1.php');

	class BlackBox_Soft_Sell
	{
		private $application_id;
		private $target;
		private $config;
		
		public function __construct($application_id, $target)
		{
			$this->application_id = intval($application_id);
			$this->target = strtolower($target);
			$this->config = array(
				'tcf' => array(
					'percentage'=> 50,
					'promo_id'	=> 29521
				),
				
				'bmg178' => array(
					'percentage'=> 50,
					'promo_id'	=> 29522
				),
								
				'ntl' => array(
					'percentage'=> 50,
					'promo_id'	=> 29523
				),
			);
		}
		
		public function Check()
		{
			$result = false;
			
			if(isset($this->config[$this->target]))
			{
				$result = (mt_rand(1, 100) <= $this->config[$this->target]['percentage']);
			}
			
			return $result;
		}
		
		public function Sell($app_data, $holidays)
		{
			$paydate = $app_data['paydate'];
			unset($app_data['paydate']);
			
			if(empty($app_data['best_call_time']))
			{
				$app_data['best_call_time'] = 'MORNING';
			}
			
			$pd_model = new Paydate_Model();
			$result = $pd_model->Build_From_Data($paydate);
			$paydates = $pd_model->Pay_Dates($holidays, 2);
			
			switch(strtoupper($_SESSION['config']->mode))
			{
				case 'LOCAL':
					$bfw = 'http://bfw.4.edataserver.com.ds59.tss:8080';
					$key = 'ccfbc42153db16b9eb1d1ed1073b5932';
				break;
				
				case 'RC':
					$bfw = 'http://rc.bfw.1.edataserver.com';
					$key = 'fd24d635c58f47139455e6d541f81622';
				break;
				
				default:
				case 'LIVE':
					$bfw = 'http://bfw.1.edataserver.com';
					$key = '982396b45ca962de72652cc3926910d7';
				break;
			}
			
			$promo_id = $this->config[$this->target]['promo_id'];
			
			$data =<<<DATA
<tss_loan_request>
	<signature>
		<data name="page">app_allinone</data>
		<data name="site_type">soap_oc</data>
		<data name="license_key">{$key}</data>
		<data name="promo_id">{$promo_id}</data>
		<data name="promo_sub_code"></data>
	</signature>
	<collection>

DATA;

			foreach($app_data as $key => $value)
			{
				$data .= "		<data name=\"{$key}\">{$value}</data>\n";
			}

			$data .= "		<data name=\"income_frequency\">{$paydate['frequency']}</data>\n";
			for($i = 1; $i <= 2; $i++)
			{
				list($y, $m, $d) = explode('-', $paydates[$i-1]);
				
				$data .=<<<DATA
		<data name="income_date{$i}_y">{$y}</data>
		<data name="income_date{$i}_m">{$m}</data>
		<data name="income_date{$i}_d">{$d}</data>

DATA;
			}
			
			if(strcasecmp($app_data['state'], 'CA') === 0)
			{
				$data .= "		<data name=\"cali_agree\">agree</data>\n";
			}
			
			$data .=<<<DATA
		<data name="citizen">TRUE</data>
		<data name="employer_length">TRUE</data>
		<data name="client_ip_address">{$_SESSION['data']['client_ip_address']}</data>
		<data name="ss_app_id">{$this->application_id}</data>
	</collection>
</tss_loan_request>
DATA;
			
			$soap = new SoapClient("{$bfw}/cm_soap.php?wsdl", array('connection_timeout' => 60));
			$result = $soap->User_Data($data);

			if(preg_match('/<data name="page">app_completed<\/data>/is', $result))
			{
				return true;
			}
			
			return false;
		}
	}

?>
