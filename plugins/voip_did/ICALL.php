<?php

/** iCall AgileVoice VoIP DID Pool Plugin 
* @example include_once(PATH_PLUGINS.'voip_did/'.$plugin_name.'.php');
* @example eval('$did_plugin = new plg_voip_did_'.$plugin_name.';');
* @example $did_plugin->id = $voip_did_plugin_id;
* @example $did_plugin->did = $did;
* @example $did_plugin->country = $country;
* @example $did_plugin->method();
*/
class plgn_voip_did_ICALL
{
	var $id;                    // voip_did_plugin_id from database
	var $did;					// full E164 DID
	var $country;				// country calling code
	var $release_minutes;		// The configured release minutes for reserved DIDs
	var $plugin;				// The plugin name
	var $reserve=24;			// Number of hours reserved 
	var $name='ICALL';  		// Plugin name
	var $avail_countries;		// Available countries array 

	/** Get the plugin settings from the database */
	function config() {
		$db =& DB();
		$rs = & $db->Execute(sqlSelect($db,"voip_did_plugin","*","id = $this->id"));
		$this->release_minutes = $rs->fields['release_minutes'];
		$this->avail_countries = $rs->fields['avail_countries'];  
		$this->plugin_data = unserialize($rs->fields['plugin_data']);  
		$this->username = $this->plugin_data['username'];
		$this->apikey = $this->plugin_data['apikey'];
		$this->testmode = $this->plugin_data['testmode'];
	}
	
	/** 
	* Once a DID has been purchased and payment has been received from the customer, this
	* function then asks the DID provider to actually provision the DID to us.
	*/	
	function purchase() {
		$this->config();
		
		# Include the voip class
		include_once(PATH_MODULES.'voip/voip.inc.php');
		$v = new voip;

		$db =& DB();
		$cc = ""; $npa = ""; $nxx = ""; $e164 = "";
		if ($v->e164($this->did, $e164, $cc, $npa, $nxx)) {
			$station = substr($e164, 8);
			$result = $this->process('purchaseDID', array('number' => $npa.$nxx.$station));
		}

		if($result == true)
		{
			// connect to iCall API and purchase this number
			require_once(PATH_MODULES."voip_did_plugin/voip_did_plugin.inc.php");
			$plugin = new voip_did_plugin;
			$plugin->account_id = $this->account_id;
			$result = $plugin->purchase($this->id, $this->did);
		}
		return $result;
	}
	
	/** Reserve a DID
	*/
    function reserve() {
		$this->config();
		
		require_once(PATH_MODULES."voip_did_plugin/voip_did_plugin.inc.php");
		$plugin = new voip_did_plugin;
		
		return $plugin->reserve($this->id, $this->did);
    }
    
    /** Release a reserved DID
    */
    function release() {
    	# DIDx doesn't support an API method to cancel a DID
    	# So, I guess the number remains ours - just free it from the customer.
		require_once(PATH_MODULES."voip_did_plugin/voip_did_plugin.inc.php");
		$plugin = new voip_did_plugin;
		
		return $plugin->release($this->id, $this->did);    	
    }
     
    /** Task to refresh available dids cart items
    */
    function refresh() {
 	}
	
