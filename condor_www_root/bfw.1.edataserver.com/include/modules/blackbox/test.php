#!/usr/bin/php5
<?

include_once('applog.1.php');
include_once('../olp/server.php');
include_once('vendor_post.php');
include_once('abstract_vendor_post_implementation.php');
include_once('http_client.php');

$sql = Server::Get_Server( "local", "blackbox" );

$lead_data = unserialize('a:67:{s:12:"server_trace";a:8:{i:0;s:9:"10.1.32.1";i:1;s:9:"10.1.32.1";i:2;s:9:"10.1.32.1";i:3;s:9:"10.1.32.1";i:4;s:9:"10.1.32.1";i:5;s:9:"10.1.32.1";i:6;s:9:"10.1.32.1";i:7;s:9:"10.1.32.1";}s:10:"name_first";s:14:"NATASHATSSTEST";s:9:"name_last";s:11:"HALLTSSTEST";s:13:"email_primary";s:24:"648325764@TSSMASTERD.COM";s:10:"phone_home";s:10:"7573920151";s:10:"phone_work";s:10:"2522675320";s:8:"ext_work";s:0:"";s:14:"best_call_time";s:7:"MORNING";s:10:"date_dob_y";s:4:"1978";s:10:"date_dob_m";s:2:"04";s:10:"date_dob_d";s:2:"20";s:10:"ssn_part_1";s:3:"419";s:10:"ssn_part_2";s:2:"13";s:10:"ssn_part_3";s:4:"2728";s:10:"phone_cell";s:0:"";s:11:"home_street";s:13:"128 TIFF LANE";s:9:"home_city";s:14:"ELIZABETH CITY";s:10:"home_state";s:2:"NC";s:8:"home_zip";s:5:"27909";s:13:"employer_name";s:11:"TAN KENNELS";s:15:"state_id_number";s:8:"20271186";s:21:"income_direct_deposit";s:4:"TRUE";s:11:"income_type";s:10:"EMPLOYMENT";s:16:"income_frequency";s:6:"WEEKLY";s:14:"income_date1_y";s:4:"0000";s:14:"income_date1_m";s:2:"00";s:14:"income_date1_d";s:2:"00";s:14:"income_date2_y";s:4:"0000";s:14:"income_date2_m";s:2:"00";s:14:"income_date2_d";s:2:"00";s:9:"bank_name";s:12:"NAVY FEDERAL";s:8:"bank_aba";s:9:"256074974";s:12:"bank_account";s:9:"815557181";s:16:"ref_01_name_full";s:10:"JENN AXTON";s:17:"ref_01_phone_home";s:10:"7247360455";s:19:"ref_01_relationship";s:6:"SISTER";s:16:"ref_02_name_full";s:12:"JENN AXTON 2";s:17:"ref_02_phone_home";s:10:"7247360455";s:19:"ref_02_relationship";s:8:"SISTER 2";s:14:"legal_notice_1";s:4:"TRUE";s:6:"offers";s:5:"FALSE";s:7:"paydate";a:12:{s:9:"frequency";s:6:"WEEKLY";s:10:"weekly_day";s:3:"MON";s:12:"biweekly_day";s:0:"";s:18:"twicemonthly_date1";s:0:"";s:18:"twicemonthly_date2";s:0:"";s:17:"twicemonthly_week";s:0:"";s:16:"twicemonthly_day";s:0:"";s:12:"monthly_date";s:0:"";s:12:"monthly_week";s:0:"";s:11:"monthly_day";s:0:"";s:17:"monthly_after_day";s:0:"";s:18:"monthly_after_date";s:0:"";}s:17:"bank_account_type";s:8:"CHECKING";s:18:"income_monthly_net";s:4:"2900";s:17:"client_ip_address";s:9:"10.1.32.1";s:8:"populate";s:0:"";s:12:"client_state";a:1:{s:7:"_SERVER";a:35:{s:9:"UNIQUE_ID";s:24:"gYDrbAoBIAEAAE@kWjsAAAAE";s:10:"SCRIPT_URL";s:1:"/";s:10:"SCRIPT_URI";s:39:"http://ca.3.123onlinecash.com.ds32.tss/";s:9:"HTTP_HOST";s:31:"ca.3.123onlinecash.com.ds32.tss";s:15:"HTTP_USER_AGENT";s:76:"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0";s:11:"HTTP_ACCEPT";s:99:"text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";s:20:"HTTP_ACCEPT_LANGUAGE";s:14:"en-us,en;q=0.5";s:20:"HTTP_ACCEPT_ENCODING";s:12:"gzip,deflate";s:19:"HTTP_ACCEPT_CHARSET";s:30:"ISO-8859-1,utf-8;q=0.7,*;q=0.7";s:15:"HTTP_KEEP_ALIVE";s:3:"300";s:15:"HTTP_CONNECTION";s:10:"keep-alive";s:11:"HTTP_COOKIE";s:102:"unique_id=e43930263584b7a27c5bfe68631a13dd; ett_promo_id=10000; CP=null*; GroopzUID=0.8374024887373627";s:12:"CONTENT_TYPE";s:33:"application/x-www-form-urlencoded";s:14:"CONTENT_LENGTH";s:3:"942";s:4:"PATH";s:154:"/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/sbin:/sbin:/bin:/usr/sbin:/usr/bin:/home/db2inst1/sqllib/bin:/home/db2inst1/sqllib/adm:/home/db2inst1/sqllib/misc";s:16:"SERVER_SIGNATURE";s:108:"<address>Apache/2.0.52 (Gentoo/Linux) PHP/4.3.6 Server at ca.3.123onlinecash.com.ds32.tss Port 80</address> ";s:15:"SERVER_SOFTWARE";s:38:"Apache/2.0.52 (Gentoo/Linux) PHP/4.3.6";s:11:"SERVER_NAME";s:31:"ca.3.123onlinecash.com.ds32.tss";s:11:"SERVER_ADDR";s:9:"10.1.32.1";s:11:"SERVER_PORT";s:2:"80";s:11:"REMOTE_ADDR";s:9:"10.1.32.1";s:13:"DOCUMENT_ROOT";s:13:"/virtualhosts";s:12:"SERVER_ADMIN";s:14:"root@localhost";s:15:"SCRIPT_FILENAME";s:50:"/virtualhosts/ca.3.123onlinecash.com/www/index.php";s:11:"REMOTE_PORT";s:5:"39227";s:17:"GATEWAY_INTERFACE";s:7:"CGI/1.1";s:15:"SERVER_PROTOCOL";s:8:"HTTP/1.1";s:14:"REQUEST_METHOD";s:4:"POST";s:12:"QUERY_STRING";s:0:"";s:11:"REQUEST_URI";s:1:"/";s:11:"SCRIPT_NAME";s:10:"/index.php";s:8:"PHP_SELF";s:10:"/index.php";s:15:"PATH_TRANSLATED";s:50:"/virtualhosts/ca.3.123onlinecash.com/www/index.php";s:4:"argv";a:0:{}s:4:"argc";i:0;}}s:14:"promo_sub_code";s:8:"coldhit|";s:15:"client_url_root";s:38:"http://ca.3.123onlinecash.com.ds32.tss";s:10:"enterprise";b:0;s:10:"global_key";s:40:"4c7b54b0ec938c3e2cbe75690ff50a0384a924ae";s:4:"page";s:16:"app_2part_page02";s:9:"home_unit";s:0:"";s:13:"income_stream";s:4:"TRUE";s:12:"monthly_1200";s:4:"TRUE";s:16:"checking_account";s:4:"TRUE";s:7:"citizen";s:4:"TRUE";s:9:"unique_id";s:32:"e43930263584b7a27c5bfe68631a13dd";s:12:"ett_promo_id";s:5:"10000";s:2:"CP";s:5:"null*";s:8:"username";s:24:"648325764@TSSMASTERD.COM";s:15:"employer_length";s:4:"TRUE";s:9:"GroopzUID";s:18:"0.8374024887373627";s:3:"dob";s:10:"1978-04-20";s:22:"social_security_number";s:9:"419132728";s:9:"date_hire";s:10:"2005-01-16";s:13:"bb_enterprise";N;}');

if (!$lead_data)
{
	print "Could not unserialize data\n";
	die();
}

$lead_data = Array('data' => $lead_data);
$vendor_post = new Vendor_Post($sql, 'test', $lead_data, 'LOCAL');
$result = $vendor_post->Post();

print "Success? " . ($result->Is_Success() ? 'Yes' : 'No') . "\n";
print_r($result);