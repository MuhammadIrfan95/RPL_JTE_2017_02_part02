<?php

interface IObjectDriver
{
	public function quoteTableName($name);
	public function quoteColumnName($name);
}