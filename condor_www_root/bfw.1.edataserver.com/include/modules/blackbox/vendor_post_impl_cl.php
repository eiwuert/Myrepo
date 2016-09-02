<?php
/**
 * Vendor Implementation for Cashland
 * 
 * This class implements the vendor post for Cashland
 * 
 * This class must contain the zips for the stores AS WELL AS
 * the restriction list in webadmin2. I thought it would make
 * it easier to also have it restrict by state so you can add the
 * state to the restriction list under webadmin and perhaps it 
 * will be faster than scanning the zip list everytime. 
 * 
 * @author Jason Gabriele <jason.gabriele@sellingsource.com>
 */
require_once 'ole_smtp_lib.php';

class Vendor_Post_Impl_CL extends Abstract_Vendor_Post_Implementation
{
	public $store_id = null;
	public $address = null;
	public $phone = null;
	
	// must be passed as 2nd arg to Generic_Thank_You_Page:
	//		parent::Generic_Thank_You_Page($url, self::REDIRECT);
	const REDIRECT = 6;
	
	public static function Get_Post_Type() {
		return Http_Client::HTTP_GET;
	}
	
	protected $rpc_params  = Array
		(
			// Params which will be passed regardless of $this->mode
			'ALL'     => Array(
              'email'      => '1234@tssmasterd.com',//'jason.gabriele@sellingsource.com'),
			  'store_addresses' => array(
						'A'   => array('address' => '1209 North Barron Street, Eaton, Oh 45320',
									   'phone' => '1-937-472-5656'),
						'AIM' => array('address' => '10 S. Canton Rd., Akron, OH 44312',
									   'phone' => '1-330-564-2300'),
						'ALI' => array('address' => '1622 W. State St., Alliance, OH 44601',
									   'phone' => '1-330-821-2160'),
						'ASH' => array('address' => '1310 Claremont Ave., Ashland, Oh 44805',
									   'phone' => '1-419-281-7585'),
						'ATB' => array('address' => '1626 E. Prospect Rd., Ashtabula, Oh 44044',
									   'phone' => '1-440-992-0781'),
						'AUS' => array('address' => '4805 Mahoning Ave, Austintown, Oh 44515',
									   'phone' => '1-330-792-7778'),
						'B' => array('address' => '643 Troy St., Dayton, OH 45404',
									 'phone' => '1-937-224-0012'),
						'BAR' => array('address' => '790 Wooster Rd., Barberton, Oh 44203',
									   'phone' => '1-330-753-4666'),
						'BGN' => array('address' => '1028-C N. Main Street, Bowling Green, Oh 43402',
									   'phone' => '1-419-353-0241'),
						'BLP' => array( 'address' => '714 Washington Boulevard, Belpre, OH 45714',
									    'phone' => '1-740-401-0687'),
						'BLR' => array('address' => '2401 Belmont St., Bellaire, OH 43906',
									   'phone' => '1-740-676-2395'),
						'BRD'=> array('address' => '640 Boardman Canfield Road, Youngstown, OH 44512',
									  'phone' => '1-330-965-8706'),
						'BRY'=> array('address' => '123 E. South St., Bryan, OH 43506',
									  'phone' => '1-419-633-3475'),
						'BUC' => array('address' => '101 N. Sandusky Ave., Bucyrus, OH 44820',
									   'phone' => '1-419-563-2842'),
						'CAM' => array('address' => '723 Southgate Parkway, Cambridge, Oh 43725',
									   'phone' => '1-740-439-9885'),
						'CBA' => array("phone" => "1-330-482-3011",
									   'address' => '144 N. Main St, Columbiana, Oh 44408'),
						'CCA' => array('address' => '5600 Cleveland Ave., Columbus, Oh 43231',
									   'phone'=>'1-614-394-0010'),
						'CCH' => array('address' => '1590 Goodman Ave.,  Cincinnati, Oh 45224',
									   'phone' => '1-513-522-3712'),
						'CDP' => array('address' => '4081 East Galbraith Rd., Cincinnati, Oh 45236',
									   'phone' => '1-513-891-8329'),
						'CDT' => array('address' => '537 Vine Street, Cincinnati, Oh 45202',
									   'phone' => '1-513-721-5626'),
						'CEL' => array('address' => '1971 Havermann Rd., Celina, Oh 45822',
									   'phone' => '1-419-586-9962'),
						'CEN' => array('address' => '980 Miamisburg-Centerville Rd., Dayton, OH 45459',
									   'phone' => '1-937-432-9601'),
						'CFP' => array('address' => '2252 Waycross Rd., Forest Park, Oh 45240',
									   'phone' => '1-513-851-7800'),
						'CFS' => array('address' => '1682 State Rd., Cuyahoga Falls, Oh 44223',
									   'phone' => '1-330-564-0689'),
						'CGW' => array('address' => '5708 Glenway Ave., Cincinnati, Oh 45238',
									   'phone' => '1-513-451-0900'),
						'CHI' => array('address' => '25 N. Bridge Street, Chillicothe, Oh 45601',
									   'phone' => '1-740-774-9876'),
						'CMD' => array('address' => '3048 Mahoning Rd, N.E., Canton, Oh 44705',
									   'phone' => '1-330-455-2728'),
						'CNB' => array('address' => '3474 North Bend Rd., Cincinnati, Oh 45239',
									   'phone' => '1-513-481-1120'),
						'CNR' => array("phone" => "1-614-335-0015",
									   'address' => '5495 Hall Rd, Unit 2, Columbus, Oh 43228'),
						'COO' => array('address' => '205 Lancaster Pike, Circleville, OH 43113',
									   'phone' => '1-740-420-6365'),
						'COS' => array('address' => '518 S. Second Street, Coshocton, Oh 43812',
									   'phone' => '1-740-295-0140'),
						'CPH' => array('address' => '3712 Cleveland Ave. N.W., Canton, Oh 44709',
									   'phone' => '1-330-493-7452'),
						'CPK' => array('address' => '210 Third Avenue, Chesapeake, Oh 45619',
									   'phone' => '1-740-867-0544'),
						'CRR' => array('address' => '4499 Refugee Rd., Columbus, Oh 43232',
									   'phone' => '1-614-866-6944'),
						'CUY' => array('address' => '1205 Northmoreland Blvd., Cuyahoga Falls, Oh 44221',
									   'phone' => '1-330-564-2374'),
						'CWH' => array('address' => '805 S. Hamilton Rd., Whitehall, Oh 43213',
									   'phone' => '1-614-228-5331'),
						'CWT' => array('address' => '2409 W. Tuscarawas St., Canton, Oh 44708',
									   'phone' => '1-330-458-2402'),
						'CWV' => array('address' => '12 Westerville Square, Westerville, Oh 43081',
									   'phone' => '1-614-942-1066'),
						'DEF' => array('address' => '1210 South Clinton Street, Defiance, OH 43512',
									   'phone' => '1-419-782-4446'),
						'DEL' => array('address' => '268 South Sandusky Street, Delaware, Oh 43015',
									   'phone' => '1-740-362-8708'),
						'DLW' => array('address' => '2320-22 East Dorothy Lane, Kettering, OH 45420',
									   'phone' => '1-937-395-0103'),
						'DOV' => array('address' => '207 South Wooster Ave., Dover, Oh 44622',
									   'phone' => '1-330-602-5356'),
						'ELK' => array('address' => '34071 Vine Street, Unit B, Eastlake, Oh 44095',
									   'phone' => '1-440-942-6258'),
						'ELP' => array("phone" => "1-330-382-0933",
									   'address' => '16761 St. Clair Ave, East Liverpool, Oh 43920'),
						'ELY' => array('address' => '505 North Abbe Rd., Elyria, Oh 44035',
									   'phone' => '1-440-365-5584'),
						'FBN' => array('address' => '83 W Dayton-Yellow Springs Rd., Fairborn, OH 45324',
									   'phone' => '1-937-879-2268'),
						'FFD' => array('address' => '6526 Dixie Highway, Fairfield, Oh 45014',
									   'phone' => '1-513-454-2400'),
						'FIN' => array('address' => '1055 Tiffin Avenue, Findlay, Oh 45840',
									   'phone' => '1-419-424-3443'),
						'FOS' => array('address' => '1227 N. Countyline Rd., Fostoria, Oh 44830',
									   'phone' => '1-419-436-1411'),
						'FRE' => array('address' => '1839 West State St., Unit B., Fremont, Oh 43420',
									   'phone' => '1-419-332-2314'),
						'FVP' => array('address' => '22547 Lorain Rd., Fairview Park, Oh 44126',
									   'phone' => '1-440-716-9178'),
						'G' => array('address' => '5150 Salem Ave, Trotwood, OH 45426',
									 'phone' => '1-937-854-0690'),
						'GAL' => array('address' => '305 1/2 Upper River Road, Gallipolis, Oh 45631',
									   'phone' => '1-740-441-8815'),
						'GEO' => array("phone" => "1-937-378-5626",
									   'address' => '4893 State Route 125, Georgetown, Oh 45121'),
						'GRO' => array("phone" => "1-614-539-0033",
									   'address' => '2228 Stringtown Rd, Grove City, oh 43123'),
						'HAM' => array('address' => '952 West Main Street, Hamilton, Oh 45013',
									   'phone' => '1-513-868-7900'),
						'HAR' => array('address' => '1118 Harrison Ave., Harrison, Oh 45030',
									   'phone' => '1-513-452-2274'),
						'HUB' => array('address' => '322 West Liberty Street, Hubbard, Oh 44425',
									   'phone' => '1-330-534-2012'),
						'IAG' => array('address' => '7085 Michigan Road, Indianapolis, IN 46268',
									   'phone' => '1-317-472-4920'),
						'IAL' => array('address' => '1201 N Wayne St, Suite B, Angola, IN 46703',
									   'phone' => '1-260-624-3265'),
						'IAN' => array('address' => '540 South Scatterfield Rd., Anderson, IN 46012',
									   'phone' => '1-765-683-0215'),
						'IBF' => array('address' => '616 North Main Street, Bluffton, IN 46714',
									   'phone' => '1-260-824-3452'),
						'IBG' => array('address' => '353 S. First St., Beech Grove, IN 46107',
									   'phone' => '1-317-472-7300'),
						'IBL' => array('address' => '1710 West Third Street, Bloomington, IN 47404',
									   'phone' => '1-812-323-7619'),
						'ICN' => array('address' => '2223 North Park Rd., Connersville, IN 47331',
									   'phone' => '1-765-285-3563'),
						'IEN' => array('address' => '301 E. Diamond Avenue, Evansville, IN 47711',
									   'phone' => '1-812-421-0265'),
						'IEV' => array('address' => '2039 Washington Avenue, Evansville, IN 47714',
									   'phone' => '1-812-471-2824'),
						'IFF' => array('address' => '2480 E. Wabash St., Frankfort, IN 46041',
									   'phone' => '1-765-659-0922'),
						'IFK' => array('address' => '154 North Morton Street, Franklin, IN 46131',
									   'phone' => '1-317-346-0468'),
						'IFW' => array('address' => '4850 S. Calhoun St., Fort Wayne, IN 46807',
									   'phone' => '1-260-744-0520'),
						'IGO' => array('address' => '1827 Lincolnway East, Goshen, IN 46526',
									   'phone' => '1-574-535-0172'),
						'IHB' => array('address' => '1641 East 37th Avenue, Hobart, IN 46342',
									   'phone' => '1-219-947-7346'),
						'IHN' => array('address' => '1274 South Jefferson Street, Huntington, IN 46750',
									   'phone' => '1-260-359-1361'),
						'IKO' => array('address' => '1722 Markland Avenue, Kokomo, IN 46901',
									   'phone' => '1-765-868-9532'),
						'ILF' => array('address' => '3267 Teal Rd., Lafayette, IN 47905',
									   'phone' => '1-765-471-0896'),
						'ILG' => array('address' => '3920 East Market Street, Ste. E, Logansport, IN 46947',
									   'phone' => '1-574-753-2091'),
						'IML' => array('address' => '7896 Broadway, Merrillville, IN 46410',
									   'phone' => '1-219-736-6573'),
						'IMN' => array('address' => '103 East Mcgalliard, Muncie, IN 47304',
									   'phone' => '1-765-741-0890'),
						'IMR' => array('address' => '1701 South Western Avenue, Marion IN 46953',
									   'phone' => '1-765-651-0926'),
						'IMV' => array('address' => '1560 S. Ohio St., Martinsville, IN 46151',
									   'phone' => '1-765-349-5621'),
						'IMW' => array('address' => '702 South Ironwood Drive, Mishawaka, IN 46544',
									   'phone' => '1-574-254-0267'),
						'INA' => array('address' => '2635 Charlestown Rd., New Albany, IN 47150',
									   'phone' => '1-812-981-0854'),
						'INTHT' => array('address' => '1440 South 3rd Street, Terre Haute, IN 47802',
									   'phone' => '1-812-234-3084'),
						'ISB' => array('address' => '5207 Western Avenue, South Bend, IN 46619',
									   'phone' => '1-574-234-0469'),
						'ISM' => array('address' => '1104 East Tipton St., Suite B, Seymour, IN 47274',
									   'phone' => '1-812-522-1170'),
						'ISP' => array('address' => '5725 Crawfordsville Rd., Speedway, IN 46224',
									   'phone' => '1-317-472-0125'),
						'ITF' => array('address' => '9945 East 21st., Suite B, Indianapolis, IN 46219',
									   'phone' => '1-317-472-4980'),
						'IWP' => array('address' => '1604 N. Arlington Ave., Indianapolis, IN 46218',
									   'phone' => '1-317-472-4915'),
						'IWS' => array('address' => '714 North Detroit Street, Warsaw, IN 46580',
									   'phone' => '1-574-269-3813'),
						'IWT' => array('address' => '6767 W. Washington St., Indianapolis, IN 46241',
									   'phone' => '1-317-522-2065'),
						'KBG' => array('address' => '719 US 31 W Bypass, Bowling Green, KY 42101',
									   'phone' => '1-270-783-8017'),
						'KDV' => array('address' => '1019 Huntsonville Rd., Danville, KY 40422',
									   'phone' => '1-859-236-4782'),
						'KFC' => array('address' => '6321 Bardstown Rd., Louisville, KY 40291',
									   'phone' => '1-502-657-7000'),
						'KFF' => array('address' => '363 Versailles Rd., Frankfort, KY 40601',
									   'phone' => '1-502-848-9979'),
						'KHN' => array('address' => '2 North Green St., Henderson, KY 42420',
									   'phone' => '1-270-830-7184'),
						'KHP' => array('address' => '2110 Fort Campbell Blvd., Hopkinsville, KY 42240',
									   'phone' => '1-270-885-4545'),
						'KLV' => array('address' => '335 Esplanade, Louisville, KY 40214',
									   'phone' => '1-502-657-1950'),
						'KLX' => array('address' => '361 Southland Dr., Lexington, KY 40503',
									   'phone' => '1-859-967-1700'),
						'KPD' => array('address' => '3199 Jackson Street, Paducah, KY 42001',
									   'phone' => '1-270-444-6426'),
						'KPH' => array('address' => '7201 Preston Highway, Louisville, KY 40219',
									   'phone' => '1-502-657-4300'),
						'KPR' => array('address' => '200 Highway 62 West, Princeton, KY 42445',
									   'phone' => '1-270-365-5732'),
						'KSB' => array('address' => '122 Main Street, Shelbyville, KY 40065',
									   'phone' => '1-502-647-1604'),
						'KSH' => array('address' => '2140 Old Shepherdsville Rd., Louisville, KY 40218',
									   'phone' => '1-502-657-1200'),
						'KWN' => array('address' => '810 By-Pass Rd Suite B, Winchester, KY 40391',
									   'phone' => '1-859-737-5607'),
						'MAD' => array('address' => '1025 South Main Street, Adrian, MI 49221',
									   'phone' => '1-517-265-7012'),
						'MET' => array('address' => '703 Pike Street, Marietta, Oh 45750',
									   'phone' => '1-740-376-9202'),
						'MGC' => array('address' => '33471 Ford Road, Garden City, MI 48135',
									   'phone' => '1-734-525-3963'),
						'MHD' => array('address' => '38 East Carleton, Hillsdale, MI 49242',
									   'phone' => '1-517-437-5973'),
						'MJK' => array('address' => '1184 North West Avenue, Jackson, MI 49202',
									   'phone' => '1-517-796-0557'),
						'MLN' => array('address' => '2521 S. Cedar Street, Lansing, MI 48910',
									   'phone' => '1-517-482-2063'),
						'MMH' => array('address' => '28147 John R Road, Madison Heights, MI 48071',
									   'phone' => '1-248-547-1852'),
						'MMR' => array('address' => '524 North Telegraph Road, Monroe, MI 48162',
									   'phone' => '1-734-243-0411'),
						'MSH' => array('address' => '4075 E. 14 Mile Road, Sterling Heights, MI 48310',
									   'phone' => '1-586-978-2953'),
						'MTY' => array('address' => '9025 Pardee Road, Taylor, MI 48180',
									   'phone' => '1-313-295-0731'),
						'MWL' => array('address' => '2221 South Wayne Road, Westland, MI 48186',
									   'phone' => '1-734-721-9803'),
						'MWR' => array('address' => '6768 E. 12 Mile Road, Warren, MI 48092',
									   'phone' => '1-586-558-4749'),
						'MYP' => array('address' => '1530 Holmes Road, Ypsilanti, MI 48198',
									   'phone' => '1-734-484-3457'),
						'IRN' => array('address' => '205 South Sixth Street, Ironton, Oh 45638',
									   'phone' => '1-740-533-2615'),
						'KEN' => array('address' => '905 E. Columbus Street, Kenton, OH 43326',
									   'phone' => '1-419-675-1644' ),
						'KMD' => array("phone" => "1-270-825-1856",
									   'address' => '315 South Main Street, Madisonville, Ky 42431'),
						'KMH' => array("phone" => "1-606-784-2271",
									   'address' => '125 Pinecrest Drive, Morehead, Ky 40351'),
						'L' => array('address' => '3515 Roosevelt Blvd. Middletown, OH 45044',
									 'phone' => '1-513-424-3727'),
						'LAN' => array('address' => '845 N. Memorial Drive, Lancaster, Oh 43130',
									   'phone' => '1-740-689-0808'),
						'LEB' => array("phone" => "1-513-934-5299",
									   'address' => '744 Columbus Ave, Lebanon, Oh 45036'),
						'LGN' => array('address' => '42 Hocking Mall, Logan, Oh 43138',
									   'phone' => '1-740-380-3976'),
						'LIM' => array('address' => '1269 Bellefontaine Ave., Lima, OH 45804',
									   'phone' => '1-419-222-8839'),
						'LIN' => array('address' => '2933 Linden Ave., Dayton, OH 45410',
									   'phone' => '1-937-252-0434'),
						'LMA' => array('address' => '2355 Elida Road, Lima, OH 45805',
									   'phone' => '1-419-224-0215'),
						'LND' => array('address' => '3681 West 105th Street, Cleveland, Oh 44111',
									   'phone' => '1-216-252-6377'),
						'LON' => array('address' => '160 S. Main St., London, Oh 43140',
									   'phone' => '1-740-845-1051'),
						'LOU' => array('address' => '536 W. Main Street, Louisville, Oh 44641',
									   'phone' => '1-330-875-1808'),
						'LRN' => array('address' => '4280 Oberlin Ave., Lorain, Oh 44053',
									   'phone' => '1-440-282-2505'),
						'MAN' => array('address' => '871 Ashland Rd., Mansfield, Oh 44905',
									   'phone' => '1-419-589-0230'),
						'MAO' => array('address' => '1299 Mt. Vernon Ave., Marion, Oh 43302',
									   'phone' => '1-740-725-1311'),
						'MAR' => array('address' => '302 E. Fifth St., Marysville, Oh 43040',
									   'phone' => '1-937-642-9877'),
						'MAS' => array('address' => '909 Lincoln Way East, Massillon, Oh 44646',
									   'phone' => '1-330-834-2670'),
						'MED' => array('address' => '410 S. Elmwood, Medina, Oh 44256',
									   'phone' => '1-330-721-9118'),
						'MET' => array("phone" => "1-740-376-9202",
									   'address' => '703 Pike Street, Marietta, Oh 45750'),
						'MIA' => array('address' => '3858 Miamisburg-Centerville Rd., West Carrollton, OH 45449',
									   'phone' => '1-937-866-9253'),
						'MIL' => array('address' => '1057 State Route 28, Milford, Oh 45750',
									   'phone' => '1-513-965-8862'),
						'MOL' => array('address' => '5901 Andrews Rd., Mentor On The Lake, Oh 44060',
									   'phone' => '1-440-209-0886'),
						'MRY' => array("phone" => "1-330-448-6627",
									   'address' => '906 South Irvine Ave, Masury, Oh 44438'),
						'MSF' => array('address' => '261 Lexington Ave., Mansfield, Oh 44907',
									   'phone' => '1-419-522-0941'),
						'MTV' => array('address' => '987 Coshocton Ave., Mt. Vernon, Oh 43050',
									   'phone' => '1-740-392-6136'),
						'N' => array('address' => '6375 Chambersburg Rd., Huber Heights, OH 45424',
									 'phone' => '1-937-233-4397'),
						'NAP' => array('address' => '1414 N. Scott St., Napoleon, OH 43545',
									   'phone' => '1-419-592-1700'),
						'NBL' => array('address' => '815 South Main St., Bellefontaine, Oh 43311',
									   'phone' => '1-937-592-2400'),
						'NIL' => array('address' => '5876 St. Rt. 422, Niles, Oh 44446',
									   'phone' => '1-330-544-9330'),
						'NLX' => array('address' => '401 S. Main Street, New Lexington, Oh 43764',
									   'phone' => '1-740-342-5407'),
						'NOR' => array('address' => '214 Milan Avenue Suite A, Norwalk, Oh 44857',
									   'phone' => '1-419-668-9045'),
						'NRK' => array('address' => '1154 North 21st St., Newark, Oh 43055',
									   'phone' => '1-740-364-0478'),
						'NUJ' => array('address' => '1331 Wilmington, Ave., Dayton, OH 45420',
									   'phone' => '1-937-396-1846'),
						'NUK' => array('address' => '4977 N. Main St., Dayton, OH 45405',
									   'phone' => '1-937-278-0148'),
						'NUS' => array('address' => '1010 N. Bechtle Ave., Springfield, Oh 45504',
									   'phone' => '1-937-322-5700'),
						'NUW' => array('address' => '1254 Bellefontaine Ave., Wapakoneta, OH 45895',
									   'phone' => '1-419-739-7707'),
						'NUZ' => array('address' => '645 West Second Street, Xenia, Oh 45385',
									   'phone' => '1-937-374-2738'),
						'O' => array('address' => '1105 Northview Drive, Hillsboro, Oh 45133',
									 'phone' => '1-937-393-5555'),
						'OHELD' => array('address' => '18500 Lake Shore Blvd., Euclid, Oh 44119',
										 'phone' => '1-216-481-2168'),
						'OHGHT' => array("phone" => "1-216-581-4650",
										 'address' => '12548 Rockside Rd, Garfield , Ohio 44125'),
						'ORV' => array('address' => '330 West High Street, Orrville, Oh 44667',
									   'phone' => '1-330-684-1045'),
						'P' => array('address' => '602 Woodman Dr., Dayton, OH 45431',
									 'phone' => '1-937-253-7842'),
						'PAN' => array('address' => '1892 Mentor Avenue, Painesville, Oh 44077',
									   'phone' => '1-440-639-0425'),
						'PAR' => array('address' => '5750 Chevrolet Blvd., Parma, Oh 44130',
									   'phone' => '1-440-842-0449'),
						'PIQ' => array('address' => '104 N. College Street, Piqua, OH 45356',
									   'phone' => '1-937-773-7989'),
						'POM' => array('address' => '397 W. Main St., Pomeroy, Oh 45769',
									   'phone' => '1-740-992-9000'),
						'POR' => array('address' => '1157 Young Street Unit B, Portsmouth, Oh 45662',
									   'phone' => '1-740-354-1114'),
						'Q' => array('address' => '5582 Springboro Pike, Dayton, OH 45449',
									 'phone' => '1-937-298-2726'),
						'R' => array('address' => '624 Wagner Ave., Greenville, Oh 45331',
									 'phone' => '1-937-547-9910'),
						'RAV' => array('address' => '3477 W. State Route 59, Ravenna, Oh 44266',
									   'phone' => '1-330-296-2309'),
						'REY' => array('address' => '1699 Brice Rd., Reynoldsburg, Oh 43068',
									   'phone' => '1-614-863-6513'),
						'S' => array('address' => '601 E. 2nd St., Franklin, OH 45005',
									 'phone' => '1-937-746-3237'),
						'SAL' => array('address' => '2586 East State St., Suite 2, Salem, Oh 44460',
									   'phone' => '1-330-332-5578'),
						'SAN' => array('address' => '223 West Perkins Ave., Sandusky, Oh 44870',
									   'phone' => '1-419-621-8587'),
						'SID' => array('address' => '1240 Wapakoneta Ave., Sidney, OH 45365',
									   'phone' => '1-937-493-9966'),
						'SPR' => array('address' => '2135 E. Main Street, Springfield, Oh 45503',
									   'phone' => '1-937-325-5446'),
						'STU' => array("phone" => "1-740-264-2316",
									   'address' => '2134 Sunset Blvd. Steubenville, Oh 43952'),
						'TAL' => array('address' => '3247 West Alexis Rd., Toledo, Oh 43613',
									   'phone' => '1-419-472-3073' ),
						'TLW' => array('address' => '5021 Lewis Ave., Toledo, Oh 43612',
									   'phone' => '1-419-470-1661'),
						'TOR' => array('address' => '2037 Woodville Rd., Oregon, Oh 43616',
									   'phone' => '1-419-698-4214'),
						'TRY' => array('address' => '2222 North Reynolds Rd., Toledo, Oh 43615',
									   'phone' => '1-419-535-5533'),
						'TSB' => array('address' => '2034 South Byrne Rd., Toledo, Oh 43614',
									   'phone' => '1-419-382-1180'),
						'TSR' => array('address' => '533 S. Reynolds, Toledo, Oh 43615',
									   'phone' => '1-419-578-9498'),
						'TTG' => array('address' => '246 E. Alexis Rd., Toledo, Oh 43612',
									   'phone' => '1-419-476-9362'),
						'URB' => array("phone" => "1-937-652-4800",
									   'address' => '776 Scioto Street, Urbana, Oh 43078'),
						'V' => array('address' => '322 E. National Rd., Vandalia, OH 45377',
									 'phone' => '1-937-454-9190'),
						'VWT' => array('address' => '1108 S. Shannon St., Van Wert, Oh 45791',
									   'phone' => '1-419-232-3230'),
						'W' => array('address' => '957 S. South Street, Wilmington, Oh 45177',
									 'phone' => '1-937-383-4321'),
						'WAR' => array('address' => '1040 Elm Road, N.E., Warren, Oh 44483',
									   'phone' => '1-330-372-5877'),
						'WAS' => array('address' => '1149 East Temple Street, Washington Court House, Oh 43160',
									   'phone' => '1-740-636-0817'),
						'WAV' => array('address' => '408 E.Emmitt, Waverly, Oh 45690',
									   'phone' => '1-740-941-1123'),
						'WEL' => array('address' => '702 South Pennsylvania Avenue, Wellston, Oh 45692',
									   'phone' => '1-740-384-1412'),
						'WHL' => array('address' => '8308 B&D Ohio River Road, Wheelersburg, Oh 45694',
									   'phone' => '1-740-574-1862'),
						'WIL' => array('address' => '15 E. Walton Ave., Willard, OH 44890',
									   'phone' => '1-419-964-0225'),
						'WOO' => array('address' => '806 East Bowman Street, Wooster, Oh 44691',
									   'phone' => '1-330-264-8129'),
						'WUN' => array('address' => '11592 State Route 41, West Union, Oh 45693',
									   'phone' => '1-937-544-7701'),
						'YOU' => array('address' => '890 E. Midlothian Blvd., Youngstown, Oh 44502',
									   'phone' => '1-330-788-7003'),
						'ZAN' => array('address' => '1821 Maple Ave., Zanesville, Oh 43701',
									   'phone' => '1-740-450-3810'))),
			// Specific cases varying with $this->mode, having higher priority than ALL.
			'LOCAL'   => Array(
				),
			'RC'      => Array(
				),
			'LIVE'    => Array(
              'email'      => 'leads@cashlandloans.com', 
				)
		);
	
