<?php

namespace Javanile\Sheetbase\Drivers\Google;

use Javanile\Sheetbase\DriverInterface;

/**
 *
 */
class Google implements DriverInterface
{
    /**
     * @var mixed|null
     */
	private $databases = null;

    /**
     * @var null
     */
    private $currentDatabase = null;

    /**
     * @var null
     */
	private $currentSpreadsheetId = null;


	##
	private $table = null;
	
	##	
	private $worksheet = null;

    /**
     * @var null
     */
	private $sheets = null;

	##
	private $cell = null;
	
	##
	private $list = null;

    /**
     * @throws \Exception
     */
    public function __construct($args)
    {
		$this->gc = new \Google\Client();

		if (isset($args['p12_file'])) {
		    $this->authWithP12();
        } elseif (isset($args['json_file']) && file_exists($args['json_file'])) {
            $this->gc->setAuthConfig($args['json_file']);
        } else {
		    throw new \Exception('Provide valid credentials');
        }

        $this->gc->setApplicationName("Client_Library_Examples");
        $this->gc->setScopes([
            'https://spreadsheets.google.com/feeds',
            "https://www.googleapis.com/auth/drive",
            "https://www.googleapis.com/auth/drive.file",
            "https://www.googleapis.com/auth/drive.readonly",
            "https://www.googleapis.com/auth/drive.metadata.readonly",
            "https://www.googleapis.com/auth/drive.appdata",
            "https://www.googleapis.com/auth/drive.apps.readonly",
            "https://www.googleapis.com/auth/drive.metadata",
        ]);

		$this->gss = new \Google\Service\Sheets($this->gc);
		$this->databases = $args['database'];
	}

    /**
     * @param $database
     */
    public function addDatabase($database)
    {
        $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
            'properties' => [
                'title' => $database
            ]
        ]);
        $spreadsheet = $this->gss->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);
    }

	##
	public function setDatabase($database)
    {
        if (empty($database)) {
            throw new \Exception('Invalid database name');
        }

        if ($database == $this->currentDatabase) {
            return $database;
        }

        if (!$this->hasDatabase($database)) {
			throw new \Exception('No database found: "'.$database.'"');
		}

        $this->currentDatabase = $database;
        $this->currentSpreadsheetId = $this->getSpreadsheetId($database);
        $this->table = null;
        $this->spreadsheet = null;
        $this->worksheets = null;
        $this->worksheet = null;
        $this->cell = null;
        $this->list = null;
	}

    /**
     * @param $name
     * @return bool
     */
	public function hasDatabase($name)
    {
		return isset($this->databases[$name]);
	}

    /**
     * @param $name
     * @return mixed
     */
	public function getSpreadsheetId($name)
    {
		return $this->databases[$name];		
	}

    /**
     * @param $spreadsheetId
     * @return int|string|void
     */
	protected function getDatabaseBySpreadsheetId($spreadsheetId)
    {
		foreach ($this->databases as $database => $id) {
			if ($id == $spreadsheetId) {
				return $database;
			}			
		}		
	}

    /**
     * @param $table
     * @param int $cols
     * @param null $rows
     *
     * @throws \Exception
     */
	public function addTable($table, $cols = 10, $rows = null)
    {
		if ($this->hasTable($table)) {
			throw new \Exception('Table already exists: "'.$table.'"');
		}

		$this->requireSpreadsheet();
				
		$fields = array();
		if (is_array($cols)) {
			foreach($cols as $col) {
				$fields[] = strtolower($col);
			}		
			$cols = count($fields);			
			$rows = $rows ? $rows : 1;
		} else {
			$rows = $rows ? $rows : 10;			
		}

        $response = $this->gss->spreadsheets->batchUpdate(
            $this->currentSpreadsheetId,
            new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [
                    /*
                    new \Google\Service\Sheets\Request([
                        'updateSpreadsheetProperties' => [
                            'properties' => [
                                'title' => $title
                            ],
                            'fields' => 'title'
                        ]
                    ]),
                    new \Google\Service\Sheets\Request([
                        'findReplace' => [
                            'find' => $find,
                            'replacement' => $replacement,
                            'allSheets' => true
                        ]
                    ])
                    */
                    new \Google\Service\Sheets\Request([
                        "addSheet" => [
                            "properties" => [
                                "title" => "Deposits",
                                "gridProperties" => [
                                    "rowCount" => 20,
                                    "columnCount" => 12
                                ],
                                "tabColor" => [
                                    "red" => 1.0,
                                    "green" => 0.3,
                                    "blue" => 0.4
                                ]
                            ]
                        ]
                    ])
                ]
            ]
        ));

		/*
		if (count($fields)>0) {
			$feed = $worksheet->getCellFeed();
			foreach($fields as $c=>$field) {
				$feed->editCell(1, $c+1,$field);
			} 			
		}
		*/

		//
		$this->sheets = null;
	}

    /**
     * @param $name
     * @return false
     */
	public function hasTable($name)
    {
		$this->requireSheets();
		//return (boolean) $this->worksheets->getByTitle($name);
        return false;
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

    /**
     * @return mixed|void
     */
	public function getTables()
    {
		$this->requireSheets();

		$tables = array();
		foreach($this->sheets as $sheet) {
			$tables[] = strtolower($sheet->getTitle());
		}

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
	public function search($query,$col=1)
    {
		
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

    /**
     * @param $row
     */
	public function insert($row)
    {
        $this->requireDatabase();
		//$this->requireList();
        $range = 'principale!A1:E1';  // TODO: Update placeholder value.
        $requestBody = new \Google\Service\Sheets\ValueRange();

        $response = $this->gss->spreadsheets_values->append($this->currentSpreadsheetId, $range, $requestBody, [
            'valueInputOption' => 'RAW'
        ]);

        echo '<pre>', var_export($response, true), '</pre>', "\n";
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
	public function requireSheets()
    {
	
		##
		$this->requireSpreadsheet();
		    	
		##
		$this->sheets = $this->getSheets();
	}

    /**
     *
     */
	protected function getSheets()
    {
        $response = $this->gss->spreadsheets->get($this->currentSpreadsheetId);

        return $response->sheets;
    }

    /**
     *
     */
	public function requireSpreadsheet()
    {
		$this->requireDatabase();
		$this->spreadsheet = $this->gss->spreadsheets->get($this->databaseid);
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

    /**
     *
     * @throws \Exception
     */
	public function requireDatabase()
    {
		if (empty($this->currentDatabase)) {
			throw new \Exception("GoogleDB-DRIVE require database: use setDatabase(.)");
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
