<?php

namespace db;

require_once(ini_load("composer"));
include_library("log");

use InfluxDB2\Client;
use InfluxDB2\Model\Organization;
use InfluxDB2\Model\BucketRetentionRules;
use InfluxDB2\Model\PostBucketRequest;
use InfluxDB2\Service\BucketsService;
use InfluxDB2\Service\OrganizationsService;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\ApiException;
use LOG;

class INFLUX
{
  private Client $client;
  private $organizationService;
  private $organizationID;
  private $bucketService;
  private $bucketID;
  public $precision = WritePrecision::S;
  public $bucketRetention = 0;
  public LOG $log;

  function newInflux($url, $token, $timeout): Client
  {
    return new Client(["url" => $url, "token" => $token, "timeout" => $timeout]);
  }

  function Init()
  {
    $this->client = $this->newInflux($this->url, $this->token, $this->timeout);
    if($this->organization) $this->setOrganization($this->organization);
    if($this->bucket) $this->setBucket($this->bucket);
  }

  function __construct(
    public string $token,
    private string $organization = "",
    private string $bucket = "",
    public string $url = "http://localhost:8086",
    public int $timeout = 10,
    LOG $log = NULL
  ) {
    $this->log = $log ? $log : new LOG();
    $this->Init();
  }

  function setURL(string $url)
  {
    $this->url = $url;
    $this->client = $this->newInflux($this->url, $this->token, $this->timeout);
  }

  function setToken(string $token)
  {
    $this->token = $token;
    $this->client = $this->newInflux($this->url, $this->token, $this->timeout);
  }

  //------------------------------------------------------------------------------------------------------------------- Organization

  function getOrganizationList(): array
  {
    if(!isset($this->organizationService))
      $this->organizationService = $this->client->createService(OrganizationsService::class);

    $output = [];
    $orgs = $this->organizationService->getOrgs()->getOrgs();
    foreach ($orgs as $org) {
      $output[$org["name"]] = $org["id"];
    }
    return $output;
  }

  function setOrganization(string $name): string
  {
    $orgs = $this->getOrganizationList();
    if(isset($orgs[$name])) {
      $this->organization = $name;
      $this->organizationID = $orgs[$name];
    }
    return $this->organizationID;
  }

  // create if not exist
  function createOrganization(string $name, bool $set = true): string
  {
    if(!isset($this->organizationService))
      $this->organizationService = $this->client->createService(OrganizationsService::class);
    $response = NULL;

    $list = $this->getOrganizationList();
    if(!isset($list[$name])) {
      $model = new Organization(["name" => $name]);
      $response = $this->organizationService->postOrgs($model)->getId();
    } else $response = $list[$name];

    if($set) $this->setOrganization($name);
    return $response;
  }

  //------------------------------------------------------------------------------------------------------------------- Bucket

  function getBucketList(bool $noSystemType = true): array
  {
    if(!isset($this->bucketService))
      $this->bucketService = $this->client->createService(BucketsService::class);

    $output = [];
    $buckets = $this->bucketService->getBuckets()->getBuckets();

    foreach ($buckets as $bucket) {
      if(!$noSystemType || $bucket["type"] != "system")
        $output[$bucket["name"]] = $bucket["id"];
    }
    return $output;
  }

  function getBucketLists(bool $noSystemType = true): array
  {
    if(!isset($this->bucketService))
      $this->bucketService = $this->client->createService(BucketsService::class);

    $output = [];
    $buckets = $this->bucketService->getBuckets();
    $buckets = $buckets["buckets"];

    $orgs = array_flip($this->getOrganizationList());

    foreach ($buckets as $bucket) {
      if(!$noSystemType || $bucket["type"] != "system") {
        if(!isset($output[$orgs[$bucket["org_id"]]]))
          $output[$orgs[$bucket["org_id"]]] = [];
          $output[$orgs[$bucket["org_id"]]][$bucket["name"]] = $bucket["id"];
      }
    }
    return $output; 
  }

  function setBucket(string $name): string
  {
    $buckets = $this->getBucketList($this->organizationID);
    if(isset($buckets[$name])) {
      $this->bucket = $name;
      $this->bucketID = $buckets[$name];
    }
    else {
      $this->bucketID = $this->createBucket($name);
    }
    return $this->bucketID;
  }

  function deleteBucket(string $name, string $organization = null): bool
  {
    if(!$organization) $organization = $this->organization;

    $buckets = $this->getBucketLists();
    if(isset($buckets[$organization]) && isset($buckets[$organization][$name])) {
      $this->bucketService->deleteBucketsID($buckets[$organization][$name]);
      return true;
    }
    return false;
  }

