<?php
/**
 * This file is on Google's library
 */
require_once realpath('/path/to/autoload.php');

/**
 * Client id and service account name as reported 
 * on https://console.developers.google.com/ - Projects - Credentials
 */
$client_id = 'long-string.apps.googleusercontent.com'; // 'CLIENT ID' on Google
$service_account_name = 'long-string@developer.gserviceaccount.com'; // 'EMAIL ADDRESS' on Google

/**
 * This is the .p12 file generated on https://console.developers.google.com/ - Projects - Credentials
 */
$key_file_location = '/path/to/file_name.p12';

/**
 * Email address for admin user that should be used to perform API actions
 * Needs to be created via Google Apps Admin interface and be added to an admin role
 * that has permissions for Admin APIs for Users
 */
$delegatedAdmin = 'admin@yourdomain.com';

/**
 * Array of scopes you need for whatever actions you want to perform
 * See https://developers.google.com/admin-sdk/directory/v1/guides/authorizing
 * The admin.directory.user is needed to create the user, the admin.directory.group is needed to add the 
 * user to a group (see later on this file)
 */
$scopes = array(
		'https://www.googleapis.com/auth/admin.directory.user',
		'https://www.googleapis.com/auth/admin.directory.group'
);

/**
 * Create AssertionCredentails object for use with Google_Client
 */
$cred = new Google_Auth_AssertionCredentials(
		$service_account_name,
		$scopes,
		file_get_contents($key_file_location)
);
/**
 * API requests shall be used using the delegated admin
 */
$cred->sub = $delegatedAdmin;

/**
 * Create Google_Client for making API calls
 */
$client = new Google_Client();
$client->setApplicationName("This is the name");

$client->setAssertionCredentials($cred);
if ($client->getAuth()->isAccessTokenExpired()) {
	$client->getAuth()->refreshTokenWithAssertion($cred);
}

/**
 * Create Google_Service_Directory
 */
$service = new Google_Service_Directory($client);

/**
 * Create the user
 */
$nameInstance = new Google_Service_Directory_UserName();
$nameInstance -> setGivenName('John');
$nameInstance -> setFamilyName('Doe');

$email = 'john.doe@domain.com';
$password = 'password';

$userInstance = new Google_Service_Directory_User();
$userInstance -> setName($nameInstance);
$userInstance -> setHashFunction("MD5");
$userInstance -> setPrimaryEmail($email);
$userInstance -> setPassword(hash("md5", $password));
try
{
	$createUserResult = $service->users->insert($userInstance);
	var_dump($createUserResult);
}
catch (Google_IO_Exception $gioe)
{
	echo "Error in connection: ".$gioe->getMessage();
}
catch (Google_Service_Exception $gse)
{
	echo "User already exists: ".$gse->getMessage();
}

/**
 * If you want it, add the user to a group
 */
$memberInstance = new Google_Service_Directory_Member();
$memberInstance->setEmail($email);
$memberInstance->setRole('MEMBER');
$memberInstance->setType('USER');
try
{
	$insertMembersResult = $service->members->insert('groupname@domain.com', $memberInstance);
}
catch (Google_IO_Exception $gioe)
{
	echo "Error in connection: ".$gioe->getMessage();
}

echo 'Done!';