
CREATE TABLE tokens (
  date_modified DATE NULL ,
  date_created DATE NULL ,
  token_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  company_id INTEGER NULL,
  loan_type_id INTEGER NULL,
  token_name   varchar(50) not null,
  value_array blob NOT NULL,
  UNIQUE (company_id, loan_type_id, token_name)	ON CONFLICT FAIL);

CREATE TABLE application (
  date_modified DATE NULL,
  date_created DATE NULL,
  company_id INTEGER NULL,
  application_id INTEGER NOT NULL PRIMARY KEY,
  customer_id INTEGER NULL,
  archive_db2_id INTEGER NULL,
  archive_mysql_id INTEGER NULL,
  archive_cashline_id INTEGER NULL,
  login_id INTEGER NULL,
  is_react varchar(5) NULL,
  loan_type_id INTEGER NULL,
  rule_set_id INTEGER NULL,
  enterprise_site_id INTEGER NULL,
  application_status_id INTEGER NULL,
  date_application_status_set INTEGER NULL,
  date_next_contact DATE NULL ,
  ip_address varchar(40)  NULL ,
  application_type varchar(40)  NULL,
  bank_name varchar(100)  NULL ,
  bank_aba varchar(9)  NULL ,
  bank_account varchar(17)  NULL ,
  bank_account_type varchar(40)  NULL,
  date_fund_estimated DATE NULL,
  date_fund_actual date NULL,
  date_first_payment date  NULL,
  fund_requested float NULL,
  fund_qualified float NULL,
  fund_actual float NULL,
  finance_charge float NULL,
  payment_total float NULL,
  apr float NULL,
  income_monthly float NULL,
  income_source varchar(40)  NULL,
  income_direct_deposit varchar(40)  NULL,
  income_frequency varchar(40)  NULL,
  income_date_soap_1 date NULL,
  income_date_soap_2 date NULL,
  paydate_model varchar(40)  NULL,
  day_of_week varchar(40)  NULL,
  last_paydate date NULL,
  day_of_month_1 INTEGER NULL,
  day_of_month_2 INTEGER NULL,
  week_1 INTEGER NULL,
  week_2 INTEGER NULL,
  track_id varchar(40)  NULL,
  agent_id INTEGER NULL,
  agent_id_callcenter INTEGER NULL,
  dob date NULL,
  ssn varchar(9)  NULL ,
  legal_id_number varchar(30) ,
  legal_id_state varchar(2) ,
  legal_id_type varchar(30) ,
  identity_verified varchar(30) ,
  email varchar(100) NULL,
  email_verified varchar(30) NULL,
  name_last varchar(30) NULL,
  name_first varchar(30) NULL,
  name_middle varchar(30) NULL,
  name_suffix varchar(30) NULL,
  street varchar(100)  NULL ,
  unit varchar(10) NULL,
  city varchar(30)  NULL ,
  state varchar(30)  NULL,
  county varchar(30) NULL,
  zip_code varchar(30) NULL,
  tenancy_type varchar(30) NULL,
  phone_home varchar(30) NULL,
  phone_cell varchar(30) NULL,
  phone_fax varchar(30) NULL,
  call_time_pref varchar(30) NULL,
  contact_method_pref varchar(30) NULL,
  marketing_contact_pref varchar(30) NULL,
  employer_name varchar(100) NULL,
  job_title varchar(100) NULL,
  supervisor varchar(50) NULL,
  shift varchar(100) NULL,
  date_hire date  NULL,
  job_tenure float  NULL,
  phone_work varchar(10)  NULL,
  phone_work_ext varchar(8)  NULL,
  work_address_1 varchar(50)  NULL,
  work_address_2 varchar(50)  NULL,
  work_city varchar(30)  NULL,
  work_state varchar(2)  NULL,
  work_zip_code varchar(9)  NULL,
  employment_verified varchar(30) NULL,
  pwadvid varchar(30) NULL,
  olp_process varchar(255) NULL,
  is_watched varchar(30) NULL,
  schedule_model_id INTEGER NULL,
  modifying_agent_id INTEGER NULL,
  banking_start_date date  NULL,
  residence_start_date date  NULL,
  cfe_rule_set_id INTEGER NULL
  
);

CREATE TABLE rule_component (
		  date_modified DATE NULL,
		  date_created DATE NULL,
		  active_status varchar(10) NULL,
		  rule_component_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		  name varchar(100)  NULL ,
		  name_short varchar(30)  NULL,
		  grandfathering_enabled varchar(5)
		
		) ;	
		
