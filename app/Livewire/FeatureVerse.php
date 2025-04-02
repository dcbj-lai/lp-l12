<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\BibleVerse;
use Illuminate\Support\Facades\Auth;

class FeatureVerse extends Component
{
    public $verse;
    public $reference;
    public $editing = false;

    public function mount()
    {
        $latestVerse = BibleVerse::latest()->first();
        $this->verse = $latestVerse->text ?? 'Loading verse...';
        $this->reference = $latestVerse->reference ?? '';
    }

    public function saveVerse()
    {
        if (!Auth::user()->can('is-admin')) {
            return; // Ensure only admins can update
        }
        // dd('hello');
        BibleVerse::updateOrCreate([], [
            'text' => $this->verse,
            'reference' => $this->reference
        ]);

        $this->editing = false;
        session()->flash('success', 'Bible verse updated successfully!');
    }

    public function render()
    {
        return view('livewire.feature-verse');
    }
}
