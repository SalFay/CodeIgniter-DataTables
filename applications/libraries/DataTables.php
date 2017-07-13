<?php

class DataTables
{

    private static $VALID_MATCH_TYPES = array('before', 'after', 'both', 'none');


    /**
     * @var \CI_Controller
     */
    private $ci;

    private $primary_column;

    private $preResultFunc = false;

    // assoc. array.  key is column name being passed from the DataTables data property and value is before, after, both, none
    private $matchType = array();

    private $protectIdentifiers = false;

    private $table;

    /**
     * @var \CI_DB_query_builder
     */
    private $db;

    private $count_query;

    /**
     * @param  string              $db_object
     * @param \CI_DB_query_builder $table
     * @param null                 $primary_column
     */
    public function __construct($db_object = null, $table = null, $primary_column = null)
    {
        $this->ci    =& get_instance();
        $this->table = $table;

        $this->db = $db_object;
    }

    public function init($db_object, $table, $primary_column = null)
    {
        $this->ci    =& get_instance();
        $this->table = $table;

        $this->db = $db_object;

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
     * @param $func
     *
     * @return $this
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
     * @param        $col
     * @param string $type
     *
     * @return $this
     * @throws Exception
     */
    public function setColumnSearchType($col, $type = 'both')
    {
        $type = strtolower(trim($type));
        //make sure we have a valid type
        if (in_array($type, self:: $VALID_MATCH_TYPES) === false) {
            throw new RuntimeException('[' . $type . '] is not a valid type.  Must Use: ' . implode(', ',
                    self:: $VALID_MATCH_TYPES));
        }

        $this->matchType[$col] = $type;


        return $this;
    }

    /**
     * @param array $formats
     * @param bool  $debug
     *
     * @return array
     * @throws Exception
     */
    public function make(array $formats = array(), $debug = false)
    {

        $input = $this->ci->input;
        $start = (int)$input->post_get('start');
        $limit = (int)$input->post_get('length');


        $output_json                    = array();
        $output_json['start']           = $start;
        $output_json['limit']           = $limit;
        $output_json['draw']            = (int)$input->post_get('draw');
        $output_json['recordsTotal']    = 0;
        $output_json['recordsFiltered'] = 0;
        $output_json['data']            = array();

        //query the data for the records being returned
        $selectArray    = array();
        $customCols     = array();
        $columnIdxArray = array();

        foreach ($input->post_get('columns') as $column) {
            $columnIdxArray[] = $column['data'];
            if (substr($column['data'], 0, 1) === '$') {
                //indicates a column specified in the appendToSelectStr()
                $customCols[] = $column['data'];
                continue;
            }
            $selectArray[] = $column['data'];
        }
        if ($this->primary_column !== null && in_array($this->primary_column, $selectArray) === false) {
            $selectArray[] = $this->primary_column;
        }

        foreach ($input->post_get('order') as $o) {
            if ($o['column'] !== '') {
                $colName = $columnIdxArray[$o['column']];
                //handle custom sql expressions/subselects
                if (substr($colName, 0, 2) === '$.') {
                    $aliasKey = substr($colName, 2);

                    $colName = $aliasKey;
                }
                $this->ci->db->order_by($colName, $o['dir'], false);
            }
        }


        $query = $this->sqlJoinsAndWhere();
        $query->limit($limit, $start);

        $query = $query->get();

        $output_json = array();

        if ( ! $query) {
            $output_json['errorMessage'] = $this->ci->db->_error_message();

            return $output_json;
        }

        if ($debug === true) {
            $output_json['debug_sql'] = $this->ci->db->last_query();
        }

        //process the results and create the JSON objects
        $dataArray    = array();
        $allColsArray = array_merge($selectArray, $customCols);
        foreach ($query->result() as $row) {
            $colObj = array();
            //loop rows returned by the query
            foreach ($allColsArray as $column) {
                if (trim($column) === '') {
                    continue;
                }

                $propParts = explode('.', $column);

                $prop = trim(end($propParts));
                //loop columns in each row that the grid has requested
                if (count($propParts) > 1) {
                    //nest the objects correctly in the json if the column name includes
                    //the table alias
                    $nestedObj = array();
                    if (isset($colObj[$propParts[0]])) {
                        //check if we alraedy have a object for this alias in the array
                        $nestedObj = $colObj[$propParts[0]];
                    }


                    $nestedObj[$propParts[1]] = $this->formatValue($formats, $prop, $row->$prop);
                    $colObj[$propParts[0]]    = $nestedObj;
                } else {
                    $colObj[$column] = $this->formatValue($formats, $prop, $row->$prop);
                }
            }

            if ($this->primary_column !== null) {
                $tmpRowIdSegments   = explode('.', $this->primary_column);
                $idCol              = trim(end($tmpRowIdSegments));
                $colObj['DT_RowId'] = $row->$idCol;
            }
            $dataArray[] = $colObj;
        }


        $query = $this->sqlJoinsAndWhere();
        $totalRecords = $query->count_all_results();


        $output_json['start']           = $start;
        $output_json['limit']           = $limit;
        $output_json['draw']            = (int)$input->post_get('draw');
        $output_json['recordsTotal']    = $totalRecords;
        $output_json['recordsFiltered'] = $totalRecords;
        $output_json['data']            = $dataArray;
        //$output_json['debug'] = $whereDebug;

        if ($this->preResultFunc !== false) {
            $func = $this->preResultFunc;
            call_user_func_array($func, [&$output_json]);
        }

        return $output_json;

    }

    private function sqlJoinsAndWhere()
    {
        // this is protected in CI 3 and can no longer be turned off. must be turned off in the config
        // $this -> CI -> db-> _protect_identifiers = FALSE;
        $this->db->from($this->table);
        $input = $this->ci->input;

        $searchableColumns = array();
        foreach ($input->post_get('columns') as $column) {
            $is_alias = false;
            $colName  = $column['data'];

            if ($column['searchable'] !== 'false') {
                $searchableColumns[] = $colName;
            }
            if (substr($colName, 0, 2) === '$.') {
                $aliasKey = substr($colName, 2);
                $is_alias = true;
                $colName  = $aliasKey;
            }


            if ($column['search']['value'] !== '') {
                $searchType = $this->getColumnSearchType($colName);
                if ($is_alias) {
                    $this->db->having("$colName LIKE '%" . $column['search']['value'] . "%' ", null,
                        $this->protectIdentifiers);
                } else {
                    $this->db->like($colName, $column['search']['value'], $searchType, $this->protectIdentifiers);
                }
            }
        }

        // put together a global search if specified
        $globSearch = $input->post_get('search');
        if ($globSearch['value'] !== '') {
            $gSearchVal = $this->db->escape_like_str($globSearch['value']);
            $sqlOr      = '';
            $op         = '';
            foreach ($searchableColumns as $c) {
                if(strpos($c,'$')!==false){
                    $field = substr($c,2);
                    $this->db->or_having("$field LIKE '%".$gSearchVal."%' ");
                    continue;
                }
                $sqlOr .= $op . $c . ' LIKE \'' . $gSearchVal . '%\'';
                $op    = ' OR ';
            }
            $this->db->where('(' . $sqlOr . ')');
        }

        return $this->db;
    }

    /**
     * Get the current search type for a column
     *
     * @param col
     *        column sepcified in the DataTables "data" property
     *
     * @return string
     */
    public function getColumnSearchType($col)
    {
        //	log_message('info', 'getColumnSearchType() ' . var_export($this -> matchType, TRUE));
        return isset($this->matchType[$col]) ? $this->matchType[$col] : 'both';
    }


    private function formatValue($formats, $column, $value)
    {
        if (isset($formats[$column]) === false || trim($value) == '') {
            return $value;
        }

        switch ($formats[$column]) {
            case 'date' :
                $dtFormats = array('Y-m-d H:i:s', 'Y-m-d');
                $dt        = null;
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
                return number_format($value, 2) . '%';
            case 'currency' :
                return '$' . number_format((float)$value, 2);
            case 'boolean' :
                $b = filter_var($value, FILTER_VALIDATE_BOOLEAN);

                return $b ? 'Yes' : 'No';
        }

        return $value;
    }


}
