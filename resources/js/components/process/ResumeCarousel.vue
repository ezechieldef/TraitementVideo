<template>
    <div class="w-full">
        <div class="flex justify-between items-center mb-2" v-if="items.length > 1">
            <span class="text-md font-semibold theme-title">Résumés</span>
            <div class="flex items-center gap-2">
                <button class="px-2 py-1 rounded-md border hover:bg-black/5 disabled:opacity-50"
                    :disabled="items.length <= 1" @click="prev">
                    <i class="ti ti-chevron-left" />
                </button>
                <div class="flex items-center gap-1 mx-2">
                    <span v-for="(r, i) in items" :key="r.id"
                        :class="['inline-block h-2 rounded-full transition-all', i === activeIndex ? 'bg-green-600 w-4' : 'bg-slate-400 w-2']"></span>
                </div>
                <button class="px-2 py-1 rounded-md border hover:bg-black/5 disabled:opacity-50"
                    :disabled="items.length <= 1" @click="next">
                    <i class="ti ti-chevron-right" />
                </button>
            </div>
        </div>
        <div class="relative" :style="{ height: containerHeight }">
            <template v-if="items.length">
                <div v-for="(r, idx) in items" :key="r.id"
                    class="absolute inset-0 transition-transform duration-300 ease-out" :style="layerStyle(idx)"
                    @click="selectByIndex(idx)">
                    <div class="h-full">
                        <div :ref="el => setCardRef(idx, el)"
                            class="rounded-lg border border-gray-500/30 theme-surface p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="font-semibold truncate">{{ r.titre || 'Sans titre' }}</span>
                                    <span v-if="r.isApproved"
                                        class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 rounded px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                        <i class="ti ti-check" /> APPROUVÉ
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1 bg-slate-100 dark:bg-slate-700 rounded px-2 py-0.5 text-[10px] text-slate-600 dark:text-slate-300">
                                        <i class="ti ti-world text-xs" /> {{ r.langue || '-' }}
                                    </span>
                                </div>
                                <div class="text-xs theme-muted-text shrink-0">#{{ r.id }}</div>
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-xs theme-muted-text block mb-1">Titre</label>
                                    <input v-model="draft.titre" type="text"
                                        class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm bg-white/70 dark:bg-slate-800" />
                                </div>
                                <div>
                                    <label class="text-xs theme-muted-text block mb-1">Contenu (Markdown)</label>
                                    <!-- One textarea per carte; on récupère la ref par index -->
                                    <textarea v-model="draft.contenu" :ref="el => setMdTextarea(idx, el)" rows="14"
                                        class="hidden"></textarea>
                                    <div v-if="mdInitError" class="text-xs text-red-600">{{ mdInitError }}</div>
                                </div>
                                <div class="flex items-center justify-end gap-2 pt-2">
                                    <button class="px-3 py-1 rounded-md border" :disabled="saving"
                                        @click.stop="resetDraft">Réinitialiser</button>
                                    <button
                                        class="px-3 py-1 rounded-md bg-green-600 hover:bg-green-700 text-white disabled:opacity-50 flex items-center gap-1"
                                        :disabled="saving || !dirty" @click.stop="saveCurrent">
                                        <span v-if="saving"
                                            class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                        <span v-else class="inline-flex items-center gap-1"><i
                                                class="ti ti-device-floppy" /> Sauvegarder</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div v-else class="px-3 py-6 text-center theme-muted-text text-sm">Aucun résumé disponible</div>
        </div>
    </div>
</template>
<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import EasyMDE from 'easymde'
import 'easymde/dist/easymde.min.css'

const props = defineProps({
    resumes: { type: Array, default: () => [] },
    selectedId: { type: [Number, String, null], default: null },
    height: { type: String, default: '' },
    saving: { type: Boolean, default: false },
})
const emit = defineEmits(['update:selectedId', 'save'])
// Alias used by template (was referencing 'items')
const items = computed(() => props.resumes)

const cardRefs = ref([])
function setCardRef(i, el) { cardRefs.value[i] = el }
const measuredHeight = ref(0)
const containerHeight = computed(() => props.height ? props.height : (measuredHeight.value ? measuredHeight.value + 'px' : 'auto'))

