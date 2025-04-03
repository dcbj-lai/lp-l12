<?php

use App\Models\Step;
use App\Models\Ripple;
use Livewire\Volt\Volt;
use App\Livewire\RippleFeed;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StepController;
use App\Http\Controllers\UserController;
use App\Livewire\Attendance\MyAttendance;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\UtilityController;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\AttendanceController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// Route::view('dashboard', 'dashboard')
//     ->middleware(['auth', 'verified'])
//     ->name('dashboard');
Route::get('dashboard', function () {
    return view('dashboard');
})
->middleware(['auth', 'verified'])
->name('dashboard');


Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

// Google Routes
Route::controller(GoogleController::class)->group(function() {
    Route::get('auth/google', 'googleLogin')->name('auth.google');
    Route::get('auth/google-callback', 'googleAuthenticate')->name('auth.google-callback');
});

/**Attendance routes */
Route::middleware('auth')->group(function () {
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.check_in');

    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.check_out');
    Route::get('/attendance', [AttendanceController::class, 'index'])
    ->middleware('can:is-pnc')
    ->name('attendance.index');
    Route::get('/attendance/week', [AttendanceController::class, 'week'])->name('attendance.week');

});
Route::get('/my-attendance',[AttendanceController::class, 'myAttendance'])
    ->middleware('auth')
    ->name('attendance.my_attendance');


 // HR-Only Routes
Route::middleware(['auth','can:is-pnc'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}/payroll', [UserController::class, 'togglePayroll'])->name('users.togglePayroll');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update'); 
});


// Finance Routes
Route::middleware(['auth', 'can:is-finance'])->group(function () {
    Route::get('/payouts', [PayrollController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/{control_number}/edit', [PayrollController::class, 'edit'])->name('payouts.edit');
    Route::post('/payouts', [PayrollController::class, 'store'])->name('payouts.store');
    Route::post('/payroll/generate/{control_number}', [PayrollController::class, 'generate'])->name('payroll.generate');

    Route::post('/users/{user}/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
    Route::delete('/adjustments/{adjustment}', [AdjustmentController::class, 'destroy'])->name('adjustments.destroy');

    Route::post('/adjustments/package', [AdjustmentController::class, 'applyPackage'])->name('adjustments.package');
    Route::delete('/adjustments/package', [AdjustmentController::class, 'applyPackage'])->name('adjustments.package');

});

Route::middleware(['auth'])->group(function () {
    Route::get('/payslips', [PayrollController::class, 'userPayslips'])->name('payslips.index');
    Route::get('/payslips/{payslip}', [PayrollController::class, 'showPayslip'])->name('payslips.show');
    Route::get('/payslips/{payslip}/download', [PayrollController::class, 'downloadPayslip'])->name('payslips.download');
    
});


Route::get('/ripple', fn() => view('ripple.index'))
    ->name('ripple')
    ->middleware('auth');

Route::get('/ripples/download/{ripple}', function (Ripple $ripple) {
        if (auth()->id() !== $ripple->user_id) {
            abort(403);
        }
        
        return Storage::download($ripple->file_path);
    })->name('ripples.download')->middleware('auth');


/**Steps */


Route::middleware('auth')->group(function () {
    Route::get('/my-steps', [StepController::class, 'index'])->name('my-steps.index');
    Route::post('/my-steps', [StepController::class, 'store'])->name('my-steps.store');
});


Route::get('/steps', fn() => view('steps.index'))
    ->name('steps.index')
    ->middleware('auth');

// About

Route::get('/about', [UtilityController::class, 'about'])->name('about');


require __DIR__.'/auth.php';
