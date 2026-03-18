<?php

namespace App\Http\Controllers;

use App\Models\SkillCategory;

class SkillCategoryController extends Controller
{
    public function index()
    {
        $categories = SkillCategory::all();

        return response()->json([
            'success' => true,
            'message' => 'List skill category',
            'data' => $categories
        ]);
    }
}