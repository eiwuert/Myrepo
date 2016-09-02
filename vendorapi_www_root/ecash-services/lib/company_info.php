<?PHP

class Company_Info
{
	// 500 Fast Cash Info
	private $NAME_500 = '500FastCash';
	private $STREET_500 = '515 G SE';
	private $CITY_500 = 'MIAMI';
	private $STATE_500 = 'OK';
	private $ZIP_500 = '74534';
	private $BUSINESS_PHONE_500 = '1-888-919-6669';
	private $FAX_500 = '1-800-416-1619';
	private $COLLECTIONS_PHONE_500 = '1-888-339-6669';
	private $COLLECTIONS_FAX_500 = '1-800-361-5520';
	private $TELEWEB_PHONE_500 = '1-800-756-3126';
	private $CUSTOMER_SERVICE_EMAIL_500 = 'customerService@500FastCash.com';
	private $COLLECTIONS_EMAIL_500 = 'collections@500FastCash.com';
	private $IMAGE_DIR_500 = '';

	// Ameriloan Info
	private $NAME_AMERI = 'AmeriLoan';
	private $STREET_AMERI = '3531 P Street NW';
	private $PO_BOX_AMERI = 'PO BOX 111';
	private $CITY_AMERI = 'Miami';
	private $STATE_AMERI = 'OK';
	private $ZIP_AMERI = '74355';
	private $BUSINESS_PHONE_AMERI = '1-800-362-9090';
	private $FAX_AMERI = '1-800-256-9166';
	private $COLLECTIONS_PHONE_AMERI = '1-800-536-8918';
	private $COLLECTIONS_FAX_AMERI = '1-800-803-9176';
	private $TELEWEB_PHONE_AMERI = '1-800-756-3112';
	private $CUSTOMER_SERVICE_EMAIL_AMERI = 'customerService@ameriloan.com';
	private $COLLECTIONS_EMAIL_AMERI = 'collections@ameriloan.com';
	private $IMAGE_DIR_AMERI = '';

	// United Cash Loans Info
	private $NAME_UCL = 'UnitedCashLoans';
	private $STREET_UCL = '3531 P Street NW';
	private $PO_BOX_UCL = 'PO BOX 111';
	private $CITY_UCL = 'Miami';
	private $STATE_UCL = 'OK';
	private $ZIP_UCL = '74355';
	private $BUSINESS_PHONE_UCL = '1-800-279-8511';
	private $FAX_UCL = '1-800-803-8794';
	private $COLLECTIONS_PHONE_UCL = '1-800-354-0602';
	private $COLLECTIONS_FAX_UCL = '1-800-9137';
	private $TELEWEB_PHONE_UCL = '1-800-756-3117';
	private $CUSTOMER_SERVICE_EMAIL_UCL = 'customerService@unitedCashLoans.com';
	private $COLLECTIONS_EMAIL_UCL = 'collections@unitedCashLoans.com';
	private $IMAGE_DIR_UCL = '';

	// One Click Cash Info
	private $NAME_OCC = 'OneClickCash';
	private $STREET_OCC = '52946 Highway 12';
	private $SUITE_OCC = 'Suite 3';
	private $CITY_OCC = 'Niobrara';
	private $STATE_OCC = 'NE';
	private $ZIP_OCC = '68760';
	private $BUSINESS_PHONE_OCC = '1-800-230-3266';
	private $FAX_OCC = '1-888-553-6477';
	private $COLLECTIONS_PHONE_OCC = '1-800-349-9418';
	private $COLLECTIONS_FAX_OCC = '1-800-803-8972';
	private $TELEWEB_PHONE_OCC = '1-800-756-3118';
	private $CUSTOMER_SERVICE_EMAIL_OCC = 'customerService@oneClickCash.com';
	private $COLLECTIONS_EMAIL_OCC = 'collections@oneClickCash.com';
	private $IMAGE_DIR_OCC = '';

