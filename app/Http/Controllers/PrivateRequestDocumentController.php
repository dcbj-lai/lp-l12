<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        /*
         |-------------------------------------------------
         | Authorization
         |-------------------------------------------------
         | HR / PNC can view all
         | Request owner can view own proof
         */
        $isPnc = Gate::allows('is-pnc');

        if (!$isPnc) {
            // Check ownership via DB
            $owns = StaffRequest::where('offset_proof_path', $path)
                ->where('user_id', $user->id)
                ->exists();

            if (!$owns) {
                abort(403);
            }
        }

        $disk = Storage::disk('private_s3');

        if (!$disk->exists($path)) {
            abort(404);
        }

        $stream = $disk->readStream($path);

        $mime = Storage::mimeType($path) ?? 'application/octet-stream';

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
