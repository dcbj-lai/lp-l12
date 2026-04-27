<?php

namespace App\Livewire\Resources;

use App\Models\Resource;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;


class ResourceIndex extends Component
{
    use WithFileUploads;

    #[Validate('nullable|image|max:2048')]
    public $image;
    public $resources = [];

    public $selectedResourceId = null;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|in:room,equipment')]
    public $type = 'room';

    #[Validate('nullable|string')]
    public $description = '';

    public $location = '';
    public $capacity = null;

    public function mount()
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);

        $this->loadResources();
    }

    public function loadResources()
    {
        $this->resources = Resource::latest()->get();
    }

    public function selectResource($id)
    {
        $resource = Resource::findOrFail($id);

        $this->selectedResourceId = $resource->id;

        $this->name = $resource->name;
        $this->type = $resource->type;
        $this->description = $resource->description;
        $this->location = $resource->location;
        $this->capacity = $resource->capacity;
    }

    public function updateSelected()
    {
        $this->validate();

        $resource = Resource::findOrFail($this->selectedResourceId);

        // 🖼️ Handle image upload
        if ($this->image) {

            // delete old
            if ($resource->image_path) {
                Storage::disk('s3')->delete($resource->image_path);
            }

            $filename = 'resource_' . time() . '.' . $this->image->getClientOriginalExtension();

            $path = $this->image->storeAs(
                'resources/' . $resource->id,
                $filename,
                's3'
            );

            Storage::disk('s3')->setVisibility($path, 'public');

            $resource->image_path = $path;
        }

        $resource->update([
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->type === 'room' ? $this->location : null,
            'capacity' => $this->type === 'room' ? $this->capacity : null,
            'image_path' => $resource->image_path,
        ]);

        $this->reset('image'); // clear temp

        $this->loadResources();

        $this->dispatch('resource-updated');

        $this->modal('manage-resource-modal')->close();
    }

    public function deleteSelected()
    {
        $resource = Resource::findOrFail($this->selectedResourceId);

        if ($resource->image_path) {
            Storage::disk('s3')->delete($resource->image_path);
        }

        $resource->delete();

        $this->reset([
            'selectedResourceId',
            'name',
            'type',
            'description',
            'location',
            'capacity',
        ]);

        $this->loadResources();

        $this->dispatch('resource-deleted');
        $this->modal('confirm-delete-resource')->close();
        $this->modal('manage-resource-modal')->close();
    }

    public function createNew()
    {
        $this->reset([
            'selectedResourceId',
            'name',
            'type',
            'description',
            'location',
            'capacity',
            'image',
        ]);

        $this->type = 'room';
    }

    public function store()
    {
        $this->validate();

        $resource = Resource::create([
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->type === 'room' ? $this->location : null,
            'capacity' => $this->type === 'room' ? $this->capacity : null,
            'created_by' => auth()->id(),
        ]);

        // 🖼️ Handle image
        if ($this->image) {

            $filename = 'resource_' . time() . '.' . $this->image->getClientOriginalExtension();

            $path = $this->image->storeAs(
                'resources/' . $resource->id,
                $filename,
                's3'
            );

            \Storage::disk('s3')->setVisibility($path, 'public');

            $resource->update([
                'image_path' => $path,
            ]);
        }

        $this->reset([
            'selectedResourceId',
            'name',
            'type',
            'description',
            'location',
            'capacity',
            'image',
        ]);

        $this->loadResources();

        $this->modal('manage-resource-modal')->close();
    }

    public function render()
    {
        return view('livewire.resources.resource-index');
    }
}
