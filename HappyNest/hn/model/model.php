<?php
	require_once 'happy_parent.php';
	require_once 'happy_child.php';
	require_once 'session.php';
	
	class ModelFactory {
		static $logger;
	
		static function initLogger()
		{
			self::$logger= new Logger(__CLASS__);
		}
		
		static $loadAlways= true;
	
		static $dbh;
		static $activeParents;
		static $allParents;
		static $allChildren;
		static $allSessions;
	
		static function initialise() {
			self::$logger->debug('initialising');
				
			if (! isset(self::$dbh)) {
				self::$logger->debug('connecting');
	
				$dsn= "mysql:host=" . config::$host . ";dbname=" . config::$db;
	
				self::$dbh= new PDO($dsn, config::$user, config::$pwd);
	
				if (!self::$dbh) {
					throw new Exception("could not connect to database");
				}
	
				self::$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
		}
	
		static function getAllParents() {
			$sth= self::$dbh->query("select * from parent", PDO::FETCH_CLASS, "HappyParentDTO");
			
			if (!$sth) {
				throw new Exception('failed to get parents from DB');
			} else {
				$resultSet= $sth->fetchAll();
				$sth->closeCursor();
				return self::loadAll($resultSet);
			}
				
		}
	
		static function getActiveParents() {
			$result= array();
			$allParents= self::getAllParents();

			foreach($allParents as $happyParent) {
				if (isset($happyParent->children[0])) {
					$result[$happyParent->id]= $happyParent;
				}
			}
			
			return $result;
		}
		
		static function getSingleParent($id) {
			$sth= self::$dbh->query("select * from parent where id = $id", PDO::FETCH_CLASS, "HappyParentDTO");
				
			if (!$sth) {
				throw new Exception('failed to get parents from DB');
			} else {
				$resultSet= $sth->fetchAll();
				$sth->closeCursor();
				$result= self::loadAll($resultSet);
				return $result[$id];
			}
		}
		
		static function makeNewParent()
		{
			$happyParent= new HappyParent(new HappyParentDTO());
			
			$happyChild= new HappyChild(new HappyChildDTO());
			
			array_push($happyParent->children, $happyChild);
			$happyChild->parent= $happyParent;
			
			return $happyParent;
		}
	
		static function putParent(&$happyParent)
		{
			self::$logger->always("putting parent");
			
			self::$dbh->beginTransaction();
			
			try {

				if ($happyParent instanceof HappyParent) {
					
					if (is_numeric($happyParent->id)) {
						self::$logger->always("updating parent");
						self::updateParent($happyParent);
					} else {
						self::$logger->always("inserting parent");
						self::insertParent($happyParent);
					}
				}
				
				self::$dbh->commit();
				
			} catch (Exception $e) {
				self::$dbh->rollBack();
				
				echo "<h2>Exception storing Parent</h2>";
				echo "<pre>\n";
				echo $e->getMessage(); echo "\n";
				echo $e->getTraceAsString();
				echo "</pre>";
				 
			}
		}
		
		static function getAllSessions()
		{
			if (!isset(self::$allSessions)) {
				self::loadAllSessions();
			}
			return self::$allSessions;
		}
					
		static function copyFromRequest(&$object, $requestKeyPrefix=NULL, $controlIndex=NULL)
		{
			if ($object instanceof DecoratedObject)
			{
				self::$logger->debug("decorated object, unwrapping");
				$actualObject= $object->unwrap();
			} else {
				self::$logger->debug("non-decorated object, using as is");
				$actualObject= $object;
			}
			
			foreach ($actualObject as $key => $value) {
	
				if (is_null($requestKeyPrefix)) {
					$requestKey= $key;
				} else {
					$requestKey= $requestKeyPrefix . "_" . $key;
				}
				
				if (!is_null($controlIndex)) {
					$requestKey= $requestKey . "_" . $controlIndex;
				}
	
				if (array_key_exists($requestKey, $_REQUEST)) {
					self::$logger->debug("request key $requestKey exists, value is [" . $_REQUEST[$requestKey] . "]");
					$actualObject->{$key}= $_REQUEST[$requestKey];
				}
			}
		}
		
		private static function insertParent($happyParent)
		{
			$adapter= new HappyParentEntityAdapter($happyParent);
			$adapter->insert(self::$dbh);
			
			$happyParent->id= self::$dbh->lastInsertId();
			
			foreach($happyParent->children as &$child) {
				self::$logger->debug("inserting child");
			
				$child->parent_id= $happyParent->id;
				self::$logger->debugDump("child", $child);
			
				$adapter= new HappyChildEntityAdapter($child);
				$adapter->insert(self::$dbh);
				$child->id= self::$dbh->lastInsertId();
					
				foreach($child->sessions as $session) {
					foreach($session as $so) {
						self::$logger->debug("inserting session ");
						$adapter= new SessionOccurenceEntityAdapter($so);
						$adapter->insert(self::$dbh);
					}
				}
			}
		}
		
		private static function updateParent($happyParent)
		{
			self::$logger->always("updating parent");
			
			$adapter= new HappyParentEntityAdapter($happyParent);
			$adapter->update(self::$dbh);
			
			foreach($happyParent->children as &$child) {
				self::$logger->always("updating child");
					
				$child->parent_id= $happyParent->id;
				self::$logger->debugDump("child", $child);
					
				$adapter= new HappyChildEntityAdapter($child);
				
				if (is_numeric($child->id)) {
					$adapter->update(self::$dbh);
				} else {
					$adapter->insert(self::$dbh);
					$child->id= self::$dbh->lastInsertId();
				}
				
				foreach($child->sessions as $session) {
					self::$logger->debug("updating sessions");
					foreach($session as $so) {
						self::$logger->debugDump("session occurence", $so);
						$adapter= new SessionOccurenceEntityAdapter($so);
						
						if ($so->isNew()) {
							//TODO really I want isNew and isChanged here
							$adapter->insert(self::$dbh);
						} else if ($so->isTouched()) {
							$adapter->update(self::$dbh);
						}
					}
				}
				
			}
		}
	
		private static function loadAll($resultSet) {
			function cmp_parents($parent1, $parent2)
			{
				$child1= "";
				$child2= "";
	
				if (isset($parent1->children[0]))
				{
					$child1= $parent1->children[0]->nickname;
				}
				if (isset($parent2->children[0]))
				{
					$child2= $parent2->children[0]->nickname;
				}
	
				if ($child1 > $child2) {
					return 1;
				} elseif ($child1 < $child2) {
					return -1;
				} else {
					return 0;
				}
			}
				
			self::$logger->debug('loading');
				
			$result= array();
				
			self::$logger->debug('got ' . sizeof($resultSet) . ' parents');
				
			foreach($resultSet as $dto) {
				$result[$dto->id]= new HappyParent($dto);
			}
			
			self::$logger->debug('have ' . count(array_keys($result)) . ' keys in result');

			$placeHolders = implode(',', array_fill(0, count(array_keys($result)), '?'));
						
			$sth= self::$dbh->prepare("select * from child where parent_id in ($placeHolders) and (leave_date is null or leave_date > sysdate())");
			$sth->setFetchMode(PDO::FETCH_CLASS, "HappyChildDTO");
			$sth->execute(array_keys($result));
			
			$children= array();
			
			foreach($sth as $dto)
			{
				$happyChild= new HappyChild($dto);
				$children[$dto->id]= $happyChild;
				$happyChild->parent= $result[$dto->parent_id];
				
				array_push($happyChild->parent->children, $happyChild);
			}
			
			$sth->closeCursor();
			
			$placeHolders = implode(',', array_fill(0, count(array_keys($children)), '?'));
			$sth= self::$dbh->prepare("select * from session_occurence where child_id in ($placeHolders)");
			$sth->setFetchMode(PDO::FETCH_CLASS, "SessionOccurenceDTO");
			$sth->execute(array_keys($children));
			
			foreach($sth as $dto)
			{
				$sessionOccurence= new SessionOccurence($dto);
				$happyChild= $children[$dto->child_id];
				if (!isset($happyChild->sessions[$dto->session_id])) {
					$happyChild->sessions[$dto->session_id]= array();
				} else {
					$so= array_pop($happyChild->sessions[$dto->session_id]);
					$so->valid_to= $sessionOccurence->valid_from;
					array_push($happyChild->sessions[$dto->session_id], $so);
				}
				array_push($happyChild->sessions[$dto->session_id], $sessionOccurence);
			}
			
			$sth->closeCursor();
				
			
// 			$children= $sth->fetchAll();
// 			$sth->closeCursor();
				
// 			self::$logger->debugDump($children);
				
// 			foreach($children as $dto) {
// 				$happyChild= new HappyChild($dto);
// 				$happyChild->parent= $result[$dto->parent_id];
				
// 				array_push($happyChild->parent->children, $happyChild);
// 			}
				
				
			uasort($result, "cmp_parents");
			
			return $result;
		}
		
		private function loadAllSessions()
		{
			self::$allSessions= array();
			
			$sth= self::$dbh->query("select * from session", PDO::FETCH_CLASS, "SessionDTO");
				
			if (!$sth) {
				throw new Exception('failed to get sessions from DB: ' . self::$dbh->errorInfo()[2]);
			} else {
				foreach($sth as $sessionDTO) {
					self::$allSessions[$sessionDTO->id]= new Session($sessionDTO);
				}
			}
		}
	}
	ModelFactory::initLogger();
?>	