	protected $static_thankyou = FALSE;
	
	public function Generate_Fields(&$lead_data, &$params)
	{
		//Paydate Freq
        if(isset($lead_data['data']['paydate_model']) && 
           isset($lead_data['data']['paydate_model']['income_frequency']) &&
           $lead_data['data']['paydate_model']['income_frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate_model']['income_frequency'];
        }
        elseif(isset($lead_data['data']['income_frequency']) && 
           $lead_data['data']['income_frequency'] != "")
        {
            $freq = $lead_data['data']['income_frequency'];
        }
        elseif(isset($lead_data['data']['paydate']) && 
               isset($lead_data['data']['paydate']['frequency']) &&
               $lead_data['data']['paydate']['frequency'] != "")
        {
        	$freq = $lead_data['data']['paydate']['frequency'];
        }
		$fields = array();
		
		//To Email
		$fields['TO_EMAIL'] = $params['email'];
		
		//Find correct store
		$state = strtoupper($lead_data['data']['home_state']);
		$zip = $lead_data['data']['home_zip'];
		
		if(isset($_SESSION['suppression_list_catch']['cl']['store']['ref']))
		{
			$this->store_id = $_SESSION['suppression_list_catch']['cl']['store']['ref'];
			$fields['STORE_ID'] = '['.$this->store_id.']';
			$this->address  = $fields['STORE_ADDRESS'] = $params['store_addresses'][$this->store_id]['address'];
			$this->phone = $params['store_addresses'][$this->store_id]['phone'];
		}
        
        //Paydates
		$fields["PAYDATE1"] = $lead_data['data']['paydates'][0];
		$fields["PAYDATE2"] = $lead_data['data']['paydates'][1];
		
		//DOB
		$fields["DOB"] = $lead_data['data']['date_dob_y'] . "/" . 
		    		     $lead_data['data']['date_dob_m'] . "/" .
		    		     $lead_data['data']['date_dob_d'];
        
        //Put in rest of the fields
        $fields["NAME_FIRST"] = $lead_data['data']['name_first'];
        $fields["NAME_LAST"] = $lead_data['data']['name_last'];
        $fields["EMAIL_PRIMARY"] = $lead_data['data']['email_primary'];
        $fields["PHONE_HOME"] = $lead_data['data']['phone_home'];
        $fields["PHONE_WORK"] = $lead_data['data']['phone_work'];
        $fields["PHONE_CELL"] = $lead_data['data']['phone_cell'];
        $fields["HOME_STREET"] = $lead_data['data']['home_street'];
        $fields["HOME_UNIT"] = $lead_data['data']['home_unit'];
        $fields["HOME_CITY"] = $lead_data['data']['home_city'];
        $fields["HOME_STATE"] = $lead_data['data']['home_state'];
        $fields["HOME_ZIP"] = $lead_data['data']['home_zip'];
        $fields["EMPLOYER_NAME"] = $lead_data['data']['employer_name'];
        $fields["INCOME_DIRECT_DEPOSIT"] = $lead_data['data']['income_direct_deposit'];
        $fields["INCOME_TYPE"] = $lead_data['data']['income_type'];
        $fields["INCOME_FREQUENCY"] = $freq;
        $fields["INCOME_MONTHLY_NET"] = $lead_data['data']['income_monthly_net'];
        $fields["BANK_NAME"] = $lead_data['data']['bank_name'];
        $fields["BANK_ABA"] = $lead_data['data']['bank_aba'];
        $fields["BANK_ACCOUNT_TYPE"] = $lead_data['data']['bank_account_type'];
        $fields["REF_01_NAME_FULL"] = $lead_data['data']['ref_01_name_full'];
        $fields["REF_01_PHONE_HOME"] = $lead_data['data']['ref_01_phone_home'];
        $fields["REF_01_RELATIONSHIP"] = $lead_data['data']['ref_01_relationship'];
        $fields["REF_02_NAME_FULL"] = $lead_data['data']['ref_02_name_full'];
        $fields["REF_02_PHONE_HOME"] = $lead_data['data']['ref_02_phone_home'];
        $fields["REF_02_RELATIONSHIP"] = $lead_data['data']['ref_02_relationship'];
        $fields["CLIENT_IP_ADDRESS"] = $lead_data['data']['client_ip_address'];
        
        //Check to make sure no fields are null
        foreach($fields as $key => $value)
        {
        	if($value == NULL) $fields[$key] = " ";
        }
        
        return $fields;
	}

