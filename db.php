<?php

require_once 'PdoFactory.php';

class WpMultiDb extends wpdb {

    private $hosts = array();
    private $db;
    private $user;
    private $password;
    private $port;
    public $charset;

    /**
     * Construct wpdb class
     * @param array  $hosts    Host addresses
     * @param string $db       Database name
     * @param string $user     User name
     * @param string $password User pass
     * @param string $port     Port
     * @param string $charset  Charset
     */
    public function __construct($hosts, $db, $user, $password, $port, $charset = 'utf8')
    {
        if(WP_DEBUG) {
            $this->show_errors();
        }

        $this->hosts = $hosts;
        $this->db = $db;
        $this->user = $user;
        $this->password = $password;
        $this->port = $port;
        $this->charset = $charset;

        $this->connect();
    }

    /**
     * Create connection
     */
    private function connect()
    {
        $pdoFactory = new PdoFactory(
            $this->hosts, 
            $this->db, 
            $this->user, 
            $this->password, 
            $this->port, 
            $this->charset
        );

        try {
            $this->dbh = $pdoFactory->createConnection();
        } catch(Exception $e) {
            $this->bail('Error establishing a database connection');
        }
    }

    /**
     * Perform a database query, using current database connection
     *
     * @param string $query Database query
     * @return int|false Number of rows affected/selected or false on error
     */
    function query($query)
    {
        if (function_exists('apply_filters')){
            $query = apply_filters('query', $query);
        }

        $this->flush();
        
        try{
            $statement = $this->dbh->prepare($query);
            $statement->execute();
            $this->result = $statement->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            $this->print_error($e->getMessage());
            return 0;
        }
        
        if($this->queryIs($query, array('create', 'alter', 'truncate', 'drop'))) {
            return $this->result;
        }

        if($this->queryIs($query, array('insert', 'replace'))) {
            $this->insert_id = $this->dbh->lastInsertId();
        }

        if($this->queryIs($query, array('insert', 'delete', 'update', 'replace'))) {
            $this->rows_affected = $statement->rowCount();
            return $this->rows_affected;
        }

        $this->last_result = $this->result;
        $this->num_rows = count($this->result);
        return $this->num_rows;
    }

    /**
     * Does the query match any of the specified query types?
     * @param  string $query Query statement
     * @param  array  $types Array of query types e.g. array('insert', 'delete')
     * @return boolean
     */
    private function queryIs($query, $types)
    {
        $types = implode('|', $types);
        return preg_match('/^\\s*('.$types.') /i', $query);
    }
}

require_once 'db-config.php';
$wpdb = new WpMultiDb($hosts, DB_NAME, DB_USER, DB_PASSWORD, $port, DB_CHARSET);

?>
