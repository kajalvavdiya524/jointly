
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
=====================
Success messages
=====================
*/
defined('_OTP_SEND_TO_MOBILE_NO')  OR define('_OTP_SEND_TO_MOBILE_NO','Verification code has been sent to your phone number.');
defined('_DATA_NOT_FOUND')  OR define('_DATA_NOT_FOUND','Data not found');
defined('_USER_PROFILE_CREATED')  OR define('_USER_PROFILE_CREATED','User profile created.');
defined('_USER_PROFILE_UPDATED')  OR define('_USER_PROFILE_UPDATED','User profile updated.');
defined('_USER_MOB_VER_SUCCESS')  OR define('_USER_MOB_VER_SUCCESS','Your mobile number has been verified.');
defined('_USER_PROFILE')  OR define('_USER_PROFILE','User Profile.');
defined('__REFERRAL_ADDED')  OR define('__REFERRAL_ADDED','Referral sent.');
defined('_SET_REFERRAL_STATUS')  OR define('_SET_REFERRAL_STATUS','Referral status udpated.');
defined('_SET_REFERRER_STATUS')  OR define('_SET_REFERRER_STATUS','Referral status updated.');
defined('_SET_BUSINESS_STATUS')  OR define('_SET_BUSINESS_STATUS','Referral status updated.');
defined('__PAYMENT_SUCCESSFULLY')  OR define('__PAYMENT_SUCCESSFULLY','Payment sent to the referrer.');
defined('_STATE_LIST') OR define('_STATE_LIST','State list.');
defined('_SENT_REMINDER') OR define('_SENT_REMINDER','Reminder has been sent.');
define('_USER_LOGGED_OUT', 'Logout successful.');
defined('_OTP_SEND_SUCCESSFULLY') OR define('_OTP_SEND_SUCCESSFULLY', 'Verification code has been sent.');
defined('_ADD_CARD_SUCCESS') OR define('_ADD_CARD_SUCCESS', 'Card details have been saved securely.');
defined('_UPDATE_CARD_SUCCESS') OR define('_UPDATE_CARD_SUCCESS', 'Card details updated.');
define('_ACCOUNT_ADDED_SUCCESS', 'Billing address saved.');
define('_ACCOUNT_UPDATED_SUCCESS', 'Billing address updated.');

define('_BANK_ADDED_SUCCESS', 'Bank account details have been saved securely.');
define('_BANK_UPDATE_SUCCESS', 'Bank account details updated.');
define('_PRIVACY_DETAILS', 'Privacy policy details.');
define('_TERMS_DETAILS', 'Terms of service details.');
define('_TEAM_MEMBER_SUCCESSFULLY', 'Team member added successfully.');
define('_TEAM_MEMBER_LIST', 'Team member list.');
define('_DELETE_TEAM_MEMBER_SUCCESSFULLY', 'Team member deleted succesfully.');
define('_RELSTIONSHIP_DATA', 'Relationship data.');
define('_INVITE_CUSTOMER', 'Invite customer successfully.');
defined('_DATA_FOUND')  OR define('_DATA_FOUND','Data found');
defined('_BLOCK_REFERRER')  OR define('_BLOCK_REFERRER','Referrer user block successfully.');
defined('_MOBILE_NO_NOT_REGISTER')  OR define('_MOBILE_NO_NOT_REGISTER','Mobile no not register.');