  // create if not exist
  function createBucket(string $name, string $organization = null, bool $set = true): string
  {
    if(!$organization) $organization = $this->organization;

    $buckets = $this->getBucketLists();
    if(isset($buckets[$organization]) && isset($buckets[$organization][$name]))
      return $buckets[$organization][$name];

    $orgs = $this->getOrganizationList();

    $rule = new BucketRetentionRules();
    $rule->setEverySeconds($this->bucketRetention);

    $bucketRequest = new PostBucketRequest();
    $bucketRequest->setName($name)->setRetentionRules([$rule])->setOrgId($orgs[$organization]);

    $response = $this->bucketService->postBuckets($bucketRequest);
    if($set) $this->setBucket($name);
    return $response->getId();
  }

  //------------------------------------------------------------------------------------------------------------------- Insert

  private function insert(mixed $data, string $precision = "") 
  {
    ob_start();
    try {
      $writeApi = $this->client->createWriteApi();
      $writeApi->write($data, $precision ?: $this->precision, $this->bucket, $this->organization);
      $writeApi->close();
      ob_get_clean();
    } catch (ApiException $e) {
      $debug = ob_get_clean();
      $this->log->Warning("Exception code " . $e->getCode() . " with 'influx' connection " . $this->url);
      $this->log->Warning($e->getMessage());
      if($debug) $this->log->Debug($debug);
      $this->Init();
    }
  }

  function insertValue(string $name, array $key, array $value, string $time = null, string $precision = "")
  {
    $point = Point::measurement($name)->addField($key, $value)->time($time ?: microtime(true));
    $this->insert($point, $precision);
  }

  function insertRow(string $name, array $row, string $precision = "")
  {
    if(isset($row["time"])) {
      $time = $row["time"];
      unset($row["time"]);
    }
    else $time = microtime(true);
    $data = ['name' => $name, 'fields' => $row, 'time' => $time ?: microtime(true)];
    $this->insert($data, $precision);
  }

  function insertArray(string $name, array $array, string $precision = "")
  {
    $data = []; $i = 0;
    foreach($array as $row) {
      if(isset($row["time"])) {
        $time = $row["time"];
        unset($row["time"]);
      }
      else $time = microtime(true);
      $data[$i] = ['name' => $name, 'fields' => $row, 'time' => $time];
      $i++;
    }
    $this->insert($data, $precision);
  }

  //------------------------------------------------------------------------------------------------------------------- Select

  /**
    * @param string $query Flux query string
    * @param string $mode "raw", "stream" or empty
    * @return result depending on a $mode chosen 
    */
  function Run(string $query, string $mode = ""): mixed
  {
    if(!isset($this->queryApi))
      $this->queryApi = $this->client->createQueryApi();

    return match (strtolower($mode)) {
      "raw" => $this->queryApi->queryRaw($query, $this->organization),
      "stream" => $this->queryApi->queryStream($query, $this->organization),
      default => $this->queryApi->query($query, $this->organization)
    };
  }

  function selectArray(string $query, string $timeKey = "time"): array
  {
    $temp = [];
    $results = $this->Run($query);

    foreach($results as $result) {
      foreach($result->records as $record) {
        $row = $record->values; 
        if(!isset($temp[$row["_time"]])) $temp[$row["_time"]] = [];
        $temp[$row["_time"]][$row["_field"]] = $row["_value"];
      }
    }
    $array = []; $i = 0;
    foreach($temp as $time => $row) {
      $row[$timeKey] = $time;
      $array[$i] = $row;
      $i++;
    }
    return $array;
  }

  function selectLast(string $name = ""): array|null
  {
    $flux = "from(bucket:\"" . $this->bucket . "\") |> range(start:-1y) |> last()";
    if($name) $flux .= " |> filter(fn:(r) => r._measurement == \"" . $name . "\")";
    $array = $this->selectArray($flux);
    if(isset($array[0])) return $array[0];
    else return NULL;    
  }

  function selectDataArray(string $name, array $fields, string $start, string $stop = ""): array
  {
    return [];
  }

  function selectAllDataArray(string $name, string $start, string $stop = ""): array
  {
    $flux = "from(bucket:\"" . $this->bucket . "\") |> range(start:" . $start;
    if($stop) $flux .= ", stop:". $stop;
    $flux .= ")";
    if($name) $flux .= " |> filter(fn:(r) => r._measurement == \"" . $name . "\")";
    return $this->selectArray($flux);    
  }
}
