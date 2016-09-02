<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:output method="xml" indent="yes" omit-xml-declaration="yes" />
	
	<xsl:template match="/">
		<result>
			<success>1</success>
			<decision>ACCEPTED</decision>
			<message>Accepted</message>
			<thank_you_content>Thanks <xsl:value-of select="data/application/name_first"/>!</thank_you_content>
		</result>
	</xsl:template>
</xsl:stylesheet>