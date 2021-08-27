<?php

namespace Javanile\Sheetbase\Drivers\Google;

use Javanile\Sheetbase\DriverInterface;

/**
 *
 */
class Google implements DriverInterface
{
    /**
     *
     */
    protected $client;

    /**
     *
     */
    protected $service;

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

    /**
     * @var null
     */
	private $table = null;

    /**
     * @var null
     */
	private $sheet = null;

    /**
     * @var null
     */
    private $sheetTitle = null;

    /**
     * @var null
     */
	private $sheets = null;

    /**
     * @var null
     */
    private $tables = null;

	##
	private $cell = null;
	
	##
	private $list = null;

    /**
     * @throws \Exception
     */
    public function __construct($args)
    {
		$this->client = new \Google\Client();

		if (isset($args['p12_file'])) {
		    $this->authWithP12();
        } elseif (isset($args['json_file']) && file_exists($args['json_file'])) {
            $this->client->setAuthConfig($args['json_file']);
        } else {
		    throw new \Exception('Provide valid credentials');
        }

        $this->client->setApplicationName("Client_Library_Examples");
        $this->client->setScopes([
            'https://spreadsheets.google.com/feeds',
            "https://www.googleapis.com/auth/drive",
            "https://www.googleapis.com/auth/drive.file",
            "https://www.googleapis.com/auth/drive.readonly",
            "https://www.googleapis.com/auth/drive.metadata.readonly",
            "https://www.googleapis.com/auth/drive.appdata",
            "https://www.googleapis.com/auth/drive.apps.readonly",
            "https://www.googleapis.com/auth/drive.metadata",
        ]);

		$this->service = new \Google\Service\Sheets($this->client);
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
        $spreadsheet = $this->service->spreadsheets->create($spreadsheet, [
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

        $response = $this->service->spreadsheets->batchUpdate(
            $this->currentSpreadsheetId,
            new \Google\Service\Sheets\BatchUpdateSpreadsheetRequest([
                'requests' => [
                    new \Google\Service\Sheets\Request([
                        "addSheet" => [
                            "properties" => [
                                "title" => $table,
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
     *
     * @return boolean
     *
     * @throws \Exception
     */
	public function hasTable($name)
    {
        $this->requireTables();

        return in_array($name, $this->tables);
	}

    /**
     * @param $table
     * @return mixed|void
     */
	public function setTable($table)
    {
		if ($table == $this->table) {
            return;
		}

		if (!$this->hasTable($table)) {
            throw new \Exception("Table or Sheet with name '{$table}' not exists.");
        }

        $this->table = $table;
        $this->sheet = null;
        $this->cell = null;
        $this->list = null;
 	}

    /**
     * @return mixed|void
     *
     * @throws \Exception
     */
	public function getTables()
    {
		$this->requireSheets();

		$tables = array();
		foreach ($this->sheets as $sheet) {
			$tables[] = strtolower($sheet->getProperties()->getTitle());
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
			for ($row=2; $row<=$this->currentTable->getRowCount(); $row++) {

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
     * @throws \Exception
     */
	public function insert($row)
    {
        $this->requireSheet();

        $sheetTitle = $this->sheet->getProperties()->getTitle();

        $range = $sheetTitle.'!A:A';
        $requestBody = new \Google\Service\Sheets\ValueRange([
            'values' => [
                $row
            ]
        ]);

        $response = $this->service->spreadsheets_values->append($this->currentSpreadsheetId, $range, $requestBody, [
            'valueInputOption' => 'RAW'
        ]);

        //echo '<pre>', var_export($response, true), '</pre>', "\n";
	}

    /**
     * @return array
     */
	public function all()
    {
        $this->requireSheetTitle();

        $range = $this->sheetTitle.'!A1:Z10';
        $response = $this->service->spreadsheets_values->get($this->currentSpreadsheetId, $range);

        //echo '<pre>', var_export($response, true), '</pre>', "\n";

        return $response->values;
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

    /**
     * @return int
     */
	public function getRowCount()
    {
		$this->requireSheet();

		return (int) $this->sheet->getProperties()->getGridProperties()->rowCount;
	}

    /**
     * @return int
     */
	public function getColCount()
    {
        $this->requireSheet();

        return (int) $this->sheet->getProperties()->getGridProperties()->columnCount;
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

    /**
     * @throws \Exception
     */
    protected function requireSheets()
    {
		$this->requireDatabase();

		$this->sheets = $this->getSheets();
	}


    /**
     *
     * @throws \Exception
     */
	protected function requireTables()
    {
        if ($this->tables !== null) {
            return;
        }

        $tables = [];
        $this->requireSheets();
        foreach ($this->sheets as $sheet) {
            $tables[] = strtolower($sheet->getProperties()->getTitle());
        }

        $this->tables = $tables;
    }

    /**
     *
     */
	protected function getSheets()
    {
        $response = $this->service->spreadsheets->get($this->currentSpreadsheetId);

        return $response->sheets;
    }

    /**
     *
     * @throws \Exception
     */
	public function requireSpreadsheet()
    {
		$this->requireDatabase();
		$this->spreadsheet = $this->service->spreadsheets->get($this->currentSpreadsheetId);
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

    /**
     * @throws \Exception
     */
	protected function requireTable()
    {
		$this->requireDatabase();
		if (empty($this->table)) {
			throw new Exception("GoogleDB-DRIVE require table: use setTable(.)");
		}			
	}

    /**
     * @throws \Exception
     */
    protected function requireSheet()
    {
        if ($this->sheet !== null) {
            return;
        }

        $this->requireTable();
        $this->requireSheets();
        foreach ($this->sheets as $sheet) {
            if ($this->table == strtolower($sheet->getProperties()->getTitle())) {
                $this->sheet = $sheet;
            }
        }
    }

    /**
     * @throws \Exception
     */
    protected function requireSheetTitle()
    {
        if ($this->sheetTitle !== null) {
            return;
        }

        $this->requireSheet();
        $this->sheetTitle = $this->sheet->getProperties()->getTitle();
    }
}
