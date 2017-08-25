<?php

/**
 * CodeIgniter DataTables Library
 *
 *
 * @author Fayaz K <fayazkyusufzai@gmail.com>
 */
class DataTables
{
	private static $VALID_MATCH_TYPES = [ 'before', 'after', 'both', 'none' ];

	/**
	 * @var \CI_Controller
	 */
	private $ci;

	/**
	 * @var \CI_Input
	 */
	private $input;

	/**
	 * @var string
	 */
	private $primary_column;

	/**
	 * @var bool|callable
	 */
	private $preResultFunc = false;

	/**
	 * @var array
	 */
	private $matchType = [];

	/**
	 * @var bool
	 */
	private $protectIdentifiers = false;

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var array
	 */
	private $aliased_columns = [];

	private $mutators = [];

	private $output_array = [];

	private $columns_format = [];

	private $joins = [];

	private $wheres = [];


	/**
	 * DataTables constructor.
	 */
	public function __construct()
	{
		$this->ci    =& get_instance();
		$this->input = $this->ci->input;
	}

	/**
	 * @param string $table
	 * @param null   $primary_column
	 *
	 * @return $this
	 */
	public function init( $table, $primary_column = null )
	{
		$this->table          = $table;
		$this->primary_column = $primary_column;

		return $this;
	}


	/**
	 * @param boolean $boolProtect
	 *
	 * @return $this
	 */
	public function setProtectIdentifiers( $boolProtect )
	{
		$this->protectIdentifiers = $boolProtect;

		return $this;
	}

	/**
	 * @param $func
	 *
	 * @return $this
	 * @throws \RuntimeException
	 */
	public function setPreResultCallback( $func )
	{
		if ( is_object( $func ) === false || ( $func instanceof Closure ) === false ) {
			throw new RuntimeException( 'Expected Anonymous Function Parameter Not Received' );
		}

		$this->preResultFunc = $func;

		return $this;
	}


	/**
	 * Sets the wildcard matching to be a done on a specific column in the search
	 *
	 * @param $col
	 * @param $type
	 *
	 * @return \DataTables
	 * @throws \Exception
	 */
	public function setColumnSearchType( $col, $type )
	{
		$type = strtolower( trim( $type ) );
		//make sure we have a valid type
		if ( in_array( $type, self:: $VALID_MATCH_TYPES, true ) === false ) {
			throw new RuntimeException( '[' . $type . '] is not a valid type.  Must Use: ' . implode( ', ',
					self::$VALID_MATCH_TYPES ) );
		}

		$this->matchType[ $col ] = $type;


		return $this;
	}

