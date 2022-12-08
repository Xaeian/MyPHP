Some code snippets are a piece of shit, but sharing these libraries allows me to put them in different projects. I hope someday they will be better 🥴

## Usage

Of course, you can copy code fragments, but if someone would like to work on them similarly to **Python**, where we include the used modules, then you should:

- Add the `PHPPATH` system variable with the location of the `__main.php` file
  - For example, `C:\my\location\for\library\_main.php`
- At the beginning of the script, call a line of code:

```PHP
require_once($_SERVER["PHPPATH"]);
```

- Of course, you can manually specify the location of the `__main.php` file, but this can make things difficult when moving the script.
  - In cooperation with the **Apache** server case, the indication of the path is required.

Modules starting with `_` are loaded automatically. The rest should be included with the `include_library` function.

## Main

**split** - Splits the string `$str` into a table of strings with the possibility of using the `$split` character within the `$stringChar` tags and including the escape character `$escapeChar`.

```php
function split(string $str, string $split = " ", string $stringChar = '"', string $escapeChar = "\\"):
```

# influx

## InfluxDB

The library _"simplifies"_ access to the database from **InfluxDB** data via a driver provided by Google (requires installation - preferably via **composer**). Only basic functionalities have been implemented in the library.

In the case of a non-existing organization or bucket, it will be created.
Measurements indexed by the name of the end device (node/endpoint `name` and measurement/series name/key `_measurment`). Tagging is not used `_tag`.

### Insert

TODO

### Select

TODO



# excel

in `.../PhpSpreadsheet/Calculation/Calculation.php` change line `2678`:

```php
$this->delta = 1 * 10 ** (0 - ini_get('precision'));
```

to this:

```php
$this->delta = 1 * 10 ** (0 - intval(ini_get('precision')));
```

# TCP/IP

If you are looking for a simple implementation of a TCP/IP server in PHP technology for serialized data, you've come to the right place. The library has a low overhead from the application level, but you have to take into account that it will not be suitable for some solutions. I use it for **IoT** devices that upload data to the database via it.

## Server

An example server application that returns a received message to the client with an added prefix. Closes the connection and waits for the next one.

```php
require_once("./conn.php");

use lib\conn\TCP_SERVER;

$servis = function($req) {
  echo($req . PHP_EOL);
  return ">> " . $req;
};

$server = new TCP_SERVER($server = "127.0.0.1", $port = 7000, $timeout = 0);

while(1) {
  $server->Loop($servis);
}
```

## Client

A sample client application that sends `Hello` to the server and displays the response.

```php
require_once("./conn.php");

use lib\conn\TCP_CLIENT;

$client = new TCP_CLIENT($server = "127.0.0.1", $port = 7000, $timeout = 1000);
$res = $client->Run("Hello");
echo($res);
```