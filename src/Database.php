<?php

namespace Javanile\Sheetbase;

/**
 *
 */
class Database
{
    /**
     * @var null
     */
	static $instance = null;

    /**
     * @var Drive
     */
	private $drive = null;

    /**
     * @var Cache
     */
	private $cache = null;

    /**
     * @var bool|mixed
     */
	private $useCache = true;

    /**
     * @var bool|mixed
     */
    private $point = true;

    /**
     * @param $args
     */
	public function __construct($args)
    {
		$this->drive = new Drive($args);
		$this->cache = new Cache($args, $this->drive);
        $this->useCache = isset($args['cache']) ? $args['cache'] : $this->useCache;

		if ($this->useCache) {
			$this->point =& $this->cache; 
		} else {
			$this->point =& $this->drive; 			
		}
	}

    /**
     * @return mixed
     */
	public static function &getInstance()
    {
		if (static::$instance === null) {
			static::$instance = new static();
		}

		return static::$instance;
	}
	
	##
	public function setDatabase($database) {
				
		##
		return $this->point->setDatabase($database);
	}
	
	##
	public function hasDatabase($database) {
				
		##
		return $this->point->hasDatabase($database);
	}
	
	##
	public function setTable($table) {
				
		##
		return $this->point->setTable($table);
	}
	
	##
	public function addTable($table,$cols=10,$rows=null) {
				
		##
		return $this->point->addTable($table,$cols,$rows);
	}

	##
	public function hasTable($table) {
				
		##
		return $this->point->hasTable($table);
	}

	##
	public function set($row, $col, $value) {
	
		##
		return $this->point->set($row,$col,$value);	
	}

	##
	public function get($row, $col) 
    {
		##
		return $this->point->get($row,$col);	
	}

	##
	public function all() {

		##
		return $this->point->all();			
	}

	##
	public function column($column,$searchColumn=0,$searchValue="") {

		##
		return $this->point->column($column,$searchColumn,$searchValue);			
	}

	##
	public function search($query,$column=0) {
		
		##
		return $this->point->search($query,$column);		
	}
		
	##
	public function insert($query) {
		
		##
		return $this->point->insert($query);
	}
	
	##
	public function getColCount() {

		##
		return $this->point->getColCount();		
	}

	##
	public function getRowCount() {

		##
		return $this->point->getRowCount();		
	}

	##
	public function submit() {
		
		##
		echo '<pre>';
		echo get_called_class()."->submit()\n";
		echo "\n";
		
		##
		$this->cache->submit();
		
		##
		echo '</pre>';
	}
		
	##
	public function flush() {
		
		##
		$this->cache->flush();
	}
	
	##
	public function dropCache() {
		
		##
		$this->cache->dropCache();
	}
	
}
