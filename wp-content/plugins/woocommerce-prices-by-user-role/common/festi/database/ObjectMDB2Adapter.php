<?php 

require_once dirname(__FILE__).'/ObjectAdapter.php';

/**
 * Adapter for PEAR::MDB2
 *
 * @package    phpObjectDB
 * @author     Denis Panaskin <goliathdp@gmail.com>
 */
class ObjectMDB2Adapter extends ObjectAdapter
{
    public function quote($obj, $type = null)
    {
        return $this->db->quote($obj, $type);
    }
    
    public function getRow($sql)
    {
        $result = $this->db->getRow($sql);
        
        if(PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    }
    
    public function getAll($sql)
    {
        $result = $this->db->getAll($sql);
        
        if(PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    }
    
    public function getOne($sql)
    {
        $result = $this->db->getOne($sql);
        
        if(PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    }
    
    public function getCol($sql)
    {
        $result = $this->db->getCol($sql);
        
        if(PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    }
    
    
    public function getAssoc($sql)
    {
        $result = $this->db->getAssoc($sql);
        
        if(PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    }
    
    public function query($sql)
    {
        $result = $this->db->query($sql);
        
        if(PEAR::isError($result)) {
            throw new DatabaseException($result->userinfo, $result->code);
        }

        return $result;
    }
    
    public function getInsertID()
    {
        return $this->getOne("SELECT LAST_INSERT_ID()");
    }

    public function begin($isolationLevel = false)
    {
        // TODO: Savepoint
        if ($this->db->inTransaction()) {
            $this->commit();
        }
        
        self::$_isStartTransaction = true;
        
        $this->db->beginTransaction();
    }
    
    public function commit()
    {
        $this->db->commit();
        self::$_isStartTransaction = false;
    }
    
    public function rollback()
    {
        $this->db->rollback();
        self::$_isStartTransaction = false;
    }
   
}