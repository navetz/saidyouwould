<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
    state: String,
    challenge: Object,
});

const date = computed(() => (props.challenge?.delivery_date
    ? new Intl.DateTimeFormat('en-US', { dateStyle: 'long', timeZone: 'UTC' })
        .format(new Date(`${props.challenge.delivery_date}T12:00:00Z`))
    : null));

const isSelf = computed(() => props.challenge?.mode === 'self');
const recipientLabel = computed(() => (isSelf.value
    ? 'Future you'
    : props.challenge?.recipient_name || props.challenge?.recipient_email));

const amount = computed(() => (props.challenge?.amount_cents
    ? `$${(props.challenge.amount_cents / 100).toFixed(2).replace(/\.00$/, '')}`
    : '$5'));

const silenceLine = computed(() => {
    if (isSelf.value) return 'No previews. No reminders. The next time you see it is the day it arrives.';
    if (props.challenge?.notify_recipient) return 'They were put on notice today. The video itself stays sealed.';
    return 'They have no idea. Don\u2019t bring it up. Let the year do the work.';
});

onMounted(() => {
    if (props.state === 'sealed') sessionStorage.removeItem('oyl-pending');
});
</script>

<template>
    <Head title="Sealed" />
    <main class="dark-page">
        <header class="topbar topbar-dark"><a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a></header>

        <section v-if="state === 'sealed'" class="result-card">
            <span class="result-mark seal-mark">1Y</span>
            <p class="eyebrow">Paid &middot; locked &middot; scheduled</p>
            <h1>Sealed.</h1>
            <p class="result-copy"><template v-if="challenge.goal_title">&ldquo;<em>{{ challenge.goal_title }}</em>&rdquo; is</template><template v-else>Your video is</template> on the record. It surfaces for <strong>{{ recipientLabel }}</strong> on {{ date }}.</p>
            <dl class="seal-summary">
                <div><dt>The stake</dt><dd>{{ amount }}, paid</dd></div>
                <div><dt>Arrives</dt><dd>{{ date }}</dd></div>
                <div><dt>Between now and then</dt><dd>{{ silenceLine }}</dd></div>
            </dl>
            <a class="huddle-card" href="https://habithuddle.com" target="_blank" rel="noopener">
                <span class="huddle-kicker">One more thing</span>
                <strong>A year is built one day at a time.</strong>
                <p>{{ isSelf ? 'The version of you opening that video is made of 365 ordinary days. Stack them with friends watching on Habit Huddle, from the same maker. Free.' : 'Want to be on the right side of your own next bet? Build the daily habit with friends watching on Habit Huddle, from the same maker. Free.' }}</p>
                <span class="huddle-link">habithuddle.com &rarr;</span>
            </a>
            <a class="button button-light" href="/">Seal another one</a>
        </section>

        <section v-else-if="state === 'unpaid'" class="result-card">
            <span class="result-mark">!</span>
            <p class="eyebrow">Not sealed yet</p>
            <h1>The payment<br>didn't land.</h1>
            <p class="result-copy">Your video is uploaded and your message is saved, but it isn't sealed until the payment clears. Head back and finish checkout. Unsealed messages are deleted after 48 hours.</p>
            <a class="button button-light" href="/?checkout=cancelled">Finish sealing it</a>
        </section>

        <section v-else class="result-card">
            <span class="result-mark">1Y</span>
            <p class="eyebrow">No message in this session</p>
            <h1>Nothing to<br>show here.</h1>
            <p class="result-copy">If you already sealed a message, it's safely scheduled. Check your email for the receipt. Otherwise, start one now.</p>
            <a class="button button-light" href="/">Start a Said You Would</a>
        </section>
    </main>
</template>
