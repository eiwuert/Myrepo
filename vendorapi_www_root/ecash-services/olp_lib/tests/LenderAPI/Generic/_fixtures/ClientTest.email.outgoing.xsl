<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:fn="http://www.w3.org/2005/02/xpath-functions"
	xmlns:exslt="http://exslt.org/common"
	xmlns:func="http://exslt.org/functions"
	xmlns:date="http://exslt.org/dates-and-times"
	exclude-result-prefixes="exslt func fn"
	extension-element-prefixes="exslt func date">

	<xsl:import href="@@path@@/exslt/date.format-date.function.xsl" />
	<xsl:import href="@@path@@/xml/olp.date.func.xml" />
	
	<xsl:output method="xml" indent="yes" omit-xml-declaration="yes"/>
	
	<xsl:template match="/">
		<email>
			<template>ECA_B&amp;M_Store_ver6</template>
			<recipients>
				<xsl:choose>
					<xsl:when test="data/config/mode = 'LIVE'">
						<to><xsl:value-of select="data/brick_and_mortar_store/dm_email" /></to>
						<to><xsl:value-of select="data/brick_and_mortar_store/store_email" /></to>
					</xsl:when>
					<xsl:otherwise>
						<to>eric.johney@sellingsource.com</to>
					</xsl:otherwise>
				</xsl:choose>
			</recipients>
			<tokens>
				<name_first><xsl:value-of select="data/application/name_first"/></name_first>
				<name_last><xsl:value-of select="data/application/name_last"/></name_last>
				<address_street><xsl:value-of select="data/application/home_street"/></address_street>
				<address_unit><xsl:value-of select="data/application/home_unit"/></address_unit>
				<address_city><xsl:value-of select="data/application/home_city"/></address_city>
				<address_state><xsl:value-of select="data/application/home_state"/></address_state>
				<address_zip><xsl:value-of select="data/application/home_zip"/></address_zip>
				<phone_home><xsl:value-of select="data/application/phone_home"/></phone_home>
				<phone_work><xsl:value-of select="data/application/phone_work"/></phone_work>
				<phone_cell><xsl:value-of select="data/application/phone_cell"/></phone_cell>
				<branch_id><xsl:value-of select="data/brick_and_mortar_store/store_id"/></branch_id>
				<customer_email><xsl:value-of select="data/application/email_primary"/></customer_email>
				<application_id><xsl:value-of select="data/application/application_id"/></application_id>
			</tokens>
		</email>
	</xsl:template>
</xsl:stylesheet>