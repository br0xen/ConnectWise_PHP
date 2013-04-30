ConnectWise PHP Class
=========

This is a PHP Class for interfacing with the ConnectWise API
Just include it in your project as you would any other class.
You MUST have PHP compiled with curl support for this to work.

There are some config variables at the top of the file that you can
change directly in the class file, or you can set them at runtime 
using the appropriate setters.
e.g. - 
```php
$connectwise->useSSL(TRUE);
$connectwise->validSSL(FALSE);
$connectwise->setCWHost("connectwise.example.com");
```

Usage is reasonably easy, here is an example to retrieve all companies 
that have a name starting with 'Connect'
```php
$connectwise->setAction("FindPartnerCompaniesAction");
$options = array('Conditions' => 'CompanyName like "Connect*"');
$connectwise->setParameters($options);
$ret = $connectwise->makeCall();
```
At this point, $ret is an array containing the response from the API.
