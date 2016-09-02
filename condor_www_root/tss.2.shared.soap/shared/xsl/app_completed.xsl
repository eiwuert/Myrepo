<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="html"/>
	
	<!-- This template processes the root node ("/"> -->
	<xsl:template match ="/">
		
		<!-- Tags with no xsl: prefix are copied to the output -->
		<table width="760" cellpadding="0" cellspacing="30" border="0" align="center">
			<tr>
				<td valign="top" align="center">
					
					<div class="form-section-alternate">
						<h1 style="padding: 0px; margin: 5px;">Thank You!</h1>
						<xsl:apply-templates select="tss_loan_response/content/section" />
					</div>
					
				</td>
			</tr>
		</table>
		
	</xsl:template>
	
	<!--
	 !	template for section element
	 !-->
	<xsl:template match="section">
		<p><xsl:value-of disable-output-escaping="yes" select="verbiage" /></p>
	</xsl:template>

</xsl:stylesheet>


