<script setup>
import { ref, nextTick, onMounted, onBeforeUnmount } from 'vue';

// Accessible info tooltip that works on BOTH desktop (hover) and mobile (tap).
// The bubble is position:fixed and clamped to the viewport so it never clips
// inside cards or off the screen edge.
const props = defineProps({
    text: { type: String, required: true },
    label: { type: String, default: 'More information' },
});

const open = ref(false);
const btn = ref(null);
const pop = ref(null);
const popStyle = ref({});

function place() {
    const b = btn.value, p = pop.value;
    if (!b || !p) return;
    const r = b.getBoundingClientRect();
    const vw = window.innerWidth, vh = window.innerHeight, pad = 8;
    p.style.maxWidth = Math.min(280, vw - pad * 2) + 'px';
    const pw = p.offsetWidth, ph = p.offsetHeight;
    let left = r.left + r.width / 2 - pw / 2;
    left = Math.max(pad, Math.min(left, vw - pw - pad));
    let top = r.bottom + 8;
    if (top + ph > vh - pad) top = Math.max(pad, r.top - ph - 8);
    popStyle.value = { left: left + 'px', top: top + 'px' };
}

function show() { open.value = true; nextTick(place); }
function hide() { open.value = false; }
function toggle(e) { e.stopPropagation(); open.value ? hide() : show(); }

function onDocDown(e) { if (btn.value && !btn.value.contains(e.target) && pop.value && !pop.value.contains(e.target)) hide(); }
function onKey(e) { if (e.key === 'Escape') hide(); }
function onScroll() { if (open.value) hide(); }

onMounted(() => {
    document.addEventListener('pointerdown', onDocDown, true);
    document.addEventListener('keydown', onKey);
    window.addEventListener('scroll', onScroll, true);
    window.addEventListener('resize', onScroll);
});
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocDown, true);
    document.removeEventListener('keydown', onKey);
    window.removeEventListener('scroll', onScroll, true);
    window.removeEventListener('resize', onScroll);
});
</script>

<template>
    <span class="bnk-info" @mouseenter="show" @mouseleave="hide">
        <button ref="btn" type="button" class="bnk-info-btn" :aria-label="label" @click="toggle">
            <span class="bnk-info-ms">info</span>
        </button>
        <teleport to="body">
            <transition name="bnk-info-fade">
                <span v-if="open" ref="pop" class="bnk-info-pop" role="tooltip" :style="popStyle">{{ text }}</span>
            </transition>
        </teleport>
    </span>
</template>

<style scoped>
.bnk-info { display: inline-flex; align-items: center; }
.bnk-info-btn { appearance: none; border: 0; cursor: pointer; line-height: 0; display: inline-flex; align-items: center; justify-content: center;
    /* generous tap target without disturbing layout */
    width: 26px; height: 26px; margin: -7px -6px; border-radius: 50%;
    background: var(--golds, rgba(168,133,78,.12)); color: var(--gold, #A8854E); opacity: .9;
    transition: opacity .15s ease, background .15s ease; -webkit-tap-highlight-color: transparent; }
.bnk-info-btn:hover, .bnk-info-btn:focus-visible { opacity: 1; background: var(--gold, #A8854E); color: #fff; outline: none; }
.bnk-info-ms { font-family: 'Material Symbols Rounded'; font-size: 15px; }
</style>

<style>
/* Global (teleported to body, so cannot be scoped) */
.bnk-info-pop {
    position: fixed; z-index: 1000; width: max-content; max-width: 280px;
    background: var(--card, #fff); color: var(--text, #23262B);
    border: 1px solid var(--border, #ECE8E0); border-radius: 11px;
    box-shadow: 0 1px 2px rgba(28,32,40,.06), 0 12px 32px rgba(28,32,40,.16);
    padding: 11px 13px; font: 500 12px/1.55 'Hanken Grotesk', sans-serif;
    letter-spacing: normal; text-transform: none; pointer-events: auto;
}
.bnk-info-fade-enter-active, .bnk-info-fade-leave-active { transition: opacity .14s ease, transform .14s ease; }
.bnk-info-fade-enter-from, .bnk-info-fade-leave-to { opacity: 0; transform: translateY(-3px); }
</style>
