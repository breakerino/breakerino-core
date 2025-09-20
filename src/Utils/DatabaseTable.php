<?php

/**
 * ------------------------------------------------------------------------
 * Breakerino Core > Utils > DatabaseTable
 * ------------------------------------------------------------------------
 * @version     1.0.1
 * @author      Breakerino
 * @created     28/11/2022
 * @updated     04/03/2024
 * ------------------------------------------------------------------------
 */

// TODO: Remake it as an universal abstract class and create a WP adapter for it.

namespace Breakerino\Core\Utils;

use \Breakerino\Core\Exceptions\Generic as GenericException;

class DatabaseTable {
	/**
	 * ------------------------------------------------------------------------
	 * %1$s = table name
	 * %2$s = columns
	 * %3$s = charset collate
	 * ------------------------------------------------------------------------
	 * @var string
	 */
	private const CREATE_DATABASE_TABLE_SQL_FORMAT = 'CREATE TABLE IF NOT EXISTS `%1$s` (%2$s) %3$s;';

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	public const BASE_TABLE_COLUMN_SCHEMA = [
		'name' => '',
		'type' => '',
		'length' => null,
		'unsigned' => true,
		'auto_increment' => false,
		'null' => false,
		'default' => null,
	];

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	public const BASE_TABLE_INDEX_SCHEMA = [
		'type' => '',
		'column' => ''
	];

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	public const BASE_TABLE_SCHEMA = [
		'name' => '',
		'indexes' => [],
		'columns' => []
	];

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	private const DATABASE_TABLE_SUPPORTED_COLUMN_TYPES = ['char', 'bool', 'varchar', 'text', 'longtext', 'tinytext', 'decimal', 'int', 'bigint', 'smallint', 'mediumint', 'float', 'double'];

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	private const DATABASE_TABLE_SUPPORT_INDEX_TYPES = ['primary', 'unique', 'index'];

	/**
	 * ------------------------------------------------------------------------
	 * @var string
	 * ------------------------------------------------------------------------
	 */
	private const DATABASE_TABLE_COLUMN_SEPARATOR = ',';

	/**
	 * ------------------------------------------------------------------------
	 * @var string
	 * ------------------------------------------------------------------------
	 */
	private $tableName = '';

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	private $tableColumns = [];

	/**
	 * ------------------------------------------------------------------------
	 * @var array
	 * ------------------------------------------------------------------------
	 */
	private $tableIndexes = [];

	/**
	 * Constructor
	 *
	 * @param array $schema
	 */
	public function __construct(array $schema) {
		$this->set_props($schema);
	}

	/**
	 * Set props from schema
	 *
	 * @return void
	 */
	public function set_props(array $schema) {
		global $wpdb;

		$this->tableName = $wpdb->prefix  . $schema['name'];
		$this->tableColumns = $schema['columns'];
		$this->tableIndexes = $schema['indexes'];
	}

	/**
	 * Get table schema
	 *
	 * @return array
	 */
	public function get_schema() {
		return apply_filters('breakerino_database_table_get_schema', [
			'name' => $this->tableName,
			'indexes' => $this->tableIndexes,
			'columns' => $this->tableColumns
		]);
	}

	/**
	 * Get the SQL for altering the table structure
	 *
	 * @param array $schema
	 * @return string
	 */
	public function get_alter_table_sql(array $schema) {
		// TODO: To implement
		// Take the shcema and prepare sql to delete non-included columns, add new columns or change the existing ones
	}

	/**
	 * Get the SQL to create a table from a schema
	 *
	 * @param array $schema
	 * @return string
	 */
	public static function get_create_table_sql(array $tableSchema, bool $withPrefix = true) {
		global $wpdb;

		['name' => $tableName, 'columns' => $columns, 'indexes' => $indexes] = $tableSchema;

		$sql = sprintf(
			self::CREATE_DATABASE_TABLE_SQL_FORMAT,
			($withPrefix ? $wpdb->prefix : '') . $tableName,
			implode(
				self::DATABASE_TABLE_COLUMN_SEPARATOR,
				array_merge(
					array_map('self::get_column_sql', $columns),
					array_map(function ($indexSchema) use ($columns) {
						return self::get_index_sql($indexSchema, $columns);
					}, $indexes)
				)
			),
			$wpdb->get_charset_collate()
		);

		return $sql;
	}

	/**
	 * Get the SQL to create a column from a schema
	 *
	 * @param array $schema
	 * @return void
	 */
	public static function get_column_sql(array $columnSchema) {
		$columnSchema = \wp_parse_args($columnSchema, self::BASE_TABLE_COLUMN_SCHEMA);

		if (!in_array($columnSchema['type'], self::DATABASE_TABLE_SUPPORTED_COLUMN_TYPES)) {
			throw new GenericException('Unknown column type: %s', [$columnSchema['type']], 'error', 'DatabaseHelper');
		}

		$sql = '';
		$sql .= sprintf('%1$s %2$s', $columnSchema['name'], strtoupper($columnSchema['type']));

		switch ($columnSchema['type']) {
			case 'decimal':
				if (is_array($columnSchema['length']) && is_numeric(array_sum($columnSchema['length']))) {
					$sql .= vsprintf('(%d, %d)', $columnSchema['length']);
					break;
				}
			default:
				if (is_numeric($columnSchema['length'])) {
					$sql .= sprintf('(%1$s)', $columnSchema['length']);
				}
		}

		if (!$columnSchema['unsigned']) {
			$sql .= ' SIGNED';
		}

		if ($columnSchema['auto_increment']) {
			$sql .= ' AUTO_INCREMENT';
		}

		if ($columnSchema['null']) {
			$sql .= ' NULL';
		}

		if (!is_null($columnSchema['default'])) {
			$sql .= sprintf(' DEFAULT %s', $columnSchema['default']);
		}

		return $sql;
	}

