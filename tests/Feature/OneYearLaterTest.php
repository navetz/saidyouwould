<?php

namespace Tests\Feature;

use App\Contracts\PaymentGateway;
use App\Contracts\VideoStorage;
use App\Mail\ChallengeSealedReceipt;
use App\Mail\FutureChallengeDelivery;
use App\Mail\ImmediateChallengeNotice;
use App\Models\OylChallenge;
use App\Services\ChallengeLinks;
use App\Services\StripeCheckoutGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class OneYearLaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_sender_gets_a_direct_video_upload_url(): void
    {
        $videos = $this->fakeVideoStorage();

        $this->postJson('/api/uploads', [
            'filename' => 'promise.mp4',
            'content_type' => 'video/mp4',
            'size' => 1024,
        ])->assertCreated()
            ->assertJsonPath('storage_key', 'one-year-later/videos/test.mp4')
            ->assertJsonPath('upload_url', 'https://r2.example/upload');

        $this->assertSame('promise.mp4', $videos->uploadRequest['filename']);
    }

    public function test_creating_a_challenge_requires_payment_before_any_email(): void
    {
        $videos = $this->fakeVideoStorage();
        $this->fakePayments();
        Mail::fake();

        $response = $this->postJson('/api/challenges', $this->challengePayload());

        $response->assertCreated()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.example/session')
            ->assertJsonMissingPath('challenge.recipient_email');

        $this->assertSame('one-year-later/videos/test.mp4', $videos->validatedKey);
        $this->assertDatabaseHas('oyl_challenges', [
            'recipient_email' => 'friend@example.com',
            'status' => 'awaiting_payment',
            'payment_status' => 'unpaid',
            'stripe_session_id' => 'cs_test_123',
        ]);
        Mail::assertNothingSent();
    }

    public function test_a_challenge_needs_no_written_promise(): void
    {
        $this->fakeVideoStorage();
        $this->fakePayments();
        Mail::fake();

        $this->postJson('/api/challenges', $this->challengePayload([
            'goal_title' => null,
            'goal_description' => null,
        ]))->assertCreated();

        $this->assertDatabaseHas('oyl_challenges', [
            'goal_title' => null,
            'status' => 'awaiting_payment',
        ]);
    }

    public function test_friend_challenges_require_a_recipient_email(): void
    {
        $this->fakeVideoStorage();
        $this->fakePayments();

        $this->postJson('/api/challenges', $this->challengePayload(['recipient_email' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('recipient_email');
    }

    public function test_paid_webhook_activates_challenge_and_notifies_recipient(): void
    {
        $this->fakeVideoStorage();
        $payments = $this->fakePayments();
        Mail::fake();

        $this->postJson('/api/challenges', $this->challengePayload(['notify_recipient' => true]))->assertCreated();
        $challenge = OylChallenge::sole();

        $this->postWebhook($payments, $challenge)->assertOk();

        $challenge->refresh();
        $this->assertSame('pending', $challenge->status);
        $this->assertSame('paid', $challenge->payment_status);
        $this->assertSame(500, $challenge->amount_cents);
        Mail::assertSent(ImmediateChallengeNotice::class, fn ($mail) => $mail->hasTo('friend@example.com'));
        Mail::assertSent(ChallengeSealedReceipt::class, fn ($mail) => $mail->hasTo('alex@example.com'));
    }

    public function test_silent_mode_sends_no_email_to_the_recipient(): void
    {
        $this->fakeVideoStorage();
        $payments = $this->fakePayments();
        Mail::fake();

        // Only the one-line promise is required; the description is optional.
        $this->postJson('/api/challenges', $this->challengePayload(['notify_recipient' => false, 'goal_description' => null]))->assertCreated();
        $challenge = OylChallenge::sole();

        $this->postWebhook($payments, $challenge)->assertOk();

        $this->assertSame('pending', $challenge->fresh()->status);
        Mail::assertNotSent(ImmediateChallengeNotice::class);
        Mail::assertSent(ChallengeSealedReceipt::class, fn ($mail) => $mail->hasTo('alex@example.com'));
    }

    public function test_self_mode_targets_the_sender_and_needs_no_recipient(): void
    {
        $this->fakeVideoStorage();
        $payments = $this->fakePayments();
        Mail::fake();

        $this->postJson('/api/challenges', $this->challengePayload([
            'mode' => 'self',
            'recipient_email' => null,
            'recipient_name' => null,
            'notify_recipient' => null,
        ]))->assertCreated();

        $challenge = OylChallenge::sole();
        $this->assertSame('alex@example.com', $challenge->recipient_email);
        $this->assertFalse($challenge->notify_recipient);

        $this->postWebhook($payments, $challenge)->assertOk();
        Mail::assertNotSent(ImmediateChallengeNotice::class);
        Mail::assertSent(ChallengeSealedReceipt::class, fn ($mail) => $mail->hasTo('alex@example.com'));
    }

    public function test_anonymous_challenges_never_reveal_the_sender_to_the_recipient(): void
    {
        $this->fakeVideoStorage();
        $payments = $this->fakePayments();
        Mail::fake();

        $this->postJson('/api/challenges', $this->challengePayload([
            'anonymous' => true,
            'notify_recipient' => true,
        ]))->assertCreated();

        $challenge = OylChallenge::sole();
        $this->assertTrue($challenge->anonymous);
        $this->postWebhook($payments, $challenge)->assertOk();

        $mailable = new ImmediateChallengeNotice($challenge->fresh(), 'https://example.com/ack');
        $this->assertStringNotContainsString('Alex', $mailable->envelope()->subject);
        $notice = $mailable->render();
        $this->assertStringNotContainsString('Alex', $notice);
        $this->assertStringContainsString('Someone who knows you', $notice);

        $delivery = (new FutureChallengeDelivery($challenge->fresh(), 'https://example.com/watch'))->render();
        $this->assertStringNotContainsString('Alex', $delivery);

        [$anonymous, $token] = $this->challenge(['anonymous' => true]);
        $this->get(app(ChallengeLinks::class)->acknowledgement($anonymous, $token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('challenge.sender_name', null)
                ->where('challenge.anonymous', true));
    }

    public function test_self_mode_is_never_anonymous(): void
    {
        $this->fakeVideoStorage();
        $this->fakePayments();

        $this->postJson('/api/challenges', $this->challengePayload([
            'mode' => 'self',
            'recipient_email' => null,
            'recipient_name' => null,
            'notify_recipient' => null,
            'anonymous' => true,
        ]))->assertCreated();

        $this->assertFalse(OylChallenge::sole()->anonymous);
    }

    public function test_success_page_verifies_payment_with_stripe_and_activates(): void
    {
        $this->fakeVideoStorage();
        $payments = $this->fakePayments();
        Mail::fake();

        $this->postJson('/api/challenges', $this->challengePayload())->assertCreated();
        $challenge = OylChallenge::sole();
        $payments->paidSessions['cs_test_123'] = ['paid' => true, 'amount_total' => 500, 'currency' => 'usd'];

        $this->get('/challenge-created?session_id=cs_test_123')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Oyl/Created')
                ->where('state', 'sealed')
                ->where('challenge.goal_title', 'Run the Chicago Marathon'));

        $this->assertSame('paid', $challenge->fresh()->payment_status);
        Mail::assertSent(ChallengeSealedReceipt::class);
    }

    public function test_activation_is_idempotent_between_webhook_and_success_page(): void
    {
        $this->fakeVideoStorage();
        $payments = $this->fakePayments();
        Mail::fake();

        $this->postJson('/api/challenges', $this->challengePayload(['notify_recipient' => true]))->assertCreated();
        $challenge = OylChallenge::sole();

        $this->postWebhook($payments, $challenge)->assertOk();
        $this->postWebhook($payments, $challenge)->assertOk();

        Mail::assertSent(ImmediateChallengeNotice::class, 1);
        Mail::assertSent(ChallengeSealedReceipt::class, 1);
    }

    public function test_webhook_rejects_invalid_signatures(): void
    {
        $this->fakePayments();

        $this->call(
            'POST',
            '/api/stripe/webhook',
            server: ['HTTP_STRIPE_SIGNATURE' => 'bogus', 'CONTENT_TYPE' => 'application/json'],
            content: '{}',
        )->assertStatus(400);
    }

    public function test_stripe_webhook_signature_verification(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $gateway = new StripeCheckoutGateway();

        $payload = '{"type":"checkout.session.completed"}';
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_test');

        $this->assertTrue($gateway->verifyWebhook($payload, "t={$timestamp},v1={$signature}"));
        $this->assertFalse($gateway->verifyWebhook($payload, "t={$timestamp},v1=deadbeef"));
        $this->assertFalse($gateway->verifyWebhook($payload, 't='.($timestamp - 4000).",v1={$signature}"));
    }

    public function test_signed_acknowledgement_link_works_and_can_be_acknowledged(): void
    {
        [$challenge, $token] = $this->challenge();
        $url = app(ChallengeLinks::class)->acknowledgement($challenge, $token);

        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->component('Oyl/Recipient')
            ->where('mode', 'acknowledge')
            ->where('challenge.goal_title', 'Write the book'));

        $this->postJson('/api/recipient/'.$token.'/acknowledge')->assertOk();
        $this->assertNotNull($challenge->fresh()->acknowledged_at);
    }

    public function test_delivery_command_sends_due_video_link_and_marks_challenge_delivered(): void
    {
        Mail::fake();
        [$challenge] = $this->challenge(['delivery_date' => today()]);

        $this->artisan('oyl:deliver-due')->assertSuccessful();

        $this->assertSame('delivered', $challenge->fresh()->status);
        Mail::assertSent(FutureChallengeDelivery::class, fn ($mail) => $mail->hasTo('friend@example.com'));
    }

    public function test_delivery_command_purges_abandoned_unpaid_challenges(): void
    {
        Mail::fake();
        $this->fakeVideoStorage();
        [$abandoned] = $this->challenge(['status' => 'awaiting_payment', 'payment_status' => 'unpaid']);
        $abandoned->forceFill(['created_at' => now()->subDays(3)])->save();
        [$fresh] = $this->challenge(['status' => 'awaiting_payment', 'payment_status' => 'unpaid']);

        $this->artisan('oyl:deliver-due')->assertSuccessful();

        $this->assertDatabaseMissing('oyl_challenges', ['id' => $abandoned->id]);
        $this->assertDatabaseHas('oyl_challenges', ['id' => $fresh->id]);
    }

    public function test_delivered_video_redirects_to_private_storage_and_accepts_an_answer(): void
    {
        $this->fakeVideoStorage();
        [$challenge, $token] = $this->challenge([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        $videoUrl = app(ChallengeLinks::class)->video($challenge, $token);
        $this->get($videoUrl)->assertRedirect('https://r2.example/private-video');

        $this->postJson('/api/recipient/'.$token.'/response', [
            'response' => 'did_it',
            'response_note' => 'Finished the draft in March.',
        ])->assertOk()->assertJsonPath('response.response', 'did_it');

        $this->assertDatabaseHas('oyl_recipient_responses', [
            'challenge_id' => $challenge->id,
            'response' => 'did_it',
        ]);
    }

    private function challengePayload(array $overrides = []): array
    {
        return array_merge([
            'video_storage_key' => 'one-year-later/videos/test.mp4',
            'video_upload_token' => 'verified-upload-token',
            'mode' => 'friend',
            'notify_recipient' => false,
            'recipient_email' => 'friend@example.com',
            'recipient_name' => 'Maya',
            'goal_title' => 'Run the Chicago Marathon',
            'goal_description' => 'Finish the race, no matter the time.',
            'delivery_date' => today()->addYear()->toDateString(),
            'written_terms' => 'You said you would put $1,000 on this.',
            'sender_email' => 'alex@example.com',
            'sender_name' => 'Alex',
            'terms_accepted' => true,
            'website' => '',
        ], $overrides);
    }

    private function postWebhook(FakePaymentGateway $payments, OylChallenge $challenge)
    {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => $challenge->stripe_session_id,
                'payment_status' => 'paid',
                'amount_total' => 500,
                'currency' => 'usd',
            ]],
        ]);

        return $this->call(
            'POST',
            '/api/stripe/webhook',
            server: ['HTTP_STRIPE_SIGNATURE' => $payments->validSignature, 'CONTENT_TYPE' => 'application/json'],
            content: $payload,
        );
    }

    private function fakeVideoStorage(): FakeVideoStorage
    {
        $fake = new FakeVideoStorage();
        $this->app->instance(VideoStorage::class, $fake);

        return $fake;
    }

    private function fakePayments(): FakePaymentGateway
    {
        $fake = new FakePaymentGateway();
        $this->app->instance(PaymentGateway::class, $fake);

        return $fake;
    }

    private function challenge(array $overrides = []): array
    {
        $token = Str::random(64);
        $challenge = OylChallenge::create(array_merge([
            'sender_email' => 'alex@example.com',
            'sender_name' => 'Alex',
            'mode' => 'friend',
            'notify_recipient' => true,
            'recipient_email' => 'friend@example.com',
            'recipient_name' => 'Maya',
            'goal_title' => 'Write the book',
            'goal_description' => 'Complete a full first draft.',
            'written_terms' => null,
            'video_storage_key' => 'one-year-later/videos/test.mp4',
            'delivery_date' => today()->addYear(),
            'status' => 'pending',
            'payment_status' => 'paid',
            'paid_at' => now(),
            'recipient_token_hash' => hash('sha256', $token),
            'recipient_token_encrypted' => Crypt::encryptString($token),
        ], $overrides));

        return [$challenge, $token];
    }
}

