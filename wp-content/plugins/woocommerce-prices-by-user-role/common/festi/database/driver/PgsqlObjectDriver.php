<?php

class PgsqlObjectDriver implements IObjectDriver
{
	public function quoteTableName($name)
	{
		return '"'.$name.'"';
	}
	
	public function quoteColumnName($key)
	{
		$key = "\"".$key."\"";
        if (strpos($key, '.') !== false) {
            $key = str_replace(".", "\".\"", $key);
        }
        
        return $key;
	}
}