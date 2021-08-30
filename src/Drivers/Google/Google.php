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
    private $database = null;

    /**
     * @var null
     */
	private $spreadsheetId = null;

    /**
     * @var null
     */
    private $spreadsheet = null;

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
    protected $sheetTitle = null;

    /**
     * @var null
     */
	protected $sheets = null;

    /**
     * @var null
     */
    protected $tables = null;

    /**
     * @var null
     */
    protected $zeroBased = null;

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
        $this->zeroBased = isset($args['zero_based']) ? boolval($args['zero_based']) : true;
	}

    /**
     * @param $database
     */
    public function addDatabase($database)
    {
        if ($this->hasDatabase($database)) {
            throw new \Exception("Database with name '{$database}' already exists.");
        }

        $spreadsheet = new \Google\Service\Sheets\Spreadsheet([
            'properties' => [
                'title' => $database
            ]
        ]);

        $response = $this->service->spreadsheets->create($spreadsheet, [
            'fields' => 'spreadsheetId'
        ]);

        $this->databases[$database] = $response->spreadsheetId;

        return $response->spreadsheetId;
    }

	##
	public function setDatabase($database)
    {
        if (empty($database)) {
            throw new \Exception('Invalid database name');
        }

        if ($database == $this->database) {
            return $database;
        }

        if (!$this->hasDatabase($database)) {
			throw new \Exception('No database found: "'.$database.'"');
		}

        $this->database = $database;
        $this->spreadsheetId = $this->getSpreadsheetId($database);
        $this->spreadsheet = null;
        $this->table = null;
        $this->tables = null;
        $this->sheet = null;
        $this->sheets = null;
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
            $this->spreadsheetId,
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

    /**
     * @param $row
     * @param $col
     * @param $value
     * @return mixed|void
     */
	public function set($row, $col, $value)
    {
        if (!$this->zeroBased) {
            $row = $row - 1;
            $col = $col - 1;
        }

        $this->requireSheetTitle();
        $range = '\''.$this->sheetTitle.'\'!R['.$row.']C['.$col.']';
        $requestBody = new \Google\Service\Sheets\ValueRange(['values' => [[$value]]]);
        $response = $this->service->spreadsheets_values->update($this->spreadsheetId, $range, $requestBody, [
            'valueInputOption' => 'RAW'
        ]);

        //@TODO: evaluate escapes problems, below line was an escapes for '"' and '&'
        //$this->cell->editCell($row, $col, strtr($value,'"&',' -'));

        //echo '<pre>', var_export($response, true), '</pre>', "\n";

        return $response->updatedCells;
	}

    /**
     * @param $row
     * @param $col
     *
     * @return mixed|string
     */
	public function get($row, $col)
    {
        if (!$this->zeroBased) {
            $row = $row - 1;
            $col = $col - 1;
        }

        $this->requireSheetTitle();

        $range = '\''.$this->sheetTitle.'\'!R['.$row.']C['.$col.']';
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);

        //echo '<pre>', var_export($response->values, true), '</pre>', "\n";

        return $response->values[0][0];
	}

    /**
     * @param $query
     * @param int $col
     * @return int|mixed
     */
	public function search($query, $col = 1)
    {
		if (is_array($query) || is_object($query)) {
            return $this->searchByQuery($query, $col = 1);
        }

        $this->requireSheetTitle();
		$columnLetter = self::columnLetter($col);
        $range = '\''.$this->sheetTitle.'\'!'.$columnLetter.'2:'.$columnLetter;
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);

        //echo '<pre>', var_export($response->values, true), '</pre>', "\n";
        foreach ($response->values as $rowIndex => $row) {
            if ($query == $row[0]) {
                return $this->getRow();
            }
        }
	}

    /**
     *
     */
	protected function searchByQuery($query, $col = 1)
    {
        $this->requireCell();

        for($row=2; $row<=$this->worksheet->getRowCount(); $row++) {
            $exit = true;
            foreach($query as $key=>$value) {
            }
            if ($exit) {
                return $row;
            }
        }
    }

	/**
     *
     */
	public function column($column, $searchColumn = null, $searchValue = null)
    {
        if ($searchColumn === null) {
            return $this->getColumn($column);
        }

		$this->requireCell();
		$this->requireWorksheet();
    	$data = array();
    	$rowCount = $this->getRowCount();
    	for ($row=2; $row<=$rowCount; $row++) {
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

        $response = $this->service->spreadsheets_values->append($this->spreadsheetId, $range, $requestBody, [
            'valueInputOption' => 'RAW'
        ]);

        //echo '<pre>', var_export($response, true), '</pre>', "\n";
	}

    /**
     * @return array
     *
     * @throws \Exception
     */
	public function all()
    {
        $this->requireSheetTitle();

        $range = $this->sheetTitle;
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);

        //echo '<pre>', var_export($response->values, true), '</pre>', "\n";

        return $this->zeroBased ? $response->values : self::transformMatrixToOneBased($response->values);
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
     * @param $query
     * @param int $col
     * @return int|mixed
     */
    public function getRow($row)
    {
        $this->requireSheetTitle();
        $range = '\''.$this->sheetTitle.'\'!'.$row.':'.$row;
        $response = $this->service->spreadsheets_values->get($this->spreadsheetId, $range);

        return $response->values[0];
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
        $response = $this->service->spreadsheets->get($this->spreadsheetId);

        return $response->sheets;
    }

    /**
     *
     * @throws \Exception
     */
	public function requireSpreadsheet()
    {
		$this->requireDatabase();
		$this->spreadsheet = $this->service->spreadsheets->get($this->spreadsheetId);
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
		if (empty($this->database)) {
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

    /**
     * @param $col
     * @return string
     */
    protected static function columnLetter($col)
    {
        $col = intval($col);
        if ($col <= 0) {
            return '';
        }

        $letter = '';
        while ($col != 0) {
            $p = ($col - 1) % 26;
            $col = intval(($col - $p) / 26);
            $letter = chr(65 + $p) . $letter;
        }

        return $letter;
    }

    /**
     * @param $values
     */
    protected static function transformMatrixToOneBased($values)
    {
        $index = 1;
        $newValues = [];
        foreach ($values as $row) {
            $newValues[$index] = self::transformArrayToOneBased($row);
            $index++;
        }

        return $newValues;
    }

    /**
     * @param $values
     */
    protected static function transformArrayToOneBased($values)
    {
        $index = 1;
        $newValues = [];
        foreach ($values as $value) {
            $newValues[$index] = $value;
            $index++;
        }

        return $newValues;
    }
}
