<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facades\App\Services\CsvUpload;
use App\Events\ImportCsvData;

class ApiController extends Controller
{
    public function storeCsvData(Request $request)
    {
        if(!request()->file('file')) {
            $data = [
                'success'       => true, 
                'message'       => "Please upload a file",
                'status_code'   => 200,
            ];
            return response()->json($data);
        }
        $extension = strtolower(request()->file('file')->getClientOriginalExtension());
        if ($extension != 'csv') {
            $data = [
                'success'       => true, 
                'message'       => "Please upload csv file",
                'status_code'   => 200,
            ];
            return response()->json($data);
        }
        $path = request()->file('file')->storeAs('public/csv_upload', 'modules-'.date("m-d-y").'.'.$extension);

        $path = storage_path('app/'.$path);

        $view = [];
        $count = session('count_add');

        $data = CsvUpload::importData($path,$count);  
        $csvData = $data['csvData'];
        $errors = CsvUpload::validateCsvColumns($path, $csvData);
        if ($errors != "") {
            $data = [
                'success'       => true, 
                'message'       => $errors,
                'status_code'   => 200,
            ];
            return response()->json($data);
        }
        if ($csvData) {
            $errors = CsvUpload::validateFields($csvData);
            $errors = array_filter($errors);
            if (!empty($errors)) {
                $data = [
                'success'       => true, 
                'message'       => $errors,
                'status_code'   => 200,
            ];
            return response()->json($data);
            }
            $importedData = $csvData;
            $count = count($csvData);
        }
        event(new ImportCsvData($importedData));

        $data = [
            'success'       => true, 
            'message'       => 'Data imported successfully!',
            'status_code'   => 200,
        ];
        return response()->json($data);
    }
}
