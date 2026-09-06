<!doctype html>
<html lang="en">
<body style="margin:0;background:#171e1a;color:#f7f1e8;font-family:Arial,Helvetica,sans-serif;line-height:1.6">
<div style="max-width:620px;margin:0 auto;padding:56px 24px">
    <p style="font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#aab1ac;margin:0">Said You Would</p>
    <h1 style="font-family:Georgia,serif;font-size:46px;line-height:1.08;margin:18px 0;font-weight:400">The year is up.</h1>
    @if($challenge->mode === 'self')
        <p style="font-size:19px;margin:0 0 8px">One year ago today, you recorded a video for this exact moment. You sealed it, you paid for it, and you have not seen it since.</p>
    @elseif($challenge->notify_recipient)
        <p style="font-size:19px;margin:0 0 8px">One year ago, {{ $challenge->publicSenderName() ?: 'someone who knows you' }} recorded a video about a promise you made. Today it unlocks.</p>
    @else
        <p style="font-size:19px;margin:0 0 8px">One year ago, {{ $challenge->publicSenderName() ?: 'someone who knows you' }} recorded a video about something you said, and told you nothing. They paid to have it delivered today.</p>
    @endif
    @if($challenge->goal_title)
        <h2 style="font-family:Georgia,serif;font-size:28px;color:#f4b35d;font-weight:400;margin:26px 0">&ldquo;{{ $challenge->goal_title }}&rdquo;</h2>
    @endif
    <p style="margin:28px 0"><a href="{{ $deliveryUrl }}" style="display:inline-block;background:#d9553b;color:#ffffff;text-decoration:none;padding:15px 26px;border-radius:999px;font-weight:bold">Watch the video</a></p>
    <p style="font-size:13px;color:#aab1ac">After watching, one honest answer: did you do it?</p>
    <p style="font-size:12px;color:#7e8881;margin-top:40px;border-top:1px solid #2c3630;padding-top:18px">
        A year is built one day at a time. From the maker of <a href="https://habithuddle.com" style="color:#e9aa55">Habit Huddle</a>: build the next one with friends watching.
    </p>
</div>
</body>
</html>
