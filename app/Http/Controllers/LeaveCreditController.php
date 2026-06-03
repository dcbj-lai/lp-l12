<?php

namespace App\Http\Controllers;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveCreditController extends Controller
{
    public function index(Request $request)
    {
        $users = $this->usersWithCredits($request);

        $totals = [
            'employees' => $users->count(),
            'pto' => $users->sum(fn (User $user) => (float) ($user->requestCredit?->pto ?? 0)),
            'wfh' => $users->sum(fn (User $user) => (float) ($user->requestCredit?->wfh ?? 0)),
        ];

        return view('leave-credits.index', compact('users', 'totals'));
    }

    public function csv(Request $request): StreamedResponse
    {
        $users = $this->usersWithCredits($request);
        $fileName = $this->reportFileName('csv');

        return response()->stream(function () use ($users) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Preferred Name',
                'Email',
                'Department',
                'Position',
                'Leave Credits',
                'WFH Credits',
                'Credits Updated At',
            ]);

            foreach ($users as $user) {
                fputcsv($handle, $this->rowFor($user));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function pdf(Request $request)
    {
        $users = $this->usersWithCredits($request);

        $pdf = Pdf::loadView('leave-credits.report-pdf', [
            'users' => $users,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($this->reportFileName('pdf'));
    }

    protected function usersWithCredits(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        return User::query()
            ->with(['department', 'requestCredit'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('preferred_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereHas('department', fn ($department) => $department->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->get();
    }

    protected function rowFor(User $user): array
    {
        return [
            $user->name,
            $user->preferred_name ?? '-',
            $user->email,
            $user->department?->name ?? '-',
            $user->position ?? '-',
            $this->formatCredit($user->requestCredit?->pto),
            $this->formatCredit($user->requestCredit?->wfh),
            optional($user->requestCredit?->updated_at)->format('Y-m-d H:i') ?? '-',
        ];
    }

    protected function formatCredit(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2);
    }

    protected function reportFileName(string $extension): string
    {
        return 'leave_credits_' . Str::slug(now()->format('Y-m-d_H-i-s')) . '.' . $extension;
    }
}