	// US Fast Cash Info
	private $NAME_USFC = 'USFastCash';
	private $STREET_USFC = '3531 P Street NW';
	private $PO_BOX_USFC = 'PO BOX 111';
	private $CITY_USFC = 'Miami';
	private $STATE_USFC = 'OK';
	private $ZIP_USFC = '74355';
	private $BUSINESS_PHONE_USFC = '1-800-640-1295';
	private $FAX_USFC = '1-800-803-8796';
	private $COLLECTIONS_PHONE_USFC = '1-800-636-9460';
	private $COLLECTIONS_FAX_USFC = '1-800-803-9852';
	private $TELEWEB_PHONE_USFC = '1-800-756-3115';
	private $CUSTOMER_SERVICE_EMAIL_USFC = 'customerService@USFastCash.com';
	private $COLLECTIONS_EMAIL_USFC = 'collections@USFastCash.com';
	private $IMAGE_DIR_USFC = '';

	// Unknown Info
	private $NAME_UNKNOWN = 'unknown';
	private $STREET_UNKNOWN = 'unknown';
	private $CITY_UNKNOWN = 'unknown';
	private $STATE_UNKNOWN = 'unknown';
	private $ZIP_UNKNOWN = 'unknown';
	private $BUSINESS_PHONE_UNKNOWN = 'unknown';
	private $FAX_UNKNOWN = 'unknown';
	private $COLLECTIONS_PHONE_UNKNOWN = 'unknown';
	private $COLLECTIONS_FAX_UNKNOWN = 'unknown';
	private $TELEWEB_PHONE_UNKNOWN = 'unknown';
	private $CUSTOMER_SERVICE_EMAIL_UNKNOWN = 'unknown';
	private $COLLECTIONS_EMAIL_UNKNOWN = 'unknown';
	private $IMAGE_DIR_UNKNOWN = 'unknown';


	/**
    * Desc:
	 *		This is called and gets the company information for the company passed in.
	 *
	 * Param:
	 *		$comp: This should be one of the following strings: 5fc, ameriloan,
	 *					ucl, occ, usfc. It is the company name.
	 *
	 * Return
	 *		$result: This is an array which may contain with the following values:
	 *							name => The company name.
	 *							street => The company street (this may or may not be present).
	 *							po_box => The company po box (this may or may not be present).
	 *							suite => The company suite number (this may or may not be present).
	 *							city => The company state.
	 *							state => The company state.
	 *							zip => The company zip code.
	 *							business_phone => The business phone number.
	 *							fax => The company fax number.
	 *							collections_phone => The collections phone number.
	 *							collections_fax => The collections fax number.
	 *							teleweb_phone => The teleweb phone number.
	 *							customer_service_email => The customer service email.
	 *							collections_email => The collecion email address.
	 *							image_dir => The company image location.
	 */
	public function get_company_info($company)
	{
		$result = NULL;

		if (strtolower($company) == '5fc')
		{
			$result = $this->get_500_fast_cash();
		}
		else if (strtolower($company) == 'ameriloan')
		{
			$result = $this->get_ameriloan();
		}
		else if (strtolower($company) == 'ucl')
		{
			$result = $this->get_united_cash_loans();
		}
		else if (strtolower($company) == 'occ')
		{
			$result = $this->get_one_click_cash();
		}
		else if (strtolower($company) == 'usfc')
		{
			$result = $this->get_us_fast_cash();
		}
		else
		{
			$result = $this->get_empty_data();
		}

		return $result;
	}


	/**
	 * Desc
	 * 	This loads the 500 Fast Cash info.
	 *
	 * Return:
	 *		$result: This is an array which may contain with the following values:
	 *							name => The company name.
	 *							street => The company street.
	 *							city => The company state.
	 *							state => The company state.
	 *							zip => The company zip code.
	 *							business_phone => The business phone number.
	 *							fax => The company fax number.
	 *							collections_phone => The collections phone number.
	 *							collections_fax => The collections fax number.
	 *							teleweb_phone => The teleweb phone number.
	 *							customer_service_email => The customer service email.
	 *							collections_email => The collecion email address.
	 *							image_dir => The company image location.
	 */
	private function get_500_fast_cash()
	{
		$result = array('name' => $this->NAME_500,
							'street' => $this->STREET_500,
							'city' => $this->CITY_500, 
							'state' => $this->STATE_500,
							'zip' => $this->ZIP_500,
							'business_phone' => $this->BUSINESS_PHONE_500,
							'fax' => $this->FAX_500,
							'collections_phone' => $this->COLLECTIONS_PHONE_500,
							'collections_fax' => $this->COLLECTIONS_FAX_500,
							'teleweb_phone' => $this->TELEWEB_PHONE_500,
							'customer_service_email' => $this->CUSTOMER_SERVICE_EMAIL_500,
							'collections_email' => $this->COLLECTIONS_EMAIL_500,
							'image_dir' => $this->IMAGE_DIR_500);

		return $result;
	}


