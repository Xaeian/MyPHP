<?php

require_once(__DIR__ . "/../lib.php");
include_library("influx");

use db\INFLUX;

$token = "n--FmpTGMJbI7CjU4BKHzOqzmQrGSyqU6Hr65f_Akw5C5MRIHqB35WqyTm2khjvGIlOg2Zg8IMCURxqUiq1r3g==";
$influx = new INFLUX($token, "myorg", "mybucket", "http://localhost:8086");

// $influx->insertValue("myname", "")
// $influx->insertRow("myname", )


// insert point

// insert row

// insert array