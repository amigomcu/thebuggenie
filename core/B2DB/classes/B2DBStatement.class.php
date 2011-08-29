<?php

	/**
	 * B2DB Statement Base class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 2.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package b2db
	 * @subpackage core
	 */

	/**
	 * B2DB Statement Base class
	 *
	 * @package b2db
	 * @subpackage core
	 */
	class B2DBStatement
	{

		/**
		 * Current B2DBCriteria
		 *
		 * @var B2DBCriteria
		 */
		protected $crit;
		
		/**
		 * PDO statement
		 *
		 * @var PDOStatement
		 */
		public $statement;

		public $values = array();

		public $params = array();

		protected $insert_id = null;
		
		public $custom_sql = '';

		/**
		 * Returns a statement
		 *
		 * @param B2DBCriteria $crit
		 *
		 * @return B2DBStatement
		 */
		public static function getPreparedStatement($crit)
		{
			try
			{
				$statement = new B2DBStatement($crit);
			}
			catch (Exception $e)
			{
				throw $e;
			}

			return $statement;
		}

		public function __construct($crit)
		{
			try
			{
				if ($crit instanceof B2DBCriteria)
					$this->crit = $crit;
				else
					$this->custom_sql = $crit;
				
				$this->_prepare();
			}
			catch (Exception $e)
			{
				throw $e;
			}
		}

		/**
		 * Performs a query, then returns a resultset
		 *
		 * @param string $action[optional] The crud action performed (select, insert, update, delete, create, alter)
		 *
		 * @return B2DBResultset
		 */
		public function performQuery($action = '')
		{
			try
			{
				$values = ($this->getCriteria() instanceof B2DBCriteria) ? $this->getCriteria()->getValues() : array();
				TBGLogging::log('executing PDO query (' . \b2db\Core::getSQLCount() . ')', 'B2DB');

				$time = explode(' ', microtime());
				$pretime = $time[1] + $time[0];
				$res = $this->statement->execute($values);

				if (!$res)
				{
					$error = $this->statement->errorInfo();
					if (\b2db\Core::isDebugMode())
					{
						$time = explode(' ', microtime());
						$posttime = $time[1] + $time[0];
						\b2db\Core::sqlHit($this->printSQL(), implode(', ', $values), $posttime - $pretime);
					}
					throw new B2DBException($error[2], $this->printSQL());
				}
				if (\b2db\Core::isDebugMode())
				{
					TBGLogging::log('done', 'B2DB');
				}
				if ($this->getCriteria() instanceof B2DBCriteria && $this->getCriteria()->action == 'insert')
				{
					if (\b2db\Core::getDBtype() == 'mysql')
					{
						$this->insert_id = \b2db\Core::getDBLink()->lastInsertId();
					}
					elseif (\b2db\Core::getDBtype() == 'pgsql')
					{
						TBGLogging::log('sequence: ' . \b2db\Core::getTablePrefix() . $this->getCriteria()->getTable()->getB2DBName() . '_id_seq', 'b2db');
						$this->insert_id = \b2db\Core::getDBLink()->lastInsertId(\b2db\Core::getTablePrefix() . $this->getCriteria()->getTable()->getB2DBName() . '_id_seq');
						TBGLogging::log('id is: ' . $this->insert_id, 'b2db');
					}
				}
				$action = ($this->getCriteria() instanceof B2DBCriteria) ? $this->getCriteria()->action : '';
				$retval = new B2DBResultset($this);
				if (\b2db\Core::isDebugMode())
				{
					$time = explode(' ', microtime());
					$posttime = $time[1] + $time[0];
					\b2db\Core::sqlHit($this->printSQL(), implode(', ', $values), $posttime - $pretime);
				}
				if (!$this->getCriteria() || $this->getCriteria()->action != 'select')
				{
					$this->statement->closeCursor();
				}
				return $retval;
			}
			catch (Exception $e)
			{
				throw $e;
			}
		}
		
		/**
		 * Returns the criteria object
		 *
		 * @return B2DBCriteria
		 */
		public function getCriteria()
		{
			return $this->crit;
		}

		/**
		 * Return the ID for the inserted record
		 */
		public function getInsertID()
		{
			return $this->insert_id;
		}

		public function getColumnValuesForCurrentRow()
		{
			return $this->values;
		}
		
		/**
		 * Return the number of affected rows
		 */
		public function getNumRows()
		{
			return $this->statement->rowCount();
		}

		/**
		 * Fetch the resultset
		 */
		public function fetch()
		{
			try
			{
				if ($this->values = $this->statement->fetch(PDO::FETCH_ASSOC))
				{
					return $this->values;
				}
				else
				{
					return false;
				}
			}
			catch (PDOException $e)
			{
				throw new B2DBException('An error occured while trying to fetch the result: "' . $e->getMessage() . '"');
			}
		}

		/**
		 * Prepare the statement
		 */
		protected function _prepare()
		{
			try
			{
				if (!\b2db\Core::getDBLink() instanceof PDO)
				{
					throw new B2DBException('Connection not up, can\'t prepare the statement');
				}
				if ($this->crit instanceof B2DBCriteria)
				{
					$this->statement = \b2db\Core::getDBLink()->prepare($this->crit->getSQL());
				}
				else
				{
					$this->statement = \b2db\Core::getDBLink()->prepare($this->custom_sql);
				}
			}
			catch (Exception $e)
			{
				throw $e;
			}
		}
		
		public function resetPtr()
		{
			$this->statement->reset();
		}
		
		public function printSQL()
		{
			$str = '';
			if ($this->getCriteria() instanceof B2DBCriteria)
			{
				$str .= $this->crit->getSQL();
				foreach ($this->crit->getValues() as $val)
				{
					if (is_object($val))
					{
						throw new B2DBException('waat');
					}
					if (is_int($val))
					{
						$val = $val;
					}
					elseif (is_null($val))
					{
						$val = 'null';
					}
					else
					{
						$val = '\'' . $val . '\'';
					}
					$str = substr_replace($str, $val, mb_strpos($str, '?'), 1);
				}
			}
			return $str;
		}
		
	}
