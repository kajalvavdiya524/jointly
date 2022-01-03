<?php
class Mdl_common extends CI_Model
{
	function __construct()
	{
		parent::__construct();
		date_default_timezone_set('UTC');
		/* ---- set language ---- */
		$lang = 'english';
		if($this->session->userdata('lang') != '')
		{
			$lang = $this->session->userdata('lang');
		}
		define('LANG', $lang);
		$this->lang->load("message", LANG);
		
		//$this->load_constants_for_lang();
	}

	/*=================================================================================
	Check partner session
	==================================================================================*/

	function checkOwnerSession()
	{
		if(!$this->session->userdata('owner_id'))
		redirect('');
	}

	/*=================================================================================
	Check admin session
	==================================================================================*/

	function checkAdminSession()
	{
		if(!$this->session->userdata('admin_id'))
		redirect('admin');
	}

	/*=================================================================================
	Check user session
	==================================================================================*/

	function checkUserSession()
	{
		if(!$this->session->userdata('user_id'))
		redirect('');
	}

	/*=================================================================================
	API Parameter Validation
	==================================================================================*/

	function param_validation($paramarray, $data)
	{
		$NovalueParam = '';
		foreach($paramarray as $val)
		{
			if(!array_key_exists($val, $data))
			{
				$NovalueParam[] = $val;
			}
		}
		if(is_array($NovalueParam) && count($NovalueParam) > 0)
		{
			$returnArr['error'] = true;
			$returnArr['message'] = 'Sorry, that is not valid input. You missed '.implode(',', $NovalueParam).' parameters';
			return $returnArr;
		}
		else
		{
			return false;
		}
	}

	/*=================================================================================
	get average user review
	==================================================================================*/

	function get_avg_user_review($user_id)
	{
		$sql = "SELECT IFNULL(FORMAT(AVG(avg_review),2),'0.00') AS avg_star
		FROM `sa_user_review`
		WHERE other_user_id = ".$user_id." OR other2_user_id = ".$user_id;

		$res = $this->db->query($sql);
		if($res->num_rows() > 0)
		{
			$result = $res->row_array();
			return $result['avg_star'];
		}
	}

	/*=================================================================================
	get average club review
	==================================================================================*/

	function get_avg_club_review($club_id)
	{
		$sql = "SELECT IFNULL(FORMAT(AVG(avg_review),2),'0.00') AS avg_star
		FROM sa_club_review
		WHERE club_id = ".$club_id;

		$res = $this->db->query($sql);
		if($res->num_rows() > 0)
		{
			$result = $res->row_array();
			return $result['avg_star'];
		}
	}
	/*=================================================================================
	get user rank
	==================================================================================*/

	function get_user_rank($rank_id)
	{
		$sql = "SELECT IFNULL(value,0) AS rank FROM sa_rank WHERE rank_id = ".$rank_id;

		$res = $this->db->query($sql);
		if($res->num_rows() > 0)
		{
			$result = $res->row_array();
			return $result['rank'];
		}
	}

	/*=================================================================================
	Upload file
	==================================================================================*/

	function upload_file($uploadFile, $filetype, $folder, $fileName = '')
	{
		$resultArr = array();

		$config['max_size'] = '1024000';
		if($filetype == 'img') 	$config['allowed_types'] = 'gif|jpg|png|jpeg';
		if($filetype == 'All') 	$config['allowed_types'] = 'gif|jpg|png|jpeg|pdf|doc|docx|zip|xls';
		if($filetype == 'csv') 	$config['allowed_types'] = 'csv';
		if($filetype == 'swf') 	$config['allowed_types'] = 'swf';
		if($filetype == 'mp3') 	$config['allowed_types'] = 'mp3|wma|wav|.ra|.ram|.rm|.mid|.ogg';
		if($filetype == 'html') 	$config['allowed_types'] = 'html|htm';

		if(strpos($folder,'application/views') !== FALSE)
		$config['upload_path'] = './'.$folder.'/';
		else
		$config['upload_path'] = './uploads/'.$folder.'/';

		if($fileName != "")
		$config['file_name'] = $fileName;

		$this->load->library('upload', $config);
		$this->upload->initialize($config);

		if(!$this->upload->do_upload($uploadFile))
		{
			$resultArr['success'] = false;
			$resultArr['error'] = $this->upload->display_errors();
		}
		else
		{
			$resArr = $this->upload->data();
			$resultArr['success'] = true;

			if(strpos($folder,'application/views') !== FALSE)
			$resultArr['path'] = $folder."/".$resArr['file_name'];
			else
			$resultArr['path'] = "uploads/".$folder."/".$resArr['file_name'];
		}
		return $resultArr;
	}