	/**
	 * Desc:
	 *		This loads the Ameriloan info.
	 *
	 * Return:
	 *		$result: This is an array which may contain with the following values:
	 *							name => The company name.
	 *							street => The company street.
	 *							po_box => The company po box.
	 *							city => The company state.
	 *							state => The company state.
	 *							zip => The company zip code.
	 *							business_phone => The business phone number.
	 *							fax => The company fax number.
	 *							collections_phone => The collections phone number.
	 *							collections_fax => The collections fax number.
	 *							teleweb_phone => The teleweb phone number.
	 *							customer_service_email => The customer service email.
	 *							collections_email => The collecion email address.
	 *							image_dir => The company image location.
	 */
	private function get_ameriloan()
	{
		$result = array('name' => $this->NAME_AMERI,
							'street' => $this->STREET_AMERI,
							'po_box' => $this->PO_BOX_AMERI,
							'city' => $this->CITY_AMERI, 
							'state' => $this->STATE_AMERI,
							'zip' => $this->ZIP_AMERI,
							'business_phone' => $this->BUSINESS_PHONE_AMERI,
							'fax' => $this->FAX_AMERI,
							'collections_phone' => $this->COLLECTIONS_PHONE_AMERI,
							'collections_fax' => $this->COLLECTIONS_FAX_AMERI,
							'teleweb_phone' => $this->TELEWEB_PHONE_AMERI,
							'customer_service_email' => $this->CUSTOMER_SERVICE_EMAIL_AMERI,
							'collections_email' => $this->COLLECTIONS_EMAIL_AMERI,
							'image_dir' => $this->IMAGE_DIR_AMERI);

		return $result;
	}

	
	/**
	 * Desc:
	 *		This loads the United Cash Loan info.
	 *
	 * Return:
	 *		$result: This is an array which may contain with the following values:
	 *							name => The company name.
	 *							street => The company street.
	 *							po_box => The company po box.
	 *							city => The company state.
	 *							state => The company state.
	 *							zip => The company zip code.
	 *							business_phone => The business phone number.
	 *							fax => The company fax number.
	 *							collections_phone => The collections phone number.
	 *							collections_fax => The collections fax number.
	 *							teleweb_phone => The teleweb phone number.
	 *							customer_service_email => The customer service email.
	 *							collections_email => The collecion email address.
	 *							image_dir => The company image location.
	 */
	private function get_united_cash_loans()
	{
		$result = array('name' => $this->NAME_UCL,
							'street' => $this->STREET_UCL,
							'po_box' => $this->PO_BOX_UCL,
							'city' => $this->CITY_UCL,
							'state' => $this->STATE_UCL,
							'zip' => $this->ZIP_UCL,
							'business_phone' => $this->BUSINESS_PHONE_UCL,
							'fax' => $this->FAX_UCL,
							'collections_phone' => $this->COLLECTIONS_PHONE_UCL,
							'collections_fax' => $this->COLLECTIONS_FAX_UCL,
							'teleweb_phone' => $this->TELEWEB_PHONE_UCL,
							'customer_service_email' => $this->CUSTOMER_SERVICE_EMAIL_UCL,
							'collections_email' => $this->COLLECTIONS_EMAIL_UCL,
							'image_dir' => $this->IMAGE_DIR_UCL);

		return $result;
	}


