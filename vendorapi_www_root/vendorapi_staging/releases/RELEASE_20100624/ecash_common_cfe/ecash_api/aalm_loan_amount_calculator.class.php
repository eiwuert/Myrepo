<?php
    require_once(ECASH_COMMON_DIR . 'nada/NADA.php');
    /**
     * Loan Amount Calculator using Agean Business Logic & Rules
     *
     * @requires Business Rule new_loan_amount
     * @requires Business Rule max_react_loan_amount
     */
    Class AALM_LoanAmountCalculator extends LoanAmountCalculator
    {
        private $nada;
        private $db;
    
        public function __construct(DB_Database_1 $db)
        {
            $this->db = $db;
            $this->nada = new NADA_API($db);
        }
    
        /**
         * Calculates the Maximum loan amount for an application
         *
         * @param object $data
         * @return int
         */
        public function calculateMaxLoanAmount($data)
        {
            if(! is_object($data))
            {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->loan_type_name))
            {
                throw new Exception("\$data->loan_type_name must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->num_paid_applications))
            {
                $data->num_paid_applications = self::countNumberPaidApplications($data);
            }
    
            if(! isset($data->prev_max_qualify))
            {
                $data->prev_max_qualify = self::getPreviouslyQualifiedAmmount($data);
            }
    
            $max_loan_amount = 0;
    
            switch ($data->loan_type_name)
            {
                case 'California Payday Loan':
                    $data->num_paid_applications = 0;
                    $data->business_rules['loan_percentage'] = $data->business_rules['ca_loan_percentage'];
                    $data->business_rules['loan_amount_increment'] = $data->business_rules['ca_loan_amount_increment'];
                    $data->business_rules['loan_cap'] = $data->business_rules['ca_loan_cap'];
                    $max_loan_amount = self::_calculatePayDayMaxLoanAmount($data);
                    break;
                case 'Delaware Title Loan': // This needs to have it's own calculations
                    $max_loan_amount = self::_calculateTitleMaxLoanAmount($data);
                    break;
                case 'Delaware Payday Loan':
                default:
                    $max_loan_amount = self::_calculatePayDayMaxLoanAmount($data);
                    break;
            }
            
            // [#42869] -- increase max loan amount if they have a specific datax return
            // add to first-time loans
            if(!empty($data->idv_increase_eligible) && is_numeric($data->idv_increase_eligible) && $data->idv_increase_eligible > 0 && $data->num_paid_applications == 0)
            {
                $max_loan_amount += $data->idv_increase_eligible;
            }
            else if (!empty($data->idv_increase_eligible) && $data->idv_increase_eligible
                && isset($data->business_rules['datax_amount_increase']) && $data->num_paid_applications == 0)
            {
                $max_loan_amount += $data->business_rules['datax_amount_increase'];
            }			
            
	    return $max_loan_amount;
        }
    
        /**
         * Calculates the various loan amounts available to the applicant
         *
         * @param object $data
         * @return array
         */
        public function calculateLoanAmountsArray($data)
        {
            if(! is_object($data))
            {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->loan_type_name))
            {
                throw new Exception("\$data->loan_type_name must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            switch ($data->loan_type_name)
            {
                case 'California Payday Loan':
                    $data->business_rules['loan_percentage'] = $data->business_rules['ca_loan_percentage'];
                    $data->business_rules['loan_amount_increment'] = $data->business_rules['ca_loan_amount_increment'];
                    $data->business_rules['loan_cap'] = $data->business_rules['ca_loan_cap'];
                    return self::_calculatePayDayLoanAmountsArray($data);
                    break;
                case 'Delaware Title Loan': // This needs to have it's own calculations
                    return self::_calculateTitleLoanAmountsArray($data);
                case 'Delaware Payday Loan':
                default:
                    return self::_calculatePayDayLoanAmountsArray($data);
                    break;
            }
        }
    
    
        private function _calculatePayDayMaxLoanAmount($data) {
            /**
             * There's an excessive amount of dependancy checking here... But it will
             * help if there are ever any bugs.
             */
            if(! is_object($data)) {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->business_rules)) {
                throw new Exception("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
            } else {
                $rules = $data->business_rules;
            }
    
            if(! isset($data->income_monthly)) {
                throw new Exception("\$data->income_monthly must be set for " .__CLASS__ . "::" . __FUNCTION__);
            } else {
                $income = $data->income_monthly;
            }
    
            $loan_amounts = array();
            $max_loan_amount = 0;

            // Number of paid off accounts
            $num_paid = isset($data->num_paid_applications) ? $data->num_paid_applications : 0;
		//if ($data->status == 'refi') $num_paid = $num_paid +1;
		// error_log(print_r("app: " . $data->application_id . ", num_paid: " . $num_paid, true));
    
            // The max qualified for on previous applications 
            $last_fund_amount = isset($data->prev_max_qualify) ? $data->prev_max_qualify : 0;
		//error_log(print_r("app: " . $data->application_id . ", last_fund_amount: " . $last_fund_amount, true));
            
            $loan_diff = 0;
            $prev_loan_cap = 0;
            // The maximum amount of the loan based on the number of paid accounts
            if(is_array($rules['loan_cap'])) {
                $max = count($rules['loan_cap']) - 1;
                if($num_paid < $max) {
                    $loan_amount_cap = $rules['loan_cap'][$num_paid];
                    $prev_loan_cap = $rules['loan_cap'][$num_paid - 1];
                    $loan_diff = $rules['loan_cap'][$num_paid] - $rules['loan_cap'][$num_paid - 1];
                } else {
                    $loan_amount_cap = $rules['loan_cap'][$max];
                    $prev_loan_cap = $rules['loan_cap'][$max - 1];
                    $loan_diff = $rules['loan_cap'][$max] - $rules['loan_cap'][$max - 1];
                }
            } else {
                $prev_loan_cap = $loan_amount_cap = $rules['loan_cap'];
            }
            if(($last_fund_amount > 0) && ($num_paid > 0)) {
                if ($last_fund_amount > $prev_loan_cap) $prev_cap_vs_funded = $last_fund_amount - $prev_loan_cap;
                else $prev_cap_vs_funded = 0;
                $loan_amount_cap = $loan_amount_cap + $loan_diff;// + $prev_cap_vs_funded
            }
	    $loan_amount_cap = max($loan_amount_cap,$last_fund_amount);
    
            // The percentage of their income that they can get towards a loan
            if(is_array($rules['loan_percentage'])) {
                $max = count($rules['loan_percentage']) - 1;
                if($num_paid < $max) {
                    $percentage_of_income = $rules['loan_percentage'][$num_paid];
                } else {
                    $percentage_of_income = $rules['loan_percentage'][$max];
                }
            } else {
                $percentage_of_income = $rules['loan_percentage'];
            }
    
            // Loan amounts must be in increments of this number
            $loan_amount_increment = $rules['loan_amount_increment'];
    
            // Determine the whole dollar amount based on their percentage of income
            $amount_based_on_percentage = (int)($income * ($percentage_of_income / 100));
    
            /**
             * Agean wants loan amounts based in increments of 50, so if the number isn't
             * divisible by the increment amount, reduce it till it is.
             */
            while(!($amount_based_on_percentage % $loan_amount_increment) == 0) {
                $amount_based_on_percentage--;
            }
    
            if($amount_based_on_percentage < $loan_amount_cap) {
                return $amount_based_on_percentage;
            } else {
                return $loan_amount_cap;
            }
        }
    
        /**
         * Calculates the maximum loan amount for Title Loans
         *
         * Unline Payday Loans, this method does not use the loan number
         * currently.  The code is in there, but it is commented out and
         * defaulted to 0.  If Agean decides to change the percentage or
         * cap based on the loan number, it'll be a trivial change.
         *
         * @param object $data
         * @return integer
         */
        private function _calculateTitleMaxLoanAmount($data)
        {
            /**
             * There's an excessive amount of dependancy checking here... But it will
             * help if there are ever any bugs.
             */
            if(! is_object($data))
            {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->business_rules))
            {
                throw new Exception("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $rules = $data->business_rules;
            }
    
            if(! isset($data->income_monthly))
            {
                throw new Exception("\$data->income_monthly must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $income = $data->income_monthly;
            }
    
            // Number of paid off accounts
            //Setting to zero since the percentage should always be the same, but they may request
            //this be used again in the future
            //$num_paid = $data->num_paid_applications;
            $num_paid = 0;
    
            // The percentage of their income that they can get towards a loan
            if(is_array($rules['loan_percentage']))
            {
                $max = count($rules['loan_percentage']) - 1;
                if($num_paid < $max)
                {
                    $percentage_of_income = $rules['loan_percentage'][$num_paid];
                }
                else
                {
                    $percentage_of_income = $rules['loan_percentage'][$max];
                }
            }
            else
            {
                $percentage_of_income = $rules['loan_percentage'];
            }
    
            // Loan amounts must be in increments of this number
            $loan_amount_increment = $rules['loan_amount_increment'];
    
            if(isset($data->vehicle_vin) && strlen($data->vehicle_vin) > 8)
            {
                $nada_value = $this->nada->getVehicleByVin($data->vehicle_vin)->value;
            }
    
            if(!isset($nada_value) || $nada_value == null)
            {
                // Get max loan amount based on NADA check
                $nada_value = $this->nada->getValueFromDescription(
                    $data->vehicle_make,
                    $data->vehicle_model,
                    $data->vehicle_series,
                    $data->vehicle_style,
                    $data->vehicle_year);
            }
    
            // Determine the whole dollar amount based on their percentage of income
            $amount_based_on_percentage = (int)($income * ($percentage_of_income / 100));
    
            if($nada_value && $nada_value > $loan_amount_increment)
            {
                $nada_percentage = (isset($data->flags['cust_no_ach'])) ? 0.3 : 0.4; //@TODO: Biz rule this mutha!
                $nada_max_loan = (int)($nada_value * $nada_percentage);
                // Get the lower of the two loan amounts
                $loan_amount = ($amount_based_on_percentage < $nada_max_loan) ? $amount_based_on_percentage : $nada_max_loan;
            }
            else
            {
                $loan_amount = $amount_based_on_percentage;
            }
    
            // The loan cap
            if(is_array($rules['loan_cap']))
            {
                $max = count($rules['loan_cap']) - 1;
                if($num_paid < $max)
                {
                    $loan_cap = $rules['loan_cap'][$num_paid];
                }
                else
                {
                    $loan_cap = $rules['loan_cap'][$max];
                }
            }
            else
            {
                $loan_cap = $rules['loan_cap'];
            }
            //if percentage of income or vehicle value is greater than the loan cap, use loan cap
            if($loan_amount > $loan_cap)
                $loan_amount = $loan_cap;
            /**
             * Agean wants loan amounts based in increments of 50, so if the number isn't
             * divisible by the increment amount, reduce it till it is.
             */
            while(!($loan_amount % $loan_amount_increment) == 0)
            {
                $loan_amount--;
            }
    
            return $loan_amount;
        }
    
        private function _calculatePayDayLoanAmountsArray($data)
        {
    
            if(! is_object($data))
            {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->business_rules))
            {
                throw new Exception("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $rules = $data->business_rules;
            }
    
            if(! isset($data->income_monthly))
            {
                throw new Exception("\$data->income_monthly must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $income = $data->income_monthly;
            }
    
            if(! isset($data->is_react))
            {
                throw new Exception("\$data->is_react must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $is_react = $data->is_react;
            }
    
            $min_loan_amount = ($is_react == 'yes') ? (empty($data->business_rules['minimum_loan_amount']['min_react']) ? 0: $data->business_rules['minimum_loan_amount']['min_react'] ): (empty($data->business_rules['minimum_loan_amount']['min_non_react']) ? 0 : $data->business_rules['minimum_loan_amount']['min_non_react']);

            $loan_amounts = array();
    
            // Get max loan amount
            $loan_amount = self::calculateMaxLoanAmount($data);
    
            // The increment amount for the loan
            $increment_amount = $rules['loan_amount_increment'];
    
            while($loan_amount >= $min_loan_amount)
            {
                $loan_amounts[] = $loan_amount;
    
                /**
                 * If the loan amount is not divisible by the increment...
                 *
                 * This is used to accomodate JiffyCash's Max loan amount
                 * that is not divisible by the increment value.
                 */
                if(($loan_amount % $increment_amount) != 0)
                {
                    $remainder = $loan_amount % $increment_amount;
                    $loan_amount = $loan_amount - $remainder;
                }
    
                $loan_amount -= $increment_amount;
            }
    
            return array_reverse($loan_amounts);
        }
    
        private function _calculateTitleLoanAmountsArray($data)
        {
            if(! is_object($data))
            {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            if(! isset($data->business_rules))
            {
                throw new Exception("\$data->business_rules must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $rules = $data->business_rules;
            }
    
            if(! isset($data->income_monthly))
            {
                throw new Exception("\$data->income_monthly must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $income = $data->income_monthly;
            }
    
            if(! isset($data->is_react))
            {
                throw new Exception("\$data->is_react must be set for " .__CLASS__ . "::" . __FUNCTION__);
            }
            else
            {
                $is_react = $data->is_react;
            }
    
            $min_loan_amount = ($is_react == 'yes') ? $data->business_rules['minimum_loan_amount']['min_react'] : $data->business_rules['minimum_loan_amount']['min_non_react'];
    
            $loan_amounts = array();
    
            $loan_amount = self::calculateMaxLoanAmount($data);
            // The increment amount for the loan
            $increment_amount = $rules['loan_amount_increment'];
    
            while($loan_amount >= $min_loan_amount)
            {
                $loan_amounts[] = $loan_amount;
                $loan_amount = $loan_amount - $increment_amount;
            }
            return array_reverse($loan_amounts);
        }
    
        public function getPreviouslyQualifiedAmmount($data) {
            if (!is_object($data)) {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            // application_list defaults to an empty array if it is not set
            $application_list = (isset($data->application_list)) ? $data->application_list : array();
    
            $max_funded = 0;
            foreach($application_list as $application){
	    	//error_log(print_r("app: " . $data->application_id . ", application_id from application_list " . $application->application_id, true));
		//error_log(print_r("app: " . $application->application_id . ", fund_qualified " . $application->fund_qualified, true));
		//error_log(print_r("app: " . $application->application_id . ", fund_actual " . $application->fund_actual, true));
		if ($application->application_id != $data->application_id)
		{
                	$max_funded = max($max_funded,$application->fund_qualified*1,$application->fund_actual*1);
		}
            }
    		//error_log(print_r("app: " . $data->application_id . ", max_funded " . $max_funded, true));
            return $max_funded;
        }
    
        public function countNumberPaidApplications($data)
        {
            require_once('agean_api.php');
    
            if(!is_object($data))
            {
                throw new Exception("\$data must be an object! :: " .__CLASS__ . "::" . __FUNCTION__);
            }
    
            // application_list defaults to an empty array if it is not set
            $application_list = (isset($data->application_list)) ? $data->application_list : array();
    
            $num_paid = 0;
	 	$paid_status = array();
            $chain = 'paid::customer::*root';
	    	$chain1 = 'refi::servicing::customer::*root';
                $chain2 = 'settled::customer::*root';
            $status_map = eCash_API_2::_Fetch_Status_Map($this->db);
            foreach ($status_map as $id => $info)
	    {
                if ($info['chain'] == $chain || $info['chain'] == $chain1 || $info['chain'] == $chain2)
		{
                	$paid_status[] = $id;
                }
            }
    
            foreach($application_list as $application)
            {
                if(in_array($application->application_status_id, $paid_status)) $num_paid++;
            }
    
            return $num_paid;
        }
    }