	/*=================================================================================
	Pagination data
	==================================================================================*/

	function pagination_data($str, $total_rows, $per_page, $js_function)
	{
		$config['base_url'] = base_url().$str;
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $per_page;
		$config['next_link'] = '&gt;';
		$config['prev_link'] = '&lt;';
		$config['first_link'] = '&gt;&gt;';
		$config['last_link'] = '&lt;&lt;';

		$this->pagination->initialize($config);

		$jsFunction['name'] = $js_function;
		$jsFunction['params'] = array();
		$this->pagination->initialize_js_function($jsFunction);

		$data['base_url'] = $config['base_url'];
		$data['page_link'] = $this->pagination->create_js_links();

		return $data;
	}

	/*=================================================================================
	Send mail
	==================================================================================*/

	function send_mail($toEmail, $subject, $mail_body, $fromEmail = '', $fromName = '', $ccEmails = '', $replyToEmail = '')
	{
		require_once(APPPATH . 'libraries/smtp/class.phpmailer.php');

		if(!$fromEmail)
		$fromEmail = FROM_EMAIL;

		if(!$fromName)
		$fromName = FROM_EMAIL_NAME;

		$mail     = new PHPMailer();
		$mail->IsSMTP();
		$mail->IsHTML(true); // send as HTML
		$mail->SMTPAuth = true;                  // enable SMTP authentication
		$mail->SMTPSecure = SMTP_SECURE;           // sets the prefix to the servier
		$mail->Host = SMTP_HOST;          // sets GMAIL as the SMTP server
		$mail->Port = SMTP_PORT;             // set the SMTP port
		$mail->Username = SMTP_USERNAME;         // GMAIL username
		$mail->Password = SMTP_PASSWORD;         // GMAIL password
		$mail->From = $fromEmail;
		$mail->FromName = $fromName;
		if($replyToEmail != '')
		$mail->addReplyTo($replyToEmail, '');
		$mail->Subject = $subject;
		$mail->Body = $mail_body;            //HTML Body
		$emailId = $toEmail;
		$emails  = explode(",", $emailId);

		foreach($emails as $email){
			$mail->AddAddress($email);
		}

		if($mail->Send()){
			return true;
		}
		else
		{
			return false;
		}
	}

	/*=================================================================================
	Send Push
	==================================================================================*/

