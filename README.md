# AlphaHue; A simple PHP SDK for the Phillips Hue API

## Synopsis
This is a quick-to-implement library that connects your PHP project to the Phillips Hue lighting system. 
## Setup
### Installation with Composer.
Clone the repository.
```
$ git clone https://github.com/innesian/PhpRestClient.git
```
Install Composer in your project using cURL (command below) or [download the composer.phar directly](http://getcomposer.org/composer.phar).
```
$ curl -sS http://getcomposer.org/installer | php
```
Let Composer install the project dependencies:
```
$ php composer.phar install
```
Once installed, include the autoloader in your script.
```php
<?php
include_once 'vendor/autoload.php'; // Path to autoload.php file.

// There are instructions on how to obtain Hostname and Username below.
$bridge_hostname = '192.168.1.1';
$bridge_username = 'xxxxxxxxxxx';

$hue = new \AlphaHue\AlphaHue($bridge_hostname, $bridge_username);
```
### (or) add PhpRestClient as a dependency to your Hue project using Composer.
Create a *composer.json* file in your project and add `adam-innes/php-rest-client` as a required dependency.
```
{
    "require": {
        "adam-innes/php-rest-client": ">=1.0.0"
    }
}
```
### Discovering Bridge Hostname and creating a Username
[Phillips has a how-to on getting the internal IP address and creating a username](http://www.developers.meethue.com/documentation/getting-started)
* Make sure the Bridge is connected to the network and working. Check that your smartphone can connect.
* Get the internal IP address from your router -or- use the [broker service](http://www.meethue.com/api/nupnp).
* Follow the directions on obtaining a username from the [documentation](http://www.developers.meethue.com/documentation/getting-started).

### Connecting from a remote server
If you're connecting to your bridge from an external server you may need to forward a port via your router.
* *Step 1*: [Determine the internal IP address of your Bridge](http://www.meethue.com/api/nupnp).
* *Step 2*: [Forward an unused port to the internal IP address of the Bridge](https://www.noip.com/support/knowledgebase/general-port-forwarding-guide/)
* After forwarding (for example, port 24055, to the Bridge) your *Bridge Hostname* would be [<yourIpAddress>:24055](https://www.google.com/search?q=what+is+my+ip&oq=what+is+my+ip)


