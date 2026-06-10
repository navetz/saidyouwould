<?php

namespace App\Console\Commands;

use App\Contracts\ChallengeMailer;
use App\Contracts\VideoStorage;
use App\Models\OylChallenge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class DeliverDueOylChallenges extends Command
{
    protected $signature = 'oyl:deliver-due {--limit=100 : Maximum challenges to process}';

    protected $description = 'Email signed video links for due Said You Would challenges';

    public function handle(ChallengeMailer $mailer, VideoStorage $videos): int
    {
        $this->purgeAbandoned($videos);

        $challenges = OylChallenge::query()
            ->where('status', 'pending')
            ->whereDate('delivery_date', '<=', today())
            ->orderBy('id')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $sent = 0;
        foreach ($challenges as $challenge) {
            try {
                $token = Crypt::decryptString($challenge->recipient_token_encrypted);
                if ($mailer->sendFutureDelivery($challenge, $token)) {
                    $challenge->update(['status' => 'delivered', 'delivered_at' => now()]);
                    $sent++;
                    $this->line('Delivered challenge #'.$challenge->id);
                } else {
                    $this->warn('Email failed for challenge #'.$challenge->id);
                }
            } catch (Throwable $exception) {
                report($exception);
                $this->error('Could not process challenge #'.$challenge->id.': '.$exception->getMessage());
            }
        }

        $this->info("Processed {$challenges->count()} due challenge(s); delivered {$sent}.");

        return $sent === $challenges->count() ? self::SUCCESS : self::FAILURE;
    }

    private function purgeAbandoned(VideoStorage $videos): void
    {
        $abandoned = OylChallenge::query()
            ->where('status', 'awaiting_payment')
            ->where('payment_status', 'unpaid')
            ->where('created_at', '<', now()->subHours(48))
            ->limit(100)
            ->get();

        foreach ($abandoned as $challenge) {
            try {
                $videos->delete($challenge->video_storage_key);
                $challenge->delete();
                $this->line('Purged abandoned challenge #'.$challenge->id);
            } catch (Throwable $exception) {
                report($exception);
                $this->warn('Could not purge challenge #'.$challenge->id);
            }
        }
    }
}
