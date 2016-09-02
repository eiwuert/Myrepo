<?xml version='1.0' ?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:fn="http://www.w3.org/2005/02/xpath-functions" 
	xmlns:exslt="http://exslt.org/common" 
	xmlns:func="http://exslt.org/functions" 
	xmlns:str="http://exslt.org/strings" 
	xmlns:date="http://exslt.org/dates-and-times"
	xmlns:olp="http://olp.edataserver.com" 
	xmlns:ns1="leads.cashadvance.com/soap_cashadvance.php"
	exclude-result-prefixes="olp func exslt fn ns1" 
	extension-element-prefixes="exslt func str date"
	>
	<xsl:output method="xml" indent="yes" omit-xml-declaration="yes" />

	<func:function name="olp:lc">
		<xsl:param name="a" />
		<func:result><xsl:value-of select="translate($a,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')" /></func:result>
	</func:function>

	<func:function name="olp:ymd-to-mdy">
		<xsl:param name="a" />
		<xsl:variable name="b" select="str:split($a, '-')" />
		<func:result select="concat($b[2], '/', $b[3], '/', $b[1])" />
	</func:function>

	<!-- Main document template -->
	<xsl:template match="//ns1:create_leadResponse">
		<result>
			<xsl:choose>
				<xsl:when test="status_id = 1">
					<success>1</success>
					<decision>ACCEPTED</decision>
					<message>Accepted</message>
					<redirect_url><xsl:value-of select="delivery_url" /></redirect_url>
					<thank_you_content><xsl:value-of select="delivery_message" /></thank_you_content>
				</xsl:when>
				<xsl:otherwise>
					<success>0</success>
					<decision>REJECTED</decision>
					<message>Rejected</message>
				</xsl:otherwise>
			</xsl:choose>	
		</result>
	</xsl:template>

</xsl:stylesheet>
<!-- Stylus Studio meta-information - (c) 2004-2008. Progress Software Corporation. All rights reserved.

<metaInformation>
	<scenarios>
		<scenario default="yes" name="Scenario1" userelativepaths="yes" externalpreview="no" url="cac.response.accepted.xml" htmlbaseurl="" outputurl="" processortype="saxon8" useresolver="yes" profilemode="0" profiledepth="" profilelength=""
		          urlprofilexml="" commandline="" additionalpath="" additionalclasspath="" postprocessortype="none" postprocesscommandline="" postprocessadditionalpath="" postprocessgeneratedext="" validateoutput="no" validator="internal"
		          customvalidator="">
			<advancedProp name="sInitialMode" value=""/>
			<advancedProp name="bSchemaAware" value="true"/>
			<advancedProp name="bXsltOneIsOkay" value="true"/>
			<advancedProp name="bXml11" value="false"/>
			<advancedProp name="iValidation" value="0"/>
			<advancedProp name="bExtensions" value="true"/>
			<advancedProp name="iWhitespace" value="0"/>
			<advancedProp name="sInitialTemplate" value=""/>
			<advancedProp name="bTinyTree" value="true"/>
			<advancedProp name="bUseDTD" value="false"/>
			<advancedProp name="bWarnings" value="true"/>
			<advancedProp name="iErrorHandling" value="fatal"/>
		</scenario>
	</scenarios>
	<MapperMetaTag>
		<MapperInfo srcSchemaPathIsRelative="yes" srcSchemaInterpretAsXML="no" destSchemaPath="..\olp.response.xml" destSchemaRoot="result" destSchemaPathIsRelative="yes" destSchemaInterpretAsXML="no">
			<SourceSchema srcSchemaPath="cac.response.accepted.xml" srcSchemaRoot="SOAP-ENV:Envelope" AssociatedInstance="" loaderFunction="document" loaderFunctionUsesURI="no"/>
		</MapperInfo>
		<MapperBlockPosition>
			<template match="//ns1:create_leadResponse">
				<block path="result/xsl:choose" x="203" y="96"/>
				<block path="result/xsl:choose/=[0]" x="114" y="129"/>
			</template>
		</MapperBlockPosition>
		<TemplateContext>
			<template match="//ns1:create_leadResponse" mode="" srcContextPath="/SOAP-ENV:Envelope/SOAP-ENV:Body/ns1:create_leadResponse" srcContextFile="file:///c:/project.d/olp_lib/LenderAPI/xml/cac/cac.response.accepted.xml" targetContextPath=""
			          targetContextFile=""/>
		</TemplateContext>
		<MapperFilter side="source"></MapperFilter>
	</MapperMetaTag>
</metaInformation>
-->