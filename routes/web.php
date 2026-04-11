<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AiContentController as AdminAiContentController;
use App\Http\Controllers\Admin\DevotionalEntryController as AdminDevotionalEntryController;
use App\Http\Controllers\Admin\ThemeController as AdminThemeController;
use App\Http\Controllers\DevotionalEntryController;
use App\Http\Controllers\EmailOtpController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserEmailResetNotification;
use App\Http\Controllers\UserEmailVerification;
use App\Http\Controllers\UserEmailVerificationNotificationController;
use App\Http\Controllers\UserPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\UserTwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn () => Inertia::render('dashboard'))->name('dashboard');

    // User-facing Themes...
    Route::get('themes', new ThemeController()->index(...))->name('themes.index');
    Route::get('themes/{theme}', new ThemeController()->show(...))->name('themes.show');

    // User-facing Devotional Entries...
    Route::get('themes/{theme}/entries/{entry}', new DevotionalEntryController()->show(...))->name('themes.entries.show');
    Route::post('themes/{theme}/entries/{entry}/complete', new DevotionalEntryController()->complete(...))->name('themes.entries.complete');

    // Bible Study / Reading Plans...
    Route::get('bible-study', new ReadingPlanController()->index(...))->name('bible-study.index');
    Route::get('bible-study/reading-plan/{readingPlan}', new ReadingPlanController()->show(...))->name('bible-study.reading-plan.show');
    Route::post('bible-study/reading-plan/{readingPlan}/activate', new ReadingPlanController()->activate(...))->name('bible-study.reading-plan.activate');
    Route::post('bible-study/reading-plan/day/{day}/complete', new ReadingPlanController()->completeDay(...))->name('bible-study.reading-plan.complete-day');
});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // User Profile...
    Route::redirect('settings', '/settings/profile');
    Route::get('settings/profile', [UserProfileController::class, 'edit'])->name('user-profile.edit');
    Route::patch('settings/profile', [UserProfileController::class, 'update'])->name('user-profile.update');

    // User Password...
    Route::get('settings/password', [UserPasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [UserPasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('password.update');

    // Appearance...
    Route::get('settings/appearance', fn () => Inertia::render('appearance/update'))->name('appearance.edit');

    // User Two-Factor Authentication...
    Route::get('settings/two-factor', [UserTwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
});

Route::middleware('guest')->group(function (): void {
    // User...
    Route::get('register', [UserController::class, 'create'])
        ->name('register');
    Route::post('register', [UserController::class, 'store'])
        ->name('register.store');

    // User Password...
    Route::get('reset-password/{token}', [UserPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [UserPasswordController::class, 'store'])
        ->name('password.store');

    // User Email Reset Notification...
    Route::get('forgot-password', [UserEmailResetNotification::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [UserEmailResetNotification::class, 'store'])
        ->name('password.email');

    // Session...
    Route::get('login', [SessionController::class, 'create'])
        ->name('login');
    Route::post('login', [SessionController::class, 'store'])
        ->name('login.store');

    // Social Login...
    Route::get('auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
        ->name('social.redirect');
    Route::get('auth/{provider}/callback', [SocialLoginController::class, 'callback'])
        ->name('social.callback');

    // Email OTP...
    Route::get('auth/email-otp', [EmailOtpController::class, 'create'])
        ->name('email-otp.create');
    Route::post('auth/email-otp', [EmailOtpController::class, 'store'])
        ->name('email-otp.store');
    Route::get('auth/email-otp/verify', [EmailOtpController::class, 'showVerify'])
        ->name('email-otp.verify.show');
    Route::post('auth/email-otp/verify', [EmailOtpController::class, 'verify'])
        ->name('email-otp.verify');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    // Admin Themes...
    Route::get('themes', new AdminThemeController()->index(...))->name('themes.index');
    Route::get('themes/create', new AdminThemeController()->create(...))->name('themes.create');
    Route::post('themes', new AdminThemeController()->store(...))->name('themes.store');
    Route::get('themes/{theme}/edit', new AdminThemeController()->edit(...))->name('themes.edit');
    Route::put('themes/{theme}', new AdminThemeController()->update(...))->name('themes.update');
    Route::delete('themes/{theme}', new AdminThemeController()->destroy(...))->name('themes.destroy');
    Route::put('themes/{theme}/publish', new AdminThemeController()->publish(...))->name('themes.publish');

    // Admin Devotional Entries...
    Route::get('themes/{theme}/entries', new AdminDevotionalEntryController()->index(...))->name('themes.entries.index');
    Route::get('themes/{theme}/entries/create', new AdminDevotionalEntryController()->create(...))->name('themes.entries.create');
    Route::put('themes/{theme}/entries/reorder', new AdminDevotionalEntryController()->reorder(...))->name('themes.entries.reorder');
    Route::post('themes/{theme}/entries', new AdminDevotionalEntryController()->store(...))->name('themes.entries.store');
    Route::get('themes/{theme}/entries/{entry}/edit', new AdminDevotionalEntryController()->edit(...))->name('themes.entries.edit');
    Route::put('themes/{theme}/entries/{entry}', new AdminDevotionalEntryController()->update(...))->name('themes.entries.update');
    Route::delete('themes/{theme}/entries/{entry}', new AdminDevotionalEntryController()->destroy(...))->name('themes.entries.destroy');
    Route::put('themes/{theme}/entries/{entry}/publish', new AdminDevotionalEntryController()->publish(...))->name('themes.entries.publish');

    // Admin AI Content...
    Route::get('ai-content/generate', new AdminAiContentController()->create(...))->name('ai-content.create');
    Route::post('ai-content/generate', new AdminAiContentController()->store(...))->name('ai-content.store');
});

Route::middleware('auth')->group(function (): void {
    // User Email Verification...
    Route::get('verify-email', [UserEmailVerificationNotificationController::class, 'create'])
        ->name('verification.notice');
    Route::post('email/verification-notification', [UserEmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // User Email Verification...
    Route::get('verify-email/{id}/{hash}', [UserEmailVerification::class, 'update'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Session...
    Route::post('logout', [SessionController::class, 'destroy'])
        ->name('logout');
});
