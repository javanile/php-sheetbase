<?php

namespace Javanile\Sheetbase;

/**
 *
 */
interface DriverInterface
{
    /**
     * @param $database
     */
    public function addDatabase($database);

    /**
     * @param $database
     * @return mixed
     */
    public function setDatabase($database);

    /**
     * @param $name
     * @return mixed
     */
    public function hasDatabase($name) ;

    /**
     * @param $table
     * @param int $cols
     * @param null $rows
     */
    public function addTable($table, $cols = 10, $rows = null);

    /**
     * @param $name
     * @return false
     */
    public function hasTable($name);

    /**
     * @param $table
     * @return mixed
     */
    public function setTable($table);

    /**
     * @return mixed
     */
    public function getTables();

    /**
     * @param $row
     * @param $col
     * @param $value
     * @return mixed
     */
    public function set($row, $col, $value);

    /**
     * @param $row
     * @param $col
     * @return mixed
     */
    public function get($row, $col);

    /**
     * @param $query
     * @param int $col
     * @return mixed
     */
    public function search($query, $col=1);

    /**
     * @param $column
     * @param int $searchColumn
     * @param string $searchValue
     * @return mixed
     */
    public function column($column,$searchColumn=0,$searchValue="");

    /**
     * @param $row
     * @return mixed
     */
    public function insert($row);

    /**
     * @return mixed
     */
    public function all();

    /**
     * @return mixed
     */
    public function getRowCount();

    /**
     * @return mixed
     */
    public function getColCount();
}
