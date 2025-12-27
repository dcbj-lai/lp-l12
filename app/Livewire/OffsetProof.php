<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Request as StaffRequest;
use Illuminate\Support\Facades\Storage;

class OffsetProof extends Component
{
    use WithFileUploads;

    public StaffRequest $request;
    public $file;

    protected function rules()
    {
        return [
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    public function remove()
    {
        if ($this->request->offset_proof_path) {
            $this->request->deleteOffsetProof();
            $this->request->update(['offset_proof_path' => null]);
        }
    }

    public function updatedFile()
    {
        $this->validate();

        // ALWAYS replace
        if ($this->request->offset_proof_path) {
            $this->request->deleteOffsetProof();
        }

        $path = $this->file->storeAs(
            $this->request->offsetProofFolder(),
            StaffRequest::sanitizeFilename($this->file->getClientOriginalName()),
            'private_s3'
        );

        $this->request->update([
            'offset_proof_path' => $path,
        ]);

        $this->file = null;
    }

    public function render()
    {
        return view('livewire.offset-proof');
    }
}
