<?php
    class CF{
        protected $sdk_version = '1.3';
        protected $api_version = '3.0';
	public $error = array();
        private $api_timeout = '30';
        private $app_id = 'F2';
        private $host='zzz.checkfront.com';
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
    require "vendor/autoload.php";
	//PUT YOUR TWILLIO ACCESS.
    $AccountSid = "zzz";
    $AuthToken = "zzz";
    $client = new Services_Twilio($AccountSid, $AuthToken);

    require_once('dbconfig.php');
    date_default_timezone_set('America/New_York');
	//PUT YOUR CHECKFRONT KEY and SECRET.
    $config=array(
    	'api_key'=>'zzz',
        'api_secret'=>'zzz'
    );
    $cf=new CF($config);
    exec('php front.php');
    $con=mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die("Error " . mysqli_error($con));
    $sqlCheck='SELECT * FROM messages WHERE is_active=1; ';
    $result=mysqli_query($con, $sqlCheck);
    while($res=mysqli_fetch_array($result, MYSQLI_ASSOC)){
		$body=strtolower(trim(trim($res['body'], '.')));
		if(strpos($body, 'cancel')===0){
			$explodedBody=explode(" ", $body);
			$date=isset($explodedBody[1])?$explodedBody[1]:'';
			if($date!=''){
				$explodedDate=explode(',', $date);
				$countExplodedDate=count($explodedDate);
				$firstDate=$explodedDate[0];
				$lastDate=$explodedDate[$countExplodedDate-1];
			
				/*SEQUENTIAL DATE CHECK
				$moreOfOneDayGap=0;		
				if($firstDate!=$lastDate){
					for($i=0; i<$countExplodedDate; i++){

					}
				}*/	
				$today=date('d');
				if($firstDate==$today){
					$sentTime=$res['date_sent'];
					$timestamp = strtotime($sentTime);
					$localTime = date('H:i',$timestamp);
					$hm=explode(':', $localTime);
					$larger=0;
					if($hm[0]>6){
                				$larger=1;
					}else if($hm[0]=6){
                				if($hm[1]>0){
                        				$larger=1;
                				}
					}
					if($larger>0){
						//send sms
						$sendMesage="You've missed 6 PM Cancellation Window.";
						$sendMessage="Your usage for ".$month." is ".$usage." nights";
                				try{
                        				$message = $client->account->messages->create(array(
                                				=>"xxx-xxx-xxx", /*PUT YOUR TWILLIO NUMBER.*/
                                				"To"=>$res['num_from'],
                                				"Body" =>$sendMessage
                        				));
                        				$messageUpdate="UPDATE messages SET sent_sid='".$message->sid."', is_active=0, modified=now()".
                                			" WHERE ".
                                			"id=".$res['id'].";";
                        				$messageResult=mysqli_query($con, $messageUpdate);
                        				if($messageResult){
                                				echo "Message=".$sendMessage."  and SID=".$message->sid;
                        				}else{
                                				echo "Message not sent";
                        				}
                				}catch (Services_Twilio_RestException $e) {
                         				$error = $e->getMessage();
                        				echo $error;
                				}finally{
								exit();
						}

						//send
					}
				}
				if($lastDate-$today<0){
					$month=date('m')+1;
				}else{
					$month=date('m');
				}
				$checkingDate=date('Y').'-'.$month.'-'.$lastDate;
				echo $checkingDate;
			}else{
				//send sms
				$sendMessage="Please send SMS in the right format. USAGE: Cancel dd[,...,dd]. ";
                		try{
                        		$message = $client->account->messages->create(array(
                                		=>"xxx-xxx-xxx", /*PUT YOUR TWILLIO NUMBER.*/
                                		"To"=>$res['num_from'],
                                		"Body" =>$sendMessage
                        		));
                        		$messageUpdate="UPDATE messages SET sent_sid='".$message->sid."', is_active=0, modified=now()".
                                		" WHERE ".
                                		"id=".$res['id'].";";
                        		$messageResult=mysqli_query($con, $messageUpdate);
                        		if($messageResult){
                                		echo "Message=".$sendMessage."  and SID=".$message->sid;
                        		}else{
                                		echo "Message not sent";
                        		}
                		}catch (Services_Twilio_RestException $e) {
                         		$error = $e->getMessage();
                        		echo $error;
                		}finally{
					exit();
				}

				//send
			}
			/*truncate customers table and call front.php when count() returns 0*/
			 $customerCountSQL="SELECT COUNT(*) AS TOTAL FROM customers WHERE replace(replace(replace(replace(customer_phone, ' ', ''), '-', ''), '(', ''), ')', '')=substring('".$res['num_from']."', 3);";
                        $customerTotalResultset=mysqli_query($con, $customerCountSQL);
			$total=mysqli_fetch_array($customerTotalResultset, MYSQLI_ASSOC);
			if($total['TOTAL']==0){
				$truncateCustomerSQL="TRUNCATE customers;";
				mysqli_query($con, $customerCountSQL);
				exec('php front.php');
			}
			

			$sqlCheck="SELECT * FROM customers WHERE replace(replace(replace(replace(customer_phone, ' ', ''), '-', ''), '(', ''), ')', '')=substring('".$res['num_from']."', 3);";
			$customerResultset=mysqli_query($con, $sqlCheck);
			$found=0;
			while($customer=mysqli_fetch_array($customerResultset, MYSQLI_ASSOC)){
				//$parameters=array();
				//$bookingCreationParameters=array();
				$response=$cf->call('booking/form');
                		$fields=$response['booking_form_ui'];
				//var_dump($fields);
                		foreach($fields as $k=>$v){
					//echo $k;
					if(($k=='msg')||($k=='errors')||($k=='mode')||($k=='_cnf')){
						continue;
					}
					//echo $k;
					//echo $customer[$k];
					$bookingCreationParameters[$k]=$customer[$k];
					//var_dump($bookingCreationParameters);
				}
				if($found==1){
					break;
				}
				$sqlCheck="SELECT * FROM bookings WHERE customer_id=".$customer['customer_id'].";";
				//please add status 
				//echo $sqlCheck;
				$bookingResultset=mysqli_query($con, $sqlCheck);
				while($booking=mysqli_fetch_array($bookingResultset, MYSQLI_ASSOC)){
					$startDate=DateTime::createFromFormat('Y-m-d', $booking['booking_from_date']);
					$endDate=DateTime::createFromFormat('Y-m-d', $booking['booking_to_date']);
					$interval=new DateInterval('P'.($countExplodedDate-1).'D');
					$lastDate=$startDate->add($interval);
					if($lastDate->format('Y-m-d')==$checkingDate){
						//$responseStatus=$cf->call('booking/'.$booking['code'].'/status', 'STOP');
						//var_dump($responseStatus);
						$response=$cf->call('booking/'.$booking['code']);
						$slipCount=0;
						foreach($response['booking']['items'] as $item){
							if($booking['num_days']>$countExplodedDate){
								$interval = new DateInterval('P1D');
								$creationDate=$lastDate->add($interval);
								if(($booking['num_days']-$countExplodedDate)==1){
									$param='?date='.$creationDate->format('Y-m-d');
								}else{
									$param='?start_date='.$creationDate->format('Y-m-d').'&end_date='.$endDate->format('Y-m-d');
								}
							}
							$itemResponse=$cf->call('item/'.$item['id'].$param);
							if($slipCount==0){
								$paramCreate='?slip[]='.$itemResponse['item']['rate']['slip'];
							}else{
								$paramCreate.='&slip[]='.$itemResponse['item']['rate']['slip'];
							}
							$slipCount++;
						}
						$sessionResponse = $cf->call('booking/session'.$paramCreate);
						$sessionID=$sessionResponse['booking']['session']['id'];
						//var_dump(http_build_query($bookingCreationParameters));				
						$bookingCreationResponse=$cf->call('booking/create', array('session_id'=>$sessionID, 'form'=>$bookingCreationParameters));
						var_dump($bookingCreationResponse);
						
						$sendMessage="Your cancellation request is processed.";
                				try{
                        				$message = $client->account->messages->create(array(
                                				"From" =>"xxx-xxx-xxx", /*PUT YOUR TWILLIO NUMBER.*/
                                				"To"=>$res['num_from'],
                                				"Body" =>$sendMessage
                        				));
                        				$messageUpdate="UPDATE messages SET sent_sid='".$message->sid."', is_active=0, modified=now()".
                                				" WHERE ".
                                				"id=".$res['id'].";";
                        				$messageResult=mysqli_query($con, $messageUpdate);
                        				if($messageResult){
                                				echo "Message=".$sendMessage." and SID=".$message->sid;
                        				}else{
                                				echo "Message not sent";
                        				}
                				}catch (Services_Twilio_RestException $e) {
                         				$error = $e->getMessage();
                        				echo $error;
                				}finally{
							exit();
						}
						$found=1;
						break;
					}
				}
			}
			if($found==0){
				//send sms
				$sendMessage="Your cancellation request is out of range.";
                                                try{
                                                        $message = $client->account->messages->create(array(
                                                                "From" =>=>"xxx-xxx-xxx", /*PUT YOUR TWILLIO NUMBER.*/
                                                                "To"=>$res['num_from'],
                                                                "Body" =>$sendMessage
                                                        ));
                                                        $messageUpdate="UPDATE messages SET sent_sid='".$message->sid."', is_active=0, modified=now()".
                                                                " WHERE ".
                                                                "id=".$res['id'].";";
                                                        $messageResult=mysqli_query($con, $messageUpdate);
                                                        if($messageResult){
                                                                echo "Message=".$sendMessage." and SID=".$message->sid;
                                                        }else{
                                                                echo "Message not sent";
                                                        }
                                                }catch (Services_Twilio_RestException $e) {
                                                        $error = $e->getMessage();
                                                        echo $error;
                                                }finally{
                                                        exit();
                                                }
				
				//send
			}
		}
		if(strpos($body, 'usage')===0){
			//usage check.
			$explodedUsage=explode(' ', $body);
			if(isset($explodedUsage[1])&& !empty($explodedUsage[1])){
				$month=strtoupper($explodedUsage[1]);
			}else{
				$month=date('M');
			}
		$usage=0;
	 	$usageSQL="select booking_from_date, booking_to_date  from bookings where customer_id in(SELECT distinct customer_id FROM customers, messages WHERE replace(replace(replace(replace(customer_phone, ' ', ''), '-', ''), '(', ''), ')', '')=substring('".$res['num_from']."', 3));";
		$usageResultset=mysqli_query($con, $usageSQL);
		while($ua=mysqli_fetch_array($usageResultset, MYSQLI_ASSOC)){
			$bf=$ua['booking_from_date'];
			$bt=$ua['booking_to_date'];
			if($bt==''){
				$usage=1;
			}else
			if($bf!='' && $bt!=''){
				if((strtoupper(date('M', $bf))!=$month) && (strtoupper(date('M', $bt))!=$month)){
					$usage+=0;
				}
				if((strtoupper(date('M', $bf))==$month) && (strtoupper(date('M', $bt))==$month)){
                                        $usage+=(date('d', $bt)-date('d', $bf)+1);
                                }
				if((strtoupper(date('M', $bf))==$month) && (strtoupper(date('M', $bt))!=$month)){
					if(date('m', $bf)==12){
						$m=1;
					}else{
						$m=date('m', $bf);
					}
                                        $end = date('Y-m-d',mktime(1,1,1,$m,0,date('Y')));
					$usage+=(date('d', $end)-date('d', $bf)+1);
                                }
				if((strtoupper(date('M', $bf))!=$month) && (strtoupper(date('M', $bt))==$month)){
                                        $m=date('m', $bt);
					$start = date('Y-m-d',mktime(1,1,1,$m,1,date('Y')));
					$usage+=(date('d', $bt)-date('d', $start)+1);
                                }

			}

		}	
		$sendMessage="Your usage for ".$month." is ".$usage." nights";
		try{
            		$message = $client->account->messages->create(array(
				=>"xxx-xxx-xxx", /*PUT YOUR TWILLIO NUMBER.*/
				"To"=>$res['num_from'],
				"Body" =>$sendMessage
             		));
			$messageUpdate="UPDATE messages SET sent_sid='".$message->sid."', is_active=0, modified=now()".
				" WHERE ".
				"id=".$res['id'].";";
			$messageResult=mysqli_query($con, $messageUpdate);
			if($messageResult){
				echo "Message=".$sendMessage."  and SID=".$message->sid;
			}else{
				echo "Message not sent";
			}
        	}catch (Services_Twilio_RestException $e) {
           		 $error = $e->getMessage();
            		echo $error;
        	}finally{

			exit();
		}
	}
}
?>

