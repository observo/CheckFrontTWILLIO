<?php
	class CF{
		protected $sdk_version = '1.3';
		protected $api_version = '3.0';
		public $error = array();
		private $api_timeout = '30';
		private $app_id = 'F2'; 
		private $host='zzz.checkfront.com'; //PUT YOUR NAME.
		private $auth_type = 'token';
		private $api_key;
		private $api_secret;
		function __construct($config=array()) {
			if(isset($config['host'])) $this->host = $config['host'];
			$this->oauth_url = "https://{$this->host}/oauth";
			$this->api_url = "https://{$this->host}/api/{$this->api_version}";
			if(isset($config['app_id'])) $this->app_id = $config['app_id'];
				$this->api_key = isset($config['api_key']) ? $config['api_key'] : '';
				$this->api_secret = isset($config['api_secret']) ? $config['api_secret'] : '';
		}
		
		function call($url, $data=array()){
			$url=$this->api_url.'/'.$url;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "CF {$this->sdk_version} ({$this->app_id})");
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $this->api_key . ':' . $this->api_secret);
			$headers = array('Accept: application/json');
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->api_timeout);
			if($data){
				curl_setopt($ch, CURLOPT_POST, true);
				if(is_array($data)){
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
				}else{
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				}
			}else{
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
			if($response = curl_exec($ch)){
				$response = json_decode($response,true);
				if($error = json_last_error()){
					$this->error = array('json'=>$error);
				}else{
					return $response;
				}
			}else{
				$this->error = array('curl'=>curl_error($ch));
			}
		}
	}
	require_once('dbconfig.php');
	$con=mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die("Error " . mysqli_error($con));
	//PUT YOUR API KEY and SECRET
	$config=array(
		'api_key'=>'zzz',
		'api_secret'=>'zzz'
	);
	
	$cf=new CF($config);
	$response=$cf->call('ping');
	if($response['name']=='Altitude Pads' && $response['request']['status']=='OK'){
		$timezone=$response['pong']['timezone'];
		date_default_timezone_set($timezone);
		$yesterday=date('Y-m-d', strtotime("yesterday")); 
	}	
	$bookingCheck='SELECT COUNT(*) AS TOTAL FROM bookings';
	$result=mysqli_query($con, $bookingCheck);
	$result=mysqli_fetch_array($result, MYSQLI_ASSOC);
	$res=$result['TOTAL'];
	if($res>0){
		//today's insert
		$response=$cf->call('booking?start_date='.$yesterday);
		$pages=$response['request']['pages'];
		$pageCount=1;
		do{
			if($response['request']['status']=='OK'){
				$processedRecords=0;
				$records=$response['request']['records'];
				foreach($response['booking/index'] as $booking){
					$booking_id=isset($booking['booking_id'])?$booking['booking_id']:0;
					$code=isset($booking['code'])?$booking['code']:'';
					$status_id=isset($booking['status_id'])?$booking['status_id']:'';
					$status_name=isset($booking['status_name'])?$booking['status_name']:'';
					$created_date=isset($booking['created_date'])?$booking['created_date']:'';
					$total=isset($booking['total'])?$booking['total']:0.0;
					$tax_total=isset($booking['tax_total'])?$booking['tax_total']:0.0;
					$paid_total=isset($booking['paid_total'])?$booking['paid_total']:0.0;
					$customer_id=isset($booking['customer_id'])?$booking['customer_id']:0;
					$customer_name=isset($booking['customer_name'])?$booking['customer_name']:'';
					$customer_email=isset($booking['customer_email'])?$booking['customer_email']:'';
					$summary=isset($booking['summary'])?$booking['summary']:'';
					$date_desc=isset($booking['date_desc'])?$booking['date_desc']:'';
					$tid=isset($booking['tid'])?$booking['tid']:'';
					$token=isset($booking['token'])?$booking['token']:'';
					if(isset($date_desc) && !empty($date_desc) && $date_desc!=''){
						$dateRange=explode('-', $date_desc);
						$booking_from_date=(isset($dateRange[0]) && !empty($dateRange[0]) && $dateRange[0]!='')?date('Y-m-d', strtotime(trim($dateRange[0]))):null;
						$booking_to_date=(isset($dateRange[1]) && !empty($dateRange[1]) && $dateRange[1]!='')?date('Y-m-d', strtotime(trim($dateRange[1]))):null;
						if(isset($booking_from_date) && isset($booking_to_date)){
							$initial=DateTime::createFromFormat('Y-m-d', $booking_from_date);
							$final=DateTime::createFromFormat('Y-m-d', $booking_to_date);
							$num_days=abs($final->diff($initial)->days+1);
						}else{
							$num_days=1;
						}
					}else{
						$booking_from_date=null;
						$booking_to_date=null;
					}
					$sqlCheck='SELECT COUNT(*) AS TOTAL '.
						'FROM bookings '.
						'WHERE '.
						"booking_id='".$booking_id."';";
						
					$result=mysqli_query($con, $sqlCheck);
					$result=mysqli_fetch_array($result, MYSQLI_ASSOC);
					$res=$result['TOTAL'];
					if($res>0){
						//modify
						$sqlUpdate="UPDATE bookings SET ".
						"code='".mysqli_real_escape_string($con, $code)."', ".
						"status_id='".mysqli_real_escape_string($con, $status_id)."', ".
						"status_name='".mysqli_real_escape_string($con, $status_name)."', ".
						"created_date='".mysqli_real_escape_string($con, $created_date)."', ".
						"total='".mysqli_real_escape_string($con, $total)."', ".
						"tax_total='".mysqli_real_escape_string($con, $tax_total)."', ".
						"paid_total='".mysqli_real_escape_string($con, $paid_total)."', ".
						"customer_id='".mysqli_real_escape_string($con, $customer_id)."', ".
						"customer_name='".mysqli_real_escape_string($con, $customer_name)."', ".
						"customer_email='".mysqli_real_escape_string($con, $customer_email)."', ".
						"summary='".mysqli_real_escape_string($con, $summary)."', ".
						"date_desc='".mysqli_real_escape_string($con, $date_desc)."', ".
						"tid='".mysqli_real_escape_string($con, $tid)."', ".
						"token='".mysqli_real_escape_string($con, $token)."', ".
						"booking_from_date='".mysqli_real_escape_string($con, $booking_from_date)."', ".
 						"booking_to_date='".mysqli_real_escape_string($con, $booking_to_date)."', ".
 						"num_days='".mysqli_real_escape_string($con, $num_days)."', ".
						"modified=now() WHERE ".
						"booking_id='".$booking_id."';";
						echo "Record Exists(booking_id)=($booking_id).".PHP_EOL;
						//echo $sqlUpdate;
						$result=mysqli_query($con, $sqlUpdate);
						if($result){
							$processedRecords++;
						}
					}else{
						//insert
						$sqlInsert='INSERT INTO bookings(
							booking_id,
							code,
							status_id,
							status_name,
							created_date,
							total,
							tax_total,
							paid_total,
							customer_id,
							customer_name,
							customer_email,
							summary,
							date_desc,
							tid,
							token,
							booking_from_date,
							booking_to_date,
							num_days,
							created
							)VALUES('.
							"'".mysqli_real_escape_string($con, $booking_id)."',".
							"'".mysqli_real_escape_string($con, $code)."',".
							"'".mysqli_real_escape_string($con, $status_id)."',".
							"'".mysqli_real_escape_string($con, $status_name)."',".
							"'".mysqli_real_escape_string($con, $created_date)."',".
							"'".mysqli_real_escape_string($con, $total)."',".
							"'".mysqli_real_escape_string($con, $tax_total)."',".
							"'".mysqli_real_escape_string($con, $paid_total)."',".
							"'".mysqli_real_escape_string($con, $customer_id)."',".
							"'".mysqli_real_escape_string($con, $customer_name)."',".
							"'".mysqli_real_escape_string($con, $customer_email)."',".
							"'".mysqli_real_escape_string($con, $summary)."',".
							"'".mysqli_real_escape_string($con, $date_desc)."',".
							"'".mysqli_real_escape_string($con, $tid)."',".
							"'".mysqli_real_escape_string($con, $token)."',".
							"'".mysqli_real_escape_string($con, $booking_from_date)."',".
							"'".mysqli_real_escape_string($con, $booking_to_date)."',".
							"'".mysqli_real_escape_string($con, $num_days)."',".
							"now());";
						//echo $sqlInsert.PHP_EOL;
						$result=mysqli_query($con, $sqlInsert);
						if($result){
							$processedRecords++;
						}
					}
				}
				echo "(PAGE, RECORDS, PROCESSED)=(".$pageCount.", ".$records.", ".$processedRecords.").".PHP_EOL;
				$pageCount++;
				$response=$cf->call('booking?start_date='.$yesterday.'&page='.$pageCount);
			}
		}while($pageCount<=$pages);
	}else{
		//initial insert
		$response=$cf->call('booking');
		$pages=$response['request']['pages'];
		$pageCount=1;
		do{
			if($response['request']['status']=='OK'){
				$processedRecords=0;
				$records=$response['request']['records'];
				//if($response['request']['status']){
				foreach($response['booking/index'] as $booking){
                    			$booking_id=isset($booking['booking_id'])?$booking['booking_id']:0;
					$code=isset($booking['code'])?$booking['code']:'';
					$status_id=isset($booking['status_id'])?$booking['status_id']:'';
					$status_name=isset($booking['status_name'])?$booking['status_name']:'';
					$created_date=isset($booking['created_date'])?$booking['created_date']:'';
					$total=isset($booking['total'])?$booking['total']:0.0;
					$tax_total=isset($booking['tax_total'])?$booking['tax_total']:0.0;
					$paid_total=isset($booking['paid_total'])?$booking['paid_total']:0.0;
					$customer_id=isset($booking['customer_id'])?$booking['customer_id']:0;
					$customer_name=isset($booking['customer_name'])?$booking['customer_name']:'';
					$customer_email=isset($booking['customer_email'])?$booking['customer_email']:'';
					$summary=isset($booking['summary'])?$booking['summary']:'';
					$date_desc=isset($booking['date_desc'])?$booking['date_desc']:'';
					$tid=isset($booking['tid'])?$booking['tid']:'';
					$token=isset($booking['token'])?$booking['token']:'';
					if(isset($date_desc) && !empty($date_desc) && $date_desc!=''){
						$dateRange=explode('-', $date_desc);
						$booking_from_date=(isset($dateRange[0]) && !empty($dateRange[0]) && $dateRange[0]!='')?date('Y-m-d', strtotime(trim($dateRange[0]))):null;
						$booking_to_date=(isset($dateRange[1]) && !empty($dateRange[1]) && $dateRange[1]!='')?date('Y-m-d', strtotime(trim($dateRange[1]))):null;
						 if(isset($booking_from_date) && isset($booking_to_date)){
                                                        $initial=DateTime::createFromFormat('Y-m-d', $booking_from_date);
                                                        $final=DateTime::createFromFormat('Y-m-d', $booking_to_date);
                                                        $num_days=abs($final->diff($initial)->days+1);
                                                }else{
							$num_days=1;
						}

					}else{
						$booking_from_date=null;
						$booking_to_date=null;
					}
					$sqlInsert='INSERT INTO bookings(
						booking_id,
						code,
						status_id,
						status_name,
						created_date,
						total,
						tax_total,
						paid_total,
						customer_id,
						customer_name,
						customer_email,
						summary,
						date_desc,
						tid,
						token,
						booking_from_date,
						booking_to_date,
						num_days,
						created
						)VALUES('.
						"'".mysqli_real_escape_string($con, $booking_id)."',".
						"'".mysqli_real_escape_string($con, $code)."',".
						"'".mysqli_real_escape_string($con, $status_id)."',".
						"'".mysqli_real_escape_string($con, $status_name)."',".
						"'".mysqli_real_escape_string($con, $created_date)."',".
						"'".mysqli_real_escape_string($con, $total)."',".
						"'".mysqli_real_escape_string($con, $tax_total)."',".
						"'".mysqli_real_escape_string($con, $paid_total)."',".
						"'".mysqli_real_escape_string($con, $customer_id)."',".
						"'".mysqli_real_escape_string($con, $customer_name)."',".
						"'".mysqli_real_escape_string($con, $customer_email)."',".
						"'".mysqli_real_escape_string($con, $summary)."',".
						"'".mysqli_real_escape_string($con, $date_desc)."',".
						"'".mysqli_real_escape_string($con, $tid)."',".
						"'".mysqli_real_escape_string($con, $token)."',".
						"'".mysqli_real_escape_string($con, $booking_from_date)."',".
						"'".mysqli_real_escape_string($con, $booking_to_date)."',".
						 "'".mysqli_real_escape_string($con, $num_days)."',".
						"now());";
					//echo $sqlInsert.PHP_EOL;
					$result=mysqli_query($con, $sqlInsert);
					if($result){
						$processedRecords++;
					}					
                }
				echo "(PAGE, RECORDS, PROCESSED)=(".$pageCount.", ".$records.", ".$processedRecords.").".PHP_EOL;
				$pageCount++;
				$response=$cf->call('booking?page='.$pageCount);
			}
		}while($pageCount<=$pages);
	}
	$customerCheck='SELECT COUNT(*) AS TOTAL FROM customers';
	$result=mysqli_query($con, $customerCheck);
	$result=mysqli_fetch_array($result, MYSQLI_ASSOC);
	$res=$result['TOTAL'];
	if($res>0){
		//today's insert
	}else{
		//initial insert
		$response=$cf->call('customer');
		$pages=$response['request']['pages'];
		$pageCount=1;
		do{
			if($response['request']['status']=='OK'){
				$processedRecords=0;
				$records=$response['request']['records'];
				foreach($response['customers'] as $customer){
                    			$customer_id=isset($customer['customer_id'])?$customer['customer_id']:0;
					$meta_id=isset($customer['meta_id'])?$customer['meta_id']:'';
					$token=isset($customer['token'])?$customer['token']:'';
					$code=isset($customer['code'])?$customer['code']:'';
					$status_id=isset($customer['status_id'])?$customer['status_id']:'';
					$created_date=isset($customer['created_date'])?$customer['created_date']:null;
					$updated_date=isset($customer['updated_date'])?$customer['updated_date']:null;
					$last_booking_date=isset($customer['last_booking_date'])?$customer['last_booking_date']:null;
					$customer_name=isset($customer['customer_name'])?$customer['customer_name']:'';
					$customer_email=isset($customer['customer_email'])?$customer['customer_email']:'';
					$customer_email_optin=isset($customer['customer_email_optin'])?$customer['customer_email_optin']:'';
					$customer_region=isset($customer['customer_region'])?$customer['customer_region']:'';
					$customer_city=isset($customer['customer_city'])?$customer['customer_city']:'';
					$customer_address=isset($customer['customer_address'])?$customer['customer_address']:'';
					$customer_country=isset($customer['customer_country'])?$customer['customer_country']:'';
					$customer_postal_zip=isset($customer['customer_postal_zip'])?$customer['customer_postal_zip']:'';
					$customer_phone=isset($customer['customer_phone'])?$customer['customer_phone']:'';
					$customer_crm_id=isset($customer['customer_crm_id'])?$customer['customer_crm_id']:'';
					$referer=isset($customer['referer'])?$customer['referer']:'';
					$notes=isset($customer['notes'])?$customer['notes']:'';
					
					$sqlInsert='INSERT INTO customers(
						customer_id,
						meta_id,
						token,
						code,
						status_id,
						created_date,
						updated_date,
						last_booking_date,
						customer_name,
						customer_email,
						customer_email_optin,
						customer_region,
						customer_city,
						customer_address,
						customer_country,
						customer_postal_zip,
						customer_phone,
						customer_crm_id,
						referer,
						notes,
						created
						)VALUES('.
						"'".mysqli_real_escape_string($con, $customer_id)."',".
						"'".mysqli_real_escape_string($con, $meta_id)."',".
						"'".mysqli_real_escape_string($con, $token)."',".
						"'".mysqli_real_escape_string($con, $code)."',".
						"'".mysqli_real_escape_string($con, $status_id)."',".
						"'".mysqli_real_escape_string($con, $created_date)."',".
						"'".mysqli_real_escape_string($con, $updated_date)."',".
						"'".mysqli_real_escape_string($con, $last_booking_date)."',".
						"'".mysqli_real_escape_string($con, $customer_name)."',".
						"'".mysqli_real_escape_string($con, $customer_email)."',".
						"'".mysqli_real_escape_string($con, $customer_email_optin)."',".
						"'".mysqli_real_escape_string($con, $customer_region)."',".
						"'".mysqli_real_escape_string($con, $customer_city)."',".
						"'".mysqli_real_escape_string($con, $customer_address)."',".
						"'".mysqli_real_escape_string($con, $customer_country)."',".
						"'".mysqli_real_escape_string($con, $customer_postal_zip)."',".
						"'".mysqli_real_escape_string($con, $customer_phone)."',".
						"'".mysqli_real_escape_string($con, $customer_crm_id)."',".
						"'".mysqli_real_escape_string($con, $referer)."',".
						"'".mysqli_real_escape_string($con, $notes)."',".
						"now());";
					//echo $sqlInsert.PHP_EOL;
					$result=mysqli_query($con, $sqlInsert);
					if($result){
						$processedRecords++;
					}					
                }
				echo "(PAGE, RECORDS, PROCESSED)=(".$pageCount.", ".$records.", ".$processedRecords.").".PHP_EOL;
				$pageCount++;
				$response=$cf->call('customer?page='.$pageCount);
			}
		}while($pageCount<=$pages);
	}
?>

