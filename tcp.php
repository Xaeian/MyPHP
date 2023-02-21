<?php

include_library("log");

//--------------------------------------------------------------------------------------------------------------------- <-- MySQL

abstract class TCP_Base
{
  protected ?Socket $sock = NULL;
  protected LOG $log;
  protected int $timeout;
  public int $buffer = 8192;

  function __construct(?LOG $log = NULL)
  {
    $this->log = $log ? $log : new LOG();
  }

  function SetTimeout()
  {
    if($this->timeout) {
      $sec = intval($this->timeout / 1000);
      $usec = intval(($this->timeout % 1000) * 1000);
      socket_set_option($this->sock, SOL_SOCKET, SO_RCVTIMEO, ["sec" => $sec, "usec" => $usec]);
    }
  }
  
  function ThrowException($function)
  {
    $msg = $this->sock ? socket_strerror(socket_last_error($this->sock)) : socket_strerror(socket_last_error());
    throw new Exception($msg . " (" . $function . ")");
  }
}

class TCP_Server extends TCP_Base
{
  function __construct(
    public string $server = "127.0.0.1",
    public int $port = 7000,
    float $timeout = 0, // [ms]
    ?LOG $log = null
  ) {
    $this->timeout = $timeout;
    parent::__construct($log);
    set_time_limit(0);
    ob_implicit_flush(); 
    ignore_user_abort(true);
    ini_set('max_execution_time', 0);
    $this->sock = $this->Open();
  }

  function Open(): Socket
  {
    try {
      ob_start();
      if(($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
        $this->ThrowException("socket_create");
      }
      socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);
      $this->SetTimeout();
      if(socket_bind($sock, $this->server, $this->port) === false) {
        $this->ThrowException("socket_bind");
      }
      if(socket_listen($sock, 5) === false) {
        $this->ThrowException("socket_listen");
      }
      ob_get_clean();
    }
    catch (Exception $e) {
      $debug = ob_get_clean();
      $this->log->Warning("Exception code " . $e->getCode() . " with TCP/IP server " . $this->server . ":" . $this->port);
      $this->log->Warning($e->getMessage());
      if($debug) $this->log->Debug($debug);
      if($sock) socket_close($sock);
      exit();
    }
    return $sock;
  }

  function Close()
  {
    socket_close($this->sock);
  }

  function Loop($service): void
  {
    ob_start();
    try {
      if(($msgsock = socket_accept($this->sock)) === false) {
        $this->ThrowException("socket_accept");
      }
      if(($req = socket_read($msgsock, $this->buffer, PHP_BINARY_READ)) === false) {
        $this->ThrowException("socket_read");
      }
      ob_get_clean();
      $res = $service($req);
      ob_start();
      if(socket_write($msgsock, $res, strlen($res)) === false) {
        $this->ThrowException("socket_write");
      }
      ob_get_clean();
    }
    catch (Exception $e) {
      $debug = ob_get_clean();
      $this->log->Warning("Exception code " . $e->getCode() . " with 'tcp/ip' server " . $this->server . ":" . $this->port);
      $this->log->Warning($e->getMessage());
      if($debug) $this->log->Debug($debug);
      $this->Close();
      $this->sock = $this->Open();
    }
    finally {
      if(isset($msgsock) && $msgsock) {
        socket_close($msgsock);
        unset($msgsock);
      }
    }
  }
}

class TCP_Client extends TCP_Base
{
  function __construct(
    public string $server = "127.0.0.1",
    public int $port = 7000,
    float $timeout = 2000, // [ms]
    ?LOG $log = NULL
  ) {
    $this->timeout = $timeout;
    parent::__construct($log);
  }

  function Run(string $req): ?string
  {
    ob_start();
    try {
      if(($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
        $this->ThrowException("socket_create");
      }
      $this->sock = $sock;
      $this->SetTimeout();
      if((socket_connect($sock, $this->server, $this->port)) === false) {
        $this->ThrowException("socket_connect");
      }
      socket_write($sock, $req, strlen($req));
      $res = "";
      while($get = socket_read($sock, $this->buffer)) $res .= $get;
      if($res === "") $this->log->Warning("Server TCP/IP $this->server:$this->port is not responding");
      socket_close($sock);
      ob_get_clean();
    } catch (Exception $e) {
      $debug = ob_get_clean();
      $this->log->Warning("Exception code " . $e->getCode() . " with 'tcp/ip' client " . $this->server . ":" . $this->port);
      $this->log->Warning($e->getMessage());
      if($debug) $this->log->Debug($debug);
      $res = null;
    }
    return $res;
  }

  function Disp(): string
  {
    return "TCP_Client $this->server:$this->port";
  }
}

//---------------------------------------------------------------------------------------------------------------------
