<?xml version='1.0' ?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:variable name="lowercase">abcdefghijklmnopqrstuvwxyz</xsl:variable>
	<xsl:variable name="uppercase">ABCDEFGHIJKLMNOPQRSTUVWXYZ</xsl:variable>
	<xsl:template match="/">
		<app>
			<name>
				<xsl:value-of select="translate(data/application/name_first,$lowercase,$uppercase)"/>
			</name>
			<place_to_live>
				<xsl:value-of select="data/application/home_street"/>
			</place_to_live>
			<total_income>
				<xsl:value-of select="data/application/income_monthly_net" />
			</total_income>
		</app>
	</xsl:template>
</xsl:stylesheet>