const activeIndex = computed({
    get() {
        const idx = props.resumes.findIndex(r => r.id === props.selectedId)
        return idx >= 0 ? idx : 0
    },
    set(v) {
        const safe = Math.max(0, Math.min(v, props.resumes.length - 1))
        emit('update:selectedId', props.resumes[safe]?.id ?? null)
    }
})

function layerStyle(idx) {
    const n = props.resumes.length
    if (!n) return { opacity: 0 }
    const rel = ((idx - activeIndex.value) % n + n) % n
    const maxNext = 2
    if (rel === 0) return { opacity: 1, transform: 'translateY(0) scale(1)', zIndex: 50 }
    if (rel > 0 && rel <= maxNext) {
        const translate = 14 * rel
        const scale = 1 - rel * 0.04
        const opacity = 1 - rel * 0.18
        return { opacity, transform: `translateY(${translate}px) scale(${scale})`, filter: `blur(${0.2 * rel}px)`, zIndex: 50 - rel }
    }
    return { opacity: 0, pointerEvents: 'none', transform: 'translateY(80px) scale(0.9)' }
}
function prev() { if (props.resumes.length) activeIndex.value = (activeIndex.value - 1 + props.resumes.length) % props.resumes.length }
function next() { if (props.resumes.length) activeIndex.value = (activeIndex.value + 1) % props.resumes.length }
function selectByIndex(i) { activeIndex.value = i }

// Draft state
const draft = ref({ titre: '', contenu: '' })
const original = ref({ titre: '', contenu: '' })
const dirty = computed(() => draft.value.titre !== original.value.titre || draft.value.contenu !== original.value.contenu)

const mdInstance = ref(null)
// Tableau de textareas (un par carte visible dans le v-for)
const mdTextareas = ref([])
const mdInitError = ref('')

function setMdTextarea(i, el) { mdTextareas.value[i] = el }

function initMde() {
    const el = mdTextareas.value[activeIndex.value]
    if (!el) return
    try {
        if (mdInstance.value) { mdInstance.value.toTextArea(); mdInstance.value = null }
        mdInstance.value = new EasyMDE({
            element: el,
            autoDownloadFontAwesome: false,
            spellChecker: false,
            status: false,
            toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list', '|', 'link', 'code', 'table', 'horizontal-rule', '|', 'preview', 'side-by-side', 'fullscreen'],
            initialValue: draft.value.contenu || ''
        })
        mdInstance.value.codemirror.on('change', () => {
            draft.value.contenu = mdInstance.value.value()
        })
    } catch (e) {
        mdInitError.value = e?.message || 'Erreur initialisation éditeur'
    }
}

function bindDraft() {
    const r = props.resumes[activeIndex.value]
    if (!r) { draft.value = { titre: '', contenu: '' }; original.value = { titre: '', contenu: '' }; return }
    draft.value = { titre: r.titre || '', contenu: r.contenu || '' }
    original.value = { ...draft.value }
    nextTick(() => initMde())
}

watch(activeIndex, () => { bindDraft(); })
watch(() => props.resumes.map(r => r.id).join(','), () => { if (!props.resumes.find(r => r.id === props.selectedId)) emit('update:selectedId', props.resumes[0]?.id ?? null); bindDraft(); recalcHeight() })

function resetDraft() { bindDraft() }
function saveCurrent() {
    const r = props.resumes[activeIndex.value]
    if (!r) return
    emit('save', { id: r.id, titre: draft.value.titre.trim(), contenu: draft.value.contenu })
}

async function recalcHeight() {
    await nextTick()
    const el = cardRefs.value[activeIndex.value] || cardRefs.value[0]
    measuredHeight.value = el ? el.offsetHeight : 0
}

watch([activeIndex, () => draft.value.titre, () => draft.value.contenu], () => { recalcHeight() })

function onKey(e) { if (e.key === 'ArrowLeft') prev(); if (e.key === 'ArrowRight') next() }
onMounted(() => { window.addEventListener('keydown', onKey); bindDraft(); recalcHeight() })
onUnmounted(() => { window.removeEventListener('keydown', onKey); if (mdInstance.value) mdInstance.value.toTextArea() })
</script>
<style scoped></style>
