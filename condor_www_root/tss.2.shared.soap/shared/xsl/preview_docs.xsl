<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html"/>
	
	<!-- This template processes the root node ("/"> -->
	<xsl:template match ="/">
		<xsl:apply-templates select="tss_loan_response/content/section" />
	</xsl:template>
	
	<!--
	 !	template for section element
	 !-->
	<xsl:template match="section">
		<xsl:value-of disable-output-escaping="yes" select="verbiage" /> 
	</xsl:template>
	
</xsl:stylesheet>


