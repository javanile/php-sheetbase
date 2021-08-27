<?php

namespace Javanile\Sheetbase;

/**
 *
 */
class Drive
{
	##
	private $databases = null;
	
	##	
	private $databaseid = null;
	
	##
	private $database = null;
			
	##
	private $table = null;
	
	##	
	private $worksheet = null;
	
	##
	private $worksheets = null;
	
	##
	private $spreadsheet = null;
		
	##
	private $cell = null;
	
	##
	private $list = null;

	##
	public function __construct($args) {
				
		##
		$this->gc = new Google_Client();
		
		##
		$this->gc->setApplicationName("Client_Library_Examples");
		
		##
		$key = file_get_contents($args['p12']);
		 
		##
		$gac = new Google_Auth_AssertionCredentials(
			$args['emailapp'], 
			array(
				'https://spreadsheets.google.com/feeds',
				"https://www.googleapis.com/auth/drive",            	
				"https://www.googleapis.com/auth/drive.file",
				"https://www.googleapis.com/auth/drive.readonly",
				"https://www.googleapis.com/auth/drive.metadata.readonly",
				"https://www.googleapis.com/auth/drive.appdata",
				"https://www.googleapis.com/auth/drive.apps.readonly",
            	"https://www.googleapis.com/auth/drive.metadata",           
			),
			$key
		);

		##
		$this->gc->setAssertionCredentials($gac);
		
		##
		if ($this->gc->getAuth()->isAccessTokenExpired()) {
			$this->gc->getAuth()->refreshTokenWithAssertion($gac);
		}
		
		##
		$at = json_decode($this->gc->getAuth()->getAccessToken());
		
    
		##
		$serviceRequest = new Google\Spreadsheet\DefaultServiceRequest($at->access_token);
		
    
		##
		Google\Spreadsheet\ServiceRequestFactory::setInstance($serviceRequest);		
			
		## Google Spreadsheet Service uset to retrieve list of 
		$this->gss = new Google\Spreadsheet\SpreadsheetService();
	
    
		##
		$this->databases = $args['database'];
		
	}
			
