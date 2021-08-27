<?php

class Helpers
{


    protected function batchUpdate()
    {
        $response = $this->service->spreadsheets->batchUpdate(
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
    }
}