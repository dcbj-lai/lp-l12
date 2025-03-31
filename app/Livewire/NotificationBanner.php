<?php

namespace App\Livewire;

use Livewire\Component;

class NotificationBanner extends Component
{
    public $message;
    public $type;
    public $visible = false;

    protected $listeners = ['showNotification'];

    public function showNotification($message, $type = 'info')
    {
        $this->message = $message;
        $this->type = $type;
        $this->visible = true;

        // Dispatch JavaScript event to auto-dismiss
        $this->dispatch('hideNotificationAfterDelay');
    }

    public function closeNotification()
    {
        $this->visible = false;
    }

    public function render()
    {
        return view('livewire.notification-banner');
    }
}
