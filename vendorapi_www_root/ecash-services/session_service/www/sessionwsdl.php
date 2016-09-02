<?php
header('Content-Type: text/xml; charset=utf-8');
print '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$service_location = !empty($_GET['service_location']) ? $_GET['service_location'] : 'http://' . $_SERVER['SERVER_NAME'];
?>
<wsdl:definitions xmlns="http://schemas.xmlsoap.org/wsdl/"
	xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
	xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
	xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema"
	xmlns:tns="http://schemas.sellingsource.com/soap/session"
	targetNamespace="http://schemas.sellingsource.com/soap/session">
  <wsdl:types>
    <xsd:schema targetNamespace="http://schemas.sellingsource.com/soap/session">
      <xsd:complexType name="SessionReadResponse">
        <xsd:all>
          <xsd:element name="session" type="xsd:string"/>
          <xsd:element name="session_id" type="xsd:string"/>
          <xsd:element name="session_lock_key" type="xsd:string"/>
        </xsd:all>
      </xsd:complexType>
    </xsd:schema>
  </wsdl:types>
  <message name="acquireAndReadAsJsonRequest">
    <part name="session_id" type="xsd:string"/>
    <part name="block_seconds" type="xsd:int"/>
    <part name="timeout" type="xsd:int"/>
  </message>
  <message name="acquireAndReadAsJsonResponse">
    <part name="acquireAndReadAsJsonReturn" type="tns:SessionReadResponse"/>
  </message>
  <message name="createSessionAndReadAsJsonRequest">
    <part name="session_id" type="xsd:string"/>
    <part name="lock_time" type="xsd:int"/>
  </message>
  <message name="createSessionAndReadAsJsonResponse">
    <part name="createSessionAndReadAsJsonReturn" type="tns:SessionReadResponse"/>
  </message>
  <message name="newSessionAndReadAsJsonRequest">
    <part name="lock_time" type="xsd:int"/>
  </message>
  <message name="newSessionAndReadAsJsonResponse">
    <part name="newSessionAndReadAsJsonReturn" type="tns:SessionReadResponse"/>
  </message>
  <message name="jsonSaveAndReleaseRequest">
    <part name="session_id" type="xsd:string"/>
    <part name="session_lock_key" type="xsd:string"/>
    <part name="json_data" type="xsd:string"/>
  </message>
  <message name="jsonSaveAndReleaseResponse">
    <part name="jsonSaveAndReleaseReturn" type="xsd:string"/>
  </message>
  <wsdl:portType name="SessionServicePortType">
    <wsdl:operation name="acquireAndReadAsJson">
      <wsdl:input message="tns:acquireAndReadAsJsonRequest"/>
      <wsdl:output message="tns:acquireAndReadAsJsonResponse"/>
    </wsdl:operation>
    <wsdl:operation name="createSessionAndReadAsJson">
      <wsdl:input message="tns:createSessionAndReadAsJsonRequest"/>
      <wsdl:output message="tns:createSessionAndReadAsJsonResponse"/>
    </wsdl:operation>
    <wsdl:operation name="jsonSaveAndRelease">
      <wsdl:input message="tns:jsonSaveAndReleaseRequest"/>
      <wsdl:output message="tns:jsonSaveAndReleaseResponse"/>
    </wsdl:operation>
    <wsdl:operation name="newSessionAndReadAsJson">
      <wsdl:input message="tns:newSessionAndReadAsJsonRequest"/>
      <wsdl:output message="tns:newSessionAndReadAsJsonResponse"/>
    </wsdl:operation>
  </wsdl:portType>
  <binding name="SessionServiceBinding" type="tns:SessionServicePortType">
    <soap:binding style="rpc" transport="http://schemas.xmlsoap.org/soap/http"/>
    <wsdl:operation name="acquireAndReadAsJson">
      <soap:operation soapAction="" style="rpc"/>
      <wsdl:input>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="createSessionAndReadAsJson">
      <soap:operation soapAction="" style="rpc"/>
      <wsdl:input>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="newSessionAndReadAsJson">
      <soap:operation soapAction="" style="rpc"/>
      <wsdl:input>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:output>
    </wsdl:operation>
    <wsdl:operation name="jsonSaveAndRelease">
      <soap:operation soapAction="" style="rpc"/>
      <wsdl:input>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:input>
      <wsdl:output>
        <soap:body use="encoded"
			namespace="http://schemas.sellingsource.com/soap/session"
			encodingStyle="http://schemas.xmlsoap.org/soap/encoding/"/>
      </wsdl:output>
    </wsdl:operation>
  </binding>
  <wsdl:service name="SessionService">
    <wsdl:port name="SessionServicePort" binding="tns:SessionServiceBinding">
		<?php echo '<soap:address location="' . $service_location . '/index.php"/>' . PHP_EOL; ?>
    </wsdl:port>
  </wsdl:service>
</wsdl:definitions>