	function send_push($data, $message_array)
	{
		//return true;
		$device_type = $data['device_type'];
		$register_id = $data['register_id'];
		$token       = $data['device_token'];
		$badge       = 0;

		//echo $device_type;die;
		if($device_type == 0){
			if($register_id != "")
			{
				$apiKey          = "AIzaSyCamQAu7QCGOu_WfPWirB5n2rXej6jVo2g";
				$registrationIDs = array($register_id);
				// Set POST variables
				$url    = 'https://android.googleapis.com/gcm/send';
				$fields = array(
					'registration_ids'=> $registrationIDs,
					'data'            => $message_array,
				);
				$headers = array(
					'Authorization: key=' . $apiKey,
					'Content-Type: application/json'
				);
				// Open connection
				$ch = curl_init();
				// Set the url, number of POST vars, POST data
				curl_setopt( $ch, CURLOPT_URL, $url );
				curl_setopt( $ch, CURLOPT_POST, true );
				curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
				// Execute post
				$result = curl_exec($ch);

				// Close connection
				curl_close($ch);
			}
		}
		else
		{
			//echo "In Else";die;
			//error_reporting( - 1);
			//echo $token;die;
			if($token && strlen($token) == 64)
			{

				// Using Autoload all classes are loaded on - demand
				require_once APPPATH.'/libraries/push/ApnsPHP/Autoload.php';

				// Instantiate a new ApnsPHP_Push object
				$push    = new ApnsPHP_Push(
					//ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
					ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
					//APPPATH.' / libraries / push / ApnsPHP / sikkaaDev.pem'
					APPPATH.'/libraries/push/ApnsPHP/sikkaaProduction.pem'
				);
				// Set the Root Certificate Autority to verify the Apple remote peer
				//$push->setRootCertificationAuthority(APPPATH.' / libraries / push / ApnsPHP / sikkaaDev.pem');
				$push->setRootCertificationAuthority(APPPATH.'/libraries/push/ApnsPHP/sikkaaProduction.pem');

				// Connect to the Apple Push Notification Service
				$push->connect();

				// Instantiate a new Message with a single recipient
				//$message = new ApnsPHP_Message('223eb502c158e7bda83128fbd966aa709fcf4b904399d7c01934f9a2f892a029');
				$message = new ApnsPHP_Message($token);

				// Set badge icon to "3"
				$message->setBadge((int)$badge);

				// Set a simple welcome text
				$message->setText(trim($message_array['push_message']));

				// Play the default sound
				$message->setSound();

				// Set a custom property
				//echo json_encode($custom_msg);die
				$message->setCustomProperty('push_type', $message_array['push_type']);

				//echo trim($message_array['message_push']);die;
				// Set the expiry value to 30 seconds
				$message->setExpiry(30);

				//echo print_r($message);die;
				// Add the message to the message queue
				$push->add($message);
				//echo $token;die;
				// Send all messages in the message queue
				$push->send();

				// Disconnect from the Apple Push Notification Service
				$push->disconnect();
				//var_dump($push);
				// Examine the error message container
				$aErrorQueue = $push->getErrors();
				//print_r($aErrorQueue);
				//var_dump($aErrorQueue);
				return true;

			}
		}
	}

	/*=================================================================================
	Send Push
	==================================================================================*/

	/*public function send_push($token, $msg, $badge, $custom_msg)
	{
	// Using Autoload all classes are loaded on-demand
	require_once APPPATH.'/libraries/ApnsPHP/Autoload.php';

	// Instantiate a new ApnsPHP_Push object
	$push = new ApnsPHP_Push(
	ApnsPHP_Abstract::ENVIRONMENT_SANDBOX,
	//ApnsPHP_Abstract::ENVIRONMENT_PRODUCTION,
	APPPATH.'/libraries/ApnsPHP/echo_development.pem'
	//APPPATH.'/libraries/ApnsPHP/echo_production.pem'
	);
	// Set the Root Certificate Autority to verify the Apple remote peer
	$push->setRootCertificationAuthority(APPPATH.'/libraries/ApnsPHP/echo_development.pem');
	//$push->setRootCertificationAuthority(APPPATH.'/libraries/ApnsPHP/echo_production.pem');

	// Connect to the Apple Push Notification Service
	$push->connect();

	// Instantiate a new Message with a single recipient
	//$message = new ApnsPHP_Message('223eb502c158e7bda83128fbd966aa709fcf4b904399d7c01934f9a2f892a029');
	$message = new ApnsPHP_Message($token);
	// Set a custom identifier. To get back this identifier use the getCustomIdentifier() method
	// over a ApnsPHP_Message object retrieved with the getErrors() message.
	$message->setCustomIdentifier("Message-Badge-3");

	// Set badge icon to "3"
	$message->setBadge((int)$badge+1);

	// Set a simple welcome text
	$message->setText($msg);

	// Play the default sound
	$message->setSound();

	// Set a custom property
	if($custom_msg)
	{
	$message->setCustomProperty(key($custom_msg), json_encode($custom_msg));
	}
	// Set the expiry value to 30 seconds
	$message->setExpiry(30);
	// Add the message to the message queue
	$push->add($message);

	// Send all messages in the message queue
	$push->send();

	// Disconnect from the Apple Push Notification Service
	$push->disconnect();

	// Examine the error message container
	$aErrorQueue = $push->getErrors();
	//var_dump($aErrorQueue);
	return true;
	}*/

