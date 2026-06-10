<script setup>
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    defaultDeliveryDate: String,
    maxVideoSizeMb: Number,
    priceCents: Number,
});

const form = ref({
    mode: 'friend',
    notify_recipient: false,
    recipient_email: '',
    recipient_name: '',
    goal_title: '',
    goal_description: '',
    delivery_date: props.defaultDeliveryDate,
    written_terms: '',
    sender_email: '',
    sender_name: '',
    terms_accepted: false,
    website: '',
});

const steps = [
    { key: 'promise', label: 'The promise' },
    { key: 'video', label: 'The video' },
    { key: 'delivery', label: 'The delivery' },
    { key: 'seal', label: 'The seal' },
];
const step = ref(1);
const maxStepReached = ref(1);
const stepError = ref('');

const videoFile = ref(null);
const videoPreview = ref('');
const videoInput = ref(null);
const cameraPreview = ref(null);
const isRecording = ref(false);
const recordingSeconds = ref(0);
const submitting = ref(false);
const submitStage = ref('');
const errors = ref({});
const generalError = ref('');
const resumeCheckout = ref(null);
let mediaRecorder = null;
let mediaStream = null;
let timer = null;
let chunks = [];

const price = computed(() => {
    const dollars = (props.priceCents ?? 500) / 100;
    return Number.isInteger(dollars) ? `$${dollars}` : `$${dollars.toFixed(2)}`;
});

const isSelf = computed(() => form.value.mode === 'self');

const longDate = (iso) => new Intl.DateTimeFormat('en-US', { dateStyle: 'long', timeZone: 'UTC' })
    .format(new Date(`${iso}T12:00:00Z`));

const todayLabel = computed(() => new Intl.DateTimeFormat('en-US', { dateStyle: 'long' }).format(new Date()));
const formattedDeliveryDate = computed(() => (form.value.delivery_date ? longDate(form.value.delivery_date) : ''));
const heroArrival = computed(() => longDate(props.defaultDeliveryDate));

const daysAway = computed(() => {
    if (!form.value.delivery_date) return null;
    const target = new Date(`${form.value.delivery_date}T12:00:00Z`);
    const now = new Date();
    const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 12));
    return Math.round((target - today) / 86400000);
});

const recordingTime = computed(() => {
    const minutes = Math.floor(recordingSeconds.value / 60).toString().padStart(2, '0');
    const seconds = (recordingSeconds.value % 60).toString().padStart(2, '0');
    return `${minutes}:${seconds}`;
});

const recapFor = computed(() => {
    if (isSelf.value) return `Future you (${form.value.sender_email})`;
    return form.value.recipient_name
        ? `${form.value.recipient_name} (${form.value.recipient_email})`
        : form.value.recipient_email;
});

const recapMeanwhile = computed(() => {
    if (isSelf.value) return 'Nothing. No previews, no reminders. It just arrives.';
    if (form.value.notify_recipient) return 'They get put on notice by email today. The video stays sealed.';
    return 'Total silence. They have no idea until the day it lands.';
});

const sealNote = computed(() => {
    if (isSelf.value) return `No previews, no reminders. It just arrives on ${formattedDeliveryDate.value}.`;
    if (form.value.notify_recipient) return `They are notified today. The video stays sealed until ${formattedDeliveryDate.value}.`;
    return `Nothing is sent today. A year passes in silence. The video lands ${formattedDeliveryDate.value}.`;
});

const hero = computed(() => (isSelf.value
    ? {
        eyebrow: `Put ${price.value} on your word`,
        em: 'Future you gets it in one year.',
        copy: `You swear this time is different. Say it to the camera, seal it for ${price.value}, and exactly one year from today it lands back in your inbox with one question:`,
        endLabel: 'you face it',
        how1: 'Sixty seconds of your promise, on camera.',
        how3: 'It lands back in your inbox. Did you do it?',
    }
    : {
        eyebrow: `Put ${price.value} on their word`,
        em: 'Your friend gets it in one year.',
        copy: `They swear this is their year. The gym, the business, the book. Get it on camera, seal it for ${price.value}, and exactly one year from today it lands in their inbox with one question:`,
        endLabel: 'they face it',
        how1: 'Sixty seconds of their big talk, on camera.',
        how3: 'It lands in their inbox. Did they do it?',
    }));