CREATE TABLE rule_component_parm (
		  date_modified DATE NULL,
		  date_created DATE NULL,
		  active_status varchar(10),
		  rule_component_parm_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
		  rule_component_id INTEGER  NULL,
		  parm_name varchar(30)  NULL ,
		  parm_subscript varchar(30) ,
		  sequence_no INTEGER ,
		  display_name varchar(50)  NULL ,
		  description varchar(255)  NULL ,
		  parm_type varchar(10),
		  user_configurable varchar(10),
		  input_type varchar(10),
		  presentation_type varchar(10),
		  value_label varchar(50) ,
		  subscript_label varchar(50) ,
		  value_min INTEGER ,
		  value_max INTEGER ,
		  value_increment INTEGER,
		  length_min INTEGER,
		  length_max INTEGER,
		  enum_values varchar(255) ,
		  preg_pattern varchar(255) ,
		  UNIQUE (rule_component_id,parm_name,parm_subscript)
		); 
CREATE TABLE rule_set (
	  date_modified DATE NULL,
	  date_created DATE NULL,
	  active_status varchar(10),
	  rule_set_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	  name varchar(100),
	  loan_type_id INTEGER,
	  date_effective DATE NULL,
	  UNIQUE (loan_type_id,date_effective)
	);

CREATE TABLE rule_set_component (
	  date_modified DATE NULL,
	  date_created DATE NULL,
	  active_status varchar(10),
	  rule_set_id INTEGER,
	  rule_component_id INTEGER,
	  sequence_no INTEGER
	
	);

CREATE TABLE rule_set_component_parm_value (
	  date_modified DATE NULL,
	  date_created DATE NULL,
	  agent_id INTEGER,
	  rule_set_id INTEGER,
	  rule_component_id INTEGER,
	  rule_component_parm_id INTEGER,
	  parm_value text  NULL,
	  PRIMARY KEY  (rule_set_id,rule_component_id,rule_component_parm_id)
	);
	
CREATE TABLE company (
	  date_modified DATE NULL,
	  date_created DATE NULL,
	  active_status varchar(10),
	  company_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	  name varchar(100) ,
	  name_short varchar(10) ,
	  co_entity_type varchar(100),
	  ecash_process_type varchar(1),
	  property_id INTEGER
	 
	);

CREATE TABLE loan_type (
	  date_modified DATE NULL,
	  date_created DATE NULL,
	  active_status varchar(10),
	  company_id INTEGER,
	  loan_type_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
	  name varchar(100) ,
	  name_short varchar(25) ,
	  abbreviation varchar(5) ,
	  UNIQUE  (company_id,name_short)
	);

CREATE TABLE document (
  date_modified DATE NULL,
  date_created DATE NULL,
  company_id INTEGER ,
  application_id INTEGER ,
  document_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  document_list_id INTEGER,
  document_method_legacy varchar(30),
  document_event_type varchar(20),
  name_other varchar(255) ,
  document_id_ext varchar(255) ,
  agent_id INTEGER ,
  signature_status varchar(30),
  sent_to varchar(255) ,
  document_method varchar(30),
  transport_method varchar(15),
  archive_id INTEGER 
);

CREATE TABLE document_list (
  date_modified DATE NULL,
  date_created DATE NULL,
  active_status varchar(10),
  company_id INTEGER,
  document_list_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  name varchar(255) ,
  name_short varchar(50) ,
  required varchar(3),
  esig_capable varchar(3),
  system_id INTEGER ,
  send_method varchar(10),
  document_api varchar(10),
  doc_send_order INTEGER ,
  doc_receive_order INTEGER,
  only_receivable varchar(3),
  UNIQUE (company_id,system_id,name_short)
); 

CREATE TABLE document_list_body (
  date_modified date null,
  date_created date null,
  company_id INTEGER,
  document_list_id INTEGER,
  document_list_body_id INTEGER,
  send_method varchar(10),
  PRIMARY KEY  (document_list_id,document_list_body_id)
); 

CREATE TABLE document_list_package (
  date_modified DATE NULL,
  date_created DATE NULL,
  company_id INTEGER,
  document_package_id INTEGER,
  document_list_id INTEGER,
  PRIMARY KEY  (document_list_id,document_package_id)
);

CREATE TABLE document_package (
  date_modified DATE NULL,
  date_created DATE NULL,
  active_status varchar(10),
  company_id INTEGER,
  document_package_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  name varchar(255) ,
  name_short varchar(50) ,
  document_list_id INTEGER,
  UNIQUE (name_short,company_id)
);

CREATE TABLE document_process (
  date_modified DATE NULL,
  date_created DATE NULL,
  document_list_id INTEGER,
  application_status_id INTEGER,
  current_application_status_id INTEGER,
  PRIMARY KEY  (document_list_id,application_status_id,current_application_status_id)
);
CREATE TABLE document_queue (
  date_modified DATE NULL,
  date_created DATE NULL,
  document_queue_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  company_id INTEGER,
  application_id INTEGER,
  transaction_register_id INTEGER,
  document_name varchar(255)

);

