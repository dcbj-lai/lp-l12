<?php

namespace App\Livewire;

use App\Jobs\SendHolidayCampaign;
use App\Models\HolidayCampaign;
use App\Models\User;
use App\Services\AmazonS3Service;
use App\Services\HtmlAssetProcessor;
use App\Services\OpenAiHtmlEmailService;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithFileUploads;

class HolidayCampaignForm extends Component
{
    use WithFileUploads;

    protected AmazonS3Service $s3;

    public array $assets = [];
    public array $assetMap = [];

    public string $uploadKey = 'upload-1';

    public string $html = '';
    public string $processedHtml = '';

    public ?int $campaignId = null;

    public array $users = [];
    public array $selectedUsers = [];
    public string $externalEmails = '';
    public string $subject = '';
    public string $fromEmail = '';
    public string $fromName = '';

    //AI inputs

    public string $aiInput = '';     // raw text or base content
    public string $aiPrompt = '';    // extra instructions
    public string $aiMode = 'format_only'; // format_only | enhance | prompt
    public string $enhancePrompt = '';

    public string $backgroundColor = '#ffffff';

    public array $aiPresets = [
        'Make it warm and friendly',
        'Make it formal and professional',
        'Make it festive (holiday theme)',
        'Make it concise and clear',
        'Highlight key message in bold',
        'Add a strong call-to-action',
        'Make tone inspirational and uplifting',
        'Use softer, empathetic tone',
    ];


    public function boot(AmazonS3Service $s3)
    {
        $this->s3 = $s3->useDisk('s3'); // ✅ ensure public disk
    }

    public function mount()
    {
        $this->users = User::select('id', 'name', 'email')->get()->toArray();

        $this->selectedUsers = collect($this->users)
            ->pluck('id')
            ->toArray();

        $this->subject = 'A Message from Life College International'; // Default subject

        $this->fromEmail = config('mail.from.address');
        $this->fromName = config('mail.from.name');
    }

    protected function rules()
    {
        return [
            'assets.*' => 'file|mimes:jpg,jpeg,png,gif,svg,webp|max:2048',
        ];
    }

    public function applyPreset(string $value): void
    {
        if (empty($value)) {
            return;
        }

        // Append instead of overwrite (better UX)
        $this->aiPrompt = trim($this->aiPrompt . ' ' . $value);
    }

    private function folder(): string
    {
        return 'greeting_mail_assets';
    }

    public function updatedAssets()
    {
        $this->validate();

        foreach ($this->assets as $file) {
            $originalName = $file->getClientOriginalName();

            $path = $this->s3->upload($file, $this->folder());
            $url = $this->s3->url($path);

            $this->assetMap[$originalName] = $url;
        }

        // ✅ reset file input via key (stable way)
        $this->assets = [];
        $this->uploadKey = 'upload-' . uniqid();

        $this->dispatch('flash', type: 'success', message: 'Assets uploaded.');
    }

    public function processHtml(
        HtmlAssetProcessor $processor,
        OpenAiHtmlEmailService $service
    ) {
        $processed = $processor->process(
            $this->html,
            $this->assetMap
        );

        // ✅ SAME INSTANCE
        $service->setBackgroundColor($this->backgroundColor);

        // ❌ DO NOT call app() again anywhere
        $this->processedHtml = $service->process($processed);

        $this->dispatch('flash', type: 'info', message: 'HTML processed safely.');
    }

    public function saveCampaign()
    {
        if (empty($this->processedHtml)) {
            $this->dispatch('flash', type: 'error', message: 'Process HTML first.');
            return;
        }

        if (empty(trim($this->subject))) {
            $this->dispatch('flash', type: 'error', message: 'Subject is required.');
            return;
        }

        $campaign = HolidayCampaign::create([
            'html' => $this->processedHtml,
            'assets' => $this->assetMap,
            'subject' => $this->subject,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
        ]);

        $this->campaignId = $campaign->id;
    }


    public function sendCampaign()
    {
        if (!$this->campaignId) {
            $this->dispatch('flash', type: 'error', message: 'Campaign not saved.');
            return;
        }

        $campaign = HolidayCampaign::find($this->campaignId);

        // Users
        $emails = User::whereIn('id', $this->selectedUsers)
            ->pluck('email')
            ->toArray();

        // External emails
        if (!empty($this->externalEmails)) {
            $external = collect(explode(',', $this->externalEmails))
                ->map(fn($e) => trim($e))
                ->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                ->toArray();

            $emails = array_merge($emails, $external);
        }

        // Deduplicate
        $emails = array_values(array_unique($emails));

        if (empty($emails)) {
            $this->dispatch('flash', type: 'error', message: 'No valid recipients.');
            return;
        }

        // 🔥 Dispatch job
        SendHolidayCampaign::dispatch($campaign, $emails);

        $this->dispatch('flash', type: 'success', message: 'Campaign queued.');

        // Optional reset
        $this->externalEmails = '';

        // ✅ Close modal (Flux)
        Flux::modal('recipient-modal')->close();

    }