	function retDIDs($npa) {
		$this->config();
		$arg['npa'] = $npa;
		$retdids = array();
		$x = 0;
		$dids = $this->process('checkNPA', $arg);
		if (!isset($dids->error['msg'])) {		
			foreach($dids->numbers->number as $did)
			{
				if((string) $did['type'] == 'tier1') {
					$retdids[] = (string) $did;
					$x++;
				}
				if($x == 5) { break; }
			}
		}
		return $retdids;
	}
	function getNPAs()
	{
		$npastates = array(
			'201' => 'NJ', '202' => 'DC', '203' => 'CT', '204' => 'MB', '205' => 'AL', '206' => 'WA',
			'207' => 'ME', '208' => 'ID', '209' => 'CA', '210' => 'TX', '212' => 'NY', '213' => 'CA',
			'214' => 'TX', '215' => 'PA', '216' => 'OH', '217' => 'IL', '218' => 'MN', '219' => 'IN',
			'224' => 'IL', '225' => 'LA', '226' => 'CN', '228' => 'MS', '229' => 'GA', '231' => 'MI',
			'234' => 'OH', '239' => 'FL', '240' => 'MD', '246' => 'BD', '248' => 'MI', '250' => 'BC',
			'251' => 'AL', '252' => 'NC', '253' => 'WA', '254' => 'TX', '256' => 'AL', '260' => 'IN',
			'262' => 'WI', '264' => 'AI', '267' => 'PA', '268' => 'AN', '269' => 'MI', '270' => 'KY',
			'276' => 'VA', '281' => 'TX', '284' => 'BV', '289' => 'ON', '301' => 'MD', '302' => 'DE',
			'303' => 'CO', '304' => 'WV', '305' => 'FL', '306' => 'SK', '307' => 'WY', '308' => 'NE',
			'309' => 'IL', '310' => 'CA', '312' => 'IL', '313' => 'MI', '314' => 'MO', '315' => 'NY',
			'316' => 'KS', '317' => 'IN', '318' => 'LA', '319' => 'IA', '320' => 'MN', '321' => 'FL',
			'323' => 'CA', '325' => 'TX', '330' => 'OH', '331' => 'IL', '334' => 'AL', '336' => 'NC',
			'337' => 'LA', '339' => 'MA', '340' => 'VI', '345' => 'CQ', '347' => 'NY', '351' => 'MA',
			'352' => 'FL', '360' => 'WA', '361' => 'TX', '386' => 'FL', '401' => 'RI', '402' => 'NE',
			'403' => 'AB', '404' => 'GA', '405' => 'OK', '406' => 'MT', '407' => 'FL', '408' => 'CA',
			'409' => 'TX', '410' => 'MD', '412' => 'PA', '413' => 'MA', '414' => 'WI', '415' => 'CA',
			'416' => 'ON', '417' => 'MO', '418' => 'QC', '419' => 'OH', '423' => 'TN', '424' => 'CA',
			'425' => 'WA', '430' => 'TX', '432' => 'TX', '434' => 'VA', '435' => 'UT', '438' => 'CN',
			'440' => 'OH', '441' => 'BM', '443' => 'MD', '445' => 'PA', '450' => 'QC', '469' => 'TX',
			'470' => 'GA', '473' => 'GN', '475' => 'CT', '478' => 'GA', '479' => 'AR', '480' => 'AZ',
			'484' => 'PA', '501' => 'AR', '502' => 'KY', '503' => 'OR', '504' => 'LA', '505' => 'NM',
			'506' => 'NB', '507' => 'MN', '508' => 'MA', '509' => 'WA', '510' => 'CA', '512' => 'TX',
			'513' => 'OH', '514' => 'QC', '515' => 'IA', '516' => 'NY', '517' => 'MI', '518' => 'NY',
			'519' => 'ON', '520' => 'AZ', '530' => 'CA', '540' => 'VA', '541' => 'OR', '551' => 'NJ',
			'557' => 'MO', '559' => 'CA', '561' => 'FL', '562' => 'CA', '563' => 'IA', '564' => 'WA',
			'567' => 'OH', '570' => 'PA', '571' => 'VA', '573' => 'MO', '574' => 'IN', '575' => 'NM',
			'580' => 'OK', '585' => 'NY', '586' => 'MI', '601' => 'MS', '602' => 'AZ', '603' => 'NH',
			'604' => 'BC', '605' => 'SD', '606' => 'KY', '607' => 'NY', '608' => 'WI', '609' => 'NJ',
			'610' => 'PA', '612' => 'MN', '613' => 'ON', '614' => 'OH', '615' => 'TN', '616' => 'MI',
			'617' => 'MA', '618' => 'IL', '619' => 'CA', '620' => 'KS', '623' => 'AZ', '626' => 'CA',
			'630' => 'IL', '631' => 'NY', '636' => 'MO', '641' => 'IA', '646' => 'NY', '647' => 'ON',
			'649' => 'TC', '650' => 'CA', '651' => 'MN', '657' => 'CA', '660' => 'MO', '661' => 'CA',
			'662' => 'MS', '664' => 'RT', '670' => 'NN', '671' => 'GU', '678' => 'GA', '682' => 'TX',
			'684' => 'AS', '689' => 'FL', '701' => 'ND', '702' => 'NV', '703' => 'VA', '704' => 'NC',
			'705' => 'ON', '706' => 'GA', '707' => 'CA', '708' => 'IL', '709' => 'NF', '712' => 'IA',
			'713' => 'TX', '714' => 'CA', '715' => 'WI', '716' => 'NY', '717' => 'PA', '718' => 'NY',
			'719' => 'CO', '720' => 'CO', '724' => 'PA', '727' => 'FL', '731' => 'TN', '732' => 'NJ',
			'734' => 'MI', '740' => 'OH', '754' => 'FL', '757' => 'VA', '758' => 'SA', '760' => 'CA',
			'762' => 'GA', '763' => 'MN', '765' => 'IN', '767' => 'DM', '769' => 'MS', '770' => 'GA',
			'772' => 'FL', '773' => 'IL', '774' => 'MA', '775' => 'NV', '778' => 'BC', '779' => 'IL',
			'780' => 'AB', '781' => 'MA', '784' => 'ZF', '785' => 'KS', '786' => 'FL', '787' => 'PR',
			'801' => 'UT', '802' => 'VT', '803' => 'SC', '804' => 'VA', '805' => 'CA', '806' => 'TX',
			'807' => 'ON', '808' => 'HI', '809' => 'DR', '810' => 'MI', '812' => 'IN', '813' => 'FL',
			'814' => 'PA', '815' => 'IL', '816' => 'MO', '817' => 'TX', '818' => 'CA', '819' => 'QC',
			'828' => 'NC', '830' => 'TX', '831' => 'CA', '832' => 'TX', '835' => 'PA', '843' => 'SC',
			'845' => 'NY', '847' => 'IL', '848' => 'NJ', '850' => 'FL', '856' => 'NJ', '857' => 'MA',
			'858' => 'CA', '859' => 'KY', '860' => 'CT', '862' => 'NJ', '863' => 'FL', '864' => 'SC',
			'865' => 'TN', '867' => 'NT', '868' => 'TR', '869' => 'KA', '870' => 'AR', '876' => 'JM',
			'878' => 'PA', '901' => 'TN', '902' => 'NS', '903' => 'TX', '904' => 'FL', '905' => 'ON',
			'906' => 'MI', '907' => 'AK', '908' => 'NJ', '909' => 'CA', '910' => 'NC', '912' => 'GA',
			'913' => 'KS', '914' => 'NY', '915' => 'TX', '916' => 'CA', '917' => 'NY', '918' => 'OK',
			'919' => 'NC', '920' => 'WI', '925' => 'CA', '928' => 'AZ', '931' => 'TN', '936' => 'TX',
			'937' => 'OH', '939' => 'PR', '940' => 'TX', '941' => 'FL', '947' => 'MI', '949' => 'CA',
			'951' => 'CA', '952' => 'MN', '954' => 'FL', '956' => 'TX', '959' => 'CT', '970' => 'CO',
			'971' => 'OR', '972' => 'TX', '973' => 'NJ', '978' => 'MA', '979' => 'TX', '980' => 'NC',
			'984' => 'NC', '985' => 'LA', '989' => 'MI');
			
		$this->config();
		$retnpas = array();
		$npas = $this->process('getAvailNPA');

		foreach($npas->numbers->npa as $npa)
		{
			$areacode = new StdClass;
			if($npa['tier1'] == (string) 'true')
			{
				$areacode->npa = (string) $npa;
				$areacode->state = $npastates[(string) $npa];
				$retnpas[] = $areacode;
			}
		}
		return $retnpas;
	}
	function pullDID($e164, $cc, $npa, $nxx, $station)
	{
		$this->config();
		$this->did = $e164;
		$res = $this->process('reserveDID', array('number' => $npa.$nxx.$station));
		var_dump($res);
		
		if (!isset($dids->error['msg'])) {
			// no error, check if the number exists in the database
			$db = &DB();

			$rs = $db->Execute(sqlSelect($db, "voip_pool", "*", "country_code = '" . $cc . "' AND npa = '" . $npa . "' AND nxx = '" . $nxx . "' AND station = '" . $station . "'"));
			// number doesn't exist - add the number to the database
			if($rs && $rs->RecordCount() == 0) {

				$id = $db->GenID(AGILE_DB_PREFIX . "" . 'pool_id');
				$fields = array('id' => $id, 'site_id' => 1, 'country_code' => $cc, 'npa' => $npa, 'nxx' => $nxx, 'station' => $station, 'voip_did_plugin_id' => $this->id, 'areacode' => 1);
				$db->Execute(sqlInsert($db,"voip_pool",$fields));
			}
		}
	}
	function process($method, $args = '')
	{

		$url = "http://carriers.icall.com/api/?key=$this->apikey&username=$this->username&method=service.$method";
		if($args != '') {
			foreach($args as $key => $value)
			{
				$url .= "&$key=$value";
			}
		}
		if($this->testmode && ($method == 'orderDID' || $method == 'reserveDID' || $method == 'removeDID'))
		{
			$url .= "&testing=true";
		}
		
		$session = curl_init($url);
		curl_setopt($session, CURLOPT_HEADER, false);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

		$res = curl_exec($session);
		// var_dump($res);
		curl_close($session);
		$res = simplexml_load_string($res);
		return $res;
	}

}
?>