	/**
	 * Desc:
	 *		This loads the One Click Cash info.
	 *
	 * Return:
	 *		$result: This is an array which may contain with the following values:
	 *							name => The company name.
	 *							street => The company street.
	 *							suite => The company suite number.
	 *							city => The company state.
	 *							state => The company state.
	 *							zip => The company zip code.
	 *							business_phone => The business phone number.
	 *							fax => The company fax number.
	 *							collections_phone => The collections phone number.
	 *							collections_fax => The collections fax number.
	 *							teleweb_phone => The teleweb phone number.
	 *							customer_service_email => The customer service email.
	 *							collections_email => The collecion email address.
	 *							image_dir => The company image location.
	 */
	private function get_one_click_cash()
	{
		$result = array('name' => $this->NAME_OCC,
							'street' => $this->STREET_OCC,
							'suite' => $this->SUITE_OCC,
							'city' => $this->CITY_OCC,
							'state' => $this->STATE_OCC,
							'zip' => $this->ZIP_OCC,
							'business_phone' => $this->BUSINESS_PHONE_OCC,
							'fax' => $this->FAX_OCC,
							'collections_phone' => $this->COLLECTIONS_PHONE_OCC,
							'collections_fax' => $this->COLLECTIONS_FAX_OCC,
							'teleweb_phone' => $this->TELEWEB_PHONE_OCC,
							'customer_service_email' => $this->CUSTOMER_SERVICE_EMAIL_OCC,
							'collections_email' => $this->COLLECTIONS_EMAIL_OCC,
							'image_dir' => $this->IMAGE_DIR_OCC);

		return $result;
	}


	/**
	 * Desc:
	 *		This loads the US Fast Cash info.
	 *
	 * Return:
	 *		$result: This is an array which may contain with the following values:
	 *							name => The company name.
	 *							street => The company street.
	 *							po_box => The company po box.
	 *							city => The company state.
	 *							state => The company state.
	 *							zip => The company zip code.
	 *							business_phone => The business phone number.
	 *							fax => The company fax number.
	 *							collections_phone => The collections phone number.
	 *							collections_fax => The collections fax number.
	 *							teleweb_phone => The teleweb phone number.
	 *							customer_service_email => The customer service email.
	 *							collections_email => The collecion email address.
	 *							image_dir => The company image location.
	 */
	private function get_us_fast_cash()
	{
		$result = array('name' => $this->NAME_USFC,
							'street' => $this->STREET_USFC,
							'po_box' => $this->PO_BOX_USFC,
							'city' => $this->CITY_USFC, 
							'state' => $this->STATE_USFC,
							'zip' => $this->ZIP_USFC,
							'business_phone' => $this->BUSINESS_PHONE_USFC,
							'fax' => $this->FAX_USFC,
							'collections_phone' => $this->COLLECTIONS_PHONE_USFC,
							'collections_fax' => $this->COLLECTIONS_FAX_USFC,
							'teleweb_phone' => $this->TELEWEB_PHONE_USFC,
							'customer_service_email' => $this->CUSTOMER_SERVICE_EMAIL_USFC,
							'collections_email' => $this->COLLECTIONS_EMAIL_USFC,
							'image_dir' => $this->IMAGE_DIR_USFC);

		return $result;
	}


	/**
	 * Desc:
	 *		This loads empty data.
	 *
	 * Return:
	 *		$result: This is an array which may contain with the following values:
	 *							name => unknown.
	 *							street => unknown.
	 *							city => unknown.
	 *							state => unknown.
	 *							zip => unknown.
	 *							business_phone => unknown.
	 *							fax => unknown.
	 *							collections_phone => unknown.
	 *							collections_fax => unknown.
	 *							teleweb_phone => unknown.
	 *							customer_service_email => unknown.
	 *							collections_email => unknown.
	 *							image_dir => unknown.
	 */
	private function get_empty_data()
	{
		$result = array('name' => $this->NAME_UNKNOWN,
							'street' => $this->STREET_UKNOWN,
							'city' => $this->CITY_UNKNOWN, 
							'state' => $this->STATE_UNKNOWN,
							'zip' => $this->ZIP_UNKNOWN,
							'business_phone' => $this->BUSINESS_PHONE_UNKNOWN,
							'fax' => $this->FAX_UNKNOWN,
							'collections_phone' => $this->COLLECTIONS_PHONE_UNKNOWN,
							'collections_fax' => $this->COLLECTIONS_FAX_UNKNOWN,
							'teleweb_phone' => $this->TELEWEB_PHONE_UNKNOWN,
							'customer_service_email' => $this->CUSTOMER_SERVICE_EMAIL_UNKNOWN,
							'collections_email' => $this->COLLECTIONS_EMAIL_UNKNOWN,
							'image_dir' => $this->IMAGE_DIR_UNKNOWN);

		return $result;
	}
}

?>
