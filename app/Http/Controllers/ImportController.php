<?php

namespace App\Http\Controllers;

use App\Models\ImportProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    protected $token;

    public function __construct()
    {
        $this->token = config('app.import_token');
    }

    public function trigger(Request $request)
    {
        if ($request->query('token') !== $this->token) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        $existing = ImportProgress::where('source', 'met')
            ->whereIn('status', ['pending', 'running'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Import already in progress.',
                'status' => $existing->status,
                'progress' => $existing->processed_objects . '/' . $existing->total_objects,
            ]);
        }

        $limit = $request->query('limit', 1000);
        $offset = $request->query('offset', 0);

        $artisanPath = base_path('artisan');
        $logPath = storage_path('logs/import.log');
        $command = sprintf(
            'cd %s && php %s import:met --limit=%d --offset=%d > %s 2>&1 &',
            base_path(),
            $artisanPath,
            $limit,
            $offset,
            $logPath
        );

        Log::info('Triggering import batch: limit=' . $limit . ', offset=' . $offset);
        exec($command, $output, $returnCode);

        return response()->json([
            'message' => 'Import batch started.',
            'limit' => $limit,
            'offset' => $offset,
            'status_url' => url('/import/status?token=' . $this->token),
        ]);
    }

    public function status(Request $request)
    {
        if ($request->query('token') !== $this->token) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        $progress = ImportProgress::where('source', 'met')->orderBy('id', 'desc')->first();

        if (!$progress) {
            return response()->json(['status' => 'no_import']);
        }

        return response()->json([
            'status' => $progress->status,
            'total_objects' => $progress->total_objects,
            'processed_objects' => $progress->processed_objects,
            'imported_objects' => $progress->imported_objects,
            'skipped_objects' => $progress->skipped_objects,
            'failed_objects' => $progress->failed_objects,
            'progress_percentage' => $progress->total_objects > 0
                ? round(($progress->processed_objects / $progress->total_objects) * 100, 1)
                : 0,
            'started_at' => $progress->started_at,
            'completed_at' => $progress->completed_at,
            'error_message' => $progress->error_message,
        ]);
    }

    public function reset(Request $request)
    {
        if ($request->query('token') !== $this->token) {
            return response()->json(['error' => 'Invalid token'], 403);
        }

        ImportProgress::where('source', 'met')->delete();
        return response()->json(['message' => 'Import progress reset.']);
    }
}