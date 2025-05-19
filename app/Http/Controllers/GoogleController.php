<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Summary of googleLogin
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     * 
     * Description: This function redirects to Google
     */

     public function googleLogin() {
        return Socialite::driver('google')->redirect();
    }
    /**
     * Summary of googleAuthenticate
     * @return void
     * 
     * Description: Authenticate the user Google account
     */
    public function googleAuthenticate() {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $user = User::where('google_id', $googleUser->id)->first();
            // dd($googleUser);

            if ($user) {
                Auth::login($user);
                return redirect()->route('dashboard');
            } else {
                $userData = User::create([
                    'name' => $googleUser->name,
                    'google_id' => $googleUser->id,
                    'email' => $googleUser->email,
                    'password' => Hash::make('1234567890'),
                    'supervisor_id' => config('app.superuser_id'),
                ]);
                if ($userData) {
                    Auth::login($userData);
                    return redirect()->route('dashboard');
                }
            }
            } catch (\Throwable $th) {
                dd($th);
            }
    }

    public function showForm()
    {
        return view('google.credentials');
    }

    public function upload(Request $request)
{
    $request->validate([
        'credentials_json' => 'required|file|mimes:json,txt',
    ]);

    $file = $request->file('credentials_json');

    $fixedFileName = 'google-service-account-key.json';
    $targetPath = "google-calendar/{$fixedFileName}";

    Storage::put($targetPath, file_get_contents($file));

    return back()->with('success', 'Google credentials uploaded and overwritten successfully.');
}

}
