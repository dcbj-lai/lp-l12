<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PublicCardController extends Controller
{
    public function show(string $slug)
    {
        $users = User::with('department:id,name')
            ->select(
                'id',
                'name',
                'preferred_name',
                'position',
                'department_id',
                'email',           // REQUIRED
                'phone_work',      // REQUIRED
                'phone_mobile',   // optional
                'address',
                'profile_photo_path',
            )
            ->get();

        $user = $users->first(function ($user) use ($slug) {
            $normalized = Str::slug($user->preferred_name ?: $user->name);
            return $normalized === $slug;
        });

        if (!$user) {
            abort(404);
        }

        $cardUrl = $user->cardUrl();
        $qrImage = base64_encode(QrCode::format('svg')->size(260)->margin(1)->generate($cardUrl));

        return view('public.card', [
            'user' => $user,
            'cardUrl' => $cardUrl,
            'qrImage' => $qrImage,
        ]);
    }

    public function vcard(string $slug)
    {
        $users = User::with('department:id,name')
            ->select(
                'id',
                'name',
                'preferred_name',
                'position',
                'department_id',
                'email',
                'phone_work',
                'phone_mobile'
            )
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

        $lines = [
            "BEGIN:VCARD",
            "VERSION:3.0",
            "N:{$fullName};;;;",
            "FN:{$displayName}",
            "ORG:Life College International",
        ];

        if ($user->position) {
            $lines[] = "TITLE:{$user->position}";
        }

        if ($user->email) {
            $lines[] = "EMAIL;TYPE=INTERNET:{$user->email}";
        }

        if ($user->phone_work) {
            $lines[] = "TEL;TYPE=WORK,VOICE:{$user->phone_work}";
        }

        if ($user->phone_mobile) {
            $lines[] = "TEL;TYPE=CELL:{$user->phone_mobile}";
        }

        $lines[] = "END:VCARD";

        $vcard = implode("\n", $lines);

        return response($vcard, 200)
            ->header('Content-Type', 'text/vcard; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . Str::slug($displayName) . '.vcf"');
    }
}
