<?php

namespace Javanile\Sheetbase;

/**
 *
 */
class Cache
{
	##
	private $cache = null;
	
	##
	private $databases = null;
	
	##
	private $database = null;
	
	##	
	private $databaseid = null;

	##
	private $tables = null;

	##
	private $table = null;
	
	##
	private $hash = null;
	
	##
	private $define = null;
	
	##
	private $static = null;
	
	##
	private $change = null;

	##
	private $insert = null;

	##
	private $create = null;

	##
	private $data = null;
	
	##
	private $info = null;
	
	##
	private $drive = null;

    /**
     * @param $args
     * @param $drive
     */
	public function __construct($args,&$drive)
    {
		$this->databases = $args['database'];
		$this->cache = __DIR__.'/cache';
		$this->drive = $drive;
	}

	##
	public function setDatabase($database) {
	
		##
		if (!$this->hasDatabase($database)) {
			throw new Exception('No database found: "'.$database.'"');			
		}
		
		##
		$this->database = $database; 

		##
		$this->databaseid = $this->getDatabaseId($database); 

		##
		$this->create = $this->cache.'/create/'.$this->databaseid;		
		
		##
		$this->tables = null;
		
		##
		$this->hash = null;
		
		##
		$this->table = null;
		
		##
		$this->define = null;

		##
		$this->static = null;
		
		##
		$this->insert = null;		

		##
		$this->change = null;		
	}
			
	##
	public function hasDatabase($name) {
	
		##
		return isset($this->databases[$name]);		
	}
	
	##
	public function getDatabaseId($name) {
	
		##
		return $this->databases[$name];		
	}
	
	##
	public function setTable($table) {
		
		##
		if ($this->table == $table || !$table) {
			return;
		}
		
		##
		$this->requireDatabase();
	
		##
		$this->requireTables();
		
		##
		if (!$this->hasTable($table)) {
			throw new Exception('No setTable for not existing: "'.$table.'"');			
		}
		
		##
		$this->table = $table;
		
		##
		$this->hash = $this->databaseid.'$'.$this->table;
				
		##
		$this->define = $this->cache.'/define/'.$this->hash;
		
		##
		$this->static = $this->cache.'/static/'.$this->hash;
		
		##
		$this->insert = $this->cache.'/insert/'.$this->hash;
		
		##
		$this->change = $this->cache.'/change/'.$this->hash;	
		
		##
		$this->data = null;
		
		##
		$this->info = null;
 	}
	
	
	##
	public function addTable($table,$cols=10,$rows=null) {				
			
		##
		$this->requireTables();
		
		##
		if ($this->hasTable($table)) {
			throw new Exception('Table already exists: "'.$table.'"');						
		}
		
		##
		$this->tables[] = strtolower($table);
		
		##
		$this->saveTables();
						
		##		
		$fields = array(); 
			
		##
		if (is_array($cols)) {
			foreach($cols as $col) {
				$fields[] = strtolower($col);
			}		
			$cols = count($fields);			
			$rows = $rows ? $rows : 1;
		} else {
			$rows = $rows ? $rows : 10;			
		}
				
		##
		if (count($fields)>0) {
			$temp = $this->table;
			$this->setTable($table);			
			foreach($fields as $c=>$field) {
				$this->set(1,$c+1,$field);				
			} 			
			$this->setTable($temp);
		}
		
		##
		$this->saveCreate(array(
			'action' => 'addTable',
			'table'	 => $table,
			'cols'	 => $cols,
			'rows'	 => $rows,
		));
	}

	##
	public function hasTable($table) {	
		
		## use false to disable cache
		$this->requireTables(true);
			
		##
		return in_array(strtolower($table), $this->tables);
	}
		
	##
	public function set($row,$col,$value) {
		
		##
		$this->requireData();
		
		##		
		$this->data[$row][$col] = $value;		
				
		##
		$this->saveData();
		
		##
		$this->saveChange($row,$col,$value);
	}
	
	##
	public function get($row,$col) {
		
		##
		$this->requireData();
		
		##
		return @$this->data[$row][$col];				
	}
		
	##
	public function search($query,$column=0,$start=2) {		
						
		##
		$this->requireData();
		$this->requireInfo();
		
		##
		if (is_array($query)) {
			
			##
			$transkey = $this->getTranskey();
						
			##
			for($row = $start; $row <= $this->info['rowCount']; $row++) {				
				$exit = true;
				foreach($query as $key=>$value) {
					if (@$this->data[$row][$transkey[$key]] != $value) {
						$exit = false;
					}					
				}			
				if ($exit) {
					return $row;
				}
			}
			
		} else {
			
			##
			if ($column > 0) {
				##
				for($row = $start; $row <= $this->info['rowCount']; $row++) {
					if ($this->data[$row][$column] == $query) {
						return $row;
					}
				}
			} else {
				##
				for($row = $start; $row <= $this->info['rowCount']; $row++) {
					foreach($this->data[$row] as $cell) {
						if ($cell == $query) {
							return $row;							
						}
					} 					
				}
			}
		}	

		##
		return 0;			
	}
	
