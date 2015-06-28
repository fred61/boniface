<?php
	require_once 'lib/log.php';
	
	interface Entity {
		function isPrimaryKey($columnName);
		
		function insert($pdo);
		
		function update($pdo);
	}
	
	trait EntityTrait {
		private $primaryKey;
		private $tableName;
		private $autoPK;
	
		function isPrimaryKey($columnName) {
			if (is_array($this->primaryKey)) {
				return array_key_exists($columnName, $this->primaryKey);
			} else {
				return $columnName == $this->primaryKey;
			}
		}
		
		function insert($pdo)
		{
			$sql= $this->getInsertStatement();
			
			self::$logger->debug("insert statement: $sql");
			
			$stmt= $pdo->prepare($sql);
			$this->bindInsertValues($stmt);
			$stmt->execute();
			$stmt->closeCursor();
		}
		
		function update($pdo)
		{
			$sql= $this->getUpdateStatement();
				
			self::$logger->info("update statement: $sql");
				
			$stmt= $pdo->prepare($sql);
			$this->bindInsertValues($stmt);
			$stmt->execute();
			$stmt->closeCursor();
				
		}
		
		private function getInsertStatement()
		{
			self::$logger->debug("getting insert statment");
			self::$logger->debugDump("entity adapter", $this);
			return "INSERT INTO " . $this->tableName . "(" . $this->getColumnList() . ") VALUES (" . $this->getColumnList(":") . ")";  
		}
		
		private function getUpdateStatement()
		{
			$selectList= "";
			$whereClause= "";
			
			foreach ($this->getActualObject() as $key => $value) {
				if ($this->isPrimaryKey($key)) {
					$whereClause= "${whereClause}${key} = :${key} and ";
					self::$logger->info("where: $whereClause");
				} else {
					$selectList= "${selectList}${key} = :${key}, ";
				}
			}
			
			$selectList= substr($selectList, 0, strlen($selectList) - 2);
			$whereClause= substr($whereClause, 0, strlen($whereClause) - 5);
			self::$logger->info("where: $whereClause");
				
			return "UPDATE " . $this->tableName . " SET " . $selectList . " WHERE " . $whereClause; 
		}
		
		private function getColumnList($prefix= NULL)
		{
			$result= "";

			foreach ($this->getActualObject() as $key => $value) {
				if (!$this->isPrimaryKey($key) || !$this->autoPK) {
					$result= $result . $prefix . "$key, ";
				}
			}
			
			return substr($result, 0, strlen($result) - 2);
		}
		
		private function bindInsertValues(&$stmt)
		{
			foreach ($this->getActualObject() as $key => $value) {
				if (!$this->isPrimaryKey($key) || !$this->autoPK) {
						self::$logger->debug("binding $key to $value");
						$stmt->bindValue(":$key", $value);
				}
			}
		}
		
		private function getActualObject()
		{
			if ($this->adaptee instanceof DecoratedObject) {
				return $this->adaptee->unwrap();
			} else {
				return $this->adaptee;
			}
		}
	}
?>