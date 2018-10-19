<?php
/**
 * Created by PhpStorm.
 * User: Fayaz.K
 * Date: 10/3/2018
 * Time: 11:02 AM
 */

class CIDataTables
{

    private $ci;

    private $db;

    private $query_data = [];

    private $data_input = [];

    private $data_output = [];

    private $edit_columns = [];

    private $add_columns = [];

    public function __construct()
    {
        $this->ci =& get_instance();
        $this->db = $this->ci->db;

        $this->data_input[ 'columns' ] = $this->ci->input->post( 'columns' );
        $this->data_input[ 'order' ]   = $this->ci->input->post( 'order' );
        $this->data_input[ 'start' ]   = $this->ci->input->post( 'start' );
        $this->data_input[ 'length' ]  = $this->ci->input->post( 'length' );
        $this->data_input[ 'search' ]  = $this->ci->input->post( 'search' );

        $this->data_output[ 'input' ] = $this->data_input;
    }

    public function setDatabase( $options )
    {
        $this->db = $this->ci->load->database( $options, TRUE, TRUE );
        return $this;
    }// setDatabase()

    public function select( $select = '*', $escape = NULL )
    {
        $this->query_data[ 'select' ][] = [
            'select' => $select,
            'escape' => $escape,
        ];
        return $this;
    }// select()

    public function from( $table )
    {
        $this->query_data[ 'from' ] = $table;
        return $this;
    }// from()

    public function leftJoin( $table, $cond, $escape = NULL )
    {
        $this->query_data[ 'join' ][] = [
            'table'     => $table,
            'condition' => $cond,
            'escape'    => $escape,
            'type'      => 'LEFT',
        ];
        return $this;
    }// leftJoin()

    public function rightJoin( $table, $cond, $escape = NULL )
    {
        $this->query_data[ 'join' ][] = [
            'table'     => $table,
            'condition' => $cond,
            'escape'    => $escape,
            'type'      => 'RIGHT',
        ];
        return $this;
    }// rightJoin()

    public function outerJoin( $table, $cond, $escape = NULL )
    {
        $this->query_data[ 'join' ][] = [
            'table'     => $table,
            'condition' => $cond,
            'escape'    => $escape,
            'type'      => 'OUTER',
        ];
        return $this;
    }// outerJoin()

    public function innerJoin( $table, $cond, $escape = NULL )
    {
        $this->query_data[ 'join' ][] = [
            'table'     => $table,
            'condition' => $cond,
            'escape'    => $escape,
            'type'      => 'INNER',
        ];
        return $this;
    }// innerJoin()

    public function leftOuterJoin( $table, $cond, $escape = NULL )
    {
        $this->query_data[ 'join' ][] = [
            'table'     => $table,
            'condition' => $cond,
            'escape'    => $escape,
            'type'      => 'LEFT OUTER',
        ];
        return $this;
    }// leftOuterJoin()

    public function rightOuterJoin( $table, $cond, $escape = NULL )
    {
        $this->query_data[ 'join' ][] = [
            'table'     => $table,
            'condition' => $cond,
            'escape'    => $escape,
            'type'      => 'RIGHT OUTER',
        ];
        return $this;
    }// rightOuterJoin()

