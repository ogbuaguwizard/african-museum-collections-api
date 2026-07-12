<?php

namespace App\Http\Controllers;

use App\Models\Artifact;
use Illuminate\Http\Request;

class ArtifactController extends Controller
{
    public function index(Request $request)
    {
        $query = Artifact::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereFullText(['title', 'description', 'artist', 'culture', 'period', 'dynasty', 'country'], $search);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        $artifacts = $query->latest()->paginate(20);

        return view('artifacts.index', compact('artifacts'));
    }

    public function show(Artifact $artifact)
    {
        return view('artifacts.show', compact('artifact'));
    }
}