/*
======================
Fail messages
======================
*/
defined('__NOTIFICATION_LIST_EMPTY')  OR define('__NOTIFICATION_LIST_EMPTY', 'Notification list is empty.');
defined('__STATE_LIST_EMPTY')  OR define('__STATE_LIST_EMPTY', 'State list empty.');
defined('__COUNTRY_LIST_EMPTY')  OR define('__COUNTRY_LIST_EMPTY', 'Country list empty.');
defined('__INVALID_CURRENT_PASSWORD')  OR define('__INVALID_CURRENT_PASSWORD','Invalid Current Password.');
define('__INVALID_CARD_NUMBER', 'Invalid card number.');
defined('__USERNAME_ALREADY_EXIST')  OR define('__USERNAME_ALREADY_EXIST','It seems username already exists.');
defined('__EMAIL_ALREADY_EXIST')  OR define('__EMAIL_ALREADY_EXIST','Email ID already exists.');
defined('__MOBILE_ALREADY_EXIST')  OR define('__MOBILE_ALREADY_EXIST','Phone number already exists.');
defined('__SOMETHING_WENT_WRONG')  OR define('__SOMETHING_WENT_WRONG','Something went wrong! Please try again later.');
defined('__USER_ACCESS_DENIED')  OR define('__USER_ACCESS_DENIED','Access denied');
defined('__USER_BLOCKED')  OR define('__USER_BLOCKED','Your login is blocked by admin. Contact administrator for more details.');
defined('__USER_NOT_LOGIN')  OR define('__USER_NOT_LOGIN','Your login is not activated by admin.');
defined('__USER_OTP_WRONG')  OR define('__USER_OTP_WRONG', 'Invalid verification code entered. Please try again.');
defined('__OTP_EXPIRED')  OR define('__OTP_EXPIRED','Verification code has expired. Please select resend verification code.');
defined('__MOBILE_VERY_PENDING')  OR define('__MOBILE_VERY_PENDING', 'Mobile number not verified.');
defined('__NO_ACCOUNT_REGISTERED_WITH_THIS_NO')  OR define('__NO_ACCOUNT_REGISTERED_WITH_THIS_NO','No registered user with this phone number.');

defined('__REFERAL_NOT_FOUND')  OR define('__REFERAL_NOT_FOUND','Referral not found.');
defined('__BUSINESS_REFERAL_NOT_FOUND')  OR define('__BUSINESS_REFERAL_NOT_FOUND','Business referral not found.');

defined('__SEND_REFERAL_NOT_FOUND')  OR define('__SEND_REFERAL_NOT_FOUND','Sent referral not found.');

defined('__MY_REFERAL_NOT_FOUND')  OR define('__MY_REFERAL_NOT_FOUND','My referral not found.');

defined('__STATUS_NOT_UPDATED_TO_CANCEL')  OR define('__STATUS_NOT_UPDATED_TO_CANCEL','Referral cannot be cancelled once job in progress.');
defined('__CANNOT_MODIFY_BEFORE_BUSINESS_RESPONSE')  OR define('__CANNOT_MODIFY_BEFORE_BUSINESS_RESPONSE','You cannot change status before business has viewed the referral.');

defined('__bUSINESS_ALREADY_SET_TO_CANCEL')  OR define('__bUSINESS_ALREADY_SET_TO_CANCEL','You cannot change status to job complete, because business cancel already this job.');
defined('__bUSINESS_ALREADY_SET_TO_CANCEL_FOR_PROGRESS')  OR define('__bUSINESS_ALREADY_SET_TO_CANCEL_FOR_PROGRESS','You cannot change status to job in progress, because already cancelled.');
defined('__REFERRAL_NOT_EXIST')  OR define('__REFERRAL_NOT_EXIST','Referral does not exist.');
defined('__BOTH_MOBILE_NUMBER_MUST_BE_DIFFERENT')  OR define('__BOTH_MOBILE_NUMBER_MUST_BE_DIFFERENT','Customer and business cannot have the same number.');

defined('__STATUS_CANCEL_BY_REFERRAL')  OR define('__STATUS_CANCEL_BY_REFERRAL','You cannot change status, because already cancel this job by referral');
defined('__REFERRAL_STATUS_NOT_JOB_COMPLETED')  OR define('__REFERRAL_STATUS_NOT_JOB_COMPLETED','You cannot change status as job complete, before status not updated by referral');

defined('__CARD_NUMBER_ALREADY_EXISTS') OR define('__CARD_NUMBER_ALREADY_EXISTS','Card number already exists.');
define('__ACCOUNT_NUMBER_ALREADY_EXIST', 'Account number already exists.');