	##
	public function column($column,$searchColumn=0,$searchValue="") {
		
		##
		$this->requireData();
		$this->requireInfo();
		
		##
		$data = array();
		
		##
		for($row=2; $row<=$this->info['rowCount']; $row++) {		
					
			$cell = @$this->data[$row][$column];
			
			if ($searchColumn > 0) {				
				if (@$this->data[$row][$searchColumn] == $searchValue) {
					$data[$row] = $cell; 																				
				}
			} else {
				$data[$row] = $cell; 															
			}			
		}
		
		##
		return $data;				
	}
		
	##
	public function insert($query) {
		
		##
		$this->requireData();
		$this->requireInfo();
		$this->requireTable();
		
		##
		$empty = 0;
		
		##
		foreach($query as $cell) {
			if (!$cell) { $empty++; } 
		}
		
		##
		if (count($query)==$empty) {
			return;
		}
		
		##
		$data = static::load($this->insert);
				
		##
		$data[] = $query;
		
		##
		static::save($this->insert,$data);
		
		##
		$this->info['rowCount']++; 
		
		##
		$this->saveInfo();
		
		##
		$row = array();
		
		##
		$transkey = $this->getTranskey();
				
		##
		foreach($query as $key=>$value) {
			$row[$transkey[$key]] = $value;			
		}
				
		##
		$this->data[$this->info['rowCount']] = $row;
		
		##
		$this->saveData();
	}
	
	##
	public function all() {
		
		##
		$this->requireData();
				
		##
		return $this->data;				
	}
	
	##
	public function getColCount() {
		
		$this->requireInfo();
		
		return (int) $this->info['colCount'];
	}

	##
	public function getRowCount() {
		
		$this->requireInfo();
		
		return (int) $this->info['rowCount'];
	}

	##
	public function getTranskey() {
		
		##
		$transkey = array();
		
		##
		for($i=1; $i<=$this->info['colCount']; $i++) {
			$key = isset($this->data[1][$i]) && $this->data[1][$i] ? $this->data[1][$i] : $i;
			$transkey[$key] = $i;
		}
		
		##
		return $transkey;	
	} 
	
	
	##
	public static function load($file) {
		
		##
		return file_exists($file) ? json_decode(file_get_contents($file),true) : array();			
	}
	
	##
	public static function save($file,$data) {
		
		##
		file_put_contents($file, json_encode($data));		
	}

	##
	public function requireDatabase() {		
		if (!$this->database) {
			throw new Exception("GoogleDB require database: use setDatabase(.)");
		}			
	}

	##
	public function requireTable() {		
		if (!$this->table) {
			throw new Exception("GoogleDB require table: use setTable(.)");
		}			
	}
	
	##
	public function requireStatic() {
		
		##
		$this->requireTable();
		
		##
		if (!file_exists($this->static)) {	
			$this->drive->setDatabase($this->database);			
			if ($this->drive->hasTable($this->table)) {
				$this->drive->setTable($this->table);
				$this->data = $this->drive->all();				
			} else {
				$this->data = array(1=>array(1=>""));
			}
			$this->saveData();
		}		
	}
	
	##
	public function requireDefine() {
		
		##
		$this->requireTable();
				
		##
		if (!file_exists($this->define) || (time()-filemtime($this->define)) > 300) {	
			
			##
			$this->drive->setDatabase($this->database);			
			
			##
			$this->drive->setTable($this->table);						
			
			##
			$this->info = array(
				'colCount'	=> $this->drive->getColCount(),
				'rowCount'	=> $this->drive->getRowCount(),
			);
			
			##
			$this->saveInfo();			
		} 	
	}
	
	##
	public function requireData() {
		
		##
		$this->requireStatic();
		
		##
		if (!$this->data) {
			$this->data = static::load($this->static);			
		}		
	}
	
	##
	public function requireInfo() {
		
		##
		$this->requireDefine();
		
		##
		if (!$this->info) {
			$this->info = static::load($this->define);			
		}		
	}

	##
	public function requireTables($cache=true) {
		
		##
		$this->requireDatabase();
			
		##
		if (!$this->tables) {
		
			##
			$file = $this->cache.'/tables/'.$this->databaseid;
			
			##
			if (!file_exists($file) || !$cache) {
								
				##
				$this->drive->setDatabase($this->database);
				
				##
				$this->tables = $this->drive->getTables();
				
				##
				$this->saveTables();
				
			} else {
				
				##
				$this->tables = static::load($file);				
			}			
		}		
	}
	
	##
	public function saveData() {
		static::save($this->static, $this->data);		
	}
	
	##
	public function saveInfo() {
		static::save($this->define, $this->info);		
	}
	
