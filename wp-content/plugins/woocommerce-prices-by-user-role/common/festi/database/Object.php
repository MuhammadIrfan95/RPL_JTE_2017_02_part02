<?php

require_once dirname(__FILE__).'/DatabaseException.php';
require_once dirname(__FILE__).'/IObject.php';
require_once dirname(__FILE__).'/driver/IObjectDriver.php';
require_once dirname(__FILE__).'/ObjectAdapter.php';

abstract class Object implements IObject
{
	const TYPE_MYSQL = "mysql";
	const TYPE_MSSQL = "mssql";
	
    const FETCH_ALL   = 100;
    const FETCH_ROW   = 101;
    const FETCH_ASSOC = 102;
    const FETCH_COL   = 103;
    const FETCH_ONE   = 104;
    
    private static $_instances;
    
    protected $adapter;
    
    public static function &factory(&$db)
    {
        $libName = false;
        $parentClass = get_parent_class($db);
        $currentClass = get_class($db);
        
        if ($parentClass == 'ObjectAdapter') {
            return $db;
        }
        
        switch ($currentClass) {
            case 'W3_Db': {
                $libName = 'WPDB';
            } break;
            
            case 'wpdb': {
                $libName = 'WPDB';
            } break;
                
            case 'PDO': {
                $libName = 'PDO';
            } break;
            
            case 'MysqlHandlerSocket': {
            	$libName = 'HandlerSocket';
            } break;
                
            default: {
                if($parentClass == 'MDB2_Driver_Common') {
                    $libName = 'MDB2';
                }
            }
        } // end switch
        
        if (!$libName) {
            throw new DatabaseException('Object Adapter not found');
        }
        
        $className = 'Object'.$libName.'Adapter';
        $path = dirname(__FILE__).'/'.$className.'.php'; 
        
        if (!include_once($path)) {
            throw new DatabaseException('Object Adapter not installed');
        }
        
        $instance = new $className($db);
        
        return $instance;
    } // end factory
    
    public function __construct(&$adapter) 
    {
        $this->adapter = $adapter;
    } // end __construct
    
    /**
     * Returns objects instance by name
     * 
     * @param string $name
     * @return Object
     */
    public static function &getInstance($name, &$db, $path = false)
    {
        if (isset(self::$_instances[$name])) {
            return self::$_instances[$name];
        }
        
        self::$_instances[$name] = self::getNewInstance($name, $db, $path);
       
        return self::$_instances[$name];
    } // end getInstance
    
    public static function getNewInstance($name, &$db, $path = false)
    {
    	$adapter = self::factory($db);
        
        $className = $name.'Object';
       
        // default path to objects
        if ($path) {
            $classFile = $path.'/'.$className.'.php';
            if (!file_exists($classFile)) {
                $path = false;
            }
        }

        if (!$path) {
            $path = realpath(dirname(__FILE__).'/../../objects/');
        }
       
        $classFile = $path.'/'.$className.'.php';
                          
        if ( !file_exists($classFile) ) {
            throw new DatabaseException(sprintf('File "%s" for object "%s" was not found.', $classFile, $name));
        }
            
        require_once $classFile;
        if ( !class_exists($className) ) {
            throw new DatabaseException(sprintf('Class "%s" was not found in file "%s".', $className, $classFile));
        }
        
        return new $className($adapter);
    } // end getNewInstance
    
    public function quote($obj, $type = null)
    {
        return $this->adapter->quote($obj, $type);
    }
    
    public function getRow($sql)
    {
        return $this->adapter->getRow($sql);
    }
    
    public function getAll($sql)
    {
        return $this->adapter->getAll($sql);
    }
    
    public function getOne($sql)
    {
        return $this->adapter->getOne($sql);
    }
    
    public function quoteTableName($name)
    {
        return $this->adapter->quoteTableName($name);
    }
	
	public function quoteColumnName($name)
    {
        return $this->adapter->quoteColumnName($name);
    }
    
    public function getCol($sql)
    {
        return $this->adapter->getCol($sql);
    }
    
    public function insert($table, $values, $is_update_dublicate = false)
    {
        return $this->adapter->insert($table, $values, $is_update_dublicate);
    }
    
    public function delete($table, $condition)
    {
        return $this->adapter->delete($table, $condition);
    }
    
    public function update($table, $values, $condition = array())
    {
        return $this->adapter->update($table, $values, $condition);
    }
    
    public function query($sql)
    {
        return $this->adapter->query($sql);
    }
    
    public function getAssoc($sql)
    {
        return $this->adapter->getAssoc($sql);
    }
    
    public function inTransaction()
    {
        return $this->adapter->inTransaction();
    }
    
    public function begin($isolationLevel = false)
    {
        return $this->adapter->begin($isolationLevel);
    }
    
    public function commit()
    {
        return $this->adapter->commit();
    }
    
    public function rollback()
    {
        return $this->adapter->rollback();
    }
    
    public function getAllSplit($query, $col, $page)
    {
        $result = array();
        $page -= 1;
        
        if(!preg_match('/SQL_CALC_FOUND_ROWS/Umis', $query)) {
            $query = preg_replace("/^SELECT/Umis", "SELECT SQL_CALC_FOUND_ROWS ", $query);
        }
        
        $query .= " LIMIT ".($page * $col).", ".$col;
        
        $result['rows'] = $this->getAll($query);
        
        $result['cnt']      = $this->getOne('SELECT FOUND_ROWS()');   
        $result['pageCnt']  = ceil($result['cnt'] / $col);
        
        return $result; 
    }// end getAllSplit
    
    public function searchByPage($sql, $condition, $ordeBy, $col, $page)
    {
        $where = $this->getSqlCondition($condition);
        
		if ($where) {
			$sql .= " WHERE ".join(" AND ", $where); 
		}
        
        if ($ordeBy) {
            $sql .= " ORDER BY ".join(", ", $ordeBy);
        }
        
        return $this->getAllSplit($sql, $col, $page);
    } // end searchByPage
    
    
    public function getSqlCondition($obj = array()) 
    {
         return $this->adapter->getSqlCondition($obj);
    } // end getSqlCondition
    
    public function getUpdateValues($values, $tableName = false)
    {
        return $this->adapter->getUpdateValues($values, $tableName);
    }
    
    public function getInsertSQL($table, $values, $is_update_dublicate = false) 
    {
        return $this->adapter->getInsertSQL($table, $values, $is_update_dublicate);
    }
    
    public function getUpdateSQL($table, $values, $condition = array()) 
    {
        return $this->adapter->getUpdateSQL($table, $values, $condition);
    }
    
    public function massInsert($table, $values, $inForeach = false) 
    {
        return $this->adapter->massInsert($table, $values, $inForeach);
    }
	
	public function getInsertID() 
    {
        return $this->adapter->getInsertID();
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
        $condition, 
        $selectSql = false, 
        $orderBy = array()
    )
    {
        return $this->adapter->getSelectSQL($condition, $selectSql, $orderBy);
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
        $type = self::FETCH_ALL
    )
    {
        return $this->adapter->select($selectSql, $condition, $orderBy, $type);
    } // end select
    
    /**
     * Returns an array of filter fields
     * 
     * @param $search
     * @return array
     */
    public function getConditionFields($search)
    {
        return $this->adapter->getConditionFields($search);
    } // end getConditionFields
	
	public function getDatabaseType()
	{
		return $this->adapter->getDatabaseType();
	} // end getDatabaseType
    
}
