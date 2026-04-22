<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AiContentController as AdminAiContentController;
use App\Http\Controllers\Admin\BibleStudy\HistoricalContextController as AdminBibleStudyHistoricalContextController;
use App\Http\Controllers\Admin\BibleStudy\InsightController as AdminBibleStudyInsightController;
use App\Http\Controllers\Admin\BibleStudy\PassageController as AdminBibleStudyPassageController;
use App\Http\Controllers\Admin\BibleStudy\ThemeController as AdminBibleStudyThemeController;
use App\Http\Controllers\Admin\BibleStudy\WordHighlightController as AdminBibleStudyWordHighlightController;
use App\Http\Controllers\Admin\DevotionalEntryController as AdminDevotionalEntryController;
use App\Http\Controllers\Admin\SabbathSchool\QuarterlyController as AdminSabbathSchoolController;
use App\Http\Controllers\Admin\ThemeController as AdminThemeController;
use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\DevotionalEntryController;
use App\Http\Controllers\DevotionalImageController;
use App\Http\Controllers\DevotionalSettingsController;
use App\Http\Controllers\EmailOtpController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ObservationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\ReadingPlanController;
use App\Http\Controllers\SabbathSchool\LessonController as SabbathSchoolLessonController;
use App\Http\Controllers\SabbathSchool\LessonDayCompletionController as SabbathSchoolCompletionController;
use App\Http\Controllers\SabbathSchool\LessonDayController as SabbathSchoolLessonDayController;
use App\Http\Controllers\SabbathSchool\LessonDayObservationController as SabbathSchoolObservationController;
use App\Http\Controllers\SabbathSchool\QuarterlyController as SabbathSchoolQuarterlyController;
use App\Http\Controllers\ScriptureController;
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
use App\Http\Controllers\WordStudyController;
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

    // Devotional Image Generation...
    Route::post('entries/{entry}/generate-image', new DevotionalImageController()->store(...))->name('entries.generate-image');

    // Observations...
    Route::post('entries/{entry}/observations', new ObservationController()->store(...))->name('observations.store');
    Route::put('observations/{observation}', new ObservationController()->update(...))->name('observations.update');
    Route::delete('observations/{observation}', new ObservationController()->destroy(...))->name('observations.destroy');

    // Scripture passages...
    Route::get('scripture', [ScriptureController::class, 'show'])->name('scripture.show');
    Route::get('scripture/chapter', [ScriptureController::class, 'chapter'])->name('scripture.chapter');

    // Bookmarks...
    Route::get('bookmarks', new BookmarkController()->index(...))->name('bookmarks.index');
    Route::post('bookmarks', new BookmarkController()->store(...))->name('bookmarks.store');
    Route::delete('bookmarks/{bookmark}', new BookmarkController()->destroy(...))->name('bookmarks.destroy');

    // Bible Study / Reading Plans...
    Route::get('bible-study', new ReadingPlanController()->index(...))->name('bible-study.index');
    Route::get('bible-study/reading-plan/{readingPlan}', new ReadingPlanController()->show(...))->name('bible-study.reading-plan.show');
    Route::post('bible-study/reading-plan/{readingPlan}/activate', new ReadingPlanController()->activate(...))->name('bible-study.reading-plan.activate');
    Route::post('bible-study/reading-plan/day/{day}/complete', new ReadingPlanController()->completeDay(...))->name('bible-study.reading-plan.complete-day');

    // Word Study...
    Route::get('bible-study/word-study/search', [WordStudyController::class, 'search'])->name('bible-study.word-study.search');
    Route::get('bible-study/word-study/{wordStudy}', [WordStudyController::class, 'show'])->name('bible-study.word-study.show');

    // Sabbath School...
    Route::get('sabbath-school', new SabbathSchoolQuarterlyController()->index(...))->name('sabbath-school.index');
    Route::get('sabbath-school/{quarterly}', new SabbathSchoolQuarterlyController()->show(...))->name('sabbath-school.show');
    Route::get('sabbath-school/{quarterly}/lessons/{lesson}', new SabbathSchoolLessonController()->show(...))->name('sabbath-school.lessons.show');
    Route::get('sabbath-school/{quarterly}/lessons/{lesson}/days/{lessonDay}', new SabbathSchoolLessonDayController()->show(...))->name('sabbath-school.lessons.days.show');
    Route::post('sabbath-school/days/{lessonDay}/complete', new SabbathSchoolCompletionController()->store(...))->name('sabbath-school.days.complete');
    Route::delete('sabbath-school/days/{lessonDay}/complete', new SabbathSchoolCompletionController()->destroy(...))->name('sabbath-school.days.uncomplete');
    Route::post('sabbath-school/days/{lessonDay}/observations', new SabbathSchoolObservationController()->store(...))->name('sabbath-school.observations.store');
    Route::put('sabbath-school/observations/{lessonDayObservation}', new SabbathSchoolObservationController()->update(...))->name('sabbath-school.observations.update');
    Route::delete('sabbath-school/observations/{lessonDayObservation}', new SabbathSchoolObservationController()->destroy(...))->name('sabbath-school.observations.destroy');

    // Notifications...
    Route::get('notifications', new NotificationController()->index(...))->name('notifications.index');
    Route::put('notifications/preferences', new NotificationController()->updatePreferences(...))->name('notifications.preferences.update');

    // Devotional Settings...
    Route::delete('settings/social/{provider}', new DevotionalSettingsController()->disconnectSocialAccount(...))->name('settings.disconnect-social');

    // Partner...
    Route::post('partner', new PartnerController()->store(...))->name('partner.store');
    Route::delete('partner', new PartnerController()->destroy(...))->name('partner.destroy');
});

