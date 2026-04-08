<?php

namespace App\Livewire;

use App\Jobs\SendHolidayCampaign;
use App\Models\HolidayCampaign;
use App\Models\User;
use App\Services\AmazonS3Service;
use App\Services\HtmlAssetProcessor;
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

    public function processHtml(HtmlAssetProcessor $processor)
    {
        $this->processedHtml = $processor->process(
            $this->html,
            $this->assetMap
        );

        // ✅ use Livewire event instead
        $this->dispatch('flash', type: 'info', message: 'HTML processed. Review preview.');
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

        return redirect()->route('holiday.campaign')
            ->with('success', 'Campaign queued successfully.');
    }

    public function render()
    {
        return view('livewire.holiday-campaign-form');
    }
}