	/**
	 * HTTP Post Process
	 * 
	 * This is an override for the post process
	 * @param array Field List
	 * @param boolean Qualify (not used)
	 * @return object vendor post obj
	 */
	public function HTTP_Post_Process($fields, $qualify = FALSE) 
	{
		//Basic Data
		$data = array(
				"name"						=> strtoupper( $fields['NAME_FIRST'] ) . ' ' . strtoupper( $fields['NAME_LAST'] ),
				"client_ip_address"			=> $fields['CLIENT_IP_ADDRESS'],
				"site_name"                 => "Cashland"
			);
			
		$t_data = array_merge($data, $fields);
		
		$res = FALSE;
		/* Old mail handiling, kept incase we need this reuse ths again
		   
		//TX mode
		$mode = (BFW_MODE=="LOCAL" || BFW_MODE=="RC") ? "rc" : "live";
		$t_data["email_primary"]      = "no-reply@sellingsource.com";
		$t_data["email_primary_name"] = $fields['NAME_FIRST'] . ' ' . $fields['NAME_LAST'];
		$t_data['applciation_id'] = $this->getApplicationId();

		try
		{
			require_once(BFW_CODE_DIR.'OLP_TX_Mail_Client.php');
			$tx = new OlpTxMailClient(false);
			$res = $tx->sendMessage($mode,
									"cashland_part_2", 
									$fields['TO_EMAIL'], 
									$_SESSION['statpro']['track_key'], 
									$t_data);
			$res = TRUE;
		}
        catch(Exception $e)
		{
			$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
			$applog->Write("CL App Failed for {$_SESSION['application_id']}");
		}
		
		if($res === FALSE)
		{
			$applog->Write("CL App Failed for {$_SESSION['application_id']}");
		}
	}
	*/
			// fill out the local mail() body
			$body =  "Store Address:  {$fields['STORE_ADDRESS']}\r\n";
			$body .= "First Name:     {$fields['NAME_FIRST']}\r\n";
			$body .= "Last Name:      {$fields['NAME_LAST']}\r\n";
			$body .= "Email:          {$fields['EMAIL_PRIMARY']}\r\n";
			$body .= "DOB:            {$fields['DOB']}\r\n";
			$body .= "Home Phone:     {$fields['PHONE_HOME']}\r\n";
			$body .= "Work Phone:     {$fields['PHONE_WORK']}\r\n";
			$body .= "Cell Phone:     {$fields['PHONE_CELL']}\r\n";
			$body .= "Home Street:    {$fields['HOME_STREET']}\r\n";
			$body .= "Home Unit:      {$fields['HOME_UNIT']}\r\n";
			$body .= "Home City:      {$fields['HOME_CITY']}\r\n";
			$body .= "Home State:     {$fields['HOME_STATE']}\r\n";
			$body .= "Home Zip:       {$fields['HOME_ZIP']}\r\n";
			$body .= "Employer:       {$fields['EMPLOYER_NAME']}\r\n";
			$body .= "Has Direct Dep: {$fields['INCOME_DIRECT_DEPOSIT']}\r\n";
			$body .= "Income Source:  {$fields['INCOME_TYPE']}\r\n";
			$body .= "Income Freq.:   {$fields['INCOME_FREQUENCY']}\r\n";
			$body .= "Monthly Income: {$fields['INCOME_MONTHLY_NET']}\r\n";
			$body .= "Bank Name:      {$fields['BANK_NAME']}\r\n";
			$body .= "Bank ABA:       {$fields['BANK_ABA']}\r\n";
			$body .= "Bank Type:      {$fields['BANK_ACCOUNT_TYPE']}\r\n";
			$body .= "Paydate #1:     {$fields['PAYDATE1']}\r\n";
			$body .= "Paydate #2:     {$fields['PAYDATE2']}\r\n";
			$body .= "Reference #1\r\n";
			$body .= "Name:     {$fields['REF_01_NAME_FULL']}\r\n";
			$body .= "Phone:    {$fields['REF_01_PHONE_HOME']}\r\n";
			$body .= "Relation: {$fields['REF_01_RELATIONSHIP']}\r\n";
			$body .= "Reference #2\r\n";
			$body .= "Name:     {$fields['REF_02_NAME_FULL']}\r\n";
			$body .= "Phone:    {$fields['REF_02_PHONE_HOME']}\r\n";
			$body .= "Relation: {$fields['REF_02_RELATIONSHIP']}\r\n";
			
			$subject = "{$fields['STORE_ID']} Online Application From The Selling Source";
			$headers = "From: \"The Selling Source\" <no-reply@sellingsource.com>\r\n";
			$headers = "Date: " . date("r") . "\r\n";
			
			if(!mail($fields['TO_EMAIL'],$subject,$body,$headers))
			{
					$applog = OLP_Applog_Singleton::Get_Instance(APPLOG_SUBDIRECTORY, APPLOG_SIZE_LIMIT, APPLOG_FILE_LIMIT, NULL, APPLOG_ROTATE);
					$applog->Write("mail() failed sending CL");
			}
			else
			{
				$res = TRUE;
			}
			
		$t = array();
		$result = $this->Generate_Result($res, $t);
		$result->Set_Data_Sent(serialize($fields));
		$result->Set_Data_Received("");
		
		return $result;
	}

