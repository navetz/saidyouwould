<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    challenge: Object,
    videoUrl: String,
    priceCents: Number,
});

const price = computed(() => {
    const dollars = (props.priceCents ?? 500) / 100;
    return Number.isInteger(dollars) ? `$${dollars}` : `$${dollars.toFixed(2)}`;
});

const longDate = (iso) => new Intl.DateTimeFormat('en-US', { dateStyle: 'long', timeZone: 'UTC' })
    .format(new Date(`${iso}T12:00:00Z`));

const daysAway = computed(() => {
    const target = new Date(`${props.challenge.delivery_date}T12:00:00Z`);
    const now = new Date();
    const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 12));
    return Math.round((target - today) / 86400000);
});

const name = computed(() => props.challenge.sender_name || 'Someone');
const isSelf = computed(() => props.challenge.mode === 'self');
const title = computed(() => props.challenge.goal_title || 'It’s all in the video.');
const who = computed(() => (isSelf.value ? `${name.value}, to future ${props.challenge.sender_name ? name.value : 'self'}` : `${name.value}, to a friend`));
const answer = computed(() => props.challenge.response?.response || null);

const copied = ref(false);
async function share() {
    const url = props.challenge.url;
    const text = `${name.value} said: ${title.value}`;
    if (navigator.share) {
        try { await navigator.share({ title: 'Said You Would', text, url }); return; } catch { /* dismissed */ }
    }
    try {
        await navigator.clipboard.writeText(url);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    } catch { /* clipboard blocked */ }
}
</script>

<template>
    <Head :title="title" />

    <!-- Delivered: dark page, the video plays. -->
    <main v-if="challenge.delivered" class="dark-page">
        <header class="topbar topbar-dark">
            <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
            <nav class="topnav"><Link href="/board" class="topnav-link">The board</Link></nav>
        </header>
        <section class="delivery-wrap">
            <div class="delivery-intro">
                <p class="eyebrow">Sealed {{ longDate(challenge.sealed_on) }} &middot; Landed {{ longDate(challenge.delivery_date) }}</p>
                <h1>{{ title }}</h1>
                <p>{{ who }}. Recorded {{ longDate(challenge.sealed_on) }}, locked for a year.</p>
            </div>
            <div class="delivered-video">
                <video :src="videoUrl" controls playsinline preload="metadata"></video>
            </div>
            <div class="public-answer">
                <template v-if="answer === 'did_it'">
                    <span class="board-answer yes">Did it</span>
                    <p v-if="challenge.response.response_note" class="public-note">&ldquo;{{ challenge.response.response_note }}&rdquo;</p>
                </template>
                <template v-else-if="answer === 'didnt_do_it'">
                    <span class="board-answer no">Didn&rsquo;t</span>
                    <p v-if="challenge.response.response_note" class="public-note">&ldquo;{{ challenge.response.response_note }}&rdquo;</p>
                </template>
                <p v-else class="subdued">No answer yet.</p>
            </div>
            <div class="public-actions public-actions-dark">
                <button type="button" class="button button-light" @click="share">{{ copied ? 'Link copied' : 'Share this' }}</button>
                <a class="button button-primary" href="/">Seal yours for {{ price }} <b>&rarr;</b></a>
            </div>
        </section>
    </main>

    <!-- Pending: the countdown card. -->
    <main v-else class="site-shell">
        <header class="topbar">
            <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
            <nav class="topnav">
                <Link href="/board" class="topnav-link">The board</Link>
                <a href="/" class="button button-primary button-small">Seal yours</a>
            </nav>
        </header>
        <section class="public-wrap">
            <div class="public-card">
                <span class="public-seal">Sealed</span>
                <p class="eyebrow">On the record since {{ longDate(challenge.sealed_on) }}</p>
                <h1>{{ title }}</h1>
                <p class="public-who">{{ who }}</p>
                <div class="public-count">
                    <b>{{ daysAway <= 0 ? 'Today' : daysAway }}</b>
                    <i v-if="daysAway > 0">{{ daysAway === 1 ? 'day' : 'days' }} until it lands &middot; {{ longDate(challenge.delivery_date) }}</i>
                    <i v-else>it lands today</i>
                </div>
                <dl v-if="challenge.written_terms" class="public-terms">
                    <dt>The incentive</dt>
                    <dd>{{ challenge.written_terms }}</dd>
                </dl>
                <p class="public-locked">The video is locked. It plays on this page the day it lands. Did {{ isSelf ? 'they' : 'their friend' }} do it? Come back and see.</p>
                <div class="public-actions">
                    <button type="button" class="button button-quiet" @click="share">{{ copied ? 'Link copied' : 'Share this' }}</button>
                    <a class="button button-primary" href="/">Seal yours for {{ price }} <b>&rarr;</b></a>
                </div>
            </div>
            <Link href="/board" class="text-link">See the whole board <span>&rarr;</span></Link>
        </section>
        <footer class="footer">
            <div>
                <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
                <p>You said you would. One year later, we check.</p>
            </div>
            <a class="huddle-promo" href="https://habithuddle.com/?src=syw-public" target="_blank" rel="noopener">
                <span class="huddle-kicker">From the maker of</span>
                <strong>Habit Huddle</strong>
                <span>A year is built one day at a time. Build the habit with friends watching.</span>
            </a>
            <p class="footer-legal">&copy; {{ new Date().getFullYear() }} &middot; We hold no money between people. We enforce nothing. We just remember.</p>
        </footer>
    </main>
</template>
