ConnectWise CodeIgniter Library
=========

This is a CodeIgniter library for interfacing with the ConnectWise API
Just put it in your application's library folder and load it as you 
would any other library.

Designed to use the [Curl library for CodeIgniter](http://philsturgeon.co.uk/code/codeigniter-curl) by Phil Sturgeon.


There are some config variables at the top of the file that you can
change directly in the library, or you can set them at runtime using the
appropriate setters.
e.g. - 
```php
$this->connectwise->useSSL(TRUE);
$this->connectwise->validSSL(FALSE);
$this->connectwise->setCWHost("connectwise.example.com");
```

Usage is reasonably easy, here is an example to retrieve all companies 
that have a name starting with 'Connect'
```php
$this->connectwise->setAction("FindPartnerCompaniesAction");
$options = array('Conditions' => 'CompanyName like "Connect*"');
$this->connectwise->setParameters($options);
$ret = $this->connectwise->makeCall();
```
At this point, $ret is an array containing the response from the API.