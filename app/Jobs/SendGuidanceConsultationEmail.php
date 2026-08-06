<?php

namespace App\Jobs;

use App\Mail\StudentGoHomeMail;
use App\Mail\StudentResumeClassMail;
use App\Models\Consultation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendGuidanceConsultationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $consultationId)
    {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        $consultation = Consultation::with('client')->find($this->consultationId);

        if (! $consultation) {
            return;
        }

        try {
            $client = $consultation->client;

            if (! $client) {
                throw new RuntimeException('The Guidance consultation client no longer exists.');
            }

            $teacherRecipients = array_values(array_unique(array_filter([
                $consultation->check_in_teacher_email,
                $consultation->teacher_email,
            ])));

            $ccRecipients = array_values(array_unique(array_filter([
                config('mail.guidance_recipients.guidance'),
                config('mail.guidance_recipients.academic_council'),
                $client->isUnderAccessibility()
                    ? config('mail.guidance_recipients.sas')
                    : null,
            ])));

            $to = $teacherRecipients ?: $ccRecipients;

            if ($to === []) {
                throw new RuntimeException('No Guidance consultation email recipients are configured.');
            }

            $dateDisplay = $consultation->time_in
                ? Carbon::parse($consultation->time_in)->timezone('Asia/Manila')->format('M d, Y')
                : 'N/A';

            $timeInDisplay = $consultation->time_in
                ? Carbon::parse($consultation->time_in)->timezone('Asia/Manila')->format('g:i A')
                : 'N/A';

            $timeOutDisplay = $consultation->time_out
                ? Carbon::parse($consultation->time_out)->timezone('Asia/Manila')->format('g:i A')
                : 'N/A';

            $studentName = trim("{$client->first_name} {$client->last_name}");
            $teacherName = $consultation->current_teacher ?: $consultation->check_in_teacher;

            $mail = match ($consultation->after_consultation) {
                'resume' => new StudentResumeClassMail(
                    $studentName,
                    $teacherName,
                    $dateDisplay,
                    $timeInDisplay,
                    $timeOutDisplay,
                ),
                'go_home' => new StudentGoHomeMail(
                    $studentName,
                    $teacherName,
                    $dateDisplay,
                    $timeInDisplay,
                    $timeOutDisplay,
                    $consultation->going_home_method,
                    $consultation->fetcher_name,
                    $consultation->self_approved_by,
                ),
                default => throw new RuntimeException('The Guidance consultation outcome does not have an email template.'),
            };

            $message = Mail::to($to);

            if ($ccRecipients !== [] && $to !== $ccRecipients) {
                $message->cc($ccRecipients);
            }

            $message->send($mail);

            $consultation->update([
                'email_status' => Consultation::EMAIL_STATUS_SENT,
                'email_sent_at' => now(),
                'email_failed_at' => null,
                'email_failure_message' => null,
            ]);
        } catch (Throwable $exception) {
            $this->markFailed($exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        if ($exception) {
            $this->markFailed($exception);
        }
    }

    private function markFailed(Throwable $exception): void
    {
        Consultation::whereKey($this->consultationId)->update([
            'email_status' => Consultation::EMAIL_STATUS_FAILED,
            'email_sent_at' => null,
            'email_failed_at' => now(),
            'email_failure_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
