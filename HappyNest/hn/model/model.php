<?php
	require_once 'happy_parent.php';
	require_once 'happy_child.php';
	require_once 'session.php';
	require_once 'lib/log.php';
	
	class ModelFactory {
		use LoggerTrait;
		
		static $loadAlways= true;
	
		static $dbh;
		static $activeParents;
		static $allParents;
		static $allChildren;
		static $allSessions;
	
		static function initialise() {
			self::debug('initialising');
				
			if (! isset(self::$dbh)) {
				self::debug('connecting');
	
				$dsn= "mysql:host=" . config::$host . ";dbname=" . config::$db;
	
				self::$dbh= new PDO($dsn, config::$user, config::$pwd, array(
 			   PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
				));
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
			self::info("putting parent");
			
			self::$dbh->beginTransaction();
			
			try {

				if ($happyParent instanceof HappyParent) {
					
					if (is_numeric($happyParent->id)) {
						self::info("updating parent");
						self::updateParent($happyParent);
					} else {
						self::info("inserting parent");
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
		
		static function getBookmarkDates()
		{
			$result= array();
				
			$sth= self::$dbh->query("select * from bookmark_date", PDO::FETCH_ASSOC);
			
			if (!$sth) {
				throw new Exception('failed to get bookmark dates from DB: ' . self::$dbh->errorInfo()[2]);
			} else {
				foreach($sth as $row) {
					array_push($result, $row);
				}
			}
			
			return $result;
		}
					
		static function copyFromRequest(&$object, $requestKeyPrefix=NULL, $controlIndex=NULL)
		{
			if ($object instanceof DecoratedObject)
			{
				self::debug("decorated object, unwrapping");
				$actualObject= $object->unwrap();
			} else {
				self::debug("non-decorated object, using as is");
				$actualObject= $object;
			}
			
			foreach ($actualObject as $key => $value) {
	
				if (is_null($requestKeyPrefix)) {
					$requestKey= $key;
				} else {
					$requestKey= $requestKeyPrefix . "_" . $key;
				}
				
	
				if (array_key_exists($requestKey, $_REQUEST)) {
					
					if (is_null($controlIndex)) {
						self::debug("request key $requestKey exists, value is [" . $_REQUEST[$requestKey] . "]");
						$actualObject->{$key}= $_REQUEST[$requestKey];
					} else {
						self::debug("request key $requestKey exists, value is [" . $_REQUEST[$requestKey][$controlIndex] . "]");
						$actualObject->{$key}= $_REQUEST[$requestKey][$controlIndex];
					}
				}
			}
		}
		
		private static function insertParent($happyParent)
		{
			$adapter= new HappyParentEntityAdapter($happyParent);
			$adapter->insert(self::$dbh);
			
			$happyParent->id= self::$dbh->lastInsertId();
			
			foreach($happyParent->children as &$child) {
				self::debug("inserting child");
			
				$child->parent_id= $happyParent->id;
				self::debugDump("child", $child);
			
				$adapter= new HappyChildEntityAdapter($child);
				$adapter->insert(self::$dbh);
				$child->id= self::$dbh->lastInsertId();
					
				foreach($child->sessions as $session) {
					foreach($session as $so) {
						self::debug("inserting session ");
						$adapter= new SessionOccurenceEntityAdapter($so);
						$adapter->insert(self::$dbh);
					}
				}
			}
		}
		
		private static function updateParent($happyParent)
		{
			self::info("updating parent");
			
			$adapter= new HappyParentEntityAdapter($happyParent);
			$adapter->update(self::$dbh);
			
			foreach($happyParent->children as &$child) {
				self::info("updating child");
					
				$child->parent_id= $happyParent->id;
				self::debugDump("child", $child);
					
				$adapter= new HappyChildEntityAdapter($child);
				
				if (is_numeric($child->id)) {
					$adapter->update(self::$dbh);
				} else {
					$adapter->insert(self::$dbh);
					$child->id= self::$dbh->lastInsertId();
				}
				
				foreach($child->sessions as $session) {
					self::debug("updating sessions");
					foreach($session as $so) {
						self::debugDump("session occurence", $so);
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
				
			self::debug('loading');
				
			$result= array();
				
			self::debug('got ' . sizeof($resultSet) . ' parents');
				
			foreach($resultSet as $dto) {
				$result[$dto->id]= new HappyParent($dto);
			}
			
			self::debug('have ' . count(array_keys($result)) . ' keys in result');

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
			
			self::loadAllSessions();
			
			foreach($sth as $dto)
			{
				$sessionOccurence= new SessionOccurence($dto);
				
				$sessionOccurence->session= self::$allSessions[$dto->session_id];
				
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
			
			foreach($children as $happyChild)
			{
				foreach($happyChild->sessions as $sessionOccurences)
				{
					usort($sessionOccurences, function ($s1, $s2){
						if ($s1->valid_from > $s2->valid_from) {
							return 1;
						} else if ($s1->valid_from < $s2->valid_from) {
							return -1;
						} else {
							return 0;
						}
					});
				}
			}
				
			
// 			$children= $sth->fetchAll();
// 			$sth->closeCursor();
				
// 			self::debugDump($children);
				
// 			foreach($children as $dto) {
// 				$happyChild= new HappyChild($dto);
// 				$happyChild->parent= $result[$dto->parent_id];
				
// 				array_push($happyChild->parent->children, $happyChild);
// 			}
				
				
			uasort($result, "cmp_parents");
			
			return $result;
		}
		
		private static function loadAllSessions()
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
?>	