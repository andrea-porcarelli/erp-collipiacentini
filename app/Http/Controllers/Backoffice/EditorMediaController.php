<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EditorMediaController extends Controller
{
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = validator($request->all(), [
            'upload' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => ['message' => $validator->errors()->first('upload')],
            ], 422);
        }

        $file   = $request->file('upload');
        $userId = Auth::id() ?? 'anon';
        $name   = Str::random(16) . '.' . $file->getClientOriginalExtension();
        $path   = $file->storeAs("editor/{$userId}", $name, 'public');

        return response()->json([
            'url'      => asset('storage/' . $path),
            'uploaded' => 1,
            'fileName' => $file->getClientOriginalName(),
        ]);
    }
}