CREATE TABLE cfe_action (
  date_modified DATE NULL,
  date_created DATE NULL,
  active_status varchar(10),
  cfe_action_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  name varchar(255) 
); 

CREATE TABLE cfe_event (
  date_modified DATE NULL,
  date_created DATE NULL,
  cfe_event_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  name varchar(255) NOT NULL,
  short_name varchar(255) NOT NULL
);

CREATE TABLE cfe_rule (
  date_modified DATE NULL,
  date_created DATE NULL,
  cfe_rule_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  cfe_rule_set_id INTEGER,
  name varchar(255) ,
  cfe_event_id INTEGER,
  salience INTEGER
);

CREATE TABLE cfe_rule_action (
  date_modified DATE NULL,
  date_created DATE NULL,
  cfe_rule_action_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  cfe_rule_id INTEGER,
  cfe_action_id INTEGER,
  params blob ,
  sequence_no INTEGER,
  rule_action_type INTEGER
);

CREATE TABLE cfe_rule_condition (
  date_modified DATE NULL,
  date_created DATE NULL,
  cfe_rule_condition_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  cfe_rule_id INTEGER,
  operator varchar(30),
  operand1 varchar(255),
  operand1_type INTEGER,
  operand2 varchar(255) ,
  operand2_type INTEGER,
  sequence_no INTEGER
);

CREATE TABLE cfe_rule_set (
  date_modified DATE NULL,
  date_created DATE NULL,
  active_status varchar(10),
  cfe_rule_set_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  name varchar(100),
  loan_type_id INTEGER,
  date_effective  DATE NULL,
  created_by varchar(255) ,
  UNIQUE (loan_type_id,date_effective)
);

INSERT INTO application (application_id, name_last, loan_type_id, rule_set_id, company_id) values ('119701', 'FRAHMTSSTEST', 1, 1, 1);
INSERT INTO rule_component (active_status, rule_component_id,name,name_short, grandfathering_enabled) values ('active', 1, 'Test Rule', 'test_rule', 'yes');

INSERT INTO rule_component (active_status, rule_component_id,name,name_short, grandfathering_enabled) values ('active', 2, 'Multi Part Test Rule', 'multi_test_rule', 'yes');
INSERT INTO rule_component_parm (active_status, rule_component_parm_id, rule_component_id, parm_name) values ('active', 1, 1, 'test_rule');
INSERT INTO rule_component_parm (active_status, rule_component_parm_id, rule_component_id, parm_name) values ('active', 2, 2, 'test_rule');
INSERT INTO rule_component_parm (active_status, rule_component_parm_id, rule_component_id, parm_name) values ('active', 3, 2, 'test_rule2');
INSERT INTO rule_set (active_status, rule_set_id, name, loan_type_id, date_effective) values ('active', 1, 'test rule set', 1, date('now'));
INSERT INTO rule_set_component (active_status, rule_set_id, rule_component_id) values ('active', 1, 1);
INSERT INTO rule_set_component (active_status, rule_set_id, rule_component_id) values ('active', 1, 2);
INSERT INTO rule_set_component_parm_value (rule_set_id,rule_component_id, rule_component_parm_id,parm_value) values (1, 1, 1, 'works'); 
INSERT INTO rule_set_component_parm_value (rule_set_id,rule_component_id, rule_component_parm_id,parm_value) values (1, 2, 2, 'worksto'); 
INSERT INTO rule_set_component_parm_value (rule_set_id,rule_component_id, rule_component_parm_id,parm_value) values (1, 2, 3, 'worksthree'); 
INSERT INTO company (active_status, company_id, name, name_short) values ('active', 1, 'test company', 'test'); 
INSERT INTO loan_type (active_status, company_id, loan_type_id, name, name_short) values ('active', 1, 1, 'test loan type', 'testloan'); 

INSERT INTO document ( company_id,  document_list_id , archive_id) values (1,1,1); 
INSERT INTO document_list (active_status, company_id, name, name_short, required, esig_capable, system_id, send_method, document_api) values ('active', 1, 'testTemplate1', 'testTemplate1', 'yes', 'yes', 3, 'email,fax', 'condor');
INSERT INTO document_list (active_status, company_id, name, name_short, required, esig_capable, system_id, send_method, document_api) values ('active', 1, 'testTemplate2', 'testTemplate2', 'no', 'yes', 3, 'email,fax', 'condor');

INSERT INTO document_package (active_status, company_id, name, name_short, document_list_id ) values ('active', 1, 'test package', 'test package',1);
INSERT INTO document_list_package (company_id, document_package_id, document_list_id ) values (1, 1, 1);
INSERT INTO document_list_package (company_id, document_package_id, document_list_id ) values (1, 1, 2);
