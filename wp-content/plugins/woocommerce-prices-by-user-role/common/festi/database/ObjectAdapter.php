<?php

/**
 * @abstract
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 * @version rc1.2
 */
abstract class ObjectAdapter implements IObject
{
    protected $db;
	protected $driver;

    private static $_instances;
    
    /**
     * Flag for detection transaction
     * @var boolean
     */
    protected static $_isStartTransaction = false;

    protected $_dbTableNameDelimiterInColumnName = ".";

    protected $reservedWords = array(
    	'NOW()',
        'NOT NULL',
        'NULL',
        'CURRENT_DATE()',
        'CURRENT_TIME()',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'NOW'
    );

    public function __construct(&$db)
    {
        $this->db = $db;
		
		$this->driver = $this->_createDriverInstance();
    }
	
	private function _createDriverInstance()
	{
		$type = $this->getDatabaseType();
		
		$className = ucfirst($type).'ObjectDriver';
		
		require_once dirname(__FILE__).'/driver/'.$className.'.php';
		
		return new $className();
	} // end _createDriverInstance
    
    public function inTransaction()
    {
        return self::$_isStartTransaction;
    }

    /**
     * Returns objects instance by name
     *
     * @param string $name
     * @return Object
     */
    public function &get($name, $path = false)
    {
        if (isset(self::$_instances[$name])) {
            return self::$_instances[$name];
        }

        self::$_instances[$name] = Object::getInstance($name, $this->db, $path);

        return self::$_instances[$name];
    } // end get

    public function insert($table, $values, $is_update_dublicate = false)
    {
        $sql = $this->getInsertSQL($table, $values, $is_update_dublicate);

        $this->query($sql);

        return $this->getInsertID();
    }

    public function delete($table, $condition)
    {
        $where = $this->getSqlCondition($condition);

        $sql = "DELETE FROM ".$table;
		
		if ($where) {
			$sql .= " WHERE ".join(" AND ", $where);
		}

        return $this->query($sql);
    } // end delete

    /**
     * Generate an mass insert
     *
     * @param string $table
     * @param mixed $values
     * @throws DatabaseException
     * @return mixed
     */
    public function massInsert($table, $values, $inForeach = false)
    {
        if ($inForeach) {
            $res = array();
            foreach ($values as $value) {
                $res[] = $this->insert($table, $value);
            }
            
        } else {
            $rows = array();
            foreach ($values as $items) {
                $data = $this->getInsertValues($items);
                $rows[] = '('.join(', ', $data).')';
            }
            $sql = "INSERT INTO ".$table." (".join(", ", array_keys($values[0])).") VALUES ".join(', ', $rows);
            $res = $this->query($sql);
        }

        return $res;
    } // end massInsert

    /**
     * Returns quoted values for insert sql
     *
     * @param array $values
     * @see Object::getInsertSQL()
     * @see Object::massInsert()
     * @return array
     */
    private function getInsertValues($values)
    {
        foreach ($values as &$item) {
            if ( is_null($item) ) {
                $item = 'NULL';
                continue;
            }

            if ( !in_array($item, $this->reservedWords) ) {
                $item = $this->quote($item);
            }
        }
        unset($item);

        return $values;
    } // end getInsertValues

    public function update($table, $values, $condition = array())
    {
        $sql = $this->getUpdateSQL($table, $values, $condition);

        return $this->query($sql);
    }

    /**
     * Returns the generated SQL query to insert data
     *
     * @param string $table
     * @param array $values
     * @param boolean $is_update_dublicate
     * @return string SQL string
     */
    public function getInsertSQL($table, $values, $isUpdateDublicate = false)
    {
        $values = $this->getInsertValues($values);
		$columns = array_keys($values);
		
		foreach ($columns as &$column) {
			$column = $this->quoteColumnName($column);
		}
		unset($column);

        $sql = "INSERT INTO ".
					$this->quoteTableName($table)." ".
					"(".join(", ", $columns).") ".
					"VALUES (".join(", ", $values).")";

        if ($isUpdateDublicate) {
            $sql .= " ON duplicate KEY UPDATE ";
            $rows = array();
            foreach ($values as $field => $value) {
                $rows[] = $field." = ".$value;
            }

            $sql .= join(", ", $rows);
        }

        return $sql;
    } // end getInsertSQL

    public function getUpdateValues($values, $tableName = false)
    {
        foreach ($values as $key => &$item) {
            
            if ($tableName) {
                $key = $tableName.$this->_dbTableNameDelimiterInColumnName.$key;
            }
            
            if (is_null($item)) {
                $item = $this->quoteColumnName($key)." = NULL";
                continue;
            }

            $lastSymbol = mb_substr($key, mb_strlen($key) - 1, 1);
            if (in_array($lastSymbol, array('+', '-'))) {
                $key = str_replace($lastSymbol, "", $key);
                $item = $key." = ".$key." ".$lastSymbol." ".$this->quote($item);
                continue;
            }

            $key = $this->quoteColumnName($key);
            
            if (!$this->reservedWords || !in_array($item, $this->reservedWords)) {
                $item = $key." = ".$this->quote($item);
            } else {
                $item = $key." = ".$item;
            }
        }
        unset($item);
        
        return $values;
    } // end getUpdateValues
    
    
    /**
     * Returns the generated SQL query to update the data
     *
     * @param string $table
     * @param array $values
     * @param string|array $where
     * @return string
     */
    public function getUpdateSQL($table, $values, $condition = array())
    {
        $values = $this->getUpdateValues($values);
        
        $sql = "UPDATE ".$table." SET ".join(", ", $values);

        if (is_array($condition)) {
            $sqlCondition = $this->getSqlCondition($condition);
            if ($sqlCondition) {
                $sql .= " WHERE ".join(' AND ', $sqlCondition);
            }
        } else {
            $sql .= " WHERE ".$condition;
        }

        return $sql;
    } // end getUpdateSQL

