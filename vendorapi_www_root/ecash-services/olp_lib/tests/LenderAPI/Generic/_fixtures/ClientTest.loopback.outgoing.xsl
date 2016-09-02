<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:fn="http://www.w3.org/2005/02/xpath-functions" 
	xmlns:exslt="http://exslt.org/common" 
	xmlns:func="http://exslt.org/functions" 
	xmlns:str="http://exslt.org/strings" 
	xmlns:date="http://exslt.org/dates-and-times"
	xmlns:olp="http://olp.edataserver.com" 
	exclude-result-prefixes="olp func exslt fn" 
	extension-element-prefixes="exslt func str date">
	<xsl:output method="xml" indent="yes" />
	<xsl:template match="/">
		<result>
				<xsl:choose>
					<xsl:when test="data/application/name_first = 'Joe'">
						<message>Application Accepted!</message>
						<success>1</success>
						<decision>consent</decision>
						<redirect_url>http://sellingsource.com</redirect_url>
						<thank_you_content>Thanks, buddy!</thank_you_content>
					</xsl:when>
					<xsl:otherwise>
						<message>Application Rejected!</message>
						<success>0</success>
						<decision>denial</decision>
						<redirect_url>http://sellingsource.com?page=sorry</redirect_url>
						<thank_you_content>Sorry, yo!</thank_you_content>
					</xsl:otherwise>
				</xsl:choose>
				<xsl:value-of select="data/name_first" />
		</result>
	</xsl:template>
</xsl:stylesheet>