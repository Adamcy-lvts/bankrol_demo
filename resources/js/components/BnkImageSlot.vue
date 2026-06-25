<script setup>
import { ref, onMounted } from 'vue';

// User-fillable image slot. Click or drag-drop an image; it is downscaled and
// stored (base64) in localStorage under `id`, so it survives reloads and shows
// on the shared demo for that browser. `src` is an optional default photo.
const props = defineProps({
    id: { type: String, required: true },
    src: { type: String, default: '' },
    placeholder: { type: String, default: 'Drop an image' },
});

const key = () => `bnk_img_${props.id}`;
const url = ref('');
const failed = ref(false);
const over = ref(false);
const input = ref(null);

onMounted(() => {
    try { const saved = localStorage.getItem(key()); if (saved) { url.value = saved; return; } } catch (e) { /* ignore */ }
    if (props.src) url.value = props.src;
});

function pick() { if (input.value) input.value.click(); }
function onRootClick() { if (!url.value || failed.value) pick(); }
function onFile(e) { const f = e.target.files && e.target.files[0]; if (f) ingest(f); e.target.value = ''; }
function onDrop(e) { over.value = false; const f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]; if (f) ingest(f); }
function onErr() { failed.value = true; }
function clearImg() { url.value = props.src || ''; failed.value = !props.src ? false : false; try { localStorage.removeItem(key()); } catch (e) { /* ignore */ } if (!props.src) { url.value = ''; failed.value = false; } }

async function ingest(file) {
    if (!/^image\//.test(file.type)) return;
    const dataUrl = await downscale(file, 1500);
    url.value = dataUrl; failed.value = false;
    try { localStorage.setItem(key(), dataUrl); } catch (e) { /* quota — keep in-memory */ }
}

function downscale(file, max) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = () => {
            const img = new Image();
            img.onload = () => {
                const scale = Math.min(1, max / Math.max(img.width, img.height));
                const w = Math.max(1, Math.round(img.width * scale)), h = Math.max(1, Math.round(img.height * scale));
                const c = document.createElement('canvas'); c.width = w; c.height = h;
                c.getContext('2d').drawImage(img, 0, 0, w, h);
                try { resolve(c.toDataURL('image/webp', 0.82)); } catch (e) { resolve(c.toDataURL('image/jpeg', 0.85)); }
            };
            img.onerror = () => resolve(reader.result);
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    });
}
</script>

<template>
    <div class="bnk-slot" :class="{ 'is-over': over }" @click="onRootClick" @dragover.prevent="over = true" @dragenter.prevent="over = true" @dragleave.prevent="over = false" @drop.prevent="onDrop">
        <img v-if="url && !failed" :src="url" class="bnk-slot-img" alt="" @error="onErr" />
        <div v-if="!url || failed" class="bnk-slot-empty">
            <span class="bnk-ms">add_photo_alternate</span>
            <span class="bnk-cap">{{ placeholder }}</span>
            <span class="bnk-sub">Click or drop an image</span>
        </div>
        <div v-if="url && !failed" class="bnk-slot-ctl">
            <button type="button" @click.stop="pick">Replace</button>
            <button type="button" @click.stop="clearImg">Remove</button>
        </div>
        <input ref="input" type="file" accept="image/*" hidden @change="onFile" />
    </div>
</template>

<style scoped>
.bnk-slot { position: relative; overflow: hidden; background: var(--card2, rgba(0,0,0,.05)); cursor: pointer; }
.bnk-slot.is-over { outline: 2px solid var(--gold, #A8854E); outline-offset: -2px; }
.bnk-slot-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.bnk-slot-empty { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 5px; text-align: center; padding: 12px; color: var(--t3, #9A958B); border: 1.5px dashed var(--border, rgba(0,0,0,.18)); border-radius: inherit; }
.bnk-slot-empty .bnk-ms { font-family: 'Material Symbols Rounded'; font-size: 26px; opacity: .8; }
.bnk-slot-empty .bnk-cap { font-size: 12px; font-weight: 600; max-width: 90%; }
.bnk-slot-empty .bnk-sub { font-size: 10.5px; opacity: .75; }
.bnk-slot-ctl { position: absolute; right: 8px; bottom: 8px; display: flex; gap: 6px; opacity: 0; transition: opacity .15s ease; }
.bnk-slot:hover .bnk-slot-ctl { opacity: 1; }
.bnk-slot-ctl button { appearance: none; border: 0; border-radius: 6px; padding: 5px 10px; cursor: pointer; background: rgba(0,0,0,.62); color: #fff; font: 600 11px 'Hanken Grotesk', sans-serif; backdrop-filter: blur(6px); }
.bnk-slot-ctl button:hover { background: rgba(0,0,0,.8); }
</style>
