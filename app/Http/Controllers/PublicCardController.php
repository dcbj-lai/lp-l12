<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;

class PublicCardController extends Controller
{
    public function show(string $slug)
    {
        $users = User::with('department:id,name')
            ->select('id', 'name', 'preferred_name', 'position', 'department_id', 'profile_photo_path')
            ->get();

        $user = $users->first(function ($user) use ($slug) {
            $normalized = Str::slug($user->preferred_name ?: $user->name);
            return $normalized === $slug;
        });

        if (!$user) {
            abort(404);
        }

        return view('public.card', [
            'user' => $user,
        ]);
    }

    public function vcard(string $slug)
    {
        $users = User::with('department:id,name')
            ->select('id', 'name', 'preferred_name', 'position', 'department_id')
            ->get();

        $user = $users->first(function ($user) use ($slug) {
            $normalized = Str::slug($user->preferred_name ?: $user->name);
            return $normalized === $slug;
        });

        if (!$user) {
            abort(404);
        }

        $fullName = $user->name;
        $displayName = $user->preferred_name ?: $user->name;

        $vcard = <<<VCARD
BEGIN:VCARD
VERSION:3.0
N:{$fullName};;;;
FN:{$displayName}
ORG:Life College International
TITLE:{$user->position}
EMAIL;TYPE=INTERNET:{$user->email}
END:VCARD
VCARD;

        return response($vcard, 200)
            ->header('Content-Type', 'text/vcard; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $displayName . '.vcf"');
    }
}
