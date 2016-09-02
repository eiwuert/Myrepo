<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/">
		<result>
			<message>
				<xsl:value-of select="result/message" />
			</message>
			<success>
				<xsl:value-of select="result/success" />
			</success>
			<decision>
				<xsl:value-of select="result/decision" />
			</decision>
			<redirect_url>
				<xsl:value-of select="result/redirect_url" />
			</redirect_url>
			<thank_you_content>
				<xsl:value-of select="result/thank_you_content" />
			</thank_you_content>
		</result>
	</xsl:template>
</xsl:stylesheet>