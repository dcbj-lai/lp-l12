<?php

namespace App\Livewire\Resources;

use App\Models\Resource;
use App\Services\ResourceReservationService;
use App\Support\ReservationRecurrence;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateReservation extends Component
{
    use WithFileUploads;

    #[Validate('required|file|max:10240|mimes:pdf,jpg,jpeg,png')]
    public $floor_plan;

    #[Validate('nullable|file|max:10240|mimes:pdf,doc,docx,jpg,jpeg,png')]
    public $gate_pass;

    public $rooms = [];
    public $equipment = [];

    #[Validate('required|email')]
    public $requester_email = '';

    #[Validate('required|exists:resources,id')]
    public $resource_id = null;
    public $equipment_quantities = [];

    #[Validate('required|string|max:255')]
    public $title = '';

    #[Validate('required|integer|min:1')]
    public $number_of_pax = null;

    #[Validate('required|string|max:255')]
    public $setup_arrangement = '';

    #[Validate('required|string|max:50')]
    public $contact_number = '';

    #[Validate('nullable|string|max:2000')]
    public $consumables = '';

    #[Validate('required|in:none,daily,weekly,monthly,yearly,weekdays,custom')]
    public $recurrence = 'none';

    #[Validate('integer|min:2|max:52')]
    public $occurrences = 13;

    public $recurrence_ends = 'after';
    public $recurrence_until = null;
    public $custom_interval = 1;
    public $custom_unit = 'week';
    public $custom_weekdays = [];

    #[Validate('required|date')]
    public $event_date;

    #[Validate('required|date_format:H:i')]
    public $start_time;

    #[Validate('required|date_format:H:i')]
    public $end_time;

    public $selected_equipment_to_add = null;
    public $selected_equipment_quantity = 1;
    public array $scheduleConflictWarnings = [];
    public bool $scheduleChecked = false;
    public bool $scheduleCheckAttempted = false;

    #[Validate('nullable|string|max:500')]
    public $notes = '';

    protected function messages(): array
    {
        return ['resource_id.required' => 'Room selection is required.'];
    }

    public function mount()
    {
        $this->rooms = Resource::where('type', 'room')->where('capacity', '>', 0)->orderBy('name')->get();
        $this->equipment = Resource::where('type', 'equipment')->where('total_quantity', '>', 0)->orderBy('name')->get();
    }

    public function updatedRecurrence(): void
    {
        if ($this->recurrence === 'yearly') {
            $this->occurrences = 2;
        }
        if ($this->recurrence === 'custom' && $this->event_date && !$this->custom_weekdays) {
            $this->custom_weekdays = [CarbonImmutable::parse($this->event_date)->dayOfWeek];
        }
    }

    public function updatedCustomUnit(): void
    {
        if ($this->custom_unit === 'year') {
            $this->occurrences = 2;
        }
    }

    public function updatedEventDate(): void
    {
        if ($this->recurrence === 'custom' && $this->event_date && !$this->custom_weekdays) {
            $this->custom_weekdays = [CarbonImmutable::parse($this->event_date)->dayOfWeek];
        }
    }

    public function updatedSelectedEquipmentToAdd(): void
    {
        $this->selected_equipment_quantity = 1;
    }

    public function updated($property): void
    {
        if (in_array($property, ['resource_id', 'event_date', 'start_time', 'end_time', 'recurrence', 'occurrences', 'recurrence_ends', 'recurrence_until', 'custom_interval', 'custom_unit', 'custom_weekdays'], true)
            || str_starts_with($property, 'equipment_quantities.')) {
            $this->scheduleConflictWarnings = [];
            $this->scheduleChecked = false;
            $this->scheduleCheckAttempted = false;
            if ($this->selected_equipment_to_add) {
                $available = $this->selectedEquipmentAvailableQuantity(app(ResourceReservationService::class));
                $this->selected_equipment_quantity = $available > 0
                    ? min($available, max(1, (int) $this->selected_equipment_quantity))
                    : 0;
            }
        }
    }

    public function submitReservation(ResourceReservationService $service)
    {
        $this->scheduleCheckAttempted = false;
        $recurrenceDates = [];
        try {
            $this->validate();
            $this->validate(['equipment_quantities.*' => 'integer|min:1']);
            if ($this->end_time <= $this->start_time) {
                throw ValidationException::withMessages(['end_time' => 'End time must be after start time.']);
            }

            if ($this->recurrence !== 'none') {
                $this->validate([
                    'recurrence_ends' => 'required|in:on,after',
                    'recurrence_until' => 'required_if:recurrence_ends,on|nullable|date',
                    'custom_interval' => 'required_if:recurrence,custom|integer|min:1|max:52',
                    'custom_unit' => 'required_if:recurrence,custom|in:day,week,month,year',
                    'custom_weekdays' => 'array',
                    'custom_weekdays.*' => 'integer|between:0,6',
                ]);
                try {
                    $recurrenceDates = $this->recurrenceDates();
                } catch (\InvalidArgumentException $e) {
                    throw ValidationException::withMessages(['recurrence' => $e->getMessage()]);
                }
            }
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
            $this->dispatch('flash', type: 'error', message: 'Please complete or correct the highlighted fields before submitting.');
            return;
        }

        $startDateTime = $this->event_date . ' ' . $this->start_time;
        $endDateTime = $this->event_date . ' ' . $this->end_time;
        $this->scheduleConflictWarnings = $this->approvedScheduleConflicts($service, $recurrenceDates);
        $paths = [];

        try {
            foreach (['floor_plan', 'gate_pass'] as $field) {
                if ($this->{$field}) {
                    $paths[$field] = $this->{$field}->storeAs(
                        'reservations',
                        Str::uuid() . '.' . $this->{$field}->getClientOriginalExtension(),
                        config('filesystems.facility_upload_disk')
                    );
                }
            }
            $data = [
                'user_id' => null, // 🔥 public booking
                'requester_email' => $this->requester_email,
                'resource_id' => $this->resource_id,
                'equipment_quantities' => $this->equipment_quantities,
                'title' => $this->title,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'notes' => $this->notes,
                'number_of_pax' => $this->number_of_pax,
                'setup_arrangement' => $this->setup_arrangement,
                'contact_number' => $this->contact_number,
                'consumables' => $this->consumables,
                'floor_plan_path' => $paths['floor_plan'] ?? null,
                'gate_pass_path' => $paths['gate_pass'] ?? null,
            ];

            if ($this->recurrence === 'none') {
                $service->create($data);
            } else {
                $service->createSeriesForDates($data, $recurrenceDates, $this->recurrenceLabel());
            }

            $message = 'Your booking request has been submitted for approval.';
            if ($this->scheduleConflictWarnings) {
                $message .= ' Some dates overlap approved bookings and are flagged for admin review.';
            }
            $this->dispatch('flash', type: 'success', message: $message);

            $this->reset([
                'requester_email',
                'resource_id',
                'equipment_quantities',
                'title',
                'number_of_pax',
                'setup_arrangement',
                'contact_number',
                'consumables',
                'recurrence',
                'occurrences',
                'recurrence_ends',
                'recurrence_until',
                'custom_interval',
                'custom_unit',
                'custom_weekdays',
                'event_date',
                'start_time',
                'end_time',
                'notes',
                'floor_plan',
                'gate_pass',
                'scheduleConflictWarnings',
                'scheduleChecked',
                'scheduleCheckAttempted',
            ]);
            $this->recurrence = 'none';
            $this->occurrences = 13;
            $this->recurrence_ends = 'after';
            $this->custom_interval = 1;
            $this->custom_unit = 'week';

        } catch (\Throwable $e) {
            foreach ($paths as $path) {
                \Storage::disk(config('filesystems.facility_upload_disk'))->delete($path);
            }
            \Log::error('Booking failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->dispatch(
                'flash',
                type: 'error',
                message: $e->getMessage() ?: 'An error occurred while submitting your booking request. Please try again later.'
            );
        }
    }

    public function checkSchedule(ResourceReservationService $service): void
    {
        $this->scheduleChecked = false;
        $this->scheduleCheckAttempted = true;
        $this->validate([
            'resource_id' => 'required|exists:resources,id',
            'event_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        try {
            $dates = $this->recurrence === 'none' ? [] : $this->recurrenceDates();
            $service->validateRequestResources((int) $this->resource_id, $this->equipment_quantities);
            $this->scheduleConflictWarnings = $this->approvedScheduleConflicts($service, $dates);
            $this->scheduleChecked = true;
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['recurrence' => $e->getMessage()]);
        }
    }

    private function approvedScheduleConflicts(ResourceReservationService $service, array $recurrenceDates): array
    {
        $warnings = [];
        foreach ($recurrenceDates ?: [$this->event_date] as $date) {
            $conflicts = $service->approvedConflictNames(
                (int) $this->resource_id,
                $this->equipment_quantities,
                $date . ' ' . $this->start_time,
                $date . ' ' . $this->end_time
            );
            if ($conflicts) {
                $warnings[$date] = $conflicts;
            }
        }
        return $warnings;
    }

    public function recurrenceDates(): array
    {
        if (!$this->event_date || $this->recurrence === 'none') {
            return [];
        }

        $frequency = $this->recurrence === 'custom'
            ? ['day' => 'daily', 'week' => 'weekly', 'month' => 'monthly', 'year' => 'yearly'][$this->custom_unit] ?? 'weekly'
            : $this->recurrence;
        $weekdays = $frequency === 'weekly'
            ? ($this->recurrence === 'custom' ? $this->custom_weekdays : [CarbonImmutable::parse($this->event_date)->dayOfWeek])
            : [];

        return ReservationRecurrence::dates(
            $this->event_date,
            $frequency,
            $this->recurrence === 'custom' ? (int) $this->custom_interval : 1,
            $weekdays,
            $this->recurrence_ends,
            $this->recurrence_until,
            (int) $this->occurrences
        );
    }

    public function recurrenceLabel(): string
    {
        $date = CarbonImmutable::parse($this->event_date);
        if ($this->recurrence === 'custom') {
            $label = 'Every ' . $this->custom_interval . ' ' . $this->custom_unit . ((int) $this->custom_interval === 1 ? '' : 's');
            if ($this->custom_unit === 'week') {
                $label .= ' on ' . collect($this->custom_weekdays)->map(fn ($day) => CarbonImmutable::now()->startOfWeek(CarbonImmutable::SUNDAY)->addDays((int) $day)->format('D'))->join(', ');
            }
        } else {
            $label = match ($this->recurrence) {
                'weekly' => 'Weekly on ' . $date->format('l'),
                'monthly' => 'Monthly on the ' . $this->monthlyPatternLabel(),
                'yearly' => 'Yearly on ' . $date->format('F j'),
                'weekdays' => 'Every weekday (Monday to Friday)',
                default => 'Daily',
            };
        }

        return $label . ($this->recurrence_ends === 'on' ? ' until ' . $this->recurrence_until : ' for ' . count($this->recurrenceDates()) . ' dates');
    }

    public function monthlyPatternLabel(): string
    {
        if (!$this->event_date) {
            return '';
        }
        $date = CarbonImmutable::parse($this->event_date);
        $week = (int) ceil($date->day / 7);
        $ordinal = [1 => 'first', 2 => 'second', 3 => 'third', 4 => 'fourth', 5 => 'fifth'][$week];
        return ($date->addWeek()->month !== $date->month ? 'last' : $ordinal) . ' ' . $date->format('l');
    }

    public function addEquipment()
    {
        if (!$this->selected_equipment_to_add) {
            return;
        }

        $available = $this->selectedEquipmentAvailableQuantity(app(ResourceReservationService::class));
        if ($available < 1) {
            return;
        }

        $this->equipment_quantities[(int) $this->selected_equipment_to_add] = min($available, max(1, (int) $this->selected_equipment_quantity));
        $this->scheduleConflictWarnings = [];
        $this->scheduleChecked = false;
        $this->scheduleCheckAttempted = false;

        $this->selected_equipment_to_add = null;
        $this->selected_equipment_quantity = 1;
    }

    public function removeEquipment($id)
    {
        unset($this->equipment_quantities[$id]);
        $this->scheduleConflictWarnings = [];
        $this->scheduleChecked = false;
        $this->scheduleCheckAttempted = false;
    }

    public function selectedEquipmentAvailableQuantity(ResourceReservationService $service): int
    {
        $item = $this->selected_equipment_to_add
            ? Resource::where('type', 'equipment')->where('total_quantity', '>', 0)
                ->find((int) $this->selected_equipment_to_add)
            : null;
        if (!$item) {
            return 0;
        }

        $total = (int) $item->total_quantity;
        if (!$this->event_date || !$this->start_time || !$this->end_time || $this->end_time <= $this->start_time) {
            return $total;
        }

        try {
            $dates = $this->recurrence === 'none' ? [$this->event_date] : $this->recurrenceDates();
            if (!$dates) {
                return $total;
            }

            foreach ($dates as $date) {
                $total = min($total, $service->availableEquipmentQuantity(
                    (int) $item->id,
                    $date . ' ' . $this->start_time,
                    $date . ' ' . $this->end_time
                ));
            }
        } catch (\InvalidArgumentException $e) {
            return (int) $item->total_quantity;
        }

        return $total;
    }

    public function render()
    {
        return view('livewire.resources.create-reservation');
    }
}
