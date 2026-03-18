<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserSearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $users = User::where('id', '!=', auth()->id()) 
            ->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('institution', 'like', "%{$query}%")
                  ->orWhere('major', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'institution', 'major')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}