	/**
	 * Get the SQL to create a index from a schema
	 *
	 * @param array $schema
	 * @return void
	 */
	public static function get_index_sql(array $indexSchema, array $columnsSchema) {
		['type' => $type, 'column' => $column] = $indexSchema;

		if (!in_array($type, self::DATABASE_TABLE_SUPPORT_INDEX_TYPES)) {
			throw new GenericException('Unknown index type: %s', [$type], 'error', 'DatabaseTable');
		}

		if (!array_key_exists($column, $columnsSchema)) {
			throw new GenericException('Unknown column: %s (for index type %s)', [$type, $column], 'error', 'DatabaseTable');
		}

		$sql = sprintf('%1$s KEY(%2$s)', strtoupper($type), $column);
		return $sql;
	}

	/**
	 * Create table
	 *
	 * @return bool
	 */
	public function create() {
		global $wpdb;

		$schema = $this->get_schema();
		$sql = self::get_create_table_sql($schema, false);

		require_once \ABSPATH . '/wp-admin/includes/upgrade.php';
		\dbDelta($sql);

		if (!empty($wpdb->last_error)) {
			throw new GenericException('An error occured while creating table %s: %s', [$this->tableName, $wpdb->last_error], 'error');
		}

		return true;
	}

	/**
	 * Drop table
	 *
	 * @return bool
	 */
	public function drop() {
		// NOTE: Probably for testing purposes only (to avoid fuckups), not used in production
		global $wpdb;

		$wpdb->query('DROP TABLE IF EXISTS ' . $this->tableName);

		if (!empty($wpdb->last_error)) {
			throw new GenericException('An error occured while creating table %s: %s', [$this->tableName, $wpdb->last_error], 'error');
		}

		return true;
	}

	/**
	 * Alter table
	 *
	 * @return boolean
	 */
	public function alter() { //table
		// TODO: Implement
	}

	/**
	 * Check if table exists
	 *
	 * @return boolean
	 */
	public function exists() { // table
		global $wpdb;

		return $wpdb->get_var('SHOW TABLES LIKE "' . $this->tableName . '"') === $this->tableName;
	}

	/**
	 * Insert data
	 *
	 * @param array $data
	 * @return void
	 */
	public function insert(array $data) {
		global $wpdb;

		$result = $wpdb->insert($this->tableName, $data);

		if (!empty($wpdb->last_error)) {
			throw new GenericException('An error occured while inserting data into table %s: %s', [$this->tableName, $wpdb->last_error], 'error');
		}

		return $result;
	}

	/**
	 * Get rows
	 *
	 * @param array $where
	 * @param array $include
	 * @return mixed
	 */
	public function query(array $queryArgs, bool $single = false) {
		global $wpdb;

		$queryArgs = \wp_parse_args($queryArgs, [
			'select' => [],
			'where' => [],
			'include' => [],
			'limit' => null,
			'offset' => null,
			'order' => null,
			'orderby' => null,
		]);

		$selectClause = !empty($queryArgs['select']) ? array_map(function ($column) {
			return '`' . $column . '`';
		}, $queryArgs['select']) : ['*'];

		$sql = sprintf(
			'SELECT %s FROM `%s`',
			implode(', ', $selectClause),
			$this->tableName
		);

		if (!empty($queryArgs['where'])) {
			$sql .= ' WHERE ' . implode(' AND ', array_map(function ($key, $value) {
				return sprintf('`%s` = \'%s\'', $key, $value);
			}, array_keys($queryArgs['where']), array_values($queryArgs['where'])));
		}

		if (($single || (!is_null($queryArgs['limit']) && is_numeric($queryArgs['limit'])))) {
			$sql .= ' LIMIT ' . ($single ? 1 : $queryArgs['limit']);
		}

		if ((!is_null($queryArgs['offset']) && is_numeric($queryArgs['offset']))) {
			$sql .= ' OFFSET ' . $queryArgs['offset'];
		}

		if (!is_null($queryArgs['order'])) {
			$sql .= ' ORDER BY ' . $queryArgs['order'];
		}

		$results = $wpdb->get_results($sql, ARRAY_A);

		if (!empty($wpdb->last_error)) {
			throw new GenericException('An error occured while getting data from table %s: %s', [$this->tableName, $wpdb->last_error], 'error');
		}

		return $single ? (reset($results) ?? null) : ($results ?? []);
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	public function truncate() {
		global $wpdb;

		$wpdb->query('TRUNCATE TABLE ' . $this->tableName);

		if (!empty($wpdb->last_error)) {
			throw new GenericException('An error occured while truncating table %s: %s', [$this->tableName, $wpdb->last_error], 'error');
		}
	}

	/**
	 * Update row
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function update(array $data, array $where) {
		// TODO: Implement, probably use wpdb->update()
		// Data would be an array of rows, each containing at least the row primary key and field to update
		// Dont't allow to edit primary key
		global $wpdb;

		$result = $wpdb->update($this->tableName, $data, $where);

		if (!empty($wpdb->last_error)) {
			throw new GenericException('An error occured while inserting data into table %s: %s', [$this->tableName, $wpdb->last_error], 'error');
		}

		return $result;
	}

	/**
	 * Delete row
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function delete(array $data) {
	}
}