@props(['title', 'subtitle' => '', 'internal' => false])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $title }} · LP Facilities</title>
    @vite(['resources/css/app.css'])
</head>
<body class="facility-portal">
    <header class="facility-topbar">
        <div class="facility-topbar-inner">
            <div class="facility-brand">
                <img src="{{ asset('images/life_logo.svg') }}" alt="LIFE" width="40" height="40">
                <div><strong>Life Portal</strong><span>Facilities & events</span></div>
            </div>
            @if($internal)
                <nav class="facility-header-actions" aria-label="Account navigation">
                    <a class="facility-button facility-button--quiet" href="{{ route('dashboard') }}"><x-facilities.icon name="home" /> Portal</a>
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="facility-button facility-button--outline" type="submit">Log out</button></form>
                </nav>
            @else
                <span class="facility-private"><x-facilities.icon name="lock" /> Private reservation link</span>
            @endif
        </div>
    </header>
    <main class="facility-main">
        <div class="facility-page-heading">
            <p class="facility-eyebrow">{{ $internal ? 'Finance workspace' : 'Your reservation' }}</p>
            <h1>{{ $title }}</h1>
            @if($subtitle)<p class="facility-subtitle">{{ $subtitle }}</p>@endif
        </div>
        @if($errors->any())
            <div role="alert" class="facility-alert facility-alert--red"><x-facilities.icon name="alert" /><div><strong>Please check the following</strong>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div></div>
        @endif
        {{ $slot }}
        <footer class="facility-footer">
            <span>Life Portal · Facilities</span>
            <span>Need help? <a href="mailto:events.facilities@life.edu.ph">events.facilities@life.edu.ph</a></span>
            <span>All schedules use Asia/Manila time.</span>
        </footer>
    </main>
</body>
</html>
