<!doctype html>
<html lang="en">
<body style="margin:0;background:#171e1a;color:#f7f1e8;font-family:Arial,Helvetica,sans-serif;line-height:1.6">
<div style="max-width:620px;margin:0 auto;padding:52px 24px">
    <p style="font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#aab1ac;margin:0">Said You Would</p>
    <h1 style="font-family:Georgia,serif;font-size:42px;line-height:1.1;margin:16px 0;font-weight:400">Sealed.</h1>
    @if($challenge->mode === 'self')
        <p style="font-size:18px;margin:0">Your message to future you is locked. No edits, no previews, no reminders. The next time you see it will be the day it arrives.</p>
    @elseif($challenge->notify_recipient)
        <p style="font-size:18px;margin:0">{{ $challenge->recipient_name ?: $challenge->recipient_email }} has been put on notice today. The video itself stays sealed until the date below.</p>
    @else
        <p style="font-size:18px;margin:0">{{ $challenge->recipient_name ?: $challenge->recipient_email }} has <strong>no idea</strong>. Nothing was sent today, and nothing will be until the day the video lands. Keep it that way.</p>
    @endif
    @if($challenge->anonymous && $challenge->mode === 'friend')
        <p style="font-size:15px;margin:14px 0 0;color:#aab1ac">You chose to stay anonymous. Your name and email are never shown to them &mdash; what the video reveals is up to you.</p>
    @endif
    <div style="background:#101713;border:1px solid #34403a;border-radius:14px;padding:26px;margin:30px 0">
        <p style="font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:#8d978f;margin:0 0 6px">On the record</p>
        <h2 style="font-family:Georgia,serif;margin:0 0 12px;font-size:24px;font-weight:400;color:#f4b35d">{{ $challenge->goal_title ?: 'The video says it all.' }}</h2>
        <p style="margin:0 0 6px"><strong>Arrives:</strong> {{ $challenge->delivery_date->format('F j, Y') }}</p>
        <p style="margin:0 0 6px"><strong>To:</strong> {{ $challenge->mode === 'self' ? 'Future you ('.$challenge->recipient_email.')' : ($challenge->recipient_name ? $challenge->recipient_name.' ('.$challenge->recipient_email.')' : $challenge->recipient_email) }}</p>
        @if($challenge->amount_cents)
            <p style="margin:0"><strong>Stake:</strong> ${{ number_format($challenge->amount_cents / 100, 2) }}, paid</p>
        @endif
    </div>
    @if($challenge->is_public)
        <p style="font-size:15px;margin:0 0 10px">It is on the public board. The promise and the date show today; the video plays there the day it lands.<br><a href="{{ $challenge->publicUrl() }}" style="color:#e9aa55">{{ $challenge->publicUrl() }}</a></p>
    @endif
    <p style="font-size:13px;color:#aab1ac">There is nothing else to do. The year does the rest.</p>
    <p style="font-size:12px;color:#7e8881;margin-top:40px;border-top:1px solid #2c3630;padding-top:18px">
        Said You Would holds no money between people and enforces no agreement.<br><br>
        A year is built one day at a time. From the maker of <a href="https://habithuddle.com" style="color:#e9aa55">Habit Huddle</a>: start the daily habit that wins this bet.
    </p>
</div>
</body>
</html>
