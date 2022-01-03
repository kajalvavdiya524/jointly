<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
=====================
Success messages
=====================
*/
defined('_OTP_SEND_TO_MOBILE_NO')  OR define('_OTP_SEND_TO_MOBILE_NO','OTP send successfully to your phone no.');
defined('_DATA_NOT_FOUND')  OR define('_DATA_NOT_FOUND','Data not found');
defined('_USER_PROFILE_CREATED')  OR define('_USER_PROFILE_CREATED','User profile saved successfully.');
defined('_USER_MOB_VER_SUCCESS')  OR define('_USER_MOB_VER_SUCCESS','Your mobile number verified successfully.');
defined('_USER_PROFILE')  OR define('_USER_PROFILE','User Profile.');
defined('__REFERRAL_ADDED')  OR define('__REFERRAL_ADDED','Referral added successfully.');
defined('_SET_REFERRAL_STATUS')  OR define('_SET_REFERRAL_STATUS','Referral status saved successfully.');
defined('_SET_REFERRER_STATUS')  OR define('_SET_REFERRER_STATUS','Referrer status saved successfully.');
defined('_SET_BUSINESS_STATUS')  OR define('_SET_BUSINESS_STATUS','Business status saved successfully.');
defined('__PAYMENT_SUCCESSFULLY')  OR define('__PAYMENT_SUCCESSFULLY','Payment successfully to Referrer.');
defined('_STATE_LIST') OR define('_STATE_LIST','State list.');
defined('_SENT_REMINDER') OR define('_SENT_REMINDER','Reminder send successfully');
define('_USER_LOGGED_OUT', 'You are logged out successfully.');
define('_PRIVACY_DETAILS', 'Privacy policy details.');
define('_TERMS_DETAILS', 'Terms of service details.');
/*
======================
Fail messages
======================
*/
defined('__NOTIFICATION_LIST_EMPTY')  OR define('__NOTIFICATION_LIST_EMPTY', 'Notification list empty.');
defined('__STATE_LIST_EMPTY')  OR define('__STATE_LIST_EMPTY', 'State list empty.');
defined('__COUNTRY_LIST_EMPTY')  OR define('__COUNTRY_LIST_EMPTY', 'Country list empty.');
defined('__INVALID_CURRENT_PASSWORD')  OR define('__INVALID_CURRENT_PASSWORD','Invalid Current Password.');
define('__INVALID_CARD_NUMBER', 'Invalid card number.');
defined('__USERNAME_ALREADY_EXIST')  OR define('__USERNAME_ALREADY_EXIST','It seems username already exists.');
defined('__EMAIL_ALREADY_EXIST')  OR define('__EMAIL_ALREADY_EXIST','It seems Email ID already exists.');
defined('__MOBILE_ALREADY_EXIST')  OR define('__MOBILE_ALREADY_EXIST','Phone number already exists.');
defined('__SOMETHING_WENT_WRONG')  OR define('__SOMETHING_WENT_WRONG','Something went wrong! please try again later.');
defined('__USER_ACCESS_DENIED')  OR define('__USER_ACCESS_DENIED','Access denied');
defined('__USER_BLOCKED')  OR define('__USER_BLOCKED','Your login is blocked by admin. Contact administrator for more details.');
defined('__USER_OTP_WRONG')  OR define('__USER_OTP_WRONG', 'Otp is wrong,enter right code.');
defined('__OTP_EXPIRED')  OR define('__OTP_EXPIRED','It seems your verification code is expired. Please try to resend new code.');
defined('__MOBILE_VERY_PENDING')  OR define('__MOBILE_VERY_PENDING', 'It seems you have not verified your mobile number yet.');
defined('__NO_ACCOUNT_REGISTERED_WITH_THIS_NO')  OR define('__NO_ACCOUNT_REGISTERED_WITH_THIS_NO','It seems there is no user registered with this phone no.');
defined('_OTP_SEND_SUCCESSFULLY') OR define('_OTP_SEND_SUCCESSFULLY', 'Otp send successfully.');
defined('__REFERAL_NOT_FOUND')  OR define('__REFERAL_NOT_FOUND','Referral not found.');
defined('__STATUS_NOT_UPDATED_TO_CANCEL')  OR define('__STATUS_NOT_UPDATED_TO_CANCEL','You cannot cancel after job in progress by business');
defined('__CANNOT_MODIFY_BEFORE_BUSINESS_RESPONSE')  OR define('__CANNOT_MODIFY_BEFORE_BUSINESS_RESPONSE','You cannot change status,before not view by business.');

