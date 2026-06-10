<script setup>
import { Head } from '@inertiajs/vue3';
defineProps({ message: String });
</script>

<template>
    <Head title="Link unavailable" />
    <main class="dark-page">
        <header class="topbar topbar-dark"><a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a></header>
        <section class="result-card error-card">
            <span class="result-mark">!</span>
            <p class="eyebrow">Private link unavailable</p>
            <h1>This moment<br>can't be opened.</h1>
            <p class="result-copy">{{ message }}</p>
            <p class="subdued">Ask the sender to contact support if you believe the link should still work.</p>
            <a class="button button-light" href="/">Go to Said You Would</a>
        </section>
    </main>
</template>
