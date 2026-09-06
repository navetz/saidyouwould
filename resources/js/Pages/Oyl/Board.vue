<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import BoardCard from '@/Components/BoardCard.vue';

const props = defineProps({
    upcoming: Array,
    landed: Array,
    priceCents: Number,
});

const price = computed(() => {
    const dollars = (props.priceCents ?? 500) / 100;
    return Number.isInteger(dollars) ? `$${dollars}` : `$${dollars.toFixed(2)}`;
});

const nextUp = computed(() => props.upcoming[0] || null);
</script>

<template>
    <Head title="The board" />
    <main class="site-shell">
        <header class="topbar">
            <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
            <nav class="topnav">
                <Link href="/board" class="topnav-link active">The board</Link>
                <a href="/" class="button button-primary button-small">Seal yours</a>
            </nav>
        </header>

        <section class="board-hero">
            <p class="eyebrow">The board</p>
            <h1>Who said what.<br><em>And the day we check.</em></h1>
            <p class="hero-copy">Every one of these is a real promise, sealed for {{ price }}. The video stays locked until the day it lands. Then it plays right here.</p>
        </section>

        <section class="board-section">
            <div class="board-head">
                <h2>Landing soon</h2>
                <span class="board-count">{{ upcoming.length }}</span>
            </div>
            <div v-if="upcoming.length" class="board-grid">
                <BoardCard v-for="item in upcoming" :key="item.slug" :item="item" />
            </div>
            <div v-else class="board-empty">
                <span class="record-orb"><span></span></span>
                <strong>Nothing sealed yet.</strong>
                <p>The board fills up one promise at a time. The first one on it is yours if you want it.</p>
                <a class="button button-primary" href="/">Record yours <b>&rarr;</b></a>
            </div>
        </section>

        <section class="board-section">
            <div class="board-head">
                <h2>Landed</h2>
                <span class="board-count">{{ landed.length }}</span>
            </div>
            <div v-if="landed.length" class="board-grid">
                <BoardCard v-for="item in landed" :key="item.slug" :item="item" />
            </div>
            <p v-else class="board-quiet">Nothing has landed yet. The first videos unlock <template v-if="nextUp">on {{ new Intl.DateTimeFormat('en-US', { dateStyle: 'long', timeZone: 'UTC' }).format(new Date(`${nextUp.delivery_date}T12:00:00Z`)) }}</template><template v-else>a year after the first seal</template>.</p>
        </section>

        <section class="board-cta">
            <p class="eyebrow">Your turn</p>
            <h2>Say it on camera.<br><em>We check in a year.</em></h2>
            <a class="button button-primary step-continue" href="/">Seal yours for {{ price }} <b>&rarr;</b></a>
        </section>

        <footer class="footer">
            <div>
                <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
                <p>You said you would. One year later, we check.</p>
            </div>
            <a class="huddle-promo" href="https://habithuddle.com/?src=syw-board" target="_blank" rel="noopener">
                <span class="huddle-kicker">From the maker of</span>
                <strong>Habit Huddle</strong>
                <span>A year is built one day at a time. Build the habit with friends watching.</span>
            </a>
            <p class="footer-legal">&copy; {{ new Date().getFullYear() }} &middot; We hold no money between people. We enforce nothing. We just remember.</p>
        </footer>
    </main>
</template>
