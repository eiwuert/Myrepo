<?php

	/***
			transaction_proc.php
			--
			processes the transact files for each company, attempting to combine
			rediculous amounts of rows into smarter rows!
	***/

	require_once('mysqli.1.php');

	class Loan
	{
		var $advance_amount;
		var $running_balance;
		var $total_returns;
		var $service_charges;
		var $customer_id;
		var $fees;
		var $check_dropped = "No";
		var $check_returned = "No";
		var $balances = array();
		var $cycles;
		var $legacy = array();
		// Anal.
		var $anal = array();
		var $hasFullPulled = "No";
		var $paydowns;
		function Loan()
		{
			$this->legacy['advance'] = 0;
			$this->legacy['cycle'] = "";
			$this->legacy['paidflag'] = "yes";
			$this->anal['fund_amount'] = 0;
		}
		function Get_MySQL_Data()
		{

		}
	}

	class Cashline_Processor
	{
		private $transact_set;
		private $obj;
		private $last_obj;
		private $last_svc_charge_object;
		private $last_svc_charge;
		private $ach_array;
		private $customer_id;

		// ====================================================
		// This function handles all the transact records for a given customer
		// object.  It uses this information to generate one (or more)
		// "loan" rows... The idea is to take a set of transact records and
		// deduce each individual loan...
		private function Process_Transact_Set($rows)
		// ====================================================
		{
			$loans = array();
			$prev = FALSE;
			$next = FALSE;
			$curr = FALSE;
			$loancount = 1;

			reset ( $rows );
			foreach ( $rows as $transact )
			{
				// previous transaction object
				$prev = $curr;

				// current transaction object
				$curr = $transact;

				// previewing our next transaction. this can be null
				$next = next($rows);


				// if we have no 'loan' object.. meaning we have yet to
				// see an 'advance' transaction.. and we're now seeing a transaction
				// that is not a new loan... its talking about a loan that does
				// not exist.
				if ( !isset ( $loan ) && $transact->type != "ADVANCE" )
				{
					// looks corrupt to me.
					return $loans;
				}



				switch ( $transact->type )
				{
					// advance transactions denote the beginning of a new loan
					// this is not true in all cases.. as in old NMS loans
					// have an advance record for every pay cycle... ugggh
					case "ADVANCE":
						// were we already brewing a loan?
						// if so, put it into our return array
						if ( isset($loan) )
						{
							$loans[] = $loan;
						}
						// Create a new loan object with default values
						// and a number of balance trackers.
						$loan = new Loan();
						$loan -> transactions = array();
						$loan -> advance_amount = $transact->amount;
						$loan ->_advpaid = $transact->paid;
						$loan -> running_balance = -$transact->amount;
						$loan -> balance2 =- ( $transact->amount - $transact->paid);
						$loan -> balance4 = 0;
						$loan -> customer_id = $transact->custnum;
						$loan -> cycles = 1;
						$loan -> legacy['custnum'] = $transact->custnum;
						$loan -> legacy['advance'] = $transact->amount;
						$loan -> legacy['advdate'] = date("Ymd",$transact->td0);
						$loan -> legacy['date_advance'] = date("Y-m-d", $transact->td0);
						$loan -> legacy['cycle'] = "A";
						$loan -> loan_id = $loan->customer_id * 100 + $loancount;
						$loan -> legacy['loannum'] = $loancount++;
						$loan -> legacy['paiddate'] = $transact->pd;
						$loan -> legacy['achret'] = 0;
						$loan -> legacy['first_due_date'] = '0000-00-00';

						$loan->anal['fund_amount'] = $transact->amount;
						$loan->anal['date_advance'] = date("Y-m-d", $transact->td0);
						$loan->anal['date_loan_paid'] = ($transact->pd0 > 1) ? date("Y-m-d", $transact->pd0) : null;
						$loan->anal['first_due_date'] = null;

						$loan -> debit_transactions = array();
						$loan -> return_transactions = array();
						$loan -> paydowns=0;
						break;

					// service charge.
					// these almost always directly follow an ADVANCE transaction...
					// We add this fee to the "running balance"
					// this denotes a fee accrued, but not necessarily BILLED
					case "SERVICE CHARGE":
						$loan -> running_balance -= $transact->amount;
						$loan -> balance2 -= $transact->amount;
						$loan -> service_charges += $transact->amount;

						// is this the last transaction?
						if ( $next==FALSE || $next->type != "DEBIT-SERVICE CHARGE" )
							$loan->legacy['paidflag'] = "no";

						// 'S' represents a service charge in our cycle summary
						$loan -> legacy['cycle'] .= "S";
						$loan -> legacy['next_due_date'] = date("Y-m-d",$transact->dd0);
						$loan->anal['next_due_date'] = date("Y-m-d",$transact->dd0);
						if ( $loan -> legacy['first_due_date'] == '0000-00-00' )
						{
							$loan -> legacy['first_due_date'] = date("Y-m-d",$transact->dd0);
							$loan->anal['first_due_date'] = date("Y-m-d",$transact->dd0);
						}
						break;

					// debit-service charge
					// this transaction is "fabricated"
					// This is an event that implicitly exists in the original
					// data but has no physical representation.  this transaction
					// was created in the pre-processing prior to this function call.
					case "DEBIT-SERVICE CHARGE":
						// this is the debit action of a service charge.. which denotes
						// a change in pay cycles
						//echo "debit-service charge for $transact->custnum \r";
						$loan -> cycles ++;
						$loan -> running_balance += $transact->amount;
						$loan -> balance2 += $transact->amount;
						$loan -> balance4 += $transact->paid;

						// 'P' represents a DSC in our cycle summary
						$loan -> legacy['cycle'] .= "P";

						// log this debit attempt
						if ( !isset ( $balances[$loan -> cycles] ) )
							$balances[$loan -> cycles] = array();

						//$loan -> balances[$loan -> cycles][] = "DEBIT-SC " . $transact->amount;
						break;

					// ACH RETURN... this is the pain in the ass transaction
					// it represents one or more failed debit attempts
					// an ach return comes back with an 'amount' which may or not
					// be a summed amount of multiple debits.... :(
					case "ACH RETURN":
						// has this person returned on this loan yet?
						// no? ok. mark it..
						if ( $loan -> legacy['achret'] == 0 )
						{
							$loan -> legacy['achret'] = $loan->cycles - 1;
							$loan->anal['first_return_pay_cycle'] = $loan->cycles - 1;
						}
						// if we have an ach return, this is the first cycle, and this is their first loan
						// this is an (F)irst loan (F)irst cycle (D)efault
						if ( $loan->cycles == 2 && $loancount == 1)
						{
							$loan -> FFD = TRUE;
						}
						$loan -> balance2 -= $transact->amount;
						$loan -> running_balance -= $transact->amount;
						$loan -> total_returns += $transact->amount;
						$loan -> legacy['cycle'] .= "R";

						// extract return codes from the payment_history
						// this isnt flawless. :P
						if ( ereg(" - ([A-Z][0-9]{2}) ([A-Za-z0-9 ]+)([.]?)",$transact->payment_history,$match) )
						{
							$loan -> legacy['last_return_code'] = $match[1];
							$loan -> legacy['last_return_msg'] = $match[2];
							$loan -> anal['last_return_code'] = $match[1];
							$loan -> anal['last_return_msg'] = $match[2];
							if ( !isset($loan->legacy['first_return_code']) )
							{
								$loan -> legacy['first_return_code'] = $match[1];
								$loan -> legacy['first_return_msg'] = $match[2];
								$loan -> legacy['first_return_date'] = date("Y-m-d", $transact->td0);
								$loan -> anal['first_return_code'] = $match[1];
								$loan -> anal['first_return_msg'] = $match[2];
								$loan -> anal['first_return_date'] = date("Y-m-d", $transact->td0);
							}
						}
						$loan -> legacy['last_return_date'] = date("Y-m-d",$transact->td0);
						$loan -> anal['last_return_date'] = date("Y-m-d",$transact->td0);



						$transact->_cycle_id = $loan->cycles;

						break;
					// DEBIT-ACH RETRY... generated transaction
					// this transaction is generated using the 'paid' amount
					// on an ach return transaction.....
					case "DEBIT-ACH RETRY":
						$loan -> running_balance += $transact->amount;
						$loan -> balance2 += $transact->amount;
						if ( !isset ( $balances[$loan -> cycles] ) )
							$balances[$loan -> cycles] = array();
		//				$loan -> balances[$loan -> cycles][] = "DEBIT-ACH RETRY " . $transact->amount;
						break;

					// RETURN FEE... this will always succeed the first
					// ach return transaction on a given loan
					// This is usually a value of $30
					case "RETURN FEE":
						$loan -> fees += $transact->amount;
						$loan -> running_balance -= $transact->amount;
						$loan -> balance2 -= $transact->amount;
						if ( !isset ( $balances[$loan -> cycles] ) )
							$balances[$loan -> cycles] = array();
//						$loan -> balances[$loan -> cycles][] = "DEBIT-RF " .  $transact->amount;
						$loan -> legacy['cycle'] .= "F";
						break;

					// DEPOSITED CHECK .. quick check was dropped
					// Interesting thing about these, with little exception, will always be
					// dropped for the exact balance of the account. HOWEVER.. the 'amount'
					// and 'paid' values on this transaction are about 95% of the time completely
					// wrong.  however, rest assured, barring the exceptions, at the time of a
					// quick check drop, the account is considered zero'd until the check returns
					// .. which it usually does.
					case "DEPOSITED CHECK":
						// you made us do this.
						$loan -> check_dropped = "Yes";
						$loan -> legacy['depchkdate'] = $transact->transaction_date;
						$loan -> legacy['depchkamt'] = $transact->amount;
						$loan -> running_balance = 0;
						$loan -> balance2 = 0;
						$loan -> legacy['cycle'] .= "D";
						break;

					// RETURNED CHECK .. this is the transaction representing the return of the quick
					// check.  Good news, everybody! the 'amount' value on this transaction is 99% of the time
					// ACCURATE.  which means we can use this as a sanity check.   If we get to this point,
					// and the amount of the transaction is '300'.. 99% of the time, that is the balance of the account
					// as at that point it is likely going to outside collections
					case "RETURNED CHECK":
						// .... most likely.
						$loan -> check_returned = "Yes";
						$loan->running_balance = -$transact->amount;
						$loan->balance2 = -$transact->amount;
						$loan -> legacy['retchkdate'] = $transact->td;
						$loan -> legacy['retchkamt'] = $transact->amount;
						$loan -> legacy['cycle'] .= "C";
						break;

					// DEBIT-PAYDOWN .. this is generated based on the implication of a change in the
					// service charge amount... .e.g, we see a service charge for 90, then one for 75.
					// the variance in that case is '15'. 90 is 30% of 300, and 75 is 30% of 250... therefor
					// $50 was paid on the principal.  This is not denoted with a transaction usually..
					// so we make one!
					case "DEBIT-PAYDOWN":
						$loan -> running_balance += $transact->amount;
						$loan -> paydowns += $transact->amount;
						$loan -> legacy['cycle'] .= " ";
						if ( !isset ( $balances[$loan -> cycles] ) )
							$balances[$loan -> cycles] = array();
//						$loan -> balances[$loan -> cycles][] = $transact->amount;
						break;

					// DEBIT-RETURN FEE .. generated transaction which is implicit when we're presented with a RETURN FEE
					// transaction.. return fees are always debited the same day.
					case "DEBIT-RETURN FEE":
						$loan -> running_balance += $transact->amount;
						$loan -> balance2 += $transact->amount;
						$loan -> legacy['cycle'] .= "X";
						break;

					// ADJUSTMENT .. this is the "DAMNIT" transaction.  it pretty much signifies that the loan is not able
					// to be logically processed with any accuracy because it has been hand edited by a human
					case "ADJUSTMENT":
						$loan -> legacy['cycle'] .= "J";
						break;

					// P/O-S/C CANCELLED .. see ADJUSTMENT. same crap. :(
					case "P/O-S/C CANCELLED":
						$loan -> legacy['cycle'] .= "Z";
						break;

					// WESTERN UNION, CAR PAYMENT, MONEY ORDER.. we treat all of these
					// the same way. These are basically payments that were made outside of the normal
					// automatic debit cycle
					case "WESTERN UNION":
					case "CARD PAYMENT":
					case "MONEY ORDER":
						$loan -> balance2 += $transact->paid;
						$loan -> running_balance += $transact->paid;
						$loan -> legacy['cycle'] .= "M";
						break;
					default : break;
				}
				// If the next transaction is absent, we've reached the end of the line
				// for both this customer and this loan... the loan may still be active
				// if the next transaction is an 'advance', it means we've completed this
				// loan
				if ( $next === FALSE || $next->type == "ADVANCE" )
				{
					// No loan object? corrupt.
					if (!isset ($loan)) break;

					$loan->legacy['feesaccrued'] = $loan->service_charges + $loan->fees;


					// If we've reached a zero balance, AND the calculated advance payment
					// matches the advance amount, use balance counter 2.
					if ( $loan->balance2 == 0 && $loan->_advpaid == $loan->advance_amount )
						$paidamt = ( $loan->legacy['feesaccrued'] + $loan->legacy['advance'] );
					// If not, use Balance counter 1, offset by 2
					// I dont know how I can explain this one :P
					// balance2 is the amount of the advance minus the paid amount
					else
						$paidamt = ( $loan->legacy['feesaccrued'] + $loan->legacy['advance'] ) + $loan->balance2;


					// Since we're in the business to get out of the red
					// we do that first.  once we're in the black we can make $$$

					// advpaid gets filled first (recovery)
					$advpaid = min ( $loan->legacy['advance'], $paidamt );

					// fees get paid next (profit)
					$feespaid = min ( $loan->legacy['feesaccrued'], $paidamt-$advpaid );
					$loan->anal['loan_balance'] = -$loan->balance2;

					// legacy loan fixer upper by hargrove
					if ($next !== FALSE)
					{
						$feespaid = $loan->legacy['feesaccrued'];
						$advpaid = $loan->legacy['advance'];
						$loan->anal['loan_balance'] = 0;
					}

					// for debugging only //
					$loan->anal['last_return_msg'] = $loan->balance2 . "|" . $loan->_advpaid . "|" . $loan->advance_amount. "|" . $paidamt ;
					$loan->anal['first_return_msg'] = $loan->legacy['cycle'];
					$loan->anal['amount_paid'] = $advpaid + $feespaid;
					$loan->anal['fees_accrued'] = $loan->service_charges + $loan->fees;
					$loan->anal['fees_paid'] = $feespaid;
					$loan->anal['principal_paid'] = $advpaid;

					$loan->anal['current_cycle'] = $loan->cycles;
				}
			}

			$loans[] = $loan;

			return $loans;
		}


		// ====================================================
		// This function basically returns the ach return transactions
		// that are "due".. most ach return transactions will have a paid
		// timestamp.  this timestamp indicates if/when the balance in question
		// is re-debited.
		private function achret($time,$MODE)
		// ====================================================
		{

			foreach($this->ach_array as $key=>$obj)
			{

				if ( $MODE == TRUE )
				{
					if ( $obj->pd0 < $time )
					{
						return array($key,$obj);
					}
				}
				else
				{
					if ( $obj->pd0 == $time )
					{
						return array($key,$obj);
					}
				}
			}
			return FALSE;
		}



		// ====================================================
		// preproc() -- this is massive
		private function preproc()
		// ====================================================
		{


			// we see an ach return transaction
			// if the paid timestamp isnt NULL, we add this to our array
			// of ach return trasactions that will eventually be retried
			if ( $this->obj->type=="ACH RETURN" && $this->obj->pd0 != 0 )
			{
				$this->ach_array[] = clone $this->obj;
			}

			// Lets check if the transaction we're dealing with
			// happens at the same time or after any of the ach returns
			// pending a debit-retry. if so, we know we can put out a
			// debit-ach retry transaction and pop that bitch off the stack
			while ( $ob=$this->achret($this->obj->td0, TRUE) )
			{
				list($key,$ar) = $ob;

				// peace!
				unset ( $this->ach_array[$key] );

				// our new debit-ach retry transaction
				$ar->td = $ar->pd;
				$ar->td0 = $ar->pd0;
				$ar->type = "DEBIT-ACH RETRY";
				$this->transact_set[] = $ar;

			}



			if ( // interesting situation here.
			// if all these conditions are met, it means we saw a service charge
			// at some point in the past.  That service charge was apparently paid eventually
			// and it happened right before or at the same time as our "working" transaction
			// its time to "debit" that transaction (create the transaction)..
				$this->last_svc_charge_object != NULL &&
				$this->obj->td0 >= $this->last_svc_charge_object->pd0 &&
				!$this->last_svc_charge_object->debited &&
				$this->last_svc_charge_object->paid != 0
				)
			{
				// set up the new transaction
				// this transaction is a debit-service charge
				// and represents the action of charging the customer interest
				$obj2 = clone $this->last_svc_charge_object;
				$obj2->td = $obj2->pd;
				$obj2->td0 = $obj2->pd0;
				$obj2->type = "DEBIT-SERVICE CHARGE";
				$this->transact_set[] = $obj2;

				// make sure we dont debit this service charge more than once
				$this->last_svc_charge_object->debited = TRUE;

				// clk loans are always going to be a multiple of 50, never less than 100
				// our service charges are equal to 30% of the principle on the account
				// if this service charge has an amount of 15
				// that means its PAYDOWN time.
				// why? because $15 is always the last service charge amount, assuming we
				// achieve "total loan fruition" :P
				if ( $obj2->amount == 15 )
				{
					$obj3= clone $obj2;
					$obj3->type = "DEBIT-PAYDOWN";
					$obj3->amount = 50;
					$obj3->paid = 50;
					$this->transact_set[]=$obj3;
				}
			}
			// A check was dropped?  This is a last attempt at inside collections
			// After this we're either getting our money or this guy is going to outside
			// collections
			// however this means that we're basically waiting for the returned check.
			// wipe out pending debits (there shouldnt be any anyway... )
			if ( $this->obj->type == "DEPOSITED CHECK" )
			{
				$this->last_svc_charge_object = NULL;
			}


			// we've found a service charge and one preceeds this... make sure it gets debited
			// it may have been debited already, but we're not positive!! we must do this step
			// as in some cases the code above may not be triggered and these situations
			// are mildly different but different enough to require separate code chunks
			if ( $this->obj->type == "SERVICE CHARGE" && is_object($this->last_svc_charge_object) )
			{
				// last svc charge is still pending a debit
				// tap that shit!
				if (!$this->last_svc_charge_object->debited  && $this->last_svc_charge_object->paid != 0)
				{
					$obj2 = clone $this->last_svc_charge_object;
					$obj2->type = "DEBIT-SERVICE CHARGE";
					$obj2->td = $this->last_svc_charge_object->pd;
					$obj2->td0= $this->last_svc_charge_object->pd0;
					$this->last_svc_charge_object->debited=true;
					$this->transact_set[]=$obj2;
				}

				// the good ol $15 check..
				// if this is the case, it is noted that the working transaction is going to be the last
				// service charge.. which also means the principle is getting paid off.
				if ( $this->obj->amount == 15 )
				{
					$obj2->type = "DEBIT-PAYDOWN";
					$obj2->amount = 50;
					$obj2->paid = 50;
					$this->transact_set[]=$obj2;
				}
				// The value wasnt 15 so we compare the working service charge
				// to the previous one.  If we see a variance, it means that the difference
				// between this transaction and the previous service charge is a change in the balance
				// account for this change in our internal balance counter by nothing the debiting
				// of the principle of the loan.
				else
				{
					$variance = $this->last_svc_charge - $this->obj->amount;
					if ( $variance > 0 && ($variance %15) == 0 )
					{
						//$obj2 = clone $this->obj;
						$obj2->td0= $this->last_svc_charge_object->pd0;
						$obj2->type = "DEBIT-PAYDOWN";
						$obj2->amount = ($variance / 15) * 50;
						$obj2->paid = ($variance / 15) * 50;
						$this->transact_set[] = $obj2;
					}
					$this->last_svc_charge = $this->obj->amount;
				}
			}

			// add transact object to the current set
			$this->transact_set[] = $this->obj;

			// When we see this return fee, it means we had our first return
			// we do not charge the return fee every time we get a return, just
			// the first time
			// we immediately debit this fee.. so we just create that tranasaction
			// now
			if ( $this->obj->type == "RETURN FEE" )
			{
				$obj2 = clone $this->obj;
				$obj2->type = "DEBIT-RETURN FEE";
				$obj2->amount = $this->obj->amount;
				$obj2->paid = $this->obj->amount;
				$this->transact_set[] = $obj2;
			}
			$this->last_obj = clone $this->obj;

			// update our "last service charge" objects...
			if ( $this->obj->type == "SERVICE CHARGE" )
			{
				$this->last_svc_charge_object = clone $this->obj;
				$this->last_svc_charge_object->debited = false;
			}


			// Check for ACH returns waiting that have the same time as now.
			// we do this twice.. the first time near the top is only ones we've passed. the ones
			// that are equal get debited AFTER the working transaction
			while ( $ob=$this->achret($this->obj->td0,FALSE) )
			{
				list($key,$ar) = $ob;

				unset ( $this->ach_array[$key] );

				$ar->td = $ar->pd;
				$ar->td0 = $ar->pd0;
				$ar->type = "DEBIT-ACH RETRY";
				$this->transact_set[] = $ar;
			}
			$this->customer_id = $this->obj->custnum;
		}




		public function Process_Customer($transactions)
		{
			$pct=0;
			$start = time();

			$this->last_svc_charge_object		=	NULL;
			$this->transact_set				=	array();
			$this->last_obj = null;
			$this->customer_id				=	0;
			$this->last_svc_charge			=	0;
			$this->ach_array					=	array();
			$this->pending_outbound			=	array();
			$queries					=	array();
			$updates					=	array();
			$qc							=	0;
			$loans = array();
			$count_b = sizeof($transactions);


			foreach ($transactions as $this->obj)
			{
				$this->obj = (object) $this->obj;

				//print_r($this->obj);
				if (++$count_a == $count_b)
				{
					// new set
					if ( count($this->transact_set) )
					{
						//print "Calling preproc (last trans) ($this->obj->type)\n";
						$this->preproc();

						if ( is_object($this->last_svc_charge_object) )
						{
							// we have an existing svc chargeobj
							if  ( $this->last_svc_charge_object->paid == $this->last_svc_charge_object->amount && !$this->last_svc_charge_object->debited  && $this->last_svc_charge_object->paid != 0)
							{
								// same amounts.. means its supposed to be paid.
								$obj2 = clone $this->last_svc_charge_object;
								$obj2->td = $obj2->pd;
								$obj2->td0 = $obj2->pd0;
								$obj2->type = "DEBIT-SERVICE CHARGE";
								$this->last_svc_charge_object = NULL;
								$this->transact_set[] = $obj2;
								if ( $obj2->amount == 15 )
								{
									$obj2->type = "DEBIT-PAYDOWN";
									$obj2->amount = 50;
									$obj2->paid = 50;
									$obj2->td0 = $obj2->pd0;
									$this->transact_set[] = $obj2;
								}
							}
						}
						//print_r($this->transact_set);
						$loans = $this->Process_Transact_Set($this->transact_set);
					}

					$this->transact_set = array();
					$this->last_svc_charge = 0;
					$this->last_svc_charge_object = FALSE;
					$this->ach_array = array();
				}
				//print "Calling preproc ($obj->type)\n";
				$this->preproc();

			}
			return $loans;
		}
	}

?>
