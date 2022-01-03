<?php
require APPPATH . '/libraries/stripe/init.php';

class Payment_cron extends CI_Controller
{
    public function __construct()
	{
		parent::__construct();
		Stripe\stripe::setApiKey(STRIPE_API_KEY);
	}
    
    public function setAutoPayment()
	{
		$this->db->select('*');
		$this->db->where('is_auto_payment', 1);
		$this->db->where('card_type', 'credit');
		$this->db->where('is_flag', 1);
		$this->db->where('is_del', 0);
		$userCard = $this->db->get('tbl_card')->result_array();

		if(!empty($userCard)) {
			$current_data = date('Y-m-d');
			$application_fee = $this->mdl_common->get_record('tbl_commission',Array('is_del' => 0),'application_fee')->row()->application_fee;
			foreach($userCard as $card) {
				if($card['default_amount'] != '' && $card['card_stripe_token'] != '') {
					$role = $this->db->get_where('tbl_user',Array('user_id' => $input_data['user_id'],'is_del' => '0'))->row()->role;
					if($role == 'manager') {
						$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('business_id' => $card['business_id'],'business_status !=' => 6,'is_del' => 0))->result_array();
						$is_flag = '0';
						if(empty($exist_referrer_data)) {
							$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('referrer_id' => $card['business_id'],'business_status !=' => 6,'is_del' => 0))->result_array();
							$is_flag = '1';
						}
					}
					else {
						$exist_referrer_data = $this->db->get_where('tbl_manage_referrer',array('business_id' => $card['business_id'],'business_status !=' => 6,'is_del' => 0))->result_array();
						$is_flag = '0';
					}
					
					if(!empty($exist_referrer_data)) 
					{
						foreach($exist_referrer_data as $referrer) 
						{
							if($is_flag == '1') {
								$referrer_id = $referrer['business_id'];
							}
							else {
								$referrer_id = $referrer['referrer_id'];
							}

							$get_card_data = $this->db->get_where('tbl_card',Array('business_id' => $referrer_id,'card_type' => 'debit','is_del' => '0'))->row_array();

							$this->db->select('*');
							$this->db->where('user_id', $card['business_id']);
							$this->db->where('manage_id', $referrer['manage_id']);
							$this->db->where('info_id', $referrer['info_id']);
							$this->db->where('is_paid', 0);
							$this->db->orderby('payment_id', 'desc');
							$paymentData = $this->db->get()->row_array();
							if(!empty($paymentData)) {
								$payment_date = date("Y-m-d", strtotime("+1 month", $paymentData['payment_date']));
								if(strtotime($current_data) == $payment_date) {
									if($sender_data['role'] == 'manager') {
										$teamMember = $this->mdl_common->get_record('tbl_manage_team_member',Array('user_id' => $card['business_id'],'is_del' => 0))->row_array();
									}
									else {
										$teamMember = $this->mdl_common->get_record('tbl_manage_team_member',Array('mobile_no' => $sender_data['mobile_no'],'is_del' => 0))->row_array();
									}
									$monthly_referral_fee = str_replace('$','',$teamMember['monthly_referral_fee']);
									
									$this->db->select("SUM(amount) AS total_sum");
									$this->db->where('user_id', $card['business_id']);
									$this->db->where('manage_id', $referrer['manage_id']);
									$this->db->where('info_id', $referrer['info_id']);
									//$this->db->where('is_paid', 1);
									$total_sum = $this->db->get('tbl_payment')->row()->total_sum;
									$total_amount = $total_sum + $card['default_amount'];

									$charge_id = '';
									if($monthly_referral_fee == '') {
										try
										{
											//$paid_amount = $card['default_amount'] - $application_fee;
											$amt = $price = $card['default_amount'];
											$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
											$change_amount = $amt - $fees;
											//$pay = ($change_amount * $commission) / 100;
											//$pay_amt = $change_amount - $pay;
											$pay_amt = $change_amount - $application_fee;
											
											//for manange stripe
											$amount = ($card['default_amount'] * 100);
											$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
											$change_amt = $amount - round($stripe_fees);
											//$payble = ($change_amt * $commission) / 100;
											//$payble_amount = round($change_amt) - round($payble);
											$payble_amount = round($change_amt) - round($application_fee);
											
											$charge = \Stripe\Charge::create(array(
												"amount" => $amount,
												"currency" => $receiver_data['currency'],
												"customer" => $sender_data['customer_token'],
												"statement_descriptor_suffix" => "ID".$referrer['assign_referral_id'],
												"transfer_data[destination]" => $receiver_data['customer_token'],
												"transfer_data[amount]" => $payble_amount
											));
											$charge_id = $charge->id;
										}
										catch (Exception $e) {
											$error = $e->getMessage();
											$charge_id = '';
										}
									}
									else {
										if($monthly_referral_fee >= $total_amount) {
											try
											{
												//$paid_amount = $card['default_amount'] - $application_fee;
												$amt = $price = $card['default_amount'];
												$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
												$change_amount = $amt - $fees;
												//$pay = ($change_amount * $commission) / 100;
												//$pay_amt = $change_amount - $pay;
												$pay_amt = $change_amount - $application_fee;
												
												//for manange stripe
												$amount = ($card['default_amount'] * 100);
												$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
												$change_amt = $amount - round($stripe_fees);
												//$payble = ($change_amt * $commission) / 100;
												//$payble_amount = round($change_amt) - round($payble);
												$payble_amount = round($change_amt) - round($application_fee);
												
												$charge = \Stripe\Charge::create(array(
													"amount" => $amount,
													"currency" => $receiver_data['currency'],
													"customer" => $sender_data['customer_token'],
													"statement_descriptor_suffix" => "ID".$referrer['assign_referral_id'],
													"transfer_data[destination]" => $receiver_data['customer_token'],
													"transfer_data[amount]" => $payble_amount
												));
												$charge_id = $charge->id;
											}
											catch (Exception $e) {
												$error = $e->getMessage();
												$charge_id = '';
											}
										}
									}

									if($charge_id != '')
									{
										$ins_data['user_id'] = $card['business_id'];
										$ins_data['manage_id'] = $referrer['manage_id'];
										$ins_data['info_id'] = $referrer['info_id'];
										$ins_data['is_paid'] = 0;
										$ins_data['fees'] = $fees;
										$ins_data['commission']	= 0;										
										$ins_data['charge_id'] = $charge_id;
										$ins_data['amount'] = $card['default_amount'];
										$ins_data['payable_amount'] = number_format($pay_amt, 2, '.', '');
										$ins_data['currency'] = $receiver_data['currency'];
										$ins_data['payment_date'] = date('Y-m-d');
										$ins_data['date_added'] = date('Y-m-d H:i:s');
										$ins_data['application_fee'] = $application_fee;
										$this->mdl_common->insert_record('tbl_payment',$ins_data);

										$ins_rdata['user_id'] = $get_card_data['business_id'];
										$ins_rdata['manage_id'] = $referrer['manage_id'];
										$ins_rdata['info_id'] = $referrer['info_id'];
										$ins_rdata['is_paid'] = 1;
										$ins_rdata['fees'] = $fees;
										$ins_rdata['commission']= 0;	
										$ins_rdata['charge_id'] = $charge_id;
										$ins_rdata['amount'] = $card['default_amount'];
										$ins_rdata['payable_amount'] = number_format($pay_amt, 2, '.', '');
										$ins_rdata['currency'] = $receiver_data['currency'];
										$ins_rdata['payment_date'] = date('Y-m-d');
										$ins_rdata['date_added'] = date('Y-m-d H:i:s');
										$ins_rdata['application_fee'] = $application_fee;
										$this->mdl_common->insert_record('tbl_payment',$ins_rdata);

										$up_status_data['business_status'] = 6;
										$up_status_data['referral_status'] = 6;
										$this->mdl_common->update_record('tbl_manage_referrer',Array('manage_id'=>$referrer['manage_id'],'info_id'=>$referrer['info_id']),$up_status_data);

										//
										$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $card['business_id'],'is_del' => 0))->row_array();
										$business_name = $business_data['first_name'].' '.$business_data['last_name'];
										$ref_data = $this->db->get_where('tbl_user',Array('user_id'=> $referrer_id,'is_del' => 0))->row_array();
										$ref_name = $ref_data['first_name'].' '.$ref_data['last_name'];
										//$push_msg =  $business_name." has viewed your Referral.";
										//$push_msg="Please update your bank account details to receive payment from ".$business_name;
										$push_msg="Congratulations ".$ref_name.",you have received a referral fee from ".$business_name." on behalf of Referral ".$referrer['assign_referral_id'];

										$this->db->select("P.user_id,register_id,P.device_type");
										//$this->db->where('P.device_type',1);
										$this->db->where('P.user_id', $referrer_id);
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
												'user_id' => $referrer_id,
												'message' => $push_msg,
												'notification_post_user_id'=> $card['business_id'],
												'is_flag'=> 2,
												'manage_id'=> $referrer['manage_id'],
												'info_id'=>$referrer['info_id'],
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
											$message_array['custom_data']['manage_id'] = $referrer['manage_id'];
											$message_array['custom_data']['info_id'] = $referrer['info_id'];
											$message_array['custom_data']['is_flag'] = 2;

											$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_id))->num_rows();
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
							}
							else {
								$sender_data = $this->mdl_common->get_record('tbl_user',Array('user_id' => $card['business_id'],'is_del' => 0))->row_array();
						
								$receiver_data = $this->mdl_common->get_record('tbl_referrer_account',Array('referrer_id' => $referrer_id, 'is_del' => 0))->row_array();

								try
								{
									//$paid_amount = $card['default_amount'] - $application_fee;
									$amt = $price = $card['default_amount'];
									$fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE);
									$change_amount = $amt - $fees;
									//$pay = ($change_amount * $commission) / 100;
									//$pay_amt = $change_amount - $pay;
									$pay_amt = $change_amount - $application_fee;
									
									//for manange stripe
									$amount = ($card['default_amount'] * 100);
									$stripe_fees = ((($price * STRIPE_FEE) / 100) + EXTRA_STRIPE_FEE) * 100;
									$change_amt = $amount - round($stripe_fees);
									//$payble = ($change_amt * $commission) / 100;
									//$payble_amount = round($change_amt) - round($payble);
									$payble_amount = round($change_amt) - round($application_fee);
									
									$charge = \Stripe\Charge::create(array(
										"amount" => $amount,
										"currency" => $receiver_data['currency'],
										"customer" => $sender_data['customer_token'],
										"statement_descriptor_suffix" => "ID".$referrer['assign_referral_id'],
										"transfer_data[destination]" => $receiver_data['customer_token'],
										"transfer_data[amount]" => $payble_amount
									));
									$charge_id = $charge->id;
								}
								catch (Exception $e) {
									$error = $e->getMessage();
									$charge_id = '';
								}
								if($charge_id != '')
								{
									$ins_data['user_id'] = $card['business_id'];
									$ins_data['manage_id'] = $referrer['manage_id'];
									$ins_data['info_id'] = $referrer['info_id'];
									$ins_data['is_paid'] = 0;
									$ins_data['fees'] = $fees;
									$ins_data['commission']	= 0;										
									$ins_data['charge_id'] = $charge_id;
									$ins_data['amount'] = $card['default_amount'];
									$ins_data['payable_amount'] = number_format($pay_amt, 2, '.', '');
									$ins_data['currency'] = $receiver_data['currency'];
									$ins_data['payment_date'] = date('Y-m-d');
									$ins_data['date_added'] = date('Y-m-d H:i:s');
									$ins_data['application_fee'] = $application_fee;
									$this->mdl_common->insert_record('tbl_payment',$ins_data);

									$ins_rdata['user_id'] = $get_card_data['business_id'];
									$ins_rdata['manage_id'] = $referrer['manage_id'];
									$ins_rdata['info_id'] = $referrer['info_id'];
									$ins_rdata['is_paid'] = 1;
									$ins_rdata['fees'] = $fees;
									$ins_rdata['commission']= 0;	
									$ins_rdata['charge_id'] = $charge_id;
									$ins_rdata['amount'] = $card['default_amount'];
									$ins_rdata['payable_amount'] = number_format($pay_amt, 2, '.', '');
									$ins_rdata['currency'] = $receiver_data['currency'];
									$ins_rdata['payment_date'] = date('Y-m-d');
									$ins_rdata['date_added'] = date('Y-m-d H:i:s');
									$ins_rdata['application_fee'] = $application_fee;
									$this->mdl_common->insert_record('tbl_payment',$ins_rdata);

									$up_status_data['business_status'] = 6;
									$up_status_data['referral_status'] = 6;
									$this->mdl_common->update_record('tbl_manage_referrer',Array('manage_id'=>$referrer['manage_id'],'info_id'=>$referrer['info_id']),$up_status_data);

									//
									$business_data = $this->db->get_where('tbl_user',Array('user_id'=> $card['business_id'],'is_del' => 0))->row_array();
									$business_name = $business_data['first_name'].' '.$business_data['last_name'];
									$ref_data = $this->db->get_where('tbl_user',Array('user_id'=> $referrer_id,'is_del' => 0))->row_array();
									$ref_name = $ref_data['first_name'].' '.$ref_data['last_name'];
									//$push_msg =  $business_name." has viewed your Referral.";
									//$push_msg="Please update your bank account details to receive payment from ".$business_name;
									$push_msg="Congratulations ".$ref_name.",you have received a referral fee from ".$business_name." on behalf of Referral ".$referrer['assign_referral_id'];

									$this->db->select("P.user_id,register_id,P.device_type");
									//$this->db->where('P.device_type',1);
									$this->db->where('P.user_id', $referrer_id);
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
											'user_id' => $referrer_id,
											'message' => $push_msg,
											'notification_post_user_id'=> $card['business_id'],
											'is_flag'=> 2,
											'manage_id'=> $referrer['manage_id'],
											'info_id'=>$referrer['info_id'],
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
										$message_array['custom_data']['manage_id'] = $referrer['manage_id'];
										$message_array['custom_data']['info_id'] = $referrer['info_id'];
										$message_array['custom_data']['is_flag'] = 2;

										$notification_count = $this->db->get_where('tbl_notification',Array('is_count' => 0,'user_id' => $referrer_id))->num_rows();
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
						}
					}
				}
			}
		}
	}
}
?>