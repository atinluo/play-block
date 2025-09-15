<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class Play_Block_Likes_Table extends \BerlinDB\Database\Table {

	/**
	 * Table name, without the global table prefix.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $name = 'play_block_likes';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $db_version_key = 'play_block_likes_version';

	/**
	 * Optional description.
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	public $description = 'Play Block Likes';

	/**
	 * Database version.
	 *
	 * @since 1.0.0
	 * @var   mixed
	 */
	protected $version = 202410013;

	/**
	 * Key => value array of versions => methods.
	 *
	 * @since 1.0.0
	 * @var   array
	 */
	protected $upgrades = array(
		'202410013' => 202410013,
	);

	/**
	 * Setup this database table.
	 *
	 * @since 1.0.0
	 */
	protected function set_schema() {
		$this->schema = "id bigint(20) UNSIGNED NOT NULL auto_increment,
			object_id bigint(20) UNSIGNED NOT NULL default '0',
			object_type varchar(20) NOT NULL default '',
			user_id bigint(20) UNSIGNED NOT NULL DEFAULT '0',
			action varchar(20) NOT NULL default 'like',
			description longtext NOT NULL default '',
			date_created datetime NOT NULL,
			PRIMARY KEY (id),
			KEY `object` (object_id,object_type),
			KEY `user` (user_id)";
	}

	/**
	 * Upgrade to version 202410011
	 * - Add the `description` varchar column
	 *
	 * @since 1.0.0
	 *
	 * @return boolean
	 */
	protected function __202410013() {

		// Look for column.
		$result = $this->column_exists( 'description' );

		// Maybe add column.
		if ( false === $result ) {
			$result = $this->get_db()->query( "
				ALTER TABLE {$this->table_name} ADD COLUMN `description` longtext default '' AFTER `action`;
			" );
		}

		// Return success/fail.
		return $this->is_success( $result );
	}
}
