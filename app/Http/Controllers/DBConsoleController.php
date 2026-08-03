<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DBConsoleController extends Controller
{
    public function index()
    {
        return view('dev-tools.db-console', [
            'output' => null,
            'query' => '',
            'devToolsToken' => request()->query('dt', ''),
        ]);
    }

    public function run(Request $request)
    {
        $query = $request->input('query');
        try {
            if (stripos($query, 'select') === 0 || stripos($query, 'show') === 0) {
                $output = DB::select($query);
            } else {
                $output = DB::statement($query);
            }
        } catch (\Throwable $e) {
            $output = $e->getMessage();
        }

        return view('dev-tools.db-console', [
            'output' => $output,
            'query' => $query,
            'devToolsToken' => $request->input('dt', ''),
        ]);
    }
}
