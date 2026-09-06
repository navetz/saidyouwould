<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({ item: Object });

const longDate = (iso) => new Intl.DateTimeFormat('en-US', { dateStyle: 'medium', timeZone: 'UTC' })
    .format(new Date(`${iso}T12:00:00Z`));

const daysAway = computed(() => {
    const target = new Date(`${props.item.delivery_date}T12:00:00Z`);
    const now = new Date();
    const today = new Date(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate(), 12));
    return Math.round((target - today) / 86400000);
});

const countdown = computed(() => {
    const d = daysAway.value;
    if (props.item.delivered) return { big: 'Landed', small: longDate(props.item.delivery_date) };
    if (d <= 0) return { big: 'Today', small: 'landing now' };
    if (d === 1) return { big: '1', small: 'day to go' };
    return { big: String(d), small: 'days to go' };
});

const who = computed(() => {
    const name = props.item.sender_name || 'Someone';
    return props.item.mode === 'self' ? `${name}, to future ${props.item.sender_name ? name : 'self'}` : `${name}, to a friend`;
});

const answer = computed(() => {
    const r = props.item.response?.response;
    if (r === 'did_it') return { label: 'Did it', tone: 'yes' };
    if (r === 'didnt_do_it') return { label: 'Didn’t', tone: 'no' };
    return null;
});
</script>

<template>
    <Link :href="item.url" class="board-card" :class="{ landed: item.delivered }">
        <div class="board-card-count">
            <b>{{ countdown.big }}</b>
            <i>{{ countdown.small }}</i>
        </div>
        <p class="board-card-title">{{ item.goal_title || 'It’s all in the video.' }}</p>
        <p class="board-card-meta">{{ who }} &middot; sealed {{ longDate(item.sealed_on) }}<template v-if="!item.delivered"> &middot; lands {{ longDate(item.delivery_date) }}</template></p>
        <p v-if="item.written_terms" class="board-card-terms">{{ item.written_terms }}</p>
        <span v-if="answer" class="board-answer" :class="answer.tone">{{ answer.label }}</span>
        <span v-else-if="item.delivered" class="board-answer watch">Watch it &rarr;</span>
        <span v-else class="board-sealed">Sealed</span>
    </Link>
</template>