    // FIXME: Need refactoring this
    public function getSqlCondition($obj = array())
    {
        $result = array();

        foreach ($obj as $key => $item) {
            $buffer = explode("&", $key);
            $action = !empty($buffer[1]) ? $buffer[1] : "=";

            if (in_array($action, array('IN', 'NOT IN'))) {
                $values = array();
                if (!$item) {
                    continue;
                }
                $item = is_scalar($item) ? array($item) : $item;
                foreach($item as $val) {
                    $values[] = $this->quote($val);
                }

                if($values) {
                    $result[] = $buffer[0]." ".$action." (".join(', ', $values).')';
                }
                continue;
            }

            if(strtolower($action) == 'or_sql') {
                $result[] = '('.join(' OR ', $item).')';
                continue;
            }

            if ($key == 'sql_or') {

                $search = array();
                foreach ($item as $row) {
                    if (is_scalar($row)) {
                        $search[] = $row;
                    } else {
                        $search[] = join(' AND ', $this->getSqlCondition($row));
                    }
                }

                $result[] = '(('.join(' ) OR (', $search).'))';
                continue;
            }

            if ($key == 'sql_and') {

                $search = array();
                foreach ($item as $row) {
                    if (is_scalar($row)) {
                        $search[] = $row;
                    } else {
                        $search[] = join(' AND ', $this->getSqlCondition($row));
                    }
                }

                $result[] = join(' AND ', $search);
                continue;
            }

            $command = strtolower($action);

            if ($command == 'or') {

                list($value, $others) = $item;

                $action = empty($buffer[2]) ? '&=' : '&'.$buffer[2];

                $condition = array($buffer[0].$action => $value);
                $ors = $this->getSqlCondition($condition);

                $others = $this->getSqlCondition($others);
                $conditions = array_merge($ors, $others);
                $result[] = '('.join(' OR ', $conditions).')';

                continue;
            } else if ($command == "match") {

               $result[] = "MATCH (".$buffer[0].") AGAINST (".$this->quote($item).")";
               continue;
            } else if ($command == "between") {
                $sqlCondition = $buffer[0]." ".$command." ";
                if (is_array($item)) {
                    $sqlCondition .= $this->quote($item[0])." AND ".$this->quote($item[1]);
                } else {
                    $sqlCondition .= $item;
                }
                
                $result[] = $sqlCondition;
                continue;
            }

            if (!in_array($item, $this->reservedWords)) {
                $result[] = $buffer[0]." ".$action." ".$this->quote($item);
            } else {
                $result[] = $buffer[0]." ".$action." ".$item;
            }
        }

        return $result;
    } // end getSqlCondition

	public function quoteTableName($name)
    {
        return $this->driver->quoteTableName($name);
    }
	
	public function quoteColumnName($name)
    {
        return $this->driver->quoteColumnName($name);
    }
	
    /**
     * Returns sql query without where. The method should be overridden
     *
     * @throws DatabaseException
     */
    protected function getSql()
    {
        throw new DatabaseException('Undefined method getSql', 2001);
    } // end getSql
    
    /**
     * Returns generate select sql query
     *
     * @param array $condition
     * @param string $selectSql
     * @return string
     */
    public function getSelectSQL(
        $condition, $selectSql = false, $orderBy = array()
    )
    {
        $sql = $selectSql ? $selectSql : $this->getSql();
    
        $where = $this->getSqlCondition($condition);
    
        if ($where) {
            $sql .=  ' WHERE '.join(' AND ', $where);
        }
    
        if ($orderBy) {
            $sql .= " ORDER BY ".join(', ', $orderBy);
        }
    
        return $sql;
    } // end getSelectSQL
    
    /**
     * Fetch rows returned from a query
     *
     * @param string $selectSql
     * @param array $condition
     * @param string $type
     * @throws DatabaseException
     * @return array
     */
    public function select(
        $selectSql, 
        $condition = array(), 
        $orderBy = array(), 
        $type = Object::FETCH_ALL
    )
    {
        $sql = $this->getSelectSQL($condition, $selectSql, $orderBy);
    
        $methods = array(
            Object::FETCH_ALL   => 'getAll',
            Object::FETCH_ROW   => 'getRow',
            Object::FETCH_ASSOC => 'getAssoc',
            Object::FETCH_COL   => 'getCol',
            Object::FETCH_ONE   => 'getOne',
        );
    
        if (!isset($methods[$type])) {
            $msg = sprintf('Undefined select type %s', $type);
            throw new DatabaseException($msg, 3005);
        }
    
        if (!is_callable(array($this, $methods[$type]))) {
            $msg = sprintf(
                'Method "%s" was not found in Object.', 
                $methods[$type]
            );
            
            throw new DatabaseException($msg, 3006);
        }
    
        return call_user_func_array(array($this, $methods[$type]), array($sql));
    } // end select
    
    /**
     * Returns an array of filter fields
     *
     * @param $search
     * @return array
     */
    public function getConditionFields($search)
    {
        $fields = array();
    
        foreach ($search as $key => $item) {
            $buffer = explode("&", $key);
    
            $info = explode('.', $buffer[0]);
    
            if (!isset($info[1])) {
                continue;
            }
    
            $fields[$info[0]][$info[1]] = $buffer[0];
        }
    
        return $fields;
    } // end getConditionFields
}