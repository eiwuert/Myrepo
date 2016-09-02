<?xml version="1.0"?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:fn="http://www.w3.org/2005/02/xpath-functions">
	
	<xsl:output method="html"/>
	
	<!-- TSS_LOAN_RESPONSE -->
	<xsl:template match ="/">
		
		<style>
			
			body { text-align: center; }
			.esig { width: 660px; margin: 0 auto; text-align: left; }
			
			.esig h2 { background-color: #000000; color:#FFFFFF; font-size: 20px; font-weight: bold;
				text-align: left; width: auto; padding: 10px 0 10px 15px; }
			.esig p { font-family: arial; font-size: 11pt; padding: 15px; padding-left: 30px; padding-right: 30px; margin: 0px; }
			.esig .input { font-family: arial; font-size: 12pt; margin-left: 100px; padding: 8px; }
			.esig a { color: #006699; }
			.esig small { display: block; font-family: arial; font-size: 8pt; margin-top: 5px; }
			.esig hr { width: 600px; color: #000000; }
			
			.errors { background-color: #dd0000; color: #ffffff; }
			.errors h1 { margin: 0px; padding: 4px; font-size: 11pt; background-color: #333333; text-align: center; }
			.errors ul { padding: 10px; padding-top: 0px; padding-left: 30px; font-size: 9pt; }
			
		</style>
		
		<form action="@@URL_ROOT@@" method="POST">
			<input type="hidden" name="page" value="legal"/>
			
			<div class="esig">
					<xsl:apply-templates select="tss_loan_response/content/section"/>
			</div>
		</form>
		<br/>
	</xsl:template>
	
	<!-- SECTION -->
	
	<!-- Section elements with questions: these are parsed
		differently from sections with only a verbiage element -->
	<xsl:template match="section[question]">
		<xsl:apply-templates select="question"/>
	</xsl:template>
	
	<!-- The first template section is a header,
		so we don't want it placed within a <p> tag. -->
	<xsl:template match="section[1]">
		<xsl:value-of disable-output-escaping="yes" select="verbiage"/>
		<!--<xsl:apply-templates select="/tss_loan_response/errors"/>-->
	</xsl:template>
	
	<!-- All other section elements -->
	<xsl:template match="section">
		<p><xsl:value-of disable-output-escaping="yes" select="verbiage"/></p>
	</xsl:template>
	
	<xsl:template match="//errors[data]">
		<div class="errors">
			<h1>To continue, please correct the following errors:</h1>
			<ul>
				<xsl:apply-templates select="data"/>
			</ul>
		</div>
	</xsl:template>
	
	<xsl:template match="//errors/data">
		<li><xsl:value-of disable-output-escaping="yes" select="."/></li>
	</xsl:template>
	
	<!-- QUESTION -->
	
	<!-- "Radio" questions, but not legal_agree, as
		that is going to be a submit button -->
	<xsl:template match="question[@recommend = 'radio' and child::option[1]/@name != 'legal_agree']">
		
		<xsl:variable name="name">
			<xsl:value-of select="child::option[1]/@name"/>
		</xsl:variable>
		<xsl:variable name="value">
			<xsl:value-of select="//collection/data[@name = $name]"/>
		</xsl:variable>
		
		<div class="input">
			
			<!-- this resets the value of the checkbox -->
			<input type="hidden">
				<xsl:attribute name="name">
					<xsl:value-of select="$name"/>
				</xsl:attribute>
			</input>
			
			<input type="checkbox" value="TRUE">
				
				<xsl:attribute name="name">
					<xsl:value-of select="child::option[1]/@name"/>
				</xsl:attribute>
				
				<xsl:if test="$value = 'TRUE'">
					<xsl:attribute name="checked"/>
				</xsl:if>
				
			</input>
			
			<xsl:value-of disable-output-escaping="yes" select="../verbiage"/>
			
		</div>
		
	</xsl:template>
	
	<!-- Special case for the submit button -->
	<xsl:template match="question[child::option[1]/@name = 'legal_agree']">
		<center>
			
			<input type="hidden" value="TRUE">
				<xsl:attribute name="name">
					<xsl:value-of select="child::option[1]/@name"/>
				</xsl:attribute>
			</input>
			
			<input type="submit" value="I AGREE - Send Me My Cash"/>
			
			<br/><br/><hr/>
			
			<input type="submit" value="I DO NOT AGREE - Don't Send Any Cash">
				<xsl:attribute name="name">
					<xsl:value-of select="child::option[1]/@name"/>
				</xsl:attribute>
			</input>
		</center>
	</xsl:template>
	
	<!-- Special case for the eSignature box -->
	<xsl:template match="question[child::option[1]/@name = 'esignature']">
		
		<xsl:variable name="esignature">
			<xsl:value-of select="//data[@name = 'name_first']"/>&amp;nbsp;
			<xsl:value-of select="//data[@name = 'name_last']"/>
		</xsl:variable>
		
		<xsl:variable name="name">
			<xsl:value-of select="child::option[1]/@name"/>
		</xsl:variable>
		<xsl:variable name="value">
			<xsl:value-of select="//collection/data[@name = $name]"/>
		</xsl:variable>
		
		<p align="center">
			<b>eSignature </b>
			<input type="text" name="esignature" size="40">
				<xsl:attribute name="value"><xsl:value-of select="$value"/></xsl:attribute>
			</input>
			<br/>
			
			<!-- This isn't guaranteed to work -->
			<!--<small>Type your full name (<xsl:value-of disable-output-escaping="yes" select="$esignature"/>) in the box above.</small>-->
		</p>
	</xsl:template>

</xsl:stylesheet>