defined('_UPDATE_PAYMENT_METHOD_FOR_PAID') OR define('_UPDATE_PAYMENT_METHOD_FOR_PAID','Please enter your payment method to pay the referral fee <and open Payment Method - Make Payment page>');//Please update your Payment Method to pay the Referral Fee and close out the Referral.
defined('_UPDATE_PAYMENT_METHOD_FOR_RECEIVED') OR define('_UPDATE_PAYMENT_METHOD_FOR_RECEIVED','Referrer has not provided debit card details so payment cannot be processed. We will send them a notification and please check back later.');

defined('_UPDATE_STATUS_NEEDED_BY_REFERRAL') OR define('_UPDATE_STATUS_NEEDED_BY_REFERRAL','Please say to referral for update their status to job completed');


defined('__ALREADY_STATUS_CANCEL') OR define('__ALREADY_STATUS_CANCEL','Status already set as cancel.');

defined('__ALREADY_STATUS_COMPELTED') OR define('__ALREADY_STATUS_COMPELTED','Status already set as job complete.');
defined('__ALREADY_STATUS_PAID') OR define('__ALREADY_STATUS_PAID','Status already set as paid.');
defined('__ALREADY_STATUS_IN_PROGRESS') OR define('__ALREADY_STATUS_IN_PROGRESS','Status already set as job in progress.');
defined('__CANNOT_CHANGE_AFTER_JOB_COMPLETED') OR define('__CANNOT_CHANGE_AFTER_JOB_COMPLETED','Status cannot change to job in progress after set as complete.');
defined('__CUSTOMER_ACCOUNT_DETAILS_NOT_EXISTS') OR define('__CUSTOMER_ACCOUNT_DETAILS_NOT_EXISTS','Customer account details not exists.');
defined('__CARD_NOT_EXISTS') OR define('__CARD_NOT_EXISTS','Card details not exists.');
defined('__BANK_NOT_EXISTS') OR define('__BANK_NOT_EXISTS','Bank details not exists.');
defined('__ACCOUNT_NOT_EXISTS') OR define('__ACCOUNT_NOT_EXISTS','User account details not exists.');
defined('__PAYMENTS_NOT_FOUND') OR define('__PAYMENTS_NOT_FOUND','Payments not found');
defined('__REMINDER_RESTRICTION') OR define('__REMINDER_RESTRICTION','Reminder can only be sent once per week.');
defined('__bUSINESS_ALREADY_SET_TO_CANCEL_REMINDER')  OR define('__bUSINESS_ALREADY_SET_TO_CANCEL_REMINDER','You cannot send a Reminder, because the business already cancelled this referral.');
defined('__VIEW_NEW_REFERRAL_RESTRICTION')  OR define('__VIEW_NEW_REFERRAL_RESTRICTION','Sorry you cannot view any new referrals until all outstanding referral fees are paid.');
defined('__SET_JOB_COMPLETED_FIRST') OR define('__SET_JOB_COMPLETED_FIRST','Change status to job complete first before paying the referral fee.');
defined('STRIPE_VALIDATION_MSG_FOR_VERIFIED_ACCOUNTS') OR define('STRIPE_VALIDATION_MSG_FOR_VERIFIED_ACCOUNTS','Sorry you cannot change those details once your bank account has been verified.');
defined('__TEAM_MEMBER_LIST_EMPTY') OR define('__TEAM_MEMBER_LIST_EMPTY','Team meber list empty.');
defined('__YOU_BLOCKED_ANOTHER_USER') OR define('__YOU_BLOCKED_ANOTHER_USER','You are blocked by another user.');
defined('__MOBILE_NO_ALREADY_REGISTER') OR define('__MOBILE_NO_ALREADY_REGISTER','Mobile no already register.');
defined('__CUSTOMER_ALERADY_INVITED') OR define('__CUSTOMER_ALERADY_INVITED','Customer already invited.');
defined('__MOBILE_NO_NOT_REGISTER') OR define('__MOBILE_NO_NOT_REGISTER','Mobile no not register.');
defined('__MONTHLY_STRIPE_FEE_COMPLETED') OR define('__MONTHLY_STRIPE_FEE_COMPLETED','Your stripe fees limit is over.');
?>

