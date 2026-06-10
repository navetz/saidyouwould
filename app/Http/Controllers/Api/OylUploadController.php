<?php

namespace App\Http\Controllers\Api;

use App\Contracts\VideoStorage;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OylUploadController extends Controller
{
    public function store(Request $request, VideoStorage $videos): JsonResponse
    {
        $maximumBytes = config('one-year-later.max_video_size_kb') * 1024;
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', Rule::in(config('one-year-later.allowed_video_types'))],
            'size' => ['required', 'integer', 'min:1', 'max:'.$maximumBytes],
        ], [
            'content_type.in' => 'Upload an MP4, MOV, M4V, or WebM video.',
            'size.max' => 'The video must be smaller than '.round($maximumBytes / 1024 / 1024).' MB.',
        ]);

        return response()->json($videos->createUpload(
            $validated['filename'],
            $validated['content_type'],
            $validated['size'],
        ), 201);
    }
}
