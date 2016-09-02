<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html"/>

<!-- This template processes the root node ("/"> -->
<xsl:template match ="/">
	<!-- Tags with no xsl: prefix are copied to the output -->
	<h2> Errors </h2>
	<ul>
		<xsl:apply-templates select="tss_loan_response/errors" />
	</ul>
	<h2> Content </h2>
	<xsl:apply-templates select="tss_loan_response/content/section" />
	<h2> Signature </h2>
	<ul>
		<xsl:apply-templates select="tss_loan_response/signature" />
	</ul>
	<h2> Collection </h2>
	<ul>
		<xsl:apply-templates select="tss_loan_response/collection" />
	</ul>
</xsl:template>



<!--
 !	template for section element
 !-->
<xsl:template match="section">
	<br />
	<xsl:value-of disable-output-escaping="yes" select="verbiage" /> 
	<xsl:apply-templates select="question" /> 
	<br />
</xsl:template>



<!-- question elements -->
<xsl:template match="question">

	<xsl:choose>
		<!-- IF (use a radio button) -->
		<xsl:when test="@recommend = 'radio'">
				<xsl:for-each select="option">
					<br />
					<input type="radio">
						<xsl:attribute name="name">
							<xsl:value-of select="@name" />
						</xsl:attribute>
						<xsl:attribute name="value">
							<xsl:value-of select="." />
						</xsl:attribute>
					</input>
					<xsl:value-of select="."/>
					<br />
				</xsl:for-each>
		</xsl:when>
			<!-- ELSE IF (use a combo box) -->
		<xsl:when test="@recommend = 'combo'">
				<br />
				<select>
				<xsl:attribute name="name">
					<xsl:value-of select="option/@name" />
				</xsl:attribute>
				<xsl:for-each select="option">
					<option>
					<xsl:attribute name="value">
						<xsl:value-of select="." />
					</xsl:attribute>
					<xsl:value-of select="."/>
					</option>
					<br />
				</xsl:for-each>
				</select>
		</xsl:when>
		<!--  ELSE IF (text field) -->
		<xsl:when test="@recommend = 'text'">
			<xsl:for-each select="option">
				<br />
				<input type="text">
					<xsl:attribute name="name">
						<xsl:value-of select="@name" />
					</xsl:attribute>
				</input>
			</xsl:for-each>
		</xsl:when>
	</xsl:choose>
	<br />
</xsl:template>


<!-- signature elements -->
<xsl:template match="signature">
	<xsl:apply-templates select="data" /> 
</xsl:template>


<!-- errors elements -->
<xsl:template match="errors">
	<xsl:apply-templates select="data" /> 
</xsl:template>


<!-- collection elements -->
<xsl:template match="collection">
	<xsl:apply-templates select="data" /> 
</xsl:template>


<!-- data elements -->
<xsl:template match="data">
	<li> <!-- Start a new list item -->
		<xsl:value-of select="@name"/>
		<xsl:text>: </xsl:text>
		<!-- Output the text inside the <data> element -->
		<xsl:value-of select="."/>
	</li> <!-- End of the list item -->
</xsl:template>

</xsl:stylesheet>


