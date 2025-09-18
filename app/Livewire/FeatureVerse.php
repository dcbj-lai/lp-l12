<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class FeatureVerse extends Component
{
    public $verse;
    public $reference;
    public $editing = false;

    public function mount()
    {
        $this->fetchVerseFromApi();
    }

    public function fetchVerseFromApi()
    {
        try {
            $response = Http::timeout(5)->get('https://beta.ourmanna.com/api/v1/get?format=json');

            if ($response->successful()) {
                $data = $response->json();
                $this->verse = $data['verse']['details']['text'] ?? 'Let your light shine before others, that they may see your good deeds and glorify your Father in heaven.';
                $this->reference = $data['verse']['details']['reference'] ?? 'Matthew 5:16';
            } else {
                $this->useFallbackVerse();
            }
        } catch (\Exception $e) {
            $this->useFallbackVerse();
        }
    }

    private function useFallbackVerse()
    {
        $this->verse = 'Let your light shine before others, that they may see your good deeds and glorify your Father in heaven.';
        $this->reference = 'Matthew 5:16';
    }

    public function render()
    {
        return view('livewire.feature-verse');
    }
}
