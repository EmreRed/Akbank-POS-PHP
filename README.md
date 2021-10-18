### SETUP
```php
include('Pos.php') // For Pure PHP (You have to download the files first)

Candy::plugin('EmreRed/POS-PHP-Client'); // For Candy PHP (No processing needed, you can use it directly. 😉)
```
<a href="https://github.com/CandyPack/CandyPHP">Check out CandyPHP!</a>

<hr>

### Akbank

#### Define
```php
$akbank = Pos::bank('akbank');
```
#### Set
```php
$akbank->config(['clientid' => "100000000",
                 'name'     => "username",
                 'password' => "password",
                 'key'      => "key"]);
```
#### Test Mode
```php
$akbank->test(); // Turns on test mode
$akbank->test(false); // Turns off test mode
```
#### 3D Pay
```php
$order = [
  'id' => 1,
  'amount' => 100,
  'url' => [
    'ok'   => 'https://www.example.com/success',
    'fail' => 'https://www.example.com/fail',
  ],
  'card' => [
    'number' => 5000000000000000,
    'cvv' => 123,
    'year' => '2022',
    'month' => '04'
  ]
];
$akbank->pay3d($order); // Returns form data
$akbank->form($order);  // Prints the payment form
```
#### 3D Pay Verify
```php
$akbank->verify();
```
