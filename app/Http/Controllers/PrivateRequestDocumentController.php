<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Request as StaffRequest;

class PrivateRequestDocumentController extends Controller
{
    public function show(Request $request, string $path)
    {
        $path = ltrim($path, '/');

        // Must live under requests/
        $parts = explode('/', $path);
        if (count($parts) < 2 || $parts[0] !== 'requests') {
            abort(404);
        }

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $staffRequest = StaffRequest::with('user')
            ->where('offset_proof_path', $path)
            ->first();

        abort_unless($staffRequest?->canViewOffsetProof($user), 403);

        $disk = Storage::disk('private_s3');

        if (!$disk->exists($path)) {
            abort(404);
        }

        $stream = $disk->readStream($path);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        return response()->stream(
            fn() => fpassthru($stream),
            200,
            [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            ]
        );
    }
}
