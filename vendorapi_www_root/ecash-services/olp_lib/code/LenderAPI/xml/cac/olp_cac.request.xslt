<?xml version='1.0' ?>
<xsl:stylesheet version="1.0" 
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
	xmlns:fn="http://www.w3.org/2005/02/xpath-functions" 
	xmlns:xi="http://www.w3.org/2001/XInclude"
	xmlns:exslt="http://exslt.org/common" 
	xmlns:func="http://exslt.org/functions" 
	xmlns:str="http://exslt.org/strings" 
	xmlns:date="http://exslt.org/dates-and-times"
	xmlns:olp="http://olp.edataserver.com" 
	exclude-result-prefixes="olp func exslt fn" 
	extension-element-prefixes="exslt func str date"
	>
	<xsl:output method="xml" indent="yes" omit-xml-declaration="yes" />

	<xi:include href="http://ds02.tss/func.xml" parse="xml"/>

	<!-- Main document template -->
	<xsl:template match="/">
		<create_lead xmlns="leads.cashadvance.com/soap_cashadvance.php">
			<user_name>SellingSource</user_name>
			<password>password</password>
			<xsl:apply-templates/>
		</create_lead>
	</xsl:template>
	<xsl:template match="data">
		<lead_info xmlns="leads.cashadvance.com/soap_cashadvance.php">
			<Email><xsl:value-of select="application/email_primary"/></Email>
			<First_Name><xsl:value-of select="application/name_first"/></First_Name>
			<Last_Name><xsl:value-of select="application/name_last"/></Last_Name>
			<Address_1><xsl:value-of select="application/home_street"/></Address_1>
			<Address_2></Address_2>
			<Apartment_Number><xsl:value-of select="application/home_unit"/></Apartment_Number>
			<City><xsl:value-of select="application/home_city"/></City>
			<State><xsl:value-of select="application/home_state"/></State>
			<ZIP_Code><xsl:value-of select="application/home_zip"/></ZIP_Code>
			<Home_Phone><xsl:value-of select="application/phone_home"/></Home_Phone>
			<Work_Phone><xsl:value-of select="application/phone_work"/></Work_Phone>
			<Cell_Phone><xsl:value-of select="application/phone_cell"/></Cell_Phone>
			<Military><xsl:value-of select="olp:bool-to-yesno(application/military)"/></Military>
			<Occupation>nananana</Occupation>
			<Employer><xsl:value-of select="application/employer_name"/></Employer>
			<Monthly_Income><xsl:value-of select="application/income_monthly_net"/></Monthly_Income>
			<Income_Source><xsl:value-of select="olp:lc(application/income_type)"/><!-- need ucwords(.) --></Income_Source>
			<Gender>na</Gender>
			<SSN><xsl:value-of select="application/social_security_number"/></SSN>
			<DOB><xsl:value-of select="concat(application/date_dob_m,'/',application/date_dob_d,'/',application/date_dob_y)"/></DOB>
			<Payment_Method><xsl:value-of select="olp:cac-payment-method(application/income_direct_deposit)" /></Payment_Method>
			<Payment_Frequency><xsl:value-of select="olp:cac-payment-frequency(application/income_frequency)"/></Payment_Frequency>
			<Account_Number><xsl:value-of select="application/bank_account"/></Account_Number>
			<Routing_Number><xsl:value-of select="application/bank_aba"/></Routing_Number>
			<Account_Type><xsl:value-of select="application/bank_account_type"/></Account_Type>
			<Drivers_License_Number><xsl:value-of select="application/state_id_number"/></Drivers_License_Number>
			<Drivers_License_State>
				<xsl:choose>
					<xsl:when test="application/state_issued_id != ''"><xsl:value-of select="application/state_issued_id"/></xsl:when>
					<xsl:otherwise><xsl:value-of select="application/home_state"/></xsl:otherwise>
				</xsl:choose>
			</Drivers_License_State>
			<Bank_Name><xsl:value-of select="application/bank_name"/></Bank_Name>
			<Bank_Phone>8886667777</Bank_Phone>
			<Outstanding_Loans>0</Outstanding_Loans>
			<Pay_Date_1><xsl:value-of select="olp:ymd-to-mdy(application/paydate1)"/></Pay_Date_1>
			<Pay_Date_2><xsl:value-of select="olp:ymd-to-mdy(application/paydate2)"/></Pay_Date_2>
			<Reference_1_Name><xsl:value-of select="application/ref_01_name_full"/></Reference_1_Name>
			<Reference_1_Phone><xsl:value-of select="application/ref_01_phone_home"/></Reference_1_Phone>
			<Reference_1_Relationship><xsl:value-of select="application/ref_01_relationship"/></Reference_1_Relationship>
			<Reference_2_Name><xsl:value-of select="application/ref_02_name_full"/></Reference_2_Name>
			<Reference_2_Phone><xsl:value-of select="application/ref_02_phone_home"/></Reference_2_Phone>
			<Reference_2_Relationship><xsl:value-of select="application/ref_02_relationship"/></Reference_2_Relationship>
			<IP_Address><xsl:value-of select="application/client_ip_address"/></IP_Address>
			<Best_Time_To_Call><xsl:value-of select="olp:lc(application/best_call_time)"/></Best_Time_To_Call>
			<Own_Rent><xsl:value-of select="olp:lc(application/residence_type)"/></Own_Rent>
			<US_Citizen><xsl:value-of select="application/citizen" /></US_Citizen>
			<xsl:variable name="sr" select="olp:time-since(application/residence_start_date)" />
			<Years_At_Residence><xsl:value-of select="$sr/years" /></Years_At_Residence>
			<Months_At_Residence><xsl:value-of select="$sr/months" /></Months_At_Residence>
			<xsl:variable name="se" select="olp:time-since(application/date_of_hire)" />
			<Years_Employed><xsl:value-of select="$se/years" /></Years_Employed>
			<Months_Employed><xsl:value-of select="$se/months" /></Months_Employed>
			<Supervisor_Name></Supervisor_Name>
			<Supervisor_Phone>8886667777</Supervisor_Phone>
			<Work_City></Work_City>
			<Work_State><xsl:value-of select="application/home_state"/></Work_State>
			<Work_Zip><xsl:value-of select="application/home_zip"/></Work_Zip>
			<Years_Bank_Account>0</Years_Bank_Account>
			<SRC><xsl:value-of select="constant/src"/></SRC>
		</lead_info>
	</xsl:template>
</xsl:stylesheet>
