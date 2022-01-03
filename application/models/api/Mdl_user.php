<?php
class Mdl_user Extends CI_Model
{
	/*Save push details*/
	function save_push_details($user_id, $data)
	{
		$this->db->where("device_token = '" . $data['device_token'] . "' AND user_id !='" . $user_id . "'");
		$this->db->delete("tbl_push");

		$this->db->select("*");
		$this->db->from("tbl_push");
		$this->db->where("device_token = '" . $data['device_token'] . "' AND user_id ='" . $user_id . "' ");
		$push_entry = $this->db->get()->num_rows();

		if($push_entry == 0)
		{
			$data['user_id'] = $user_id;
			$data['created_date'] = date('Y-m-d H:i:s');
			$this->db->insert("tbl_push", $data);
			return $this->db->insert_id();
		} else {
			$this->db->where('user_id',$user_id);
			$this->db->where('device_token',$data['device_token']);
			$this->db->update('tbl_push',Array('register_id' => $data['register_id'], 'modified_date' => date('Y-m-d H:i:s')));
			return $user_id;
		}
	}
	/*Save User access token*/
	function save_user_access_token($access_token_data)
	{
		$this->db->where('device_token', $access_token_data['device_token']);
		$this->db->where('user_id', $access_token_data['user_id']);

		$res = $this->db->get('tbl_access_token')->result_array();

		if(!empty($res) && $access_token_data['device_token'] != ''){
			$update_data['access_token'] = $access_token_data['access_token'];
			$update_data['modified_date'] = date('Y-m-d H:i:s');

			$this->db->where('device_token', $access_token_data['device_token']);
			$this->db->where('user_id', $access_token_data['user_id']);
			$this->db->update('tbl_access_token', $update_data);
		}
		else
		{
			$insert_data['user_id'] = $access_token_data['user_id'];
			$insert_data['device_token'] = $access_token_data['device_token'];
			$insert_data['access_token'] = $access_token_data['access_token'];
			$insert_data['created_date'] = date('Y-m-d H:i:s');

			$this->mdl_common->insert_record('tbl_access_token', $insert_data);
		}
		return $access_token_data['access_token'];
	}
	/*=================================================================================
	Check User already exist or not
	==================================================================================*/
	public function check_user_already_exist($data, $user_id='')
	{
		// print_r($user_id);die();
		$return_arr = array();
		foreach($data as $column){
			if(!empty($column)){
				$this->db->select('user_id');
				$this->db->where($column['db_key'], $column['value']);
				$this->db->where('is_del', 0);
				//$this->db->where('user_type!= -1');
				if($user_id != '')
				$this->db->where('user_id != ', $user_id);
				$res = $this->db->get('tbl_user')->row_array();
				//echo $this->db->last_query();
				if(!empty($res)){
					$return_arr[RESPONSE_FLAG] = RESPONSE_FLAG_FAIL;
					$return_arr[RESPONSE_MESSAGE] = $column['message_if_exist'];
					$return_arr[RESPONSE_DATA] = $res;
					return $return_arr;
				}
			}
		}
		$return_arr[RESPONSE_FLAG] = RESPONSE_FLAG_SUCCESS;
		return $return_arr;
	}
	function get_user_details($user_id)
	{
		$this->db->select("U.user_id,U.first_name,U.last_name,U.country_code,U.mobile_no,U.is_verify,U.is_profile_complete,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as profile_image,U.email_id,U.business_name,U.address,U.city,U.state,U.postal_code,U.abn,U.is_business_details,U.is_notification,U.customer_token,U.role,U.business_country_code,U.business_phone_number,U.subscription_level,U.max_number_tier_level,IFNULL(R.country,'')AS c_sort_name,IFNULL(R.currency,'')AS currency,C.currency,C.country_id,C.country_name,C.min_fee,C.max_fee");
		$this->db->from('tbl_user AS U');
		$this->db->join('tbl_referrer_account AS R','U.user_id=R.referrer_id','left');
		$this->db->join('tbl_countries AS C','U.country_code=C.country_code','left');
		$this->db->where('U.user_id', $user_id);
		$this->db->where('U.is_del',0);
		$user_details = $this->db->get()->row_array();
		if(!empty($user_details))
		{
			$user_details['count'] = $notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $user_details['user_id']))->num_rows();
		}
		return $user_details;
	}
	/*validate user access token*/
	function validate_user_access_token($access_token, $user_id)
	{
		//Check Valid access token
		$this->db->select('*');
		$this->db->from('tbl_access_token');
		$this->db->where('user_id', $user_id);
		$this->db->where('access_token', $access_token);
		$res = $this->db->get()->result_array();

		if(!empty($res)){
			//Check user blocked
			$this->db->select('*');
			$this->db->from('tbl_user');
			$this->db->where('user_id', $user_id);
			$this->db->where('is_del', 0);
			$res = $this->db->get()->row_array();
			if($res['is_flag'] == 1)
			{
				$response['flag'] = RESPONSE_FLAG_SUCCESS;
				$response['user_data'] = $res;
			}
			else
			{
				$response['flag'] = RESPONSE_FLAG_ACCESS_DENIED;
				$response['msg'] = __USER_BLOCKED;
			}
		}
		else
		{
			$response['flag'] = RESPONSE_FLAG_ACCESS_DENIED;
			$response['msg'] = __USER_ACCESS_DENIED;
		}
		return $response;
	}
	public function get_referral_list($data,$offset='')
	{
		if($data['is_flag'] == 1) // RECEIVE
		{
			$userData = $this->mdl_common->get_record('tbl_user',Array('user_id' => $data['user_id'], 'role' => 'team member', 'is_del' => 0))->row_array();
			if(!empty($userData)) {
				$this->db->select('*');
				$this->db->where('is_del',0);
				$this->db->where('find_in_set("'.$data['user_id'].'", team_member_id) <> 0');
				$this->db->order_by('manage_id','DESC');
				if($offset!= '')
				{
					$this->db->limit(PER_PAGE_LIST, $offset);
				}
				$refferal = $this->db->get('tbl_manage_referrer')->result_array();
			}
			else {
				$this->db->select('*');
				$this->db->where('is_del',0);
				$this->db->where('business_id',$data['user_id']);
				$this->db->order_by('manage_id','DESC');
				if($offset!= '')
				{
					$this->db->limit(PER_PAGE_LIST, $offset);
				}
				$refferal = $this->db->get('tbl_manage_referrer')->result_array();
			}
			//echo $this->db->last_query();die;
			$new_refferal_array = array();
			if(!empty($refferal))
			{	
				foreach ($refferal as $value) 
				{
					// $block_data = $this->mdl_common->get_record("tbl_block_referrer", Array('user_id' => $data['user_id'],'referrer_id' => $value['referrer_id']))->row_array();
					// if(empty($block_data)) {
						# code...
						if($value['referral_id']!= '0' || $value['referral_id2']!= '0' || $value['referral_id3']!= '0')
						{
							// $get_refferal = $this->db->get_where('tbl_user',array('user_id'=>$value['referral_id'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
							// $get_refferal2 = $this->db->get_where('tbl_user',array('user_id'=>$value['referral_id2'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
							// $get_refferal3 = $this->db->get_where('tbl_user',array('user_id'=>$value['referral_id3'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
							
							$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status, IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,U.business_country_code,U.business_phone_number,
							IF(UR.first_name!= '' && UR.last_name!= '',CONCAT(UR.first_name,' ',UR.last_name),'')AS referral_name,IF(UR.profile_image!='', CONCAT('".base_url()."', UR.profile_image), '') as referral_image,
							IF(UR2.first_name!= '' && UR2.last_name!= '',CONCAT(UR2.first_name,' ',UR2.last_name),'')AS referral_name2,IF(UR2.profile_image!='', CONCAT('".base_url()."', UR2.profile_image), '') as referral_image2,
							IF(UR3.first_name!= '' && UR3.last_name!= '',CONCAT(UR3.first_name,' ',UR3.last_name),'')AS referral_name3,IF(UR3.profile_image!='', CONCAT('".base_url()."', UR3.profile_image), '') as referral_image3,
							MR.date_added");
							$this->db->from('tbl_manage_referrer AS MR');
							$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
							$this->db->join('tbl_user AS UR','MR.referral_id=UR.user_id','left');
							$this->db->join('tbl_user AS UR2','MR.referral_id2=UR2.user_id','left');
							$this->db->join('tbl_user AS UR3','MR.referral_id3=UR3.user_id','left');
							$this->db->where('MR.business_id',$value['business_id']);
							$this->db->where('MR.manage_id',$value['manage_id']);
							$result = $this->db->get()->row_array();

							$referral_info = $this->mdl_common->get_record("tbl_new_referrer_info", array('info_id' => $value['info_id']))->row_array();
							$referral_name = $referral_info['referral_name'];
							$referral_name2 = $referral_info['referral_name2'];
							$referral_name3 = $referral_info['referral_name3'];

							$refferal_data = Array(
								'manage_id' => $result['manage_id'],
								'info_id' => $result['info_id'],
								'business_status' => $result['business_status'],
								'referral_status' => $result['referral_status'],
								'referrer_name' => $result['referrer_name'],
								'referrer_business_country_code' => ($result['business_country_code']) ? $result['business_country_code'] : '',
								'referrer_business_phone_number' => ($result['business_phone_number']) ? $result['business_phone_number'] : '',
								'referral_name' => ($result['referral_name']) ? $result['referral_name'] : $referral_name,
								'referral_image' => $result['referral_image'],
								'referral_name2' => ($result['referral_name2']) ? $result['referral_name2'] : $referral_name2,
								'referral_image2' => $result['referral_image2'],
								'referral_name3' => ($result['referral_name3']) ? $result['referral_name2'] : $referral_name3,
								'referral_image3' => $result['referral_image3'],
								'date_added' => $result['date_added'],
							);
							if(!empty($refferal_data))
							{
								array_push($new_refferal_array,$refferal_data);
							}
						}
						else
						{
							$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,U.business_country_code AS referrer_business_country_code,U.business_phone_number AS referrer_business_phone_number,
							IF(NR.referral_name!= '',CONCAT(NR.referral_name),'')AS referral_name,'' as referral_image,
							IF(NR.referral_name2!= '',CONCAT(NR.referral_name2),'')AS referral_name2,'' as referral_image2,
							IF(NR.referral_name3!= '',CONCAT(NR.referral_name3),'')AS referral_name3,'' as referral_image3,
							MR.date_added");
							$this->db->from('tbl_manage_referrer AS MR');
							$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
							$this->db->join('tbl_new_referrer_info AS NR','MR.info_id=NR.info_id','left');
							$this->db->where('MR.business_id',$value['business_id']);
							$this->db->where('MR.manage_id',$value['manage_id']);
							$refferal_data= $this->db->get()->row_array();
							if(!empty($refferal_data))
							{
								array_push($new_refferal_array,$refferal_data);
							}
						}
					//}
				}
			}
			return $new_refferal_array;


		}
		else if($data['is_flag'] == 2) // Sent
		{
			$this->db->select('*');
			$this->db->where('is_del',0);
			$this->db->where('referrer_id',$data['user_id']);
			$this->db->order_by('manage_id','DESC');
			if($offset!= '')
			{
				$this->db->limit(PER_PAGE_LIST, $offset);
			}
			$refferal = $this->db->get('tbl_manage_referrer')->result_array();
			//echo $this->db->last_query();die;
			$new_refferal_array = array();
			if(!empty($refferal))
			{	
				foreach ($refferal as $value) 
				{
					if($value['referral_id']!= '0' || $value['referral_id2']!= '0' || $value['referral_id3']!= '0' || $value['business_id']!= '0')
					{
						// $get_refferal = $this->db->get_where('tbl_user',array('user_id'=>$value['referral_id'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
						// $get_refferal2 = $this->db->get_where('tbl_user',array('user_id'=>$value['referral_id2'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
						// $get_refferal3 = $this->db->get_where('tbl_user',array('user_id'=>$value['referral_id3'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
						// $get_business = $this->db->get_where('tbl_user',array('user_id'=>$value['business_id'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
						
						$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status,
						IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referral_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referral_image,
						IF(U2.first_name!= '' && U2.last_name!= '',CONCAT(U2.first_name,' ',U2.last_name),'')AS referral_name2,IF(U2.profile_image!='', CONCAT('".base_url()."', U2.profile_image), '') as referral_image2,
						IF(U3.first_name!= '' && U3.last_name!= '',CONCAT(U3.first_name,' ',U3.last_name),'')AS referral_name3,IF(U3.profile_image!='', CONCAT('".base_url()."', U3.profile_image), '') as referral_image3,
						IF(B.first_name!= '' && B.last_name!= '',CONCAT(B.first_name,' ',B.last_name),'')AS business_name,B.business_country_code,B.business_phone_number,MR.date_added");
						$this->db->from('tbl_manage_referrer AS MR');
						$this->db->join('tbl_user AS U','MR.referral_id=U.user_id','left');
						$this->db->join('tbl_user AS U2','MR.referral_id2=U2.user_id','left');
						$this->db->join('tbl_user AS U3','MR.referral_id3=U3.user_id','left');
						$this->db->join('tbl_user AS B','MR.business_id=B.user_id','left');
						$this->db->where('MR.referrer_id',$value['referrer_id']);
						$this->db->where('MR.manage_id',$value['manage_id']);
						$result = $this->db->get()->row_array();

						$referral_info = $this->mdl_common->get_record("tbl_new_referrer_info", array('info_id' => $value['info_id']))->row_array();
						$business_name = $referral_info['business_name'];
						$referral_name = $referral_info['referral_name'];
						$referral_name2 = $referral_info['referral_name2'];
						$referral_name3 = $referral_info['referral_name3'];
						
						$refferal_data = Array(
							'manage_id' => $result['manage_id'],
							'info_id' => $result['info_id'],
							'business_status' => $result['business_status'],
							'referral_status' => $result['referral_status'],
							'referral_name' => ($result['referral_name']) ? $result['referral_name'] : $referral_name,
							'referral_image' => $result['referral_image'],
							'referral_name2' => ($result['referral_name2']) ? $result['referral_name2'] : $referral_name2,
							'referral_image2' => $result['referral_image2'],
							'referral_name3' => ($result['referral_name3']) ? $result['referral_name3'] : $referral_name3,
							'referral_image3' => $result['referral_image3'],
							'business_name' => ($result['business_name']) ? $result['business_name'] : $business_name,
							'business_country_code' => ($result['business_country_code']) ? $result['business_country_code'] : $business_name,
							'business_phone_number' => ($result['business_phone_number']) ? $result['business_phone_number'] : $business_name,
							'date_added' => $result['date_added'],
						);

						if(!empty($refferal_data))
						{
							array_push($new_refferal_array,$refferal_data);
						}
					}
					else
					{
						$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status,
						IF(U.referral_name!= '',CONCAT(U.referral_name),'')AS referral_name,'' as referral_image,
						IF(U.referral_name2!= '',CONCAT(U.referral_name2),'')AS referral_name2,'' as referral_image2,
						IF(U.referral_name3!= '',CONCAT(U.referral_name3),'')AS referral_name3,'' as referral_image3,
						IF(B.business_name!= '',CONCAT(B.business_name),'')AS business_name,'' AS business_country_code,'' AS business_phone_number,MR.date_added");
						$this->db->from('tbl_manage_referrer AS MR');
						$this->db->join('tbl_new_referrer_info AS U','MR.info_id=U.info_id','left');
						$this->db->join('tbl_new_referrer_info AS B','MR.info_id=B.info_id','left');
						$this->db->where('MR.referrer_id',$value['referrer_id']);
						$this->db->where('MR.manage_id',$value['manage_id']);
						$refferal_data= $this->db->get()->row_array();
						if(!empty($refferal_data))
						{
							array_push($new_refferal_array,$refferal_data);
						}
					}
				}
			}
			return $new_refferal_array;
		}
		if($data['is_flag'] == 3) // MY
		{
			$this->db->select('*');
			$this->db->where('is_del',0);
			$this->db->where('referral_id',$data['user_id']);
			$this->db->order_by('manage_id','DESC');
			if($offset!= '')
			{
				$this->db->limit(PER_PAGE_LIST, $offset);
			}
			$refferal = $this->db->get('tbl_manage_referrer')->result_array();
			//echo $this->db->last_query();die;
			$new_refferal_array = array();
			if(!empty($refferal))
			{	
				foreach ($refferal as $value) 
				{
					# code...
					if($value['business_id']!= '0')
					{
						$get_business = $this->db->get_where('tbl_user',array('user_id'=>$value['business_id'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
						if(!empty($get_business))
						{
							$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(B.first_name!= '' && B.last_name!= '',CONCAT(B.first_name,' ',B.last_name),'')AS business_name,IF(B.profile_image!='', CONCAT('".base_url()."', B.profile_image), '') as business_image,MR.date_added");
							$this->db->from('tbl_manage_referrer AS MR');
							$this->db->join('tbl_user AS B','MR.business_id=B.user_id','left');
							$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
							$this->db->where('MR.referral_id',$value['referral_id']);
							$this->db->where('MR.manage_id',$value['manage_id']);
							$refferal_data= $this->db->get()->row_array();
							//echo $this->db->last_query();
							if(!empty($refferal_data))
							{
								array_push($new_refferal_array,$refferal_data);
							}
						}
						else
						{
							$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(B.business_name!= '',CONCAT(B.business_name),'')AS business_name,'' as business_image,MR.date_added");
							$this->db->from('tbl_manage_referrer AS MR');
							$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
							$this->db->join('tbl_new_referrer_info AS B','MR.info_id=B.info_id','left');
							$this->db->where('MR.referral_id',$value['referral_id']);
							$this->db->where('MR.manage_id',$value['manage_id']);
							$refferal_data= $this->db->get()->row_array();
						//	echo $this->db->last_query();
							if(!empty($refferal_data))
							{
								array_push($new_refferal_array,$refferal_data);
							}
						}
						
					}
					else
					{
						$this->db->select("MR.manage_id,MR.info_id,MR.business_status,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(B.business_name!= '',CONCAT(B.business_name),'')AS business_name,'' as business_image,MR.date_added");
						$this->db->from('tbl_manage_referrer AS MR');
						$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
						$this->db->join('tbl_new_referrer_info AS B','MR.info_id=B.info_id','left');
						$this->db->where('MR.referral_id',$value['referral_id']);
						$this->db->where('MR.manage_id',$value['manage_id']);
						$refferal_data= $this->db->get()->row_array();
						//	echo $this->db->last_query();
						if(!empty($refferal_data))
						{
							array_push($new_refferal_array,$refferal_data);
						}
					}
				}
			}
			return $new_refferal_array;
		}
	}
	public function get_business_user_view($data)
	{	
		$userData = $this->mdl_common->get_record('tbl_user',Array('user_id' => $data['user_id'], 'role' => 'team member', 'is_del' => 0))->row_array();
		if(!empty($userData)) {
			$this->db->select('*');
			$this->db->where('is_del',0);
			$this->db->where('manage_id',$data['manage_id']);
			$this->db->where('info_id',$data['info_id']);
			$this->db->where('find_in_set("'.$data['user_id'].'", team_member_id) <> 0');
			$refferal = $this->db->get('tbl_manage_referrer')->row_array();
		}
		else {
			$this->db->select('*');
			$this->db->where('is_del',0);
			$this->db->where('manage_id',$data['manage_id']);
			$this->db->where('info_id',$data['info_id']);
			$this->db->where('business_id',$data['user_id']);
			$refferal = $this->db->get('tbl_manage_referrer')->row_array();
		}
		
		$refferal_data = array();
		if(!empty($refferal))
		{	
			// $block_data = $this->mdl_common->get_record("tbl_block_referrer", Array('user_id' => $data['user_id'],'referrer_id' => $refferal['referrer_id']))->row_array();
			// if(empty($block_data)) {
				# code...
				if($refferal['referral_id']!= '0' || $refferal['referral_id2']!= '0' || $refferal['referral_id2']!= '0')
				{
					$get_refferal = $this->db->get_where('tbl_user',array('user_id'=>$refferal['referral_id'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
					$get_refferal2 = $this->db->get_where('tbl_user',array('user_id'=>$refferal['referral_id2'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
					$get_refferal3 = $this->db->get_where('tbl_user',array('user_id'=>$refferal['referral_id3'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
					$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.business_status,MR.date_added,C.min_fee,C.max_fee,
					IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referrer_image,U.address AS referrer_address,U.city AS referrer_city,U.state AS referrer_state,U.country_code AS referrer_c_code,U.mobile_no AS referrer_mobile_no,IF(U.email_id!='',CONCAT(U.email_id),'')AS referrer_email_id,IF(U.business_name!= '',CONCAT(U.business_name),'')AS ref_bus_name,U.business_country_code,U.business_phone_number,U.role,

					IF(UR.first_name!= '' && UR.last_name!= '',CONCAT(UR.first_name,' ',UR.last_name),'')AS referral_name,IF(UR.profile_image!='', CONCAT('".base_url()."', UR.profile_image), '') as referral_image,UR.address AS referral_address,UR.city AS referral_city,UR.state AS referral_state,UR.country_code AS referral_c_code,UR.mobile_no AS referral_mobile_no,UR.postal_code AS referral_postal_code,IF(UR.email_id!='',CONCAT(UR.email_id),'')AS referral_email_id,IF(UR.business_name!= '',CONCAT(UR.business_name),'')AS cust_bus_name,

					IF(UR2.first_name!= '' && UR2.last_name!= '',CONCAT(UR2.first_name,' ',UR2.last_name),'')AS referral_name2,IF(UR2.profile_image!='', CONCAT('".base_url()."', UR2.profile_image), '') as referral_image2,UR2.address AS referral_address2,UR2.city AS referral_city2,UR2.state AS referral_state2,UR2.country_code AS referral_c_code2,UR2.mobile_no AS referral_mobile_no2,UR2.postal_code AS referral_postal_code2,IF(UR2.email_id!='',CONCAT(UR2.email_id),'')AS referral_email_id2,IF(UR2.business_name!= '',CONCAT(UR2.business_name),'')AS cust_bus_name2,

					IF(UR3.first_name!= '' && UR3.last_name!= '',CONCAT(UR3.first_name,' ',UR3.last_name),'')AS referral_name3,IF(UR3.profile_image!='', CONCAT('".base_url()."', UR3.profile_image), '') as referral_image3,UR3.address AS referral_address3,UR3.city AS referral_city3,UR3.state AS referral_state3,UR3.country_code AS referral_c_code3,UR3.mobile_no AS referral_mobile_no3,UR3.postal_code AS referral_postal_code3,IF(UR3.email_id!='',CONCAT(UR3.email_id),'')AS referral_email_id3,IF(UR3.business_name!= '',CONCAT(UR3.business_name),'')AS cust_bus_name3
					");
					$this->db->from('tbl_manage_referrer AS MR');
					$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
					$this->db->join('tbl_user AS UR','MR.referral_id=UR.user_id','left');
					$this->db->join('tbl_user AS UR2','MR.referral_id2=UR2.user_id','left');
					$this->db->join('tbl_user AS UR3','MR.referral_id3=UR3.user_id','left');
					$this->db->join('tbl_countries AS C','U.country_code=C.country_code','left');
					$this->db->where('MR.business_id',$refferal['business_id']);
					$this->db->where('MR.manage_id',$refferal['manage_id']);
					$result = $this->db->get()->row_array();

					$referral_info = $this->mdl_common->get_record("tbl_new_referrer_info", array('info_id' => $data['info_id']))->row_array();
					$referral_name = $referral_info['referral_name'];
					$referral_country_code = $referral_info['referral_country_code'];
					$referral_mobile_no = $referral_info['referral_mobile_no'];
					$referral_name2 = $referral_info['referral_name2'];
					$referral_country_code2 = $referral_info['referral_country_code2'];
					$referral_mobile_no2 = $referral_info['referral_mobile_no2'];
					$referral_name3 = $referral_info['referral_name3'];
					$referral_country_code3 = $referral_info['referral_country_code3'];
					$referral_mobile_no3 = $referral_info['referral_mobile_no3'];
					$relationship = $this->mdl_common->get_record('tbl_relationship', Array('relationship_id' => $referral_info['relationship']))->row()->relationship;
					$relationship2 = $this->mdl_common->get_record('tbl_relationship', Array('relationship_id' => $referral_info['relationship2']))->row()->relationship;
					$relationship3 = $this->mdl_common->get_record('tbl_relationship', Array('relationship_id' => $referral_info['relationship3']))->row()->relationship;


					$refferal_data = Array(
						'manage_id' => $result['manage_id'],
						'info_id' => $result['info_id'],
						'assign_referral_id' => $result['assign_referral_id'],
						'message' => $result['message'],
						'business_status' => $result['business_status'],
						'date_added' => $result['date_added'],
						'role' => $result['role'],
						'referrer_name' => $result['referrer_name'],
						'referrer_image' => $result['referrer_image'],
						'referrer_address' => $result['referrer_address'],
						'referrer_city' => $result['referrer_city'],
						'referrer_state' => $result['referrer_state'],
						'referrer_c_code' => $result['referrer_c_code'],
						'referrer_mobile_no' => $result['referrer_mobile_no'],
						'referrer_business_country_code' => ($result['business_country_code']) ? $result['business_country_code'] : '',
						'referrer_business_phone_number' => ($result['business_phone_number']) ? $result['business_phone_number'] : '',
						'referrer_email_id' => $result['referrer_email_id'],
						'ref_bus_name' => $result['ref_bus_name'],
						'referral_name' => ($result['referral_name'] != '') ? $result['referral_name'] : $referral_name,
						'referral_image' => ($result['referral_image']) ? $result['referral_image'] : '',
						'referral_address' => ($result['referral_address'] != null) ? $result['referral_address'] : '',
						'referral_city' => ($result['referral_city'] != null) ? $result['referral_city'] : '',
						'referral_state' => ($result['referral_state'] != null) ? $result['referral_state'] : '',
						'referral_c_code' => ($result['referral_c_code'] != null) ? $result['referral_c_code'] : $referral_country_code,
						'referral_mobile_no' => ($result['referral_mobile_no'] != null) ? $result['referral_mobile_no'] : $referral_mobile_no,
						'relationship' => $relationship,
						'referral_postal_code' => ($result['referral_postal_code'] != null) ? $result['referral_postal_code'] : '',
						'referral_email_id' => $result['referral_email_id'],
						'cust_bus_name' => $result['cust_bus_name'],
						'referral_name2' => ($result['referral_name2']) ? $result['referral_name2'] : $referral_name2,
						'referral_image2' => ($result['referral_image2']) ? $result['referral_image2'] : '',
						'referral_address2' => ($result['referral_address2']) ? $result['referral_address2'] : '',
						'referral_city2' => ($result['referral_city2']) ? $result['referral_city2'] : '',
						'referral_state2' => ($result['referral_state2']) ? $result['referral_state2'] : '',
						'referral_c_code2' => ($result['referral_c_code2']) ? $result['referral_c_code2'] : $referral_country_code2,
						'referral_mobile_no2' => ($result['referral_mobile_no2']) ? $result['referral_mobile_no2'] : $referral_mobile_no2,
						'relationship2' => $relationship2,
						'referral_postal_code2' => ($result['referral_postal_code2']) ? $result['referral_postal_code2'] : '',
						'referral_email_id2' => $result['referral_email_id2'],
						'cust_bus_name2' => $result['cust_bus_name2'],
						'referral_name3' => ($result['referral_name3']) ? $result['referral_name3'] : $referral_name3,
						'referral_image3' => ($result['referral_image3']) ?  $result['referral_image3'] : '',
						'referral_address3' => ($result['referral_address3']) ? $result['referral_address3'] : '',
						'referral_city3' => ($result['referral_city3']) ? $result['referral_city3'] : '',
						'referral_state3' => ($result['referral_state3']) ? $result['referral_state3'] : '',
						'referral_c_code3' => ($result['referral_c_code3']) ? $result['referral_c_code3'] : $referral_country_code3,
						'referral_mobile_no3' => ($result['referral_mobile_no3']) ? $result['referral_mobile_no3'] : $referral_mobile_no3,
						'relationship3' => $relationship3,
						'referral_postal_code3' => ($result['referral_postal_code3']) ? $result['referral_postal_code3'] : '',
						'referral_email_id3' => $result['referral_email_id3'],
						'cust_bus_name3' => $result['cust_bus_name3'],
						'min_fee' => $result['min_fee'],
						'max_fee' => $result['max_fee'],
					);
				}
				else
				{
					$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.business_status,
					IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referrer_image,U.address AS referrer_address,U.city AS referrer_city,U.state AS referrer_state,U.country_code AS referrer_c_code,U.mobile_no AS referrer_mobile_no,IF(U.email_id!='',CONCAT(U.email_id),'')AS referrer_email_id,IF(U.business_name!= '',CONCAT(U.business_name),'')AS ref_bus_name,U.business_country_code as referrer_business_country_code,U.business_phone_number as referrer_business_phone_number,U.role,
					IF(NR.referral_name!= '',CONCAT(NR.referral_name),'')AS referral_name,'' as referral_image,'' AS referral_address,'' referral_city,'' AS referral_state,IF(NR.referral_country_code!= '',CONCAT(NR.referral_country_code),'')AS referral_c_code,IF(NR.referral_mobile_no!= '',CONCAT(NR.referral_mobile_no),'')AS referral_mobile_no,'' AS referral_email_id,'' AS referral_postal_code,'' AS cust_bus_name,R1.relationship AS relationship,
					IF(NR.referral_name2!= '',CONCAT(NR.referral_name2),'')AS referral_name2,'' as referral_image2,'' AS referral_address2,'' referral_city2,'' AS referral_state2,IF(NR.referral_country_code2!= '',CONCAT(NR.referral_country_code2),'')AS referral_c_code2,IF(NR.referral_mobile_no2!= '',CONCAT(NR.referral_mobile_no2),'')AS referral_mobile_no2,'' AS referral_email_id2,'' AS referral_postal_code2,'' AS cust_bus_name2,R2.relationship AS relationship2,
					IF(NR.referral_name3!= '',CONCAT(NR.referral_name3),'')AS referral_name3,'' as referral_image3,'' AS referral_address3,'' referral_city3,'' AS referral_state3,IF(NR.referral_country_code3!= '',CONCAT(NR.referral_country_code3),'')AS referral_c_code3,IF(NR.referral_mobile_no3!= '',CONCAT(NR.referral_mobile_no3),'')AS referral_mobile_no3,'' AS referral_email_id3,'' AS referral_postal_code3,'' AS cust_bus_name3,R3.relationship AS relationship3,
					MR.date_added,C.min_fee,C.max_fee");
					$this->db->from('tbl_manage_referrer AS MR');
					$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
					$this->db->join('tbl_new_referrer_info AS NR','MR.info_id=NR.info_id','left');
					$this->db->join('tbl_relationship AS R1','R1.relationship_id=NR.relationship','left');
					$this->db->join('tbl_relationship AS R2','R2.relationship_id=NR.relationship2','left');
					$this->db->join('tbl_relationship AS R3','R3.relationship_id=NR.relationship3','left');
					$this->db->join('tbl_countries AS C','U.country_code=C.country_code','left');
					$this->db->where('MR.business_id',$refferal['business_id']);
					$this->db->where('MR.manage_id',$refferal['manage_id']);
					$refferal_data= $this->db->get()->row_array();
				}
			//}
		}
		return $refferal_data;
	}
	public function get_referral_user_view($data)
	{
		$this->db->select('*');
		$this->db->where('is_del',0);
		$this->db->where('manage_id',$data['manage_id']);
		$this->db->where('info_id',$data['info_id']);
		$this->db->where('referral_id',$data['user_id']);
		$refferal = $this->db->get('tbl_manage_referrer')->row_array();
		//echo $this->db->last_query();die;
		$refferal_data = array();
		if(!empty($refferal))
		{

			if($refferal['business_id']!= '0')
			{
				$get_business = $this->db->get_where('tbl_user',array('user_id'=>$refferal['business_id'],'is_profile_complete'=>1,'is_del'=>0))->row_array();
				if(!empty($get_business))
				{
					$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referrer_image,U.address AS referrer_address,U.city AS referrer_city,U.state AS referrer_state,U.country_code AS referrer_c_code,U.mobile_no AS referrer_mobile_no,IF(U.email_id!='',CONCAT(U.email_id),'')AS referrer_email_id,IF(U.business_name!= '',CONCAT(U.business_name),'')AS ref_bus_name,IF(B.first_name!= '' && B.last_name!= '',CONCAT(B.first_name,' ',B.last_name),'')AS business_name,IF(B.profile_image!='', CONCAT('".base_url()."', B.profile_image), '') as business_image,B.address AS business_address,B.city AS business_city,B.state AS business_state,B.country_code AS business_c_code,B.mobile_no AS business_mobile_no,IF(B.email_id!='',CONCAT(B.email_id),'')AS business_email_id,B.postal_code AS business_postal_code,IF(B.business_name!= '',CONCAT(B.business_name),'')AS bus_name,MR.date_added");
					$this->db->from('tbl_manage_referrer AS MR');
					$this->db->join('tbl_user AS B','MR.business_id=B.user_id','left');
					$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
					$this->db->where('MR.referral_id',$refferal['referral_id']);
					$this->db->where('MR.manage_id',$refferal['manage_id']);
					$refferal_data= $this->db->get()->row_array();
				}
				else
				{
					$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referrer_image,U.address AS referrer_address,U.city AS referrer_city,U.state AS referrer_state,U.country_code AS referrer_c_code,U.mobile_no AS referrer_mobile_no,IF(U.email_id!='',CONCAT(U.email_id),'')AS referrer_email_id,IF(U.business_name!= '',CONCAT(U.business_name),'')AS ref_bus_name,IF(B.business_name!= '',CONCAT(B.business_name),'')AS business_name,'' as business_image,'' AS business_address,'' AS business_city,'' AS business_state,IF(B.business_country_code!= '',CONCAT(B.business_country_code),'')AS business_c_code,IF(B.business_mobile_no!= '',CONCAT(B.business_mobile_no),'')AS business_mobile_no,'' AS business_email_id,''AS business_postal_code,'' AS bus_name,MR.date_added");
					$this->db->from('tbl_manage_referrer AS MR');
					$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
					$this->db->join('tbl_new_referrer_info AS B','MR.info_id=B.info_id','left');
					$this->db->where('MR.referral_id',$refferal['referral_id']);
					$this->db->where('MR.manage_id',$refferal['manage_id']);
					$refferal_data= $this->db->get()->row_array();
				}
				
				//echo $this->db->last_query();
				
			}
			else
			{
				$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.referral_status,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referrer_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referrer_image,U.address AS referrer_address,U.city AS referrer_city,U.state AS referrer_state,U.country_code AS referrer_c_code,U.mobile_no AS referrer_mobile_no,IF(U.email_id!='',CONCAT(U.email_id),'')AS referrer_email_id,IF(U.business_name!= '',CONCAT(U.business_name),'')AS ref_bus_name,IF(B.business_name!= '',CONCAT(B.business_name),'')AS business_name,'' as business_image,'' AS business_address,'' AS business_city,'' AS business_state,IF(B.business_country_code!= '',CONCAT(B.business_country_code),'')AS business_c_code,IF(B.business_mobile_no!= '',CONCAT(B.business_mobile_no),'')AS business_mobile_no,'' AS business_email_id,''AS business_postal_code,'' AS bus_name,MR.date_added");
				$this->db->from('tbl_manage_referrer AS MR');
				$this->db->join('tbl_user AS U','MR.referrer_id=U.user_id','left');
				$this->db->join('tbl_new_referrer_info AS B','MR.info_id=B.info_id','left');
				$this->db->where('MR.referral_id',$refferal['referral_id']);
				$this->db->where('MR.manage_id',$refferal['manage_id']);
				$refferal_data= $this->db->get()->row_array();
			//	echo $this->db->last_query();
				
			}
		}
		return $refferal_data;
	}
	public function get_referrer_user_view($data)
	{
		$this->db->select('*');
		$this->db->where('is_del',0);
		$this->db->where('manage_id',$data['manage_id']);
		$this->db->where('info_id',$data['info_id']);
		$this->db->where('referrer_id',$data['user_id']);
		$refferal = $this->db->get('tbl_manage_referrer')->row_array();
		$refferal_data = array();
		if(!empty($refferal))
		{
			if($refferal['referral_id']!= '0' || $refferal['referral_id2']!= '0' || $refferal['referral_id3']!= '0' || $refferal['business_id']!= '0')
			{
				// $get_refferal = $this->db->get_where('tbl_user',array('user_id'=>$refferal['referral_id'],'is_del'=>0))->row_array();
				// $get_refferal2 = $this->db->get_where('tbl_user',array('user_id'=>$refferal['referral_id2'],'is_del'=>0))->row_array();
				// $get_refferal3 = $this->db->get_where('tbl_user',array('user_id'=>$refferal['referral_id3'],'is_del'=>0))->row_array();
				// $get_business = $this->db->get_where('tbl_user',array('user_id'=>$refferal['business_id'],'is_del'=>0))->row_array();
				
				$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.business_status,
				IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS referral_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as referral_image,U.address AS referral_address,U.city AS referral_city,U.state AS referral_state,U.country_code AS referral_c_code,U.mobile_no AS referral_mobile_no,IF(U.email_id!='',CONCAT(U.email_id),'')AS referral_email_id,IF(U.business_name!= '',CONCAT(U.business_name),'')AS cust_bus_name,
				IF(U2.first_name!= '' && U2.last_name!= '',CONCAT(U2.first_name,' ',U2.last_name),'')AS referral_name2,IF(U2.profile_image!='', CONCAT('".base_url()."', U2.profile_image), '') as referral_image2,U2.address AS referral_address2,U2.city AS referral_city2,U2.state AS referral_state2,U2.country_code AS referral_c_code2,U2.mobile_no AS referral_mobile_no2,IF(U2.email_id!='',CONCAT(U2.email_id),'')AS referral_email_id2,IF(U2.business_name!= '',CONCAT(U2.business_name),'')AS cust_bus_name2,
				IF(U3.first_name!= '' && U3.last_name!= '',CONCAT(U3.first_name,' ',U3.last_name),'')AS referral_name3,IF(U3.profile_image!='', CONCAT('".base_url()."', U3.profile_image), '') as referral_image3,U3.address AS referral_address3,U3.city AS referral_city3,U3.state AS referral_state3,U3.country_code AS referral_c_code3,U3.mobile_no AS referral_mobile_no3,IF(U3.email_id!='',CONCAT(U3.email_id),'')AS referral_email_id3,IF(U3.business_name!= '',CONCAT(U3.business_name),'')AS cust_bus_name3,
				IF(B.first_name!= '' && B.last_name!= '',CONCAT(B.first_name,' ',B.last_name),'')AS business_name,IF(B.profile_image!='', CONCAT('".base_url()."', B.profile_image), '') as business_image,B.address AS business_address,B.city AS business_city,B.state AS business_state,B.country_code AS business_c_code,B.mobile_no AS business_mobile_no,IF(B.email_id!='',CONCAT(B.email_id),'')AS business_email_id,B.postal_code AS business_postal_code,IF(B.business_name!= '',CONCAT(B.business_name),'')AS bus_name,B.business_country_code,B.business_phone_number,MR.date_added,C.min_fee,C.max_fee,B.role");
				$this->db->from('tbl_manage_referrer AS MR');
				$this->db->join('tbl_user AS U','MR.referral_id=U.user_id','left');
				$this->db->join('tbl_user AS U2','MR.referral_id2=U2.user_id','left');
				$this->db->join('tbl_user AS U3','MR.referral_id3=U3.user_id','left');
				$this->db->join('tbl_user AS B','MR.business_id=B.user_id','left');
				$this->db->join('tbl_countries AS C','B.country_code=C.country_code','left');
				$this->db->where('MR.referrer_id',$refferal['referrer_id']);
				$this->db->where('MR.manage_id',$refferal['manage_id']);
				$result = $this->db->get()->row_array();

				$referral_info = $this->mdl_common->get_record("tbl_new_referrer_info", array('info_id' => $data['info_id']))->row_array();
				$referral_name = $referral_info['referral_name'];
				$referral_country_code = $referral_info['referral_country_code'];
				$referral_mobile_no = $referral_info['referral_mobile_no'];
				$referral_name2 = $referral_info['referral_name2'];
				$referral_country_code2 = $referral_info['referral_country_code2'];
				$referral_mobile_no2 = $referral_info['referral_mobile_no2'];
				$referral_name3 = $referral_info['referral_name3'];
				$referral_country_code3 = $referral_info['referral_country_code3'];
				$referral_mobile_no3 = $referral_info['referral_mobile_no3'];
				$business_name = $referral_info['business_name'];
				$business_country_code = $referral_info['business_country_code'];
				$business_mobile_no = $referral_info['business_mobile_no'];
				$relationship = $this->mdl_common->get_record('tbl_relationship', Array('relationship_id' => $referral_info['relationship']))->row()->relationship;
				$relationship2 = $this->mdl_common->get_record('tbl_relationship', Array('relationship_id' => $referral_info['relationship2']))->row()->relationship;
				$relationship3 = $this->mdl_common->get_record('tbl_relationship', Array('relationship_id' => $referral_info['relationship3']))->row()->relationship;

				$refferal_data = Array(
					'manage_id' => $result['manage_id'],
					'info_id' => $result['info_id'],
					'assign_referral_id' => $result['assign_referral_id'],
					'message' => $result['message'],
					'business_status' => $result['business_status'],
					'referral_name' => $result['referral_name'],
					'referral_name' => ($result['referral_name'] != '') ? $result['referral_name'] : $referral_name,
					'referral_image' => ($result['referral_image']) ? $result['referral_image'] : '',
					'referral_address' => ($result['referral_address'] != null) ? $result['referral_address'] : '',
					'referral_city' => ($result['referral_city'] != null) ? $result['referral_city'] : '',
					'referral_state' => ($result['referral_state'] != null) ? $result['referral_state'] : '',
					'referral_c_code' => ($result['referral_c_code'] != null) ? $result['referral_c_code'] : $referral_country_code,
					'referral_mobile_no' => ($result['referral_mobile_no'] != null) ? $result['referral_mobile_no'] : $referral_mobile_no,
					'relationship' => $relationship,
					'referral_postal_code' => ($result['referral_postal_code'] != null) ? $result['referral_postal_code'] : '',
					'referral_email_id' => $result['referral_email_id'],
					'cust_bus_name' => $result['cust_bus_name'],
					'referral_name2' => ($result['referral_name2']) ? $result['referral_name2'] : $referral_name2,
					'referral_image2' => ($result['referral_image2']) ? $result['referral_image2'] : '',
					'referral_address2' => ($result['referral_address2']) ? $result['referral_address2'] : '',
					'referral_city2' => ($result['referral_city2']) ? $result['referral_city2'] : '',
					'referral_state2' => ($result['referral_state2']) ? $result['referral_state2'] : '',
					'referral_c_code2' => ($result['referral_c_code2']) ? $result['referral_c_code2'] : $referral_country_code2,
					'referral_mobile_no2' => ($result['referral_mobile_no2']) ? $result['referral_mobile_no2'] : $referral_mobile_no2,
					'relationship2' => $relationship2,
					'referral_postal_code2' => ($result['referral_postal_code2']) ? $result['referral_postal_code2'] : '',
					'referral_email_id2' => $result['referral_email_id2'],
					'cust_bus_name2' => $result['cust_bus_name2'],
					'referral_name3' => ($result['referral_name3']) ? $result['referral_name3'] : $referral_name3,
					'referral_image3' => ($result['referral_image3']) ?  $result['referral_image3'] : '',
					'referral_address3' => ($result['referral_address3']) ? $result['referral_address3'] : '',
					'referral_city3' => ($result['referral_city3']) ? $result['referral_city3'] : '',
					'referral_state3' => ($result['referral_state3']) ? $result['referral_state3'] : '',
					'referral_c_code3' => ($result['referral_c_code3']) ? $result['referral_c_code3'] : $referral_country_code3,
					'referral_mobile_no3' => ($result['referral_mobile_no3']) ? $result['referral_mobile_no3'] : $referral_mobile_no3,
					'relationship3' => $relationship3,
					'referral_postal_code3' => ($result['referral_postal_code3']) ? $result['referral_postal_code3'] : '',
					'referral_email_id3' => $result['referral_email_id3'],
					'cust_bus_name3' => $result['cust_bus_name3'],
					'business_name' => ($result['business_name']) ? $result['business_name'] : $business_name,
					'business_image' => ($result['business_image']) ? $result['business_image'] : '',
					'business_address' => ($result['business_address']) ? $result['business_address'] : '',
					'business_city' => ($result['business_city']) ? $result['business_city'] : '',
					'business_state' => ($result['business_state']) ? $result['business_state'] : '',
					'business_c_code' => ($result['business_c_code']) ? $result['business_c_code'] : $business_country_code,
					'business_mobile_no' => ($result['business_mobile_no']) ? $result['business_mobile_no'] : $business_mobile_no,
					'business_email_id' => $result['business_email_id'],
					'business_postal_code' => ($result['business_postal_code']) ? $result['business_postal_code'] : '',
					'bus_name' => ($result['bus_name']) ? $result['bus_name'] : $business_name,
					'date_added' => $result['date_added'],
					'business_phone_number' => $result['business_phone_number'],
					'business_country_code' => $result['business_country_code'],
					'min_fee' => $result['min_fee'],
					'max_fee' => $result['max_fee'],
					'role' => $result['role'],
				);
			}
			else
			{
				$this->db->select("MR.manage_id,MR.info_id,MR.assign_referral_id,MR.message,MR.business_status,
				IF(U.referral_name!= '',CONCAT(U.referral_name),'')AS referral_name,'' as referral_image,'' AS referral_address,'' AS referral_city,'' AS referral_state,IF(U.referral_country_code!= '',CONCAT(U.referral_country_code),'')AS referral_c_code,IF(U.referral_mobile_no!= '',CONCAT(U.referral_mobile_no),'')AS referral_mobile_no,'' AS referral_email_id,R1.relationship AS relationship,
				IF(U.referral_name2!= '',CONCAT(U.referral_name2),'')AS referral_name2,'' as referral_image2,'' AS referral_address2,'' AS referral_city2,'' AS referral_state2,IF(U.referral_country_code2!= '',CONCAT(U.referral_country_code2),'')AS referral_c_code2,IF(U.referral_mobile_no2!= '',CONCAT(U.referral_mobile_no2),'')AS referral_mobile_no2,'' AS referral_email_id2,R2.relationship AS relationship2,
				IF(U.referral_name3!= '',CONCAT(U.referral_name3),'')AS referral_name3,'' as referral_image3,'' AS referral_address3,'' AS referral_city3,'' AS referral_state3,IF(U.referral_country_code3!= '',CONCAT(U.referral_country_code3),'')AS referral_c_code3,IF(U.referral_mobile_no3!= '',CONCAT(U.referral_mobile_no3),'')AS referral_mobile_no3,'' AS referral_email_id3,R3.relationship AS relationship3,
				IF(B.business_name!= '',CONCAT(B.business_name),'')AS business_name,'' as business_image,'' AS business_address,'' AS business_city,'' AS business_state,IF(B.business_country_code!= '',CONCAT(B.business_country_code),'')AS business_c_code,IF(B.business_mobile_no!= '',CONCAT(B.business_mobile_no),'')AS business_mobile_no,'' AS business_email_id,'' AS business_postal_code,'' AS business_email_id,'' AS bus_name,'' AS cust_bus_name,'' AS business_phone_number,B.business_country_code,MR.date_added,'' AS min_fee,'' AS max_fee, '' AS role");
				$this->db->from('tbl_manage_referrer AS MR');
				$this->db->join('tbl_new_referrer_info AS U','MR.info_id=U.info_id','left');
				$this->db->join('tbl_relationship AS R1','R1.relationship_id=U.relationship','left');
				$this->db->join('tbl_relationship AS R2','R2.relationship_id=U.relationship2','left');
				$this->db->join('tbl_relationship AS R3','R3.relationship_id=U.relationship3','left');
				$this->db->join('tbl_new_referrer_info AS B','MR.info_id=B.info_id','left');
				$this->db->where('MR.referrer_id',$refferal['referrer_id']);
				$this->db->where('MR.manage_id',$refferal['manage_id']);
				$refferal_data= $this->db->get()->row_array();
			}
		}
		return $refferal_data;
	}
	public function get_payment_history($data,$offset="")
	{
		$this->db->select("P.payment_id,P.amount,P.fees,P.commission,P.payable_amount,P.payment_date,P.is_paid,P.application_fee,IF(UR.first_name!= '' && UR.last_name!= '',CONCAT(UR.first_name,' ',UR.last_name),'')AS referral_name,IF(UR.profile_image!='', CONCAT('".base_url()."', UR.profile_image), '') as referral_image,IF(UB.first_name!= '' && UB.last_name!= '',CONCAT(UB.first_name,' ',UB.last_name),'')AS business_name,IF(URR.first_name!= '' && URR.last_name!= '',CONCAT(URR.first_name,' ',URR.last_name),'')AS referrer_name,M.business_status");
		$this->db->from('tbl_payment AS P');
		$this->db->join('tbl_manage_referrer AS M','M.manage_id=P.manage_id','left');
		$this->db->join('tbl_user AS UR','M.referral_id=UR.user_id','left');
		$this->db->join('tbl_user AS UB','M.business_id=UB.user_id','left');
		$this->db->join('tbl_user AS URR','M.referrer_id=URR.user_id','left');
		$this->db->where('P.user_id',$data['user_id']);
		$this->db->where('P.is_del',0);
		$this->db->order_by("P.payment_id","DESC");
		if($offset!= '')
		{
			$this->db->limit(PER_PAGE_LIST, $offset);
		}
		 $payments = $this->db->get()->result_array();
		//echo $this->db->last_query();die;
		return $payments;
	}
	public function get_notification_list($data,$offset='')
	{
		$this->db->select("N.notification_id,N.manage_id,N.info_id,N.is_flag,N.notification,N.message,IF(U.first_name!= '' && U.last_name!= '',CONCAT(U.first_name,' ',U.last_name),'')AS sender_name,IF(U.profile_image!='', CONCAT('".base_url()."', U.profile_image), '') as sender_image,N.notification_type,N.user_id,N.is_count,IFNULL(M.business_status,'')AS business_status");
		$this->db->from('tbl_notification AS N');
		$this->db->join('tbl_user AS U','N.notification_post_user_id=U.user_id','left');
		$this->db->join('tbl_manage_referrer AS M','M.manage_id=N.manage_id','left');
		$this->db->where('N.user_id',$data['user_id']);
		
		if($offset!= '')
		{
			$this->db->limit(PER_PAGE_LIST, $offset);
		}
		$this->db->order_by("N.notification_id","DESC");
		 $notifications = $this->db->get()->result_array();
		// return $notifications;
		// echo $this->db->last_query();die;

		 if(!empty($notifications))
		 {
		 	$new_notification_data = array();
		 	foreach ($notifications as $n_data) {
		 		# code...
		 		$new_data = $n_data;
		 		if($new_data['notification_type'] == '10')
		 		{
		 			$new_data['sender_name'] = 'Referral';
		 		}
		 		if($n_data['is_flag'] == 1)
		 		{
		 			$check_status = $this->check_business_status($n_data['user_id']);
		 			
		 			if(!empty($check_status))
		 			{
		 				$new_data['is_allow_visible'] = 1;
		 			}
		 			else
		 			{
		 				$new_data['is_allow_visible'] = 0;
		 			}
		 		}
		 		else
		 		{
		 			$new_data['is_allow_visible'] = 0;
		 		}
		 		array_push($new_notification_data,$new_data);

		 	}
		 }
		 return $new_notification_data;
		//echo $this->db->last_query();die;
		//return $notifications;

	}
	public function check_business_status($business_id)
	{
		//echo $business_id;die;
		$this->db->select('business_status');
		$this->db->where('business_status',4);
		$this->db->where('business_id',$business_id);
		$this->db->where('is_del',0);
		$status = $this->db->get('tbl_manage_referrer')->row_array();
	//	 echo $this->db->last_query();die;
		return $status;
	}
}
?>