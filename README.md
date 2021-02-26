# Installation 
1. Unzip or clone the project
2. Run `composer install` from the command line.

# Configuration
1.	Copy `config.sample.php` to `config.php`
2.	Lock down permissions on `config.php` to only be readable by the web process and the deployment user.
3.	Edit `config.php` with the domain, user, password and URL to be used for the South Carolina Department of Social Services web service.

# Testing
There is a `test.php` script in the base directory that you can run to test the process of requesting a new JWT from the web service.  It will output the XML if it works, or an error if it does not work.

# Usage
```php
require_once 'ScDssClient.php';

$client = new \ScDss\ScDssClient();
$response = $client->getCACInterfaceList();
if (!$response instanceof \SimpleXMLElement) {
    // If $response is false, you can use $client->getLastError() to see what went wrong.
    echo $client->getLastError() . "\n";
} else {
	// $response contains a SimpleXMLElement instance with the response from the server.
    echo $response->asXML();
}
```