<?php

class PdoFactory
{
    private $hosts = array();
    private $db;
    private $user;
    private $password;
    private $port;
    private $charset;

    private $blacklistKey = 'db_blacklist';
    private $blacklistTtl = 30;

    function __construct($hosts, $db, $user, $password, $port = '3306', $charset = 'utf8')
    {
        $this->hosts = $hosts;
        $this->db = $db;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
        $this->charset = $charset;
    }

    public function createConnection()
    {
        $hosts = $this->getAvailableHosts();
        if (empty($hosts)) {
            throw new Exception('Could not connect to database');
        }

        $host = $hosts[array_rand($hosts)];
        
        $dsn = 'mysql:dbname='.$this->db.';host='.$host.";port=".$this->port.';charset='.$this->charset;

        try {
            return new PDO($dsn, $this->user, $this->password, $this->connectParams());
        } catch (PDOException $e) {
            $this->blacklistHost($host);
            return $this->createConnection();
        }
    }

    private function blacklistHost($host)
    {
        $blacklist = apc_fetch($this->blacklistKey) ?: array();
        $blacklist[] = $host;
        apc_store($this->blacklistKey, $blacklist, $this->$blacklistTtl);
    }

    private function getAvailableHosts()
    {
        $blacklist = apc_fetch($this->blacklistKey) ?: array();
        return array_diff(array_unique($this->hosts), $blacklist);
    }

    private function connectParams()
    {
        $params = array(PDO::ATTR_TIMEOUT => 1);

        if($this->charset == 'utf8'){
            $params[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';
        }

        return $params;
    }
}