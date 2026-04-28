<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
    @fluxAppearance
    @livewireStyles
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    @php
        $user = Auth::user();

        // ✅ legacy roles
        $legacyRoles = $user->legacy_roles ?? [];

        // $isPNC = in_array('pnc.admin', $legacyRoles);
        $isPNC = Gate::allows('is-pnc');
        $isFinanceAdmin = in_array('finance.admin', $legacyRoles);
        $isSuperAdmin = in_array('super.admin', $legacyRoles);
        $isManager = $user->isManager();
        $isFrontDesk = in_array('frontdesk.staff', $legacyRoles);
        $isAcadAdmin = in_array('acad.admin', $legacyRoles);
        $isGuidanceAdmin = in_array('guidance.admin', $legacyRoles);
        $isGuidanceStaff = in_array('guidance.staff', $legacyRoles);
        $isCommsAdmin = in_array('comms.admin', $legacyRoles);
        $isClinicAdmin = in_array('clinic.admin', $legacyRoles);

        // ✅ new system (for later use)
        $isFacilityAdmin = $user->hasRole('facility.admin');
        $isFacilityApprover = $user->hasRole('facility.approver');

        $canAccessFacility = $isFacilityAdmin || $isFacilityApprover;
    @endphp
    <flux:sidebar sticky stashable class="border-r border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ route('dashboard') }}" class="mr-5 flex items-center space-x-2" wire:navigate>
            <x-app-logo />
        </a>
        <!-- Navigation -->
        <flux:navlist class="w-64">
            <flux:navlist.item href="{{ route('dashboard') }}" icon="layout-dashboard">Dashboard</flux:navlist.item>
            <flux:navlist.group heading="My Portal" expandable :expanded="false">
                <flux:navlist.item href="{{ route('attendance.my_attendance') }}" icon="user-check">Attendance
                </flux:navlist.item>
                <flux:navlist.item href="{{ route('my-requests') }}" icon="calendar">Requests</flux:navlist.item>
                <flux:navlist.item href="{{ route('payslips.index') }}" icon="banknotes">Payslips</flux:navlist.item>
                <flux:navlist.item href="#" icon="bot-message-square">Notifications</flux:navlist.item>
                <flux:navlist.item href="{{ route('visitors.mine') }}" icon="book-user">Visitors</flux:navlist.item>
                <flux:navlist.group heading="Life Steps">
                    <flux:navlist.item href="{{ route('my-steps.index') }}" icon="footprints">My Steps
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('steps.index') }}" icon="trophy">Leaderboard</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist.group>
            @if ($isManager)
                <flux:navlist.group heading="My Approvals" expandable :expanded="false">
                    <flux:navlist.item href="{{ route('requests.manage') }}" icon="bookmark-check">Schedule Requests
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif
            @if ($isPNC || $isSuperAdmin)
                <flux:navlist.group heading="P&C" expandable :expanded="false">
                    <flux:navlist.item href="{{ route('users.index') }}" icon="users">Users</flux:navlist.item>
                    <flux:navlist.item href="{{ route('attendance.index') }}" icon="user-check">View Staff Attendance
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('requests.manage-hr') }}" icon="list-filter-plus">View Staff
                        Requests
                    </flux:navlist.item>
                    <flux:navlist.item href="{{ route('org-settings.index') }}" icon="settings">Setup Requests
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif
            @if ($isFinanceAdmin || $isSuperAdmin)
                <flux:navlist.group heading="Finance" expandable :expanded="false">
                    <flux:navlist.item href="{{ route('payouts.index') }}" icon="hand-coins">Payroll
                    </flux:navlist.item>
                    <flux:navlist.item href="#" icon="file-chart-column-increasing">Reports</flux:navlist.item>
                </flux:navlist.group>
            @endif
            @if ($isFrontDesk || $isSuperAdmin)
                <flux:navlist.group heading="Front Desk" expandable :expanded="false">
                    <flux:navlist.item href="{{ route('frontdesk.visitors') }}" icon="logs">Visitor Logs
                    </flux:navlist.item>
                    <flux:navlist.item href="#" icon="file-chart-column-increasing">Reports</flux:navlist.item>
                </flux:navlist.group>
            @endif
            @if ($isCommsAdmin || $isSuperAdmin)
                <flux:navlist.group heading="Comms" expandable :expanded="false">
                    <flux:navlist.item href="{{ route('holiday.campaign') }}" icon="message-square-share">
                        Holiday Campaign
                    </flux:navlist.item>
                </flux:navlist.group>
            @endif



            @if ($isGuidanceAdmin || $isClinicAdmin)
                <flux:navlist.group heading="Health and Wellness" expandable="false">

                    @if ($isGuidanceAdmin)
                        <flux:navlist.group heading="Guidance" expandable :expanded="false">
                            <flux:navlist.item href="{{ route('guidance.clients.index') }}" icon="users">
                                Clients
                            </flux:navlist.item>

                            <flux:navlist.item href="{{ route('guidance.consultations.index') }}"
                                icon="clipboard-document-list">
                                Consultations
                            </flux:navlist.item>
                            <flux:navlist.item href="{{ route('guidance.import-csv.index') }}" icon="arrow-up-tray">
                                Import CSV
                            </flux:navlist.item>
                        </flux:navlist.group>
                    @endif

                    @if ($isClinicAdmin)
                        <flux:navlist.group heading="Clinic" expandable :expanded="false">
                            <flux:navlist.item href="{{ route('clinic.patients.index') }}" icon="users">
                                Patients
                            </flux:navlist.item>
                            <flux:navlist.item href="{{ route('clinic.consultations.index') }}"
                                icon="clipboard-document-list">
                                Consultations
                            </flux:navlist.item>
                            <flux:navlist.item href="{{ route('clinic.import-csv.index') }}" icon="arrow-up-tray">
                                Import CSV
                            </flux:navlist.item>
                        </flux:navlist.group>
                    @endif

                </flux:navlist.group>
            @endif

            @if ($canAccessFacility)
                <flux:navlist.group heading="Facilities" expandable :expanded="false">

                    @if ($isFacilityAdmin)
                        <flux:navlist.item href="{{ route('resources.index') }}" icon="building-office"
                            :active="request()->routeIs('resources.*')">
                            Resources
                        </flux:navlist.item>
                    @endif

                    @if ($isFacilityAdmin || $isFacilityApprover)
                        <flux:navlist.item href="{{ route('resources.reservations.index') }}" icon="calendar-days"
                            :active="request()->routeIs('reservations.*')">
                            Reservations
                        </flux:navlist.item>
                    @endif

                </flux:navlist.group>
            @endif

        </flux:navlist>
        <!-- Navigation -->
        <flux:spacer />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="info" href="{{ route('about') }}" wire:click.prevent="openModal">
                {{ __('About Life Portal') }}
            </flux:navlist.item>

            <flux:navlist.item icon="book-open-text" href="https://knowledge.laicollege.edu.ph/">
                {{ __('LAIC Knowledge Base') }}
            </flux:navlist.item>
            <flux:navlist.group heading="Team LIFE" expandable :expanded="false">
                <flux:navlist.item href="#" icon="book-user">Directory
                </flux:navlist.item>
                <flux:navlist.item href="#" icon="network">Org Chart</flux:navlist.item>
            </flux:navlist.group>
        </flux:navlist>

        <!-- Desktop User Menu -->
        <flux:dropdown position="bottom" align="start">
            <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                icon-trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-500 dark:text-white">
                                    {{ auth()->user()->initials() }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-left leading-tight">
                                <span class="truncate font-semibold">
                                    {{ auth()->user()->name }}
                                </span>

                                <span class="text-[11px] text-yellow-300 drop-shadow-[0_0_6px_rgba(253,224,71,0.9)]">
                                    {{ auth()->user()->preferred_name ?? '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item href="/settings/profile" icon="cog" wire:navigate>{{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-3" inset="left" />
    </flux:header>

    {{ $slot }}

    @fluxScripts
    @livewireScripts
</body>

</html>
