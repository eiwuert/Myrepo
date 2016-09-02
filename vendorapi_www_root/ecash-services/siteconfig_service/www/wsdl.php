<?php
	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<definitions
	xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/" xmlns:tns="http://sellingsource.com/soap/siteconfig"
	xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://schemas.xmlsoap.org/wsdl/"
	targetNamespace="http://sellingsource.com/soap/siteconfig" name="ConfigService">
	<types></types>
	<message name="getConfig">
		<part name="license_key" type="xsd:string"></part>
		<part name="promo_id" type="xsd:string"></part>
		<part name="promo_sub_code" type="xsd:string"></part>
	</message>
	<message name="getConfigResponse">
		<part name="return" type="xsd:string"></part>
	</message>
	<portType name="Config">
		<operation name="getConfig" parameterOrder="license_key promo_id promo_sub_code">
			<input message="tns:getConfig"></input>
			<output message="tns:getConfigResponse"></output>
		</operation>
	</portType>
	<binding name="ConfigPortBinding" type="tns:Config">

		<soap:binding transport="http://schemas.xmlsoap.org/soap/http"
			style="rpc"></soap:binding>
		<operation name="getConfig">
			<soap:operation soapAction=""></soap:operation>
			<input>
				<soap:body use="literal" namespace="http://sellingsource.com/soap/siteconfig"></soap:body>
			</input>
			<output>
				<soap:body use="literal" namespace="http://sellingsource.com/soap/siteconfig"></soap:body>
			</output>
		</operation>
	</binding>
	<service name="ConfigService">
		<port name="ConfigPort" binding="tns:ConfigPortBinding">
			<soap:address location="<?php echo $soap_url ?>"></soap:address>
		</port>
	</service>
</definitions>