const stepForField = {
    mode: 1,
    goal_title: 1,
    goal_description: 1,
    video: 2,
    video_storage_key: 2,
    video_upload_token: 2,
    notify_recipient: 3,
    recipient_email: 3,
    recipient_name: 3,
    sender_email: 3,
    sender_name: 3,
    delivery_date: 3,
    written_terms: 3,
    terms_accepted: 4,
};

function chooseMode(mode) {
    form.value.mode = mode;
    stepError.value = '';
    errors.value.recipient_email = null;
}

function goTo(target) {
    if (target > maxStepReached.value) return;
    stepError.value = '';
    step.value = target;
    nextTick(() => document.querySelector('#start')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function advance() {
    stepError.value = '';

    if (step.value === 2 && !videoFile.value) {
        stepError.value = 'Record or upload the video first. It is the whole point.';
        return;
    }
    if (step.value === 3) {
        if (!isSelf.value && !form.value.recipient_email.trim()) { stepError.value = 'Add their email so the video knows where to land.'; return; }
        if (!form.value.sender_email.trim()) { stepError.value = isSelf.value ? 'Add your email. That is where it lands in a year.' : 'Add your email for the sealed receipt.'; return; }
        if (!form.value.delivery_date) { stepError.value = 'Pick the day it lands.'; return; }
    }

    step.value++;
    maxStepReached.value = Math.max(maxStepReached.value, step.value);
    nextTick(() => document.querySelector('#start')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function jumpToFirstError(errorBag) {
    const fields = Object.keys(errorBag);
    if (!fields.length) return;
    const target = Math.min(...fields.map((field) => stepForField[field] ?? 4));
    step.value = target;
    nextTick(() => document.querySelector('#start')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

function clearVideo() {
    if (videoPreview.value) URL.revokeObjectURL(videoPreview.value);
    videoPreview.value = '';
    videoFile.value = null;
    if (videoInput.value) videoInput.value.value = '';
}

function setVideo(file) {
    errors.value.video = null;
    stepError.value = '';
    generalError.value = '';
    if (!file) return;
    if (file.size > props.maxVideoSizeMb * 1024 * 1024) {
        errors.value.video = [`Keep the video under ${props.maxVideoSizeMb} MB.`];
        return;
    }
    clearVideo();
    videoFile.value = file;
    videoPreview.value = URL.createObjectURL(file);
}

function handleUpload(event) {
    setVideo(event.target.files?.[0]);
}

async function startRecording() {
    errors.value.video = null;
    stepError.value = '';
    generalError.value = '';
    if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
        errors.value.video = ['Recording is not supported in this browser. Upload a video instead.'];
        return;
    }

    try {
        mediaStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
        const preferredType = ['video/webm;codecs=vp9,opus', 'video/webm', 'video/mp4']
            .find((type) => MediaRecorder.isTypeSupported(type));
        mediaRecorder = new MediaRecorder(mediaStream, preferredType ? { mimeType: preferredType } : undefined);
        chunks = [];
        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size) chunks.push(event.data);
        };
        mediaRecorder.onstop = () => {
            const type = mediaRecorder.mimeType || 'video/webm';
            const extension = type.includes('mp4') ? 'mp4' : 'webm';
            setVideo(new File(chunks, `one-year-later.${extension}`, { type }));
            stopStream();
        };
        mediaRecorder.start(1000);
        isRecording.value = true;
        recordingSeconds.value = 0;
        timer = window.setInterval(() => recordingSeconds.value++, 1000);
        await nextTick();
        cameraPreview.value.srcObject = mediaStream;
        await cameraPreview.value.play();
    } catch {
        stopStream();
        errors.value.video = ['Camera access was not available. Upload a video instead.'];
    }
}

function stopRecording() {
    if (mediaRecorder?.state === 'recording') mediaRecorder.stop();
    isRecording.value = false;
    window.clearInterval(timer);
}

function stopStream() {
    mediaStream?.getTracks().forEach((track) => track.stop());
    mediaStream = null;
    if (cameraPreview.value) cameraPreview.value.srcObject = null;
    window.clearInterval(timer);
    isRecording.value = false;
}

function firstError(field) {
    const value = errors.value[field];
    return Array.isArray(value) ? value[0] : value;
}

async function submit() {
    errors.value = {};
    generalError.value = '';
    stepError.value = '';
    if (!form.value.terms_accepted) {
        stepError.value = 'Tick the box. It is the only fine print we have.';
        return;
    }

    submitting.value = true;
    try {
        submitStage.value = 'Uploading video...';
        const uploadResponse = await fetch('/api/uploads', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                filename: videoFile.value.name,
                content_type: videoFile.value.type,
                size: videoFile.value.size,
            }),
        });
        const upload = await uploadResponse.json();
        if (!uploadResponse.ok) {
            if (uploadResponse.status === 422) { errors.value = upload.errors || {}; jumpToFirstError(errors.value); }
            else generalError.value = upload.message || 'The video upload could not start.';
            return;
        }

        const r2Response = await fetch(upload.upload_url, {
            method: 'PUT',
            headers: upload.upload_headers,
            body: videoFile.value,
        });
        if (!r2Response.ok) throw new Error('The video could not be uploaded.');

        submitStage.value = 'Sealing it...';
        const response = await fetch('/api/challenges', {
            method: 'POST',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ...form.value,
                video_storage_key: upload.storage_key,
                video_upload_token: upload.upload_token,
            }),
        });
        const data = await response.json();
        if (!response.ok) {
            if (response.status === 422) { errors.value = data.errors || {}; jumpToFirstError(errors.value); }
            else generalError.value = data.message || 'The message could not be saved.';
            return;
        }

        submitStage.value = 'Heading to checkout...';
        sessionStorage.setItem('oyl-pending', JSON.stringify({
            checkout_url: data.checkout_url,
            goal_title: data.challenge.goal_title,
            saved_at: Date.now(),
        }));
        window.location.href = data.checkout_url;
    } catch {
        generalError.value = 'The video or message could not be saved. Please try again.';
    } finally {
        submitting.value = false;
        submitStage.value = '';
    }
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    if (params.get('checkout') !== 'cancelled') return;
    try {
        const pending = JSON.parse(sessionStorage.getItem('oyl-pending'));
        // Stripe Checkout sessions stay open for 24 hours.
        if (pending?.checkout_url && Date.now() - pending.saved_at < 23 * 60 * 60 * 1000) {
            resumeCheckout.value = pending;
        }
    } catch {
        resumeCheckout.value = null;
    }
});