Route::middleware('auth')->group(function (): void {
    // User...
    Route::delete('user', [UserController::class, 'destroy'])->name('user.destroy');

    // Settings...
    Route::get('settings', new DevotionalSettingsController()->index(...))->name('settings.index');

    // User Profile...
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
    Route::put('themes/{theme}/unpublish', new AdminThemeController()->unpublish(...))->name('themes.unpublish');
    Route::post('themes/{theme}/generate-image', new AdminThemeController()->generateImage(...))->name('themes.generate-image');

    // Admin Devotional Entries...
    Route::get('themes/{theme}/entries', new AdminDevotionalEntryController()->index(...))->name('themes.entries.index');
    Route::get('themes/{theme}/entries/create', new AdminDevotionalEntryController()->create(...))->name('themes.entries.create');
    Route::put('themes/{theme}/entries/reorder', new AdminDevotionalEntryController()->reorder(...))->name('themes.entries.reorder');
    Route::post('themes/{theme}/entries', new AdminDevotionalEntryController()->store(...))->name('themes.entries.store');
    Route::get('themes/{theme}/entries/{entry}/edit', new AdminDevotionalEntryController()->edit(...))->name('themes.entries.edit');
    Route::put('themes/{theme}/entries/{entry}', new AdminDevotionalEntryController()->update(...))->name('themes.entries.update');
    Route::delete('themes/{theme}/entries/{entry}', new AdminDevotionalEntryController()->destroy(...))->name('themes.entries.destroy');
    Route::put('themes/{theme}/entries/{entry}/publish', new AdminDevotionalEntryController()->publish(...))->name('themes.entries.publish');
    Route::put('themes/{theme}/entries/{entry}/unpublish', new AdminDevotionalEntryController()->unpublish(...))->name('themes.entries.unpublish');

    // Admin AI Content...
    Route::get('ai-content/generate', new AdminAiContentController()->create(...))->name('ai-content.create');
    Route::post('ai-content/generate', new AdminAiContentController()->store(...))->name('ai-content.store');
    Route::post('ai-content/save', new AdminAiContentController()->save(...))->name('ai-content.save');

    // Admin Sabbath School...
    Route::get('sabbath-school', new AdminSabbathSchoolController()->index(...))->name('sabbath-school.index');
    Route::post('sabbath-school/import', new AdminSabbathSchoolController()->import(...))->name('sabbath-school.import');
    Route::post('sabbath-school/{quarterly}/sync', new AdminSabbathSchoolController()->sync(...))->name('sabbath-school.sync');
    Route::put('sabbath-school/{quarterly}/activate', new AdminSabbathSchoolController()->activate(...))->name('sabbath-school.activate');

    // Admin Bible Study...
    Route::get('bible-study/themes', new AdminBibleStudyThemeController()->index(...))->name('bible-study.themes.index');
    Route::post('bible-study/themes', new AdminBibleStudyThemeController()->store(...))->name('bible-study.themes.store');
    Route::get('bible-study/themes/{theme}', new AdminBibleStudyThemeController()->show(...))->name('bible-study.themes.show');
    Route::put('bible-study/themes/{theme}', new AdminBibleStudyThemeController()->update(...))->name('bible-study.themes.update');
    Route::put('bible-study/themes/{theme}/publish', new AdminBibleStudyThemeController()->publish(...))->name('bible-study.themes.publish');
    Route::delete('bible-study/themes/{theme}', new AdminBibleStudyThemeController()->destroy(...))->name('bible-study.themes.destroy');

    // Admin Bible Study Passages...
    Route::post('bible-study/themes/{theme}/passages', new AdminBibleStudyPassageController()->store(...))->name('bible-study.themes.passages.store');
    Route::put('bible-study/themes/{theme}/passages/reorder', new AdminBibleStudyPassageController()->reorder(...))->name('bible-study.themes.passages.reorder');
    Route::put('bible-study/themes/{theme}/passages/{passage}', new AdminBibleStudyPassageController()->update(...))->name('bible-study.themes.passages.update');
    Route::delete('bible-study/themes/{theme}/passages/{passage}', new AdminBibleStudyPassageController()->destroy(...))->name('bible-study.themes.passages.destroy');

    // Admin Bible Study Passage Insight...
    Route::put('bible-study/passages/{passage}/insight', new AdminBibleStudyInsightController()->update(...))->name('bible-study.passages.insight.update');

    // Admin Bible Study Passage Historical Context...
    Route::put('bible-study/passages/{passage}/historical-context', new AdminBibleStudyHistoricalContextController()->update(...))->name('bible-study.passages.historical-context.update');

    // Admin Bible Study Passage Word Highlights...
    Route::post('bible-study/passages/{passage}/word-highlights', new AdminBibleStudyWordHighlightController()->store(...))->name('bible-study.passages.word-highlights.store');
    Route::delete('bible-study/passages/{passage}/word-highlights/{highlight}', new AdminBibleStudyWordHighlightController()->destroy(...))->name('bible-study.passages.word-highlights.destroy');
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