    public function where( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'where',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function orWhere( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'or_where',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function whereIn( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'where_in',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function orWhereIn( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'or_where_in',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function whereNotIn( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'where_not_in',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function orWhereNotIn( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'or_where_not_in',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function like( $field, $match = '', $side = 'both', $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'like',
            'field'  => $field,
            'match'  => $match,
            'side'   => $side,
            'escape' => $escape,
        ];
        return $this;
    }

    public function orLike( $field, $match = '', $side = 'both', $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'or_like',
            'field'  => $field,
            'match'  => $match,
            'side'   => $side,
            'escape' => $escape,
        ];
        return $this;
    }

    public function notLike( $field, $match = '', $side = 'both', $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'not_like',
            'field'  => $field,
            'match'  => $match,
            'side'   => $side,
            'escape' => $escape,
        ];
        return $this;
    }

    public function orNotLike( $field, $match = '', $side = 'both', $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'or_not_like',
            'field'  => $field,
            'match'  => $match,
            'side'   => $side,
            'escape' => $escape,
        ];
        return $this;
    }

    public function having( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'having',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function orHaving( $key, $value = NULL, $escape = NULL )
    {
        $this->query_data[ 'where' ][] = [
            'type'   => 'or_having',
            'key'    => $key,
            'value'  => $value,
            'escape' => $escape,
        ];
        return $this;
    }

    public function groupStart( $not = '', $type = 'AND ' )
    {
        $this->query_data[ 'where' ][] = [
            'type'      => 'group_start',
            'not'       => $not,
            'condition' => $type,
        ];
        return $this;
    }

    public function notGroupStart()
    {
        $this->query_data[ 'where' ][] = [
            'type' => 'not_group_start',
        ];
        return $this;
    }

    public function orGroupStart()
    {
        $this->query_data[ 'where' ][] = [
            'type' => 'or_group_start',
        ];
        return $this;
    }

    public function orNotGroupStart()
    {
        $this->query_data[ 'where' ][] = [
            'type' => 'or_not_group_start',
        ];

        return $this;
    }

    public function groupBy( $by, $escape = NULL )
    {
        $this->query_data[ 'group_by' ][] = [
            'by'     => $by,
            'escape' => $escape,
        ];
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
        $this->edit_columns[ $column ] = $callback;
        return $this;
    }

    /**
     * @param $column
     * @param $callback
     *
     * @return $this
     */
    public function addColumn( $column, $callback )
    {
        $this->add_columns[ $column ] = $callback;
        return $this;
    }


    /**
     * Set Paging
     */
    private function setPaging()
    {
        $start  = $this->data_input[ 'start' ];
        $length = $this->data_input[ 'length' ];
        if ( $length != '' && $length != '-1' ) {
            $this->db->limit( $length, ( $start ) ? $start : 0 );
        }
    }

    private function setSortOrdering()
    {
        foreach ( $this->data_input[ 'order' ] as $order ) {
            $i         = $order[ 'column' ];
            $direction = $order[ 'dir' ];

            $column_name = $this->data_input[ 'columns' ][ $i ][ 'data' ];

            $this->db->order_by( $column_name, $direction );
        }
    }

    private function setFilters()
    {
        $likes  = [];
        $search = $this->data_input[ 'search' ][ 'value' ];
        if ( !empty( $search ) ) {
            foreach ( $this->data_input[ 'columns' ] as $column ) {
                $name = $column[ 'data' ];

                if ( $column[ 'searchable' ] === 'true' && empty( $this->add_columns[ $name ] ) ) {
                    $likes[] = $name . " LIKE '%" . $search . "%'";
                }
            }
        } else {
            /* ------------------------------------------------------
             *      Individual Column Search
             * ------------------------------------------------------
             */
            foreach ( $this->data_input[ 'columns' ] as $column ) {
                $name       = $column[ 'data' ];
                $searchable = $column[ 'searchable' ] === 'true';
                $not_custom = empty( $this->add_columns[ $name ] );
                $search     = $column[ 'search' ][ 'value' ];

                if ( $searchable && $not_custom && !empty( $search ) ) {
                    $likes[] = $name . " LIKE '%" . $search . "%'";
                }
            }
        }

        if ( !empty( $likes ) ) {
            $this->db->where( '(' . implode( ' OR ', $likes ) . ')' );
        }
    }

    private function getResult()
    {
        $result = $this->db->get( $this->query_data[ 'from' ] );
        $this->logQuery();
        return $result;
    }

    /**
     * Get result count
     *
     * @param bool $filtering
     *
     * @return integer
     */
    private function setTotals( $filtering = TRUE )
    {
        if ( $filtering ) {
            $this->setFilters();
        }
        $this->setCustomClauses();

        $query = $this->getResult();
        return $query->num_rows();
    }

    private function setCustomClauses()
    {
        // Set Select
        if ( !empty( $this->query_data[ 'select' ] ) ) {
            foreach ( $this->query_data[ 'select' ] as $select ) {
                $this->db->select( $select[ 'select' ], $select[ 'escape' ] );
            }
        }

        // set From
        $this->db->from( $this->query_data[ 'from' ] );

        // Set Joins
        foreach ( $this->query_data[ 'join' ] as $join ) {
            $this->db->join( $join[ 'table' ], $join[ 'condition' ], $join[ 'type' ], $join[ 'escape' ] );
        }

        // Set Where, Like and Having Clause
        foreach ( $this->query_data[ 'where' ] as $clause ) {
            switch ( $clause[ 'type' ] ) {
                case 'where':
                    $this->db->where( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'or_where':
                    $this->db->or_where( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'where_in':
                    $this->db->where_in( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'or_where_in':
                    $this->db->or_where_in( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'where_not_in':
                    $this->db->where_not_in( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'or_where_not_in':
                    $this->db->or_where_not_in( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'like':
                    $this->db->like( $clause[ 'field' ], $clause[ 'match' ], $clause[ 'side' ], $clause[ 'escape' ] );
                    break;
                case 'or_like':
                    $this->db->or_like( $clause[ 'field' ], $clause[ 'match' ], $clause[ 'side' ], $clause[ 'escape' ] );
                    break;
                case 'not_like':
                    $this->db->not_like( $clause[ 'field' ], $clause[ 'match' ], $clause[ 'side' ], $clause[ 'escape' ] );
                    break;
                case 'or_not_like':
                    $this->db->or_not_like( $clause[ 'field' ], $clause[ 'match' ], $clause[ 'side' ], $clause[ 'escape' ] );
                    break;
                case 'having':
                    $this->db->having( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'or_having':
                    $this->db->or_having( $clause[ 'key' ], $clause[ 'value' ], $clause[ 'escape' ] );
                    break;
                case 'group_start':
                    $this->db->group_start( $clause[ 'not' ], $clause[ 'condition' ] );
                    break;
                case 'not_group_start':
                    $this->db->not_group_start();
                    break;
                case 'or_group_start':
                    $this->db->or_group_start();
                    break;
                case 'or_not_group_start':
                    $this->db->or_not_group_start();
                    break;
                default:
                    break;
            }
        }

        if ( !empty( $this->query_data[ 'group_by' ] ) ) {
            foreach ( $this->query_data[ 'group_by' ] as $group ) {
                $this->db->order_by( $group[ 'by' ], $group[ 'escape' ] );
            }
        }
        return $this;
    }

    private function mutateQueryResult()
    {
        $output_array = [];

        $result = $this->getResult()->result();

        foreach ( $result as $index => $row ) {
            $output_array[ $index ] = $row;

            foreach ( $this->add_columns as $key => $function ) {
                $output_array[ $index ]->{$key} = call_user_func( $function, $row );
            }

            foreach ( $this->edit_columns as $key => $function ) {
                $output_array[ $index ]->{$key} = call_user_func( $function, $row );
            }
        }

        $this->data_output[ 'data' ] = $output_array;


        return $this;

    }// mutateQueryResult()

    /**
     * Builds all the necessary query segments and performs the main query based on results set from chained statements
     *
     * @param string $output
     * @param string $charset
     *
     * @return string
     */
    public function make( $output = 'json', $charset = 'UTF-8' )
    {
        $this->setPaging();
        $this->setSortOrdering();
        $this->setFilters();
        $this->setCustomClauses();
        $this->setTotals();
        $this->mutateQueryResult();
    }

    private function logQuery()
    {
        $this->data_output[ 'query' ][] = $this->db->last_query();
    }
}