onBeforeUnmount(() => {
    stopStream();
    if (videoPreview.value) URL.revokeObjectURL(videoPreview.value);
});
</script>

<template>
    <Head title="Record a video today. It lands in one year." />
    <main class="site-shell">
        <header class="topbar">
            <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
            <span class="topbar-tag">{{ price }} &middot; one video &middot; one year</span>
        </header>

        <div v-if="resumeCheckout" class="resume-banner">
            <p><strong v-if="resumeCheckout.goal_title">&ldquo;{{ resumeCheckout.goal_title }}&rdquo;</strong><strong v-else>Your video</strong> is recorded and waiting. The {{ price }} seal didn't go through.</p>
            <a class="button button-primary" :href="resumeCheckout.checkout_url">Finish sealing it <b>&rarr;</b></a>
        </div>

        <section class="hero">
            <div class="hero-toggle" role="group" aria-label="Who is this for?">
                <button type="button" :class="{ active: !isSelf }" @click="chooseMode('friend')">For a friend</button>
                <button type="button" :class="{ active: isSelf }" @click="chooseMode('self')">For future me</button>
                <span class="hero-toggle-thumb" :class="{ right: isSelf }" aria-hidden="true"></span>
            </div>
            <p class="eyebrow">{{ hero.eyebrow }}</p>
            <h1>Record a video today.<br><em>{{ hero.em }}</em></h1>
            <p class="hero-copy">{{ hero.copy }} <em>did {{ isSelf ? 'you' : 'they' }} do it?</em></p>
            <div class="year-line" aria-hidden="true">
                <span class="year-line-end"><b>{{ todayLabel }}</b><i>you press send</i></span>
                <span class="year-line-track"><span class="year-line-seal">sealed</span></span>
                <span class="year-line-end"><b>{{ heroArrival }}</b><i>{{ hero.endLabel }}</i></span>
            </div>
            <ol class="how-strip">
                <li><b>1</b><strong>Record</strong><span>{{ hero.how1 }}</span></li>
                <li><b>2</b><strong>Seal it for {{ price }}</strong><span>No edits, no previews, no take-backs.</span></li>
                <li><b>3</b><strong>One year later</strong><span>{{ hero.how3 }}</span></li>
            </ol>
            <a class="text-link" href="#start">Start yours <span>&darr;</span></a>
        </section>

        <section id="start" class="wizard">
            <ol class="step-rail">
                <li v-for="(item, index) in steps" :key="item.key">
                    <button
                        type="button"
                        class="step-pip"
                        :class="{ current: step === index + 1, done: maxStepReached > index + 1 && step !== index + 1, locked: maxStepReached < index + 1 }"
                        :disabled="maxStepReached < index + 1"
                        @click="goTo(index + 1)"
                    >
                        <b>{{ maxStepReached > index + 1 && step !== index + 1 ? '\u2713' : String(index + 1).padStart(2, '0') }}</b>
                        <span>{{ item.label }}</span>
                    </button>
                </li>
            </ol>

            <Transition name="step" mode="out-in">
                <!-- Step 1: the promise -->
                <div v-if="step === 1" key="promise" class="step-panel">
                    <p class="eyebrow">Step 1 of 4 &middot; The promise</p>
                    <h2 v-if="isSelf">What are you<br>promising?</h2>
                    <h2 v-else>What did they say<br>they'd do?</h2>
                    <div class="hero-toggle mode-mini" role="group" aria-label="Who is this for?">
                        <button type="button" :class="{ active: !isSelf }" @click="chooseMode('friend')">For a friend</button>
                        <button type="button" :class="{ active: isSelf }" @click="chooseMode('self')">For future me</button>
                        <span class="hero-toggle-thumb" :class="{ right: isSelf }" aria-hidden="true"></span>
                    </div>

                    <div class="fields-grid step-fields">
                        <label class="field field-wide">
                            <span>The promise, in one line <i>optional</i></span>
                            <input v-model="form.goal_title" type="text" maxlength="160" :placeholder="isSelf ? 'I will have launched the app and quit my job' : 'Maya says she\u2019ll be jacked by next June'">
                            <small>Skip everything here if you want. The video does the talking.</small>
                            <small v-if="firstError('goal_title')" class="field-error">{{ firstError('goal_title') }}</small>
                        </label>
                        <label class="field field-wide">
                            <span>What does &ldquo;done&rdquo; look like? <i>optional</i></span>
                            <textarea v-model="form.goal_description" rows="3" maxlength="3000" placeholder="If you want it airtight: be specific enough that a year from now the answer is a clean yes or no."></textarea>
                            <small v-if="firstError('goal_description')" class="field-error">{{ firstError('goal_description') }}</small>
                        </label>
                    </div>

                    <div class="step-nav">
                        <span></span>
                        <button type="button" class="button button-primary step-continue" @click="advance">Lock in the promise <b>&rarr;</b></button>
                    </div>
                    <p v-if="stepError" class="field-error step-error">{{ stepError }}</p>
                </div>

                <!-- Step 2: the video -->
                <div v-else-if="step === 2" key="video" class="step-panel">
                    <p class="eyebrow">Step 2 of 4 &middot; The video</p>
                    <h2>Say it<br>on camera.</h2>
                    <p class="step-copy">{{ isSelf ? 'Talk straight to the person watching this in one year. Tell them what will be true by then.' : 'Repeat what they said, word for word if you can. Add today\u2019s date. This is what they\u2019ll watch one year from now.' }}</p>

                    <div class="video-card" :class="{ 'has-video': videoPreview || isRecording }">
                        <video v-if="isRecording" ref="cameraPreview" muted playsinline class="video-preview"></video>
                        <video v-else-if="videoPreview" :src="videoPreview" controls playsinline class="video-preview"></video>
                        <div v-else class="video-empty">
                            <span class="record-orb"><span></span></span>
                            <strong>This is the part that gets watched in a year.</strong>
                            <small>MP4, MOV, M4V, or WebM &middot; up to {{ maxVideoSizeMb }} MB</small>
                        </div>
                        <div class="video-actions">
                            <button v-if="!isRecording" type="button" class="button button-primary" @click="startRecording">
                                <span class="button-dot"></span> Record video
                            </button>
                            <button v-else type="button" class="button button-primary" @click="stopRecording">
                                <span class="stop-square"></span> Stop {{ recordingTime }}
                            </button>
                            <label v-if="!isRecording" class="button button-quiet">
                                Upload video
                                <input ref="videoInput" type="file" accept="video/mp4,video/quicktime,video/webm,video/x-m4v" hidden @change="handleUpload">
                            </label>
                            <button v-if="videoPreview && !isRecording" type="button" class="clear-button" @click="clearVideo">Remove</button>
                        </div>
                    </div>
                    <p v-if="firstError('video')" class="field-error">{{ firstError('video') }}</p>

                    <div class="step-nav">
                        <button type="button" class="back-button" @click="goTo(1)">&larr; Back</button>
                        <button type="button" class="button button-primary step-continue" @click="advance">Use this video <b>&rarr;</b></button>
                    </div>
                    <p v-if="stepError" class="field-error step-error">{{ stepError }}</p>
                </div>

                <!-- Step 3: the delivery -->
                <div v-else-if="step === 3" key="delivery" class="step-panel">
                    <p class="eyebrow">Step 3 of 4 &middot; The delivery</p>
                    <h2 v-if="isSelf">Where does<br>it land?</h2>
                    <h2 v-else>Do they find out today,<br>or in a year?</h2>

                    <div v-if="!isSelf" class="mode-grid notify-grid">
                        <button type="button" class="choice-card choice-card-small" :class="{ selected: !form.notify_recipient }" @click="form.notify_recipient = false">
                            <span class="choice-kicker">Recommended</span>
                            <strong>Total silence</strong>
                            <p>They hear nothing today. A year passes. Then the video just arrives. The long game.</p>
                        </button>
                        <button type="button" class="choice-card choice-card-small" :class="{ selected: form.notify_recipient }" @click="form.notify_recipient = true">
                            <span class="choice-kicker">Or</span>
                            <strong>Put them on notice</strong>
                            <p>They get an email today: the promise, the date, and the fact that a sealed video is waiting.</p>
                        </button>
                    </div>

                    <div class="fields-grid step-fields">
                        <template v-if="!isSelf">
                            <label class="field">
                                <span>Their email</span>
                                <input v-model="form.recipient_email" type="email" placeholder="friend@example.com">
                                <small v-if="firstError('recipient_email')" class="field-error">{{ firstError('recipient_email') }}</small>
                            </label>
                            <label class="field">
                                <span>Their name <i>optional</i></span>
                                <input v-model="form.recipient_name" type="text" maxlength="100" placeholder="Maya">
                                <small v-if="firstError('recipient_name')" class="field-error">{{ firstError('recipient_name') }}</small>
                            </label>
                        </template>
                        <label class="field">
                            <span>Your email</span>
                            <input v-model="form.sender_email" type="email" placeholder="you@example.com" required>
                            <small v-if="firstError('sender_email')" class="field-error">{{ firstError('sender_email') }}</small>
                        </label>
                        <label class="field">
                            <span>Your name <i>optional</i></span>
                            <input v-model="form.sender_name" type="text" maxlength="100" placeholder="Alex">
                            <small v-if="firstError('sender_name')" class="field-error">{{ firstError('sender_name') }}</small>
                        </label>
                        <label class="field">
                            <span>The video lands on</span>
                            <input v-model="form.delivery_date" type="date" required>
                            <small v-if="firstError('delivery_date')" class="field-error">{{ firstError('delivery_date') }}</small>
                        </label>
                        <div class="date-callout">
                            <span class="sun-mark">&#9788;</span>
                            <p><strong>{{ formattedDeliveryDate }}</strong><br><template v-if="daysAway">{{ daysAway }} days from now, it surfaces.</template></p>
                        </div>
                        <label class="field field-wide">
                            <span>The incentive <i>optional</i></span>
                            <textarea v-model="form.written_terms" rows="3" maxlength="3000" :placeholder="isSelf ? 'If I pull this off, I\u2019m buying myself the good guitar.' : 'If Maya pulls this off, I owe her a steak dinner.'"></textarea>
                            <small>A beer, a steak dinner, bragging rights, $100. Recorded as text and delivered with the video. Enforced by honor; we never hold the money.</small>
                            <small v-if="firstError('written_terms')" class="field-error">{{ firstError('written_terms') }}</small>
                        </label>
                    </div>

                    <div class="step-nav">
                        <button type="button" class="back-button" @click="goTo(2)">&larr; Back</button>
                        <button type="button" class="button button-primary step-continue" @click="advance">Review the seal <b>&rarr;</b></button>
                    </div>
                    <p v-if="stepError" class="field-error step-error">{{ stepError }}</p>
                </div>

                <!-- Step 4: the seal -->
                <div v-else key="seal" class="step-panel step-panel-seal">
                    <p class="eyebrow">Step 4 of 4 &middot; The seal</p>
                    <h2>Five dollars says<br>you mean it.</h2>
                    <p class="step-copy">Every Said You Would is paid. No free messages, no drafts. The {{ price }} isn't for the video. It's the line between <em>&ldquo;yeah, yeah&rdquo;</em> and <em>on the record.</em></p>

                    <dl class="recap-card">
                        <div><dt>The promise</dt><dd>{{ form.goal_title.trim() || 'It\u2019s all in the video.' }}</dd></div>
                        <div><dt>Lands</dt><dd>{{ formattedDeliveryDate }}<template v-if="daysAway"> &middot; {{ daysAway }} days from now</template></dd></div>
                        <div><dt>For</dt><dd>{{ recapFor }}</dd></div>
                        <div v-if="form.written_terms.trim()"><dt>The incentive</dt><dd>{{ form.written_terms }}</dd></div>
                        <div><dt>Between now and then</dt><dd>{{ recapMeanwhile }}</dd></div>
                    </dl>

                    <label class="terms-check">
                        <input v-model="form.terms_accepted" type="checkbox" required>
                        <span class="custom-check">&#10003;</span>
                        <span>I understand Said You Would holds no money between people and enforces no agreement. The {{ price }} seals and delivers this message; it is not refundable once sealed.</span>
                    </label>
                    <p v-if="firstError('terms_accepted')" class="field-error centered">{{ firstError('terms_accepted') }}</p>
                    <input v-model="form.website" class="honeypot" type="text" tabindex="-1" autocomplete="off">
                    <p v-if="generalError" class="submit-error">{{ generalError }}</p>

                    <div class="step-nav step-nav-seal">
                        <button type="button" class="back-button" @click="goTo(3)">&larr; Back</button>
                        <button class="button commit-button" type="button" :disabled="submitting" @click="submit">
                            <span>{{ submitting ? submitStage : `Seal it for ${price}` }}</span>
                            <b>&rarr;</b>
                        </button>
                    </div>
                    <p v-if="stepError" class="field-error step-error centered">{{ stepError }}</p>
                    <small class="commit-note">Secure checkout by Stripe. {{ sealNote }}</small>
                </div>
            </Transition>
        </section>

        <footer class="footer">
            <div>
                <a class="wordmark" href="/">Said You Would<span class="wordmark-dot">.</span></a>
                <p>You said you would. One year later, we check.</p>
            </div>
            <a class="huddle-promo" href="https://habithuddle.com" target="_blank" rel="noopener">
                <span class="huddle-kicker">From the maker of</span>
                <strong>Habit Huddle</strong>
                <span>A year is built one day at a time. Build the habit with friends watching.</span>
            </a>
            <p class="footer-legal">&copy; {{ new Date().getFullYear() }} &middot; We hold no money between people. We enforce nothing. We just remember.</p>
        </footer>
    </main>
</template>
