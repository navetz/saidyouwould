<!doctype html>
<html lang="en">
<body style="margin:0;background:#f3efe7;color:#1f2924;font-family:Arial,Helvetica,sans-serif;line-height:1.6">
<div style="max-width:620px;margin:0 auto;padding:48px 24px">
    <p style="font-size:12px;letter-spacing:.18em;text-transform:uppercase;color:#6d756f;margin:0">Said You Would</p>
    <h1 style="font-family:Georgia,serif;font-size:38px;line-height:1.12;margin:16px 0;font-weight:400">{{ $challenge->sender_name ?: 'Someone who knows you' }} put five dollars on your word.</h1>
    <p style="font-size:18px;margin:0 0 8px">You said this mattered. There is a sealed video waiting for you, recorded today and locked until the date below.</p>
    <div style="background:#fffdf8;border:1px solid #d9d4ca;border-radius:14px;padding:26px;margin:28px 0">
        <p style="font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:#767d78;margin:0 0 6px">The promise</p>
        <h2 style="font-family:Georgia,serif;margin:0 0 10px;font-size:26px;font-weight:400">{{ $challenge->goal_title ?: 'The video says it all.' }}</h2>
        @if($challenge->goal_description)
            <p style="margin:0 0 16px;color:#3f4a44">{{ $challenge->goal_description }}</p>
        @endif
        <p style="margin:0"><strong>The video unlocks:</strong> {{ $challenge->delivery_date->format('F j, Y') }}</p>
        @if($challenge->written_terms)
            <p style="margin:14px 0 0"><strong>The incentive:</strong><br>{{ $challenge->written_terms }}</p>
        @endif
    </div>
    <p style="margin:28px 0"><a href="{{ $acknowledgementUrl }}" style="display:inline-block;background:#d9553b;color:#ffffff;text-decoration:none;padding:14px 24px;border-radius:999px;font-weight:bold">See the promise &amp; acknowledge</a></p>
    <p style="font-size:13px;color:#6d756f">You cannot watch the video yet. That is the point. One year from now it arrives, and the only question will be: did you do it?</p>
    <p style="font-size:12px;color:#8a918b;margin-top:40px;border-top:1px solid #ddd7cc;padding-top:18px">
        Said You Would holds no money between people and enforces no agreement. Written terms are recorded text only.<br><br>
        A year is built one day at a time. From the maker of <a href="https://habithuddle.com" style="color:#d9553b">Habit Huddle</a>: build the habit with friends watching.
    </p>
</div>
</body>
</html>
