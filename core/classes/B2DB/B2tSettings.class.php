<?php

	/**
	 * Settings table
	 *
	 * @author Daniel Andre Eikeland <zegenie@zegeniestudios.net>
	 * @version 2.0
	 * @license http://www.opensource.org/licenses/mozilla1.1.php Mozilla Public License 1.1 (MPL 1.1)
	 * @package thebuggenie
	 * @subpackage tables
	 */

	/**
	 * Settings table
	 *
	 * @package thebuggenie
	 * @subpackage tables
	 */
	class B2tSettings extends B2DBTable 
	{
		const B2DBNAME = 'settings';
		const ID = 'settings.id';
		const SCOPE = 'settings.scope';
		const NAME = 'settings.name';
		const MODULE = 'settings.module';
		const VALUE = 'settings.value';
		const UID = 'settings.uid';

		public function __construct()
		{
			parent::__construct(self::B2DBNAME, self::ID);
			
			parent::_addVarchar(self::NAME, 45);
			parent::_addVarchar(self::MODULE, 45);
			parent::_addVarchar(self::VALUE, 200);
			parent::_addInteger(self::UID, 10);
			parent::_addForeignKeyColumn(self::SCOPE, B2DB::getTable('B2tScopes'), B2tScopes::ID);
		}
		
		public function getDefaultScope()
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::SCOPE, 0);
			$crit->addWhere(self::NAME, 'defaultscope');
			$row = $this->doSelectOne($crit);
			return $row;
		}

		public function getSettingsForEnabledScope($scope)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(B2tScopes::ENABLED, true);
			$crit->addWhere(self::SCOPE, $scope);
			$res = $this->doSelect($crit);
			return $res;
		}

		public function saveSetting($name, $module, $value, $uid, $scope)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::NAME, $name);
			$crit->addWhere(self::MODULE, $module);
			$crit->addWhere(self::UID, $uid);
			$crit->addWhere(self::SCOPE, $scope);
			$res = $this->doSelectOne($crit);

			if ($res instanceof B2DBRow)
			{
				$theID = $res->get(self::ID);
				$crit2 = new B2DBCriteria();
				$crit2->addWhere(self::NAME, $name);
				$crit2->addWhere(self::MODULE, $module);
				$crit2->addWhere(self::UID, $uid);
				$crit2->addWhere(self::SCOPE, $scope);
				$crit2->addWhere(self::ID, $theID, B2DBCriteria::DB_NOT_EQUALS);
				$res2 = $this->doDelete($crit2);
				
				$crit = $this->getCriteria();
				$crit->addUpdate(self::NAME, $name);
				$crit->addUpdate(self::MODULE, $module);
				$crit->addUpdate(self::UID, $uid);
				$crit->addUpdate(self::VALUE, $value);
				$this->doUpdateById($crit, $theID);
			}
			else
			{
				$crit = $this->getCriteria();
				$crit->addInsert(self::NAME, $name);
				$crit->addInsert(self::MODULE, $module);
				$crit->addInsert(self::VALUE, $value);
				$crit->addInsert(self::SCOPE, $scope);
				$crit->addInsert(self::UID, $uid);
				$this->doInsert($crit);
			}
		}

		public function deleteModuleSettings($module_name, $scope)
		{
			$crit = $this->getCriteria();
			$crit->addWhere(self::MODULE, $module_name);
			$crit->addWhere(self::SCOPE, $scope);
			$this->doDelete($crit);
		}

		public function loadFixtures($scope)
		{
			$i18n = BUGScontext::getI18n();

			$settings = array();
			$settings['theme_name'] = 'oxygen';
			$settings['requirelogin'] = 0;
			$settings['defaultisguest'] = 1;
			$settings['showloginbox'] = 1;
			$settings['allowreg'] = 1;
			$settings['returnfromlogin'] = 'dashboard';
			$settings['returnfromlogout'] = 'home';
			$settings['onlinestate'] = 1;
			$settings['offlinestate'] = 2;
			$settings['awaystate'] = 8;
			$settings['showprojectsoverview'] = 1;
			$settings['userthemes'] = 0;
			$settings['b2_name'] = 'The Bug Genie';
			$settings['b2_tagline'] = '<b>Friendly</b> issue tracking and project management';
			$settings['highlight_default_lang'] = 'html4strict';
			$settings['highlight_default_numbering'] = '3';
			$settings['highlight_default_interval'] = '10';

			foreach ($settings as $settings_name => $settings_val)
			{
				$this->saveSetting($settings_name, 'core', $settings_val, 0, $scope);
			}
		}
		
	}
