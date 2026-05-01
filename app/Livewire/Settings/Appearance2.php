<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class Appearance2 extends Component
{
    public string $theme = 'system';

    public function mount()
    {
        $this->theme = session('theme', 'system');
    }

    public function setTheme($theme)
    {
        $this->theme = $theme;

        session(['theme' => $theme]);

        $this->dispatch('flash', type: 'success', message: 'Appearance updated.');
    }

    public function render()
    {
        return view('livewire.settings.appearance2');
    }
}