defined('__bUSINESS_ALREADY_SET_TO_CANCEL')  OR define('__bUSINESS_ALREADY_SET_TO_CANCEL','You cannot change status to job complete, because business cancel already this job.');
defined('__bUSINESS_ALREADY_SET_TO_CANCEL_FOR_PROGRESS')  OR define('__bUSINESS_ALREADY_SET_TO_CANCEL_FOR_PROGRESS','You cannot change status to job in progress, because   already cancel this job by business');
defined('__REFERRAL_NOT_EXIST')  OR define('__REFERRAL_NOT_EXIST','Referral not exists.');
defined('__BOTH_MOBILE_NUMBER_MUST_BE_DIFFERENT')  OR define('__BOTH_MOBILE_NUMBER_MUST_BE_DIFFERENT','
	');

defined('__STATUS_CANCEL_BY_REFERRAL')  OR define('__STATUS_CANCEL_BY_REFERRAL','You cannot change status, because already cancel this job by referral');
defined('__REFERRAL_STATUS_NOT_JOB_COMPLETED')  OR define('__REFERRAL_STATUS_NOT_JOB_COMPLETED','You cannot change status as job complete, before status not updated by referral');
defined('_ADD_CARD_SUCCESS') OR define('_ADD_CARD_SUCCESS', 'Save card successfully.');
defined('_UPDATE_CARD_SUCCESS') OR define('_UPDATE_CARD_SUCCESS', 'Update card successfully.');
defined('__CARD_NUMBER_ALREADY_EXISTS') OR define('__CARD_NUMBER_ALREADY_EXISTS','Card number already exists.');
define('_ACCOUNT_ADDED_SUCCESS', 'Account added successfully.');
define('_ACCOUNT_UPDATED_SUCCESS', 'Account updated successfully.');
define('__ACCOUNT_NUMBER_ALREADY_EXIST', 'Account number already exists.');
define('_BANK_ADDED_SUCCESS', 'Bank added successfully');
define('_BANK_UPDATE_SUCCESS', 'Bank update successfully');
defined('_UPDATE_PAYMENT_METHOD_FOR_PAID') OR define('_UPDATE_PAYMENT_METHOD_FOR_PAID','Please enter your payment method to pay the referral fee and open Payment Method Make Payment page.');//Please update your Payment Method to pay the Referral Fee and close out the Referral.
defined('_UPDATE_PAYMENT_METHOD_FOR_RECEIVED') OR define('_UPDATE_PAYMENT_METHOD_FOR_RECEIVED','Referrer has not provided bank account details so payment cannot be processed. We will send them a notification and please check back later.');

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
defined('__bUSINESS_ALREADY_SET_TO_CANCEL_REMINDER')  OR define('__bUSINESS_ALREADY_SET_TO_CANCEL_REMINDER','You cannot send Reminder, because business cancel already this job.');
defined('__VIEW_NEW_REFERRAL_RESTRICTION')  OR define('__VIEW_NEW_REFERRAL_RESTRICTION','Sorry you cannot view any new referrals until all outstanding referral fees are paid.');
defined('__SET_JOB_COMPLETED_FIRST') OR define('__SET_JOB_COMPLETED_FIRST','Status change to job complete first, before pay referral fee.');
?>

