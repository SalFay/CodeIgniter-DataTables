<?php

/**
 * Codeigniter Datatable library
 *
 *
 * @author Paul Zepernick
 */
class DataTables
{

    private static $VALID_MATCH_TYPES = ['before', 'after', 'both', 'none'];

    private $model;

    private $ci;

    private $input;

    private $primary_column;

    private $preResultFunc = false;

    // assoc. array.  key is column name being passed from the DataTables data property and value is before, after, both, none
    private $matchType = [];

    private $protectIdentifiers = false;
    private $table;
    private $aliased_columns = [];


    /**
     * @params
     *        Associative array.  Expecting key "model" and the value name of the model to load
     */
    public function __construct()
    {
        $this->ci =& get_instance();
        $this->input = $this->ci->input;
    }

    public function init($table, $primary_column = null)
    {
        $this->table = $table;
        $this->primary_column = $primary_column;
    }


    /**
     * Turn on/off protect identifiers from the Query Builder
     *
     * @param $boolProtect should database identifiers be protected?
     *
     * @return $this
     */
    public function setProtectIdentifiers($boolProtect)
    {
        $this->protectIdentifiers = $boolProtect;

        return $this;
    }

    /**
     * Register a function that will fire after the JSON object is put together
     * in the library, but before sending it to the browser.  The function should accept 1 parameter
     * for the JSON object which is stored as associated array.
     *
     * IMPORTANT: Make sure to add a & in front of the parameter to get a reference of the Array,otherwise
     * your changes will not be picked up by the library
     *
     *        function(&$json) {
     *            //do some work and add to the json if you wish.
     *        }
     *
     * @throws \Exception
     */
    public function setPreResultCallback($func)
    {
        if (is_object($func) === false || ($func instanceof Closure) === false) {
            throw new RuntimeException('Expected Anonymous Function Parameter Not Received');
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
    public function setColumnSearchType($col, $type)
    {
        $type = strtolower(trim($type));
        //make sure we have a valid type
        if (in_array($type, self:: $VALID_MATCH_TYPES, true) === false) {
            throw new RuntimeException('[' . $type . '] is not a valid type.  Must Use: ' . implode(', ',
                    self::$VALID_MATCH_TYPES));
        }

        $this->matchType[ $col ] = $type;


        return $this;
    }

    /**
     * @param array $formats
     * @param bool  $debug
     *
     * @return array
     * @throws \Exception
     * @internal param $formats Associative array.*                Associative array.
     *                Key is column name
     *                Value format: percent, currency, date, boolean
     *
     */
    public function make($formats = [], $debug = false)
    {

        $start = (int)$this->input->post_get('start');
        $limit = (int)$this->input->post_get('length');


        $output_array = [];
        $output_array['start'] = $start;
        $output_array['limit'] = $limit;
        $output_array['draw'] = (int)$this->input->post_get('draw');
        $output_array['recordsTotal'] = 0;
        $output_array['recordsFiltered'] = 0;
        $output_array['data'] = [];

        //query the data for the records being returned
        $selectArray = [];
        $customCols = [];
        $columnIdxArray = [];

        foreach ($this->input->post_get('columns') as $c) {
            $columnIdxArray[] = $c['data'];
            if (substr($c['data'], 0, 1) === '$') {
                //indicates a column specified in the appendToSelectStr()
                $customCols[] = $c['data'];
                continue;
            }
            $selectArray[] = $c['data'];
        }
        if ($this->primary_column !== null && in_array($this->primary_column, $selectArray) === false) {
            $selectArray[] = $this->primary_column;
        }

        //put the select string together
        $sqlSelectStr = implode(', ', $selectArray);
        //put the Aliased Columns
        foreach ($this->aliased_columns as $alias => $column) {
            $sqlSelectStr .= ', ' . $column . ' AS ' . $alias;
        }

        foreach ($this->input->post_get('order') as $o) {
            if ($o['column'] !== '') {
                $colName = $columnIdxArray[ $o['column'] ];
                //handle custom sql expressions/subselects
                if (substr($colName, 0, 2) === '$.') {
                    $aliasKey = substr($colName, 2);
                    $colName = $aliasKey;
                }
                $this->ci->db->order_by($colName, $o['dir']);
            }
        }

        $this->ci->db->select($sqlSelectStr, $this->protectIdentifiers);
        $this->sqlJoinsAndWhere();
        $this->ci->db->limit($limit, $start);
        $query = $this->ci->db->get();

        $output_array = [];

        if (!$query) {
            $output_array['errorMessage'] = $this->ci->db->_error_message();

            return $output_array;
        }

        if ($debug === true) {
            $output_array['debug_sql'] = $this->ci->db->last_query();
        }

        //process the results and create the JSON objects
        $dataArray = [];
        $allColsArray = array_merge($selectArray, $customCols);
        foreach ($query->result() as $row) {
            $colObj = [];
            //loop rows returned by the query
            foreach ($allColsArray as $c) {
                if (trim($c) === '') {
                    continue;
                }

                $propParts = explode('.', $c);

                $prop = trim(end($propParts));
                //loop columns in each row that the grid has requested
                if (count($propParts) > 1) {
                    //nest the objects correctly in the json if the column name includes
                    //the table alias
                    $nestedObj = [];
                    if (isset($colObj[ $propParts[0] ])) {
                        //check if we alraedy have a object for this alias in the array
                        $nestedObj = $colObj[ $propParts[0] ];
                    }


                    $nestedObj[ $propParts[1] ] = $this->formatValue($formats, $prop, $row->$prop);
                    $colObj[ $propParts[0] ] = $nestedObj;
                } else {
                    $colObj[ $c ] = $this->formatValue($formats, $prop, $row->$prop);
                }
            }

            if ($this->primary_column !== null) {
                $tmpRowIdSegments = explode('.', $this->primary_column);
                $idCol = trim(end($tmpRowIdSegments));
                $colObj['DT_RowId'] = $row->$idCol;
            }
            $dataArray[] = $colObj;
        }


        $this->sqlJoinsAndWhere();
        $totalRecords = $this->ci->db->count_all_results();


        $output_array['start'] = $start;
        $output_array['limit'] = $limit;
        $output_array['draw'] = (int)$this->input->post_get('draw');
        $output_array['recordsTotal'] = $totalRecords;
        $output_array['recordsFiltered'] = $totalRecords;
        $output_array['data'] = $dataArray;
        //$jsonArry['debug'] = $whereDebug;

        if ($this->preResultFunc !== false) {
            $func = $this->preResultFunc;
            $func($output_array);
        }

        return $output_array;

    }

    private function sqlJoinsAndWhere()
    {
        $debug = '';
        // this is protected in CI 3 and can no longer be turned off. must be turned off in the config
        // $this -> CI -> db-> _protect_identifiers = FALSE;
        $this->ci->db->from($this->table);


        $searchableColumns = [];
        foreach ($this->input->post_get('columns') as $c) {

            $colName = $c['data'];

            if (substr($colName, 0, 2) === '$.') {
                $aliasKey = substr($colName, 2);
                if (empty($this->aliased_columns[ $aliasKey ])) {
                    throw new RuntimeException('Alias[' . $aliasKey . '] Could Not Be Found In Aliased Columns');
                }

                $colName = $this->aliased_columns[ $aliasKey ];
            }

            if ($c['searchable'] !== 'false') {
                $searchableColumns[] = $colName;
            }

            if ($c['search']['value'] !== '') {
                $searchType = $this->getColumnSearchType($colName);
                //log_message('info', 'colname[' . $colName . '] searchtype[' . $searchType . ']');
                //handle custom sql expressions/subselects

                $debug .= 'col[' . $c['data'] . '] value[' . $c['search']['value'] . '] ' . PHP_EOL;
                //	log_message('info', 'colname[' . $colName . '] searchtype[' . $searchType . ']');
                $this->ci->db->like($colName, $c['search']['value'], $searchType, $this->protectIdentifiers);
            }
        }


        // put together a global search if specified
        $globSearch = $this->input->post_get('search');
        if ($globSearch['value'] !== '') {
            $gSearchVal = $globSearch['value'];
            $sqlOr = '';
            $op = '';
            foreach ($searchableColumns as $c) {
                $sqlOr .= $op . $c . ' LIKE \'' . $this->ci->db->escape_like_str($gSearchVal) . '%\'';
                $op = ' OR ';
            }

            $this->ci->db->where('(' . $sqlOr . ')');
        }


        /*        //append a static where clause to what the user has filtered, if the model tells us to do so
                $wArray = $this->model->whereClauseArray();
                if (is_null($wArray) === false && is_array($wArray) === true && count($wArray) > 0) {
                    $this->ci->db->where($wArray, $this->protectIdentifiers);
                }*/

        return $debug;
    }

    /**
     * Get the current search type for a column
     *
     * @param col
     *        column sepcified in the DataTables "data" property
     *
     * @return search type string
     */
    public function getColumnSearchType($col)
    {
        //	log_message('info', 'getColumnSearchType() ' . var_export($this -> matchType, TRUE));
        return isset($this->matchType[ $col ]) ? $this->matchType[ $col ] : 'after';
    }



    //specify the joins and where clause for the Active Record. This code is common to
    //fetch the data and get a total record count

    private function formatValue($formats, $column, $value)
    {
        if (isset($formats[ $column ]) === false || trim($value) == '') {
            return $value;
        }

        switch ($formats[ $column ]) {
            case 'date' :
                $dtFormats = ['Y-m-d H:i:s', 'Y-m-d'];
                $dt = null;
                //try to parse the date as 2 different formats
                foreach ($dtFormats as $f) {
                    $dt = DateTime::createFromFormat($f, $value);
                    if ($dt !== false) {
                        break;
                    }
                }
                if ($dt === false) {
                    //neither pattern could parse the date
                    throw new RuntimeException('Could Not Parse To Date For Formatting [' . $value . ']');
                }

                return $dt->format('m/d/Y');
            case 'percent' :
                ///$formatter = new \NumberFormatter('en_US', \NumberFormatter::PERCENT);
                //return $formatter -> format(floatval($value) * .01);
                return $value . '%';
            case 'currency' :
                return '$' . number_format((float)$value, 2);
            case 'boolean' :
                $b = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                return $b ? 'Yes' : 'No';
        }

        return $value;
    }

    public function setColumnAlias($alias, $column)
    {
        $this->aliased_columns[ $alias ] = $column;

        return $this;
    }

    public function addAliasedColumns($columns)
    {
        $this->aliased_columns = array_merge($this->aliased_columns, $columns);

        return $this;
    }
}
