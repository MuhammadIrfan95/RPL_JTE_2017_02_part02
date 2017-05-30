<?php 

require_once dirname(__FILE__).'/ObjectAdapter.php';

/**
 * Adapter for WordpressDB 
 *
 * @package    ObjectDB
 * @author     Denis Panaskin <goliathdp@gmail.com>
 */
class ObjectWPDBAdapter extends ObjectAdapter
{
    public function __construct(&$db)
    {
       parent::__construct($db);
       $this->db->hide_errors();
    } // end __construct
    
    public function quote($obj, $type = null)
    {
        return "'".esc_sql($obj)."'";
    }
    
    public function getRow($sql)
    {
        $result = $this->db->get_row($sql, ARRAY_A);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }

        return $result;
    }
    
    private function _hasError()
    {
        return !empty($this->db->last_error);
    }
    
    private function _getError()
    {
        return $this->db->last_error;
    }
    
    public function getAll($sql)
    {
        $result = $this->db->get_results($sql, ARRAY_A);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }

        return $result;
    }
    
    public function getOne($sql)
    {
        $result = $this->db->get_row($sql, ARRAY_N);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }

        return is_null($result[0]) ? false : $result[0];
    } // end getOne
    
    public function query($sql)
    {
        $result = $this->db->query($sql);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }
        
        return $result;
    }
    
    public function getCol($sql)
    {
        $result = $this->db->get_col($sql);
        
        if ($this->_hasError()) {
            throw new DatabaseException($this->_getError());
        }
        
        return $result;
    }
    
    public function getAssoc($sql)
    {
        $rows = $this->getAll($sql);
        
        $result = array();
        foreach ($rows as $row) {
            $val = array_shift($row);
            if (count($row) == 1) {
                $row = array_shift($row);
            }
            
            $result[$val] = $row;
        }

        return $result;
    } // end getAssoc
    
    public function begin($isolationLevel = false)
    {
        $this->query('START TRANSACTION');
    }
    
    public function commit()
    {
        $this->query('COMMIT');
    }
    
    public function rollback()
    {
        $this->query('ROLLBACK');
    }
    
    public function getInsertID()
    {
        return $this->getOne("SELECT LAST_INSERT_ID()");
    }

    public function getPrefix()
    {
        return $this->db->prefix;
    }
	
	public function getDatabaseType()
	{
		return Object::TYPE_MYSQL;
	}
}