	##
	public function setDatabase($database) {
	
		##
		if (!$this->hasDatabase($database)) {
			throw new Exception('No database found: "'.$database.'"');			
		}
	
		##
		if ($database != $this->database) {
		
			##
			$this->database = $database; 

			##
			$this->databaseid = $this->getDatabaseId($database);

			##
			$this->table = null;
			
			##
			$this->spreadsheet = null;
			
			##
			$this->worksheets = null;

			##	
			$this->worksheet = null;

			##
			$this->cell = null;

			##
			$this->list = null;
		}
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
	public function getDatabase($databaseid) {
		foreach($this->databases as $database=>$id) {
			if ($id == $databaseid) {
				return $database;
			}			
		}		
	}
	
	##
	public function addTable($table,$cols=10,$rows=null) {				
		
		##
		if ($this->hasTable($table)) {
			throw new Exception('Table already exists: "'.$table.'"');						
		}
				
		##
		$this->requireSpreadsheet();
				
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
		$worksheet = $this->spreadsheet->addWorksheet($table,$rows,$cols);

		##
		if (count($fields)>0) {
			$feed = $worksheet->getCellFeed();
			foreach($fields as $c=>$field) {
				$feed->editCell(1,$c+1,$field);				
			} 			
		}
			
		##
		$this->worksheets = null;			
	}

	##
	public function hasTable($name) {	
		
		##
		$this->requireWorksheets();
		
		##
		return (boolean) $this->worksheets->getByTitle($name);
	}

	##
	public function setTable($table) {
		
		##
		if ($table != $this->table) {

			##
			$this->table = $table;		

			##	
			$this->worksheet = null;

			##
			$this->cell = null;

			##
			$this->list = null;			
		} 		
 	}
	
	##
	public function getTables() {
		
		##
		$this->requireWorksheets();
		
		##
		$tables = array(); 
		
		##
		foreach($this->worksheets as $item) {
			$tables[] = strtolower($item->getTitle()); 
		}
				
		##
		return $tables;		
	}
		
	##
	public function set($row,$col,$value) {
		
		##
		$this->requireCell();
				
		##
		$this->cell->editCell($row,$col,strtr($value,'"&',' -'));		
	}
		
	##
	public function get($row,$col) {
		
		##
		$this->requireCell();
		
		##
		$cell = $this->cell->getCell($row,$col);
		
		if ($cell) {
			return $cell->getContent();
		} else {
			return '';			
		}			
	}
		
	##
	public function search($query,$col=1) {		
		
		if (is_array($query)||is_object($query)) {
			
			$this->requireCell();
			
			for($row=2; $row<=$this->worksheet->getRowCount(); $row++) {
				$exit = true;
				foreach($query as $key=>$value) {
					
					
					
				}			
				if ($exit) {
					return $row;
				}
			}
			
		} else {
		
			##
			$value = $query;
			
			##
			$this->requireCurrentCell();

			##
			$cell = null;

			##
			for($row=2; $row<=$this->currentTable->getRowCount(); $row++) {

				##
				$cell = $this->currentCell->getCell($row,$col);

				##
				if ($cell) {
					if ($cell->getContent() == $value) {
						return $row;
					}				
				} else if (!$value) {
					return $row;
				}			
			}

			##
			return 0;			
		}		
	}
	
	##
	public function column($column,$searchColumn=0,$searchValue="") {
		
		##
		$this->requireCell();
		$this->requireWorksheet();
			
		##
		$data = array();
		
		##
		$rowCount = $this->getRowCount();
				
		##
		for($row=2; $row<=$rowCount; $row++) {		
			
			##
			$cell = $this->get($row,$column);
							
			if ($searchColumn > 0) {
				$searchCell = $this->get($row, $searchColumn);
				if ($searchCell == $searchValue) {
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
	public function insert($row) {
		
		##
		$this->requireList();
					
		##
		$this->list->insert($row); 		
	}
		
	##
	public function all() {
				
		##
		$this->requireCell();
		
		##
		$data = array();
		
		##
		for($row=1; $row<=$this->worksheet->getRowCount(); $row++) {
			
			##
			for($col=1; $col<=$this->worksheet->getColCount(); $col++) {

				##
				$cell = $this->cell->getCell($row,$col);
				
				##
				$data[$row][$col] = $cell ? $cell->getContent() : ''; 												 		
			}
		}
				
		##
		return $data;				
	}
	
	##
	public function getTranskey() {
		
		##
		$transkey = array();
		
		##
		for($i=1; $i<=$this->getColCount(); $i++) {
			$val = $this->get(1,$i);
			$key = $val ? $val : $i;
			$transkey[$key] = $i;
		}
		
		##
		return $transkey;	
	} 
	
	##
	public function getRowCount() {
		
		##
		$this->requireWorksheet();
		
		##
		return (int) $this->worksheet->getRowCount();
	}
	
	##
	public function getColCount() {

		##
		$this->requireWorksheet();

		##
		return (int) $this->worksheet->getColCount();
	}
	
	##
	public function requireCell() {
		
		##
		$this->requireWorksheet();
				
		##
		if (!$this->cell) {
			$this->cell = $this->worksheet->getCellFeed();			
		}		
	}
		
	##
	public function requireWorksheet() {
		
		##		
		$this->requireWorksheets();

		##		
		if (!$this->worksheet) {
			$this->worksheet = $this->worksheets->getByTitle($this->table);	
		}
	}
	
	##
	public function requireWorksheets() {
	
		##
		$this->requireSpreadsheet();
		    	
		##
		$this->worksheets = $this->spreadsheet->getWorksheets();	
	}
	
	##
	public function requireSpreadsheet() {

		##
		$this->requireDatabase();
		
		##    	
		$this->spreadsheet = $this->gss->getSpreadsheetById($this->databaseid);		    	
	}
		
	##
	public function requireList() {
		
		##
		$this->requireWorksheet();
		
		##
		if (!$this->list) {
			$this->list = $this->worksheet->getListFeed();
		}
	}
	
	##
	public function requireDatabase() {		
		
		##
		if (!$this->database) {
			throw new Exception("GoogleDB-DRIVE require database: use setDatabase(.)");
		}			
	}

	##
	public function requireTable() {
		
		##
		$this->requireDatabase();
		
		##
		if (!$this->table) {
			throw new Exception("GoogleDB-DRIVE require table: use setTable(.)");
		}			
	}
	
}