<?php

	/**
	 * B2DB Transaction Base class
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 2.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package b2db
	 * @subpackage core
	 */

	/**
	 * B2DB Transaction Base class
	 *
	 * @package b2db
	 * @subpackage core
	 */
	class B2DBTransaction
	{
		protected $state = 0;
		
		const DB_TRANSACTION_UNSTARTED = 0;
		const DB_TRANSACTION_STARTED = 1;
		const DB_TRANSACTION_COMMITED = 2;
		const DB_TRANSACTION_ROLLEDBACK = 3;
		const DB_TRANSACTION_ENDED = 4;
		
		/**
		 * B2DBTransaction constructor
		 *
		 * @return B2DBTransaction
		 */
		public function __construct()
		{
			if (\b2db\Core::getDBLink()->beginTransaction())
			{
				$this->state = self::DB_TRANSACTION_STARTED;
				\b2db\Core::setTransaction(true);
			}
			return $this;
		}
		
		public function __destruct()
		{
			if ($this->state == self::DB_TRANSACTION_STARTED)
			{
				echo 'forcing transaction rollback';
			}
		}
		
		public function end()
		{
			if ($this->state == self::DB_TRANSACTION_COMMITED)
			{
				$this->state = self::DB_TRANSACTION_ENDED;
				\b2db\Core::setTransaction(false);
			}
		}
		
		public function commitAndEnd()
		{
			$this->commit();
			$this->end();
		}
		
		public function commit()
		{
			if ($this->state == self::DB_TRANSACTION_STARTED)
			{
				if (\b2db\Core::getDBLink()->commit())
				{
					$this->state = self::DB_TRANSACTION_COMMITED;
					\b2db\Core::setTransaction(false);
				}
				else
				{
					throw new B2DBException('Error committing transaction: ' . \b2db\Core::getDBLink()->error);
				}
			}
			else
			{
				throw new B2DBException('There is no active transaction');
			}
		}
		
		public function rollback()
		{
			if ($this->state == self::DB_TRANSACTION_STARTED)
			{
				if (\b2db\Core::getDBLink()->rollback())
				{
					$this->state = self::DB_TRANSACTION_ROLLEDBACK;
					\b2db\Core::setTransaction(false);
				}
				else
				{
					throw new B2DBException('Error rolling back transaction: ' . \b2db\Core::getDBLink()->error);
				}
			}
			else
			{
				throw new B2DBException('There is no active transaction');
			}
		}
		
	}
