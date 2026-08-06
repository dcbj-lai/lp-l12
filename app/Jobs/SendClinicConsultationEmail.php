<?php

namespace App\Jobs;

use App\Mail\StudentGoHomeClinicMail;
use App\Mail\StudentResumeClassClinicMail;
use App\Models\ClinicConsultation;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Throwable;

class SendClinicConsultationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $consultationId)
    {
        $this->onQueue('mail');
    }

    public function handle(): void
    {
        $consultation = ClinicConsultation::with('patient')->find($this->consultationId);

        if (! $consultation) {
            return;
        }

        try {
            $patient = $consultation->patient;

            if (! $patient || $patient->type !== 'student') {
                throw new RuntimeException('Clinic consultation email is available only for student patients.');
            }

            $teacherRecipients = array_values(array_unique(array_filter([
                $consultation->check_in_teacher_email,
                $consultation->teacher_email,
            ])));

            $ccRecipients = array_values(array_unique(array_filter([
                config('mail.clinic_recipients.clinic'),
                config('mail.clinic_recipients.academic_council'),
                $patient->isUnderAccessibility()
                    ? config('mail.clinic_recipients.sas')
                    : null,
            ])));

            $to = $teacherRecipients ?: $ccRecipients;

            if ($to === []) {
                throw new RuntimeException('No Clinic consultation email recipients are configured.');
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

            $studentName = trim("{$patient->first_name} {$patient->last_name}");
            $teacherName = $consultation->current_teacher ?: $consultation->check_in_teacher;

            $mail = match ($consultation->after_consultation) {
                'resume' => new StudentResumeClassClinicMail(
                    $studentName,
                    $teacherName,
                    $dateDisplay,
                    $timeInDisplay,
                    $timeOutDisplay,
                ),
                'go_home' => new StudentGoHomeClinicMail(
                    $studentName,
                    $teacherName,
                    $dateDisplay,
                    $timeInDisplay,
                    $timeOutDisplay,
                    $consultation->going_home_method,
                    $consultation->fetcher_name,
                    $consultation->self_approved_by,
                ),
                default => throw new RuntimeException('The consultation outcome does not have an email template.'),
            };

            $message = Mail::to($to);

            if ($ccRecipients !== [] && $to !== $ccRecipients) {
                $message->cc($ccRecipients);
            }

            $message->send($mail);

            $consultation->update([
                'email_status' => ClinicConsultation::EMAIL_STATUS_SENT,
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
        ClinicConsultation::whereKey($this->consultationId)->update([
            'email_status' => ClinicConsultation::EMAIL_STATUS_FAILED,
            'email_sent_at' => null,
            'email_failed_at' => now(),
            'email_failure_message' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