	public function Generate_Result(&$data_received, &$cookies)
	{
		$result = new Vendor_Post_Result();
		
		if($data_received)
		{
			$result->Set_Message("Accepted");
			$result->Set_Success(TRUE);
			$result->Set_Thank_You_Content( self::Thank_You_Content( $data_received ) );
			$result->Set_Vendor_Decision('ACCEPTED');
		}
		else
		{
			$result->Set_Message("Rejected");
			$result->Set_Success(FALSE);
			$result->Set_Vendor_Decision('REJECTED');
		}

		return $result;
	}

	public function __toString()
	{
		return "Vendor Post Implementation [CL]";
	}
	
	public function Thank_You_Content(&$data_received)
	{
        $content = "<p>Congratulations, you have been approved for a 21-DAY LOAN at your nearby
					   CASHLAND location!  Although we cannot provide you a loan over the Internet 
					   at this time, you can receive your loan TODAY in CASH at the Cashland store 
					   located at " . $this->address . "*</p>\n";
		$content.= "<p>Please bring the following items</p>\n";
		$content.= "<ul>
						<li>Driver&apos;s License or State ID</li>
						<li>Most recent pay stub</li>
						<li>Checkbook</li>
						<li>This document</li>
					</ul>";
		$content.= "<p>You may contact this location by phone at " . $this->phone . ".\n ";
		$content.= "<b><a href='http://maps.google.com/maps?q=" . urlencode($this->address);
		$content.= "' target='_blank'>Map</a></b><br>\n";
		$content.= "Control No. " . $_SESSION['application_id'] . "</p>\n";
		$content.= "<p><small>*Loans offered by Cashland to qualified individuals.  Complete 
					disclosure of interest rate, fees and payment terms provided with each loan.  
					Maximum loan amounts vary by state.  Approval pending verification of information 
					provided on your application.  Offer not valid for customers who have an outstanding 
					loan with any unit of Cashland Financial Services, Inc.  Kentucky License Number: 7341; 
					Ohio License Numbers: 700274, 750218</small></p>";
        
		return $content;
	}
}
?>