	/*=================================================================================
	Send Sms
	==================================================================================*/

	function send_sms($from, $to, $message)
	{
		require APPPATH.'/libraries/twillio/Services/Twilio.php';
		try
		{
			$to         = preg_replace("/[^0-9]/", "", $to);

			/*$AccountSid = "AC6e5ae84969cb1013670bf9e5c4387004"; //smita //+12056602121
			$AuthToken = "7f34d677ed8a8714df341b5e6813fe4a";*/

			$AccountSid = "AC7f52a0a6f29a9aa9ea97e760c56ace94"; //client //+12037186715
			$AuthToken  = "47fce0693bbf7df3e15d25972e74d05d";

			$client     = new Services_Twilio($AccountSid, $AuthToken);
			$message    = $client->account->messages->create(array(
					"From"=> "+12037186715",//+1 2037186715
					"To"=> "+$to",
					"Body"=> $message,
				));
			//print_r($message);die;
			if($message->sid)
			return TRUE;
			else
			return FALSE;
		}catch(Exception $e)
		{
			$e->getMessage();//die;
		}
	}
	function send_sinch_sms($to, $message)
	{
		$key = YOUR_APP_KEY;
		$secret = YOUR_APP_SECRET;
		$user = "application\\" . $key . ":" . $secret;

		$msg = array("message" => $message);
		$data = json_encode($msg);

		$ch = curl_init('https://messagingapi.sinch.com/v1/sms/'.$to);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_USERPWD,$user);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		$result = curl_exec($ch);
		/*if(curl_errno($ch)) {
			echo 'Curl error: ' . curl_error($ch);
		} else {
			echo $result;
		}*/
		curl_close($ch);
		//return $result; 
	}

	/*=================================================================================
	Call Verification
	==================================================================================*/

	function call_me($from, $to, $msg)
	{
		try
		{
			$AccountSid = "AC41b9509bf11cb89b4063feab7bdc3ea0";
			$AuthToken  = "0c1681f45b8e1863871e8bde5915ba14";

			/*$AccountSid = "ACc733dfe2c92390ca91bafb29d36057b0";
			$AuthToken = "339a4f1335e31e55cd97c9a3bbffd7dd";*/

			$client     = new Services_Twilio($AccountSid, $AuthToken);

			$url        = "http://twimlets.com/message";
			$message    = $client->account->calls->create(
				"+17746332198",
				"+$to",
				$url."?Message=".urlencode($msg)
			);
			//echo ' < pre > ';print_r($message);
			if($message->sid)
			return TRUE;
			else
			return FALSE;
		}catch(Exception $e)
		{
			return false;
		}

	}

	/*=================================================================================
	Generate password
	==================================================================================*/

	function generate_password($length = 9, $strength = 0)
	{
		$vowels     = 'aeuy';
		$consonants = 'bdghjmnpqrstvz';
		if($strength & 1)
		{
			$consonants .= 'BDGHJLMNPQRSTVWXZ';
		}
		if($strength & 2)
		{
			$vowels .= "AEUY";
		}
		if($strength & 4)
		{
			$consonants .= '23456789';
		}
		if($strength & 8)
		{
			$consonants .= '@#$%';
		}

		$password = '';
		$alt      = time() % 2;
		for($i = 0; $i < $length; $i++)
		{
			if($alt == 1)
			{
				$password .= $consonants[(rand() % strlen($consonants))];
				$alt = 0;
			}
			else
			{
				$password .= $vowels[(rand() % strlen($vowels))];
				$alt = 1;
			}
		}
		return $password;
	}

	/*=================================================================================
	Get Record
	==================================================================================*/

	function execute_query($sql)
	{
		return $q = $this->db->query($sql);
	}

	/*=================================================================================
	Get Record
	==================================================================================*/

	function get_record($table_name, $where, $field = '*', $order_by = array())
	{
		$this->db->select($field,false);
		$this->db->from($table_name);
		if(!empty($order_by))
		{
			foreach($order_by as $key=>$val)
			{
				$this->db->order_by($key, $val);
			}
		}
		if($where != '')
		{
			if(is_array($where))
			{
				foreach($where as $key=>$val)
				{
					$this->db->where($key, $val);
				}
			}
			else
			{
				$this->db->where($where);
			}
			//$this->db->where($where);
		}

		return $this->db->get();
	}

	/*=================================================================================
	Insert Record
	==================================================================================*/

	function insert_record($table_name, $insert_data)
	{
		$this->db->insert($table_name, $insert_data);
		return $this->db->insert_id();
	}

	/*=================================================================================
	Update Record
	==================================================================================*/

	function update_record($table_name, $where, $update_data)
	{
		if(is_array($where)){
			foreach($where as $key=>$val){
				$this->db->where($key, $val);
			}
		}
		else
		{
			$this->db->where($where);
		}

		return $this->db->update($table_name, $update_data);
		// echo $this->db->last_query();die();
	}

	/*=================================================================================
	Delete Record
	==================================================================================*/

	function delete_record($table_name, $where)
	{
		if(is_array($where))
		{
			foreach($where as $key=>$val)
			{
				$this->db->where($key, $val);
			}
		}
		else
		{
			$this->db->where($where);
		}

		return $this->db->delete($table_name);
	}

	/*=================================================================================
	check image null
	==================================================================================*/

	function check_image_null($value)
	{
		if(!empty($value))
		{
			if(strpos($value,'http') !== false)
			{
				return $value;
			}
			else
			{
				if(file_exists($value))
				return base_url().$value;
				else
				return "";
			}
		}
		else
		{
			return "";
		}
	}

	/*=================================================================================
	Compress image
	==================================================================================*/

	function compress_image($source_url, $destination_url, $quality)
	{
		$info = getimagesize($source_url);

		if($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($source_url);
		elseif($info['mime'] == 'image/gif') $image = imagecreatefromgif($source_url);
		elseif($info['mime'] == 'image/png') $image = imagecreatefrompng($source_url);

		//save it
		imagejpeg($image, $destination_url, $quality);

		//return destination file url
		return $destination_url;
	}

	/*=================================================================================
	Sorting array
	==================================================================================*/

	function aasort( & $array, $key , $order)
	{
		$sorter = array();
		$ret = array();
		reset($array);
		foreach($array as $ii => $va)
		{
			$sorter[$ii] = $va[$key];
		}
		if($order == 'ASC')
		asort($sorter);
		else
		arsort($sorter);
		foreach($sorter as $ii => $va)
		{
			$ret[$ii] = $array[$ii];
		}
		return $array = array_values($ret);
	}

	/*=================================================================================
	Get dupicate keys
	==================================================================================*/

	function get_dup_keys(array $array, $return_first = true, $return_by_key = true)
	{
		$seen = array();
		$dups = array();

		foreach($array as $k => $v)
		{
			$vk = $return_by_key ? $v : 0;
			if(!array_key_exists($v, $seen))
			{
				$seen[$v] = $k;
				$dups[$vk][] = $seen[$v];
				continue;
			}
			if($return_first && !array_key_exists($v, $dups))
			{
				$dups[$vk][] = $seen[$v];
			}
			$dups[$vk][] = $k;
		}
		return $return_by_key ? $dups : $dups[0];
	}

	/*=================================================================================
	Get Lat long from address
	==================================================================================*/

	function get_lat_long_from_address($address, $region = '')
	{

		$address = str_replace(" ", "+", $address);

		$json    = file_get_contents("http://maps.google.com/maps/api/geocode/json?address=$address&sensor=false&region=$region");
		$json    = json_decode($json);

		$lat     = $json->
		{
			'results'
		}[0]->
		{
			'geometry'
		}->
		{
			'location'
		}->
		{
			'lat'
		};
		$long = $json->
		{
			'results'
		}[0]->
		{
			'geometry'
		}->
		{
			'location'
		}->
		{
			'lng'
		};
		$location['latitude'] = $lat;
		$location['longitude'] = $long;
		return $location;
	}

	/*=================================================================================
	This function is used to Get bank details from IFSC code
	==================================================================================*/

	function get_bank_details_from_ifsc($ifsc_code)
	{
		$url = "https://ifsc.razorpay.com/".trim($ifsc_code);
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_URL, $url);

		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	/*=================================================================================
	This function is used to convert base64 image data to image file in jpg format
	==================================================================================*/

	function base64_to_jpeg($base64_string, $output_file)
	{
		$ifp = fopen($output_file, "wb");
		$data= explode(',', $base64_string);
		fwrite($ifp, base64_decode($data[1]));
		fclose($ifp);
		//	$success = file_put_contents($file, $data);
		//	print_r($output_file); die;
		return $output_file;
	}


	/*=================================================================================
	Generate Redeem Code
	==================================================================================*/

	function generate_random_string($l = 4)
	{
		return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $l));
	}

	/*=================================================================================
	Load language constanst file
	==================================================================================*/

	function load_constants_for_lang($lang = 0)
	{
		//0 = English, 1 = Arabic
		if($lang == 0)
		require(APPPATH.'config/constants_message_english.php');
		//else
		//require(APPPATH.'config / constants_message_arabic.php');
	}

	/*=================================================================================
	Send Mail For Varify Email
	==================================================================================*/

	function send_user_create_mail($user_id,$password)//1 = field owner 2 = user
	{

		//Get email wise details
		$where = "field_owner_id = '".$user_id."'";
		$res   = $this->mdl_common->get_record('tbl_field_owner', $where)->row_array();

		//Send mail
		$m_data['user_type'] = "Field_owner";
		$m_data['username'] = $res['owner_name'];
		$m_data['user_id'] = $user_id;
		$m_data['password'] = $password;
		$m_data['email'] = $res['email'];
		$m_data['subject'] = 'Sikka account';//'Confirm Varification';

		$html = $this->load->view('admin/mail/user_create_mail', $m_data, true);

		$toEmail   = $res['email'];
		$subject   = 'Sikka account';//'Varify Email';
		$mail_body = $html;
		$fromEmail = FROM_EMAIL;
		$fromName  = PROJECT_NAME;

		return $this->send_mail($toEmail, $subject, $mail_body, $fromEmail, $fromName);

	}
	/*=================================================================================
	Send Mail For Varify Email
	==================================================================================*/

	function send_end_user_create_mail($user_id,$password)//1 = field owner 2 = user
	{

		//Get email wise details
		$where = "user_id = '".$user_id."'";
		$res   = $this->mdl_common->get_record('tbl_user', $where)->row_array();

		//Send mail
		$m_data['username'] = $res['username'];
		$m_data['user_id'] = $user_id;
		$m_data['password'] = $password;
		$m_data['email'] = $res['email'];
		$m_data['subject'] = 'Sikka account';//'Confirm Varification';

		$html = $this->load->view('admin/mail/user_create_mail', $m_data, true);

		$toEmail   = $res['email'];
		$subject   = 'Sikka account';//'Varify Email';
		$mail_body = $html;
		$fromEmail = FROM_EMAIL;
		$fromName  = PROJECT_NAME;

		return $this->send_mail($toEmail, $subject, $mail_body, $fromEmail, $fromName);

	}

	/*=================================================================================
	Upload image
	==================================================================================*/
	function upload_image($temp_file,$file_name,$upload_path)
	{
		$img_name        = "image_".mt_rand(10000,999999999);
		$path            = $file_name;
		$ext             = pathinfo($path, PATHINFO_EXTENSION);
		$upload_img_name = $upload_path.$img_name.".".$ext;
		$uploaded_image  = $img_name.".".$ext;
		move_uploaded_file($temp_file, $upload_img_name);
		return $upload_img_name;
	}
	function encrypt($temp_pass)
	{
		$cryptKey = 'hyHJyj8745y4j7867HJj5gj';
		$method   = "AES-128-ECB";
		$qEncoded = base64_encode(openssl_encrypt($temp_pass,$method,$cryptKey,$option   = 0,$iv       = ''));
		//$qEncoded = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), $temp_pass, MCRYPT_MODE_CBC, md5(md5($cryptKey))));
		$qEncoded = strtr($qEncoded,
			array(
				'+'=> '.',
				'='=> '-',
				'/'=> '~'
			)
		);
		return $qEncoded;
	}

	function decrypt($temp_pass)
	{
		$temp_pass = strtr(
			$temp_pass,
			array(
				'.'=> '+',
				'-'=> '=',
				'~'=> '/'
			)
		);
		$cryptKey = 'hyHJyj8745y4j7867HJj5gj';
		$method   = "AES-128-ECB";
		$qDecoded = openssl_decrypt(base64_decode($temp_pass),$method,$cryptKey,$option   = 0,$iv       = '');
		//$qDecoded = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($cryptKey), base64_decode($temp_pass), MCRYPT_MODE_CBC, md5(md5($cryptKey))), "\0");
		return $qDecoded;
	}

	/**
	* Send FCM Push
	* @param Array $data
	* @param Array $message_data
	*
	* @return
	*/

	function send_fcm_push($data,$message_data)
	{
		$device_type = @$data['device_type'];
		$register_id = $data['register_id'];
		/*$token       = @$data['device_token'];
		$badge       = 0;*/
//	print_r($message_data);die;
		if($register_id != ""){

			/*$fields['notification'] = array
			(
			'body'=> $message_data['message'],
			'title'=> $message_data['title'],
			'icon' => 'myicon',
			'sound'=> 'mySound'
			);*/
			if($device_type != 0)
			{
				$fields['notification'] = array
				(
					'text'=> $message_data['message'],
				);
			}
			
			$message_data['custom_data']['message'] = $message_data['message'];
				//print_r($message_data['custom_data']['message']);die;
			if(!empty($message_data['custom_data'])){

				$fields['data'] = $message_data['custom_data'];
			}
			//$fields['data'] = $message_data;

			if($data['multiple'] == 1){
				$fields['registration_ids'] = $register_id;
			}
			else
			{
				$fields['to'] = $register_id;
			}
			//$fields['priority'] = $priority;
	//	print_r($fields);die;
			$headers = array
			(
				'Authorization: key=' . API_ACCESS_KEY_FOR_FIREBASE_PUSH,
				'Content-Type: application/json'
			);
			//print_r($fields);die;
			#Send Reponse To FireBase Server
			$ch = curl_init();
			curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
			curl_setopt( $ch,CURLOPT_POST, true );
			curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
			curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
			curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
			$result = curl_exec($ch );
			curl_close( $ch );
			return $result;

		}
	}
	/*====================================================
	Set user time zone from UTC
	======================================================*/
	function get_time_by_user_timezone($date_time,$time_zone)
	{
		if($date_time == "0000-00-00 00:00:00")
		return $date_time;
		$date                = new DateTime($date_time, new DateTimeZone('UTC'));
		date_default_timezone_set($time_zone);
		$user_time_zone_time = date("Y-m-d H:i:s", $date->format('U'));
		date_default_timezone_set('UTC');
		return ($user_time_zone_time != "")?$user_time_zone_time:$date_time;
	}
	
	
	function get_utc_time_from_user_timezone($date_time,$time_zone)
	{
		$date               = new DateTime($date_time, new DateTimeZone($time_zone));
		date_default_timezone_set('UTC');
		$utc_time_zone_time = date("Y-m-d H:i:s", $date->format('U'));
		return $utc_time_zone_time;
	}

        function time_format($timestamp)
	{
		//type cast, current time, difference in timestamps
		$timestamp      = strtotime($timestamp);
		$timestamp      = (int) $timestamp;
		$current_time   = time();
		
		$diff           = $current_time - $timestamp;
	
		//intervals in seconds
		$intervals      = array (
			'year' => 31556926, 'month' => 2629744, 'week' => 604800, 'day' => 86400, 'hour' => 3600, 'minute'=> 60
		);
	
		//now we just find the difference
		if ($diff == 0) {
			return 'just now';
		}
	
		if ($diff < 60) {
			return $diff == 1 ? $diff . ' second ago' : $diff . ' seconds ago';
		}
	
		if ($diff >= 60 && $diff < $intervals['hour']) {
			$diff = floor($diff/$intervals['minute']);
			return $diff == 1 ? $diff . ' minute ago' : $diff . ' minutes ago';
		}
	
		if ($diff >= $intervals['hour'] && $diff < $intervals['day']) {
			$diff = floor($diff/$intervals['hour']);
			return $diff == 1 ? $diff . ' hour ago' : $diff . ' hours ago';
		}
	
		if ($diff >= $intervals['day'] && $diff < $intervals['week']) {
			$diff = floor($diff/$intervals['day']);
			return $diff == 1 ? $diff . ' day ago' : $diff . ' days ago';
		}
	
		if ($diff >= $intervals['week'] && $diff < $intervals['month']) {
			$diff = floor($diff/$intervals['week']);
			return $diff == 1 ? $diff . ' week ago' : $diff . ' weeks ago';
		}
	
		if ($diff >= $intervals['month'] && $diff < $intervals['year']) {
			$diff = floor($diff/$intervals['month']);
			return $diff == 1 ? $diff . ' month ago' : $diff . ' months ago';
		}
	
		if ($diff >= $intervals['year']) {
			$diff = floor($diff/$intervals['year']);
			return $diff == 1 ? $diff . ' year ago' : $diff . ' years ago';
		}
	}
	/*=================================================================================
	download the image
	==================================================================================*/
	function save_image_from_url($url, $output_file,$external = 0)
	{
		if ($external != 1) {
			$ch = curl_init($url);
			$fp = fopen($output_file, 'wb');
			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$res= curl_exec($ch);
			curl_close($ch);
			fclose($fp);
			return $output_file;
		} else {
			$ch = curl_init();
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, TRUE);
			curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			$header = curl_exec($ch);
			$img_success = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if ($img_success == '200') {
				$redir = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$ch1 = curl_init($redir);
				$fp = fopen($output_file, 'wb');
				curl_setopt($ch1, CURLOPT_FILE, $fp);
				curl_setopt ($ch1, CURLOPT_SSL_VERIFYPEER, FALSE);
				curl_setopt($ch1, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
				curl_setopt($ch1, CURLOPT_HEADER, 0);
				curl_exec($ch1);
				curl_close($ch1);
				fclose($fp);
				curl_close($ch);
				return $output_file;
			}
			curl_close($ch);
			return FALSE;
		}
	}
	
	/**
	* get ip information from ip
	* 
	* @return array
	*/
	function ip_info() {
   		$ip = $_SERVER['REMOTE_ADDR'];
        $url = "http://ip-api.com/json/{$ip}";
        $details = json_decode(file_get_contents($url),TRUE); 
        return $details;
    } 
}
?>