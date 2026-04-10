<?php

declare(strict_types=1);

use App\Mail\OtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

it('renders the email OTP request page', function (): void {
    $response = $this->get(route('email-otp.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/email-otp'));
});

it('sends an OTP to a valid email', function (): void {
    Mail::fake();

    $response = $this->fromRoute('email-otp.create')
        ->post(route('email-otp.store'), [
            'email' => 'test@example.com',
        ]);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHas('email', 'test@example.com')
        ->assertSessionHas('status');

    $this->assertDatabaseHas('email_otps', [
        'email' => 'test@example.com',
    ]);
});

it('requires an email to send OTP', function (): void {
    $response = $this->fromRoute('email-otp.create')
        ->post(route('email-otp.store'), []);

    $response->assertRedirectToRoute('email-otp.create')
        ->assertSessionHasErrors('email');
});

it('requires a valid email to send OTP', function (): void {
    $response = $this->fromRoute('email-otp.create')
        ->post(route('email-otp.store'), [
            'email' => 'not-an-email',
        ]);

    $response->assertRedirectToRoute('email-otp.create')
        ->assertSessionHasErrors('email');
});

it('renders the email OTP verify page', function (): void {
    $response = $this->withSession(['email' => 'test@example.com'])
        ->get(route('email-otp.verify.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/email-otp-verify')
            ->where('email', 'test@example.com'));
});

it('renders the verify page with empty email when no session', function (): void {
    $response = $this->get(route('email-otp.verify.show'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/email-otp-verify')
            ->where('email', ''));
});

it('verifies a valid OTP and authenticates the user', function (): void {
    $code = '123456';

    EmailOtp::query()->create([
        'email' => 'test@example.com',
        'code_hash' => Hash::make($code),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'test@example.com',
            'code' => $code,
        ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->assertDatabaseMissing('email_otps', [
        'email' => 'test@example.com',
    ]);
});

it('creates a new user when verifying OTP for unknown email', function (): void {
    $code = '654321';

    EmailOtp::query()->create([
        'email' => 'newuser@example.com',
        'code_hash' => Hash::make($code),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'newuser@example.com',
            'code' => $code,
        ]);

    $response->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});

it('fails verification with an incorrect code', function (): void {
    EmailOtp::query()->create([
        'email' => 'test@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'test@example.com',
            'code' => '000000',
        ]);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('requires email and code for verification', function (): void {
    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), []);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHasErrors(['email', 'code']);
});

it('requires code to be exactly 6 characters', function (): void {
    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'test@example.com',
            'code' => '123',
        ]);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHasErrors('code');
});

it('redirects authenticated users away from email OTP pages', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('email-otp.create'));
    $response->assertRedirectToRoute('dashboard');

    $response = $this->actingAs($user)->get(route('email-otp.verify.show'));
    $response->assertRedirectToRoute('dashboard');
});

it('asserts OTP mail is sent when requesting a code', function (): void {
    Mail::fake();

    $this->fromRoute('email-otp.create')
        ->post(route('email-otp.store'), [
            'email' => 'mail-check@example.com',
        ]);

    Mail::assertSent(OtpMail::class, fn (OtpMail $mail): bool => $mail->hasTo('mail-check@example.com'));
});

it('deletes previous OTPs when requesting a new one', function (): void {
    Mail::fake();

    EmailOtp::query()->create([
        'email' => 'test@example.com',
        'code_hash' => Hash::make('111111'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->fromRoute('email-otp.create')
        ->post(route('email-otp.store'), [
            'email' => 'test@example.com',
        ]);

    expect(EmailOtp::query()->where('email', 'test@example.com')->count())->toBe(1);
});

it('rate limits OTP requests', function (): void {
    Mail::fake();

    RateLimiter::clear('send-email-otp:ratelimit@example.com');

    for ($i = 0; $i < 5; $i++) {
        $this->fromRoute('email-otp.create')
            ->post(route('email-otp.store'), [
                'email' => 'ratelimit@example.com',
            ]);
    }

    $response = $this->fromRoute('email-otp.create')
        ->post(route('email-otp.store'), [
            'email' => 'ratelimit@example.com',
        ]);

    $response->assertRedirectToRoute('email-otp.create')
        ->assertSessionHasErrors('email');
});

it('fails verification when OTP has expired', function (): void {
    EmailOtp::query()->create([
        'email' => 'expired@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'expires_at' => now()->subMinute(),
    ]);

    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'expired@example.com',
            'code' => '123456',
        ]);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHasErrors('code');

    $this->assertGuest();
    $this->assertDatabaseMissing('email_otps', [
        'email' => 'expired@example.com',
    ]);
});

it('fails verification when max attempts exceeded', function (): void {
    EmailOtp::query()->create([
        'email' => 'maxattempts@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 3,
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'maxattempts@example.com',
            'code' => '123456',
        ]);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHasErrors('code');

    $this->assertGuest();
    $this->assertDatabaseMissing('email_otps', [
        'email' => 'maxattempts@example.com',
    ]);
});

it('fails verification when no OTP exists for the email', function (): void {
    $response = $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'nootp@example.com',
            'code' => '123456',
        ]);

    $response->assertRedirectToRoute('email-otp.verify.show')
        ->assertSessionHasErrors('code');

    $this->assertGuest();
});

it('increments attempts on incorrect code', function (): void {
    EmailOtp::query()->create([
        'email' => 'attempts@example.com',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->fromRoute('email-otp.verify.show')
        ->post(route('email-otp.verify'), [
            'email' => 'attempts@example.com',
            'code' => '000000',
        ]);

    expect(EmailOtp::query()->where('email', 'attempts@example.com')->sole()->attempts)->toBe(1);
});
