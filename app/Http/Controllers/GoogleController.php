<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Directory as GoogleDirectory;
use Illuminate\Support\Facades\Log;
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

    public function googleLogin()
    {
        return Socialite::driver('google')->redirect();
    }
    /**
     * Summary of googleAuthenticate
     * @return void
     * 
     * Description: Authenticate the user Google account
     */
    public function googleAuthenticate()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = User::where('google_id', $googleUser->id)->first();
            $emailParam = $googleUser->email;
            $orgUnit = $this->getOrgUnitPath($emailParam);

            if (str_contains($orgUnit, '/Students')) {
                \Log::warning('Blocked student login', [
                    'email' => $emailParam,
                    'org_unit' => $orgUnit
                ]);
                return redirect()
                    ->route('access.denied')
                    ->with('error', 'Access denied. Students are not allowed.');
            }


            if ($user) {
                if (! $user->is_active) {
                    abort(403, 'Your account is inactive.');
                }

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
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $th) {
            throw $th;
        } catch (\Throwable $th) {
            dd($th);
        }
    }

    private function getOrgUnitPath(string $userEmail): ?string
    {
        try {
            // Load credentials JSON from private S3 disk
            $json = Storage::disk('secure_s3')->get('google/credentials.json');

            $credentials = json_decode($json, true);

            if (!is_array($credentials)) {
                throw new \RuntimeException('Invalid Google credentials JSON');
            }

            $client = new GoogleClient();
            $client->setAuthConfig($credentials);
            $client->setScopes([
                GoogleDirectory::ADMIN_DIRECTORY_USER_READONLY,
            ]);
            $client->setSubject('don.balbieran@laicollege.edu.ph'); // domain-wide delegation

            $directory = new GoogleDirectory($client);
            $user = $directory->users->get($userEmail);

            return $user->orgUnitPath ?? null;

        } catch (\Throwable $e) {
            Log::error('Google org unit lookup failed', [
                'email' => $userEmail,
                'error' => $e->getMessage(),
            ]);

            return null;
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