	##
	public function saveTables() {
		static::save($this->cache.'/tables/'.$this->databaseid, $this->tables);		
	}
	
	##
	public function saveChange($row,$col,$value) {
		$data = static::load($this->change);
		$data[$row][$col] = $value;		
		static::save($this->change,$data);
	}
	
	##
	public function saveCreate($create) {
			
		##
		$data = static::load($this->create);		
		
		##
		$data[] = $create;		
		
		##
		static::save($this->create,$data);
	}
	
	##
	public function flush() {
		
		$this->requireTable();
		
		@unlink($this->static);
		@unlink($this->insert);
		@unlink($this->define);
		@unlink($this->change);
	}
	
	##
	public function dropCache() {
		
		##
		$folders = array(
			$this->cache.'/static',
			$this->cache.'/insert',
			$this->cache.'/change',
			$this->cache.'/define',
			$this->cache.'/create',
			$this->cache.'/tables',
		);
		
		##
		foreach($folders as $folder) {
			
			##
			$files = glob($folder.'/*'); // get all file names

			##
			if (count($files)>0 && $files) {
				foreach($files as $file){ // iterate files
					if(is_file($file)) {
					  unlink($file); // delete file
					}
				} 
			}
		}
	}
	
	##
	public function submit() {
		
		##
		$this->submitCreate();
		
		##
		$this->submitInsert();
		
		##	
		$this->submitChange();
		
		## empty cached table list for all databases
		foreach($this->databases as $databaseid) {
			@unlink($this->cache.'/tables/'.$databaseid);
		}
	}
	
	##
	public function submitCreate() {
	
		##
		echo "[CREATE]\n\n";
		
		##  
		foreach(scandir($this->cache.'/create/') as $f) {
			
			if ($f[0]=='.') { continue; }
			
			##
			$file = $this->cache.'/create/'.$f;
			
			##
			$databaseid = $f;
			
			##
			if (!$databaseid) { continue; }
			
			##
			$database = $this->drive->getDatabase($databaseid); 

			##
			if (!$database) { continue; }

			##
			$this->drive->setDatabase($database);
						
			##
			$data = static::load($file); 
			if (count($data)>0) { 
				foreach($data as $line) {
					echo ' - create: '.json_encode($line)."\n";
					switch($line['action']) {
						case 'addTable':	
							if (!$this->drive->hasTable($line['table'])) {
								$this->drive->addTable($line['table'],$line['cols'],$line['rows']);
							}
							break;						
					}					
				}
			}
			
			##
			echo ' - delete: '.$file."\n";
			
			##
			unlink($file);						
			
			##
			echo "\n";
		} 		
	}
	
	##
	public function submitInsert() {
	
		##
		echo "[INSERT]\n\n";
		
		##  
		foreach(scandir($this->cache.'/insert/') as $f) {
			
			if ($f[0]=='.') { continue; }
			
			##
			$file = $this->cache.'/insert/'.$f;
			
			##
			@list($databaseid,$table) = explode('$',$f,2);
			
			##
			if (!$databaseid||!$table) { continue; }
			
			##
			$database = $this->drive->getDatabase($databaseid); 

			##
			if (!$database) { continue; }

			##
			$this->drive->setDatabase($database);
			
			##
			$this->drive->setTable($table);			
			
			##
			echo "table: $table ($database)\n";				
			
			##
			$data = static::load($file); 
			if (count($data)>0) { 
				foreach($data as $row) {
					echo " - insert: ".  json_encode($row)."\n";
					$this->drive->insert($row);					
				}
			}
			
			##
			echo ' - delete: '.$file."\n";
			
			##
			unlink($file);						
			
			##
			echo "\n";
		} 		
	}
	
	##
	public function submitChange() {

		##
		echo "[CHANGE]\n\n";

		##  
		foreach(scandir($this->cache.'/change/') as $f) {
			
			##
			if ($f[0]=='.') { continue; }
			
			##
			$file = $this->cache.'/change/'.$f;
			
			##
			@list($databaseid,$table) = explode('$',$f,2);
						
			##
			if (!$databaseid||!$table) { continue; }
						
			##
			$data = static::load($file); 
            
			##
			$database = $this->drive->getDatabase($databaseid); 
			
			##
			if (!$database) { continue; }
			
			##
			$this->drive->setDatabase($database);
			
			##
			$this->drive->setTable($table);				
			
			##
			echo "table: $table ($database)\n";				
			
			##
			foreach($data as $row=>$line) {
				foreach($line as $col=>$cell) {
					echo " - update: $row, $col, '$cell'\n";
					$this->drive->set($row,$col,$cell);
				}
			}
						
			##
			echo ' - delete: '.$file."\n";
			
			##
			unlink($file);

			##
			$file = $this->cache.'/static/'.$f;
					
			##
			echo ' - delete: '.$file."\n";
			
			##
			unlink($file);

			##
			$this->data = null;
			
			##
			echo "\n";
		} 		
	}
	
}