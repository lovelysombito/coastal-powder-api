<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Http\Requests\SearchRequest;

class SearchController extends Controller
{
    public function search(SearchRequest $request)
    {
        $results = Job::search($request->search)->get();
        $count = $results->count();

        return response()->json(['results'=>$results, 'count'=>$count], 200);
    }
}