class FakeVideoStorage implements VideoStorage
{
    public array $uploadRequest = [];

    public ?string $validatedKey = null;

    public array $deleted = [];

    public function createUpload(string $filename, string $contentType, int $size): array
    {
        $this->uploadRequest = compact('filename', 'contentType', 'size');

        return [
            'storage_key' => 'one-year-later/videos/test.mp4',
            'upload_url' => 'https://r2.example/upload',
            'upload_headers' => ['Content-Type' => 'video/mp4'],
            'upload_token' => 'verified-upload-token',
        ];
    }

    public function validateUploaded(string $storageKey, string $uploadToken): void
    {
        $this->validatedKey = $storageKey;
    }

    public function delete(string $storageKey): void
    {
        $this->deleted[] = $storageKey;
    }

    public function temporaryUrl(string $storageKey): string
    {
        return 'https://r2.example/private-video';
    }
}

class FakePaymentGateway implements PaymentGateway
{
    public string $validSignature = 'test-signature';

    public array $paidSessions = [];

    public function createCheckoutSession(OylChallenge $challenge, string $successUrl, string $cancelUrl): array
    {
        return ['id' => 'cs_test_123', 'url' => 'https://checkout.stripe.example/session'];
    }

    public function getCheckoutSession(string $sessionId): array
    {
        return $this->paidSessions[$sessionId]
            ?? ['paid' => false, 'amount_total' => null, 'currency' => null];
    }

    public function verifyWebhook(string $payload, string $signatureHeader): bool
    {
        return $signatureHeader === $this->validSignature;
    }
}
