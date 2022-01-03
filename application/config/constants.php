<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE')  OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE')   OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE')  OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ')                           OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE')                     OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE')       OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE')  OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE')                   OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE')              OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT')            OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT')       OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('EXIT_SUCCESS')        OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR')          OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG')         OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE')   OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS')  OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT')     OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE')       OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN')      OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX')      OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

/*----------------------------------------
api keys
----------------------------------------*/
defined('PROJECT_NAME') OR define('PROJECT_NAME','Jointly App');
defined('ACCESS_TOKEN') OR define('ACCESS_TOKEN','access_token');
defined('RESPONSE_FLAG')  OR define('RESPONSE_FLAG','flag');
defined('RESPONSE_FLAG_FAIL')  OR define('RESPONSE_FLAG_FAIL',0);
defined('RESPONSE_FLAG_SUCCESS')  OR define('RESPONSE_FLAG_SUCCESS',1);
defined('RESPONSE_MESSAGE')  OR define('RESPONSE_MESSAGE','msg');
defined('RESPONSE_DATA')  OR define('RESPONSE_DATA','data');
defined('RESPONSE_NEXT_OFFSET')  OR define('RESPONSE_NEXT_OFFSET','next_offset');
defined('ACCESS_FLAG')  OR define('ACCESS_FLAG','access_flag');
defined('RESPONSE_FLAG_ACCESS_DENIED')  OR define('RESPONSE_FLAG_ACCESS_DENIED', -1);
defined('RESPONSE_FLAG_PENDING_SOCIAL') OR define('RESPONSE_FLAG_PENDING_SOCIAL',2);
defined('PER_PAGE_LIST')  OR define('PER_PAGE_LIST',20);

/*----------------------------------------
Sinch SMS gateway
----------------------------------------*/
//define('YOUR_APP_KEY','2c22d1e6-225a-4e30-a201-01ad3729ac88');
//define('YOUR_APP_SECRET','SCWmMKTsOEmmriOXIBy4mg==');

//define('YOUR_APP_KEY','4a4e26c3-641b-432a-afc6-76a2c511fa7f');
//define('YOUR_APP_SECRET','/d61hpfCCEuF87Kk1FDP5g==');

/*----------------------------------------
Twillo SMS gateway
----------------------------------------*/
define('ACCOUNT_SID', 'AC58f3893b4f35c9bc5952a2987d3cea7f');
define('AUTH_TOKEN', 'b32c9e2ed8235d02b6acfc6671faf4a2');

// define('ACCOUNT_SID', '');
// define('AUTH_TOKEN', '');


/*Push notication key */
//defined('API_ACCESS_KEY_FOR_FIREBASE_PUSH')  OR define('API_ACCESS_KEY_FOR_FIREBASE_PUSH','AIzaSyDtChSYaM7wW_GpwTXhWLF7zUR3zhSaVeM');
//defined('API_ACCESS_KEY_FOR_FIREBASE_PUSH')  OR define('API_ACCESS_KEY_FOR_FIREBASE_PUSH','AIzaSyAezuHfqtvTAGjVTgQcu0gCE8IDl-oikSk');
defined('API_ACCESS_KEY_FOR_FIREBASE_PUSH')  OR define('API_ACCESS_KEY_FOR_FIREBASE_PUSH','AAAA-aROnv8:APA91bGp5mEpwvA8qwyRkvSmLcx3hwzhsnE-7fi-iuOGNGH2r2zjh6HxFuz_B0yhnP5DZNy0RIz_WOLQ-9JMnZcAFyJ9sdrdOFYyOriXJqrOEE5aQRFxU_MC_eoXbgYUmZ4_8oPo-m21');
/*
|--------------------------------------------------------------------------
| Stripe payment gateway
|--------------------------------------------------------------------------
*/

//define('STRIPE_API_KEY','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'); //live key
//define('STRIPE_API_KEY','sk_test_2xqexTU832LnJflCRIWesD2P00Re7Fu0lg'); //test key
define('STRIPE_API_KEY','sk_test_51JFiA0JiirEkyeV179XDN8UttTe4vbW8aISqsrKOxsqR84UEj6v3Y0Xu3R2p04wCzGa3JSdEysiM97yzJNUYY2Uj00RAKKeoHW'); //test key
//define('_PAY_TO_APP','20');
define('STRIPE_FEE','2.9');
define('EXTRA_STRIPE_FEE','0.30');
define('ACCOUNT_STATUS','verified');
