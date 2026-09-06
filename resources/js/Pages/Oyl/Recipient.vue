<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    mode: String,
    recipientToken: String,
    videoUrl: String,
    challenge: Object,
});

const acknowledged = ref(props.challenge.acknowledged);
const currentResponse = ref(props.challenge.response);
const selectedResponse = ref(props.challenge.response?.response || '');
const responseNote = ref(props.challenge.response?.response_note || '');
const working = ref(false);
const error = ref('');
const saved = ref(false);
const formattedDate = computed(() => new Intl.DateTimeFormat('en-US', { dateStyle: 'long', timeZone: 'UTC' })
    .format(new Date(`${props.challenge.delivery_date}T12:00:00Z`)));
const sender = computed(() => {
    if (props.challenge.anonymous) return 'Someone';
    return props.challenge.sender_name || 'Someone who believes in you';
});
const isSelf = computed(() => props.challenge.mode === 'self');
const wasSilent = computed(() => !isSelf.value && !props.challenge.notify_recipient);

async function acknowledge() {
    working.value = true;
    error.value = '';
    try {
        const response = await fetch(`/api/recipient/${encodeURIComponent(props.recipientToken)}/acknowledge`, { method: 'POST', headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();
        acknowledged.value = true;
    } catch {
        error.value = 'We could not save that acknowledgement. Please try again.';
    } finally {
        working.value = false;
    }
}

async function saveResponse() {
    if (!selectedResponse.value) return;
    working.value = true;
    error.value = '';
    saved.value = false;
    try {
        const response = await fetch(`/api/recipient/${encodeURIComponent(props.recipientToken)}/response`, {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ response: selectedResponse.value, response_note: responseNote.value }),
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message);
        currentResponse.value = data.response;
        saved.value = true;
    } catch {
        error.value = 'We could not save your answer. Please try again.';
    } finally {
        working.value = false;
    }
}
</script>

<template>
    <Head :title="mode === 'delivery' ? 'The year is up' : 'A sealed video is waiting'" />
    <main class="recipient-page" :class="{ 'delivery-page': mode === 'delivery' }">
        <header class="topbar" :class="{ 'topbar-dark': mode === 'delivery' }"><a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a></header>

        <section v-if="mode === 'acknowledge'" class="recipient-wrap">
            <div class="recipient-intro">
                <p class="eyebrow">A message is waiting for the future</p>
                <h1>{{ sender }}<br><em>put $5 on your word.</em></h1>
                <p>They recorded a private video for you and paid to have it sealed. You cannot watch it yet. That's the point.<template v-if="challenge.anonymous"> And no, we won't say who. That's their call.</template></p>
            </div>
            <article class="promise-card">
                <span class="card-label">The promise</span>
                <h2>{{ challenge.goal_title || 'The video says it all.' }}</h2>
                <p v-if="challenge.goal_description">{{ challenge.goal_description }}</p>
                <dl>
                    <div><dt>The video unlocks</dt><dd>{{ formattedDate }}</dd></div>
                    <div v-if="challenge.written_terms"><dt>The incentive</dt><dd>{{ challenge.written_terms }}</dd></div>
                </dl>
                <div class="legal-note">Incentives are recorded as text only. Said You Would holds no money between people and enforces no agreement.</div>
                <button v-if="!acknowledged" class="button commit-button compact" :disabled="working" @click="acknowledge">{{ working ? 'Saving...' : 'I understand. Ask me in a year.' }} <b>&rarr;</b></button>
                <div v-else class="acknowledged-mark"><span>&#10003;</span><p><strong>You acknowledged it.</strong><br>We'll be back on {{ formattedDate }}. The clock is already running.</p></div>
                <p v-if="error" class="field-error centered">{{ error }}</p>
            </article>
        </section>

        <section v-else class="delivery-wrap">
            <div class="delivery-intro">
                <p class="eyebrow">The year is up</p>
                <h1 v-if="isSelf">You left this<br><em>for yourself.</em></h1>
                <h1 v-else>Remember what<br><em>you said mattered?</em></h1>
                <p v-if="isSelf">One year ago you recorded this, sealed it, and walked away. Time to face the camera.</p>
                <p v-else-if="wasSilent">{{ sender }} recorded this one year ago and never said a word. It's been sealed the whole time.</p>
                <p v-else>{{ sender }} recorded this for you one year ago.</p>
            </div>
            <div class="delivered-video"><video :src="videoUrl" controls playsinline preload="metadata"></video></div>
            <article class="response-card">
                <p class="eyebrow">One honest answer</p>
                <h2>Did you do it?</h2>
                <p v-if="challenge.goal_title" class="goal-reminder">{{ challenge.goal_title }}</p>
                <div class="response-options">
                    <button type="button" :class="{ selected: selectedResponse === 'did_it' }" @click="selectedResponse = 'did_it'"><span>&#10003;</span>I did it</button>
                    <button type="button" :class="{ selected: selectedResponse === 'didnt_do_it' }" @click="selectedResponse = 'didnt_do_it'"><span>&#10005;</span>I didn't do it</button>
                </div>
                <label class="field response-note"><span>Anything to add? <i>optional</i></span><textarea v-model="responseNote" rows="4" maxlength="2000" placeholder="What actually happened over the year?"></textarea></label>
                <button class="button commit-button compact" :disabled="working || !selectedResponse" @click="saveResponse">{{ working ? 'Saving...' : currentResponse ? 'Update my answer' : 'Record my answer' }} <b>&rarr;</b></button>
                <p v-if="saved" class="saved-note">Your answer is on the record.</p>
                <p v-if="error" class="field-error centered">{{ error }}</p>
            </article>
            <a v-if="saved || currentResponse" class="huddle-card huddle-card-light" href="https://habithuddle.com" target="_blank" rel="noopener">
                <span class="huddle-kicker">{{ selectedResponse === 'did_it' ? 'Keep the streak alive' : 'Next year starts today' }}</span>
                <strong>A year is built one day at a time.</strong>
                <p>{{ selectedResponse === 'did_it' ? 'Whatever you did daily to pull this off, keep it going with friends watching on Habit Huddle. From the maker of Said You Would, free.' : 'The year didn\u2019t go your way. The next one is 365 daily checkins away. Build the habit with friends watching on Habit Huddle, free.' }}</p>
                <span class="huddle-link">habithuddle.com &rarr;</span>
            </a>
        </section>
    </main>
</template>