	/**
	 * @param bool $debug
	 *
	 * @return $this
	 * @internal param array $formats
	 */
	public function make( $debug = false )
	{

		$start = (int) $this->input->post_get( 'start' );
		$limit = (int) $this->input->post_get( 'length' );


		$output_array                    = [];
		$output_array['start']           = $start;
		$output_array['limit']           = $limit;
		$output_array['draw']            = (int) $this->input->post_get( 'draw' );
		$output_array['recordsTotal']    = 0;
		$output_array['recordsFiltered'] = 0;
		$output_array['data']            = [];

		//query the data for the records being returned
		$selectArray    = [];
		$customCols     = [];
		$columnIdxArray = [];

		foreach ( $this->input->post_get( 'columns' ) as $c ) {
			$columnIdxArray[] = $c['data'];
			if ( substr( $c['data'], 0, 1 ) === '$' ) {
				//indicates a column specified in the appendToSelectStr()
				$customCols[] = $c['data'];
				continue;
			}
			$selectArray[] = $c['data'];
		}
		if ( $this->primary_column !== null && in_array( $this->primary_column, $selectArray ) === false ) {
			$selectArray[] = $this->primary_column;
		}

		//put the select string together
		$sqlSelectStr = implode( ', ', $selectArray );
		//put the Aliased Columns
		foreach ( $this->aliased_columns as $alias => $column ) {
			$sqlSelectStr .= ', ' . $column . ' AS ' . $alias;
		}

		foreach ( $this->input->post_get( 'order' ) as $o ) {
			if ( $o['column'] !== '' ) {
				$colName = $columnIdxArray[ $o['column'] ];
				//handle custom sql expressions/subselects
				if ( substr( $colName, 0, 2 ) === '$.' ) {
					$aliasKey = substr( $colName, 2 );
					$colName  = $aliasKey;
				}
				$this->ci->db->order_by( $colName, $o['dir'] );
			}
		}

		$this->ci->db->select( $sqlSelectStr, $this->protectIdentifiers );
		$this->sqlJoinsAndWhere();
		$this->ci->db->limit( $limit, $start );
		$query = $this->ci->db->get();

		$output_array = [];

		if ( ! $query ) {
			$output_array['errorMessage'] = $this->ci->db->_error_message();

			return $output_array;
		}

		if ( $debug === true ) {
			$output_array['debug_sql'] = $this->ci->db->last_query();
		}

		//process the results and create the JSON objects
		$dataArray    = [];
		$allColsArray = array_merge( $selectArray, $customCols );
		foreach ( $query->result() as $row ) {
			$colObj = [];
			//loop rows returned by the query
			foreach ( $allColsArray as $c ) {
				if ( trim( $c ) === '' ) {
					continue;
				}

				$propParts = explode( '.', $c );

				$prop = trim( end( $propParts ) );

				//loop columns in each row that the grid has requested
				if ( count( $propParts ) > 1 ) {
					//nest the objects correctly in the json if the column name includes
					//the table alias
					$nestedObj = [];
					if ( isset( $colObj[ $propParts[0] ] ) ) {
						//check if we alraedy have a object for this alias in the array
						$nestedObj = $colObj[ $propParts[0] ];
					}

					// Apply mutation
					$field = $row->$prop;
					if ( isset( $this->mutators[ $prop ] ) ) {
						$callable                   = $this->mutators[ $prop ];
						$field = $callable( $row );
					}
					$nestedObj[ $propParts[1] ] = $this->formatValue( $prop, $field );
					$colObj[ $propParts[0] ]    = $nestedObj;
				} else {
					// Apply mutation
					$field = $row->$prop;
					if ( isset( $this->mutators[ $prop ] ) ) {
						$callable     = $this->mutators[ $prop ];
						$field = $callable( $row );
					}
					$colObj[ $c ] = $this->formatValue( $prop, $field );
				}
			}

			if ( $this->primary_column !== null ) {
				$tmpRowIdSegments   = explode( '.', $this->primary_column );
				$idCol              = trim( end( $tmpRowIdSegments ) );
				$colObj['DT_RowId'] = $row->$idCol;
			}
			$dataArray[] = $colObj;
		}


		$this->sqlJoinsAndWhere();
		$totalRecords = $this->ci->db->count_all_results();


		$output_array['start']           = $start;
		$output_array['limit']           = $limit;
		$output_array['draw']            = (int) $this->input->post_get( 'draw' );
		$output_array['recordsTotal']    = $totalRecords;
		$output_array['recordsFiltered'] = $totalRecords;
		$output_array['data']            = $dataArray;

		if ( $this->preResultFunc !== false ) {
			$func = $this->preResultFunc;
			$func( $output_array );
		}

		$this->output_array = $output_array;

		return $this;
	}

	/**
	 * @return $this
	 */
	private function sqlJoinsAndWhere()
	{
		$debug = '';
		// this is protected in CI 3 and can no longer be turned off. must be turned off in the config
		// $this -> CI -> db-> _protect_identifiers = FALSE;
		$this->ci->db->from( $this->table );

		// Add Joins
		if ( ! empty( $this->joins ) ) {
			foreach ( $this->joins as $join ) {
				$this->ci->db->join( $join['table'], $join['condition'], $join['type'], $join['escape'] );
			}
		}


		$searchableColumns = [];
		foreach ( $this->input->post_get( 'columns' ) as $c ) {

			$colName = $c['data'];

			if ( substr( $colName, 0, 2 ) === '$.' ) {
				$aliasKey = substr( $colName, 2 );
				if ( empty( $this->aliased_columns[ $aliasKey ] ) ) {
					throw new RuntimeException( 'Alias[' . $aliasKey . '] Could Not Be Found In Aliased Columns' );
				}

				$colName = $this->aliased_columns[ $aliasKey ];
			}

			if ( $c['searchable'] !== 'false' ) {
				$searchableColumns[] = $colName;
			}

			if ( $c['search']['value'] !== '' ) {
				$searchType = $this->getColumnSearchType( $colName );

				$this->ci->db->like( $colName, $c['search']['value'], $searchType, $this->protectIdentifiers );
			}
		}


		// put together a global search if specified
		$global_search = $this->input->post_get( 'search' );
		if ( $global_search['value'] !== '' ) {
			$search_value = $global_search['value'];
			$like_sql     = [];
			foreach ( $searchableColumns as $c ) {
				$searchType = $this->getColumnSearchType( $c );;
				switch ( $searchType ) {
					case 'before':
						$like_sql[] = $c . " LIKE '%" . $this->ci->db->escape_like_str( $search_value ) . "'";
						break;
					case 'after':
						$like_sql[] = $c . " LIKE '" . $this->ci->db->escape_like_str( $search_value ) . "%'";
						break;
					case 'none':
						$like_sql[] = $c . " LIKE '" . $this->ci->db->escape_like_str( $search_value ) . "'";
						break;
					default:
						$like_sql[] = $c . " LIKE '%" . $this->ci->db->escape_like_str( $search_value ) . "%'";
						break;
				}
			}

			$this->ci->db->where( '(' . implode( ' OR ', $like_sql ) . ')' );
		}

		// Add Custom Where
		if ( ! empty( $this->wheres ) ) {
			foreach ( $this->wheres as $where ) {
				switch ( $where['type'] ) {
					case 'where':
						$args = $where['args'];
						$this->ci->db->where( $args['key'], $args['value'], $args['escape'] );
						break;
				}
			}
		}


		return $this;
	}

