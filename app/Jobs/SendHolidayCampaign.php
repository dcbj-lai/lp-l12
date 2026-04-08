<?php

namespace App\Jobs;

use App\Models\HolidayCampaign;
use App\Models\User;
use App\Services\HtmlPersonalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendHolidayCampaign implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public HolidayCampaign $campaign;
    public array $emails;

    public function __construct(HolidayCampaign $campaign, array $emails)
    {
        $this->campaign = $campaign;
        $this->emails = $emails;
    }

    public function handle(HtmlPersonalizer $personalizer): void
    {
        $users = User::whereIn('email', $this->emails)
            ->get()
            ->keyBy('email');

        // ✅ Extract once (avoid closure issues)
        $fromEmail = $this->campaign->from_email ?? config('mail.from.address');
        $fromName = $this->campaign->from_name ?? config('mail.from.name');
        $subject = $this->campaign->subject ?? 'Greeting from Life College';

        foreach ($this->emails as $email) {

            $user = $users->get($email);

            $data = [
                'name' => $user?->preferred_name
                    ?? $user?->name
                    ?? 'Valued Member',
            ];

            $html = $personalizer->process($this->campaign->html, $data);

            Mail::send([], [], function ($message) use ($email, $html, $fromEmail, $fromName, $subject) {

                $message->to($email)
                    ->from($fromEmail, $fromName)
                    ->subject($subject)
                    ->html($html);
            });
        }
    }
}
