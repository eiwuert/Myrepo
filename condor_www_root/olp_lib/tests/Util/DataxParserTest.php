<?php

require('olp_lib_setup.php');

/** PHPUnit test class for the Util_DataxParser class.
 *
 * @author Ryan Murphy <ryan.murphy@sellingsource.com>
 */
class Util_DataxParserTest extends PHPUnit_Framework_TestCase
{
	/** Checks the ERROR packet for the error valid. Should return in a nice
	 * printable format.
	 *
	 * @return void
	 */
	public function testError()
	{
		$dom = new Util_DataxParser($this->getXML('ERROR'));
		
		$this->assertTrue($dom->isValid());
		$this->assertEquals('S-091 - INTERNAL ERROR; PLEASE CONTACT YOUR DATAX TECHNICAL REPRESENTATIVE', $dom->getError());
	}
	
	/** Checks the INVALID packet to make sure functions return NULL.
	 *
	 * @return void
	 */
	public function testInvalid()
	{
		$dom = new Util_DataxParser($this->getXML('INVALID'));
		
		$this->assertFalse($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertNull($dom->getDecisionCode());
		$this->assertNull($dom->getDecisionResult());
		$this->assertNull($dom->searchOneNode('//NonExistantTag'));
	}
	
	/** Checks an empty packet to make sure functions return NULL.
	 *
	 * @return void
	 */
	public function testEmpty()
	{
		$dom = new Util_DataxParser($this->getXML(''));
		
		$this->assertFalse($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertNull($dom->getDecisionCode());
		$this->assertNull($dom->getDecisionResult());
		$this->assertNull($dom->searchOneNode('//NonExistantTag'));
	}
	
	/** Checks the SENT packet to find query type.
	 *
	 * @return void
	 */
	public function testSent()
	{
		$dom = new Util_DataxParser($this->getXML('SENT'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertEquals('FBOD-PERF', $dom->searchOneNode('/DATAXINQUERY/QUERY/TYPE'));
		$this->assertEquals('TU-D2', $dom->searchOneNode('/DATAXINQUERY/AUTHENTICATION/FORCE'));
	}
	
	/** Checks the IMPACT-IDVE packet.
	 *
	 * @return void
	 */
	public function testImpactIdve()
	{
		$dom = new Util_DataxParser($this->getXML('IMPACT-IDVE'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertEquals('N', $dom->getDecisionResult());
		$this->assertEquals('IDV-D5', $dom->getDecisionCode());
		$this->assertEquals('333', $dom->searchOneNode('//AuthenticationScore'));
	}
	
	/** Checks the IMPACTPDL-IDVE packet.
	 *
	 * @return void
	 */
	public function testImpactpdlIdve()
	{
		$dom = new Util_DataxParser($this->getXML('IMPACTPDL-IDVE'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertEquals('Y', $dom->getDecisionResult());
		$this->assertEquals('IDV-A1,CRA-A1', $dom->getDecisionCode());
		$this->assertEquals('609', $dom->searchOneNode('//AuthenticationScore'));
	}
	
	/** Checks the PERF-L3 packet.
	 *
	 * @return void
	 */
	public function testPerfL3()
	{
		$dom = new Util_DataxParser($this->getXML('PERF-L3'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertEquals('Y', $dom->getDecisionResult());
		$this->assertEquals('R3,P1', $dom->getDecisionCode());
		$this->assertEquals('0', $dom->searchOneNode('//Summary/Bankruptcy'));
	}
	
	/** Checks the AALM-PERF packet.
	 *
	 * @return void
	 */
	public function testAalmPerf()
	{
		$dom = new Util_DataxParser($this->getXML('AALM-PERF'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertEquals('Y', $dom->getDecisionResult());
		$this->assertEquals('IDV-A1,CRA-A1', $dom->getDecisionCode());
		$this->assertEquals('264', $dom->searchOneNode('//AuthenticationScore'));
	}
	
	/** Checks the FBOD-PERF packet.
	 *
	 * @return void
	 */
	public function testFbodPerf()
	{
		$dom = new Util_DataxParser($this->getXML('FBOD-PERF'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertEquals('Y', $dom->getDecisionResult());
		$this->assertEquals('IDV-A5,BAV-A1,TU-A1', $dom->getDecisionCode());
		$this->assertEquals('555', $dom->searchOneNode('//VantageScore'));
		$this->assertNull($dom->searchOneNode('//NonExistantTag'));
	}
	
	/** Checks for strict on searchOneNode(). With strict checking on, if
	 * found more than one of the XPath query, will throw an exception.
	 *
	 * @return void
	 */
	public function testStrict()
	{
		$dom = new Util_DataxParser($this->getXML('STRICT'));
		
		$this->assertTrue($dom->isValid());
		$this->assertNull($dom->getError());
		$this->assertNull($dom->searchOneNode('//NonExistantTag'));
		$this->assertEquals('THIS TAG WILL BE FOUND MORE THAN ONCE.', $dom->searchOneNode('//DUPE'));
		$this->assertEquals('THIS TAG WILL ONLY BE FOUND ONCE.', $dom->searchOneNode('//SINGLE', TRUE));
		
		$this->setExpectedException('Exception');
		$this->assertNull($dom->searchOneNode('//DUPE', TRUE));
	}
	
	/** Returns sample packets of different XML types.
	 *
	 * @param string $type Which DataX packet type to return.
	 * @return string
	 */
	protected function getXML($type)
	{
		switch ($type)
		{
			case 'IMPACT-IDVE':
				// Full sample IMPACT-IDVE packet
				$xml = <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<DataxResponse>
  <GenerationTime>20070101120000</GenerationTime>
  <CodeVersion>4.0.6</CodeVersion>
  <RequestVersion>3.0</RequestVersion>
  <TransactionId>100</TransactionId>
  <TrackHash>invalidhash</TrackHash>
  <Response>
    <Detail>
      <ConsumerIDVerificationSegment>
        <Input>
          <LastName>First</LastName>
          <FirstName>Last</FirstName>
          <MiddleName/>
          <Address1>123 Main Street</Address1>
          <Address2/>
          <City>City Name</City>
          <State>AK</State>
          <Zip>99989</Zip>
        </Input>
        <Standardized>
          <LastName>FIRST</LastName>
          <FirstName>LAST</FirstName>
          <MiddleName/>
          <AddressVerification>15</AddressVerification>
          <AddressVerificationMessage>Name match unavailable</AddressVerificationMessage>
          <AddressResult>S</AddressResult>
          <DeliveryPointVerified>Y</DeliveryPointVerified>
          <Address1>123 MAIN STREET</Address1>
          <City>CITY NAME</City>
          <State>AK</State>
          <Zip>99989</Zip>
          <Zip4>1000</Zip4>
          <CRRT>C026</CRRT>
          <HighRiskAddress>N</HighRiskAddress>
        </Standardized>
        <PreviousAddresses>
          <Address>
            <Address1>123 SOUTH STREET</Address1>
            <City>OLD CITY</City>
            <State>MA</State>
            <Zip>22461</Zip>
            <Zip4>0001</Zip4>
            <ReportDate>20010101</ReportDate>
            <LastUpdate>20010101</LastUpdate>
          </Address>
          <Address>
            <Address1>582 OLD ROAD</Address1>
            <City>DEADMANVILLE</City>
            <State>WV</State>
            <Zip>38941</Zip>
            <Zip4>2941</Zip4>
            <ReportDate>20000101</ReportDate>
            <LastUpdate>20000101</LastUpdate>
          </Address>
        </PreviousAddresses>
        <SocialSecurityNumber>
          <InputSSN>888551234</InputSSN>
          <Return>16</Return>
          <ReturnMessage>Matches full name</ReturnMessage>
          <Deceased>2</Deceased>
          <DeceasedMessage>Not Deceased</DeceasedMessage>
          <Valid>1</Valid>
          <ValidMessage>Valid</ValidMessage>
          <Issue>3</Issue>
          <IssueMessage>Issued</IssueMessage>
          <IsseuState>OR</IsseuState>
          <IssueStartRange>1987</IssueStartRange>
          <IssueEndRange>1989</IssueEndRange>
        </SocialSecurityNumber>
        <Aggregates>
          <AddressUnitMismatch/>
          <AddressUnitMismatchMessage/>
          <AddressType>10</AddressType>
          <AddressTypeMessage>Single-family residential</AddressTypeMessage>
          <AddressHighRisk>1</AddressHighRisk>
          <AddressHighRiskMessage>No high risk information found</AddressHighRiskMessage>
          <ChangeOfAddress>2</ChangeOfAddress>
          <ChangeOfAddressMessage>No change of address information found</ChangeOfAddressMessage>
          <PhoneHighRisk>1</PhoneHighRisk>
          <PhoneHighRiskMessage>No high risk information found</PhoneHighRiskMessage>
        </Aggregates>
        <SSNAddresses>
          <Address>
            <LastName>FIRST</LastName>
            <FirstName>LAST</FirstName>
            <MiddleInitial>M</MiddleInitial>
            <Address1>123 MAIN STREET</Address1>
            <City>CITY NAME</City>
            <State>AK</State>
            <Zip>99989</Zip>
            <Zip4/>
            <ReportedDate>20030101</ReportedDate>
            <LastTouchedDate>20030101</LastTouchedDate>
            <AreaCode/>
            <PhoneNumber/>
            <DateOfBirthMatch>3</DateOfBirthMatch>
          </Address>
        </SSNAddresses>
        <ResidentialAddresses>
				</ResidentialAddresses>
        <HighRiskAddresses>
          <Address>
            <BusinessName/>
            <Address1/>
            <City/>
            <State/>
            <Zip/>
            <Zip4/>
            <AreaCode/>
            <PhoneNumber/>
            <Description>No high risk business at address/phone</Description>
          </Address>
        </HighRiskAddresses>
        <ResidentialPhones>
				</ResidentialPhones>
        <ChangesOfAddress>
          <ResultCode>N</ResultCode>
          <Description/>
        </ChangesOfAddress>
        <DateOfBirth>
          <InputMonth>01</InputMonth>
          <InputDay>01</InputDay>
          <InputYear>1960</InputYear>
          <Result>3</Result>
          <ResultMessage>No match</ResultMessage>
          <Age>43</Age>
        </DateOfBirth>
        <DriversLicense>
          <InputState>CA</InputState>
          <InputNumber>451571554</InputNumber>
          <LastName/>
          <FirstName/>
          <MiddleInitial/>
          <Address1/>
          <City/>
          <State/>
          <Zip/>
          <Zip4/>
          <Format>V</Format>
          <Result>6</Result>
          <ResultMessage>No match</ResultMessage>
        </DriversLicense>
        <HomePhone>
          <InputNumber>444-555-0153</InputNumber>
          <Result>21</Result>
          <ResultMessage>Data unavailable</ResultMessage>
          <Type>Standard</Type>
          <AreaCodeZipMatch>Y</AreaCodeZipMatch>
          <AreaCodePrefixMatch>Y</AreaCodePrefixMatch>
          <HighRiskPhone>N</HighRiskPhone>
          <ListingType>Residential</ListingType>
        </HomePhone>
        <CellPhone>
          <InputNumber/>
          <Type/>
        </CellPhone>
        <InputEmployerInformation>
          <Name>ACME</Name>
          <Address1/>
          <Address2/>
          <City/>
          <State/>
          <Zip/>
        </InputEmployerInformation>
        <WorkPhone>
          <InputEmployerName>ACME</InputEmployerName>
          <InputNumber>555-555-5555</InputNumber>
          <InputExtension/>
          <Type>Standard</Type>
          <EmployerMatch>N</EmployerMatch>
          <Name/>
          <Address1/>
          <City/>
          <State/>
          <Zip/>
          <Type>Unidentified</Type>
        </WorkPhone>
        <OFAC>1</OFAC>
        <OFACMessage>No match</OFACMessage>
        <IPAddress>
          <InputAddress>127.48.42.98</InputAddress>
          <MatchCountry>1</MatchCountry>
          <MatchCountryMessage>Match for US</MatchCountryMessage>
          <Country>USA</Country>
          <MatchState>2</MatchState>
          <MatchStateMessage>No match for input</MatchStateMessage>
          <State>AK</State>
          <MatchCity>2</MatchCity>
          <MatchCityMessage>No match for input</MatchCityMessage>
          <City>CITY NAME</City>
          <MatchZip>2</MatchZip>
          <MatchZipMessage>No match for input</MatchZipMessage>
          <Zip>99989</Zip>
          <MSA>807</MSA>
          <Latitude>-42.813</Latitude>
          <Longitude>23.581</Longitude>
        </IPAddress>
        <BankInformation>
          <InputBankName>Real Money Bank</InputBankName>
          <InputABANumber>812381170</InputABANumber>
          <InputAccountNumber>1000002051</InputAccountNumber>
          <Valid>Y</Valid>
          <BankNameMatch>Y</BankNameMatch>
          <BankName>Real Money Bank</BankName>
        </BankInformation>
        <NumberRecords>
          <AddressVerResidentialMatch>0</AddressVerResidentialMatch>
          <AddressVerBusinessMatch>0</AddressVerBusinessMatch>
          <PhoneVerResidentialMatch>0</PhoneVerResidentialMatch>
          <PhoneVerBusinessMatch>0</PhoneVerBusinessMatch>
          <ResidentialAddressDetRec>0</ResidentialAddressDetRec>
          <BusinessAddressDetRec>0</BusinessAddressDetRec>
          <AddressHighRiskDetRec>0</AddressHighRiskDetRec>
          <AddressHighRiskDescRec>1</AddressHighRiskDescRec>
          <ResidentialPhoneDetRec>0</ResidentialPhoneDetRec>
          <BusinessPhoneDetRec>0</BusinessPhoneDetRec>
          <PhoneHighRiskDetRec>0</PhoneHighRiskDetRec>
          <PhoneHighRiskDescRet>0</PhoneHighRiskDescRet>
          <SSNDetailRecords>1</SSNDetailRecords>
          <COARecords>0</COARecords>
        </NumberRecords>
        <AuthenticationScore>333</AuthenticationScore>
        <CustomDecision>
          <Result>N</Result>
          <Bucket>D5</Bucket>
        </CustomDecision>
        <LooseDecision>
          <Result>R</Result>
          <Bucket>R3</Bucket>
        </LooseDecision>
        <ModerateDecision>
          <Result>Y</Result>
          <Bucket>A3</Bucket>
        </ModerateDecision>
        <ConservativeDecision>
          <Result>R</Result>
          <Bucket>R11</Bucket>
        </ConservativeDecision>
      </ConsumerIDVerificationSegment>
      <GlobalDecision>
        <Result>N</Result>
        <IDVBucket>D5</IDVBucket>
        <CRABucket/>
      </GlobalDecision>
    </Detail>
  </Response>
</DataxResponse>
XML;
				break;
			
			case 'IMPACTPDL-IDVE':
				// Reduced packet
				$xml = <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<DataxResponse>
  <CodeVersion>4.0.6</CodeVersion>
  <RequestVersion>3.0</RequestVersion>
  <Response>
    <Detail>
      <ConsumerIDVerificationSegment>
        <AuthenticationScore>609</AuthenticationScore>
        <CustomDecision>
          <Result>Y</Result>
          <Bucket>A1</Bucket>
        </CustomDecision>
        <LooseDecision>
          <Result>Y</Result>
          <Bucket>A1</Bucket>
        </LooseDecision>
        <ModerateDecision>
          <Result>Y</Result>
          <Bucket>A3</Bucket>
        </ModerateDecision>
        <ConservativeDecision>
          <Result>Y</Result>
          <Bucket>A5</Bucket>
        </ConservativeDecision>
      </ConsumerIDVerificationSegment>
      <CRASegment>
        <Decision>
          <Result>Y</Result>
          <Bucket>A1</Bucket>
        </Decision>
      </CRASegment>
      <GlobalDecision>
        <Result>Y</Result>
        <IDVBucket>A1</IDVBucket>
        <CRABucket>A1</CRABucket>
      </GlobalDecision>
    </Detail>
  </Response>
</DataxResponse>
XML;
				break;
			
			case 'PERF-L3':
				// Full test packet
				$xml = <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<DataxResponse>
  <GenerationTime>20070101120000</GenerationTime>
  <CodeVersion>4.0.6</CodeVersion>
  <RequestVersion>3.0</RequestVersion>
  <TransactionId>15180</TransactionId>
  <TrackHash>fakehash</TrackHash>
  <Response>
    <Summary>
      <Bankruptcy>0</Bankruptcy>
      <ChargeOffs>0</ChargeOffs>
      <Inquiries>6</Inquiries>
      <Decision>Y</Decision>
      <DecisionBuckets>
        <Bucket>R3</Bucket>
        <Bucket>P1</Bucket>
      </DecisionBuckets>
    </Summary>
    <Detail>
      <TLT>Lots of stuff is normally here
</TLT>
    </Detail>
  </Response>
</DataxResponse>
XML;
				break;
			
			case 'AALM-PERF';
				// Reduced packet
				$xml = <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<DataxResponse>
  <CodeVersion>4.0.6</CodeVersion>
  <RequestVersion>3.0</RequestVersion>
  <Response>
    <Detail>
      <ConsumerIDVerificationSegment>
        <AuthenticationScore>264</AuthenticationScore>
        <CustomDecision>
          <Result>Y</Result>
          <Bucket>A1</Bucket>
        </CustomDecision>
        <LooseDecision>
          <Result>Y</Result>
          <Bucket>A1</Bucket>
        </LooseDecision>
        <ModerateDecision>
          <Result>Y</Result>
          <Bucket>A3</Bucket>
        </ModerateDecision>
        <ConservativeDecision>
          <Result>Y</Result>
          <Bucket>A5</Bucket>
        </ConservativeDecision>
      </ConsumerIDVerificationSegment>
      <CRASegment>
        <Decision>
          <Result>Y</Result>
          <Bucket>A1</Bucket>
        </Decision>
      </CRASegment>
      <GlobalDecision>
        <Result>Y</Result>
        <IDVBucket>A1</IDVBucket>
        <CRABucket>A1</CRABucket>
      </GlobalDecision>
    </Detail>
  </Response>
</DataxResponse>
XML;
				break;
			
			case 'ERROR':
				$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<DataxResponse>
  <GenerationTime>20070101120000</GenerationTime>
  <CodeVersion>4.0.6</CodeVersion>
  <RequestVersion>3.0</RequestVersion>
  <TransactionId>4415484</TransactionId>
  <Response>
    <ErrorCode>S-091</ErrorCode>
    <ErrorMsg>Internal error; please contact your DataX technical representative</ErrorMsg>
  </Response>
</DataxResponse>
XML;
				break;
			
			case 'FBOD-PERF':
				// Reduced packet
				$xml = <<<XML
<?xml version="1.0" encoding="iso-8859-1"?>
<DataxResponse>
  <CodeVersion>4.0.6</CodeVersion>
  <RequestVersion>3.0</RequestVersion>
  <Response>
    <Detail>
      <ConsumerIDVerificationSegment>
        <OFAC>1</OFAC>
        <OFACMessage>No match</OFACMessage>
        <ModelScore>780</ModelScore>
        <CustomDecision>
          <Result>Y</Result>
          <Buckets>
            <Bucket>A5</Bucket>
          </Buckets>
        </CustomDecision>
        <LooseDecision>
          <Result>Y</Result>
          <Bucket>A2</Bucket>
        </LooseDecision>
        <ModerateDecision>
          <Result>Y</Result>
          <Bucket>A4</Bucket>
        </ModerateDecision>
        <ConservativeDecision>
          <Result>Y</Result>
          <Bucket>A5</Bucket>
        </ConservativeDecision>
      </ConsumerIDVerificationSegment>
      <BankAccountVerificationSegment>
        <ACH>
          <Data>
            <achcode>001</achcode>
            <achmsg>Unable to Validate</achmsg>
          </Data>
        </ACH>
        <Decision>
          <Result>Y</Result>
          <Buckets>
            <Bucket>A1</Bucket>
          </Buckets>
        </Decision>
      </BankAccountVerificationSegment>
      <TransUnionSegment>
        <ReturnPacket>Loads of crap here</ReturnPacket>
        <AreaCode/>
        <PhoneNumber>123465</PhoneNumber>
        <VantageScore>555</VantageScore>
        <Decision>
          <Result>Y</Result>
          <Buckets>
            <Bucket>A1</Bucket>
          </Buckets>
        </Decision>
      </TransUnionSegment>
      <GlobalDecision>
        <Result>Y</Result>
        <Buckets>
          <IDV>
            <Bucket>A5</Bucket>
          </IDV>
          <BAV>
            <Bucket>A1</Bucket>
          </BAV>
          <TU>
            <Bucket>A1</Bucket>
          </TU>
        </Buckets>
      </GlobalDecision>
    </Detail>
  </Response>
</DataxResponse>
XML;
				break;
			
			case 'SENT':
				$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<DATAXINQUERY>
  <AUTHENTICATION>
    <LICENSEKEY>0xdeadbeef</LICENSEKEY>
    <PASSWORD>hashhere</PASSWORD>
    <FORCE>tu-d2</FORCE>
  </AUTHENTICATION>
  <QUERY>
    <TRACKID>1008</TRACKID>
    <TYPE>fbod-perf</TYPE>
    <DATA>
      <NAMEFIRST>DUDLYTEST</NAMEFIRST>
      <NAMELAST>DUKETEST</NAMELAST>
      <STREET1>123 My Street</STREET1>
      <CITY>LAS VEGAS</CITY>
      <STATE>NV</STATE>
      <ZIP>89013</ZIP>
      <PHONEHOME>7027637610</PHONEHOME>
      <EMAIL>1191830059@TSSMASTERD.COM</EMAIL>
      <DRIVERLICENSESTATE>NV</DRIVERLICENSESTATE>
      <DOBYEAR>1971</DOBYEAR>
      <DOBMONTH>03</DOBMONTH>
      <DOBDAY>20</DOBDAY>
      <SSN>847554099</SSN>
      <NAMEMIDDLE>A</NAMEMIDDLE>
      <BANKNAME>TEST</BANKNAME>
      <BANKACCTNUMBER>1234567121</BANKACCTNUMBER>
      <BANKABA>123123123</BANKABA>
      <IPADDRESS>127.0.0.1</IPADDRESS>
      <SOURCE>127.0.0.1</SOURCE>
      <PROMOID>10000</PROMOID>
    </DATA>
  </QUERY>
</DATAXINQUERY>
XML;
				break;
			
			case 'INVALID':
				$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<FAKEXML>
	<UNCLOSED>Oops, forgot to close this tag.
</FAKEXML>
XML;
				break;
			
			case 'STRICT':
				$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<FAKEXML>
	<DUPE>This tag will be found more than once.</DUPE>
	<DUPE>This tag should never be found by searchOneNode.</DUPE>
	<SINGLE>This tag will only be found once.</SINGLE>
</FAKEXML>
XML;
				break;
			
			default:
				$xml = '';
				break;
		}
		
		return $xml;
	}
}
?>
