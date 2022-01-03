<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
require APPPATH.'/libraries/api/REST_Controller.php';
require APPPATH.'/libraries/api/Format.php';

 use Restserver\Libraries\REST_Controller;
 class User Extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('api/mdl_user');
		
	}
	/*public function register_post()
	{
		$response = array();
		$input_data['country_code'] = $this->input->post('country_code',TRUE);
		$input_data['mobile_no'] = $this->input->post('mobile_no',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$push_data['device_token'] = $this->post('device_token');
		$push_data['register_id'] = $this->post('register_id');
		$push_data['device_type'] = $this->post('device_type'); //[0 = Android, 1 = iOS]

		$input_parameter = array('country_code','mobile_no');
		$validation = $this->ParamValidation($input_parameter,$input_data);

		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation, 200);





	}*/
	public function register_post()
	{
		$response = array();
		$input_data['country_code'] = $this->input->post('country_code',TRUE);
		$input_data['mobile_no'] = $this->input->post('mobile_no',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);


		$input_parameter = array('country_code','mobile_no');
		$validation = $this->ParamValidation($input_parameter,$input_data);

		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation, 200);

		$exist_response = $this->mdl_user->check_user_already_exist(
			array(
				/*array('value'           =>$input_data['username'],
					'db_key'          =>'username',
					'message_if_exist'=>__USERNAME_ALREADY_EXIST),*/
				/*array('value'           =>$input_data['email'],
					'db_key'          =>'email',
					'message_if_exist'=>__EMAIL_ALREADY_EXIST),*/
				array('value'           =>$input_data['mobile_no'],
					'db_key'          =>'mobile_no',
					'message_if_exist'=>__MOBILE_ALREADY_EXIST)),$user_id='');

		if($exist_response[RESPONSE_FLAG])
		{
			$user_data = array(
				'country_code' => $input_data['country_code'],
				'mobile_no'=>$input_data['mobile_no'],
				'date_added'=> date('Y-m-d H:i:s'),
				'otp' => rand(1111, 9999)
				//'otp' => '1234'
			);
			$user_id = $this->mdl_common->insert_record('tbl_user',$user_data);
			if($user_id)
			{
				$get_referral = $this->db->get_where('tbl_new_referrer_info',array('referral_mobile_no'=>$input_data['mobile_no'],'is_del'=>0))->result_array();
				if(!empty($get_referral))
				{
					foreach ($get_referral as $referral)
					{
						# code...
							$check_info_exist = $this->db->get_where('tbl_manage_referrer',array('info_id'=>$referral['info_id'],'is_del'=>0))->result_array();
							if(!empty($check_info_exist))
							{
								$this->mdl_common->update_record('tbl_manage_referrer',array('info_id'=>$referral['info_id'],'is_del'=>0),array('referral_id'=>$user_id));
							}


					}
				}

				$get_business = $this->db->get_where('tbl_new_referrer_info',array('business_mobile_no'=>$input_data['mobile_no'],'is_del'=>0))->result_array();
				if(!empty($get_business))
				{
					foreach ($get_business as $business)
					{
						# code...
							$check_b_exist = $this->db->get_where('tbl_manage_referrer',array('info_id'=>$business['info_id'],'is_del'=>0))->result_array();
							if(!empty($check_b_exist))
							{
								$this->mdl_common->update_record('tbl_manage_referrer',array('info_id'=>$business['info_id'],'is_del'=>0),array('business_id'=>$user_id));
							}


					}
				}
				//$this->mdl_user->save_push_details($user_id,$push_data);

				//Generate Access Token
				/*$access_token_data['access_token'] = md5(rand(11111,99999));
				$access_token_data['device_token'] = $push_data['device_token'];
				$access_token_data['user_id'] = $user_id;*/

				/*$access_token = $this->mdl_user->save_user_access_token($access_token_data);

				$user_data    = $this->mdl_user->get_user_details($user_id);
				
			//	$user_data['is_facebook'] = '0';
				$user_data[ACCESS_TOKEN] = $access_token_data['access_token'];*/
				$user_data1 = array(
					//'country_code' => $input_data['country_code'],
					'user_id' => $user_id,
					'mobile_no'=>$input_data['mobile_no'],
					'is_verify'=>'0',
					'is_profile_complete'=>'0'
					//'date_added'=> date('Y-m-d H:i:s'),
					//'otp' => '1234'
				);

				$to = $user_data['country_code'].''.$user_data['mobile_no'];
				$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), "Thank you for signup in Referral App ! Use OTP : " . $user_data['otp'] . " to valid for 15 minutes from the request. Please do not share OTP with anyone");


				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = _OTP_SEND_TO_MOBILE_NO;
				$response[RESPONSE_DATA] = $user_data1;
				//$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				//$response[RESPONSE_MESSAGE] = _OTP_SEND_TO_MOBILE_NO;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
			}
		}
		else
		{	
			$user_id = $exist_response[RESPONSE_DATA]['user_id'];
			$user_data = $this->db->get_where('tbl_user',Array('user_id'=> $user_id,'is_del'    => 0))->row_array();
			if(!empty($user_data))
			{	
					$up_data = array(
					'country_code' => $input_data['country_code'],
					'mobile_no'=>$input_data['mobile_no'],
					'date_added'=> date('Y-m-d H:i:s'),
					'otp' => rand(1111, 9999),
					//'otp' => '1234',
					'is_verify'=>'0',
					'is_profile_complete'=> $user_data['is_profile_complete']
				);
					$update_user = $this->mdl_common->update_record('tbl_user','user_id = "'.$user_id.'"',$up_data);

					$to = $up_data['country_code'].''.$up_data['mobile_no'];
				$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), "Thank you for signin in Referral App ! Use OTP : " . $up_data['otp'] . " to valid for 15 minutes from the request. Please do not share OTP with anyone");
					if($update_user)
					{
						$user_data = array(
						//'country_code' => $input_data['country_code'],
						'user_id' => $user_data['user_id'],	
						'mobile_no'=>$input_data['mobile_no'],
						'is_verify'=>'0',
						'is_profile_complete'=> $user_data['is_profile_complete'],

						//'date_added'=> date('Y-m-d H:i:s'),
						//'otp' => '1234'
					);
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
						$response[RESPONSE_MESSAGE] = _OTP_SEND_TO_MOBILE_NO;
						$response[RESPONSE_DATA] = $user_data;
					}
					else
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
						$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
					}


			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
			}
			//echo $user_id;die;
			//$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			//$response[RESPONSE_MESSAGE] = $exist_response[RESPONSE_MESSAGE];
		}	
		$this->response($response,200);
	}
	function resend_otp_post()
	{
        $response = array();
        $input_data['user_id'] = $this->input->post('user_id', TRUE);
      //  $input_data['country_code'] = $this->input->post('country_code',TRUE);
	//	$input_data['mobile_no'] = $this->input->post('mobile_no',TRUE);

        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
         
        $input_parameter = array('user_id');
        $validation = $this->ParamValidation($input_parameter, $input_data);
         
        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        	$this->response($validation, 200);
        	 
        //Get user details
        $this->db->select('*');
        $this->db->from('tbl_user');		 
        $this->db->where('user_id', $input_data['user_id']);
        $this->db->where(array('is_flag' => 1, 'is_del' => 0));
        $user_data = $this->db->get()->row_array();

        if(!empty($user_data))
        {  
			//$udata['otp'] = "1234";
			$udata['otp'] = rand(1111, 9999);
        	$udata['date_added'] = date('Y-m-d H:i:s');
        	$udata['is_verify'] = 0;


		//	$udata['modified_date'] = date('Y-m-d H:i:s');
            $is_send = $this->mdl_common->update_record('tbl_user', array('user_id' => $input_data['user_id']), $udata);

            $to = $user_data['country_code'].''.$user_data['mobile_no'];
				$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), "Thank you for signin in Referral App ! Use OTP : " . $udata['otp'] . " to valid for 15 minutes from the request. Please do not share OTP with anyone");

			//$this->mdl_common->send_sms(str_replace(" ","",$user_data['mobile_no']), "Thank you for signup in " . PROJECT_NAME . "! Use OTP : " . $user_data['otp_for_register'] . " to valid for 15 minuties from the request. Please do not share OTP with anyone");
			
           $user_data = array(
				//'country_code' => $input_data['country_code'],
				'user_id' => $user_data['user_id'],	
				'mobile_no'=>$user_data['mobile_no'],
				'is_verify'=>'0',
				'is_profile_complete'=> $user_data['is_profile_complete'],

				//'date_added'=> date('Y-m-d H:i:s'),
				//'otp' => '1234'
			);

		
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
            $response[RESPONSE_MESSAGE] = _OTP_SEND_SUCCESSFULLY;
           // $user_data[ACCESS_TOKEN] = $access_token['access_token'];
            $response[RESPONSE_DATA] = $user_data;
        }
        else
        {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
            $response[RESPONSE_MESSAGE] = __NO_ACCOUNT_REGISTERED_WITH_THIS_NO;
        } 
        $this->response($response, 200);
	}
	public function otp_check_post()
	{
		$input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['otp'] = $this->input->post('otp', TRUE);
        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
      //  $input_data['user_id'] = $this->input->post('user_id', TRUE);
      //  $input_data['access_token'] = $this->input->post('access_token', TRUE);
        $push_data['device_token'] = $this->post('device_token');
		$push_data['register_id'] = $this->post('register_id');
		$push_data['device_type'] = $this->post('device_type'); //[0 = Android, 1 = iOS]
        
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
        
        $input_parameter = array('user_id','otp');
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);
         $where = 'user_id = "' . $input_data['user_id'] . '" AND otp = "' . trim($input_data['otp']) . '"';
        $res = $this->mdl_common->get_record('tbl_user', $where)->row_array();
        
        if (empty($res)) {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
            $response[RESPONSE_MESSAGE] = __USER_OTP_WRONG;
        } else {
            $current_date = date('Y-m-d H:i:s');
            $min_time     = (strtotime($current_date) - strtotime($res['date_added']));
            $minuite      = date('i',$min_time);
            
            if($minuite > 15){
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                $response[RESPONSE_MESSAGE] = __OTP_EXPIRED;
                $this->response($response, 200);
            } else {
                $where = 'user_id = "' . $input_data['user_id'] . '" ';
             //   $udata['otp_for_register'] = "";
                $udata['is_verify'] = "1";
				//$udata['date_otp_for_register'] = date('Y-m-d H:i:s');
                //$udata['modified_date'] = date('Y-m-d H:i:s');
                $status = $this->mdl_common->update_record('tbl_user', $where, $udata);
                if($status)
                {
                    $where = 'user_id = "' . $input_data['user_id'] . '"';
                    $res1 = $this->mdl_common->get_record('tbl_user', $where)->row_array();

                    if ($res1['is_verify'] != '1') {
                        $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                        $response[RESPONSE_MESSAGE] = __MOBILE_VERY_PENDING;
                        $this->response($response, 200);
                    } elseif ($res1['is_del'] == 0) {
                        $user_data = $this->mdl_user->get_user_details($input_data['user_id']);

                        $this->mdl_user->save_push_details($input_data['user_id'],$push_data);

						//Generate Access Token
						$access_token_data['access_token'] = md5(rand(11111,99999));
						$access_token_data['device_token'] = $push_data['device_token'];
						$access_token_data['user_id'] = $input_data['user_id'];

						$access_token = $this->mdl_user->save_user_access_token($access_token_data);

                        $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                        $response[RESPONSE_MESSAGE] = _USER_MOB_VER_SUCCESS;
                       // $user_data[ACCESS_TOKEN] = $input_data['access_token']; 
                        $user_data[ACCESS_TOKEN] = $access_token_data['access_token'];
                        $response[RESPONSE_DATA] = $user_data;
                    } else {
                        $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                        $response[RESPONSE_MESSAGE] = __USER_BLOCKED;
                    }
                } else {
                    $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                    $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
                }
            }
        } 
        $this->response($response, 200); 
	}
	public function create_profile_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['first_name'] = $this->input->post('first_name',TRUE);
		$input_data['last_name'] = $this->input->post('last_name',TRUE);
		$input_data['mobile_no'] = $this->input->post('mobile_no',TRUE);
		$input_data['email_id'] = $this->input->post('email_id',TRUE);

		$input_data['is_business_details'] = $this->input->post('is_business_details',TRUE);
		$input_data['is_notification'] = $this->input->post('is_notification',TRUE);

		$input_data['business_name'] = $this->input->post('business_name',TRUE);
		$input_data['address'] = $this->input->post('address',TRUE);
		$input_data['city'] = $this->input->post('city',TRUE);
		$input_data['state'] = $this->input->post('state',TRUE);
		$input_data['postal_code'] = $this->input->post('postal_code',TRUE);
		$input_data['abn'] = $this->input->post('abn',TRUE);

		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		if($input_data['is_business_details']!= '0')
		{
			$input_parameter = array('user_id','access_token','first_name','last_name','mobile_no','email_id','business_name','address','city','state','postal_code','abn');
		}
		else
		{
			$input_parameter = array('user_id','access_token','first_name','last_name','mobile_no','email_id','address','city','state','postal_code');
		}
		//$input_parameter = array('user_id','access_token','first_name','last_name','mobile_no','email_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_response = $this->mdl_user->check_user_already_exist(
			array(
				/*array('value'           =>$input_data['username'],
					'db_key'          =>'username',
					'message_if_exist'=>__USERNAME_ALREADY_EXIST),*/
				array('value'           =>$input_data['email_id'],
					'db_key'          =>'email_id',
					'message_if_exist'=>__EMAIL_ALREADY_EXIST),
				array('value'           =>$input_data['mobile_no'],
					'db_key'          =>'mobile_no',
					'message_if_exist'=>__MOBILE_ALREADY_EXIST)),$input_data['user_id']);

			if($exist_response[RESPONSE_FLAG])
			{
				$user_data = array(
					'first_name' => $input_data['first_name'],
					'last_name' => $input_data['last_name'],
					'mobile_no' => $input_data['mobile_no'],
				//	'business_name' => $input_data['business_name'],
					'address' => $input_data['address'],
					'city' => $input_data['city'],
					'state' => $input_data['state'],
					'postal_code' => $input_data['postal_code'],
				//	'abn' => $input_data['abn'],
					'is_profile_complete'=>1,
					'date_modified' => date('Y-m-d H:i:s'),
				//	'is_notification' => $input_data['is_notification'],
					'email_id' => $input_data['email_id']
					
				);
				if($input_data['is_notification']!= '')
				{
					$user_data['is_notification'] = $input_data['is_notification'];
				}
				if($input_data['is_business_details']!= '0')
				{
					$user_data['business_name'] = $input_data['business_name'];
					$user_data['abn'] = $input_data['abn'];
				}
				if($input_data['is_business_details']!= '')
				{
					$user_data['is_business_details'] = $input_data['is_business_details'];
				}
				
				if($_FILES['profile_image']['name'] != "")
				{

					if(!file_exists('uploads/user/image'))
					{
						mkdir('uploads/user/image', 0777, true);
					}
					$img_name      = rand(11111,99999).uniqid();
					$ext           = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
					$profile_image = $img_name.".".$ext;

					move_uploaded_file($_FILES['profile_image']['tmp_name'], "uploads/user/image/" . $profile_image);
					$old_image_data= $this->mdl_common->get_record('tbl_user', "user_id = '".$input_data['user_id']."'", 'profile_image')->row_array();
					if(file_exists($old_image_data['profile_image']) && strpos($old_image_data['profile_image'],'http') !== true)
					unlink($old_image_data['profile_image']);

					$user_data['profile_image'] = "uploads/user/image/".$profile_image;

					//print_r($user_data);die;
				}
				$profile_create = $this->mdl_common->update_record('tbl_user',"user_id = '".$input_data['user_id']."'",$user_data);
				if($profile_create){
					$user_data = $this->mdl_user->get_user_details($input_data['user_id']);
					$user_data['access_token'] = $input_data['access_token'];
					
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					$response[RESPONSE_MESSAGE] = _USER_PROFILE_CREATED;
					$response[RESPONSE_DATA] = $user_data;

				}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
				}
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			    $response[RESPONSE_MESSAGE] = $exist_response[RESPONSE_MESSAGE];
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function add_new_referral_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);

		$input_data['referral_mobile_no'] = $this->input->post('referral_mobile_no',TRUE);
		$input_data['re_country_code'] = $this->input->post('re_country_code',TRUE);
		$input_data['business_mobile_no'] = $this->input->post('business_mobile_no',TRUE);
		$input_data['bu_country_code'] = $this->input->post('bu_country_code',TRUE);

		$input_data['referral_name'] = $this->input->post('referral_name',TRUE);
		$input_data['business_name'] = $this->input->post('business_name',TRUE);

		$input_data['message'] = $this->input->post('message',TRUE);
		//$input_data['is_direct_contact'] = $this->input->post('is_direct_contact',TRUE);

		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','referral_mobile_no','business_mobile_no','re_country_code','bu_country_code','message','referral_name','business_name');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			if($input_data['business_mobile_no']!= $input_data['referral_mobile_no'])
			{


				$id = rand(00000000,999999999);
				$new_referral_info = array(
					'referrer_id' => $input_data['user_id'],
					'referral_mobile_no' => $input_data['referral_mobile_no'],
					'referral_country_code' => $input_data['re_country_code'],
					'business_mobile_no' => $input_data['business_mobile_no'],
					'business_country_code' => $input_data['bu_country_code'],
					'message' => $input_data['message'],
					'referral_name'=>$input_data['referral_name'],
					'business_name'=>$input_data['business_name'],
					'assign_referral_id' =>$id,
					'date_added' =>date("Y-m-d H:i:s")

				);
				if($input_data['timezone']!= '')
				{
					$new_referral_info['timezone'] = $input_data['timezone'];
				}
				$info_id = $this->mdl_common->insert_record('tbl_new_referrer_info',$new_referral_info); 

				if($input_data['referral_mobile_no']!= '')
				{
					$check_referral_exist = $this->db->get_where('tbl_user',array('mobile_no'=>$input_data['referral_mobile_no'],'is_del'=>0))->row_array();
					//echo $this->db->last_query();die;
					if(!empty($check_referral_exist))
					{
						$referral_id = $check_referral_exist['user_id'];
					}
					else
					{
						$referral_id = 0;
					}
					//echo $referral_id;die;

				}
				if($input_data['business_mobile_no']!= '')
				{
					$check_business_exist = $this->db->get_where('tbl_user',array('mobile_no'=>$input_data['business_mobile_no'],'is_del'=>0))->row_array();
					if(!empty($check_business_exist))
					{
						$business_id = $check_business_exist['user_id'];
					}
					else
					{
						$business_id = 0;
					}

				}
				if($info_id)
				{
					$manage_referral = array(
					'info_id'=> $info_id,
					'referrer_id' => $input_data['user_id'],
					'referral_id' => (@$referral_id) ? $referral_id : 0,
					'business_id' =>(@$business_id) ? $business_id : 0,
					'message' => $input_data['message'],
					'assign_referral_id' => $id,
					'date_added' => date('Y-m-d H:i:s'),

				   );
					if($input_data['timezone']!= '')
					{
						$manage_referral['timezone'] = $input_data['timezone'];
					}
					$manage_id = $this->mdl_common->insert_record('tbl_manage_referrer',$manage_referral);
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					$response[RESPONSE_MESSAGE] = __REFERRAL_ADDED;
				}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
				}
				
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __BOTH_MOBILE_NUMBER_MUST_BE_DIFFERENT;
			}
		}	
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function referral_list_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['is_flag'] = $this->input->post('is_flag',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['offset'] = $this->input->post('offset',TRUE);
		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$refferal_list_count = count($this->mdl_user->get_referral_list($input_data));
			$refferal_list = $this->mdl_user->get_referral_list($input_data,$input_data['offset']);
			if(!empty($refferal_list))
			{
				$next_offset = $input_data['offset'] + PER_PAGE_LIST;
        		if($refferal_list_count > $next_offset)
        		{
        			$response[RESPONSE_NEXT_OFFSET] = $next_offset;
        		}
        		else
        		{
        			$response[RESPONSE_NEXT_OFFSET] = -1;
        		}
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = "Refferal list";
				$response[RESPONSE_DATA] = $refferal_list;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERAL_NOT_FOUND;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] =RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function business_user_view_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$get_business_view_details = $this->mdl_user->get_business_user_view($input_data);
			if(!empty($get_business_view_details))
			{
				$update_status = $this->mdl_common->update_record('tbl_manage_referrer', array('manage_id'=> $input_data['manage_id'], 'info_id'=> $input_data['info_id']), array('business_status'=>2));
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = "Business view details";
				$response[RESPONSE_DATA] = $get_business_view_details;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERAL_NOT_FOUND;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function refferal_user_view_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$get_referral_view_details = $this->mdl_user->get_referral_user_view($input_data);
			if(!empty($get_referral_view_details))
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = "Refferal view details";
				$response[RESPONSE_DATA] = $get_referral_view_details;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERAL_NOT_FOUND;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function referrer_user_view_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$get_referrer_view_details = $this->mdl_user->get_referrer_user_view($input_data);
			if(!empty($get_referrer_view_details))
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = "Refferer view details";
				$response[RESPONSE_DATA] = $get_referrer_view_details;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERAL_NOT_FOUND;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function manage_status_by_business_user_post()
	{
			$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{

		}
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);

	}
	public function manage_status_by_referral_user_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);
		$input_data['referral_status'] = $this->input->post('referral_status',TRUE); // 0= new , 1= cancel , 2= view , 3= job in progress , 4= job complete	

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id','referral_status');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'referral_id' => $input_data['user_id'],'is_del'=>0))->row_array(); 
			if(!empty($exist_referrer_data))
			{
				if($input_data['referral_status']!= '1') // check for cancel
				{
					$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referral_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('referral_status'=> $input_data['referral_status']));
						if($set_status)
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
							$response[RESPONSE_MESSAGE] = _SET_REFERRAL_STATUS;
						}
						else
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
						}
				}
				else
				{
					if($exist_referrer_data['business_status']!= '3') // check for job in progress
					{
						$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referral_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('referral_status'=> $input_data['referral_status']));
						if($set_status)
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
							$response[RESPONSE_MESSAGE] = _SET_REFERRAL_STATUS;
						}
						else
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
						}
					}
					else
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
						$response[RESPONSE_MESSAGE] = __STATUS_NOT_UPDATED_TO_CANCEL;
					}
				}
				/*if($exist_referrer_data['business_status'] != '3')
				{
					$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referral_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('referral_status'=> $input_data['referral_status']));
					if($set_status)
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
						$response[RESPONSE_MESSAGE] = _SET_REFERRAL_STATUS;
					}
					else
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
						$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
					}
				}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __STATUS_NOT_UPDATED_TO_CANCEL;
				}*/
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERRAL_NOT_EXIST;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);

	}

}
?>