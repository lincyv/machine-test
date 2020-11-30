<?php

namespace App\Services;

use Validator;

class CsvUpload
{
    public function importData($path, $count)
    {
        $extention = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $data = $this->getItems($path);
        $items = $data['csvData'];
        $csvData['error'] = $data['error'];
        $csvData['csvData'] = [];
        if (!empty($items)) {

            foreach ($items as $key => $item) {

                if (!count($item)) {
                    continue;
                }
                //skip empty columns
                $count++;
                $csvData['csvData'][] = $this->generateCSVData($item, $count);

            }
        }
        return $csvData;
    }

    public function getItems($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        $items = $this->csvToArray($path);
                
        if (empty($items)) {
            return false;
        }
        return $items;
    }

    public function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            return false;
        }

        $header = null;
        $data = array();
        $data['error'] = '';
        $data['csvData'] = array();
        if (($handle = fopen($filename, 'r')) !== false) {
            $i = 0;
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $i++;
                if (!$header) {
                    $header = $row;
                } else {
                    if (count($header) == count($row)) {
                        $data['csvData'][] = array_combine($header, $row);
                    }
                    if (count($header) != count($row)) {
                        $data['error'] = 'Please make sure the file headers matching with their values';
                    }
                }
            }
            fclose($handle);
        }
        return $data;
    }

    public function generateCSVData($item, $count)
    {
        $headerMap = Config('csv-upload.csvData');
        if (empty($item)) {
            return false;
        }

        $csvData = [];
        foreach ($headerMap as $dbField => $fileHeader) {            
            $csvData[$dbField] = !empty($item[$fileHeader]) ? $item[$fileHeader] : null;
        }

        $csvData['count_add'] = $count++;
        session(['count_add' => $csvData['count_add']]);

        return compact('csvData');
    }

    public function validateCsvColumns($path, $csvData)
    {
        $headerMap = Config('csv-upload.csvData');
        $headerKeys = array_values($headerMap);
        $csv = array_map("str_getcsv", file($path,FILE_SKIP_EMPTY_LINES));
        $csvKeys = array_shift($csv);

        $result = [];

        if (empty($csvData)) {
            return 'Please make sure the file contains data';
        }
        $messages = $this->checkFileDifference($csvKeys, $headerKeys);
        return $messages;
    }

    public function checkFileDifference($csvKeys, $headerKeys)
    {
        if (count($headerKeys) >= count($csvKeys)) {
            $result = array_diff($headerKeys, $csvKeys);
            $messageString = 'Please make sure the column names are spelled correctly and the file is comma separated . Columns missing - ';
        } else {
            $result = array_diff($csvKeys, $headerKeys);
            $messageString = 'Uploaded file is not match with template file. Extra columns - ';
        }
        $messages = '';

        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $messageString .= $value . ', ';
            }
            $messages = rtrim($messageString, ", ");
        }
        return $messages;
    }

    public function validateFields($csvData)
    {
        $count = 2;
        foreach ($csvData as $data) {
            $errors[] = $this->csvValidator($data['csvData'], $count++);
        }
        return $errors;
    }

    public function csvValidator($data, $count)
    {
        $validator = Validator::make($data, [
            'module_code' => 'required|alpha_num',
            'module_name' => 'required',
            'module_term' => 'required|alpha_num'
        ],
        $messages = [
            'module_code.required' => 'Module Code is required at row '.$count,
            'module_code.alpha_num' => 'Module Code contains symbols at row '.$count,
            'module_name.required' => 'Module Name is required at row '.$count,
            'module_term.required' => 'Module Term is required at row '.$count,
            'module_term.alpha_num' => 'Module Term contains symbols at row '.$count
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }
    }

}