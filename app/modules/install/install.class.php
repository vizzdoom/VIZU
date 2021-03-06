<?php

# ==================================================================================
#
#	VIZU CMS
#	Class: Install
#
# ==================================================================================

namespace modules\install;

class Install {

	private $_db; // Handle to database controller


	/**
	 * Constructor & dependancy injection
	 */

	public function __construct($db) {
		// Check if variable passed to this class is database controller
		if ($db && is_object($db) && is_a($db, 'libs\Database')) $this->_db = $db;
		else \libs\Core::error('Variable passed to class "Install" is not correct "Database" object', __FILE__, __LINE__, debug_backtrace());
	}


	/**
	 * Ckeck if required database tables exists
	 */

	public function check_db_tables() {
		$table_fields = $this->_db->query('SELECT 1 FROM `fields` LIMIT 1', true);
		$table_users  = $this->_db->query('SELECT 1 FROM `users` LIMIT 1', true);
		return ($table_fields && $table_users);
	}


	/**
	 * Ckeck if there are any users in database
	 */

	public function check_db_users() {
		$result = $this->_db->query('SELECT `id` FROM `users`', true);
		return (count($this->_db->fetch($result)) > 0) ? true : false;
	}
}