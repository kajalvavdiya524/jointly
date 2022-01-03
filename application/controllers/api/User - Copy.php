<?php
defined ( 'BASEPATH' ) or exit ( 'No direct script access allowed' );
require APPPATH.'/libraries/api/REST_Controller.php';
require APPPATH.'/libraries/api/Format.php';
require APPPATH . '/libraries/stripe/init.php';

 use Restserver\Libraries\REST_Controller;
 class User Extends REST_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('api/mdl_user');
		Stripe\stripe::setApiKey(STRIPE_API_KEY);
		
	}
	
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
				array('value'           =>$input_data['mobile_no'],
					'db_key'          =>'mobile_no',
					'message_if_exist'=>__MOBILE_ALREADY_EXIST)),$user_id='');

		if($exist_response[RESPONSE_FLAG])
		{
			//$otp = rand(1000, 9999);
			$otp = '1234';

			$get_member = $this->db->get_where('tbl_manage_team_member',array('mobile_no'=>$input_data['mobile_no'],'is_del'=>0))->row_array();
			if(!empty($get_member)) {
				$role = 'team member';
			}
			else {
				$role = 'manager';
			}

			$user_data = array(
				'country_code' => $input_data['country_code'],
				'mobile_no'=>$input_data['mobile_no'],
				'date_added'=> date('Y-m-d H:i:s'),
				'otp' => $otp,
				'role' => $role
			);
			$user_id = $this->mdl_common->insert_record('tbl_user',$user_data);
			if($user_id)
			{
				$user_data1 = array(
					'user_id' => $user_id,
					'mobile_no'=>$input_data['mobile_no'],
					'is_verify'=>'0',
					'is_profile_complete'=>'0'
				);

				$to = $user_data['country_code'].''.$user_data['mobile_no'];
				$get_verify_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'VERIFICATION_CODE','send_to'=>'VERIFICATION_CODE','when_send'=>'VERIFICATION_CODE','type'=>'VERIFICATION_CODE'))->row_array();
						$verify_msg = str_ireplace('<xxxx>',$user_data['otp'], $get_verify_msg['message']);

				$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), $verify_msg);

				//account flag add
				$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$user_id,'is_del'=>0))->row_array(); 
        		if(!empty($get_Referrer_account))
        		{
        			$user_data1['is_account_added'] = 1;
        			$user_data1['account_token'] = $get_Referrer_account['customer_token'];
        		}
        		else
        		{
        			$user_data1['is_account_added'] = 0;
        			$user_data1['account_token'] = '';
        		}

				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = _OTP_SEND_TO_MOBILE_NO;
				$response[RESPONSE_DATA] = $user_data1;
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
			$user_data = $this->db->get_where('tbl_user',Array('user_id'=> $user_id, 'is_del' => 0))->row_array();
			if(!empty($user_data))
			{	
				//$otp = rand(1000, 9999);
				$otp = '1234';

				$get_member = $this->db->get_where('tbl_manage_team_member',array('mobile_no'=>$input_data['mobile_no'],'is_del'=>0))->row_array();
				if(!empty($get_member)) {
					$role = 'team member';
				}
				else {
					$role = 'manager';
				}
					
				$up_data = array(
					'country_code' => $input_data['country_code'],
					'mobile_no'=>$input_data['mobile_no'],
					'date_added'=> date('Y-m-d H:i:s'),
					'otp' => $otp,
					'is_verify'=>'0',
					'is_profile_complete'=> $user_data['is_profile_complete'],
					'role' => $role
				);
				$update_user = $this->mdl_common->update_record('tbl_user','user_id = "'.$user_id.'"',$up_data);

				$to = $up_data['country_code'].''.$up_data['mobile_no'];

				$get_verify_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'VERIFICATION_CODE','send_to'=>'VERIFICATION_CODE','when_send'=>'VERIFICATION_CODE','type'=>'VERIFICATION_CODE'))->row_array();
				$verify_msg = str_ireplace('<xxxx>',$up_data['otp'], $get_verify_msg['message']);

				$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), $verify_msg);	
				if($update_user)
				{
					$user_data = array(
						'user_id' => $user_data['user_id'],	
						'mobile_no'=>$input_data['mobile_no'],
						'is_verify'=>'0',
						'is_profile_complete'=> $user_data['is_profile_complete'],
					);

					//account flag add
					$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$user_data['user_id'],'is_del'=>0))->row_array(); 
					if(!empty($get_Referrer_account))
					{
						$user_data['is_account_added'] = 1;
						$user_data['account_token'] = $get_Referrer_account['customer_token'];
					}
					else
					{
						$user_data['is_account_added'] = 0;
						$user_data['account_token'] = '';
					}
					
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
		}	
		$this->response($response,200);
	}
	function user_profile_post(){
		$response = array();
        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token',TRUE);
         $input_data['is_card_added'] = $this->input->post('is_card_added',TRUE);
        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
         
        $input_parameter = array('user_id','access_token','is_card_added');
        $validation = $this->ParamValidation($input_parameter, $input_data);
         
        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        	$this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
        if($res_validate_token['flag'] > 0)
        {
        	$user_profile = $this->mdl_user->get_user_details($input_data['user_id']);
        	if(!empty($user_profile))
        	{
        		$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
        		if(!empty($get_Referrer_bank))
        		{
        			$user_profile['is_bank_added'] = 1;
        		}
        		else
        		{
        			$user_profile['is_bank_added'] = 0;
        		}

        		$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
        		if(!empty($get_Referrer_account))
        		{
        			$user_profile['is_account_added'] = 1;
        			$user_profile['account_token'] = $get_Referrer_account['customer_token'];
        		}
        		else
        		{
        			$user_profile['is_account_added'] = 0;
        			$user_profile['account_token'] = '';
        		}
        		if($input_data['is_card_added']!=''){
        			$user_profile['is_card_added'] = $input_data['is_card_added'];
        		}

        		//$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
		        	
        		$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                $response[RESPONSE_MESSAGE] = _USER_PROFILE;
                       // $user_data[ACCESS_TOKEN] = $input_data['access_token']; 
                $user_profile[ACCESS_TOKEN] = $input_data['access_token'];
                $response[RESPONSE_DATA] = $user_profile;
        	}
        	else
        	{
        		$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
        		$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
        	}
        }
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }
        $this->response($response,200);
	}
	function get_user_profile_post()
	{
		$response = array();
        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token',TRUE);
        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
         
        $input_parameter = array('user_id','access_token');
        $validation = $this->ParamValidation($input_parameter, $input_data);
         
        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        	$this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
        if($res_validate_token['flag'] > 0)
        {
        	$user_profile = $this->mdl_user->get_user_details($input_data['user_id']);
        	if(!empty($user_profile))
        	{
        		$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
        		if(!empty($get_Referrer_bank))
        		{
        			$user_profile['is_bank_added'] = 1;
        		}
        		else
        		{
        			$user_profile['is_bank_added'] = 0;
        		}

        		$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
        		if(!empty($get_Referrer_account))
        		{
        			$user_profile['is_account_added'] = 1;
        			$user_profile['account_token'] = $get_Referrer_account['customer_token'];
        		}
        		else
        		{
        			$user_profile['is_account_added'] = 0;
        			$user_profile['account_token'] = '';
        		}
        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
		        		if(!empty($exist_card))
		        		{
		        			$user_profile['is_card_added'] = 1;
		        		}
		        		else
		        		{
		        			$user_profile['is_card_added'] = 0;
		        		}
        		$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                $response[RESPONSE_MESSAGE] = _USER_PROFILE;
                       // $user_data[ACCESS_TOKEN] = $input_data['access_token']; 
                $user_profile[ACCESS_TOKEN] = $input_data['access_token'];
                $response[RESPONSE_DATA] = $user_profile;
        	}
        	else
        	{
        		$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
        		$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
        	}
        }
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
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
				//$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), "Thank you for signin in Referral App ! Use OTP : " . $udata['otp'] . " to valid for 15 minutes from the request. Please do not share OTP with anyone");
            $get_verify_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'VERIFICATION_CODE','send_to'=>'VERIFICATION_CODE','when_send'=>'VERIFICATION_CODE','type'=>'VERIFICATION_CODE'))->row_array();
						$verify_msg = str_ireplace('<xxxx>',$udata['otp'], $get_verify_msg['message']);

			$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), $verify_msg);

				//$this->mdl_common->send_sinch_sms(str_replace(" ","",$to), "Your verification code is: " . $udata['otp']);

			//$this->mdl_common->send_sms(str_replace(" ","",$user_data['mobile_no']), "Thank you for signup in " . PROJECT_NAME . "! Use OTP : " . $user_data['otp_for_register'] . " to valid for 15 minuties from the request. Please do not share OTP with anyone");
			
           $user_data = array(
				//'country_code' => $input_data['country_code'],
				'user_id' => $user_data['user_id'],	
				'mobile_no'=>$user_data['mobile_no'],
				'is_verify'=>'0',
				'is_profile_complete'=> $user_data['is_profile_complete'],
				'otp' => $udata['otp'],
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
                $udata['is_verify'] = "1";
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


						$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($get_Referrer_bank))
			        		{
			        			$user_data['is_bank_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_bank_added'] = 0;
			        		}

						$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
		        		if(!empty($get_Referrer_account))
		        		{
		        			$user_data['is_account_added'] = 1;
		        			$user_data['account_token'] = $get_Referrer_account['customer_token'];
		        		}
		        		else
		        		{
		        			$user_data['is_account_added'] = 0;
		        			$user_data['account_token'] = '';
		        		}	

		        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
		        		if(!empty($exist_card))
		        		{
		        			$user_data['is_card_added'] = 1;
		        		}
		        		else
		        		{
		        			$user_data['is_card_added'] = 0;
		        		}

						$userData =  $this->db->get_where('tbl_user',array('user_id' => $input_data['user_id'],'is_del' => 0))->row_array(); 
						$user_data['is_activeted'] = $userData['is_flag'];

						if($user_data['subscription_level'] == 1) {
							$subscription_level = 'Bronze';
						}
						elseif($user_data['subscription_level'] == 2) {
							$subscription_level = 'Silver';
						}
						else {
							$subscription_level = 'Gold';
						}
						$user_data['subscription_level'] = $subscription_level;

						$settingData =  $this->db->get_where('tbl_settings')->row_array(); 
						$user_data['monthly_referral_fee'] = $settingData['monthly_referral_fee'];
						$user_data['monthly_stripe_fee'] = $settingData['monthly_stripe_fee'];

                        $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                        $response[RESPONSE_MESSAGE] = _USER_MOB_VER_SUCCESS; 
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
		$input_data['business_mobile_number'] = $this->input->post('business_mobile_number',TRUE);
		$input_data['address'] = $this->input->post('address',TRUE);
		$input_data['city'] = $this->input->post('city',TRUE);
		$input_data['state'] = $this->input->post('state',TRUE);
		$input_data['postal_code'] = $this->input->post('postal_code',TRUE);
		$input_data['abn'] = $this->input->post('abn',TRUE);

		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','first_name','last_name','mobile_no','email_id','city');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_response = $this->mdl_user->check_user_already_exist(
			array(
				array('value'           =>$input_data['mobile_no'],
					'db_key'          =>'mobile_no',
					'message_if_exist'=>__MOBILE_ALREADY_EXIST)),$input_data['user_id']);

			if($exist_response[RESPONSE_FLAG])
			{
				$user_data = array(
					'first_name' => $input_data['first_name'],
					'last_name' => $input_data['last_name'],
					'mobile_no' => $input_data['mobile_no'],
					'address' => $input_data['address'],
					'city' => $input_data['city'],
					'state' => $input_data['state'],
					'postal_code' => $input_data['postal_code'],
					'is_profile_complete'=>1,
					'date_modified' => date('Y-m-d H:i:s'),
					'email_id' => $input_data['email_id']
				);

				if($input_data['business_phone_number'] != '') {
					$user_data['business_phone_number'] = $input_data['business_phone_number'];
				}

				if($input_data['is_notification']!= '')
				{
					$user_data['is_notification'] = $input_data['is_notification'];
				}
				if($input_data['is_business_details']!= '0')
				{
					$user_data['business_name'] = $input_data['business_name'];
					$user_data['abn'] = $input_data['abn'];
				}
				else
				{
					$user_data['business_name'] = "";
					$user_data['abn']= "";
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
				}

				$user_data_toekn = $this->mdl_common->get_record('tbl_user',Array('user_id' => $input_data['user_id'],'is_del' => 0))->row_array();
				if($user_data_toekn['customer_token'] == '') {
					try {
						$customer = Stripe\Customer::create(array(
								"description" => 'Create stripe account',
								"email" => $input_data['email_id'],
								"name" => $input_data['first_name'].' '.$input_data['last_name'],
								"phone" => $user_data_toekn['country_code'].' '.$input_data['mobile_no']
							)
						);
						
						$customer_token = $customer->id;
					} catch (Exception $e) {
						$error = $e->getMessage();
						$customer_token = '';
					}
					if($customer_token!= '')
					{
						$user_data['customer_token'] = $customer_token;
					}
					$msg = _USER_PROFILE_CREATED;
				}
				else {
					try {
						$customer = Stripe\Customer::update($user_data_toekn['customer_token'],array(
								// "description" => 'Create stripe account',
								"email" => $input_data['email_id'],
								"name" => $input_data['first_name'].' '.$input_data['last_name'],
								"phone" => $user_data_toekn['country_code'].' '.$input_data['mobile_no']
							)
						);
						$customer_token = $customer->id;
						if($customer_token!= '') {
							$user_data['customer_token'] = $customer_token;
						}
					} catch (Exception $e) {
						$error = $e->getMessage();
						$customer_token = '';
					}
					$msg = _USER_PROFILE_UPDATED;
				}

				if($user_data_toekn['role'] == 'manager') {
					$user_data['is_flag'] = '0';
				}

				$profile_create = $this->mdl_common->update_record('tbl_user',"user_id = ".$input_data['user_id']."",$user_data);
				if($profile_create)
				{
					
					$user_data = $this->mdl_user->get_user_details($input_data['user_id']);
					$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
					if(!empty($get_Referrer_bank))
					{
						$user_data['is_bank_added'] = 1;
					}
					else
					{
						$user_data['is_bank_added'] = 0;
					}

					$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
					if(!empty($get_Referrer_account))
					{
						$user_data['is_account_added'] = 1;
						$user_data['account_token'] = $get_Referrer_account['customer_token'];
					}
					else
					{
						$user_data['is_account_added'] = 0;
						$user_data['account_token'] = '';
					}
					$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['user_id'],'is_del'=>0))->row_array(); 
					if(!empty($exist_card))
					{
						$user_data['is_card_added'] = 1;
					}
					else
					{
						$user_data['is_card_added'] = 0;
					}
					$user_data['access_token'] = $input_data['access_token'];

					$userData =  $this->db->get_where('tbl_user',array('user_id' => $input_data['user_id'],'is_del' => 0))->row_array(); 
					$user_data['is_activeted'] = $userData['is_flag'];
					
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					$response[RESPONSE_MESSAGE] = $msg;
					$response[RESPONSE_DATA] = $user_data;

				}else{
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
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}

	function logout_post() 
    {
        $response = array();

        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['device_token'] = $this->input->post('device_token', TRUE);
        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $input_parameter = array('user_id', 'device_token');

        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);

        $user_push = $this->mdl_common->delete_record("tbl_push", Array('user_id' => $input_data['user_id'],'device_token'=>$input_data['device_token']));
        $user_access_token = $this->mdl_common->delete_record("tbl_access_token", Array('user_id' => $input_data['user_id'], 'device_token'=>$input_data['device_token']));

        if ($user_push && $user_access_token) {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
            $response[RESPONSE_MESSAGE] = _USER_LOGGED_OUT;
        } else {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
            $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
        }

        $this->response($response, 200);
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
				$id = rand(time(),999999999);
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
					//print_r($manage_referral);die;

					if($input_data['timezone']!= '')
					{
						$manage_referral['timezone'] = $input_data['timezone'];
					}
					$manage_id = $this->mdl_common->insert_record('tbl_manage_referrer',$manage_referral);
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					$response[RESPONSE_MESSAGE] = __REFERRAL_ADDED;

					// Send push after response
					header ( 'Content-Type: application/json' );
					echo json_encode ( $response );
					$size = ob_get_length ();
					header ( "Content-Length: $size" );
					header ( 'Connection: close' );
					header ( "Content-Encoding: none\r\n" );
					ob_end_flush ();
					ob_flush ();
					flush ();

					if(session_id ())
					session_write_close ();

					$referrer_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del'      => 0))->row_array();
					//print_r($referrer_data);die;
					$referrer_name = $referrer_data['first_name'].' '.$referrer_data['last_name'];
					$referrer_location = $referrer_data['city'];

					//print_r($manage_referral['referral_id']);die;
					if($manage_referral['referral_id'] == '0')
					{
						$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'CUSTOMER','when_send'=>'NEW_REFERRAL_CREATED','type'=>'SMS_IF_CUSTOMER_IS_NOT_A_REGISTERED_USER'))->row_array();
						$push_msg = str_ireplace('<referrer_name>', $referrer_name, $get_push_msg['message']);

						//	<referrer_name> has created a Referral on your behalf. Please download the free Referral App to view the details https://www.google.com
						//echo "123";die;
						 $r_to = $input_data['re_country_code'].''.$input_data['referral_mobile_no'];
						$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$r_to),$push_msg);
					}
					else
					{	
						$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'PUSH','send_to'=>'CUSTOMER','when_send'=>'NEW_REFERRAL_CREATED','type'=>'PUSH_NOTIFICATION_IF_CUSTOMER_IS_ALREADY_REGISTERED'))->row_array();
						$push_msg = str_ireplace('<referrer_name>', $referrer_name, $get_push_msg['message']);
						$this->db->select("P.user_id,register_id,P.device_type");
	                    // $this->db->where('P.device_type',1);
	                    $this->db->where('P.user_id', $manage_referral['referral_id']);
	                    $this->db->where('U.is_notification',0);
	                    $this->db->from("tbl_push AS P");
	                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
	                    $this->db->order_by('P.push_id',"DESC");
	                    $register_token = $this->db->get()->result_array();
	               		
	                    if (!empty($register_token)) {
	                    	$push_data_len = count($register_token);
							$android_device_arr = array();
							$ios_device_arr = array();

							$n_data = array(
								'user_id' => $manage_referral['referral_id'],
								'message' => $push_msg,
								'notification_post_user_id'=> $input_data['user_id'],
								'is_flag'=> 3,
								'manage_id'=> $manage_id,
								'info_id'=>$info_id,
								'notification'=> 'new_referral_by_referrer',
								'notification_type'=>1,
								'date_added' => date('Y-m-d H:i:s')
							);
							$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
	                    	foreach ($register_token as $value) {
	                    		if($value['device_type'] == 0)
								{
									array_push($android_device_arr, $value['register_id']);
								}
								else
								{
									array_push($ios_device_arr, $value['register_id']);
								}
		                    }   
	                        /*$push_data['multiple'] = 0;
	                        $push_data['register_id'] = trim($register_token['register_id']);
	                        $push_data['device_type'] = $register_token['device_type'];*/
	                        $message_array['custom_data']['message'] =  $push_msg;
	                        $message_array['custom_data']['notification_type'] = 1;
	                        $message_array['custom_data']['is_flag'] = 3;
	                        $message_array['custom_data']['info_id'] = $info_id;
 							$message_array['custom_data']['manage_id'] = $manage_id;
	                        $message_array['custom_data']['notification'] = 'new_referral_by_referrer';

							$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $manage_referral['referral_id']))->num_rows();
							$message_array['custom_data']['count'] = $notification_count;
							$message_array['count'] = $notification_count;
							$message_array['custom_data']['notification_id'] = $notification_id;
	                    	
	                    	if(!empty($android_device_arr)){
								$push_data['device_type'] = 0;
								$push_data['register_id'] = $android_device_arr;
								$push_data['multiple'] = 1;
								
								$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
		                        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
							}
							if(!empty($ios_device_arr)){
								$push_data['device_type'] = 1;
								$push_data['register_id'] = $ios_device_arr;
								$push_data['multiple'] = 1;
								
								$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
		                        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
							}
	                    }
					}

					$business_f_name = explode(" ",$input_data['business_name']);
					if($manage_referral['business_id'] == '0')
					{
						$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'BUSINESS','when_send'=>'NEW_REFERRAL_CREATED','type'=>'SMS_IF_BUSINESS_IS_NOT_A_REGISTERED_USER'))->row_array();
						//$push_msg = str_ireplace('<referrer_name>', $referrer_name, $get_push_msg['message']);
						$push_msg=str_ireplace('<business_user_first_name>',ucfirst($business_f_name[0]),str_ireplace('<referrer_location>',$referrer_location,$get_push_msg['message']));
						//Congratulations <business_user_first_name>, you have received a new referral from somebody in <referrer_location>. Please download the free Referral App to view the details https://www.google.com.

						 $b_to = $input_data['bu_country_code'].''.$input_data['business_mobile_no'];
						$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
					}
					else
					{
						$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $manage_referral['business_id'],'is_del'      => 0))->row_array();
						$b_name = $business_data['first_name'];
						$this->db->select("P.user_id,register_id,P.device_type");
	                  	//  $this->db->where('P.device_type',1);
	                    $this->db->where('P.user_id', $manage_referral['business_id']);
	                    $this->db->where('U.is_notification',0);
	                    $this->db->from("tbl_push AS P");
	                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
	                    $this->db->order_by('P.push_id',"DESC");
	                    $register_token = $this->db->get()->result_array();
	                	//  print_r($register_token);die;
	                    if (!empty($register_token)) {
	                    	$push_data_len = count($register_token);
							$android_device_arr = array();
							$ios_device_arr = array();
	                    	$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'PUSH','send_to'=>'BUSINESS','when_send'=>'NEW_REFERRAL_CREATED','type'=>'PUSH_NOTIFICATION_IF_BUSINESS_IS_ALREADY_REGISTERED'))->row_array();
							//$push_msg = str_ireplace('<referrer_name>', $referrer_name, $get_push_msg['message']);
							$push_msg=str_ireplace('<business_user_first_name>',$b_name,str_ireplace('<referrer_location>',$referrer_location,$get_push_msg['message']));
							$check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$manage_referral['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
							if(!empty($check_complete_status))
	                        {
	                        	$is_allow_visible = 1;
	                        }
	                        else
	                        {
	                        	$is_allow_visible = 0;
	                        }

							$n_data = array(
				
							'user_id' => $manage_referral['business_id'],
							'message' => $push_msg,
							'notification_post_user_id'=> $input_data['user_id'],
							'is_flag'=> 1,
							'manage_id'=> $manage_id,
							'info_id'=>$info_id,
							'notification'=> 'new_referral_by_referrer',
							'notification_type'=>1,
							'is_allow_visible' => $is_allow_visible,
							'date_added' => date('Y-m-d H:i:s')
							
							);
	                        $notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);

	                        foreach ($register_token as $value) {
								if($value['device_type'] == 0)
								{
									array_push($android_device_arr, $value['register_id']);
								}
								else
								{
									array_push($ios_device_arr, $value['register_id']);
								}
						    } 
	                        /*$push_data['multiple'] = 0;
	                        $push_data['register_id'] = trim($register_token['register_id']);
	                        $push_data['device_type'] = $register_token['device_type'];*/

	                   		  //   $push_msg = "Congratulations ".$b_name.", you have received a new business referral from somebody in ".$referrer_location;
	                        $message_array['custom_data']['message'] =  $push_msg;
	                        $message_array['custom_data']['notification_type'] = 1;
	                        $message_array['custom_data']['is_flag'] = 1;
	                        $message_array['custom_data']['info_id'] = $info_id;
 							$message_array['custom_data']['manage_id'] = $manage_id;
	                        $message_array['custom_data']['notification'] = 'new_referral_by_referrer';
	                        $message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

							//echo $this->db->last_query();die;
							$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $manage_referral['business_id']))->num_rows();
							$message_array['custom_data']['count'] = $notification_count;
							$message_array['custom_data']['notification_id'] = $notification_id;
	                      //  $message_array['custom_data']['msg_count'] = $msg_count;
	                       
	                       if(!empty($android_device_arr)){
								$push_data['device_type'] = 0;
								$push_data['register_id'] = $android_device_arr;
								$push_data['multiple'] = 1;
								
								
								$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
						        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
								
							}
							if(!empty($ios_device_arr)){
								$push_data['device_type'] = 1;
								$push_data['register_id'] = $ios_device_arr;
								$push_data['multiple'] = 1;
								
								$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
						        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
							}
	                    }
					}
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
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
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
				if($input_data['is_flag'] == '1'){
					$response[RESPONSE_MESSAGE] = __BUSINESS_REFERAL_NOT_FOUND;

				}elseif ($input_data['is_flag'] == '2') {
					$response[RESPONSE_MESSAGE] = __SEND_REFERAL_NOT_FOUND;
					# code...
				}elseif ($input_data['is_flag'] == '3') {
					$response[RESPONSE_MESSAGE] = __MY_REFERAL_NOT_FOUND;
					# code...
				}
				//$response[RESPONSE_MESSAGE] = __REFERAL_NOT_FOUND;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] =$res_validate_token['flag'];
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
			//$status_complete_exist = $this->db->get_where('tbl_manage_referrer',"business_id=".$input_data['user_id']." AND info_id !=".$input_data['info_id']." AND(business_status = 4 AND business_status = 6) AND is_del=0")->result_array();
			//echo $this->db->last_query();die;
			/*if(empty($status_complete_exist))
			{*/
				$get_business_view_details = $this->mdl_user->get_business_user_view($input_data);
				if(!empty($get_business_view_details))
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;	
					$response[RESPONSE_MESSAGE] = "Business view details";
					$response[RESPONSE_DATA] = $get_business_view_details;

					//$update_status = $this->mdl_common->update_record('tbl_manage_referrer', array('manage_id'=> $input_data['manage_id'], 'info_id'=> $input_data['info_id']), array('business_status'=>2));

					// Send push after response
						header ( 'Content-Type: application/json' );
						echo json_encode ( $response );
						$size = ob_get_length ();
						header ( "Content-Length: $size" );
						header ( 'Connection: close' );
						header ( "Content-Encoding: none\r\n" );
						ob_end_flush ();
						ob_flush ();
						flush ();

						if(session_id ())
						session_write_close ();

						$check_update_status = $this->db->get_where('tbl_manage_referrer',Array('manage_id'=> $input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'      => 0))->row_array();
						if($check_update_status['business_status'] < 1)
						{
							$update_status = $this->mdl_common->update_record('tbl_manage_referrer', array('manage_id'=> $input_data['manage_id'], 'info_id'=> $input_data['info_id']), array('business_status'=>2,'referral_status'=>2));
							if($check_update_status['business_id']!= '0')
							{
								$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['business_id'],'is_del' => 0))->row_array();
								$business_name = $business_data['first_name'].' '.$business_data['last_name'];
							}
							else
							{
								$info_data = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_update_status['info_id'],'is_del' => 0))->row_array();
								$business_name = $info_data['business_name'];
							}
							//$push_msg =  $business_name." has viewed your Referral.";
							$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER','when_send'=>'BUSINESS_VIEWS_REFERRAL_FOR_FIRST_TIME','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
						 	$push_msg = str_ireplace('<business_name>', $business_name, $get_push_msg['message']);

							//print_r($referrer_data);die;
							//$referrer_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del'      => 0))->row_array();
							//$referrer_name = $referrer_data['first_name'].' '.$referrer_data['last_name'];

							//print_r($manage_referral['referral_id']);die;
							
							$this->db->select("P.user_id,register_id,P.device_type");
		                    //$this->db->where('P.device_type',1);
		                    $this->db->where('P.user_id', $check_update_status['referrer_id']);
		                    $this->db->where('U.is_notification',0);
		                    $this->db->from("tbl_push AS P");
		                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
		                    $this->db->order_by('P.push_id',"DESC");
		                    $register_token = $this->db->get()->result_array();
		               	 //  print_r($register_token);die;
		                    if (!empty($register_token)) {
		                        $push_data_len = count($register_token);
								$android_device_arr = array();
								$ios_device_arr = array();
		                        /*$push_data['multiple'] = 0;
		                        $push_data['register_id'] = trim($register_token['register_id']);
		                        $push_data['device_type'] = $register_token['device_type'];*/
		                         $n_data = array(
									'user_id' => $check_update_status['referrer_id'],
									'message' => $push_msg,
									'is_flag'=> 2,
									'manage_id'=> $input_data['manage_id'],
									'info_id'=>$input_data['info_id'],
									'notification'=> 'referral_view_by_business',
									'notification_post_user_id'=> $input_data['user_id'],
									'notification_type'=>2,
									'date_added' => date('Y-m-d H:i:s')
								);
								$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
								foreach ($register_token as $value) {
									if($value['device_type'] == 0)
									{
										array_push($android_device_arr, $value['register_id']);
									}
									else
									{
										array_push($ios_device_arr, $value['register_id']);
									}
							    } 
		                        
		                        $message_array['custom_data']['message'] =  $push_msg;
		                        $message_array['custom_data']['notification_type'] = 2;
		                        $message_array['custom_data']['notification'] = 'referral_view_by_business';
		                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        $message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        $message_array['custom_data']['is_flag'] = 2;

		                       
								//echo $this->db->last_query();die;
								$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_update_status['referrer_id']))->num_rows();
								$message_array['custom_data']['count'] = $notification_count;
								$message_array['custom_data']['notification_id'] = $notification_id;
		                      //  $message_array['custom_data']['msg_count'] = $msg_count;
		                       
		                       if(!empty($android_device_arr)){
									$push_data['device_type'] = 0;
									$push_data['register_id'] = $android_device_arr;
									$push_data['multiple'] = 1;
									
									
									$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
							        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
									
								}
								if(!empty($ios_device_arr)){
									$push_data['device_type'] = 1;
									$push_data['register_id'] = $ios_device_arr;
									$push_data['multiple'] = 1;
									
									$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
							        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
								}
		                    }

		                    if($check_update_status['referral_id']!= '0')
		                    {
		                    	$this->db->select("P.user_id,register_id,P.device_type");
			                    //$this->db->where('P.device_type',1);
			                    $this->db->where('P.user_id', $check_update_status['referral_id']);
			                    $this->db->where('U.is_notification',0);
			                    $this->db->from("tbl_push AS P");
			                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
			                    $this->db->order_by('P.push_id',"DESC");
			                    $register_token = $this->db->get()->result_array();
			                //  print_r($register_token);die;
			                    if (!empty($register_token)) {
			                        $push_data_len = count($register_token);
									$android_device_arr = array();
									$ios_device_arr = array();

									$n_data = array(
										'user_id' => $check_update_status['referral_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 3,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'referral_view_by_business',
										'notification_type'=>2,
										'date_added' => date('Y-m-d H:i:s')
									);
									$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);

			                        /*$push_data['multiple'] = 0;
			                        $push_data['register_id'] = trim($register_token['register_id']);
			                        $push_data['device_type'] = $register_token['device_type'];*/

			                        foreach ($register_token as $value) {
										if($value['device_type'] == 0)
										{
											array_push($android_device_arr, $value['register_id']);
										}
										else
										{
											array_push($ios_device_arr, $value['register_id']);
										}
								    } 
			                        
			                        $message_array['custom_data']['message'] =  $push_msg;
			                        $message_array['custom_data']['notification_type'] = 2;
			                        $message_array['custom_data']['notification'] = 'referral_view_by_business';
			                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        	$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        	$message_array['custom_data']['is_flag'] = 3;

			                        
									//echo $this->db->last_query();die;
									$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_update_status['referral_id']))->num_rows();
									$message_array['custom_data']['count'] = $notification_count;
									$message_array['custom_data']['notification_id'] = $notification_id;
			                      //  $message_array['custom_data']['msg_count'] = $msg_count;
			                       
			                      if(!empty($android_device_arr)){
										$push_data['device_type'] = 0;
										$push_data['register_id'] = $android_device_arr;
										$push_data['multiple'] = 1;
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
										
									}
									if(!empty($ios_device_arr)){
										$push_data['device_type'] = 1;
										$push_data['register_id'] = $ios_device_arr;
										$push_data['multiple'] = 1;
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
									}
			                    }
		                    }
		                    else
		                    {
		                    	$referral_data = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_update_status['info_id'],'is_del' => 0))->row_array();
		                    	//print_r($referral_data);die;
		                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
		                    	$r_to = $referral_data['referral_country_code'].''.$referral_data['referral_mobile_no'];
								$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$r_to),$push_msg);
		                    }

						
						}
				}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __REFERAL_NOT_FOUND;
				}
			/*}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __VIEW_NEW_REFERRAL_RESTRICTION;
			}*/
		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
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
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
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
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function manage_status_by_referrer_user_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);
		$input_data['business_status'] = $this->input->post('business_status',TRUE); // 0= new , 1= cancel , 2= view , 3= job in progress , 4= job complete	

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id','business_status');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'referrer_id' => $input_data['user_id'],'is_del'=>0))->row_array();
			if(!empty($exist_referrer_data))
			{
				if($input_data['business_status'] == '7')
				{		
					$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referrer_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('business_status'=> $input_data['business_status'],'referral_status'=>$input_data['business_status']));
					if($this->db->affected_rows())
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
						$response[RESPONSE_MESSAGE] = _SET_REFERRER_STATUS;

						header ( 'Content-Type: application/json' );
						echo json_encode ( $response );
						$size = ob_get_length ();
						header ( "Content-Length: $size" );
						header ( 'Connection: close' );
						header ( "Content-Encoding: none\r\n" );
						ob_end_flush ();
						ob_flush ();
						flush ();

						if(session_id ())
						session_write_close ();

						$get_business_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();	
						if($get_business_status['business_status'] == '7')
						{
							$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'DISPUTE_THE_REFERRAL','send_to'=>'DISPUTE_THE_REFERRAL','when_send'=>'DISPUTE_THE_REFERRAL','type'=>'DISPUTE_THE_REFERRAL'))->row_array();
							$push_msg = str_ireplace('<referrer_id>', $get_business_status['assign_referral_id'], $get_push_msg['message']);
							//$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' has been disputed.';

							//$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' status has been updated to "Job In Progress" by business';
							if($get_business_status['business_id']!= '0')
							{
								$this->db->select("P.user_id,register_id,P.device_type");
								//$this->db->where('P.device_type',1);
								$this->db->where('P.user_id', $get_business_status['business_id']);
								$this->db->where('U.is_notification',0);
								$this->db->from("tbl_push AS P");
								$this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
								$this->db->order_by('P.push_id',"DESC");
								$register_token = $this->db->get()->result_array();
							//  print_r($register_token);die;
								if (!empty($register_token)) {
									
									$push_data_len = count($register_token);
									$android_device_arr = array();
									$ios_device_arr = array();

									$n_data = array(
						
									'user_id' => $get_business_status['business_id'],
									'message' => $push_msg,
									'notification_post_user_id'=> $input_data['user_id'],
									'is_flag'=> 1,
									'manage_id'=> $input_data['manage_id'],
									'info_id'=>$input_data['info_id'],
									'notification'=> 'set_status_to_dispute',
									'notification_type'=>12,
									'date_added' => date('Y-m-d H:i:s')
									
									);
									$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
									foreach ($register_token as $value) {
										if($value['device_type'] == 0)
										{
											array_push($android_device_arr, $value['register_id']);
										}
										else
										{
											array_push($ios_device_arr, $value['register_id']);
										}
								    }
									
									$message_array['custom_data']['message'] =  $push_msg;
									$message_array['custom_data']['notification_type'] = 12;
									$message_array['custom_data']['notification'] = 'set_status_to_dispute';
									$message_array['custom_data']['manage_id'] = $input_data['manage_id'];
									$message_array['custom_data']['info_id'] = $input_data['info_id'];
									$message_array['custom_data']['is_flag'] = 1;

									
									//echo $this->db->last_query();die;
									$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['business_id']))->num_rows();
									$message_array['custom_data']['count'] = $notification_count;
									$message_array['custom_data']['notification_id'] = $notification_id;
								  	
								  	if(!empty($android_device_arr)){
										$push_data['device_type'] = 0;
										$push_data['register_id'] = $android_device_arr;
										$push_data['multiple'] = 1;
										
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
										
									}
									if(!empty($ios_device_arr)){
										$push_data['device_type'] = 1;
										$push_data['register_id'] = $ios_device_arr;
										$push_data['multiple'] = 1;
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
									}
								}
							}
							else
							{
								$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_business_status['info_id'],'is_del' => 0))->row_array();
								//print_r($business_info);die;
								//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
								$bs_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
								$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$bs_to),$push_msg);
							}
							if($get_business_status['referral_id']!= '0')
							{
								$this->db->select("P.user_id,register_id,P.device_type");
								//$this->db->where('P.device_type',1);
								$this->db->where('P.user_id', $get_business_status['referral_id']);
								$this->db->where('U.is_notification',0);
								$this->db->from("tbl_push AS P");
								$this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
								$this->db->order_by('P.push_id',"DESC");
								$register_token = $this->db->get()->result_array();
							//  print_r($register_token);die;
								if (!empty($register_token)) {
									
									$push_data_len = count($register_token);
									$android_device_arr = array();
									$ios_device_arr = array();

									$n_data = array(
						
									'user_id' => $get_business_status['referral_id'],
									'message' => $push_msg,
									'notification_post_user_id'=> $input_data['user_id'],
									'is_flag'=> 3,
									'manage_id'=> $input_data['manage_id'],
									'info_id'=>$input_data['info_id'],
									'notification'=> 'set_status_to_dispute',
									'notification_type'=>12,
									'date_added' => date('Y-m-d H:i:s')
									
									);
									$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
									foreach ($register_token as $value) {
										if($value['device_type'] == 0)
										{
											array_push($android_device_arr, $value['register_id']);
										}
										else
										{
											array_push($ios_device_arr, $value['register_id']);
										}
								    }
									
									$message_array['custom_data']['message'] =  $push_msg;
									$message_array['custom_data']['notification_type'] = 12;
									$message_array['custom_data']['notification'] = 'set_status_to_dispute';
									$message_array['custom_data']['manage_id'] = $input_data['manage_id'];
									$message_array['custom_data']['info_id'] = $input_data['info_id'];
									$message_array['custom_data']['is_flag'] = 3;

									
									//echo $this->db->last_query();die;
									$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referral_id']))->num_rows();
									$message_array['custom_data']['count'] = $notification_count;
									$message_array['custom_data']['notification_id'] = $notification_id;
								 
								 	if(!empty($android_device_arr)){
										$push_data['device_type'] = 0;
										$push_data['register_id'] = $android_device_arr;
										$push_data['multiple'] = 1;
										
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
										
									}
									if(!empty($ios_device_arr)){
										$push_data['device_type'] = 1;
										$push_data['register_id'] = $ios_device_arr;
										$push_data['multiple'] = 1;
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
									}
								}
							}
							else
							{
								$referral_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_business_status['info_id'],'is_del' => 0))->row_array();
								//print_r($referral_info);die;
								//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
								$rl_to = $referral_info['referral_country_code'].''.$referral_info['referral_mobile_no'];
								$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$rl_to),$push_msg);
							}	
						}

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
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERRAL_NOT_EXIST;
			}


		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
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
		$input_data['business_status'] = $this->input->post('business_status',TRUE); // 0= new , 1= cancel , 2= view , 3= job in progress , 4= job complete	

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id','business_status');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'business_id' => $input_data['user_id'],'is_del'=>0))->row_array(); 
			if(!empty($exist_referrer_data))
			{
				//$cancel_by_referral = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'business_id' => $input_data['user_id'],'is_del'=>0))->row_array();
				
				if($exist_referrer_data['referral_status']!= '1')
				{
					if($input_data['business_status'] == '4')
					{
						$status_complete_exist = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
						if($status_complete_exist['business_status'] == '4')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] =__ALREADY_STATUS_COMPELTED;
						}
						else if($status_complete_exist['business_status'] == '6')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_PAID;
						}
						else
						{
							/*if($exist_referrer_data['referral_status'] == '4')
							{*/
								$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('business_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('business_status'=> $input_data['business_status'],'referral_status'=>$input_data['business_status']));
								if($set_status)
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
									$response[RESPONSE_MESSAGE] = _SET_BUSINESS_STATUS;

									header ( 'Content-Type: application/json' );
									echo json_encode ( $response );
									$size = ob_get_length ();
									header ( "Content-Length: $size" );
									header ( 'Connection: close' );
									header ( "Content-Encoding: none\r\n" );
									ob_end_flush ();
									ob_flush ();
									flush ();

									if(session_id ())
									session_write_close ();

									$referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array(); 

									$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$referrer_data['referrer_id'],'is_del'=>0))->row_array(); 
									if(empty($get_Referrer_bank))
									{
									//	$push_msg = 'Referral '.$referrer_data['assign_referral_id'].' status has been updated to “Job Complete”. Please update your bank account details so we can pay your referral fee.';

										$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'PUSH','send_to'=>'REFERRER','when_send'=>'CUSTOMER_BUSINESS_UPDATES_STATUS_TO_JOB_COMPLETE_AND_REFERRER_HAS_NOT_REGISTERED_BANK_ACCOUNT','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
										$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);

										$this->db->select("P.user_id,register_id,P.device_type");
					                   // $this->db->where('P.device_type',1);
					                    $this->db->where('P.user_id', $referrer_data['referrer_id']);
					                    $this->db->where('U.is_notification',0);
					                    $this->db->from("tbl_push AS P");
					                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
					                    $this->db->order_by('P.push_id',"DESC");
					                    $register_token = $this->db->get()->result_array();
					                //  print_r($register_token);die;
					                    if (!empty($register_token)) {
					                        
					                        $push_data_len = count($register_token);
											$android_device_arr = array();
											$ios_device_arr = array();

					                       $n_data = array(
								
												'user_id' => $referrer_data['referrer_id'],
												'message' => $push_msg,
												'notification_post_user_id'=> $input_data['user_id'],
												'is_flag'=> 2,
												'manage_id'=> $input_data['manage_id'],
												'info_id'=>$input_data['info_id'],
												'notification'=> 'set_status_to_job_complete_referrer_bank_not_register',
												'notification_type'=>4,
												'date_added' => date('Y-m-d H:i:s')
											
											);
											$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
											foreach ($register_token as $value) {
												if($value['device_type'] == 0)
												{
													array_push($android_device_arr, $value['register_id']);
												}
												else
												{
													array_push($ios_device_arr, $value['register_id']);
												}
										    }
					                        
					                        $message_array['custom_data']['message'] =  $push_msg;
					                        $message_array['custom_data']['notification_type'] = 4;
					                        $message_array['custom_data']['notification'] = 'set_status_to_job_complete_referrer_bank_not_register';
					                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        			$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        			$message_array['custom_data']['is_flag'] = 2;

					                        
											//echo $this->db->last_query();die;
											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['referrer_id']))->num_rows();
											$message_array['custom_data']['count'] = $notification_count;
											$message_array['custom_data']['notification_id'] = $notification_id;
					                       
					                        if(!empty($android_device_arr)){
												$push_data['device_type'] = 0;
												$push_data['register_id'] = $android_device_arr;
												$push_data['multiple'] = 1;
												
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
												
											}
											if(!empty($ios_device_arr)){
												$push_data['device_type'] = 1;
												$push_data['register_id'] = $ios_device_arr;
												$push_data['multiple'] = 1;
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
											}
					                    }
									}
									else
									{
										//$push_msg = 'Referral '.$referrer_data['assign_referral_id'].' status has been updated to “Job Complete”.Referral Fee is now due for payment.';

										$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER_OR_BUSINESS_WHO_DID_NOT_UPDATE','when_send'=>'CUSTOMER_BUSINESS_UPDATES_STATUS_TO_JOB_COMPLETE_AND_REFERRER_HAS_NOT_REGISTERED_BANK_ACCOUNT','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
									$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);	

											$this->db->select("P.user_id,register_id,P.device_type");
						                   // $this->db->where('P.device_type',1);
						                    $this->db->where('P.user_id', $referrer_data['referrer_id']);
						                    $this->db->where('U.is_notification',0);
						                    $this->db->from("tbl_push AS P");
						                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
						                    $this->db->order_by('P.push_id',"DESC");
						                    $register_token = $this->db->get()->result_array();
						                //  print_r($register_token);die;
						                    if (!empty($register_token)) {
						                        
						                        $push_data_len = count($register_token);
												$android_device_arr = array();
												$ios_device_arr = array();
						                       /* $push_data['multiple'] = 0;
						                        $push_data['register_id'] = trim($register_token['register_id']);
						                        $push_data['device_type'] = $register_token['device_type'];*/
						                        $n_data = array(
									
												'user_id' => $referrer_data['referrer_id'],
												'message' => $push_msg,
												'notification_post_user_id'=> $input_data['user_id'],
												'is_flag'=> 2,
												'manage_id'=> $input_data['manage_id'],
												'info_id'=>$input_data['info_id'],
												'notification'=> 'set_status_to_job_complete_referrer_bank_register',
												'notification_type'=>5,
												'date_added' => date('Y-m-d H:i:s')
												
												);
												$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
						                        foreach ($register_token as $value) {
													if($value['device_type'] == 0)
													{
														array_push($android_device_arr, $value['register_id']);
													}
													else
													{
														array_push($ios_device_arr, $value['register_id']);
													}
											    }
						                        $message_array['custom_data']['message'] =  $push_msg;
						                        $message_array['custom_data']['notification_type'] = 5;
						                        $message_array['custom_data']['notification'] = 'set_status_to_job_complete_referrer_bank_register';
						                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        				$message_array['custom_data']['is_flag'] = 2;

						                        
												//echo $this->db->last_query();die;
												$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['referrer_id']))->num_rows();
												$message_array['custom_data']['count'] = $notification_count;
												$message_array['custom_data']['notification_id'] = $notification_id;
						                      //  $message_array['custom_data']['msg_count'] = $msg_count;
						                       
						                      if(!empty($android_device_arr)){
													$push_data['device_type'] = 0;
													$push_data['register_id'] = $android_device_arr;
													$push_data['multiple'] = 1;
													
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
													
												}
												if(!empty($ios_device_arr)){
													$push_data['device_type'] = 1;
													$push_data['register_id'] = $ios_device_arr;
													$push_data['multiple'] = 1;
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
												}
						                }        
									}
								}
								else
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
									$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
								}
						//	}
							/*else
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] = __REFERRAL_STATUS_NOT_JOB_COMPLETED;
							}*/
						}
						
					}
					else
					{
						if($input_data['business_status'] == '7')
						{
							$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('business_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('business_status'=> $input_data['business_status'],'referral_status'=>$input_data['business_status']));
								if($set_status)
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
									$response[RESPONSE_MESSAGE] = _SET_BUSINESS_STATUS;
								}
								else
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
									$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
								}
						}
						$status_in_progress_exist = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
						if($status_in_progress_exist['business_status'] == '3')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_IN_PROGRESS;
						}
						else if($status_in_progress_exist['business_status'] == '4')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_COMPELTED;
						}
						else if($status_in_progress_exist['business_status'] == '6')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_PAID;
						}
						else if($status_in_progress_exist['business_status'] == '1')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_CANCEL;
						}
						else
						{
							//echo "Distupe";die;
						 $set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('business_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('business_status'=> $input_data['business_status'],'referral_status'=>$input_data['business_status']));
							if($set_status)
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
							  	$response[RESPONSE_MESSAGE] = _SET_BUSINESS_STATUS;

								header ( 'Content-Type: application/json' );
								echo json_encode ( $response );
								$size = ob_get_length ();
								header ( "Content-Length: $size" );
								header ( 'Connection: close' );
								header ( "Content-Encoding: none\r\n" );
								ob_end_flush ();
								ob_flush ();
								flush ();

								if(session_id ())
								session_write_close ();

								$get_business_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();	

								if($get_business_status['business_status'] == '7')
								{
									/*$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER_OR_BUSINESS_WHO_DID_NOT_UPDATE','when_send'=>'BUSINESS_OR_CUSTOMER_UPDATES_STATUS_TO_JOB_IN_PROGRESS','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
									$push_msg = str_ireplace('<referrer_id>', $get_business_status['assign_referral_id'], $get_push_msg['message']);*/
									//$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' has been disputed.';

									$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'DISPUTE_THE_REFERRAL','send_to'=>'DISPUTE_THE_REFERRAL','when_send'=>'DISPUTE_THE_REFERRAL','type'=>'DISPUTE_THE_REFERRAL'))->row_array();
									$push_msg = str_ireplace('<referrer_id>', $get_business_status['assign_referral_id'], $get_push_msg['message']);

									//$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' status has been updated to "Job In Progress" by business';
									$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $get_business_status['referrer_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {

				                        
				                        $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();

				                        $n_data = array(
							
										'user_id' => $get_business_status['referrer_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 2,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'set_status_to_dispute',
										'notification_type'=>12,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }
				                        
				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 12;
				                        $message_array['custom_data']['notification'] = 'set_status_to_dispute';
				                         $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                        			$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                        			$message_array['custom_data']['is_flag'] = 2;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referrer_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                    	
				                    	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
				                    if($get_business_status['referral_id']!= '0')
				                    {
				                    	$this->db->select("P.user_id,register_id,P.device_type");
					                    //$this->db->where('P.device_type',1);
					                    $this->db->where('P.user_id', $get_business_status['referral_id']);
					                    $this->db->where('U.is_notification',0);
					                    $this->db->from("tbl_push AS P");
					                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
					                    $this->db->order_by('P.push_id',"DESC");
					                    $register_token = $this->db->get()->result_array();
					                //  print_r($register_token);die;
					                    if (!empty($register_token)) {
					                        
					                        $push_data_len = count($register_token);
											$android_device_arr = array();
											$ios_device_arr = array();

											$n_data = array(
								
											'user_id' => $get_business_status['referral_id'],
											'message' => $push_msg,
											'notification_post_user_id'=> $input_data['user_id'],
											'is_flag'=> 3,
											'manage_id'=> $input_data['manage_id'],
											'info_id'=>$input_data['info_id'],
											'notification'=> 'set_status_to_dispute',
											'notification_type'=>12,
											'date_added' => date('Y-m-d H:i:s')
											
											);
											$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
											foreach ($register_token as $value) {
												if($value['device_type'] == 0)
												{
													array_push($android_device_arr, $value['register_id']);
												}
												else
												{
													array_push($ios_device_arr, $value['register_id']);
												}
										    }
					                        
					                        $message_array['custom_data']['message'] =  $push_msg;
					                        $message_array['custom_data']['notification_type'] = 12;
					                        $message_array['custom_data']['notification'] = 'set_status_to_dispute';
					                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                        				$message_array['custom_data']['is_flag'] = 3;

					                        
											//echo $this->db->last_query();die;
											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referral_id']))->num_rows();
											$message_array['custom_data']['count'] = $notification_count;
											$message_array['custom_data']['notification_id'] = $notification_id;
					                      if(!empty($android_device_arr)){
												$push_data['device_type'] = 0;
												$push_data['register_id'] = $android_device_arr;
												$push_data['multiple'] = 1;
												
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
												
											}
											if(!empty($ios_device_arr)){
												$push_data['device_type'] = 1;
												$push_data['register_id'] = $ios_device_arr;
												$push_data['multiple'] = 1;
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
											}
					                    }
				                    }
				                    else
				                    {
				                    	$referral_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_business_status['info_id'],'is_del' => 0))->row_array();
				                    	//print_r($referral_info);die;
				                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
				                    	$rl_to = $referral_info['referral_country_code'].''.$referral_info['referral_mobile_no'];
										$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$rl_to),$push_msg);
				                    }	
								}	
								if($get_business_status['business_status'] == '3')
								{
									$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER_OR_BUSINESS_WHO_DID_NOT_UPDATE','when_send'=>'BUSINESS_OR_CUSTOMER_UPDATES_STATUS_TO_JOB_IN_PROGRESS','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
									$push_msg = str_ireplace('<referrer_id>', $get_business_status['assign_referral_id'], $get_push_msg['message']);

									//$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' status has been updated to "Job In Progress" by business';
									$this->db->select("P.user_id,register_id,P.device_type");
				                   // $this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $get_business_status['referrer_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                    	$push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();
				                        
				                       $n_data = array(
							
										'user_id' => $get_business_status['referrer_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 2,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'set_status_to_job_in_progress',
										'notification_type'=>3,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }
				                        
				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 3;
				                        $message_array['custom_data']['notification'] = 'set_status_to_job_in_progress';
				                         $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                        			$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                        			$message_array['custom_data']['is_flag'] = 2;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referrer_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                      //  $message_array['custom_data']['msg_count'] = $msg_count;
				                       
				                     	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
				                    if($get_business_status['referral_id']!= '0')
				                    {
				                    	$this->db->select("P.user_id,register_id,P.device_type");
					                   // $this->db->where('P.device_type',1);
					                    $this->db->where('P.user_id', $get_business_status['referral_id']);
					                    $this->db->where('U.is_notification',0);
					                    $this->db->from("tbl_push AS P");
					                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
					                    $this->db->order_by('P.push_id',"DESC");
					                    $register_token = $this->db->get()->result_array();
					             	   //  print_r($register_token);die;
					                    if (!empty($register_token)) {
					                        $push_data_len = count($register_token);
											$android_device_arr = array();
											$ios_device_arr = array();
					                       
					                        $n_data = array(
								
											'user_id' => $get_business_status['referral_id'],
											'message' => $push_msg,
											'notification_post_user_id'=> $input_data['user_id'],
											'is_flag'=> 3,
											'manage_id'=> $input_data['manage_id'],
											'info_id'=>$input_data['info_id'],
											'notification'=> 'set_status_to_job_in_progress',
											'notification_type'=>3,
											'date_added' => date('Y-m-d H:i:s')
											
											);
											$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
											foreach ($register_token as $value) {
												if($value['device_type'] == 0)
												{
													array_push($android_device_arr, $value['register_id']);
												}
												else
												{
													array_push($ios_device_arr, $value['register_id']);
												}
										    } 
					                        
					                        $message_array['custom_data']['message'] =  $push_msg;
					                        $message_array['custom_data']['notification_type'] = 3;
					                        $message_array['custom_data']['notification'] = 'set_status_to_job_in_progress';
					                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                        				$message_array['custom_data']['is_flag'] = 3;

					                        
											//echo $this->db->last_query();die;
											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referral_id']))->num_rows();
											$message_array['custom_data']['count'] = $notification_count;
											$message_array['custom_data']['notification_id'] = $notification_id;
					                      //  $message_array['custom_data']['msg_count'] = $msg_count;
					                       
					                     if(!empty($android_device_arr)){
												$push_data['device_type'] = 0;
												$push_data['register_id'] = $android_device_arr;
												$push_data['multiple'] = 1;
												
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
												
											}
											if(!empty($ios_device_arr)){
												$push_data['device_type'] = 1;
												$push_data['register_id'] = $ios_device_arr;
												$push_data['multiple'] = 1;
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
											}
					                    }
				                    }
				                    else
				                    {
				                    	$referral_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_business_status['info_id'],'is_del' => 0))->row_array();
				                    	//print_r($referral_info);die;
				                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
				                    	$rl_to = $referral_info['referral_country_code'].''.$referral_info['referral_mobile_no'];
										$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$rl_to),$push_msg);
				                    }	
								}
								if($get_business_status['business_status'] == '1')
								{
									$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' has been cancelled by business';
									$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $get_business_status['referrer_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                        $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();
				                       
										$n_data = array(
							
										'user_id' => $get_business_status['referrer_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 2,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'set_status_to_cancelled',
										'notification_type'=>6,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }
				                        
				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 6;
				                        $message_array['custom_data']['notification'] = 'set_status_to_cancelled';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                        			$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                        			$message_array['custom_data']['is_flag'] = 2;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referrer_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                      	
				                      	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
				                    if($get_business_status['referral_id']!= '0')
				                    {
				                    	$this->db->select("P.user_id,register_id,P.device_type");
					                    //$this->db->where('P.device_type',1);
					                    $this->db->where('P.user_id', $get_business_status['referral_id']);
					                    $this->db->where('U.is_notification',0);
					                    $this->db->from("tbl_push AS P");
					                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
					                    $this->db->order_by('P.push_id',"DESC");
					                    $register_token = $this->db->get()->result_array();
					                //  print_r($register_token);die;
					                    if (!empty($register_token)) {
					                        $push_data_len = count($register_token);
											$android_device_arr = array();
											$ios_device_arr = array();
					                       /* $push_data['multiple'] = 0;
					                        $push_data['register_id'] = trim($register_token['register_id']);
					                        $push_data['device_type'] = $register_token['device_type'];*/
					                        $n_data = array(
								
											'user_id' => $get_business_status['referral_id'],
											'message' => $push_msg,
											'notification_post_user_id'=> $input_data['user_id'],
											'is_flag'=> 3,
											'manage_id'=> $input_data['manage_id'],
											'info_id'=>$input_data['info_id'],
											'notification'=> 'set_status_to_cancelled',
											'notification_type'=>6,
											'date_added' => date('Y-m-d H:i:s')
											
											);
											$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
											foreach ($register_token as $value) {
												if($value['device_type'] == 0)
												{
													array_push($android_device_arr, $value['register_id']);
												}
												else
												{
													array_push($ios_device_arr, $value['register_id']);
												}
										    }
					                        
					                        $message_array['custom_data']['message'] =  $push_msg;
					                        $message_array['custom_data']['notification_type'] = 6;
					                        $message_array['custom_data']['notification'] = 'set_status_to_cancelled';
					                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                        				$message_array['custom_data']['is_flag'] = 3;

					                        
											//echo $this->db->last_query();die;
											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referral_id']))->num_rows();
											$message_array['custom_data']['count'] = $notification_count;
											$message_array['custom_data']['notification_id'] = $notification_id;
					                     
					                     	if(!empty($android_device_arr)){
												$push_data['device_type'] = 0;
												$push_data['register_id'] = $android_device_arr;
												$push_data['multiple'] = 1;
												
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
												
											}
											if(!empty($ios_device_arr)){
												$push_data['device_type'] = 1;
												$push_data['register_id'] = $ios_device_arr;
												$push_data['multiple'] = 1;
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
											}
					                    }
				                    }
				                    else
				                    {
				                    	$referral_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_business_status['info_id'],'is_del' => 0))->row_array();
				                    	//print_r($referral_info);die;
				                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
				                    	$rl_to = $referral_info['referral_country_code'].''.$referral_info['referral_mobile_no'];
										$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$rl_to),$push_msg);
				                    }	
								}

							}
							else
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
							}
						}	
					}

				}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __STATUS_CANCEL_BY_REFERRAL;
				}
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERRAL_NOT_EXIST;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
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
				$view_by_business = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'referral_id' => $input_data['user_id'],'is_del'=>0))->row_array();
				/*if($view_by_business['business_status'] != '0') // not view by business
				{*/
					if($input_data['referral_status']!= '1') // check for cancel
					{
						if($input_data['referral_status']!= '4') // check for job completed
						{
							$status_in_progress_exist = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
							if($status_in_progress_exist['referral_status'] == '3')
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_IN_PROGRESS;
							}
							else
							{
								if($exist_referrer_data['business_status']!= '1')
								{
									$check_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
									if($input_data['referral_status'] >= $check_status['referral_status'])
									{
										$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referral_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('referral_status'=> $input_data['referral_status'],'business_status'=>$input_data['referral_status']));
										if($set_status)
										{
											$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
											$response[RESPONSE_MESSAGE] = _SET_REFERRAL_STATUS;

											header ( 'Content-Type: application/json' );
											echo json_encode ( $response );
											$size = ob_get_length ();
											header ( "Content-Length: $size" );
											header ( 'Connection: close' );
											header ( "Content-Encoding: none\r\n" );
											ob_end_flush ();
											ob_flush ();
											flush ();

											if(session_id ())
											session_write_close ();

											$get_referral_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array(); 
											$get_business_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();	

											if($get_business_status['business_status'] == '7')
											{
												/*$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER_OR_BUSINESS_WHO_DID_NOT_UPDATE','when_send'=>'BUSINESS_OR_CUSTOMER_UPDATES_STATUS_TO_JOB_IN_PROGRESS','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
												$push_msg = str_ireplace('<referrer_id>', $get_business_status['assign_referral_id'], $get_push_msg['message']);*/
											//	$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' has been disputed.';
												$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'DISPUTE_THE_REFERRAL','send_to'=>'DISPUTE_THE_REFERRAL','when_send'=>'DISPUTE_THE_REFERRAL','type'=>'DISPUTE_THE_REFERRAL'))->row_array();
												$push_msg = str_ireplace('<referrer_id>', $get_business_status['assign_referral_id'], $get_push_msg['message']);

												//$push_msg = 'Referral '.$get_business_status['assign_referral_id'].' status has been updated to "Job In Progress" by business';
												$this->db->select("P.user_id,register_id,P.device_type");
												//$this->db->where('P.device_type',1);
												$this->db->where('P.user_id', $get_business_status['referrer_id']);
												$this->db->where('U.is_notification',0);
												$this->db->from("tbl_push AS P");
												$this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
												$this->db->order_by('P.push_id',"DESC");
												$register_token = $this->db->get()->result_array();
											//  print_r($register_token);die;
												if (!empty($register_token)) {

													
													$push_data_len = count($register_token);
													$android_device_arr = array();
													$ios_device_arr = array();

													$n_data = array(

													'user_id' => $get_business_status['referrer_id'],
													'message' => $push_msg,
													'notification_post_user_id'=> $input_data['user_id'],
													'is_flag'=> 2,
													'manage_id'=> $input_data['manage_id'],
													'info_id'=>$input_data['info_id'],
													'notification'=> 'set_status_to_dispute',
													'notification_type'=>12,
													'date_added' => date('Y-m-d H:i:s')
													
													);
													$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
													foreach ($register_token as $value) {
														if($value['device_type'] == 0)
														{
															array_push($android_device_arr, $value['register_id']);
														}
														else
														{
															array_push($ios_device_arr, $value['register_id']);
														}
												    }
													
													$message_array['custom_data']['message'] =  $push_msg;
													$message_array['custom_data']['notification_type'] = 12;
													$message_array['custom_data']['notification'] = 'set_status_to_dispute';
													 $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
													$message_array['custom_data']['info_id'] = $input_data['info_id'];
													$message_array['custom_data']['is_flag'] = 2;

													
													//echo $this->db->last_query();die;
													$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['referrer_id']))->num_rows();
													$message_array['custom_data']['count'] = $notification_count;
													$message_array['custom_data']['notification_id'] = $notification_id;
													if(!empty($android_device_arr)){
														$push_data['device_type'] = 0;
														$push_data['register_id'] = $android_device_arr;
														$push_data['multiple'] = 1;
														
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
														
													}
													if(!empty($ios_device_arr)){
														$push_data['device_type'] = 1;
														$push_data['register_id'] = $ios_device_arr;
														$push_data['multiple'] = 1;
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
													}
													
												}
												if($get_business_status['business_id']!= '0')
												{
													$this->db->select("P.user_id,register_id,P.device_type");
													//$this->db->where('P.device_type',1);
													$this->db->where('P.user_id', $get_business_status['business_id']);
													$this->db->where('U.is_notification',0);
													$this->db->from("tbl_push AS P");
													$this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
													$this->db->order_by('P.push_id',"DESC");
													$register_token = $this->db->get()->result_array();
												//  print_r($register_token);die;
													if (!empty($register_token)) {
														
														$push_data_len = count($register_token);
														$android_device_arr = array();
														$ios_device_arr = array();

														$n_data = array(

														'user_id' => $get_business_status['business_id'],
														'message' => $push_msg,
														'notification_post_user_id'=> $input_data['user_id'],
														'is_flag'=> 1,
														'manage_id'=> $input_data['manage_id'],
														'info_id'=>$input_data['info_id'],
														'notification'=> 'set_status_to_dispute',
														'notification_type'=>12,
														'date_added' => date('Y-m-d H:i:s')
														
														);
														$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
														foreach ($register_token as $value) {
															if($value['device_type'] == 0)
															{
																array_push($android_device_arr, $value['register_id']);
															}
															else
															{
																array_push($ios_device_arr, $value['register_id']);
															}
													    }
														
														$message_array['custom_data']['message'] =  $push_msg;
														$message_array['custom_data']['notification_type'] = 12;
														$message_array['custom_data']['notification'] = 'set_status_to_dispute';
														$message_array['custom_data']['manage_id'] = $input_data['manage_id'];
														$message_array['custom_data']['info_id'] = $input_data['info_id'];
														$message_array['custom_data']['is_flag'] = 1;

														
														//echo $this->db->last_query();die;
														$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_business_status['business_id']))->num_rows();
														$message_array['custom_data']['count'] = $notification_count;
														$message_array['custom_data']['notification_id'] = $notification_id;
														if(!empty($android_device_arr)){
															$push_data['device_type'] = 0;
															$push_data['register_id'] = $android_device_arr;
															$push_data['multiple'] = 1;
															
															
															$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
															
														}
														if(!empty($ios_device_arr)){
															$push_data['device_type'] = 1;
															$push_data['register_id'] = $ios_device_arr;
															$push_data['multiple'] = 1;
															
															$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
														}
													}
												}
												else
												{
													$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_business_status['info_id'],'is_del' => 0))->row_array();
													//print_r($business_info);die;
													//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
													$bs_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
													$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$bs_to),$push_msg);
												}	
											}

											if($get_referral_status['referral_status'] == '3')
											{
												//$push_msg = 'Referral '.$get_referral_status['assign_referral_id'].' status has been updated to "Job In Progress" by referral';

												$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER_OR_BUSINESS_WHO_DID_NOT_UPDATE','when_send'=>'BUSINESS_OR_CUSTOMER_UPDATES_STATUS_TO_JOB_IN_PROGRESS','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
												$push_msg = str_ireplace('<referrer_id>', $get_referral_status['assign_referral_id'], $get_push_msg['message']);

												$this->db->select("P.user_id,register_id,P.device_type");
							                    //$this->db->where('P.device_type',1);
							                    $this->db->where('P.user_id', $get_referral_status['referrer_id']);
							                    $this->db->where('U.is_notification',0);
							                    $this->db->from("tbl_push AS P");
							                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
							                    $this->db->order_by('P.push_id',"DESC");
							                    $register_token = $this->db->get()->result_array();
							               	 //  print_r($register_token);die;
							                    if (!empty($register_token)) {
							                        $push_data_len = count($register_token);
													$android_device_arr = array();
													$ios_device_arr = array();
							                        
							                        $n_data = array(
										
													'user_id' => $get_referral_status['referrer_id'],
													'message' => $push_msg,
													'notification_post_user_id'=> $input_data['user_id'],
													'is_flag'=> 2,
													'manage_id'=> $input_data['manage_id'],
													'info_id'=>$input_data['info_id'],
													'notification'=> 'set_status_to_job_in_progress',	
													'notification_type'=>3,
													'date_added' => date('Y-m-d H:i:s')
													
													);
													$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
													foreach ($register_token as $value) {
														if($value['device_type'] == 0)
														{
															array_push($android_device_arr, $value['register_id']);
														}
														else
														{
															array_push($ios_device_arr, $value['register_id']);
														}
												    }
							                        
							                        $message_array['custom_data']['message'] =  $push_msg;
							                        $message_array['custom_data']['notification_type'] = 3;
							                        $message_array['custom_data']['notification'] = 'set_status_to_job_in_progress';
							                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
			                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
			                        				$message_array['custom_data']['is_flag'] = 2;

							                        
													//echo $this->db->last_query();die;
													$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_referral_status['referrer_id']))->num_rows();
													$message_array['custom_data']['count'] = $notification_count;
													$message_array['custom_data']['notification_id'] = $notification_id;
							                      //  $message_array['custom_data']['msg_count'] = $msg_count;
							                       
								                     if(!empty($android_device_arr)){
														$push_data['device_type'] = 0;
														$push_data['register_id'] = $android_device_arr;
														$push_data['multiple'] = 1;
														
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
														
													}
													if(!empty($ios_device_arr)){
														$push_data['device_type'] = 1;
														$push_data['register_id'] = $ios_device_arr;
														$push_data['multiple'] = 1;
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
													}
							                    }

							                    if($get_referral_status['business_id']!= '0')
							                    {
							                    	$this->db->select("P.user_id,register_id,P.device_type");
								                    //$this->db->where('P.device_type',1);
								                    $this->db->where('P.user_id', $get_referral_status['business_id']);
								                    $this->db->where('U.is_notification',0);
								                    $this->db->from("tbl_push AS P");
								                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
								                    $this->db->order_by('P.push_id',"DESC");
								                    $register_token = $this->db->get()->result_array();
								               		 //  print_r($register_token);die;
								                    if (!empty($register_token)) {
								                        $push_data_len = count($register_token);
														$android_device_arr = array();
														$ios_device_arr = array();

								                        $check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$get_referral_status['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
								                        if(!empty($check_complete_status))
								                        {
								                        	$is_allow_visible = 1;
								                        }
								                        else
								                        {
								                        	$is_allow_visible = 0;
								                        }

								                        $n_data = array(
											
														'user_id' => $get_referral_status['business_id'],
														'message' => $push_msg,
														'notification_post_user_id'=> $input_data['user_id'],
														'is_flag'=> 1,
														'manage_id'=> $input_data['manage_id'],
														'info_id'=>$input_data['info_id'],
														'notification'=> 'set_status_to_job_in_progress',
														'notification_type'=>3,
														'is_allow_visible' => $is_allow_visible,
														'date_added' => date('Y-m-d H:i:s')
														
														);
														$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
														foreach ($register_token as $value) {
															if($value['device_type'] == 0)
															{
																array_push($android_device_arr, $value['register_id']);
															}
															else
															{
																array_push($ios_device_arr, $value['register_id']);
															}
													    }
								                        
								                        $message_array['custom_data']['message'] =  $push_msg;
								                        $message_array['custom_data']['notification_type'] = 3;
								                        $message_array['custom_data']['notification'] = 'set_status_to_job_in_progress';
								                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
			                        					$message_array['custom_data']['info_id'] = $input_data['info_id'];
			                        					$message_array['custom_data']['is_flag'] = 1;
			                        					$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

								                        
														//echo $this->db->last_query();die;
														$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $get_referral_status['business_id']))->num_rows();
														$message_array['custom_data']['count'] = $notification_count;
														$message_array['custom_data']['notification_id'] = $notification_id;
								                     
								                      	if(!empty($android_device_arr)){
															$push_data['device_type'] = 0;
															$push_data['register_id'] = $android_device_arr;
															$push_data['multiple'] = 1;
															
															
															$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
															
														}
														if(!empty($ios_device_arr)){
															$push_data['device_type'] = 1;
															$push_data['register_id'] = $ios_device_arr;
															$push_data['multiple'] = 1;
															
															$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
														}

								                    }
							                    }
							                    else
							                    {
							                    	$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_referral_status['info_id'],'is_del' => 0))->row_array();
							                    	//print_r($business_info);die;
							                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
							                    	$b_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
													$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
							                    }

											}
											elseif ($get_referral_status['referral_status'] == '4') {
												
											}


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
										$response[RESPONSE_MESSAGE] = __CANNOT_CHANGE_AFTER_JOB_COMPLETED;
									}
								}
								else
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
									$response[RESPONSE_MESSAGE] = __bUSINESS_ALREADY_SET_TO_CANCEL_FOR_PROGRESS;
								}
							}
						}
						else
						{
							$status_complete_exist = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
							if($status_complete_exist['referral_status'] == '4')
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] =__ALREADY_STATUS_COMPELTED;
							}
							else
							{
								if($exist_referrer_data['business_status']!= '1') // check for cancel by business or not
								{
									$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referral_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('referral_status'=> $input_data['referral_status'],'business_status'=>$input_data['referral_status']));
									if($set_status)
									{
										$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
										$response[RESPONSE_MESSAGE] = _SET_REFERRAL_STATUS;

										header ( 'Content-Type: application/json' );
										echo json_encode ( $response );
										$size = ob_get_length ();
										header ( "Content-Length: $size" );
										header ( 'Connection: close' );
										header ( "Content-Encoding: none\r\n" );
										ob_end_flush ();
										ob_flush ();
										flush ();

										if(session_id ())
										session_write_close ();

										$referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array(); 

										$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$referrer_data['referrer_id'],'is_del'=>0))->row_array(); 
										if(empty($get_Referrer_bank))
										{
											//$push_msg = 'Referral '.$referrer_data['assign_referral_id'].' status has been updated to “Job Complete”. Please update your bank account details so we can pay your referral fee.';

											$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'PUSH','send_to'=>'REFERRER','when_send'=>'CUSTOMER_BUSINESS_UPDATES_STATUS_TO_JOB_COMPLETE_AND_REFERRER_HAS_NOT_REGISTERED_BANK_ACCOUNT','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
											$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);	

											$this->db->select("P.user_id,register_id,P.device_type");
						                    //$this->db->where('P.device_type',1);
						                    $this->db->where('P.user_id', $referrer_data['referrer_id']);
						                    $this->db->where('U.is_notification',0);
						                    $this->db->from("tbl_push AS P");
						                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
						                    $this->db->order_by('P.push_id',"DESC");
						                    $register_token = $this->db->get()->result_array();
						                //  print_r($register_token);die;
						                    if (!empty($register_token)) {
						                        
						                        $push_data_len = count($register_token);
												$android_device_arr = array();
												$ios_device_arr = array();

												$n_data = array(
									
												'user_id' => $referrer_data['referrer_id'],
												'message' => $push_msg,
												'notification_post_user_id'=> $input_data['user_id'],
												'is_flag'=> 2,
												'manage_id'=> $input_data['manage_id'],
												'info_id'=>$input_data['info_id'],
												'notification'=> 'set_status_to_job_complete_referrer_bank_not_register',
												'notification_type'=>4,
												'date_added' => date('Y-m-d H:i:s')
												
												);
												$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
						                        foreach ($register_token as $value) {
													if($value['device_type'] == 0)
													{
														array_push($android_device_arr, $value['register_id']);
													}
													else
													{
														array_push($ios_device_arr, $value['register_id']);
													}
											    }

						                        
						                        $message_array['custom_data']['message'] =  $push_msg;
						                        $message_array['custom_data']['notification_type'] = 4;
						                        $message_array['custom_data']['notification'] = 'set_status_to_job_complete_referrer_bank_not_register';
						                          $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        				$message_array['custom_data']['is_flag'] = 2;

						                        
												//echo $this->db->last_query();die;
												$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['referrer_id']))->num_rows();
												$message_array['custom_data']['count'] = $notification_count;
												$message_array['custom_data']['notification_id'] = $notification_id;
						                      //  $message_array['custom_data']['msg_count'] = $msg_count;
						                       
						                      if(!empty($android_device_arr)){
													$push_data['device_type'] = 0;
													$push_data['register_id'] = $android_device_arr;
													$push_data['multiple'] = 1;
													
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
													
												}
												if(!empty($ios_device_arr)){
													$push_data['device_type'] = 1;
													$push_data['register_id'] = $ios_device_arr;
													$push_data['multiple'] = 1;
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
												}
						                    }
						                    $push_msg_b = 'Referral '.$referrer_data['assign_referral_id'].' status has been updated to "Job complete" by referral';
											if($referrer_data['business_id']!= '0')
						                    {
						                    	$this->db->select("P.user_id,register_id,P.device_type");
							                    //$this->db->where('P.device_type',1);
							                    $this->db->where('P.user_id', $referrer_data['business_id']);
							                    $this->db->where('U.is_notification',0);
							                    $this->db->from("tbl_push AS P");
							                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
							                    $this->db->order_by('P.push_id',"DESC");
							                    $register_token = $this->db->get()->result_array();
							                //  print_r($register_token);die;
							                    if (!empty($register_token)) {
							                       
							                       $push_data_len = count($register_token);
													$android_device_arr = array();
													$ios_device_arr = array();

													$check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$referrer_data['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
													if(!empty($check_complete_status))
													{
														$is_allow_visible = 1;
													}
													else
													{
														$is_allow_visible = 0;
													}

							                        $n_data = array(
										
													'user_id' => $referrer_data['business_id'],
													'message' => $push_msg_b,
													'notification_post_user_id'=> $input_data['user_id'],
													'is_flag'=> 1,
													'manage_id'=> $input_data['manage_id'],
													'info_id'=>$input_data['info_id'],
													'notification'=> 'set_status_to_job_complete_referrer_bank_not_register',
													'notification_type'=>4,
													'is_allow_visible' => $is_allow_visible,
													'date_added' => date('Y-m-d H:i:s')
													
													);
													$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
							                        foreach ($register_token as $value) {
														if($value['device_type'] == 0)
														{
															array_push($android_device_arr, $value['register_id']);
														}
														else
														{
															array_push($ios_device_arr, $value['register_id']);
														}
												    }

							                        $message_array['custom_data']['message'] =  $push_msg_b;
							                        $message_array['custom_data']['notification_type'] = 4;
							                        $message_array['custom_data']['notification'] = 'set_status_to_job_complete_referrer_bank_not_register';
							                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        					$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        					$message_array['custom_data']['is_flag'] = 1;
		                        					$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

							                        
													//echo $this->db->last_query();die;
													$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['business_id']))->num_rows();
													$message_array['custom_data']['count'] = $notification_count;
													$message_array['custom_data']['notification_id'] = $notification_id;
							                      //  $message_array['custom_data']['msg_count'] = $msg_count;
							                       
							                      if(!empty($android_device_arr)){
														$push_data['device_type'] = 0;
														$push_data['register_id'] = $android_device_arr;
														$push_data['multiple'] = 1;
														
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
														
													}
													if(!empty($ios_device_arr)){
														$push_data['device_type'] = 1;
														$push_data['register_id'] = $ios_device_arr;
														$push_data['multiple'] = 1;
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
													}
							                    }
						                    }
						                    else
						                    {
						                    	$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $referrer_data['info_id'],'is_del' => 0))->row_array();
						                    	//print_r($business_info);die;
						                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
						                    	$b_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
												$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg_b);
						                    }

										}
										else
										{
											//$push_msg = 'Referral '.$referrer_data['assign_referral_id'].' status has been updated to “Job Complete”.Referral Fee is now due for payment.';

											$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'REFERRER_AND_CUSTOMER_OR_BUSINESS_WHO_DID_NOT_UPDATE','when_send'=>'CUSTOMER_BUSINESS_UPDATES_STATUS_TO_JOB_COMPLETE_AND_REFERRER_HAS_NOT_REGISTERED_BANK_ACCOUNT','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();

											$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);	

											$this->db->select("P.user_id,register_id,P.device_type");
						                   // $this->db->where('P.device_type',1);
						                    $this->db->where('P.user_id', $referrer_data['referrer_id']);
						                    $this->db->where('U.is_notification',0);
						                    $this->db->from("tbl_push AS P");
						                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
						                    $this->db->order_by('P.push_id',"DESC");
						                    $register_token = $this->db->get()->result_array();
						                //  print_r($register_token);die;
						                    if (!empty($register_token)) {
						                        
						                         $push_data_len = count($register_token);
												$android_device_arr = array();
												$ios_device_arr = array();

						                        /*$push_data['multiple'] = 0;
						                        $push_data['register_id'] = trim($register_token['register_id']);
						                        $push_data['device_type'] = $register_token['device_type'];*/
						                        $n_data = array(
									
												'user_id' => $referrer_data['referrer_id'],
												'message' => $push_msg,
												'notification_post_user_id'=> $input_data['user_id'],
												'is_flag'=> 2,
												'manage_id'=> $input_data['manage_id'],
												'info_id'=>$input_data['info_id'],
												'notification'=> 'set_status_to_job_complete_referrer_bank_register',
												'notification_type'=>5,
												'date_added' => date('Y-m-d H:i:s')
												
												);
												$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
						                        foreach ($register_token as $value) {
													if($value['device_type'] == 0)
													{
														array_push($android_device_arr, $value['register_id']);
													}
													else
													{
														array_push($ios_device_arr, $value['register_id']);
													}
											    }

						                        $message_array['custom_data']['message'] =  $push_msg;
						                        $message_array['custom_data']['notification_type'] = 5;
						                        $message_array['custom_data']['notification'] = 'set_status_to_job_complete_referrer_bank_register';
						                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        				$message_array['custom_data']['is_flag'] = 2;

						                        
												//echo $this->db->last_query();die;
												$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['referrer_id']))->num_rows();
												$message_array['custom_data']['count'] = $notification_count;
												$message_array['custom_data']['notification_id'] = $notification_id;
						                      	if(!empty($android_device_arr)){
													$push_data['device_type'] = 0;
													$push_data['register_id'] = $android_device_arr;
													$push_data['multiple'] = 1;
													
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
													
												}
												if(!empty($ios_device_arr)){
													$push_data['device_type'] = 1;
													$push_data['register_id'] = $ios_device_arr;
													$push_data['multiple'] = 1;
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
												}

						                    }
						                    if($referrer_data['business_id']!= '0')
						                    {
						                    	$this->db->select("P.user_id,register_id,P.device_type");
							                   // $this->db->where('P.device_type',1);
							                    $this->db->where('P.user_id', $referrer_data['business_id']);
							                    $this->db->where('U.is_notification',0);
							                    $this->db->from("tbl_push AS P");
							                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
							                    $this->db->order_by('P.push_id',"DESC");
							                    $register_token = $this->db->get()->result_array();
							                //  print_r($register_token);die;
							                    if (!empty($register_token)) {
							                        
							                        $push_data_len = count($register_token);
													$android_device_arr = array();
													$ios_device_arr = array();

							                        $check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$referrer_data['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
							                        if(!empty($check_complete_status))
													{
														$is_allow_visible = 1;
													}
													else
													{
														$is_allow_visible = 0;
													}
													$n_data = array(
										
													'user_id' => $referrer_data['business_id'],
													'message' => $push_msg,
													'notification_post_user_id'=> $input_data['user_id'],
													'is_flag'=> 1,
													'manage_id'=> $input_data['manage_id'],
													'info_id'=>$input_data['info_id'],
													'notification'=> 'set_status_to_job_complete_referrer_bank_register',
													'notification_type'=>5,
													'is_allow_visible' => $is_allow_visible,
													'date_added' => date('Y-m-d H:i:s')
													
													);
													$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
							                        foreach ($register_token as $value) {
														if($value['device_type'] == 0)
														{
															array_push($android_device_arr, $value['register_id']);
														}
														else
														{
															array_push($ios_device_arr, $value['register_id']);
														}
												    }
	 
							                        $message_array['custom_data']['message'] =  $push_msg;
							                        $message_array['custom_data']['notification_type'] = 5;
							                        $message_array['custom_data']['notification'] = 'set_status_to_job_complete_referrer_bank_register';
							                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        					$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        					$message_array['custom_data']['is_flag'] = 1;
		                        					$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

							                        
													//echo $this->db->last_query();die;
													$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['business_id']))->num_rows();
													$message_array['custom_data']['count'] = $notification_count;
													$message_array['custom_data']['notification_id'] = $notification_id;
							                    
							                    	if(!empty($android_device_arr)){
														$push_data['device_type'] = 0;
														$push_data['register_id'] = $android_device_arr;
														$push_data['multiple'] = 1;
														
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
														
													}
													if(!empty($ios_device_arr)){
														$push_data['device_type'] = 1;
														$push_data['register_id'] = $ios_device_arr;
														$push_data['multiple'] = 1;
														
														$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
													}
							                    }
						                    }
						                    else
						                    {
						                    	$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $referrer_data['info_id'],'is_del' => 0))->row_array();
						                    	//print_r($business_info);die;
						                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
						                    	$b_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
												$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
						                    }
										}
										/*$push_msg_b = 'Referral '.$referrer_data['assign_referral_id'].' status has been updated to "Job complete" by referral';
										if($get_referral_status['business_id']!= '0')
							                    {
							                    	$this->db->select("P.user_id,register_id,P.device_type");
								                    $this->db->where('P.device_type',1);
								                    $this->db->where('P.user_id', $get_referral_status['business_id']);
								                    $this->db->where('U.is_notification',0);
								                    $this->db->from("tbl_push AS P");
								                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
								                    $this->db->order_by('P.push_id',"DESC");
								                    $register_token = $this->db->get()->row_array();
								                //  print_r($register_token);die;
								                    if (!empty($register_token)) {
								                        
								                        $push_data['multiple'] = 0;
								                        $push_data['register_id'] = trim($register_token['register_id']);
								                        $push_data['device_type'] = $register_token['device_type'];

								                        
								                        $message_array['custom_data']['message'] =  $push_msg;
								                        $message_array['custom_data']['notification_type'] = 3;
								                        $message_array['custom_data']['notification'] = 'set_status_to_job_in_progress';
								                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
			                        					$message_array['custom_data']['info_id'] = $input_data['info_id'];
			                        					$message_array['custom_data']['is_flag'] = 1;

								                        $n_data = array(
											
														'user_id' => $register_token['user_id'],
														'message' => $push_msg,
														'notification_post_user_id'=> $input_data['user_id'],
														'notification_type'=>$message_array['custom_data']['notification_type'],
														'date_added' => date('Y-m-d H:i:s')
														
														);
														$this->mdl_common->insert_record('tbl_notification',$n_data);
														//echo $this->db->last_query();die;
														$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $register_token['user_id']))->num_rows();
														$message_array['custom_data']['count'] = $notification_count;
								                      //  $message_array['custom_data']['msg_count'] = $msg_count;
								                       
								                        //notification_type
								                        
								                        //print_r($message_array);die;
								                    //  $message_array['custom_data']['type'] = "video_call";
								                        $data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								                        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
								                    }
							                    }
							                    else
							                    {
							                    	$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $get_referral_status['info_id'],'is_del' => 0))->row_array();
							                    	//print_r($business_info);die;
							                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
							                    	$b_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
													$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
							                    }
*/

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
									$response[RESPONSE_MESSAGE] = __bUSINESS_ALREADY_SET_TO_CANCEL;
								}
							}
							
						}
						
					}
					else
					{
						$status_cancel_exist = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
						if($status_cancel_exist['referral_status'] == '1')
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_CANCEL;
						}
						else
						{
							if($exist_referrer_data['business_status'] < 3) // check for job in progress or completed
							{
								$set_status=$this->mdl_common->update_record('tbl_manage_referrer',Array('referral_id' => $input_data['user_id'],'manage_id' => $input_data['manage_id'],'info_id'=> $input_data['info_id']),array('business_status'=>$input_data['referral_status'],'referral_status'=> $input_data['referral_status']));
								if($set_status)
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
									$response[RESPONSE_MESSAGE] = _SET_REFERRAL_STATUS;

									header ( 'Content-Type: application/json' );
									echo json_encode ( $response );
									$size = ob_get_length ();
									header ( "Content-Length: $size" );
									header ( 'Connection: close' );
									header ( "Content-Encoding: none\r\n" );
									ob_end_flush ();
									ob_flush ();
									flush ();

									if(session_id ())
									session_write_close ();

									$referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();

									$push_msg = 'Referral '.$referrer_data['assign_referral_id'].' has been cancelled by referral';
											$this->db->select("P.user_id,register_id,P.device_type");
						                    //$this->db->where('P.device_type',1);
						                    $this->db->where('P.user_id', $referrer_data['referrer_id']);
						                    $this->db->where('U.is_notification',0);
						                    $this->db->from("tbl_push AS P");
						                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
						                    $this->db->order_by('P.push_id',"DESC");
						                    $register_token = $this->db->get()->result_array();
						                //  print_r($register_token);die;
						                    if (!empty($register_token)) {
						                        
						                        $push_data_len = count($register_token);
												$android_device_arr = array();
												$ios_device_arr = array();

						                        $n_data = array(
									
												'user_id' => $referrer_data['referrer_id'],
												'message' => $push_msg,
												'notification_post_user_id'=> $input_data['user_id'],
												'is_flag'=> 2,
												'manage_id'=> $input_data['manage_id'],
												'info_id'=>$input_data['info_id'],
												'notification'=> 'set_status_to_cancelled',
												'notification_type'=>6,
												'date_added' => date('Y-m-d H:i:s')
												
												);
												$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
												foreach ($register_token as $value) {
													if($value['device_type'] == 0)
													{
														array_push($android_device_arr, $value['register_id']);
													}
													else
													{
														array_push($ios_device_arr, $value['register_id']);
													}
											    }

						                        
						                        $message_array['custom_data']['message'] =  $push_msg;
						                        $message_array['custom_data']['notification_type'] = 6;
						                        $message_array['custom_data']['notification'] = 'set_status_to_cancelled';
						                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        				$message_array['custom_data']['is_flag'] = 2;

						                        
												//echo $this->db->last_query();die;
												$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['referrer_id']))->num_rows();
												$message_array['custom_data']['count'] = $notification_count;
												$message_array['custom_data']['notification_id'] = $notification_id;
						                         if(!empty($android_device_arr)){
													$push_data['device_type'] = 0;
													$push_data['register_id'] = $android_device_arr;
													$push_data['multiple'] = 1;
													
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
													
												}
												if(!empty($ios_device_arr)){
													$push_data['device_type'] = 1;
													$push_data['register_id'] = $ios_device_arr;
													$push_data['multiple'] = 1;
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
											        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
												}
						            }
						            if($referrer_data['business_id']!= '0')
				                    {
				                    	$this->db->select("P.user_id,register_id,P.device_type");
					                    //$this->db->where('P.device_type',1);
					                    $this->db->where('P.user_id', $referrer_data['business_id']);
					                    $this->db->where('U.is_notification',0);
					                    $this->db->from("tbl_push AS P");
					                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
					                    $this->db->order_by('P.push_id',"DESC");
					                    $register_token = $this->db->get()->result_array();
					                //  print_r($register_token);die;
					                    if (!empty($register_token)) {
					                        
					                       $push_data_len = count($register_token);
											$android_device_arr = array();
											$ios_device_arr = array();

											$n_data = array(
								
											'user_id' => $referrer_data['business_id'],
											'message' => $push_msg,
											'notification_post_user_id'=> $input_data['user_id'],
											'is_flag'=> 1,
											'manage_id'=> $input_data['manage_id'],
											'info_id'=>$input_data['info_id'],
											'notification'=> 'set_status_to_cancelled',
											'notification_type'=>6,
											'date_added' => date('Y-m-d H:i:s')
											
											);
											$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
											foreach ($register_token as $value) {
												if($value['device_type'] == 0)
												{
													array_push($android_device_arr, $value['register_id']);
												}
												else
												{
													array_push($ios_device_arr, $value['register_id']);
												}
										    }
					                        
					                        $message_array['custom_data']['message'] =  $push_msg;
					                        $message_array['custom_data']['notification_type'] = 6;
					                        $message_array['custom_data']['notification'] = 'set_status_to_cancelled';
					                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        			$message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        			$message_array['custom_data']['is_flag'] = 1;

					                        
											//echo $this->db->last_query();die;
											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_data['business_id']))->num_rows();
											$message_array['custom_data']['count'] = $notification_count;
											$message_array['custom_data']['notification_id'] = $notification_id;
					                     	
					                     	if(!empty($android_device_arr)){
												$push_data['device_type'] = 0;
												$push_data['register_id'] = $android_device_arr;
												$push_data['multiple'] = 1;
												
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
												
											}
											if(!empty($ios_device_arr)){
												$push_data['device_type'] = 1;
												$push_data['register_id'] = $ios_device_arr;
												$push_data['multiple'] = 1;
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
										        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
											}

					                    }
				                    }
				                    else
				                    {
				                    	$business_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $referrer_data['info_id'],'is_del' => 0))->row_array();
				                    	//print_r($business_info);die;
				                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
				                    	$b_to = $business_info['business_country_code'].''.$business_info['business_mobile_no'];
										$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
				                    } 


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
				/*}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __CANNOT_MODIFY_BEFORE_BUSINESS_RESPONSE;
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
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);

	}
	public function pay_referral_fee_by_business_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		$input_data['paid_amount'] = $this->input->post('paid_amount',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);
		$input_data['currency_code'] = $this->input->post('currency_code',TRUE);
		//$input_data['referral_status'] = $this->input->post('referral_status',TRUE); // 0= new , 1= cancel , 2= view , 3= job in progress , 4= job complete	

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id','currency_code');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'business_id' => $input_data['user_id'],'is_del'=>0))->row_array(); 
			if(!empty($exist_referrer_data))
			{

				//$get_card_data = $this->db->get_where('tbl_card',Array('business_id' => $input_data['user_id'],'is_del' => '0'))->row_array();
				/*if(!empty($get_card_data))
				{*/
					//if(($get_card_data['card_token']!= '') && ($get_card_data['card_stripe_token']!=''))
					//{
						$get_account_data = $this->db->get_where('tbl_referrer_bank',Array('referrer_id' => $exist_referrer_data['referrer_id'],'is_del' => '0'))->row_array();
						if(!empty($get_account_data))
						{
							if($get_account_data['account_stripe_token']!='')
							{
								/*if($exist_referrer_data['referral_status'] >= 4)
								{*/
									/*if($exist_referrer_data['business_status']  >= 4)
									{*/
										if($exist_referrer_data['business_status'] != 6)
										{
											if($input_data['paid_amount']!= '')
											{
												$sender_data = $this->mdl_common->get_record('tbl_user',Array('user_id' => $input_data['user_id'],'is_del' => 0))->row_array();
										
												$receiver_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_id' => $exist_referrer_data['referrer_id'], 'is_del' => 0))->row_array();

												$commission = $this->mdl_common->get_record('tbl_commission',Array('is_del' => 0),'commission')->row()->commission;

												try
								                {   
								                    // for manage db
								                    $amt = $price = $input_data['paid_amount'];
								                    $fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
								                    $change_amount = $amt - $fees;
								                    $pay = ($change_amount * $commission) / 100;
								                    $pay_amt = $change_amount - $pay;
								                    
								                    //for manange stripe
								                    $amount = ($input_data['paid_amount'] * 100);
								                   // echo $amount;
								                    $stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
								                    $change_amt = $amount - round($stripe_fees);
								                   // echo $stripe_fees;die;
								                    $payble = ($change_amt * $commission) / 100;
								                    $payble_amount = round($change_amt) - round($payble);
								                    
								                   $charge = \Stripe\Charge::create(array(
													  "amount" => $amount,
													  "currency" => $input_data['currency_code'],
													  "customer" => $sender_data['customer_token'],
													  "statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
													  /*"destination" => array(
													  	"amount" => $payble_amount,
													    "account" => $receiver_data['customer_token'],
														),*/ 
													  "transfer_data[destination]" => $receiver_data['customer_token'],
													  "transfer_data[amount]" => $payble_amount
													));
													$charge_id = $charge->id;
												} catch (Exception $e) {
													$error = $e->getMessage();
													$charge_id = '';
												}
												//echo $charge_id;
												//echo $error;die;
												if($charge_id != '')
												{
													$ins_data['user_id'] = $input_data['user_id'];
													$ins_data['manage_id'] = $input_data['manage_id'];
													$ins_data['info_id'] = $input_data['info_id'];
													$ins_data['is_paid'] = 0;
													$ins_data['fees'] = $fees;
													$ins_data['commission']	= $pay;										
													$ins_data['charge_id'] = $charge_id;
													$ins_data['amount'] = $amt;
													$ins_data['payable_amount'] = number_format($pay_amt, 2, '.', '');
													$ins_data['currency'] = $get_account_data['currency'];
													$ins_data['payment_date'] = date('Y-m-d');
													$ins_data['date_added'] = date('Y-m-d H:i:s');
													$this->mdl_common->insert_record('tbl_payment',$ins_data);

													$ins_rdata['user_id'] = $get_account_data['referrer_id'];
													$ins_rdata['manage_id'] = $input_data['manage_id'];
													$ins_rdata['info_id'] = $input_data['info_id'];
													$ins_rdata['is_paid'] = 1;
													$ins_rdata['fees'] = $fees;
													$ins_rdata['commission']= $pay;											
													$ins_rdata['charge_id'] = $charge_id;
													$ins_rdata['amount'] = $amt;
													$ins_rdata['payable_amount'] = number_format($pay_amt, 2, '.', '');
													$ins_rdata['currency'] = $get_account_data['currency'];
													$ins_rdata['payment_date'] = date('Y-m-d');
													$ins_rdata['date_added'] = date('Y-m-d H:i:s');
													$this->mdl_common->insert_record('tbl_payment',$ins_rdata);

													$up_status_data['business_status'] = 6;
													$up_status_data['referral_status'] = 6;
						                        //$up_data['modified_date'] = date('Y-m-d H:i:s');
						                        $this->mdl_common->update_record('tbl_manage_referrer',Array('business_id' => $input_data['user_id'],'manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id']),$up_status_data);

													$response[RESPONSE_FLAG]= RESPONSE_FLAG_SUCCESS;
		                   							$response[RESPONSE_MESSAGE] = __PAYMENT_SUCCESSFULLY;  

					                   								// Send push after response
													header ( 'Content-Type: application/json' );
													echo json_encode ( $response );
													$size = ob_get_length ();
													header ( "Content-Length: $size" );
													header ( 'Connection: close' );
													header ( "Content-Encoding: none\r\n" );
													ob_end_flush ();
													ob_flush ();
													flush ();

													if(session_id ())
													session_write_close ();

													$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
													$business_name = $business_data['first_name'].' '.$business_data['last_name'];
													$ref_data = $this->db->get_where('tbl_user',Array('user_id'=> $exist_referrer_data['referrer_id'],'is_del' => 0))->row_array();
													$ref_name = $ref_data['first_name'].' '.$ref_data['last_name'];
													//$push_msg =  $business_name." has viewed your Referral.";
													//$push_msg="Please update your bank account details to receive payment from ".$business_name;
													$push_msg="Congratulations ".$ref_name.",you have received a referral fee from ".$business_name." on behalf of Referral ".$exist_referrer_data['assign_referral_id'];

													$this->db->select("P.user_id,register_id,P.device_type");
								                    //$this->db->where('P.device_type',1);
								                    $this->db->where('P.user_id', $exist_referrer_data['referrer_id']);
								                    $this->db->where('U.is_notification',0);
								                    $this->db->from("tbl_push AS P");
								                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
								                    $this->db->order_by('P.push_id',"DESC");
								                    $register_token = $this->db->get()->result_array();
								               	 //  print_r($register_token);die;
								                    if (!empty($register_token)) {
								                        
								                        $push_data_len = count($register_token);
														$android_device_arr = array();
														$ios_device_arr = array();

								                        $n_data = array(
											
														'user_id' => $exist_referrer_data['referrer_id'],
														'message' => $push_msg,
														'notification_post_user_id'=> $input_data['user_id'],
														'is_flag'=> 2,
														'manage_id'=> $input_data['manage_id'],
														'info_id'=>$input_data['info_id'],
														'notification'=> 'payment_complete_from_business',
														'notification_type'=>11,
														'date_added' => date('Y-m-d H:i:s')
														
														);
														$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
														foreach ($register_token as $value) {
															if($value['device_type'] == 0)
															{
																array_push($android_device_arr, $value['register_id']);
															}
															else
															{
																array_push($ios_device_arr, $value['register_id']);
															}
													    }

								                        $message_array['custom_data']['message'] =  $push_msg;
								                        $message_array['custom_data']['notification_type'] = 11;
								                        $message_array['custom_data']['notification'] = 'payment_complete_from_business';
								                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
								                        $message_array['custom_data']['info_id'] = $input_data['info_id'];
								                        $message_array['custom_data']['is_flag'] = 2;

								                        
														//echo $this->db->last_query();die;
														$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $exist_referrer_data['referrer_id']))->num_rows();
														$message_array['custom_data']['count'] = $notification_count;
														$message_array['custom_data']['notification_id'] = $notification_id;
								                      
								                      	if(!empty($android_device_arr)){
															$push_data['device_type'] = 0;
															$push_data['register_id'] = $android_device_arr;
															$push_data['multiple'] = 1;
															
															
															$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
															
														}
														if(!empty($ios_device_arr)){
															$push_data['device_type'] = 1;
															$push_data['register_id'] = $ios_device_arr;
															$push_data['multiple'] = 1;
															
															$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
														}
								                    }

												}
												else 
												{
													$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								                    $response[RESPONSE_MESSAGE] = $error;
								                    $this->response($response, 200);
												}

											}
											

										}
										else
										{
											$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
											$response[RESPONSE_MESSAGE] = __ALREADY_STATUS_PAID;
										}

									/*}
									else
									{
										$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
										$response[RESPONSE_MESSAGE] = __SET_JOB_COMPLETED_FIRST;
									}*/

								/*}
								else
								{
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
									$response[RESPONSE_MESSAGE] = _UPDATE_STATUS_NEEDED_BY_REFERRAL;
								}*/
							}
							else
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] = _UPDATE_PAYMENT_METHOD_FOR_RECEIVED;

									// Send push after response
								header ( 'Content-Type: application/json' );
								echo json_encode ( $response );
								$size = ob_get_length ();
								header ( "Content-Length: $size" );
								header ( 'Connection: close' );
								header ( "Content-Encoding: none\r\n" );
								ob_end_flush ();
								ob_flush ();
								flush ();

								if(session_id ())
								session_write_close ();

								$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
								$business_name = $business_data['first_name'].' '.$business_data['last_name'];
								//$push_msg =  $business_name." has viewed your Referral.";
								$push_msg="Please update your bank account details to receive payment from ".$business_name;

								$this->db->select("P.user_id,register_id,P.device_type");
			                   // $this->db->where('P.device_type',1);
			                    $this->db->where('P.user_id', $exist_referrer_data['referrer_id']);
			                    $this->db->where('U.is_notification',0);
			                    $this->db->from("tbl_push AS P");
			                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
			                    $this->db->order_by('P.push_id',"DESC");
			                    $register_token = $this->db->get()->result_array();
			               	 //  print_r($register_token);die;
			                    if (!empty($register_token)) {
			                        
			                        $push_data_len = count($register_token);
									$android_device_arr = array();
									$ios_device_arr = array();

									$n_data = array(
						
									'user_id' => $exist_referrer_data['referrer_id'],
									'message' => $push_msg,
									'notification_post_user_id'=> $input_data['user_id'],
									//'is_flag'=> 2,
									'manage_id'=> $input_data['manage_id'],
									'info_id'=>$input_data['info_id'],
									'notification'=> 'update_payment_method_for_received',
									'notification_type'=>7,
									'date_added' => date('Y-m-d H:i:s')
									
									);
									$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
									foreach ($register_token as $value) {
										if($value['device_type'] == 0)
										{
											array_push($android_device_arr, $value['register_id']);
										}
										else
										{
											array_push($ios_device_arr, $value['register_id']);
										}
								    }
			                        
			                        $message_array['custom_data']['message'] =  $push_msg;
			                        $message_array['custom_data']['notification_type'] = 7;
			                        $message_array['custom_data']['notification'] = 'update_payment_method_for_received';
			                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
			                        $message_array['custom_data']['info_id'] = $input_data['info_id'];
			                        //$message_array['custom_data']['is_flag'] = 2;

			                        
									//echo $this->db->last_query();die;
									$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $exist_referrer_data['referrer_id']))->num_rows();
									$message_array['custom_data']['count'] = $notification_count;
									$message_array['custom_data']['notification_id'] = $notification_id;
			                     	
			                     	if(!empty($android_device_arr)){
										$push_data['device_type'] = 0;
										$push_data['register_id'] = $android_device_arr;
										$push_data['multiple'] = 1;
										
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
										
									}
									if(!empty($ios_device_arr)){
										$push_data['device_type'] = 1;
										$push_data['register_id'] = $ios_device_arr;
										$push_data['multiple'] = 1;
										
										$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
								        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
									}
			                    }
							}
						}
						else
						{
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
							$response[RESPONSE_MESSAGE] = _UPDATE_PAYMENT_METHOD_FOR_RECEIVED;

							// Send push after response
							header ( 'Content-Type: application/json' );
							echo json_encode ( $response );
							$size = ob_get_length ();
							header ( "Content-Length: $size" );
							header ( 'Connection: close' );
							header ( "Content-Encoding: none\r\n" );
							ob_end_flush ();
							ob_flush ();
							flush ();

							if(session_id ())
							session_write_close ();

							$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
							$business_name = $business_data['first_name'].' '.$business_data['last_name'];
							//$push_msg =  $business_name." has viewed your Referral.";
							$push_msg="Please update your bank account details to receive payment from ".$business_name;

							$this->db->select("P.user_id,register_id,P.device_type");
		                    //$this->db->where('P.device_type',1);
		                    $this->db->where('P.user_id', $exist_referrer_data['referrer_id']);
		                    $this->db->where('U.is_notification',0);
		                    $this->db->from("tbl_push AS P");
		                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
		                    $this->db->order_by('P.push_id',"DESC");
		                    $register_token = $this->db->get()->result_array();
		               	 //  print_r($register_token);die;
		                    if (!empty($register_token)) {
		                        
		                        $push_data_len = count($register_token);
								$android_device_arr = array();
								$ios_device_arr = array();

								$n_data = array(
					
								'user_id' => $exist_referrer_data['referrer_id'],
								'message' => $push_msg,
								'notification_post_user_id'=> $input_data['user_id'],
								//'is_flag'=> 2,
								'manage_id'=> $input_data['manage_id'],
								'info_id'=>$input_data['info_id'],
								'notification'=> 'update_payment_method_for_received',
								'notification_type'=>7,
								'date_added' => date('Y-m-d H:i:s')
								
								);
								$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
								foreach ($register_token as $value) {
									if($value['device_type'] == 0)
									{
										array_push($android_device_arr, $value['register_id']);
									}
									else
									{
										array_push($ios_device_arr, $value['register_id']);
									}
							    }
		                        
		                        $message_array['custom_data']['message'] =  $push_msg;
		                        $message_array['custom_data']['notification_type'] = 7;
		                        $message_array['custom_data']['notification'] = 'update_payment_method_for_received';
		                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
		                        $message_array['custom_data']['info_id'] = $input_data['info_id'];
		                        //$message_array['custom_data']['is_flag'] = 2;

		                        
								//echo $this->db->last_query();die;
								$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $exist_referrer_data['referrer_id']))->num_rows();
								$message_array['custom_data']['count'] = $notification_count;
								$message_array['custom_data']['notification_id'] = $notification_id;
		                     	
		                     	if(!empty($android_device_arr)){
									$push_data['device_type'] = 0;
									$push_data['register_id'] = $android_device_arr;
									$push_data['multiple'] = 1;
									
									
									$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
							        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
									
								}
								if(!empty($ios_device_arr)){
									$push_data['device_type'] = 1;
									$push_data['register_id'] = $ios_device_arr;
									$push_data['multiple'] = 1;
									
									$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
							        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
								}
		                    }
						}
					/*}
					else
					{	
						//$payment_data['is_card'] = 1;
						$response["is_card"] = 1;
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
						$response[RESPONSE_MESSAGE] = _UPDATE_PAYMENT_METHOD_FOR_PAID;
					}*/
				/*}
				else
				{
					//$payment_data['is_card'] = 1;
					$response["is_card"] = 1;
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = _UPDATE_PAYMENT_METHOD_FOR_PAID;
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
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}
	public function add_card_post()
	{
		$response = array();

        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        $input_data['timezone'] = $this->input->post('timezone', TRUE);
        $input_data['business_id'] = $this->input->post('business_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
       // $input_data['is_user_driver'] = $this->input->post('is_user_driver', TRUE); //0-user 1-driver
        //$input_data['card_id'] = $this->input->post('card_id', TRUE); //0-add 1-edit
        $input_data['card_stripe_id'] = $this->input->post('card_stripe_id', TRUE); 
        $input_data['card_token'] = $this->input->post('card_token', TRUE); //ex.1234 5678 9562 2563
      
        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $input_parameter = array('business_id','access_token','card_stripe_id','card_token');
            
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['business_id']); 

        if($res_validate_token['flag'] > 0)
        {
            $user_data = $this->mdl_common->get_record('tbl_user',Array('user_id' => $input_data['business_id'],'is_del' => 0))->row_array();
            
               // $card_detail = $this->mdl_common->get_record('tbl_card',Array('business_id!=' => $input_data['business_id'],'card_number' => $input_data['card_number'],'is_del' => 0))->row_array();
               /* if(empty($card_detail))
                {*/
                	$card_detail_exist = $this->mdl_common->get_record('tbl_card',Array('business_id' => $input_data['business_id'],'is_del' => 0))->row_array();
                	if(empty($card_detail_exist))
                	{   
                		$in_data['business_id'] = $input_data['business_id'];
                        $in_data['card_token'] = $input_data['card_stripe_id'];
                        $in_data['card_stripe_token'] = $input_data['card_token'];
                       // $insert_data['default_card'] = $default_card;
                        $in_data['created_date'] = date('Y-m-d H:i:s');
                     //   $card_id = $this->mdl_common->insert_record('tbl_credit_card', $insert_data);
                        $card_id = $this->mdl_common->insert_record('tbl_card',$in_data);
                        //echo $this->db->last_query();die;
                       if(!empty($card_id))
                        {  
                            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                            $response[RESPONSE_MESSAGE] = _ADD_CARD_SUCCESS;
                        }
                        else
                        {
                            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                            $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
                        }
                    }
                	else
                	{  
                	    $uptoken_data['business_id'] = $input_data['business_id'];    
	                    $uptoken_data['card_token'] = $input_data['card_stripe_id'];
	                    $uptoken_data['card_stripe_token'] = $input_data['card_token'];
				        $this->mdl_common->update_record('tbl_card',Array('business_id' => $input_data['business_id']),$uptoken_data);
	                       
	                           if($this->db->affected_rows())
	                            {
				                   
	                               /* if($default_card != 0)
	                                {
	                                        try
	                                        {
	                                            //$customer = \Stripe\Customer::retrieve($user_data['customer_token']);
	                                            $customer->default_source=$card_token;
	                                            $customer->save();

	                                            $card_token = $card_id;
	                                            
	                                        } catch (Exception $e) {
	                                            $error = $e->getMessage();
	                                            $card_token = '';
	                                        }
	                                 }
	                                $card_data = $this->mdl_common->get_record('tbl_card',Array('card_id' => $card_id),'card_id,user_id,is_user_driver,card_holder_name,card_number,exp_month,exp_year,cvc,default_card')->row_array();*/

	                                $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
	                                $response[RESPONSE_MESSAGE] = _UPDATE_CARD_SUCCESS;
	                                //$card_data[ACCESS_TOKEN] = $input_data['access_token'];
	                                //$response[RESPONSE_DATA] = $card_data; 
	                            }
	                            else
	                            {
	                                $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                                $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
	                            }
                	}
              
        }
        else
        {
            $response[RESPONSE_FLAG] = $res_validate_token['flag'];
            $response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

        $this->response($response, 200);   
    }
    function get_account_details_post(){
		$response = array();

        $input_data['referrer_id'] = $this->input->post('referrer_id', TRUE);

        $account_exist = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id'],'is_del' => 0))->row_array();
    	if(!empty($account_exist)){
    		$acct = \Stripe\Account::retrieve($account_exist['customer_token']);
    		echo $acct;die;
    		echo $acct->individual->verification->status;die;

    	}else{
    		$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
    	}
        $this->response($response,200);		
	}
	function save_referrer_account_post()
    {
		$response = array();

        $input_data['referrer_id'] = $this->input->post('referrer_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
        $input_data['first_name'] = $this->input->post('first_name', TRUE);
        $input_data['last_name'] = $this->input->post('last_name', TRUE);
        $input_data['birth_date'] = $this->input->post('birth_date', TRUE);
        $input_data['country'] = $this->input->post('country', TRUE);
        $input_data['state'] = $this->input->post('state', TRUE);
        $input_data['currency'] = $this->input->post('currency', TRUE);
        $input_data['city'] = $this->input->post('city', TRUE);
        $input_data['postal_code'] = $this->input->post('postal_code', TRUE);
        $input_data['address'] = $this->input->post('address', TRUE);
        $input_data['s_address'] = $this->input->post('s_address', TRUE);
        $input_data['phone_no'] = $this->input->post('phone_no', TRUE);
        $input_data['email'] = $this->input->post('email', TRUE);
        $input_data['security_number'] = $this->input->post('security_number', TRUE);
        $input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng
       // $input_data['tos_acceptance_date'] = $this->input->post('tos_acceptance_date', TRUE);
        $input_data['tos_acceptance_ip'] = $this->input->post('tos_acceptance_ip', TRUE);
        
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
        
		$input_parameter = array('referrer_id','access_token','birth_date','country','city','postal_code','address','email','first_name','last_name');
		
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['referrer_id']);

        if ($res_validate_token['flag'] > 0) {

        	$account_exist = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id'],'is_del' => 0))->row_array();

        	if(empty($account_exist))
        	{
	            try
				{
					//for version 6.31.2 stripe
					/*$account = \Stripe\Account::create(array(
					  "type" => "custom",
					  "country" => "US",
					  "email" => $input_data['email'],
					  "requested_capabilities" => ['card_payments'],
					 "individual[first_name]" =>$input_data['first_name'],
					  "individual[last_name]" =>$input_data['last_name'],
					   "business_type" => 'individual',
					  "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
					  "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
					  "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
					  "individual[ssn_last_4]" => $input_data['security_number'],
					  "individual[address][line1]" => $input_data['address'],
					  "individual[address][city]" => $input_data['city'],
					  "individual[address][postal_code]" => $input_data['postal_code'],
					  "individual[address][state]" => $input_data['state']
					));*/

					/*	$account = \Stripe\Account::create(array(
						  "type" => "custom",
						  "country" => "US",
						  "email" => $input_data['email'],
						  "legal_entity[first_name]" =>$input_data['first_name'],
						  "legal_entity[last_name]" =>$input_data['last_name'],
						  "legal_entity[type]" => "individual",
						  "legal_entity[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
						  "legal_entity[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
						  "legal_entity[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
						  "legal_entity[ssn_last_4]" => $input_data['security_number'],
						  "legal_entity[address][line1]" => $input_data['address'],
						  "legal_entity[address][city]" => $input_data['city'],
						  "legal_entity[address][postal_code]" => $input_data['postal_code'],
						  "legal_entity[address][state]" => $input_data['state']
						));
						*/	
					$get_country_code =  $this->db->get_where('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
					$c_code = $get_country_code['country_code'];

					$account = \Stripe\Account::create([
		               "type" => "custom",
		               "country" => $input_data['country'],
		               "email" => $input_data['email'],
		              "requested_capabilities" => ['card_payments','transfers'],
		               "individual[first_name]" => $input_data['first_name'],
		               "individual[last_name]" => $input_data['last_name'],
		               "business_type" => "individual",
		               "business_profile[url]" => 'www.referralapp.net',
		               "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
		               "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
		               "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
		              // "individual[id_number]" => '000000000',
		              // "individual[ssn_last_4]" => $input_data['security_number'],
		               "individual[address][line1]" => $input_data['address'],
		               "individual[address][line2]" => (@$input_data['s_address']) ? $input_data['s_address'] : '',
		               "individual[address][city]" => $input_data['city'],
		               "individual[address][postal_code]" => $input_data['postal_code'],
		               "individual[address][state]" => $input_data['state'],
		               "individual[email]" => $input_data['email'],
		               "individual[phone]" => $c_code.$input_data['phone_no'],
		               "business_profile[mcc]" => '5969',
		               "tos_acceptance[date]" => time(),
		               "tos_acceptance[ip]" => $input_data['tos_acceptance_ip']
		           ]);
	           
					
					$acct = \Stripe\Account::retrieve($account->id);
					$acct->tos_acceptance->date = time();
					// Assumes you're not using a proxy
					$acct->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
					$acct->save();
			
					$account_id = $account->id;
					//$account->details_submitted;
					//$account->charges_enabled;
					//$account->transfers_enabled;

				} catch (Exception $e) {
					$error = $e->getMessage();
					$account_id = '';
				}
				
				if($account_id!='')
				{
					$ins_data['referrer_id'] = $input_data['referrer_id'];
					$ins_data['first_name'] = $input_data['first_name'];
					$ins_data['last_name'] = $input_data['last_name'];
					$ins_data['birth_date'] = date('Y-m-d',strtotime($input_data['birth_date']));
					$ins_data['country'] = $input_data['country'];
					$ins_data['currency'] = $input_data['currency'];
					$ins_data['state'] = $input_data['state'];
					$ins_data['city'] = $input_data['city'];
					$ins_data['postal_code'] = $input_data['postal_code'];
					$ins_data['address'] = $input_data['address'];
					
					if($input_data['s_address']!= '')
					{
						$ins_data['address2'] = $input_data['s_address'];
						$b_address = $input_data['address'].' '.$input_data['s_address'];
					}
					else
					{
						$ins_data['address2'] = '';
						$b_address= $input_data['address'];
					}
					$ins_data['email'] = $input_data['email'];
					//$ins_data['security_number'] = $input_data['security_number'];
					
					$ins_data['customer_token'] = $account_id;
					$ins_data['created_date'] = date('Y-m-d H:i:s');
					
					if($input_data['state']!= '')
					{
						$get_state =  $this->db->get_where('tbl_states',array('short_name'=>$input_data['state'],'is_del'=>0))->row_array(); 
						if(!empty($get_state))
						{
							$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('state'=>$get_state['state_name'],'date_modified'=>date('Y-m-d H:i:s')));
						}
					}

					if($input_data['city']!= '')
					{	
						$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('city'=>$input_data['city'],'date_modified'=>date('Y-m-d H:i:s')));
						
					}

					$update_address = $this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('address'=>$b_address,'postal_code'=>$input_data['postal_code'],'date_modified'=>date('Y-m-d H:i:s')));
					$acc_id = $this->mdl_common->insert_record('tbl_referrer_account', $ins_data);
					//echo $this->db->last_query();;die;
					if(!empty($acc_id))
					{
						/*$account_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_account_id' => $acc_id))->row_array();
						unset($account_data['is_del']);
						unset($account_data['created_date']);
						unset($account_data['modified_date']);*/


					$user_data = $this->mdl_user->get_user_details($input_data['referrer_id']);


					$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($get_Referrer_bank))
	        		{
	        			$user_data['is_bank_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_bank_added'] = 0;
	        		}


					$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($get_Referrer_account))
	        		{
	        			$user_data['is_account_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_account_added'] = 0;
	        		}
	        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($exist_card))
	        		{
	        			$user_data['is_card_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_card_added'] = 0;
	        		}

	        		$user_data['account_token'] = $account_id;
						
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
	                $response[RESPONSE_MESSAGE] = _ACCOUNT_ADDED_SUCCESS;
	                $user_data[ACCESS_TOKEN] = $input_data['access_token'];
	                $response[RESPONSE_DATA] = $user_data;
						
					} else {
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
					}
					
				} else {
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                $response[RESPONSE_MESSAGE] = $error;
				}
		
			}
	    	else
	    	{
	    		try{
    				$acct = \Stripe\Account::retrieve($account_exist['customer_token']);
    				$acct_id = $acct->id;
	    		}catch(Exception $e){
	    			$acc_error = $e->getMessage();
					$acct_id = '';
	    		}
	    		//print_r($account_exist);
	    		if($acct_id !=''){
	    			$acc_status= $acct->individual->verification->status;
	    			if($acc_status == ACCOUNT_STATUS){
			    		
			    		$test_array = array(
			    			  "email" => $input_data['email'],
				              "requested_capabilities" => ['card_payments','transfers'],
				               "individual[first_name]" => $input_data['first_name'],
				               "individual[last_name]" => $input_data['last_name'],
				               "business_type" => "individual",
				               "business_profile[url]" => 'www.referralapp.net',
				               "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
				               "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
				               "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
				              // "individual[id_number]" => '000000000',
				               //"individual[ssn_last_4]" => $input_data['security_number'],
				               "individual[address][line1]" => $input_data['address'],
				               "individual[address][line2]" => (@$input_data['s_address']) ? $input_data['s_address'] : '',
				               "individual[address][city]" => $input_data['city'],
				               "individual[address][postal_code]" => $input_data['postal_code'],
				               "individual[address][state]" => $input_data['state'],
				               "individual[email]" => $input_data['email'],
				               "individual[phone]" => $c_code.$input_data['phone_no'],
				               "business_profile[mcc]" => '5969',
				               "tos_acceptance[date]" => time(),
				               "tos_acceptance[ip]" => $input_data['tos_acceptance_ip']
			    		);
			    		//print_r($test_array);die;
			    		try
						{
							//for version 6.31.2 stripe
							/*$account = \Stripe\Account::create(array(
							  "type" => "custom",
							  "country" => "US",
							  "email" => $input_data['email'],
							  "requested_capabilities" => ['card_payments'],
							 "individual[first_name]" =>$input_data['first_name'],
							  "individual[last_name]" =>$input_data['last_name'],
							   "business_type" => 'individual',
							  "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
							  "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
							  "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
							  "individual[ssn_last_4]" => $input_data['security_number'],
							  "individual[address][line1]" => $input_data['address'],
							  "individual[address][city]" => $input_data['city'],
							  "individual[address][postal_code]" => $input_data['postal_code'],
							  "individual[address][state]" => $input_data['state']
							));*/

							/*	$account = \Stripe\Account::create(array(
							  "type" => "custom",
							  "country" => "US",
							  "email" => $input_data['email'],
							  "legal_entity[first_name]" =>$input_data['first_name'],
							  "legal_entity[last_name]" =>$input_data['last_name'],
							  "legal_entity[type]" => "individual",
							  "legal_entity[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
							  "legal_entity[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
							  "legal_entity[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
							  "legal_entity[ssn_last_4]" => $input_data['security_number'],
							  "legal_entity[address][line1]" => $input_data['address'],
							  "legal_entity[address][city]" => $input_data['city'],
							  "legal_entity[address][postal_code]" => $input_data['postal_code'],
							  "legal_entity[address][state]" => $input_data['state']
							));
							*/	
							$get_country_code =  $this->db->get_where('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
							$c_code = $get_country_code['country_code'];

							$account = \Stripe\Account::update($account_exist['customer_token'],[
				               //"type" => "custom",
				               //"country" => $input_data['country'],
				               "email" => $input_data['email'],
				              "requested_capabilities" => ['card_payments','transfers'],
				               "individual[first_name]" => $input_data['first_name'],
				               "individual[last_name]" => $input_data['last_name'],
				               "business_type" => "individual",
				               "business_profile[url]" => 'www.referralapp.net',
				               "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
				               "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
				               "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
				              // "individual[id_number]" => '000000000',
				               //"individual[ssn_last_4]" => $input_data['security_number'],
				               "individual[address][line1]" => $input_data['address'],
				               "individual[address][line2]" => (@$input_data['s_address']) ? $input_data['s_address'] : '',
				               "individual[address][city]" => $input_data['city'],
				               "individual[address][postal_code]" => $input_data['postal_code'],
				               "individual[address][state]" => $input_data['state'],
				               "individual[email]" => $input_data['email'],
				               "individual[phone]" => $c_code.$input_data['phone_no'],
				               "business_profile[mcc]" => '5969',
				               "tos_acceptance[date]" => time(),
				               "tos_acceptance[ip]" => $input_data['tos_acceptance_ip']
				           ]);
			           
							
							$acct = \Stripe\Account::retrieve($account->id);
							$acct->tos_acceptance->date = time();
							// Assumes you're not using a proxy
							$acct->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
							$acct->save();
					
							$account_id = $account->id;
							//$account->details_submitted;
							//$account->charges_enabled;
							//$account->transfers_enabled;

						} catch (Exception $e) {
							$error = $e->getMessage();
							$account_id = '';
						}
						//echo $error;die;
						if($account_id!='')
						{
							//$ins_data['referrer_id'] = $input_data['referrer_id'];
							$up_acc_data['first_name'] = $input_data['first_name'];
							$up_acc_data['last_name'] = $input_data['last_name'];
							$up_acc_data['birth_date'] = date('Y-m-d',strtotime($input_data['birth_date']));
							$up_acc_data['country'] = $input_data['country'];
							$up_acc_data['currency'] = $input_data['currency'];
							$up_acc_data['state'] = $input_data['state'];
							$up_acc_data['city'] = $input_data['city'];
							$up_acc_data['postal_code'] = $input_data['postal_code'];
							$up_acc_data['address'] = $input_data['address'];
							if($input_data['s_address']!= '')
							{
								$up_acc_data['address2'] = $input_data['s_address'];
								$b_address = $input_data['address'].' '.$input_data['s_address'];
							}
							else
							{
								$up_acc_data['address2'] = '';
								$b_address= $input_data['address'];
							}
							
							$up_acc_data['email'] = $input_data['email'];
							//$up_acc_data['security_number'] = $input_data['security_number'];
							$up_acc_data['address'] = $input_data['address'];
							//$up_acc_data['customer_token'] = $account_id;
							$up_acc_data['modified_date'] = date('Y-m-d H:i:s');

							if($input_data['state']!= '')
							{
								$get_state =  $this->db->get_where('tbl_states',array('short_name'=>$input_data['state'],'is_del'=>0))->row_array(); 
								if(!empty($get_state))
								{
									$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('state'=>$get_state['state_name'],'date_modified'=>date('Y-m-d H:i:s')));
								}
							}

							if($input_data['city']!= '')
							{	
								$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('city'=>$input_data['city'],'date_modified'=>date('Y-m-d H:i:s')));
								
							}
							$update_address = $this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('address'=>$b_address,'postal_code'=>$input_data['postal_code'],'date_modified'=>date('Y-m-d H:i:s')));
							
							$referral_update= $this->mdl_common->update_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id']),$up_acc_data);
							//echo $this->db->last_query();;die;
							if($referral_update)
							{
								


							$user_data = $this->mdl_user->get_user_details($input_data['referrer_id']);


							$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($get_Referrer_bank))
			        		{
			        			$user_data['is_bank_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_bank_added'] = 0;
			        		}


							$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($get_Referrer_account))
			        		{
			        			$user_data['is_account_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_account_added'] = 0;
			        		}
			        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($exist_card))
			        		{
			        			$user_data['is_card_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_card_added'] = 0;
			        		}

			        		$user_data['account_token'] = $account_id; //account token for update
							
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
			                $response[RESPONSE_MESSAGE] = _ACCOUNT_UPDATED_SUCCESS;
			                $user_data[ACCESS_TOKEN] = $input_data['access_token'];
			                $response[RESPONSE_DATA] = $user_data;
								
							} else {
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
							}
							
						} else {
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			                $response[RESPONSE_MESSAGE] = STRIPE_VALIDATION_MSG_FOR_VERIFIED_ACCOUNTS;
						}
					}else{
						try
						{
							//for version 6.31.2 stripe
							/*$account = \Stripe\Account::create(array(
							  "type" => "custom",
							  "country" => "US",
							  "email" => $input_data['email'],
							  "requested_capabilities" => ['card_payments'],
							 "individual[first_name]" =>$input_data['first_name'],
							  "individual[last_name]" =>$input_data['last_name'],
							   "business_type" => 'individual',
							  "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
							  "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
							  "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
							  "individual[ssn_last_4]" => $input_data['security_number'],
							  "individual[address][line1]" => $input_data['address'],
							  "individual[address][city]" => $input_data['city'],
							  "individual[address][postal_code]" => $input_data['postal_code'],
							  "individual[address][state]" => $input_data['state']
							));*/

							/*	$account = \Stripe\Account::create(array(
							  "type" => "custom",
							  "country" => "US",
							  "email" => $input_data['email'],
							  "legal_entity[first_name]" =>$input_data['first_name'],
							  "legal_entity[last_name]" =>$input_data['last_name'],
							  "legal_entity[type]" => "individual",
							  "legal_entity[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
							  "legal_entity[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
							  "legal_entity[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
							  "legal_entity[ssn_last_4]" => $input_data['security_number'],
							  "legal_entity[address][line1]" => $input_data['address'],
							  "legal_entity[address][city]" => $input_data['city'],
							  "legal_entity[address][postal_code]" => $input_data['postal_code'],
							  "legal_entity[address][state]" => $input_data['state']
							));
							*/	
							$get_country_code =  $this->db->get_where('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
							$c_code = $get_country_code['country_code'];

							$account = \Stripe\Account::update($account_exist['customer_token'],[
				               //"type" => "custom",
				               //"country" => $input_data['country'], 
				               "email" => $input_data['email'],
				              "requested_capabilities" => ['card_payments','transfers'],
				               "individual[first_name]" => $input_data['first_name'],
				               "individual[last_name]" => $input_data['last_name'],
				               "business_type" => "individual",
				               "business_profile[url]" => 'www.referralapp.net',
				               "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
				               "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
				               "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
				              // "individual[id_number]" => '000000000',
				               //"individual[ssn_last_4]" => $input_data['security_number'],
				               "individual[address][line1]" => $input_data['address'],
				               "individual[address][line2]" => (@$input_data['s_address']) ? $input_data['s_address'] : '',
				               "individual[address][city]" => $input_data['city'],
				               "individual[address][postal_code]" => $input_data['postal_code'],
				               "individual[address][state]" => $input_data['state'],
				               "individual[email]" => $input_data['email'],
				               "individual[phone]" => $c_code.$input_data['phone_no'],
				               "business_profile[mcc]" => '5969',
				               "tos_acceptance[date]" => time(),
				               "tos_acceptance[ip]" => $input_data['tos_acceptance_ip']
				           ]);
			           
							
							$acct = \Stripe\Account::retrieve($account->id);
							$acct->tos_acceptance->date = time();
							// Assumes you're not using a proxy
							$acct->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
							$acct->save();
					
							$account_id = $account->id;
							//$account->details_submitted;
							//$account->charges_enabled;
							//$account->transfers_enabled;

						} catch (Exception $e) {
							$error = $e->getMessage();
							$account_id = '';
						}
						
						if($account_id!='')
						{
							//$ins_data['referrer_id'] = $input_data['referrer_id'];
							$up_acc_data['first_name'] = $input_data['first_name'];
							$up_acc_data['last_name'] = $input_data['last_name'];
							$up_acc_data['birth_date'] = date('Y-m-d',strtotime($input_data['birth_date']));
							$up_acc_data['country'] = $input_data['country'];
							$up_acc_data['currency'] = $input_data['currency'];
							$up_acc_data['state'] = $input_data['state'];
							$up_acc_data['city'] = $input_data['city'];
							$up_acc_data['postal_code'] = $input_data['postal_code'];
							$up_acc_data['address'] = $input_data['address'];
							if($input_data['s_address']!= '')
							{
								$up_acc_data['address2'] = $input_data['s_address'];
								$b_address = $input_data['address'].' '.$input_data['s_address'];
							}
							else
							{
								$up_acc_data['address2'] = '';
								$b_address= $input_data['address'];
							}
							
							$up_acc_data['email'] = $input_data['email'];
							//$up_acc_data['security_number'] = $input_data['security_number'];
							$up_acc_data['address'] = $input_data['address'];
							//$up_acc_data['customer_token'] = $account_id;
							$up_acc_data['modified_date'] = date('Y-m-d H:i:s');

							if($input_data['state']!= '')
							{
								$get_state =  $this->db->get_where('tbl_states',array('short_name'=>$input_data['state'],'is_del'=>0))->row_array(); 
								if(!empty($get_state))
								{
									$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('state'=>$get_state['state_name'],'date_modified'=>date('Y-m-d H:i:s')));
								}
							}

							if($input_data['city']!= '')
							{	
								$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('city'=>$input_data['city'],'date_modified'=>date('Y-m-d H:i:s')));
								
							}
							$update_address = $this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('address'=>$b_address,'postal_code'=>$input_data['postal_code'],'date_modified'=>date('Y-m-d H:i:s')));
							
							$referral_update=$this->mdl_common->update_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id']),$up_acc_data);
							//echo $this->db->last_query();;die;
							if($referral_update)
							{
								/*$account_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_account_id' => $acc_id))->row_array();
								unset($account_data['is_del']);
								unset($account_data['created_date']);
								unset($account_data['modified_date']);*/


							$user_data = $this->mdl_user->get_user_details($input_data['referrer_id']);


							$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($get_Referrer_bank))
			        		{
			        			$user_data['is_bank_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_bank_added'] = 0;
			        		}


							$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($get_Referrer_account))
			        		{
			        			$user_data['is_account_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_account_added'] = 0;
			        		}
			        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
			        		if(!empty($exist_card))
			        		{
			        			$user_data['is_card_added'] = 1;
			        		}
			        		else
			        		{
			        			$user_data['is_card_added'] = 0;
			        		}

			        		$user_data['account_token'] = $account_id; //account token for update
							
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
			                $response[RESPONSE_MESSAGE] = _ACCOUNT_UPDATED_SUCCESS;
			                $user_data[ACCESS_TOKEN] = $input_data['access_token'];
			                $response[RESPONSE_DATA] = $user_data;
								
							} else {
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
							}
							
						} else {
							$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
			                $response[RESPONSE_MESSAGE] = $error;
						}
					}
				}else{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
		            $response[RESPONSE_MESSAGE] = $acc_error;
				}
	    	}
        } else {
            $response[RESPONSE_FLAG] = $res_validate_token['flag'];
            $response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }
        $this->response($response, 200);
	}
    function save_referrer_account1_post()
    {
		$response = array();

        $input_data['referrer_id'] = $this->input->post('referrer_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
        $input_data['first_name'] = $this->input->post('first_name', TRUE);
        $input_data['last_name'] = $this->input->post('last_name', TRUE);
        $input_data['birth_date'] = $this->input->post('birth_date', TRUE);
        $input_data['country'] = $this->input->post('country', TRUE);
        $input_data['state'] = $this->input->post('state', TRUE);
        $input_data['currency'] = $this->input->post('currency', TRUE);
        $input_data['city'] = $this->input->post('city', TRUE);
        $input_data['postal_code'] = $this->input->post('postal_code', TRUE);
        $input_data['address'] = $this->input->post('address', TRUE);
        $input_data['s_address'] = $this->input->post('s_address', TRUE);
        $input_data['phone_no'] = $this->input->post('phone_no', TRUE);
        $input_data['email'] = $this->input->post('email', TRUE);
        $input_data['security_number'] = $this->input->post('security_number', TRUE);
        $input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng
       // $input_data['tos_acceptance_date'] = $this->input->post('tos_acceptance_date', TRUE);
        $input_data['tos_acceptance_ip'] = $this->input->post('tos_acceptance_ip', TRUE);
        
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
        
		$input_parameter = array('referrer_id','access_token','birth_date','country','city','postal_code','address','email','first_name','last_name');
		
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['referrer_id']);

        if ($res_validate_token['flag'] > 0) {

        	$account_exist = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id'],'is_del' => 0))->row_array();
        	if(empty($account_exist))
        	{
	            try
				{
					//for version 6.31.2 stripe
					/*$account = \Stripe\Account::create(array(
					  "type" => "custom",
					  "country" => "US",
					  "email" => $input_data['email'],
					  "requested_capabilities" => ['card_payments'],
					 "individual[first_name]" =>$input_data['first_name'],
					  "individual[last_name]" =>$input_data['last_name'],
					   "business_type" => 'individual',
					  "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
					  "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
					  "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
					  "individual[ssn_last_4]" => $input_data['security_number'],
					  "individual[address][line1]" => $input_data['address'],
					  "individual[address][city]" => $input_data['city'],
					  "individual[address][postal_code]" => $input_data['postal_code'],
					  "individual[address][state]" => $input_data['state']
					));*/

					/*$account = \Stripe\Account::create(array(
					  "type" => "custom",
					  "country" => "US",
					  "email" => $input_data['email'],
					  "legal_entity[first_name]" =>$input_data['first_name'],
					  "legal_entity[last_name]" =>$input_data['last_name'],
					  "legal_entity[type]" => "individual",
					  "legal_entity[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
					  "legal_entity[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
					  "legal_entity[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
					  "legal_entity[ssn_last_4]" => $input_data['security_number'],
					  "legal_entity[address][line1]" => $input_data['address'],
					  "legal_entity[address][city]" => $input_data['city'],
					  "legal_entity[address][postal_code]" => $input_data['postal_code'],
					  "legal_entity[address][state]" => $input_data['state']
					));*/	
					$get_country_code =  $this->db->get_where('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
					$c_code = $get_country_code['country_code'];

					$account = \Stripe\Account::create([
						"type" => "custom",
						"country" => $input_data['country'],
						"email" => $input_data['email'],
						"requested_capabilities" => ['card_payments','transfers'],
						"individual[first_name]" => $input_data['first_name'],
						"individual[last_name]" => $input_data['last_name'],
						"business_type" => "individual",
						"business_profile[url]" => 'www.referralapp.net',
						"individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
						"individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
						"individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
						// "individual[id_number]" => '000000000',
						// "individual[ssn_last_4]" => $input_data['security_number'],
						"individual[address][line1]" => $input_data['address'],
						"individual[address][line2]" => (@$input_data['s_address']) ? $input_data['s_address'] : '',
						"individual[address][city]" => $input_data['city'],
						"individual[address][postal_code]" => $input_data['postal_code'],
						"individual[address][state]" => $input_data['state'],
						"individual[email]" => $input_data['email'],
						"individual[phone]" => $c_code.$input_data['phone_no'],
						"business_profile[mcc]" => '5969',
						"tos_acceptance[date]" => time(),
						"tos_acceptance[ip]" => $input_data['tos_acceptance_ip']
					]);
					
					$acct = \Stripe\Account::retrieve($account->id);
					$acct->tos_acceptance->date = time();
					// Assumes you're not using a proxy
					$acct->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
					$acct->save();
			
					$account_id = $account->id;
					//$account->details_submitted;
					//$account->charges_enabled;
					//$account->transfers_enabled;

				} catch (Exception $e) {
					$error = $e->getMessage();
					$account_id = '';
				}
				
				if(!empty($account_id))
				{
					$ins_data['referrer_id'] = $input_data['referrer_id'];
					$ins_data['first_name'] = $input_data['first_name'];
					$ins_data['last_name'] = $input_data['last_name'];
					$ins_data['birth_date'] = date('Y-m-d',strtotime($input_data['birth_date']));
					$ins_data['country'] = $input_data['country'];
					$ins_data['currency'] = $input_data['currency'];
					$ins_data['state'] = $input_data['state'];
					$ins_data['city'] = $input_data['city'];
					$ins_data['postal_code'] = $input_data['postal_code'];
					$ins_data['address'] = $input_data['address'];
					
					if($input_data['s_address']!= '')
					{
						$ins_data['address2'] = $input_data['s_address'];
						$b_address = $input_data['address'].' '.$input_data['s_address'];
					}
					else
					{
						$ins_data['address2'] = '';
						$b_address= $input_data['address'];
					}
					$ins_data['email'] = $input_data['email'];
					//$ins_data['security_number'] = $input_data['security_number'];
					
					$ins_data['customer_token'] = $account_id;
					$ins_data['created_date'] = date('Y-m-d H:i:s');
					
					if($input_data['state']!= '')
					{
						$get_state =  $this->db->get_where('tbl_states',array('short_name'=>$input_data['state'],'is_del'=>0))->row_array(); 
						if(!empty($get_state))
						{
							$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('state'=>$get_state['state_name'],'date_modified'=>date('Y-m-d H:i:s')));
						}
					}

					if($input_data['city']!= '')
					{	
						$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('city'=>$input_data['city'],'date_modified'=>date('Y-m-d H:i:s')));
						
					}

					$update_address = $this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('address'=>$b_address,'postal_code'=>$input_data['postal_code'],'date_modified'=>date('Y-m-d H:i:s')));
					$acc_id = $this->mdl_common->insert_record('tbl_referrer_account', $ins_data);
					//echo $this->db->last_query();;die;
					if(!empty($acc_id))
					{
						/*$account_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_account_id' => $acc_id))->row_array();
						unset($account_data['is_del']);
						unset($account_data['created_date']);
						unset($account_data['modified_date']);*/


					$user_data = $this->mdl_user->get_user_details($input_data['referrer_id']);


					$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($get_Referrer_bank))
	        		{
	        			$user_data['is_bank_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_bank_added'] = 0;
	        		}


					$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($get_Referrer_account))
	        		{
	        			$user_data['is_account_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_account_added'] = 0;
	        		}
	        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($exist_card))
	        		{
	        			$user_data['is_card_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_card_added'] = 0;
	        		}

	        		$user_data['account_token'] = $account_id;
						
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
	                $response[RESPONSE_MESSAGE] = _ACCOUNT_ADDED_SUCCESS;
	                $user_data[ACCESS_TOKEN] = $input_data['access_token'];
	                $response[RESPONSE_DATA] = $user_data;
						
					} else {
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
					}
					
				} else {
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                $response[RESPONSE_MESSAGE] = $error;
				}
		
			}
	    	else
	    	{
	    		//print_r($account_exist);
	    		try
				{
					//for version 6.31.2 stripe
					/*$account = \Stripe\Account::create(array(
					  "type" => "custom",
					  "country" => "US",
					  "email" => $input_data['email'],
					  "requested_capabilities" => ['card_payments'],
					 "individual[first_name]" =>$input_data['first_name'],
					  "individual[last_name]" =>$input_data['last_name'],
					   "business_type" => 'individual',
					  "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
					  "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
					  "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
					  "individual[ssn_last_4]" => $input_data['security_number'],
					  "individual[address][line1]" => $input_data['address'],
					  "individual[address][city]" => $input_data['city'],
					  "individual[address][postal_code]" => $input_data['postal_code'],
					  "individual[address][state]" => $input_data['state']
					));*/

				/*	$account = \Stripe\Account::create(array(
					  "type" => "custom",
					  "country" => "US",
					  "email" => $input_data['email'],
					  "legal_entity[first_name]" =>$input_data['first_name'],
					  "legal_entity[last_name]" =>$input_data['last_name'],
					  "legal_entity[type]" => "individual",
					  "legal_entity[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
					  "legal_entity[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
					  "legal_entity[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
					  "legal_entity[ssn_last_4]" => $input_data['security_number'],
					  "legal_entity[address][line1]" => $input_data['address'],
					  "legal_entity[address][city]" => $input_data['city'],
					  "legal_entity[address][postal_code]" => $input_data['postal_code'],
					  "legal_entity[address][state]" => $input_data['state']
					));
				*/	
				$get_country_code =  $this->db->get_where('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
				$c_code = $get_country_code['country_code'];

				$account = \Stripe\Account::update($account_exist['customer_token'],[
	               //"type" => "custom",
	               //"country" => $input_data['country'],
	               "email" => $input_data['email'],
	              "requested_capabilities" => ['card_payments','transfers'],
	              // "individual[first_name]" => $input_data['first_name'],
	               //"individual[last_name]" => $input_data['last_name'],
	               "business_type" => "individual",
	               "business_profile[url]" => 'www.referralapp.net',
	              // "individual[dob][month]" =>date('m',strtotime($input_data['birth_date'])),
	              // "individual[dob][day]" =>date('d',strtotime($input_data['birth_date'])),
	              // "individual[dob][year]" =>date('Y',strtotime($input_data['birth_date'])),
	              // "individual[id_number]" => '000000000',
	               //"individual[ssn_last_4]" => $input_data['security_number'],
	               "individual[address][line1]" => $input_data['address'],
	               "individual[address][line2]" => (@$input_data['s_address']) ? $input_data['s_address'] : '',
	               "individual[address][city]" => $input_data['city'],
	               "individual[address][postal_code]" => $input_data['postal_code'],
	               "individual[address][state]" => $input_data['state'],
	               "individual[email]" => $input_data['email'],
	               "individual[phone]" => $c_code.$input_data['phone_no'],
	               "business_profile[mcc]" => '5969',
	               "tos_acceptance[date]" => time(),
	               "tos_acceptance[ip]" => $input_data['tos_acceptance_ip']
	           ]);
	           
					
					$acct = \Stripe\Account::retrieve($account->id);
					$acct->tos_acceptance->date = time();
					// Assumes you're not using a proxy
					$acct->tos_acceptance->ip = $_SERVER['REMOTE_ADDR'];
					$acct->save();
			
					$account_id = $account->id;
					//$account->details_submitted;
					//$account->charges_enabled;
					//$account->transfers_enabled;

				} catch (Exception $e) {
					$error = $e->getMessage();
					$account_id = '';
				}
				
				if(!empty($account_id))
				{
					//$ins_data['referrer_id'] = $input_data['referrer_id'];
					$up_acc_data['first_name'] = $input_data['first_name'];
					$up_acc_data['last_name'] = $input_data['last_name'];
					$up_acc_data['birth_date'] = date('Y-m-d',strtotime($input_data['birth_date']));
					$up_acc_data['country'] = $input_data['country'];
					$up_acc_data['currency'] = $input_data['currency'];
					$up_acc_data['state'] = $input_data['state'];
					$up_acc_data['city'] = $input_data['city'];
					$up_acc_data['postal_code'] = $input_data['postal_code'];
					$up_acc_data['address'] = $input_data['address'];
					if($input_data['s_address']!= '')
					{
						$up_acc_data['address2'] = $input_data['s_address'];
						$b_address = $input_data['address'].' '.$input_data['s_address'];
					}
					else
					{
						$up_acc_data['address2'] = '';
						$b_address= $input_data['address'];
					}
					
					$up_acc_data['email'] = $input_data['email'];
					//$up_acc_data['security_number'] = $input_data['security_number'];
					$up_acc_data['address'] = $input_data['address'];
					//$up_acc_data['customer_token'] = $account_id;
					$up_acc_data['modified_date'] = date('Y-m-d H:i:s');

					if($input_data['state']!= '')
					{
						$get_state =  $this->db->get_where('tbl_states',array('short_name'=>$input_data['state'],'is_del'=>0))->row_array(); 
						if(!empty($get_state))
						{
							$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('state'=>$get_state['state_name'],'date_modified'=>date('Y-m-d H:i:s')));
						}
					}

					if($input_data['city']!= '')
					{	
						$this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('city'=>$input_data['city'],'date_modified'=>date('Y-m-d H:i:s')));
						
					}
					$update_address = $this->mdl_common->update_record('tbl_user',array('user_id'=>$input_data['referrer_id'],'is_del'=>0),array('address'=>$b_address,'postal_code'=>$input_data['postal_code'],'date_modified'=>date('Y-m-d H:i:s')));
					
					$this->mdl_common->update_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id']),$up_acc_data);
					//echo $this->db->last_query();;die;
					if($this->db->affected_rows())
					{
						/*$account_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_account_id' => $acc_id))->row_array();
						unset($account_data['is_del']);
						unset($account_data['created_date']);
						unset($account_data['modified_date']);*/


					$user_data = $this->mdl_user->get_user_details($input_data['referrer_id']);


					$get_Referrer_bank =  $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($get_Referrer_bank))
	        		{
	        			$user_data['is_bank_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_bank_added'] = 0;
	        		}


					$get_Referrer_account =  $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($get_Referrer_account))
	        		{
	        			$user_data['is_account_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_account_added'] = 0;
	        		}
	        		$exist_card =  $this->db->get_where('tbl_card',array('business_id'=>$input_data['referrer_id'],'is_del'=>0))->row_array(); 
	        		if(!empty($exist_card))
	        		{
	        			$user_data['is_card_added'] = 1;
	        		}
	        		else
	        		{
	        			$user_data['is_card_added'] = 0;
	        		}

	        		$user_data['account_token'] = $account_id; //account token for update
					
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
	                $response[RESPONSE_MESSAGE] = _ACCOUNT_UPDATED_SUCCESS;
	                $user_data[ACCESS_TOKEN] = $input_data['access_token'];
	                $response[RESPONSE_DATA] = $user_data;
						
					} else {
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
					}
					
				} else {
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                $response[RESPONSE_MESSAGE] = $error;
				}

	    	}
        } else {
            $response[RESPONSE_FLAG] = $res_validate_token['flag'];
            $response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

        $this->response($response, 200);
	}
	function save_bank_account_post()
    {
		$response = array();

        $input_data['referrer_id'] = $this->input->post('referrer_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
        
      // $input_data['bank_id'] = $this->input->post('bank_id', TRUE);
       $input_data['account_stripe_token'] = $this->input->post('account_stripe_token', TRUE);
        $input_data['currency'] = $this->input->post('currency', TRUE);
        $input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng
        
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
        
        
		$input_parameter = array('referrer_id','access_token','account_stripe_token','currency');
		
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['referrer_id']);

        if ($res_validate_token['flag'] > 0) 
        {
        	//$referrer_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_id' => $input_data['referrer_id'],'is_del' => 0),'customer_token')->row_array();
        	
        		//$account_no1 = preg_replace( "#(.*?)(\d{4})$#", "$2", $input_data['account_number']);
        		//$bank_detail = $this->mdl_common->get_record('tbl_referrer_bank',Array('referrer_id!=' => $input_data['referrer_id'],'account_number' => $input_data['account_number'],'is_del' => 0))->row_array();

        		/*if(!empty($referrer_data))
        		{*/

	        		/*if(empty($bank_detail))
	        		{*/
	        			$bank_detail_exist = $this->mdl_common->get_record('tbl_referrer_bank',Array('referrer_id' => $input_data['referrer_id'],'is_del' => 0))->row_array();
	        			if(empty($bank_detail_exist))
	        			{
		        			
							//	$account_no = preg_replace( "#(.*?)(\d{4})$#", "$2", $input_data['account_number']);
								/*$bank_data = $this->mdl_common->get_record('tbl_coach_bank',Array('coach_id' => $input_data['coach_id'],'is_del' => 0))->result_array();
								if (!empty($bank_data)) {
									$default_bank = 0;
								} else {
									$default_bank = 1;
								}*/
								
								$ins_data['referrer_id'] = $input_data['referrer_id'];
								
								$ins_data['account_stripe_token'] = $input_data['account_stripe_token'];
								$ins_data['currency'] = $input_data['currency'];
								//$ins_data['default_bank'] = $default_bank;
								$ins_data['created_date'] = date('Y-m-d H:i:s');
								
								$bank_id = $this->mdl_common->insert_record('tbl_referrer_bank', $ins_data);
								if(!empty($bank_id))
								{
									$bank_data = $this->mdl_common->get_record('tbl_referrer_bank',Array('bank_id' => $bank_id))->row_array();
									unset($bank_data['is_del']);
									unset($bank_data['created_date']);
									unset($bank_data['modified_date']);
									unset($bank_data['bank_verified']);
									
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					                $response[RESPONSE_MESSAGE] = _BANK_ADDED_SUCCESS;
					                $response[ACCESS_TOKEN] = $input_data['access_token'];
					                $response[RESPONSE_DATA] = $bank_data;
									
								} else {
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
								}
							 
						}
						else
						{
								$bnk_data['referrer_id'] = $input_data['referrer_id'];
								
								$bnk_data['account_stripe_token'] = $input_data['account_stripe_token'];
								$bnk_data['currency'] = $input_data['currency'];

								$bnk_data['modified_date'] = date('Y-m-d H:i:s');
								
								$this->mdl_common->update_record('tbl_referrer_bank',Array('referrer_id' => $input_data['referrer_id']),$bnk_data);
								//echo $this->db->last_query();die;
								if($this->db->affected_rows())
								{

									$bank_data = $this->mdl_common->get_record('tbl_referrer_bank',Array('referrer_id' => $input_data['referrer_id']))->row_array();
									unset($bank_data['is_del']);
									unset($bank_data['created_date']);
									unset($bank_data['modified_date']);
									unset($bank_data['bank_verified']);
									
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					                $response[RESPONSE_MESSAGE] = _BANK_UPDATE_SUCCESS;
					                $response[ACCESS_TOKEN] = $input_data['access_token'];
					                $response[RESPONSE_DATA] = $bank_data;
									
								} else {
									$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				                	$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
								}
							
						}
	        		/*}
		            else 
		            {
		            	$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	                	$response[RESPONSE_MESSAGE] = __ACCOUNT_NUMBER_ALREADY_EXIST;
		            }*/
	           /* }
    			else
    			{
    				
    				$response["is_account_added"] = 0;
    				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
    				$response[RESPONSE_MESSAGE] = __CUSTOMER_ACCOUNT_DETAILS_NOT_EXISTS;
    			}*/ 
			
			
        } else {
            $response[RESPONSE_FLAG] = $res_validate_token['flag'];
            $response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }
  
        $this->response($response, 200);
	}
	function country_list_post()
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
  
		$input_parameter = array('user_id','access_token');
		
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
        if($res_validate_token['flag'] > 0)
        {
        	 $this->db->select('country_id,c_sort_name,currency,country_name');
	        $this->db->from('tbl_countries');
	     //   $this->db->where('country_id',$input_data['country_id']);
	         $this->db->where('is_del',0);
	        $country = $this->db->get()->result_array();

	        if (!empty($country)) {
	            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
	            $response[RESPONSE_MESSAGE] = "Country List";
	            $response[RESPONSE_DATA] = $country;
	        } else {
	            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	            $response[RESPONSE_MESSAGE] = __COUNTRY_LIST_EMPTY;
	        }
        }
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }
        $this->response($response,200);


	}
	function state_list_post() 
    {
        $response = array();
      //  $input_data['country_id'] = $this->input->get('country_id', TRUE);


        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
         $input_data['country_id'] = $this->input->post('country_id', TRUE);
      
	    $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        $this->mdl_common->load_constants_for_lang($input_data['lang']);
  
		$input_parameter = array('user_id','access_token','country_id');
		
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if ($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
            $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
        if($res_validate_token['flag'] > 0)
        {
    
	        $this->db->select('id,short_name,state_name');
	        $this->db->from('tbl_states');
	        $this->db->where('country_id',$input_data['country_id']);
	         $this->db->where('is_del',0);
	        $state = $this->db->get()->result_array();

	        if (!empty($state)) {
	            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
	            $response[RESPONSE_MESSAGE] = _STATE_LIST;
	            $response[RESPONSE_DATA] = $state;
	        } else {
	            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
	            $response[RESPONSE_MESSAGE] = __STATE_LIST_EMPTY;
	        }
        }
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

        $this->response($response, 200);
    }
    function get_card_details_post()
    {
    	 $response = array();

        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        
        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
       

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $input_parameter = array('user_id','access_token','lang');
            
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']); 

        if($res_validate_token['flag'] > 0)
        {
           $card_data = $this->db->get_where('tbl_card',array('business_id'=>$input_data['user_id'],'is_del'=>0))->row_array();
            
            if(!empty($card_data)) 
            {
            	$card = Array(
                                'card_id' => $card_data['card_id'],
                                'user_id' => $card_data['business_id'],
                               
                                'card_holder_name' => $card_data['card_holder_name'],
                                //'card_number' =>  "**** **** **** ".preg_replace( "#(.*?)(\d{4})$#", "$2", str_replace(" ", "", $card_data['card_number'])),
                                'card_number' =>  "**** **** **** ".$card_data['card_number'],
                                'cvc' =>"***",
                                'expiry_date' => '**'.'/'.'**',
                                
                                ); 
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                $response[RESPONSE_MESSAGE] = "Card details";
                
                $response[RESPONSE_DATA] = $card;
            } 
            else 
            {
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                $response[RESPONSE_MESSAGE] = __CARD_NOT_EXISTS;
            }
        }
        else
        {
            $response[RESPONSE_FLAG] = $res_validate_token['flag'];
            $response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

        $this->response($response, 200);   
    }
    function get_user_account_details_post()
    {
    	$response = array();
    	$input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
       

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $input_parameter = array('user_id','access_token');
            
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']); 
        if($res_validate_token['flag'] > 0)
        {
        	$account_data = $this->db->get_where('tbl_referrer_account',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array();
            
            if(!empty($account_data)) 
            {
            	/*$bank = Array(
                                'bank_id' => $account_data['bank_id'],
                                'user_id' => $account_data['referrer_id'],
                               
                                'account_holder' => $account_data['account_holder'],
                                'bank_name' => $account_data['bank_name'],
                                'routing_number/sort_code' => "*******",
                                'account_number' => "*********".preg_replace( "#(.*?)(\d{4})$#", "$2", $account_data['account_number'])
                                
                                ); */
                unset($account_data['security_number']);
                unset($account_data['is_del']);
                unset($account_data['created_date']);
                unset($account_data['modified_date']);                
                unset($account_data['customer_token']);
                unset($account_data['email']);
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                $response[RESPONSE_MESSAGE] = "Account details";
                
                $response[RESPONSE_DATA] = $account_data;
            } 
            else 
            {
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                $response[RESPONSE_MESSAGE] = __ACCOUNT_NOT_EXISTS;
            }
        }
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }
        $this->response($response,200);

    }
    function get_bank_details_post()
    {
    	 $response = array();

        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        
        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
       

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $input_parameter = array('user_id','access_token','lang');
            
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']); 

        if($res_validate_token['flag'] > 0)
        {
           $bank_data = $this->db->get_where('tbl_referrer_bank',array('referrer_id'=>$input_data['user_id'],'is_del'=>0))->row_array();
            
            if(!empty($bank_data)) 
            {
            	$bank = Array(
                                'bank_id' => $bank_data['bank_id'],
                                'user_id' => $bank_data['referrer_id'],
                               
                                'account_holder' => $bank_data['account_holder'],
                                'bank_name' => $bank_data['bank_name'],
                                'routing_number/sort_code' => "*******",
                                'account_number' =>  "*********".$bank_data['account_number']
                              //  'account_number' => "*********".preg_replace( "#(.*?)(\d{4})$#", "$2", $bank_data['account_number'])
                                
                                ); 
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
                $response[RESPONSE_MESSAGE] = "Bank details";
                
                $response[RESPONSE_DATA] = $bank;
            } 
            else 
            {
                $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
                $response[RESPONSE_MESSAGE] = __BANK_NOT_EXISTS;
            }
        }
        else
        {
            $response[RESPONSE_FLAG] = $res_validate_token['flag'];
            $response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

        $this->response($response, 200);   
    }
    public function payment_history_post()
    {
    	 $response = array();

        $input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
        
        $input_data['user_id'] = $this->input->post('user_id', TRUE);
        $input_data['access_token'] = $this->input->post('access_token', TRUE);
         $input_data['offset'] = $this->input->post('offset', TRUE);
       

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $input_parameter = array('user_id','access_token','lang','offset');
            
        $validation = $this->ParamValidation($input_parameter, $input_data);

        if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
        $this->response($validation, 200);

        $res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']); 
        if($res_validate_token['flag'] > 0)
        {
        	$payment_count = count($this->mdl_user->get_payment_history($input_data));
        	$payment_history = $this->mdl_user->get_payment_history($input_data,$input_data['offset']);
        	if(!empty($payment_history))
        	{
        		$next_offset = $input_data['offset'] + PER_PAGE_LIST;
        		if($payment_count > $next_offset)
        		{
        			$response[RESPONSE_NEXT_OFFSET] = $next_offset;
        		}
        		else
        		{
        			$response[RESPONSE_NEXT_OFFSET] = -1;
        		}
        		$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
        		$response[RESPONSE_MESSAGE] = "Payment history list";
        		$response[RESPONSE_DATA]= $payment_history;
        	}
        	else
        	{
        		$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
        		$response[RESPONSE_MESSAGE] = __PAYMENTS_NOT_FOUND;
        	}
        }
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }
        $this->response($response,200);

    }
    public function send_reminder_post()
    {
    	$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);

		$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		//$input_data['paid_amount'] = $this->input->post('paid_amount',TRUE);
		$input_data['info_id'] = $this->input->post('info_id',TRUE);
		//$input_data['reminder_date'] = $this->input->post('reminder_date',TRUE);
		//$input_data['referral_status'] = $this->input->post('referral_status',TRUE); // 0= new , 1= cancel , 2= view , 3= job in progress , 4= job complete	

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','manage_id','info_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$exist_referral = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
			if(!empty($exist_referral))
			{
				$exist_set_reminder = $this->db->get_where('tbl_send_reminder',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'user_id'=>$input_data['user_id'],'user_type'=>1,'is_del'=>0))->row_array();
				if(!empty($exist_set_reminder))
				{
					$c_date = date('Y-m-d');
					if(strtotime($exist_set_reminder['reminder_date']) < strtotime($c_date))
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
						$response[RESPONSE_MESSAGE] = _SENT_REMINDER;
						$check_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
						if($check_status['business_status'] < '4')//0 1,2,3 check referral status
						{
							if($check_status['business_status'] != '1')
							{
								header ( 'Content-Type: application/json' );
								echo json_encode ( $response );
								$size = ob_get_length ();
								header ( "Content-Length: $size" );
								header ( 'Connection: close' );
								header ( "Content-Encoding: none\r\n" );
								ob_end_flush ();
								ob_flush ();
								flush ();

								if(session_id ())
								session_write_close ();

								$referrer_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
						     	$referrer_name = $referrer_data['first_name'].' '.$referrer_data['last_name'];

							//	$push_msg = $referrer_name.' is checking on the status of Referral '.$check_status['assign_referral_id'];

								$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'CUSTOMER_AND_BUSINESS','when_send'=>'REFERRER_SENDS_A_REMINDER_AND_STATUS_IS_NOT_JOB_COMPLETE','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
								
								//$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);
								$push_msg=str_ireplace('<referrer_name>',$referrer_name,str_ireplace('<referrer_id>',$check_status['assign_referral_id'],$get_push_msg['message']));
								// send to customer
							  if($check_status['referral_id']!= '0')
			                    {
			                    	$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $check_status['referral_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                        $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();

				                        /*$push_data['multiple'] = 0;
				                        $push_data['register_id'] = trim($register_token['register_id']);
				                        $push_data['device_type'] = $register_token['device_type'];*/
				                        $n_data = array(
							
										'user_id' => $check_status['referral_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 3,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'send_reminder_job_not_complete',
										'notification_type'=>8,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }
				                        
				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 8;
				                        $message_array['custom_data']['notification'] = 'send_reminder_job_not_complete';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
                        				$message_array['custom_data']['is_flag'] = 3;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_status['referral_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                      	
				                      	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
			                    }
			                    else
			                    {
			                    	$referral_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_status['info_id'],'is_del' => 0))->row_array();
			                    	//print_r($referral_info);die;
			                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
			                    	$rl_to = $referral_info['referral_country_code'].''.$referral_info['referral_mobile_no'];
									$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$rl_to),$push_msg);
			                    }

			                    //send to business
			                    if($check_status['business_id']!= '0')
			                    {
			                    	$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $check_status['business_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                        $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();
				                       /* $push_data['multiple'] = 0;
				                        $push_data['register_id'] = trim($register_token['register_id']);
				                        $push_data['device_type'] = $register_token['device_type'];*/

				                        $check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$check_status['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
										if(!empty($check_complete_status))
										{
											$is_allow_visible = 1;
										}
										else
										{
											$is_allow_visible = 0;
										}

				                        $n_data = array(
							
										'user_id' => $check_status['business_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 1,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'send_reminder_job_not_complete',
										'notification_type'=>8,
										'is_allow_visible' => $is_allow_visible,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }
				                        
				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 8;
				                        $message_array['custom_data']['notification'] = 'send_reminder_job_not_complete';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
                        				$message_array['custom_data']['is_flag'] = 1;
                        				$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_status['business_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                      	
				                      	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
			                    }
			                    else
			                    {
			                    	$b_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_status['info_id'],'is_del' => 0))->row_array();
			                    	//print_r($referral_info);die;
			                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
			                    	$b_to = $b_info['business_country_code'].''.$b_info['business_mobile_no'];
									$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
			                    }
			                    $date = strtotime(date('Y-m-d'));
								$r_date = strtotime("+7 day", $date);
								$reminder_date= date('Y-m-d', $r_date);
								$this->mdl_common->update_record('tbl_send_reminder',array('reminder_id'=>$exist_set_reminder['reminder_id'],'is_del'=>0),array('reminder_date'=>$reminder_date));

							}
							else
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] = __bUSINESS_ALREADY_SET_TO_CANCEL_REMINDER;
							}
						}
						else
						{
							if($check_status['business_status'] < '6' )// not paid 
							{
								header ( 'Content-Type: application/json' );
								echo json_encode ( $response );
								$size = ob_get_length ();
								header ( "Content-Length: $size" );
								header ( 'Connection: close' );
								header ( "Content-Encoding: none\r\n" );
								ob_end_flush ();
								ob_flush ();
								flush ();

								if(session_id ())
								session_write_close ();

								$referrer_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
							     	$referrer_name = $referrer_data['first_name'].' '.$referrer_data['last_name'];

								//	$push_msg = $referrer_name.' is waiting for payment of the referral fee which is now due.';

									$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'BUSINESS','when_send'=>'REFERRER_SENDS_A_REMINDER_AND_STATUS_IS_NOT_JOB_COMPLETE','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
								
								//$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);
								$push_msg=str_ireplace('<referrer_name>',$referrer_name,$get_push_msg['message']);	
							 //send to business
			                    if($check_status['business_id']!= '0')
			                    {
			                    	$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $check_status['business_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                        $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();

				                        $check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$check_status['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
										if(!empty($check_complete_status))
										{
											$is_allow_visible = 1;
										}
										else
										{
											$is_allow_visible = 0;
										}
										$n_data = array(
							
										'user_id' => $check_status['business_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 1,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'send_reminder_job_complete',
										'notification_type'=>9,
										'is_allow_visible' => $is_allow_visible,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }
				                        
				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 9;
				                        $message_array['custom_data']['notification'] = 'send_reminder_job_complete';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                    				$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                    				$message_array['custom_data']['is_flag'] = 1;
	                    				$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_status['business_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                     	
				                     	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
			                    }
			                    else
			                    {
			                    	$b_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_status['info_id'],'is_del' => 0))->row_array();
			                    	//print_r($referral_info);die;
			                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
			                    	$b_to = $b_info['business_country_code'].''.$b_info['business_mobile_no'];
									$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
			                    }
			                     $date = strtotime(date('Y-m-d'));
								$r_date = strtotime("+7 day", $date);
								$reminder_date= date('Y-m-d', $r_date);
								$this->mdl_common->update_record('tbl_send_reminder',array('reminder_id'=>$exist_set_reminder['reminder_id'],'is_del'=>0),array('reminder_date'=>$reminder_date));
							}
						}
					}
					else
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
						$response[RESPONSE_MESSAGE] = __REMINDER_RESTRICTION;
					}
				}
				else
				{
					$date = strtotime(date('Y-m-d'));
					$r_date = strtotime("+7 day", $date);
					$reminder_date= date('Y-m-d', $r_date);
					$add_reminder_data = array(
						'manage_id'=> $input_data['manage_id'],
						'info_id'=> $input_data['info_id'],
						'user_id'=> $input_data['user_id'],
						'user_type'=> 1,
						'reminder_date'=> $reminder_date
						
					);
					$reminder_id = $this->mdl_common->insert_record('tbl_send_reminder',$add_reminder_data);
					if($reminder_id)
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
						$response[RESPONSE_MESSAGE] = _SENT_REMINDER;
						$check_status = $this->db->get_where('tbl_manage_referrer',array('manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id'],'is_del'=>0))->row_array();
						if($check_status['business_status'] < '4')//0 1,2,3 check referral status
						{
							if($check_status['business_status'] != '1')
							{
								header ( 'Content-Type: application/json' );
								echo json_encode ( $response );
								$size = ob_get_length ();
								header ( "Content-Length: $size" );
								header ( 'Connection: close' );
								header ( "Content-Encoding: none\r\n" );
								ob_end_flush ();
								ob_flush ();
								flush ();

								if(session_id ())
								session_write_close ();

								$referrer_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
						     	$referrer_name = $referrer_data['first_name'].' '.$referrer_data['last_name'];

								//$push_msg = $referrer_name.' is checking on the status of Referral '.$check_status['assign_referral_id'];

								$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'CUSTOMER_AND_BUSINESS','when_send'=>'REFERRER_SENDS_A_REMINDER_AND_STATUS_IS_NOT_JOB_COMPLETE','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
								
								//$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);
								$push_msg=str_ireplace('<referrer_name>',$referrer_name,str_ireplace('<referrer_id>',$check_status['assign_referral_id'],$get_push_msg['message']));	
								// send to customer
							  if($check_status['referral_id']!= '0')
			                    {
			                    	$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $check_status['referral_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                        /*$push_data['multiple'] = 0;
				                        $push_data['register_id'] = trim($register_token['register_id']);
				                        $push_data['device_type'] = $register_token['device_type'];*/
				                         $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();
				                        
				                        $n_data = array(
							
										'user_id' => $check_status['referral_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 3,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'send_reminder_job_not_complete',
										'notification_type'=>8,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }

				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 8;
				                        $message_array['custom_data']['notification'] = 'send_reminder_job_not_complete';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
                        				$message_array['custom_data']['is_flag'] = 3;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_status['referral_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                     	
				                     	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
			                    }
			                    else
			                    {
			                    	$referral_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_status['info_id'],'is_del' => 0))->row_array();
			                    	//print_r($referral_info);die;
			                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
			                    	$rl_to = $referral_info['referral_country_code'].''.$referral_info['referral_mobile_no'];
									$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$rl_to),$push_msg);
			                    }

			                    //send to business
			                    if($check_status['business_id']!= '0')
			                    {
			                    	$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $check_status['business_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                    	$push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();

				                        $check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$check_status['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
										if(!empty($check_complete_status))
										{
											$is_allow_visible = 1;
										}
										else
										{
											$is_allow_visible = 0;
										}
										$n_data = array(
							
										'user_id' => $check_status['business_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 1,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'send_reminder_job_not_complete',
										'notification_type'=>8,
										'is_allow_visible' => $is_allow_visible,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }

				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 8;
				                        $message_array['custom_data']['notification'] = 'send_reminder_job_not_complete';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
                        				$message_array['custom_data']['info_id'] = $input_data['info_id'];
                        				$message_array['custom_data']['is_flag'] = 1;
                        				$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_status['business_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                      	
				                      	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
			                    }
			                    else
			                    {
			                    	$b_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_status['info_id'],'is_del' => 0))->row_array();
			                    	//print_r($referral_info);die;
			                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
			                    	$b_to = $b_info['business_country_code'].''.$b_info['business_mobile_no'];
									$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
			                    }

							}
							else
							{
								$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
								$response[RESPONSE_MESSAGE] = __bUSINESS_ALREADY_SET_TO_CANCEL_REMINDER;
							}
						}
						else
						{
							if($check_status['business_status'] < '6')// not paid
							{	
								header ( 'Content-Type: application/json' );
								echo json_encode ( $response );
								$size = ob_get_length ();
								header ( "Content-Length: $size" );
								header ( 'Connection: close' );
								header ( "Content-Encoding: none\r\n" );
								ob_end_flush ();
								ob_flush ();
								flush ();

								if(session_id ())
								session_write_close ();

								$referrer_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
							     	$referrer_name = $referrer_data['first_name'].' '.$referrer_data['last_name'];

									//$push_msg = $referrer_name.' is waiting for payment of the referral fee which is now due.';
									$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'BUSINESS','when_send'=>'REFERRER_SENDS_A_REMINDER_AND_STATUS_IS_NOT_JOB_COMPLETE','type'=>'PUSH_NOTIFICATION_OR_SMS_IF_USER_IS_NOT_REGISTERED'))->row_array();
								
								//$push_msg = str_ireplace('<referrer_id>', $referrer_data['assign_referral_id'], $get_push_msg['message']);
								$push_msg=str_ireplace('<referrer_name>',$referrer_name,$get_push_msg['message']);	
							 //send to business
			                    if($check_status['business_id']!= '0')
			                    {
			                    	$this->db->select("P.user_id,register_id,P.device_type");
				                    //$this->db->where('P.device_type',1);
				                    $this->db->where('P.user_id', $check_status['business_id']);
				                    $this->db->where('U.is_notification',0);
				                    $this->db->from("tbl_push AS P");
				                    $this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
				                    $this->db->order_by('P.push_id',"DESC");
				                    $register_token = $this->db->get()->result_array();
				                //  print_r($register_token);die;
				                    if (!empty($register_token)) {
				                        
				                        $push_data_len = count($register_token);
										$android_device_arr = array();
										$ios_device_arr = array();
				                        /*$push_data['multiple'] = 0;
				                        $push_data['register_id'] = trim($register_token['register_id']);
				                        $push_data['device_type'] = $register_token['device_type'];*/

				                        $check_complete_status = $this->db->get_where('tbl_manage_referrer',array('business_id'=>$check_status['business_id'],'business_status'=>'4','is_del'=>0))->row_array();
										if(!empty($check_complete_status))
										{
											$is_allow_visible = 1;
										}
										else
										{
											$is_allow_visible = 0;
										}
				                        $n_data = array(
							
										'user_id' => $check_status['business_id'],
										'message' => $push_msg,
										'notification_post_user_id'=> $input_data['user_id'],
										'is_flag'=> 1,
										'manage_id'=> $input_data['manage_id'],
										'info_id'=>$input_data['info_id'],
										'notification'=> 'send_reminder_job_complete',
										'notification_type'=>9,
										'is_allow_visible' => $is_allow_visible,
										'date_added' => date('Y-m-d H:i:s')
										
										);
										$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
										foreach ($register_token as $value) {
											if($value['device_type'] == 0)
											{
												array_push($android_device_arr, $value['register_id']);
											}
											else
											{
												array_push($ios_device_arr, $value['register_id']);
											}
									    }

				                        $message_array['custom_data']['message'] =  $push_msg;
				                        $message_array['custom_data']['notification_type'] = 9;
				                        $message_array['custom_data']['notification'] = 'send_reminder_job_complete';
				                        $message_array['custom_data']['manage_id'] = $input_data['manage_id'];
	                    				$message_array['custom_data']['info_id'] = $input_data['info_id'];
	                    				$message_array['custom_data']['is_flag'] = 1;
	                    				$message_array['custom_data']['is_allow_visible'] = $is_allow_visible;

				                        
										//echo $this->db->last_query();die;
										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $check_status['business_id']))->num_rows();
										$message_array['custom_data']['count'] = $notification_count;
										$message_array['custom_data']['notification_id'] = $notification_id;
				                      	
				                      	if(!empty($android_device_arr)){
											$push_data['device_type'] = 0;
											$push_data['register_id'] = $android_device_arr;
											$push_data['multiple'] = 1;
											
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
											
										}
										if(!empty($ios_device_arr)){
											$push_data['device_type'] = 1;
											$push_data['register_id'] = $ios_device_arr;
											$push_data['multiple'] = 1;
											
											$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
									        $this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
										}
				                    }
			                    }
			                    else
			                    {
			                    	$b_info = $this->db->get_where('tbl_new_referrer_info',Array('info_id'=> $check_status['info_id'],'is_del' => 0))->row_array();
			                    	//print_r($referral_info);die;
			                    	//$referral_data = $this->db->get_where('tbl_user',Array('user_id'=> $check_update_status['referral_id'],'is_del' => 0))->row_array();
			                    	$b_to = $b_info['business_country_code'].''.$b_info['business_mobile_no'];
									$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$b_to),$push_msg);
			                    }
							}
					    }
					}
					else
					{
						$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
						$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
					}
				}
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __REFERRAL_NOT_EXIST;
			} 

		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
    }
    public function notification_list_post()
    {
    	$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['timezone'] = $this->input->post('timezone',TRUE);
		$input_data['offset'] = $this->input->post('offset',TRUE);
		$input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';

		//$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		//$input_data['paid_amount'] = $this->input->post('paid_amount',TRUE);
		//$input_data['info_id'] = $this->input->post('info_id',TRUE);
		//$input_data['reminder_date'] = $this->input->post('reminder_date',TRUE);
		//$input_data['referral_status'] = $this->input->post('referral_status',TRUE); // 0= new , 1= cancel , 2= view , 3= job in progress , 4= job complete	

		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$notification_count = count($this->mdl_user->get_notification_list($input_data));
			$notification_list = $this->mdl_user->get_notification_list($input_data,$input_data['offset']);
			if(!empty($notification_list))
			{
				$next_offset = $input_data['offset'] + PER_PAGE_LIST;
        		if($notification_count > $next_offset)
        		{
        			$response[RESPONSE_NEXT_OFFSET] = $next_offset;
        		}
        		else
        		{
        			$response[RESPONSE_NEXT_OFFSET] = -1;
        		}
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = "Notifications";
				$response[RESPONSE_DATA] = $notification_list;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __NOTIFICATION_LIST_EMPTY;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
    }
     function privacy_details_post() 
    {
        $response = array();

        $input_data['lang'] = ($this->input->get('lang', TRUE)) ? $this->input->get('lang', TRUE) : '0';

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $this->db->select('privacy_id, privacy_policy_text');
      //  $this->db->where('setting_id',1);
        $privacy = $this->db->get('tbl_privacy')->row_array();

        if (!empty($privacy)) {
          //  print_r(explode(' ', $PRIVACY['PRIVACY_us_text']));die;
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
            $response[RESPONSE_MESSAGE] = _PRIVACY_DETAILS;
            $response[RESPONSE_DATA] = $privacy;
        } else {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
            $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
        }

        $this->response($response, 200);
    }
    function terms_service_post() 
    {
        $response = array();

        $input_data['lang'] = ($this->input->get('lang', TRUE)) ? $this->input->get('lang', TRUE) : '0';

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $this->db->select('terms_id, terms_text');
      //  $this->db->where('setting_id',1);
        $terms = $this->db->get('tbl_terms_service')->row_array();

        if (!empty($terms)) {
          //  print_r(explode(' ', $terms['terms_us_text']));die;
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
            $response[RESPONSE_MESSAGE] = _TERMS_DETAILS;
            $response[RESPONSE_DATA] = $terms;
        } else {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
            $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
        }

        $this->response($response, 200);
    }
    function read_notification_post()
    {
    	$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);

    	//$input_data['manage_id'] = $this->input->post('manage_id',TRUE);
		//$input_data['info_id'] = $this->input->post('info_id',TRUE);
		$input_data['notification_id'] = $this->input->post('notification_id',TRUE);
		
		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','notification_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);
		
		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']); 

        if($res_validate_token['flag'] > 0)
        {
			$is_read = $this->mdl_common->update_record('tbl_notification',array('notification_id'=>$input_data['notification_id'],'user_id'=>$input_data['user_id']),array('is_count'=>1,'date_modified'=>date('Y-m-d H:i:s')));
			if($is_read)
			{
				$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $input_data['user_id']))->num_rows();
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response["count"] = $notification_count;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
			}
		}
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

		$this->response($response,200);
    }

	function manage_tema_member_post()
    {
    	$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
    	$input_data['mobile_array'] = $this->input->post('mobile_array',TRUE);
		$input_data['subscription_level'] = $this->input->post('subscription_level',TRUE);
		$input_data['monthly_referral_fee'] = $this->input->post('monthly_referral_fee',TRUE);
		
		$input_data['lang'] = $this->input->post('lang', TRUE); // 0 = Eng, 1 = OTHER
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','mobile_array','subscription_level','monthly_referral_fee');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);
		
		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']); 

        if($res_validate_token['flag'] > 0)
        {
			$mobile_array = $input_data['mobile_array'];
			if(!empty($mobile_array)) 
			{
				foreach(json_decode($mobile_array) as $key => $value) {
					//$country_code = $value->country_code;
					$mobile_no = $value->mobile_no;
					$user = $this->mdl_common->get_record('tbl_user', Array('mobile_no' => $mobile_no))->row_array();
					//if(empty($user)) {
						$team_member = $this->mdl_common->get_record('tbl_manage_team_member', Array('user_id' => $input_data['user_id'], 'mobile_no' => $mobile_no))->row_array();
						if(empty($team_member)) {
							$ins_data['user_id'] = $input_data['user_id'];
							//$ins_data['country_code'] = $country_code;
							$ins_data['mobile_no'] = $mobile_no;
							$ins_data['subscription_level'] = $input_data['subscription_level'];
							$ins_data['monthly_referral_fee'] = $input_data['monthly_referral_fee'];
							$ins_data['created_date'] = date('Y-m-d H:i:s');
							$ins_data['updated_date'] = date('Y-m-d H:i:s');
							$manage_team_member = $this->mdl_common->insert_record('tbl_manage_team_member',$ins_data);

							// if(empty($user)) {
							// 	$get_push_msg = $this->db->get_where('tbl_all_notifications',Array('notification_type'=> 'SMS','send_to'=>'CUSTOMER','when_send'=>'NEW_REFERRAL_CREATED','type'=>'SMS_IF_CUSTOMER_IS_NOT_A_REGISTERED_USER'))->row_array();
							// 	$push_msg = str_ireplace('<referrer_name>', $referrer_name, $get_push_msg['message']);
							// 	$r_to = $input_data['re_country_code'].''.$input_data['referral_mobile_no'];
							// 	$sms=$this->mdl_common->send_sinch_sms(str_replace(" ","",$r_to),$push_msg);
							// }
						}
						else {
							$up_data['mobile_no'] = $mobile_no;
							$up_data['subscription_level'] = $input_data['subscription_level'];
							$up_data['monthly_referral_fee'] = $input_data['monthly_referral_fee'];
							$up_data['updated_date'] = date('Y-m-d H:i:s');
							$manage_team_member = $this->mdl_common->update_record('tbl_manage_team_member',Array('user_id' => $input_data['user_id'], 'mobile_no' => $mobile_no),$up_data);
						}
						$this->mdl_common->delete_record('tbl_manage_team_member',array('user_id' => $input_data['user_id'], 'mobile_no !=' => $mobile_no));
					//}
				}

				if($manage_team_member)
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
					$response[RESPONSE_MESSAGE] = _TEAM_MEMBER_SUCCESSFULLY;
				}
				else
				{
					$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
				}
			}
			else {
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = "Not pssed valid mobile array.";
			}
		}
        else
        {
        	$response[RESPONSE_FLAG] = $res_validate_token['flag'];
        	$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
        }

		$this->response($response,200);
    }

	function tema_member_list_post() 
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			$member_list = $this->mdl_common->get_record('tbl_manage_team_member', Array('user_id' => $input_data['user_id'], 'is_del' => 0), Array('team_member_id', 'user_id', 'mobile_no'))->result_array();
			if(!empty($member_list))
			{
				foreach($member_list as $row) {
					$mobile_no = $row['mobile_no'];
					$user = $this->mdl_common->get_record('tbl_user', Array('mobile_no' => $mobile_no))->row_array();
					$resultArray[] = Array(
						'team_member_id' => $row['team_member_id'],
						'user_id' => $row['user_id'],
						'mobile_no' => $row['mobile_no'],
						'first_name' => ($user['first_name']) ? $user['first_name'] : '',
						'last_name' => ($user['last_name']) ? $user['last_name'] : ''
					);
				}
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = _TEAM_MEMBER_LIST;
				$response[RESPONSE_DATA] = $resultArray;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __TEAM_MEMBER_LIST_EMPTY;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}

	function delete_team_member_post() 
	{
		$response = array();
		$input_data['user_id'] = $this->input->post('user_id',TRUE);
		$input_data['access_token'] = $this->input->post('access_token',TRUE);
		$input_data['team_member_id'] = $this->input->post('team_member_id',TRUE);
		$input_data['lang'] = ($this->input->post('lang', TRUE)) ? $this->input->post('lang', TRUE) : '0';
		$this->mdl_common->load_constants_for_lang($input_data['lang']);

		$input_parameter = array('user_id','access_token','team_member_id');

		$validation = $this->ParamValidation($input_parameter,$input_data);
		if($validation[RESPONSE_FLAG] == RESPONSE_FLAG_FAIL)
		$this->response($validation,200);

		$res_validate_token = $this->mdl_user->validate_user_access_token($input_data['access_token'], $input_data['user_id']);
		if($res_validate_token['flag'] > 0)
		{
			//$del_member = $this->mdl_common->update_record('tbl_manage_team_member',array('user_id' => $input_data['user_id'], 'team_member_id' => $input_data['team_member_id']),array('is_del' => 1));
			$del_member = $this->mdl_common->delete_record('tbl_manage_team_member',array('user_id' => $input_data['user_id'], 'team_member_id' => $input_data['team_member_id']));
			if(!empty($del_member))
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
				$response[RESPONSE_MESSAGE] = _DELETE_TEAM_MEMBER_SUCCESSFULLY;
			}
			else
			{
				$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
				$response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
			}
		}
		else
		{
			$response[RESPONSE_FLAG] = $res_validate_token['flag'];
			$response[RESPONSE_MESSAGE] = $res_validate_token['msg'];
		}
		$this->response($response,200);
	}

	function relationship_list_post() 
    {
        $response = array();

        $input_data['lang'] = ($this->input->get('lang', TRUE)) ? $this->input->get('lang', TRUE) : '0';

        $this->mdl_common->load_constants_for_lang($input_data['lang']);

        $this->db->select('relationship_id, relationship');
        $relationship = $this->db->get('tbl_relationship')->result_array();

        if (!empty($relationship)) {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
            $response[RESPONSE_MESSAGE] = _RELSTIONSHIP_DATA;
            $response[RESPONSE_DATA] = $relationship;
        } else {
            $response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
            $response[RESPONSE_MESSAGE] = __SOMETHING_WENT_WRONG;
        }

        $this->response($response, 200);
    }

	/*
	if($monthly_referral_fee == '') {
									try
									{  
										if(empty($paymentData)) {
											// for manage db
											$paid_amount = $input_data['paid_amount'] - $application_fee;
											$amt = $price = $paid_amount;
											$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
											$change_amount = $amt - $fees;
											//$pay = ($change_amount * $commission) / 100;
											//$pay_amt = $change_amount - $pay;
											$pay_amt = $change_amount;
											
											//for manange stripe
											$amount = ($paid_amount * 100);
											$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
											$change_amt = $amount - round($stripe_fees);
											//$payble = ($change_amt * $commission) / 100;
											//$payble_amount = round($change_amt) - round($payble);
											$payble_amount = round($change_amt);
											
											$charge = \Stripe\Charge::create(array(
												"amount" => $amount,
												"currency" => $input_data['currency_code'],
												"customer" => $sender_data['customer_token'],
												"statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
												"transfer_data[destination]" => $receiver_data['customer_token'],
												"transfer_data[amount]" => $payble_amount
											));
											$charge_id = $charge->id;
										}
										else {
											if($current_month == $payment_date) {
												// for manage db
												$amt = $price = $input_data['paid_amount'];
												$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
												$change_amount = $amt - $fees;
												//$pay = ($change_amount * $commission) / 100;
												//$pay_amt = $change_amount - $pay;
												$pay_amt = $change_amount;
												
												//for manange stripe
												$amount = ($input_data['paid_amount'] * 100);
												$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
												$change_amt = $amount - round($stripe_fees);
												// $payble = ($change_amt * $commission) / 100;
												// $payble_amount = round($change_amt) - round($payble);
												$payble_amount = round($change_amt);
												
												$charge = \Stripe\Charge::create(array(
													"amount" => $amount,
													"currency" => $input_data['currency_code'],
													"customer" => $sender_data['customer_token'],
													"statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
													"transfer_data[destination]" => $receiver_data['customer_token'],
													"transfer_data[amount]" => $payble_amount
												));
												$charge_id = $charge->id;
											}
											else {
												// for manage db
												$paid_amount = $input_data['paid_amount'] - $application_fee;
												$amt = $price = $paid_amount;
												$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
												$change_amount = $amt - $fees;
												// $pay = ($change_amount * $commission) / 100;
												// $pay_amt = $change_amount - $pay;
												$pay_amt = $change_amount;
												
												//for manange stripe
												$amount = ($paid_amount * 100);
												$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
												$change_amt = $amount - round($stripe_fees);
												// $payble = ($change_amt * $commission) / 100;
												// $payble_amount = round($change_amt) - round($payble);
												$payble_amount = round($change_amt);
												
												$charge = \Stripe\Charge::create(array(
													"amount" => $amount,
													"currency" => $input_data['currency_code'],
													"customer" => $sender_data['customer_token'],
													"statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
													"transfer_data[destination]" => $receiver_data['customer_token'],
													"transfer_data[amount]" => $payble_amount
												));
												$charge_id = $charge->id;
											}
										}
									} catch (Exception $e) {
										$error = $e->getMessage();
										$charge_id = '';
									}
									
									if($charge_id != '')
									{
										$ins_data['user_id'] = $input_data['user_id'];
										$ins_data['manage_id'] = $input_data['manage_id'];
										$ins_data['info_id'] = $input_data['info_id'];
										$ins_data['is_paid'] = 0;
										$ins_data['fees'] = $fees;
										$ins_data['commission']	= $pay;										
										$ins_data['charge_id'] = $charge_id;
										$ins_data['amount'] = $input_data['paid_amount'];
										$ins_data['payable_amount'] = number_format($pay_amt, 2, '.', '');
										$ins_data['currency'] = $input_data['currency_code'];
										$ins_data['payment_date'] = date('Y-m-d');
										$ins_data['date_added'] = date('Y-m-d H:i:s');
										$ins_data['application_fee'] = ($current_month == $payment_date) ? 0 : $application_fee;
										$this->mdl_common->insert_record('tbl_payment',$ins_data);

										$ins_rdata['user_id'] = $get_card_data['business_id'];
										$ins_rdata['manage_id'] = $input_data['manage_id'];
										$ins_rdata['info_id'] = $input_data['info_id'];
										$ins_rdata['is_paid'] = 1;
										$ins_rdata['fees'] = $fees;
										$ins_rdata['commission']= $pay;	
										$ins_rdata['charge_id'] = $charge_id;
										$ins_rdata['amount'] = $input_data['paid_amount'];
										$ins_rdata['payable_amount'] = number_format($pay_amt, 2, '.', '');
										$ins_rdata['currency'] = $input_data['currency_code'];
										$ins_rdata['payment_date'] = date('Y-m-d');
										$ins_rdata['date_added'] = date('Y-m-d H:i:s');
										$ins_data['application_fee'] = ($current_month == $payment_date) ? 0 : $application_fee;
										$this->mdl_common->insert_record('tbl_payment',$ins_rdata);

										$up_status_data['business_status'] = 6;
										$up_status_data['referral_status'] = 6;
										$this->mdl_common->update_record('tbl_manage_referrer',Array('business_id' => $input_data['user_id'],'manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id']),$up_status_data);

										$response[RESPONSE_FLAG]= RESPONSE_FLAG_SUCCESS;
										$response[RESPONSE_MESSAGE] = __PAYMENT_SUCCESSFULLY;  

										// Send push after response
										header ( 'Content-Type: application/json' );
										echo json_encode ( $response );
										$size = ob_get_length ();
										header ( "Content-Length: $size" );
										header ( 'Connection: close' );
										header ( "Content-Encoding: none\r\n" );
										ob_end_flush ();
										ob_flush ();
										flush ();

										if(session_id ())
										session_write_close ();

										$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
										$business_name = $business_data['first_name'].' '.$business_data['last_name'];
										$ref_data = $this->db->get_where('tbl_user',Array('user_id'=> $exist_referrer_data['referrer_id'],'is_del' => 0))->row_array();
										$ref_name = $ref_data['first_name'].' '.$ref_data['last_name'];
										//$push_msg =  $business_name." has viewed your Referral.";
										//$push_msg="Please update your bank account details to receive payment from ".$business_name;
										$push_msg="Congratulations ".$ref_name.",you have received a referral fee from ".$business_name." on behalf of Referral ".$exist_referrer_data['assign_referral_id'];

										$this->db->select("P.user_id,register_id,P.device_type");
										//$this->db->where('P.device_type',1);
										$this->db->where('P.user_id', $exist_referrer_data['referrer_id']);
										$this->db->where('U.is_notification',0);
										$this->db->from("tbl_push AS P");
										$this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
										$this->db->order_by('P.push_id',"DESC");
										$register_token = $this->db->get()->result_array();
										if (!empty($register_token)) {
											$push_data_len = count($register_token);
											$android_device_arr = array();
											$ios_device_arr = array();

											$n_data = array(
												'user_id' => $exist_referrer_data['referrer_id'],
												'message' => $push_msg,
												'notification_post_user_id'=> $input_data['user_id'],
												'is_flag'=> 2,
												'manage_id'=> $input_data['manage_id'],
												'info_id'=>$input_data['info_id'],
												'notification'=> 'payment_complete_from_business',
												'notification_type'=>11,
												'date_added' => date('Y-m-d H:i:s')
											);
											$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
											foreach ($register_token as $value) {
												if($value['device_type'] == 0)
												{
													array_push($android_device_arr, $value['register_id']);
												}
												else
												{
													array_push($ios_device_arr, $value['register_id']);
												}
											}

											$message_array['custom_data']['message'] =  $push_msg;
											$message_array['custom_data']['notification_type'] = 11;
											$message_array['custom_data']['notification'] = 'payment_complete_from_business';
											$message_array['custom_data']['manage_id'] = $input_data['manage_id'];
											$message_array['custom_data']['info_id'] = $input_data['info_id'];
											$message_array['custom_data']['is_flag'] = 2;

											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $exist_referrer_data['referrer_id']))->num_rows();
											$message_array['custom_data']['count'] = $notification_count;
											$message_array['custom_data']['notification_id'] = $notification_id;
											
											if(!empty($android_device_arr)){
												$push_data['device_type'] = 0;
												$push_data['register_id'] = $android_device_arr;
												$push_data['multiple'] = 1;
												
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												$this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
												
											}
											if(!empty($ios_device_arr)){
												$push_data['device_type'] = 1;
												$push_data['register_id'] = $ios_device_arr;
												$push_data['multiple'] = 1;
												
												$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
												$this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
											}
										}
									}
									else 
									{
										$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
										$response[RESPONSE_MESSAGE] = $error;
										$this->response($response, 200);
									}
								}
								else {
									if($monthly_referral_fee >= $total_amount) {
										try
										{  
											if(empty($paymentData)) {
												// for manage db
												$paid_amount = $input_data['paid_amount'] - $application_fee;
												$amt = $price = $paid_amount;
												$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
												$change_amount = $amt - $fees;
												//$pay = ($change_amount * $commission) / 100;
												//$pay_amt = $change_amount - $pay;
												$pay_amt = $change_amount;
												
												//for manange stripe
												$amount = ($paid_amount * 100);
												$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
												$change_amt = $amount - round($stripe_fees);
												//$payble = ($change_amt * $commission) / 100;
												//$payble_amount = round($change_amt) - round($payble);
												$payble_amount = round($change_amt);
												
												$charge = \Stripe\Charge::create(array(
													"amount" => $amount,
													"currency" => $input_data['currency_code'],
													"customer" => $sender_data['customer_token'],
													"statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
													"transfer_data[destination]" => $receiver_data['customer_token'],
													"transfer_data[amount]" => $payble_amount
												));
												$charge_id = $charge->id;
											}
											else {
												if($current_month == $payment_date) {
													// for manage db
													$amt = $price = $input_data['paid_amount'];
													$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
													$change_amount = $amt - $fees;
													//$pay = ($change_amount * $commission) / 100;
													//$pay_amt = $change_amount - $pay;
													$pay_amt = $change_amount;
													
													//for manange stripe
													$amount = ($input_data['paid_amount'] * 100);
													$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
													$change_amt = $amount - round($stripe_fees);
													// $payble = ($change_amt * $commission) / 100;
													// $payble_amount = round($change_amt) - round($payble);
													$payble_amount = round($change_amt);
													
													$charge = \Stripe\Charge::create(array(
														"amount" => $amount,
														"currency" => $input_data['currency_code'],
														"customer" => $sender_data['customer_token'],
														"statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
														"transfer_data[destination]" => $receiver_data['customer_token'],
														"transfer_data[amount]" => $payble_amount
													));
													$charge_id = $charge->id;
												}
												else {
													// for manage db
													$paid_amount = $input_data['paid_amount'] - $application_fee;
													$amt = $price = $paid_amount;
													$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
													$change_amount = $amt - $fees;
													// $pay = ($change_amount * $commission) / 100;
													// $pay_amt = $change_amount - $pay;
													$pay_amt = $change_amount;
													
													//for manange stripe
													$amount = ($paid_amount * 100);
													$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
													$change_amt = $amount - round($stripe_fees);
													// $payble = ($change_amt * $commission) / 100;
													// $payble_amount = round($change_amt) - round($payble);
													$payble_amount = round($change_amt);
													
													$charge = \Stripe\Charge::create(array(
														"amount" => $amount,
														"currency" => $input_data['currency_code'],
														"customer" => $sender_data['customer_token'],
														"statement_descriptor_suffix" => "ID".$exist_referrer_data['assign_referral_id'],
														"transfer_data[destination]" => $receiver_data['customer_token'],
														"transfer_data[amount]" => $payble_amount
													));
													$charge_id = $charge->id;
												}
											}
										} catch (Exception $e) {
											$error = $e->getMessage();
											$charge_id = '';
										}
										
										if($charge_id != '')
										{
											$ins_data['user_id'] = $input_data['user_id'];
											$ins_data['manage_id'] = $input_data['manage_id'];
											$ins_data['info_id'] = $input_data['info_id'];
											$ins_data['is_paid'] = 0;
											$ins_data['fees'] = $fees;
											$ins_data['commission']	= $pay;										
											$ins_data['charge_id'] = $charge_id;
											$ins_data['amount'] = $input_data['paid_amount'];
											$ins_data['payable_amount'] = number_format($pay_amt, 2, '.', '');
											$ins_data['currency'] = $input_data['currency_code'];
											$ins_data['payment_date'] = date('Y-m-d');
											$ins_data['date_added'] = date('Y-m-d H:i:s');
											$ins_data['application_fee'] = ($current_month == $payment_date) ? 0 : $application_fee;
											$this->mdl_common->insert_record('tbl_payment',$ins_data);
	
											$ins_rdata['user_id'] = $get_card_data['business_id'];
											$ins_rdata['manage_id'] = $input_data['manage_id'];
											$ins_rdata['info_id'] = $input_data['info_id'];
											$ins_rdata['is_paid'] = 1;
											$ins_rdata['fees'] = $fees;
											$ins_rdata['commission']= $pay;	
											$ins_rdata['charge_id'] = $charge_id;
											$ins_rdata['amount'] = $input_data['paid_amount'];
											$ins_rdata['payable_amount'] = number_format($pay_amt, 2, '.', '');
											$ins_rdata['currency'] = $input_data['currency_code'];
											$ins_rdata['payment_date'] = date('Y-m-d');
											$ins_rdata['date_added'] = date('Y-m-d H:i:s');
											$ins_data['application_fee'] = ($current_month == $payment_date) ? 0 : $application_fee;
											$this->mdl_common->insert_record('tbl_payment',$ins_rdata);
	
											$up_status_data['business_status'] = 6;
											$up_status_data['referral_status'] = 6;
											$this->mdl_common->update_record('tbl_manage_referrer',Array('business_id' => $input_data['user_id'],'manage_id'=>$input_data['manage_id'],'info_id'=>$input_data['info_id']),$up_status_data);
	
											$response[RESPONSE_FLAG]= RESPONSE_FLAG_SUCCESS;
											$response[RESPONSE_MESSAGE] = __PAYMENT_SUCCESSFULLY;  
	
											// Send push after response
											header ( 'Content-Type: application/json' );
											echo json_encode ( $response );
											$size = ob_get_length ();
											header ( "Content-Length: $size" );
											header ( 'Connection: close' );
											header ( "Content-Encoding: none\r\n" );
											ob_end_flush ();
											ob_flush ();
											flush ();
	
											if(session_id ())
											session_write_close ();
	
											$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $input_data['user_id'],'is_del' => 0))->row_array();
											$business_name = $business_data['first_name'].' '.$business_data['last_name'];
											$ref_data = $this->db->get_where('tbl_user',Array('user_id'=> $exist_referrer_data['referrer_id'],'is_del' => 0))->row_array();
											$ref_name = $ref_data['first_name'].' '.$ref_data['last_name'];
											//$push_msg =  $business_name." has viewed your Referral.";
											//$push_msg="Please update your bank account details to receive payment from ".$business_name;
											$push_msg="Congratulations ".$ref_name.",you have received a referral fee from ".$business_name." on behalf of Referral ".$exist_referrer_data['assign_referral_id'];
	
											$this->db->select("P.user_id,register_id,P.device_type");
											//$this->db->where('P.device_type',1);
											$this->db->where('P.user_id', $exist_referrer_data['referrer_id']);
											$this->db->where('U.is_notification',0);
											$this->db->from("tbl_push AS P");
											$this->db->join('tbl_user AS U','P.user_id=U.user_id','left');
											$this->db->order_by('P.push_id',"DESC");
											$register_token = $this->db->get()->result_array();
											if (!empty($register_token)) {
												$push_data_len = count($register_token);
												$android_device_arr = array();
												$ios_device_arr = array();
	
												$n_data = array(
													'user_id' => $exist_referrer_data['referrer_id'],
													'message' => $push_msg,
													'notification_post_user_id'=> $input_data['user_id'],
													'is_flag'=> 2,
													'manage_id'=> $input_data['manage_id'],
													'info_id'=>$input_data['info_id'],
													'notification'=> 'payment_complete_from_business',
													'notification_type'=>11,
													'date_added' => date('Y-m-d H:i:s')
												);
												$notification_id=$this->mdl_common->insert_record('tbl_notification',$n_data);
												foreach ($register_token as $value) {
													if($value['device_type'] == 0)
													{
														array_push($android_device_arr, $value['register_id']);
													}
													else
													{
														array_push($ios_device_arr, $value['register_id']);
													}
												}
	
												$message_array['custom_data']['message'] =  $push_msg;
												$message_array['custom_data']['notification_type'] = 11;
												$message_array['custom_data']['notification'] = 'payment_complete_from_business';
												$message_array['custom_data']['manage_id'] = $input_data['manage_id'];
												$message_array['custom_data']['info_id'] = $input_data['info_id'];
												$message_array['custom_data']['is_flag'] = 2;
	
												$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $exist_referrer_data['referrer_id']))->num_rows();
												$message_array['custom_data']['count'] = $notification_count;
												$message_array['custom_data']['notification_id'] = $notification_id;
												
												if(!empty($android_device_arr)){
													$push_data['device_type'] = 0;
													$push_data['register_id'] = $android_device_arr;
													$push_data['multiple'] = 1;
													
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													$this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '0'));
													
												}
												if(!empty($ios_device_arr)){
													$push_data['device_type'] = 1;
													$push_data['register_id'] = $ios_device_arr;
													$push_data['multiple'] = 1;
													
													$data1 = $this->mdl_common->send_fcm_push($push_data, $message_array);
													$this->db->insert('tbl_test',Array('json_data' => json_encode($data1),'device_type' => '1'));
												}
											}
										}
										else 
										{
											$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
											$response[RESPONSE_MESSAGE] = $error;
											$this->response($response, 200);
										}
									}
									else {
										$response[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
										$response[RESPONSE_MESSAGE] = __MONTHLY_STRIPE_FEE_COMPLETED;
										$this->response($response, 200);
									}
								}
	*/
}
?>
