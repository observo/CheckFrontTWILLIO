<?php
    require_once('dbconfig.php');
    require "vendor/autoload.php";
    date_default_timezone_set('UTC');
	//PUT YOUR SID and TOKEN.
    $account_sid = 'zzz';
    $auth_token = 'zzz';

    $con=mysqli_connect($dbhost, $dbuser, $dbpass, $dbname) or die("Error " . mysqli_error($con));

    
    $client = new Services_Twilio($account_sid, $auth_token);
    $messages = $client->account->messages->getIterator(0, 50, array(
        //'To' => "xxx-xxx-xxx",// PUT NUMBER TO SEND SMS
        //'DateSent'=>'"'.date('Y-m-d').'"'
        //'DateSent'=>'"'.date('Y-m-d').'"'
    ));
    foreach($messages as $message){
        $sid=isset($message->sid)?$message->sid:'';
        $date_created=isset($message->date_created)?$message->date_created:'';
	$date_updated=isset($message->date_updated)?$message->date_updated:'';
        $date_sent=isset($message->date_sent)?$message->date_sent:'';
        $account_sid=isset($message->account_sid)?$message->account_sid:'';
        $num_to=isset($message->to)?$message->to:'';
        $num_from=isset($message->from)?$message->from:'';
        $body=isset($message->body)?$message->body:'';
        $status=isset($message->status)?$message->status:'';
        $num_segments=isset($message->num_segments)?$message->num_segments:1;
        $num_media=isset($message->num_media)?$message->num_media:0;
        $direction=isset($message->direction)?$message->direction:'';
        $api_version=isset($message->api_version)?$message->api_version:'';
        $price=isset($message->price)?$message->price:0.0;
        $price_unit=isset($message->price_unit)?$message->price_unit:'';
        $error_code=isset($message->error_code)?$message->error_code:0;
        $error_message=isset($message->error_message)?$message->error_message:'';

	$smallcasedBody=strtolower(trim(trim($body, "\"")));
	if((strpos($smallcasedBody, 'cancel')===0)||(strpos($smallcasedBody, 'usage')===0)){
		echo $smallcasedBody;
		//continue;

        $sqlCheck="SELECT COUNT(*) AS TOTAL ".
            "FROM messages ".
            "WHERE ".
            "sid='".$sid."';";
        $result=mysqli_query($con, $sqlCheck);
        $result=mysqli_fetch_array($result, MYSQLI_ASSOC);
        $res=$result['TOTAL'];
        if($res>0){
            $sqlUpdate="UPDATE messages SET ".
                "date_created='".mysqli_real_escape_string($con, $date_created)."', ".
                "date_updated='".mysqli_real_escape_string($con, $date_updated)."', ".
                "date_sent='".mysqli_real_escape_string($con, $date_sent)."', ".
                "account_sid='".mysqli_real_escape_string($con, $account_sid)."', ".
                "num_to='".mysqli_real_escape_string($con, $num_to)."', ".
                "num_from='".mysqli_real_escape_string($con, $num_from)."', ".
                "body='".mysqli_real_escape_string($con, $body)."', ".
                "status='".mysqli_real_escape_string($con, $status)."', ".
                "num_segments=".mysqli_real_escape_string($con, $num_segments).", ".
                "num_media=".mysqli_real_escape_string($con, $num_media).", ".
                "direction='".mysqli_real_escape_string($con, $direction)."', ".
                "api_version='".mysqli_real_escape_string($con, $api_version)."', ".
                "price=".mysqli_real_escape_string($con, $price).", ".
                "price_unit='".mysqli_real_escape_string($con, $price_unit)."', ".
                "error_code=".mysqli_real_escape_string($con, $error_code).", ".
                "error_message='".mysqli_real_escape_string($con, $error_message)."', ".
		"modified=now() WHERE ".
                "sid='".$sid."';";
		//echo $sqlUpdate.PHP_EOL;
		$result=mysqli_query($con, $sqlUpdate);
		if($result){
			echo "(sid)=($sid) Updated Properly.".PHP_EOL;
		}else{
			echo "(sid)=($sid) Not Updated.".PHP_EOL;
		}
	}else{
	     $sqlInsert="INSERT INTO messages(".
                "sid,".
                "date_created,".
                "date_updated,".
                "date_sent,".
                "account_sid,".
                "num_to,".
                "num_from,".
                "body,".
                "status,".
                "num_segments,".
                "num_media,".
                "direction,".
                "api_version,".
                "price,".
                "price_unit,".
                "error_code,".
                "error_message,".
                "created) VALUES(".
                "'".mysqli_real_escape_string($con, $sid)."',".
                "'".mysqli_real_escape_string($con, $date_created)."',".
                "'".mysqli_real_escape_string($con, $date_updated)."',".
                "'".mysqli_real_escape_string($con, $date_sent)."',".
                "'".mysqli_real_escape_string($con, $account_sid)."',".
                "'".mysqli_real_escape_string($con, $num_to)."',".
                "'".mysqli_real_escape_string($con, $num_from)."',".
                "'".mysqli_real_escape_string($con, $body)."',".
                "'".mysqli_real_escape_string($con, $status)."',".
                "'".mysqli_real_escape_string($con, $num_segments)."',".
                "'".mysqli_real_escape_string($con, $num_media)."',".
                "'".mysqli_real_escape_string($con, $direction)."',".
                "'".mysqli_real_escape_string($con, $api_version)."',".
                "'".mysqli_real_escape_string($con, $price)."',".
                "'".mysqli_real_escape_string($con, $price_unit)."',".
		"'".mysqli_real_escape_string($con, $error_code)."',".
                "'".mysqli_real_escape_string($con, $error_message)."',".
                "now());";
            //echo $sqlInsert.PHP_EOL;
            $result=mysqli_query($con, $sqlInsert);
	    if($result){
		echo "(sid)=($sid) Inserted Properly.".PHP_EOL;
	    }else{
		echo "(sid)=($sid) Not Inserted.".PHP_EOL;
	    }
	}
	}
    }
?>
