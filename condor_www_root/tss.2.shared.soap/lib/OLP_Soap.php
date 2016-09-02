<?php
	
	/**
		
		@desc Encapsulates the tss_loan_request element of
		an OLP SOAP request.
		
	*/
	class OLP_Request
	{
		
		protected $received;
		protected $signature;
		protected $collection;
		
		public function __construct($xml = NULL)
		{
			
			if (is_string($xml))
			{
				$this->From_XML($xml);
			}
			
		}
		
		public function &Signature($signature = NULL)
		{
			
			if ($signature instanceof OLP_Signature)
			{
				$this->signature = $signature;
			}
			
			return($this->signature);
			
		}
		
		public function &Collection($collection = NULL)
		{
			
			if ($collection instanceof OLP_Collection)
			{
				$this->collection = $collection;
			}
			elseif (is_array($collection))
			{
				$this->collection = new OLP_Collection($collection, $this->Page());
			}
			
			return($this->collection);
			
		}
		
		public function License_Key()
		{
			
			$license_key = FALSE;
			
			if ($this->signature)
			{
				$license_key = $this->signature->Value('license_key');
			}
			
			return($license_key);
			
		}
		
		public function Site_Type()
		{
			
			$site_type = FALSE;
			
			if ($this->signature)
			{
				$site_type = $this->signature->Value('site_type');
			}
			
			return($site_type);
			
		}
		
		public function Page()
		{
			
			$page = FALSE;
			
			if ($this->signature)
			{
				$page = $this->signature->Value('page');
			}
			
			return($page);
			
		}
		
		public function Unique_ID()
		{
			
			$unique_id = FALSE;
			
			if ($this->signature)
			{
				$unique_id = $this->signature->Value('unique_id');
			}
			
			return($unique_id);
			
		}
		
		public function Email()
		{
			
			$email = FALSE;
			
			if ($this->collection)
			{
				$email = $this->collection->Value('email_primary');
			}
			
			return($email);
			
		}
		
		public function Received()
		{
			
			return($this->received);
			
		}
		
		public function Data()
		{
			
			$user_data = FALSE;
			
			if ($this->signature && $this->collection)
			{
				
				$sig_data = $this->signature->To_Array();
				$user_data = $this->collection->To_Array();
				
				// translate the "legal" request to the esig page
				// for OLP
				$user_data = array_merge($user_data, $sig_data);
				if ($user_data['page']=='legal') $user_data['page'] = 'esig';
				
			}
			
			return($user_data);
			
		}
		
		/**
			
			@desc Converts the request from XML
				to our objectimified structure.
			
		*/
		public function From_XML($xml)
		{
			
			// save this
			$this->received = $xml;
			
			// parse this XML
			$xml = '<?xml version="1.0"?>' . $xml;
			$simple_xml = @simplexml_load_string($xml);
			
			if (is_object($simple_xml))
			{
				
				// build the signature and collection objects:
				// very straightforward
				$this->signature = new OLP_Signature($simple_xml);
				$this->collection = new OLP_Collection($simple_xml, $this->Page());
				
				// build a session ID if we don't
				// already have one
				if (!$this->signature->Value('unique_id'))
				{
					$session_id = md5(microtime());
					$this->signature->Value('unique_id', $session_id);
				}
				
			}
			else
			{
				throw new Exception('Malformed or invalid request.');
			}
			
			unset($simple_xml);
			return;
			
		}
		
		public function To_XML()
		{
			
			$xml = '<tss_loan_request>';
			$xml .= $this->signature->To_XML();
			$xml .= $this->collection->To_XML();
			$xml .= '</tss_loan_request>';
			
			return($xml);
			
		}
		
	}
	
	class OLP_Pages
	{
		
		/**
			
			@desc Returns a content object for the page
				specified in the EDS response. NOTE: This is
				based	on the page name returned from OLP, not
				the	SOAPimified page name. These may or may not
				be the same.
			
		*/
		public static function From_EDS_Response(&$eds_response)
		{
			
			$page = NULL;
			$content = FALSE;
			
			if (isset($eds_response->page)) $page = strtolower($eds_response->page);
			
			switch($page)
			{
				
				case 'verify_address':
					$content = self::Page_Verify_Address($eds_response);
					break;
					
				case 'esig':
					$content = self::Page_ESignature($eds_response);
					break;
					
				case 'preview_docs':
					$content = self::Page_Legal($eds_response);
					break;
					
				case 'bb_thanks':
					$content = self::Page_Thanks($eds_response);
					break;
					
				case 'app_done_paperless':
					$content = self::Page_Thanks_Enterprise($eds_response);
					break;
					
				default:
					$content = self::Page_Declined();
					break;
					
			}
			
			return($content);
			
		}
		
		/**
			
			@desc Translate the EDS page name into the correct
				page name for the SOAP guys. I don't actually change
				the page name on the eds object, because it's
				needed later to generate the correct content
			
		*/
		public static function EDS_Page(&$eds_response)
		{
			
			$event = '';
			$page = '';
			
			if (isset($eds_response->page))
			{
				$page = strtolower($eds_response->page);
			}
			
			switch ($page)
			{
				
				// we're finished!
				case 'bb_thanks':
				case 'app_done_paperless':
					$page = 'app_completed';
					break;
					
				// esig is legal to the SOAP guys
				case 'esig':
					$page = 'legal';
					break;
					
				// not sure if cs_login still needs to
				// be here or not.. but I left it
				case 'cs_login':
				case 'app_declined':
					$page = 'app_declined';
					break;
					
				case 'app_allinone':
					$page = 'app_allinone';
					break;
					
				case 'preview_docs':
					$page = 'preview_docs';
					break;
					
				// we shouldn't really ever get anything
				// else, but... just in case: also set
				// $eds_response->page so the right page
				// gets generated
				default:
					$page = 'app_declined';
					$eds_response->page = 'app_declined';
					break;
				
			}
			
			return($page);
			
		}
		
		private static function Page_Thanks(&$eds_response)
		{
			
			$content = new OLP_Content();
			$text = '';
			
			if (isset($eds_response->eds_page['content']))
			{
				$text = $eds_response->eds_page['content'];
			}
			
			$section = new OLP_Section($text);
			$content->Add_Section($section);
			
			return($content);
			
		}
		
		private static function Page_Thanks_Enterprise(&$eds_response)
		{
			
			$content = new OLP_Content();
			$name_first = '';
			
			if (isset($eds_response->data['name_first']))
			{
				$name_first = $eds_response->data['name_first'];
			}
			
			$text = 'Getting your CASH DEPOSITED in your account is as easy as 1-2-3!<br />Welcome '.$name_first.'!
				Your cash is waiting!<br/>Your information &amp; application have been successfully submitted.';
			$content->Add_Section(new OLP_Section($text));
			
			$text = 'Thank you for your application!<br/><br/>You will receive an e-mail from us
				momentarily.<br/>Due to increasing e-mail restrictions, this email-may accidentally be
				marked as<br/>spam and sent to your Bulk Mail (Yahoo!), Junk Mail (MSN Hotmail) or
				Spam (AOL)<br/>folder.<br />PLEASE CHECK YOUR INBOX AND ANY SPAM FOLDERS FOR YOUR
				CONFIRMATION EMAIL!<br />You <u>must</u>follow the directions and confirm your details
				provided in your e-mail in<br />order for us to process your loan and get your cash to you!';
			$content->Add_Section(new OLP_Section($text));
			
			return($content);
			
		}
		
		public static function Page_Declined()
		{
			
			$content = new OLP_Content();
			
			$text = '<p>We\'re sorry but you do not qualify for a payday loan at this time.</p>';
			
			// create a new verbiage section
			$section = new OLP_Section($text);
			$content->Add_Section($section);
			
			return($content);
			
		}
		
		private static function Page_Verify_Address(&$eds_response)
		{
			
			$states = array('AK', 'AL', 'AR', 'AZ', 'CA', 'CO', 'CT', 'DC', 'DE', 'FL', 'GA', 'HI', 'IA', 'ID', 'IL',
				'IN', 'KS', 'KY', 'LA', 'MA', 'MD', 'ME', 'MI', 'MN', 'MO', 'MS', 'MT', 'NC', 'ND', 'NE', 'NH', 'NJ',
				'NM', 'NV', 'NY', 'OH', 'OK', 'OR', 'PA', 'PR', 'RI', 'SC', 'SD', 'TN', 'TX', 'UT', 'VA', 'VI', 'VT',
				'WA', 'WI', 'WV', 'WY');
			
			$content = new OLP_Content();
			
			$text = "We checked US Postal Service records and didn't find your address. Please confirm your address.";
			$content->Add_Section(new OLP_Section($text));
			
			$question = new OLP_Question('text');
			$question->Options('home_street', array());
			$content->Add_Section(new OLP_Section('Address', $question));
			
			$question = new OLP_Question('text');
			$question->Options('home_unit', array());
			$content->Add_Section(new OLP_Section('Apartment', $question));
			
			$question = new OLP_Question('text');
			$question->Options('home_city', array());
			$content->Add_Section(new OLP_Section('City', $question));
			
			$question = new OLP_Question('combo');
			$question->Options('home_state', $states);
			$content->Add_Section(new OLP_Section('State', $question));
			
			$question = new OLP_Question('text');
			$question->Options('home_zip', array());
			$content->Add_Section(new OLP_Section('Zip Code', $question));
			
			return($content);
			
		}
		
		private static function Page_Legal(&$eds_response)
		{
			
			if (isset($eds_response->eds_page) && array_key_exists('content', $eds_response->eds_page))
			{
				
				// hijack legal document from EDS response
				$doc = $eds_response->eds_page['content'];
				
				$content = new OLP_Content();
				
				$section = new OLP_Section($doc);
				$content->Add_Section($section);
				
			}
			else
			{
				throw new Exception('Legal document is missing.');
			}
			
			return($content);
			
		}
		
		private static function Page_ESignature(&$eds_response)
		{
			
			// hijacked CSS
	   	$css = "
				<style>
					#wf-legal-section {	margin: 0 15px 0 15px;  padding-top: 10px; }
					.wf-legal-block {	background-color: #FFFFFF; margin 0; padding: 0; }	
					.wf-legal-title {	background-color: #000000; color:#FFFFFF; font-size: 20px; font-weight: bold;
						text-align: left; width: auto; padding: 10px 0 10px 15px; }
					.wf-legal-table { padding: 0px; margin: auto; width: auto; }
					.wf-legal-table-cell, .wf-legal-table-cell h2, .wf-legal-table-cell h3  {
						padding: 3px; margin: 0; }
					.wf-legal-table-cell-terms { background-color: #FFFFFF; width: 50%; padding: 4px;
						margin: 0; text-align: center; border-top: 5px solid black; border-bottom: 5px solid black; }
					.wf-legal-table-cell-schedule { background-color: #FFFFFF; width: 50%; padding: 6px 8px  6px 10px;
						margin: 0; text-align: left; font-size: 10px; border-top: 5px solid black; border-bottom: 5px solid black; }
					.wf-legal-copy { font-size: 11px; text-align: left; padding: 0 15px 0 15px; }
					.wf-legal-copy li { font-size: 12px; list-style: none; margin: 3px; }
					.wf-legal-link { font-size: 10px; color: blue; }
				</style>
			";
			
			// get our esignature
			$esig = $eds_response->data['name_first'].' '.$eds_response->data['name_last'];
			
			$content = new OLP_Content();
			
			// top of the page
			$section = new OLP_Section('<h2 align="center">LOAN ACCEPTANCE & eSIGNATURE</h2>');
			$content->Add_Section($section);
			
			// some text
			$text = 'The terms of your loan are described in the
				<strong><a href="#" onClick="'.self::Open_Legal_Window('preview_docs', 'loan_note_and_disclosure').'">LOAN
				NOTE AND DISCLOSURE</a></strong> found below. Please review and accept the following documents.';
			
			// add this section
			$section = new OLP_Section($text);
			$content->Add_Section($section);
			
			// APPLICATION
			
			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', 'application').'">application</a></strong>.';
			
			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_1', array('TRUE', 'FALSE'));
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// PRIVACY POLICY
			
			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', 'privacy_policy').'">privacy
				policy</a></strong>.';
			
			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_2', array('TRUE', 'FALSE'));
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// AUTHORIZATION AGREEMENT
			
			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', 'authorization_agreement').'">authorization
				agreement</a></strong>.';
			
			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_3', array('TRUE', 'FALSE'));
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// LOAN NOTE and DISCLOSURE
			
			$text = 'I have read and accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', 'loan_note_and_discloser').'">loan
				note and disclosure</a></strong>.';
			
			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_4', array('TRUE', 'FALSE'));
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// LOAN NOTE and DISCLOSURE
			
			$text = 'To accept the terms of the
				<strong><a href="#" onclick="'.self::Open_Legal_Window('preview_docs', 'loan_note_and_discloser').'">loan
				note and disclosure</a></strong>, provide your <strong>Electronic Signature</strong> by typing your
				full name below. This signature should appear as: JOHN DOE.';
			
			$question = new OLP_Question('radio');
			$question->Options('legal_approve_docs_4', array('TRUE', 'FALSE'));
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// ESIGNATURE
			
			$text = '<b>eSIGNATURE</b> Enter your full name in the box.';
			
			$question = new OLP_Question('text');
			$question->Options('esignature', '');
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// AGREE
			
			$text = 'I AGREE - Send Me My Cash';
			
			$question = new OLP_Question('radio');
			$question->Options('legal_agree', array('TRUE', 'FALSE'));
			
			$section = new OLP_Section($text, $question);
			$content->Add_Section($section);
			
			// LOAN DOCUMENT
			
			// hijack the legal document from the EDS response
			$doc = $css . $eds_response->data['esig_doc'];
			
			$section = new OLP_Section($doc);
			$content->Add_Section($section);
			
			return($content);
			
		}
		
		private static function Open_Legal_Window($page, $anchor = NULL)
		{
			
			if (!is_null($anchor)) $anchor = "#{$anchor}";
			
			$options = 'width=800,height=600,resizable=yes,scrollbars=yes,location=yes,toolbar=yes,menubar=yes';
			$out = "window.open('?page={$page}{$anchor}', 'tss_win', '{$options}'); return false;";
			
			return($out);
			
		}
		
	}
	
	/**
		
		@desc Encapsulate the <tss_loan_response> element
		
	*/
	class OLP_Response
	{
		
		private $received;
		private $signature;
		private $errors;
		private $content;
		private $collection;
		
		public function __construct(&$eds_response = NULL, $session_id = NULL)
		{
			
			if (is_object($eds_response))
			{
				$this->From_EDS_Response($eds_response);
			}
			elseif (is_string($eds_response))
			{
				$this->From_XML($eds_response);
			}
			
			if (is_string($session_id) && $this->signature)
			{
				$this->signature->Value('unique_id', $session_id);
			}
			
		}
		
		/**
			
			@desc Build a response object for a declined loan,
				used for errors and such.
			
		*/
		public static function Declined(&$request)
		{
			
			$new = New OLP_Response();
			
			// steal the signature from our request,
			// but set the page to declined
			$new->signature = $request->Signature();
			$new->signature->Value('page', 'app_declined');
			
			// no errors, and steal the request collection
			$new->errors = new OLP_Errors();
			$new->collection = $request->Collection();
			
			// the declined page
			$new->content = OLP_Pages::Page_Declined();
			
			return($new);
			
		}
		
		public function &Signature($signature = NULL)
		{
			
			if ($signature instanceof OLP_Signature)
			{
				$this->signature = $signature;
			}
			
			return($this->signature);
			
		}
		
		public function &Errors($errors = NULL)
		{
			
			if ($errors instanceof OLP_Errors)
			{
				$this->errors = $errors;
			}
			
			return($this->errors);
			
		}
		
		public function &Collection($collection = NULL)
		{
			
			if ($collection instanceof OLP_Collection)
			{
				$this->collection = $collection;
			}
			
			return($this->collection);
			
		}
		
		public function &Content($content = NULL)
		{
			
			if ($content instanceof OLP_Content)
			{
				$this->content = $content;
			}
			
			return($this->content);
			
		}
		
		public function Received()
		{
			return($this->received);
		}
		
		public function Page()
		{
			
			$page = FALSE;
			
			if ($this->signature)
			{
				$page = $this->signature->Value('page');
			}
			
			return($page);
			
		}
		
		public function Unique_ID()
		{
			
			$unique_id = FALSE;
			
			if ($this->signature)
			{
				$unique_id = $this->signature->Value('unique_id');
			}
			
			return($unique_id);
			
		}
		
		public function Email()
		{
			
			$email = FALSE;
			
			if ($this->collection)
			{
				$email = $this->collection->Value('email_primary');
			}
			
			return($email);
			
		}
		
		public function From_XML($xml)
		{
			
			// save this
			$this->received = $xml;
			
			// parse this XML
			$xml = '<?xml version="1.0"?>' . $xml;
			$simple_xml = @simplexml_load_string($xml);
			
			if (is_object($simple_xml))
			{
				
				// build the signature and collection objects:
				// very straightforward
				$this->signature = new OLP_Signature($simple_xml);
				$this->collection = new OLP_Collection($simple_xml, $this->Page());
				$this->errors = new OLP_Errors($simple_xml);
				$this->content = new OLP_Content($simple_xml);
				
			}
			else
			{
				throw new Exception('Malformed or invalid response.');
			}
			
			unset($simple_xml);
			return;
			
		}
		
		public function To_XML()
		{
			
			$xml = '<tss_loan_response>';
			if ($this->signature)	$xml .= $this->signature->To_XML();
			if ($this->errors) $xml .= $this->errors->To_XML();
			if ($this->content) $xml .= $this->content->To_XML();
			if ($this->collection) $xml .= $this->collection->To_XML();
			$xml .= '</tss_loan_response>';
			
			return($xml);
			
		}
		
		public function From_EDS_Response(&$eds_response)
		{
			
			// build our individiual objects
			$this->signature = new OLP_Signature();
			$this->signature->From_EDS_Response($eds_response);
			
			// build our errors
			$this->errors = new OLP_Errors();
			$this->errors->From_EDS_Response($eds_response);
			
			// build our page
			if (!count($this->errors->To_Array()))
			{
				$this->content = OLP_Pages::From_EDS_Response($eds_response);
			}
			else
			{
				$this->content = new OLP_Content();
			}
			
			$this->collection = New OLP_Collection();
			$this->collection->From_EDS_Response($eds_response);
			return;
			
		}
		
	}
	
	/**
		
		@desc Handles an "array" of simple elements.
			
			Most of the content of the XML passed in our SOAP
			calls is made up of a "base" element containing
			a bunch of data elements. For instance:
			
			<tss_loan_response>
				<errors><data name="income_monthly_net">Your income...
				<signature><data name="site_type">blackbox.one.page</...
				[...]
			</tss_loan_response>
			
			This class makes it easy to work with these
			elements.
		
	*/
	class XML_Data_Array
	{
		
		protected $base_element;
		protected $data_element;
		protected $name_attribute;
		
		protected $allowed;
		protected $data;
		
		public function __construct($base_element, $data_element = 'data', $name_attribute = 'name', $data = NULL)
		{
			
			$this->base_element = $base_element;
			$this->data_element = $data_element;
			
			$this->data = array();
			
			if (is_string($data))
			{
				$this->From_XML($data);
			}
			elseif (is_array($data))
			{
				$this->From_Array($data);
			}
			elseif (is_object($data))
			{
				$this->From_Simple_XML($data);
			}
			
		}
		
		public function Value($name, $value = NULL)
		{
			
			if (!is_null($value))
			{
				if ((!is_array($this->allowed)) || (in_array($name, $this->allowed)))
				{
					$this->data[$name] = $value;
				}
			}
			else
			{
				if (array_key_exists($name, $this->data))
				{
					$value = $this->data[$name];
				}
			}
			
			return($value);
			
		}
		
		public function From_XML($xml)
		{
			
			$simple = simplexml_load_string($xml);
			$this->From_Simple_XML($simple);
			
			return($this->data);
			
		}
		
		public function To_XML()
		{
			
			if (count($this->data))
			{
				
				// make things pretty: this will pu the data array
				// in the same order as the allowed array
				if (is_array($this->allowed))
				{
					$order = array_intersect(array_keys($this->allowed), array_keys($this->data));
					$this->data = array_merge($order, $this->data);
				}
				
				$xml = "<{$this->base_element}>";
				
				foreach ($this->data as $name=>$value)
				{
					if ($value != '')
					{
						$xml .= "<{$this->data_element} {$this->name_attribute}=\"{$name}\">{$value}</{$this->data_element}>";
					}
					else
					{
						$xml .= "<{$this->data_element} {$this->name_attribute}=\"{$name}\"/>";
					}
				}
				
				$xml .= "</$this->base_element>";
				
			}
			else
			{
				$xml = "<{$this->base_element}/>";
			}
			
			return($xml);
			
		}
		
		public function From_Simple_XML($simple)
		{
			
			// find all data_elements under the base_element
			$elements = $simple->xpath("//{$this->base_element}/{$this->data_element}");
			
			// reset
			$this->data = array();
			
			foreach ($elements as $element)
			{
				
				$attr = $element->attributes();
				$name = (string)$attr[$this->name_attribute];
				
				// make sure it's either allowed, or we
				// are allowing anything
				if ((!is_array($this->allowed)) || (in_array($name, $this->allowed)))
				{
					$this->data[$name] = trim((string)$element);
				}
				
			}
			
			return($this->data);
			
		}
		
		public function From_Array($array)
		{
			
			// reset
			$this->data = array();
			
			if (is_array($array))
			{
				
				// if we're allowing anything,
				// just bring the whole array over
				if (!is_array($this->allowed))
				{
					$this->data = $array;
				}
				else
				{
					
					// figure out what both have
					$names = array_intersect(array_keys($array), $this->allowed);
					
					foreach ($names as $name)
					{
						$this->data[$name] = $array[$name];
					}
					
				}
				
			}
			
			return;
			
		}
		
		public function To_Array()
		{
			
			return($this->data);
			
		}
		
		public function Allowed()
		{
			
			return($this->allowed);
			
		}
		
	}
	
	/**
		
		@desc Encapsulate the <content> tag.
		
	*/
	class OLP_Content
	{
		
		protected $sections;
		
		public function __construct($data = NULL)
		{
			
			$this->sections = array();
			
			if (is_object($data))
			{
				$this->From_Simple_XML($data);
			}
			
		}
		
		public function Sections()
		{
			
			return($this->sections);
			
		}
		
		public function Add_Section($section)
		{
			
			$this->sections[] = $section;
			return;
			
		}
		
		public function To_XML()
		{
			
			if (count($this->sections))
			{
				
				$xml = '<content>';
				
				foreach ($this->sections as &$section)
				{
					$xml .= $section->To_XML();
				}
				
				$xml .= '</content>';
				
			}
			else
			{
				$xml = '<content/>';
			}
			
			return($xml);
			
		}
		
		public function From_Simple_XML($simple)
		{
			
			// find all content/section elements with a verbiage
			// or question (child) element
			$sections = $simple->xpath('//content/section[verbiage|question]');
			
			foreach ($sections as $element)
			{
				
				// parse the section
				$section = new OLP_Section();
				$section->From_Simple_XML($element);
				
				// add it to our list
				$this->sections[] = $section;
				
			}
			
		}
		
	}
	
	/**
		
		@desc Encapsulate the <section> element. This
			lives under the <content> element, and may contain
			a <verbiage> or <question> element (or both).
		
	*/
	class OLP_Section
	{
		
		protected $verbiage;
		protected $question;
		
		public function __construct($verbiage = NULL, $question = NULL)
		{
			
			if (is_string($verbiage)) $this->verbiage = $verbiage;
			if ($question instanceof OLP_Question) $this->question = $question;
			
		}
		
		public function Verbiage($text = NULL)
		{
			
			if (is_string($text)) $this->verbiage = $text;
			return($this->verbiage);
			
		}
		
		public function Question($question = NULL)
		{
			
			if ($question instanceof OLP_Question) $this->question = $question;
			return($this->question);
			
		}
		
		public function To_XML()
		{
			
			$xml = "<section>";
			
			if ($this->verbiage)
			{
				
				$xml .= "<verbiage>";
				
				// if we don't have any <'s, assume it's
				// just plain text - otherwise, use a
				// CDATA tag
				if (strpos($this->verbiage, '<')===FALSE) $xml .= $this->verbiage;
				else $xml .= "<![CDATA[{$this->verbiage}]]>";
				
				$xml .= "</verbiage>";
				
			}
			
			if ($this->question)
			{
				$xml .= $this->question->To_XML();
			}
			
			$xml .= "</section>";
			
			return($xml);
			
		}
		
		/**
			
			@todo Finish this!
			
		*/
		public function From_XML($xml)
		{
			
			
			
		}
		
		public function From_Simple_XML($simple)
		{
			
			if (isset($simple->verbiage))
			{
				$this->verbiage = (string)$simple->verbiage;
			}
			
			if (isset($simple->question))
			{
				
				$question = new OLP_Question();
				$question->From_Simple_XML($simple->question);
				
				$this->question = $question;
				
			}
			
		}
		
	}
	
	/**
		
		@desc Encapsulate the <question> element:
			this contains one or more <option> elements.
		
	*/
	class OLP_Question
	{
		
		protected $recommend;
		protected $options;
		
		public function __construct($recommend = NULL)
		{
			
			if (is_String($recommend)) $this->recommend = $recommend;
			
			$this->options = array();
			
		}
		
		public function Recommend($type = NULL)
		{
			
			if (is_string($type)) $this->recommend = $type;
			return($this->recommend);
			
		}
		
		/**
			
			@desc Add or return the options for this question.
			
			Not sure why it was done like this, but each
			question contains option elements, and each
			option element has a name attribute. In theory,
			then, a question could contain multiple HTML
			fields. In practice, I don't think this happens,
			but I left it as-is.
			
		*/
		public function Options($name = NULL, $option = NULL)
		{
			
			$options = FALSE;
			
			if (!is_null($name))
			{
				
				if (!is_null($option))
				{
					
					// check to see if we already have options
					// using this field name
					if (!array_key_exists($name, $this->options))
					{
						
						// create a new entry for this name
						if (is_array($option)) $this->options[$name] = $option;
						else $this->options[$name] = array($option);
						
					}
					else
					{
						
						// add to the entry for this name
						if (is_array($option)) $this->options[$name] = array_merge($this->options[$name], $option);
						else $this->options[$name][] = $option;
						
					}
					
					// return options for this name
					$options = $this->options[$name];
					
				}
				elseif (array_key_exists($name, $this->options))
				{
					// return options for this name
					$options = $this->options[$name];
				}
				
			}
			else
			{
				// return all options
				$options = $this->options;
			}
			
			return($options);
			
		}
		
		public function To_XML()
		{
			
			$xml = "<question recommend=\"{$this->recommend}\">";
			
			// get each field that has defined options
			foreach ($this->options as $name=>$options)
			{
				
				if (is_array($options) && count($options))
				{
					
					// get the options for this field name
					foreach ($options as $value)
					{
						$xml .= "<option name=\"{$name}\">{$value}</option>";
					}
					
				}
				else
				{
					$xml .= "<option name=\"{$name}\"/>";
				}
				
			}
			
			$xml .= "</question>";
			
			return($xml);
			
		}
		
		public function From_Simple_XML($simple)
		{
			
			$attr = $simple->attributes();
			
			if (array_key_exists('recommend', $attr))
			{
				$this->recommend = $attr['recommend'];
			}
			
			// get all option elements with
			// a name attribute
			$options = $simple->xpath('//option[@name]');
			
			foreach ($options as $element)
			{
				
				$attr = $element->attributes();
				$name = (string)$attr['name'];
				$option = (string)$element;
				
				$this->Options($name, $option);
				
			}
			
		}
		
	}
	
	class OLP_Signature extends XML_Data_Array 
	{
		
		protected $base_element = 'signature';
		protected $data_element = 'data';
		protected $name_attribute = 'name';
		
		protected $allowed = array
		(
			'site_type',
			'page',
			'license_key',
			'promo_id',
			'unique_id',
			'promo_sub_code',
			'pwadvid',
		);
		
		public function __construct($data = NULL)
		{
			
			if (is_string($data))
			{
				parent::From_XML($data);
			}
			elseif (is_array($data))
			{
				parent::From_Array($data);
			}
			elseif (is_object($data))
			{
				parent::From_Simple_XML($data);
			}
			
		}
		
		public function From_EDS_Response(&$eds_response)
		{
			
			$page = OLP_Pages::EDS_Page($eds_response);
			//$eds_response->page = $page;
			
			// set our local values
			$this->Value('page', $page);
			$this->Value('site_type', $eds_response->data['site_type']);
			$this->Value('license_key', $eds_response->data['license_key']);
			$this->Value('unique_id', $eds_response->data['session_id']);
			$this->Value('promo_id', $eds_response->data['promo_id']);
			
			return;
			
		}
		
	}
	
	class OLP_Collection extends XML_Data_Array
	{
		
		protected $base_element = 'collection';
		protected $data_element = 'data';
		protected $name_attribute = 'name';
		
		// used for validation
		protected $page = NULL;
		
		protected $by_page = array(
			
			'ALL' => array(
				// allowed on all pages
				'client_url_root',
				'client_ip_address',
			),
			
			'app_allinone' => array(
				
				// personal information
				'name_first',
				'name_last',
				'name_middle',
				'ssn_part_1',
				'ssn_part_2',
				'ssn_part_3',
				'state_id_number',
				'citizen',
				'date_dob_d',
				'date_dob_m',
				'date_dob_y',
				
				// contact information
				'phone_home',
				'phone_work',
				'ext_work',
				'phone_cell',
				'phone_fax',
				'best_call_time',
				'email_primary',
				'email_alternate',
				
				// residence information
				'residence_type',
				'home_street',
				'home_unit',
				'home_city',
				'home_state',
				'home_zip',
				
				// employment information
				'employer_length',
				'employer_name',
				'shift',
				
				// income information
				'income_type',
				'income_monthly_net',
				'income_frequency',
				'income_stream',
				'income_direct_deposit',
				'income_date1_d',
				'income_date1_m',
				'income_date1_y',
				'income_date2_d',
				'income_date2_m',
				'income_date2_y',
				
				// banking information
				'bank_name',
				'bank_aba',
				'bank_account',
				'bank_account_type',
				'checking_account',
				
				// personal references
				'ref_01_name_full',
				'ref_01_phone_home',
				'ref_01_relationship',
				'ref_02_name_full',
				'ref_02_phone_home',
				'ref_02_relationship',
				
				'offers',
				'legal_notice_1',
				'monthly_1200',
				'cali_agree',
				
				// debugging
				'no_checks',
				'use_tier',
				'ssforce'
				
			),
			
			'legal' => array(
				
				// esignature page
				'legal_approve_docs_1',
				'legal_approve_docs_2',
				'legal_approve_docs_3',
				'legal_approve_docs_4',
				'legal_agree',
				'esignature',
				
			),
			
		);
		
		protected $allowed = array();
		
		public function __construct($data = NULL, $page = NULL)
		{
			
			if (is_string($data))
			{
				$this->From_XML($data, $page);
			}
			elseif (is_array($data))
			{
				$this->From_Array($data, $page);
			}
			elseif (is_object($data))
			{
				$this->From_Simple_XML($data, $page);
			}
			
		}
		
		public function From_Array($array, $page = NULL)
		{
			
			$this->allowed = $this->Get_Allowed($page);
			$result = parent::From_Array($array);
			
			return($result);
			
		}
		
		public function From_XML($xml, $page = NULL)
		{
			
			$this->allowed = $this->Get_Allowed($page);
			$result = parent::From_XML($xml);
			
			return($result);
			
		}
		
		public function From_Simple_XML($simple, $page = NULL)
		{
			
			$this->allowed = $this->Get_Allowed($page);
			$result = parent::From_Simple_XML($simple);
			
			return($result);
			
		}
		
		public function From_EDS_Response(&$eds_response)
		{
			
			// get the page name
			$page = OLP_Pages::EDS_Page($eds_response);
			
			// get our data
			$data = &$eds_response->data;
			$this->From_Array($data, $page);
			
			return;
			
		}
		
		protected function Get_Allowed($page)
		{
			
			if (($page !== NULL) && isset($this->by_page[$page]) && is_array($this->by_page[$page]))
			{
				// get our allowed fields for this page
				$allowed = $this->by_page[$page];
			}
			
			// get fields allowed on all pages
			if (isset($this->by_page['ALL']) && is_array($this->by_page['ALL']))
			{
				if (!isset($allowed)) $allowed = array();
				$allowed = array_merge($allowed, $this->by_page['ALL']);
			}
			
			return($allowed);
			
		}
		
	}
	
	class OLP_Errors extends XML_Data_Array 
	{
		
		protected $base_element = 'errors';
		protected $data_element = 'data';
		protected $name_attribute = 'name';
		
		protected $allowed = NULL;
		
		public function __construct($data = NULL)
		{
			
			if (is_string($data))
			{
				parent::From_XML($data);
			}
			elseif (is_array($data))
			{
				parent::From_Array($data);
			}
			elseif (is_object($data))
			{
				parent::From_Simple_XML($data);
			}
			
		}
		
		/**
			
			@desc Translate EDS errors to SOAP error codes:
				this is _really_ ugly, and should be fixed!
				
				Why, oh why, are we using different field names
				for the errors?
			
		*/
		public function From_EDS_Response(&$eds_response)
		{
			
			$err = new Error_Message_Resource();
			
			$error_codes = NULL;
			$errors = NULL;
			
			if (isset($eds_response->errors))
			{
				$error_codes = &$eds_response->errors;
			}
			
			if (is_array($error_codes))
			{
				
				$errors = array();
				
				foreach ($error_codes as $field)
				{
					
					$descr = $err->Get_Error_Desc($field);
					
					if ($field=='social_security_number')
					{
						$field = array('ssn_part_1', 'ssn_part_2', 'ssn_part_3');
					}
					elseif ($field=='dob')
					{
						$field = array('date_dob_y', 'date_dob_m', 'date_dob_d');
					}
					elseif (substr($field, 0, 9)=='pay_date1')
					{
						$field = array('income_date1_y', 'income_date1_m', 'income_date1_d');
					}
					elseif (substr($field, 0, 9)=='pay_date2')
					{
						$field = array('income_date2_y', 'income_date2_m', 'income_date2_d');
					}
					elseif ($field == 'too_many_twice_monthly' || $field == 'too_many_monthly')
					{
						$field = array('income_date1_y', 'income_date1_m', 'income_date1_d', 
							'income_date2_y', 'income_date2_m', 'income_date2_d');
					}
					elseif ($field == 'cali_agree_conditional')
					{
						$field = 'cali_agree';
					}
					
					if (is_array($field))
					{
						foreach ($field as $name) $errors[$name] = $descr;
					}
					else $errors[$field] = $descr;
					
				}
				
				$this->From_Array($errors);
				
			}
			
			return;
			
		}
		
		private static function Translate_Fields($errors)
		{
			
			if ($error_field=='social_security_number')
			{
				$error_field = 'ssn_part_1';
			}
			if ($error_field=='dob')
			{
				$error_field = 'date_dob_d';
			}
			elseif (substr($error_field, 0, 9)=='pay_date1')
			{
				$error_field = 'income_date1_d';
			}
			elseif (substr($error_field, 0, 9)=='pay_date2')
			{
				$error_field = 'income_date2_d';
			}
			
		}
		
	}
	
?>