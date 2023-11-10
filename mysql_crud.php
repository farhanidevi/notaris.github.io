<?php
/*
 * @Author Rory Standley <rorystandley@gmail.com>
 * @Version 1.3
 * @Package Database
 */
class Database{
	/* 
	 * Create variables for credentials to MySQL database
	 * The variables have been declared as private. This
	 * means that they will only be available with the 
	 * Database class
	 */
	private $db_host = "localhost";  // Change as required
	private $db_user = "root";  // Change as required
	private $db_pass = "";  // Change as required
	private $db_name = "db_notaris";	// Change as required
	
	/*
	 * Extra variables that are required by other function such as boolean con variable
	 */
	private $con = false; // Check to see if the connection is active
	private $result = array(); // Any results from a query will be stored here
    private $myQuery = "";// used for debugging process with SQL return
    private $numResults = "";// used for returning the number of rows
	
	// Function to make connection to database
	public function connect() {
        if (!$this->con) {
            $this->con = new mysqli($this->db_host, $this->db_user, $this->db_pass, $this->db_name);

            if ($this->con->connect_error) {
                array_push($this->result, $this->con->connect_error);
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
	
	public function disconnect() {
        if ($this->con) {
            if ($this->con->close()) {
                $this->con = false;
                return true;
            } else {
                return false;
            }
        }
    }
	
	public function sql($sql) {
        $query = $this->con->query($sql);
        $this->myQuery = $sql;

        if ($query) {
            $this->numResults = $query->num_rows;
            while ($row = $query->fetch_assoc()) {
                $this->result[] = $row;
            }
            return true;
        } else {
            array_push($this->result, $this->con->error);
            return false;
        }
    }
	
	public function select($table, $rows = '*', $join = null, $where = null, $order = null, $limit = null) {
        $q = 'SELECT ' . $rows . ' FROM ' . $table;
        if ($join != null) {
            $q .= ' JOIN ' . $join;
        }
        if ($where != null) {
            $q .= ' WHERE ' . $where;
        }
        if ($order != null) {
            $q .= ' ORDER BY ' . $order;
        }
        if ($limit != null) {
            $q .= ' LIMIT ' . $limit;
        }

        $this->myQuery = $q;
        if ($this->tableExists($table)) {
            $query = $this->con->query($q);
            if ($query) {
                $this->numResults = $query->num_rows;
                while ($row = $query->fetch_assoc()) {
                    $this->result[] = $row;
                }
                return true;
            } else {
                array_push($this->result, $this->con->error . ' : ' . $q);
                return false;
            }
        } else {
            return false;
        }
    }

    public function insert($table, $params = array()) {
        if ($this->tableExists($table)) {
            $columns = implode(', ', array_keys($params));
            $values = '"' . implode('", "', $params) . '"';
            $sql = "INSERT INTO `$table` ($columns) VALUES ($values)";

            $this->myQuery = $sql;
            if ($ins = $this->con->query($sql)) {
                array_push($this->result, $this->con->insert_id);
                return true;
            } else {
                array_push($this->result, $this->con->error);
                return false;
            }
        } else {
            return false;
        }
    }

    public function delete($table, $where = null) {
        if ($this->tableExists($table)) {
            if ($where == null) {
                $delete = "DELETE $table";
            } else {
                $delete = "DELETE FROM $table WHERE $where";
            }

            if ($del = $this->con->query($delete)) {
                array_push($this->result, $this->con->affected_rows);
                $this->myQuery = $delete;
                return true;
            } else {
                array_push($this->result, $this->con->error);
                return false;
            }
        } else {
            return false;
        }
    }

    public function update($table, $params = array(), $where) {
        if ($this->tableExists($table)) {
            $args = array();
            foreach ($params as $field => $value) {
                $args[] = "$field='$value'";
            }
            $sql = "UPDATE $table SET " . implode(',', $args) . " WHERE $where";

            $this->myQuery = $sql;
            if ($query = $this->con->query($sql)) {
                array_push($this->result, $this->con->affected_rows);
                return true;
            } else {
                array_push($this->result, $this->con->error . ' - ' . $sql);
                return false;
            }
        } else {
            return false;
        }
    }

    private function tableExists($table) {
        $tablesInDb = $this->con->query('SHOW TABLES FROM ' . $this->db_name . ' LIKE "' . $table . '"');
        if ($tablesInDb) {
            if ($tablesInDb->num_rows == 1) {
                return true;
            } else {
                array_push($this->result, $table . " does not exist in this database");
                return false;
            }
        }
    }

    public function getResult() {
        $val = $this->result;
        $this->result = array();
        return $val;
    }

    public function getSql() {
        $val = $this->myQuery;
        $this->myQuery = array();
        return $val;
    }

    public function numRows() {
        $val = $this->numResults;
        $this->numResults = array();
        return $val;
    }

    public function escapeString($data) {
        return $this->con->real_escape_string($data);
    }
} 