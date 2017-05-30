<?php

/**
 * Adapter for PDO
 */
class ObjectPDOAdapter extends ObjectAdapter
{
    public function quote($obj, $type = null)
    {
        return $this->db->quote($obj, $type);
    }

    public function getRow($sql)
    {
        $query = $this->db->prepare($sql);

        if($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

    	$res = $query->execute();
        if (!$res) {
            $info = $query->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        return $query->fetch(PDO::FETCH_ASSOC);
    }

    public function getAll($sql)
    {
        $result = array();

        $query = $this->db->prepare($sql);
        if($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        $res = $query->execute();
        if (!$res) {
            $info = $query->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        $result = $query->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function getCol($sql)
    {
    	$query = $this->db->prepare($sql);
        if($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }
           
        $res = $query->execute();
        if (!$res) {
            $info = $query->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        $result = array();
        while (($cell = $query->fetchColumn()) !== false) {
        	$result[] = $cell;
        }
        
        return $result;
    }

    
    public function getOne($sql)
    {
        $query = $this->db->prepare($sql);
        if($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        $res = $query->execute();

    	if (!$res) {
        	$info = $query->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        return $query->fetchColumn();
    }

    public function getAssoc($sql)
    {
    	$result = array();

    	$query = $this->db->prepare($sql);
    	if($this->db->errorCode() > 0) {
    		$info = $this->db->errorInfo();
    		throw new DatabaseException($info[2], $info[1]);
    	}

    	$res = $query->execute();
    	if (!$res) {
    		$info = $query->errorInfo();
    		throw new DatabaseException($info[2], $info[1]);
    	}
    	
    	$result = array();
    	while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
    		$val = array_shift($row);
    		if (count($row) == 1) {
    			$row = array_shift($row);
    		}
    		$result[$val] = $row;
    	}

    	return $result;
    }

    public function begin($isolationLevel = false)
    {
        // TODO: Savepoint
        //if ($this->db->inTransaction()) {
        //    $this->commit();
        //}

        $this->db->beginTransaction();
        self::$_isStartTransaction = true;
    }

    public function commit()
    {
        $this->db->commit();
        self::$_isStartTransaction = false;
    }

    public function rollback()
    {
        $this->db->rollBack();
        self::$_isStartTransaction = false;
    }

    public function query($sql)
    {
        $affected_rows = $this->db->exec($sql);

        if($this->db->errorCode() > 0) {
            $info = $this->db->errorInfo();
            throw new DatabaseException($info[2], $info[1]);
        }

        return $affected_rows;
    }

    public function getInsertID()
    {
        return $this->db->lastInsertId();
    }
	
	public function getDatabaseType()
	{
		$type = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
		
		if ($type == "sqlsrv") {
			return Object::TYPE_MSSQL;
		}
		
		return $type;
	} // end getDatabaseType
	
}