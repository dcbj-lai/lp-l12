<?php

namespace App\Livewire\Resources;

use App\Models\Resource;
use App\Models\ResourceReservation;
use App\Services\ResourceReservationService;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Validation\Rule;


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
    #[Validate('nullable|integer|min:0')]
    public $capacity = null;
    #[Validate('required|integer|min:1')]
    public $total_quantity = 1;

    public $control_number = '';

    public function mount()
    {
        abort_unless(auth()->user()->hasRole('facility.admin'), 403);

        $this->loadResources();
    }

    public function loadResources()
    {
        $this->resources = Resource::latest()->get();
    }

    public function availabilityNow(): array
    {
        $now = now();
        $roomUsage = ResourceReservation::query()
            ->where('status', 'approved')
            ->where('end_datetime', '>', $now)
            ->get(['resource_id'])
            ->groupBy('resource_id');

        $equipmentUsage = DB::table('resource_reservation_items')
            ->join('resource_reservations', 'resource_reservation_items.reservation_id', '=', 'resource_reservations.id')
            ->whereNull('resource_reservations.deleted_at')
            ->where('resource_reservations.status', 'approved')
            ->where('resource_reservations.end_datetime', '>', $now)
            ->select('resource_reservation_items.resource_id', 'resource_reservation_items.quantity', 'resource_reservations.start_datetime', 'resource_reservations.end_datetime')
            ->get()
            ->groupBy('resource_id');

        $availability = [];
        foreach ($this->resources as $resource) {
            $usage = $resource->isRoom() ? ($roomUsage[$resource->id] ?? collect()) : ($equipmentUsage[$resource->id] ?? collect());
            $approved = $resource->isRoom() ? (int) $usage->isNotEmpty() : $this->peakEquipmentUsage($usage, $now);
            $total = $resource->isRoom() ? ((int) $resource->capacity > 0 ? 1 : 0) : $resource->total_quantity;
            $availability[$resource->id] = [
                'total' => $total,
                'approved' => $approved,
                'bookable' => max(0, $total - $approved),
            ];
        }

        return $availability;
    }

    private function peakEquipmentUsage($bookings, $now): int
    {
        $events = [];
        foreach ($bookings as $booking) {
            $start = max(strtotime($booking->start_datetime), $now->timestamp);
            $end = strtotime($booking->end_datetime);
            $events[] = [$start, (int) $booking->quantity];
            $events[] = [$end, -(int) $booking->quantity];
        }
        usort($events, fn ($a, $b) => ($a[0] <=> $b[0]) ?: ($a[1] <=> $b[1]));
        $used = $peak = 0;
        foreach ($events as [, $change]) {
            $used += $change;
            $peak = max($peak, $used);
        }

        return $peak;
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
        $this->total_quantity = $resource->total_quantity;
        $this->control_number = $resource->control_number;
    }

    public function updateSelected()
    {
        $this->control_number = trim((string) $this->control_number);
        $this->validate();
        $this->validate(['capacity' => 'required_if:type,room|nullable|integer|min:1']);

        $resource = Resource::findOrFail($this->selectedResourceId);
        $this->validate(['control_number' => ['nullable', 'string', 'max:255', Rule::unique('resources', 'control_number')->ignore($resource->id)]]);

        if ($this->type === 'equipment' && (int) $this->total_quantity < app(ResourceReservationService::class)->maximumEquipmentUsage($resource->id)) {
            $this->addError('total_quantity', 'Quantity cannot be lower than units already reserved at the same time.');
            return;
        }

        // 🖼️ Handle image upload
        if ($this->image) {

            // delete old
            if ($resource->image_path) {
                Storage::disk(config('filesystems.facility_upload_disk'))->delete($resource->image_path);
            }

            $filename = 'resource_' . time() . '.' . $this->image->getClientOriginalExtension();

            $path = $this->image->storeAs(
                'resources/' . $resource->id,
                $filename,
                config('filesystems.facility_upload_disk')
            );

            Storage::disk(config('filesystems.facility_upload_disk'))->setVisibility($path, 'public');

            $resource->image_path = $path;
        }

        $resource->update([
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->type === 'room' ? $this->location : null,
            'capacity' => $this->type === 'room' ? $this->capacity : null,
            'total_quantity' => $this->type === 'equipment' ? $this->total_quantity : 1,
            'image_path' => $resource->image_path,
            'control_number' => $this->control_number !== '' ? $this->control_number : null,
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
            Storage::disk(config('filesystems.facility_upload_disk'))->delete($resource->image_path);
        }

        $resource->delete();

        $this->reset([
            'selectedResourceId',
            'name',
            'type',
            'description',
            'location',
            'capacity',
            'total_quantity',
            'control_number',
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
            'total_quantity',
            'image',
            'control_number',
        ]);

        $this->type = 'room';
        $this->total_quantity = 1;
    }

    public function store()
    {
        $this->control_number = trim((string) $this->control_number);
        $this->validate();
        $this->validate(['capacity' => 'required_if:type,room|nullable|integer|min:1']);
        $this->validate(['control_number' => ['nullable', 'string', 'max:255', Rule::unique('resources', 'control_number')]]);

        $resource = Resource::create([
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'location' => $this->type === 'room' ? $this->location : null,
            'capacity' => $this->type === 'room' ? $this->capacity : null,
            'total_quantity' => $this->type === 'equipment' ? $this->total_quantity : 1,
            'created_by' => auth()->id(),
            'control_number' => $this->control_number !== '' ? $this->control_number : null,
        ]);

        // 🖼️ Handle image
        if ($this->image) {

            $filename = 'resource_' . time() . '.' . $this->image->getClientOriginalExtension();

            $path = $this->image->storeAs(
                'resources/' . $resource->id,
                $filename,
                config('filesystems.facility_upload_disk')
            );

            \Storage::disk(config('filesystems.facility_upload_disk'))->setVisibility($path, 'public');

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
            'total_quantity',
            'image',
            'control_number',
        ]);

        $this->loadResources();

        $this->modal('manage-resource-modal')->close();
    }

    public function render()
    {
        return view('livewire.resources.resource-index', ['availabilityNow' => $this->availabilityNow()]);
    }
}
