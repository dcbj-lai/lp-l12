<x-layouts.app title="About Life Portal">
    <div class="max-w-4xl mx-auto py-10 px-6 space-y-8 text-gray-800 dark:text-gray-200">
        <h1 class="text-4xl font-bold text-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 inline-block stroke-current" fill="none"
                viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M11.25 3L2.25 9l9 6 9-6-9-6zm0 0v6m9 3l-9 6-9-6" />
            </svg>
            About Life Portal
        </h1>

        <!-- Main Content -->
        <section class="space-y-4">
            <h2 class="text-2xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12l-7.5 7.5M3 12h16.5" />
                </svg>
                What is Life Portal?
            </h2>
            <p>Life Portal (LP) is Life Academy International College’s internal ERP software, designed to streamline
                and automate core business processes — from human resources to finance.</p>
        </section>

        <section class="space-y-4">
            <h2 class="text-2xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 7.5h3M19.5 7.5V9m-3-1.5h-1.5m0 0V6M15 12h3m-3 0v-1.5M15 12v1.5M3 12h6m-6 0V9m0 3v1.5M3 12H1.5m3 0h6m0 0v1.5m0-1.5V9" />
                </svg>
                Who is Life Portal for?
            </h2>
            <p>Life Portal supports various LAIC business units, providing tailored access for:</p>
            <ul class="list-disc list-inside space-y-1 pl-4">
                <li><strong>Employees</strong> — Track time, manage leaves, and access payslips.</li>
                <li><strong>HR</strong> — Monitor work hours and oversee team performance.</li>
                <li><strong>Finance</strong> — Automate payroll and generate payslips effortlessly.</li>
            </ul>
        </section>

        <!-- Ripples Section -->
        <section class="space-y-4">
            <h2 class="text-2xl font-semibold flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 stroke-current" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M12 3v6m0 0l3-3m-3 3l-3-3M12 15v6m0 0l3-3m-3 3l-3-3" />
                </svg>
                Life Ripples
            </h2>
            <p>Life Ripples is an organization-wide social feed designed for real-time communication. Employees can
                share memos, announcements, and engage with posts through comments and reactions — keeping everyone
                connected and in the loop.</p>
        </section>

        <!-- Final Section -->
        <section class="text-center mt-6">
            <h2 class="text-2xl font-semibold mb-2">🚀 Welcome to the Future of LAIC Operations!</h2>
            <p>Life Portal empowers you to work smarter, faster, and more connected than ever before.</p>
        </section>

        <!-- Go to Dashboard Button -->
        <div class="text-center mt-8">
            <flux:button variant="outline" href="{{ route('dashboard') }}">
                Go to Dashboard
            </flux:button>
        </div>

        <!-- Footer -->
        <footer class="mt-10 text-center text-gray-500 dark:text-gray-400 text-sm">
            <p>&copy; {{ date('Y') }} Life Academy International College. All rights reserved.</p>
            <p>Powered by the <span class="font-semibold">Digital Transformation Team at LAIC</span></p>
        </footer>
    </div>
</x-layouts.app>