	/**
	 * @param $col
	 *
	 * @return mixed|string
	 */
	public function getColumnSearchType( $col )
	{
		return isset( $this->matchType[ $col ] ) ? $this->matchType[ $col ] : 'both';
	}


	/**
	 * @param $column
	 * @param $value
	 *
	 * @return string
	 * @internal param $formats
	 */
	private function formatValue( $column, $value )
	{
		if ( ! isset( $this->columns_format[ $column ] ) ) {
			return $value;
		}
		$format_data = $this->columns_format[ $column ];
		$format_args = $format_data['args'];
		switch ( $format_data['type'] ) {
			case 'currency':
				$value = number_format( $value, 2 );
				$value = $format_args['prefix'] . $value . $format_args['suffix'];
				break;
			case 'date':
				$value = date( $format_args['format'], strtotime( $value ) );
				break;
		}

		return $value;
	}

	/**
	 * @param $alias
	 * @param $column
	 *
	 * @return $this
	 */
	public function setColumnAlias( $alias, $column )
	{
		$this->aliased_columns[ $alias ] = $column;

		return $this;
	}

	/**
	 * @param $columns
	 *
	 * @return $this
	 */
	public function addAliasedColumns( $columns )
	{
		$this->aliased_columns = array_merge( $this->aliased_columns, $columns );

		return $this;
	}

	/**
	 * @param $column
	 * @param $callback
	 *
	 * @return $this
	 */
	public function editColumn( $column, $callback )
	{
		if ( is_object( $callback ) === false || ( $callback instanceof Closure ) === false ) {
			throw new RuntimeException( 'Expected Anonymous Function Parameter Not Received' );
		}

		$this->mutators[ $column ] = $callback;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getOutputArray()
	{
		return $this->output_array;
	}

	/**
	 * @return string
	 */
	public function getOutputJson()
	{
		return json_encode( $this->output_array );
	}

	/**
	 *
	 */
	public function output()
	{
		$json = $this->getOutputJson();
		$this->ci->output->set_header( 'Pragma: no-cache' );
		$this->ci->output->set_header( 'Cache-Control: no-store, no-cache' );
		$this->ci->output->set_content_type( 'application/json' )->set_output( $json );
	}

	/**
	 * @param        $column
	 * @param string $prefix
	 * @param string $suffix
	 *
	 * @return $this
	 */
	public function formatColumnAsCurrency( $column, $prefix = '$', $suffix = '' )
	{
		$this->columns_format[ $column ] = [
			'type' => 'currency',
			'args' => [
				'prefix' => $prefix,
				'suffix' => $suffix,
			],
		];

		return $this;
	}

	/**
	 * @param        $column
	 * @param string $format
	 *
	 * @return $this
	 */
	public function formatColumnAsDate( $column, $format = 'm/d/Y' )
	{
		$this->columns_format[ $column ] = [
			'type' => 'date',
			'args' => [
				'format' => $format,
			],
		];

		return $this;
	}

	/**
	 * WHERE
	 *
	 * Generates the WHERE portion of the query.
	 * Separates multiple calls with 'AND'.
	 *
	 * @param    mixed
	 * @param    mixed
	 * @param    bool
	 *
	 * @return    CI_DB_query_builder
	 */
	public function where( $key, $value = null, $escape = null )
	{
		$this->wheres[] = [
			'type' => 'where',
			'args' => [
				'key'    => $key,
				'value'  => $value,
				'escape' => $escape,
			],
		];

		return $this;
	}

	public function join( $table, $cond, $type = '', $escape = null )
	{
		$this->joins[] = [
			'table'     => $table,
			'condition' => $cond,
			'type'      => $type,
			'escape'    => $escape,
		];
	}
}