    public function toggleSelectAll()
    {
        if (count($this->selectedUsers) === count($this->users)) {
            $this->selectedUsers = [];
        } else {
            $this->selectedUsers = collect($this->users)
                ->pluck('id')
                ->toArray();
        }
    }

    /**AI Generation */
    public function generateAiHtml(OpenAiHtmlEmailService $service)
    {
        // Guard: prevent empty usage
        if ($this->aiMode !== 'prompt' && empty(trim($this->aiInput))) {
            $this->dispatch('flash', type: 'error', message: 'Input text is required for this mode.');
            return;
        }

        if ($this->aiMode === 'prompt' && empty(trim($this->aiPrompt))) {
            $this->dispatch('flash', type: 'error', message: 'Prompt is required in prompt mode.');
            return;
        }

        $instruction = match ($this->aiMode) {
            'format_only' => "Convert the following text into a clean, table-based, email-safe HTML layout. Preserve the original wording. Use inline styles only.",
            'enhance' => "Improve the wording with a warm, professional tone and convert into a polished, table-based, email-safe HTML email. Maintain clarity, proper paragraph spacing, and visual hierarchy. Use inline styles only.",
            'prompt' => "Generate a complete, table-based, email-safe HTML email from the given prompt. Use inline styles only.",
            default => "Convert content into email-safe HTML.",
        };

        $input = trim($this->aiInput);
        $extra = $this->aiMode === 'format_only' ? '' : trim($this->aiPrompt);

        $finalPrompt = trim("
{$instruction}
" . (!empty($extra) ? "\nAdditional Instructions:\n{$extra}\n" : "") . "
Content:
{$input}

IMPORTANT:
- Return ONLY valid HTML
- No markdown
- No explanations
- Use table-based layout
- Use inline CSS only
");

        $response = app(\OpenAI\Client::class)->chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You generate strictly valid email HTML only. No text outside HTML.'
                ],
                [
                    'role' => 'user',
                    'content' => $finalPrompt
                ]
            ],
            'temperature' => 0.7,
        ]);

        $generatedHtml = trim($response->choices[0]->message->content ?? '');

        if (
            empty($generatedHtml) ||
            !str_contains($generatedHtml, '<') ||
            !str_contains($generatedHtml, '>')
        ) {
            $this->dispatch('flash', type: 'error', message: 'AI did not return valid HTML.');
            return;
        }

        if (empty($generatedHtml)) {
            $this->dispatch('flash', type: 'error', message: 'AI generation failed.');
            return;
        }

        // Inject + process
        $this->html = $generatedHtml;

        $this->processHtml(
            app(HtmlAssetProcessor::class),
            $service
        );

        // Reset modal state (premium UX)
        $this->reset(['aiInput', 'aiPrompt']);
        $this->aiMode = 'format_only';

        // Close modal
        Flux::modal('ai-generate-modal')->close();
    }

    public function enhanceHtml(OpenAiHtmlEmailService $service)
    {
        if (empty(trim($this->html))) {
            $this->dispatch('flash', type: 'error', message: 'No HTML to enhance.');
            return;
        }

        $instruction = trim($this->enhancePrompt);

        $finalPrompt = trim("
Improve the following HTML email while preserving its structure.

Instructions:
{$instruction}

IMPORTANT:
- Return ONLY valid HTML
- Do not remove structure (tables, layout)
- Improve readability and tone
- Keep it email-safe (inline CSS, table-based)
- Do not add explanations
");

        $response = app(\OpenAI\Client::class)->chat()->create([
            'model' => 'gpt-4.1-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You improve existing email HTML without breaking structure. Output HTML only.'
                ],
                [
                    'role' => 'user',
                    'content' => $finalPrompt . "\n\nHTML:\n" . $this->html
                ]
            ],
            'temperature' => 0.6,
        ]);

        $enhancedHtml = trim($response->choices[0]->message->content ?? '');

        if (
            empty($enhancedHtml) ||
            !preg_match('/<(table|p|div|html|body)[\s>]/i', $enhancedHtml)
        ) {
            $this->dispatch('flash', type: 'error', message: 'Enhancement failed.');
            return;
        }

        // Inject + process
        $this->html = $enhancedHtml;

        $this->processHtml(
            app(HtmlAssetProcessor::class),
            $service
        );

        // Reset + close
        $this->reset('enhancePrompt');

        Flux::modal('ai-enhance-modal')->close();
    }

    public function resetForm()
    {
        $this->reset([
            'html',
            'processedHtml',
            'aiInput',
            'aiPrompt',
            'enhancePrompt',
        ]);

        // optional: reset assets too
        $this->assetMap = [];

        // reset upload input
        $this->uploadKey = 'upload-' . uniqid();
    }

    /**AI Generation */

    public function render()
    {
        return view('livewire.holiday-campaign-form');
    }
}
