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

    private $input;

    private $query_data = [];

    private $data_input = [];

    private $data_output = [];

    private $edit_columns = [];

    private $add_columns = [];

    public function __construct()
    {
        $this->ci    =& get_instance();
        $this->db    = $this->ci->db;
        $this->input = $this->ci->input;

        $this->data_input[ 'columns' ] = $this->input->post( 'columns' );
        $this->data_input[ 'order' ]   = $this->input->post( 'order' );
        $this->data_input[ 'start' ]   = $this->input->post( 'start' );
        $this->data_input[ 'length' ]  = $this->input->post( 'length' );
        $this->data_input[ 'search' ]  = $this->input->post( 'search' );

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

    public function or_like( $field, $match = '', $side = 'both', $escape = NULL )
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

    public function not_like( $field, $match = '', $side = 'both', $escape = NULL )
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

    public function or_not_like( $field, $match = '', $side = 'both', $escape = NULL )
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

    public function group_start( $not = '', $type = 'AND ' )
    {
        $this->query_data[ 'where' ][] = [
            'type'      => 'group_start',
            'not'       => $not,
            'condition' => $type,
        ];
        return $this;
    }

    public function not_group_start()
    {
        $this->query_data[ 'where' ][] = [
            'type' => 'not_group_start',
        ];
        return $this;
    }

    public function or_group_start()
    {
        $this->query_data[ 'where' ][] = [
            'type' => 'or_group_start',
        ];
        return $this;
    }

    public function or_not_group_start()
    {
        $this->query_data[ 'where' ][] = [
            'type' => 'or_not_group_start',
        ];

        return $this;
    }

    public function group_by( $by, $escape = NULL )
    {
        $this->db->group_by( $by );
        $this->query_data[ 'group_by' ][] = [
            'by'     => $by,
            'escape' => $escape,
        ];
    }

    public function edit_column( $column, $callback )
    {

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
}
