<?php

namespace App\Http\Controllers;

use App\Models\SearchFilter;
use Exception;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Search;

class SearchFilterController extends Controller
{
    public function addFilter(Request $request)
    {
        /* Receive an array of data */
        if(empty($request->data)){
            throw new Exception('No data to process');
        }

        $data = $request->data;

        /* Delete all data under the table_name */
        $getFilters = SearchFilter::where("table_name", $data[0]['table_name'])->get();

        if(count($getFilters) > 0){
            for ($filter_index=0; $filter_index < count($getFilters); $filter_index++) { 
                $getFilters[$filter_index]->delete();
            }
        }

        /* Loop the array to save a new set of filters */
        for ($data_index=0; $data_index < count($data); $data_index++) { 

            $order = $data[$data_index]["order"];
            $column_type = $data[$data_index]["column_type"];
            $table_name = $data[$data_index]["table_name"];
            $column_value = $data[$data_index]["column_value"];
            $operator = $data[$data_index]['operator'];
            $where_type = $data[$data_index]["where_type"];

            if(!$order)
                throw new Exception('Order # cannnot be empty');
            if(!$column_type)
                throw new Exception('Column Type cannnot be empty');
            if(!$table_name)
                throw new Exception('Table Name cannnot be empty');
            if(!$column_value)
                throw new Exception('Column Value cannnot be empty');
            if(!$operator)
                throw new Exception('Operator cannnot be empty');

            /* Check if the filter exists to avoid duplication */
            $searchFilter = SearchFilter::where([
                "table_name" => $table_name,
                "column_type" => $column_type,
                "operator" => $operator
            ])->first();

            /* If yes, update */
            if($searchFilter){
                $searchFilter->order = $order;
                $searchFilter->column_value = $column_value;
                $searchFilter->update();
            }

            /* If not create a new one */
            $newFilter = new SearchFilter([
                'order' => $order,
                'column_type' => $column_type,
                'table_name' => $table_name,
                'column_value' => $column_value,
                'operator' => $operator,
                'where_type' => $where_type,
            ]);
            $newFilter->save();
    
        }

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => 'Filter successfully processed.',
        ], 200);

    }

    public function getFilterByTable(Request $request)
    {
        if(!$request->table_name)
            throw new Exception('Table name cannnot be empty');

        $searchFilter = SearchFilter::where("table_name", $request->table_name)
                                        ->orderBy("order")
                                        ->get();

        if(count($searchFilter) > 0){
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'data' => $searchFilter
            ], 200);
        }

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => []
        ], 200);
        
    }
}
