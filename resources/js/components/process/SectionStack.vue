<template>
    <div class="w-full">
        <div class="flex justify-between items-center mb-2 " v-if="sections.length > 1">
            <span class="text-md font-semibold theme-title">Sections</span>
            <div class="mt-3  flex items-center justify-end relative z-10">
                <button
                    class="px-2 py-1 rounded-md border theme-surface hover:bg-black/5 disabled:opacity-50 theme-body"
                    :disabled="sections.length <= 1" @click="prev">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <div class="flex items-center gap-1 mx-4">
                    <span v-for="(s, i) in sections" :key="s.id"
                        :class="['inline-block w-2 h-2 rounded-full', i === activeIndex ? 'bg-green-600 w-4' : ' dark:bg-slate-400']"></span>
                </div>
                <button
                    class="px-2 py-1 rounded-md border theme-surface hover:bg-black/5 disabled:opacity-50 theme-body"
                    :disabled="sections.length <= 1" @click="next">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="relative mb-10" :style="{ height: containerHeight }">
            <template v-if="sections.length > 0">
                <div v-for="(s, idx) in sections" :key="s.id"
                    class="absolute inset-0 transition-transform duration-300 ease-out" :style="layerStyle(idx)"
                    @click="selectByIndex(idx)">
                    <div class="h-full">
                        <div :ref="el => setCardRef(idx, el)"
                            class="group rounded-lg border border-gray-500/20 theme-surface p-3 shadow-sm transition">
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <div class="text-xs text-slate-500 flex items-center gap-2">
                                    <span class="inline-flex items-center gap-1"><i class="ti ti-clock text-xs"></i>
                                        {{ fmt(s.debut) }} - {{ fmt(s.fin) }}</span>
                                    <span class="h-3 w-px bg-slate-300/60"></span>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <span
                                        class="inline-flex theme-muted theme-body items-center gap-1  rounded-lg p-1 text-xs"><i
                                            class="ti ti-world text-xs"></i>
                                        {{ s.langue || '-' }}</span>
                                    <span
                                        class="inline-flex theme-muted theme-body  items-center gap-1 rounded-lg p-1 text-xs"><i
                                            class="ti ti-file-text text-xs"></i>
                                        {{ s.resumes_count ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="font-medium truncate theme-body " :title="s.titre || 'Sans titre'">
                                {{ s.titre || 'Sans titre' }}
                            </div>
                            <div class="flex items-center gap-2 mt-2 w-full">
                                <button
                                    class="size-8 px-2 rounded-md border border-gray-400/40 theme-body flex items-center justify-center hover:bg-black/5"
                                    title="Lire" @click.stop="emitPlay(s)">
                                    <i class="ti ti-player-play"></i>
                                    <span class="sr-only">Lire</span>
                                </button>
                                <button
                                    class="size-8 px-2 rounded-md border border-red-400/40 flex items-center justify-center hover:bg-red-50 text-red-600 disabled:opacity-50"
                                    :disabled="delId === s.id || sections.length <= 1" title="Supprimer"
                                    @click.stop="emitRemove(s)">
                                    <span v-if="delId === s.id"
                                        class="inline-block w-4 h-4 border-2 border-red-600/40 border-t-red-600 rounded-full animate-spin"></span>
                                    <i v-else class="ti ti-trash"></i>
                                    <span class="sr-only">Supprimer</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <div v-else class="px-3 py-6 text-center theme-muted-text text-sm">Aucune section</div>
        </div>


    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch, ref, nextTick } from 'vue'

const props = defineProps({
    sections: { type: Array, default: () => [] },
    selectedId: { type: [Number, String, null], default: null },
    deletingId: { type: [Number, String, null], default: null },
    // Optional fixed height for the stack. If empty, height is auto-measured from active card.
    height: { type: String, default: '' },
})
const emit = defineEmits(['update:selectedId', 'play', 'remove'])

const measuredHeight = ref(0)
const containerHeight = computed(() => {
    if (props.height) return props.height
    return measuredHeight.value > 0 ? measuredHeight.value + 'px' : 'auto'
})
const activeIndex = computed({
    get() {
        const idx = props.sections.findIndex(s => s.id === props.selectedId)
        return idx >= 0 ? idx : 0
    },
    set(val) {
        const safe = Math.max(0, Math.min(val, Math.max(0, props.sections.length - 1)))
        const id = props.sections[safe]?.id ?? null
        emit('update:selectedId', id)
    }
})

function layerStyle(idx) {
    const n = Math.max(0, props.sections.length)
    // No items
    if (n === 0) {
        return { opacity: 0, pointerEvents: 'none' }
    }
    // Relative position with wrap-around (0 = active, 1 = next, ...)
    const rel = ((idx - activeIndex.value) % n + n) % n
    // Only show up to 2 next items (active + 2 = 3 visible max)
    const maxNext = 2
    if (rel === 0) {
        return { opacity: 1, transform: 'translateY(0) scale(1)', zIndex: 50 }
    }
    if (rel > 0 && rel <= maxNext) {
        const translate = 14 * rel
        const scale = 1 - (rel * 0.04)
        const opacity = Math.max(0, 1 - (rel * 0.18))
        const z = 50 - rel
        return { opacity, transform: `translateY(${translate}px) scale(${scale})`, filter: `blur(${rel * 0.2}px)`, zIndex: z }
    }
    return { opacity: 0, pointerEvents: 'none', transform: 'translateY(80px) scale(0.9)', zIndex: 1 }
}

function prev() {
    const n = props.sections.length
    if (n === 0) return
    const nextIdx = (activeIndex.value - 1 + n) % n
    activeIndex.value = nextIdx
}
function next() {
    const n = props.sections.length
    if (n === 0) return
    const nextIdx = (activeIndex.value + 1) % n
    activeIndex.value = nextIdx
}
function selectByIndex(i) { activeIndex.value = i }

function emitPlay(s) { emit('play', s) }
function emitRemove(s) { emit('remove', s) }

function onKey(e) {
    if (e.key === 'ArrowLeft') { prev() }
    if (e.key === 'ArrowRight') { next() }
}
onMounted(() => { window.addEventListener('keydown', onKey) })
onUnmounted(() => { window.removeEventListener('keydown', onKey) })

// Keep selectedId within bounds when sections change
watch(() => props.sections.map(s => s.id), (ids) => {
    if (ids.length === 0) { emit('update:selectedId', null); return }
    if (!ids.includes(props.selectedId)) {
        emit('update:selectedId', ids[0])
    }
})

const delId = computed(() => props.deletingId)

// Auto height measurement
const cardEls = ref([])
function setCardRef(i, el) { cardEls.value[i] = el }
async function recalcHeight() {
    await nextTick()
    const el = cardEls.value[activeIndex.value] || cardEls.value[0]
    measuredHeight.value = el ? el.offsetHeight : 0
}
onMounted(() => {
    recalcHeight()
    window.addEventListener('resize', recalcHeight)
})
onUnmounted(() => { window.removeEventListener('resize', recalcHeight) })
watch(activeIndex, () => { recalcHeight() })
watch(() => props.sections.map(s => s.id).join(','), () => { recalcHeight() })

function secToHms(sec) {
    const n = Number(sec) || 0
    const h = String(Math.floor(n / 3600)).padStart(2, '0')
    const m = String(Math.floor((n % 3600) / 60)).padStart(2, '0')
    const s = String(n % 60).padStart(2, '0')
    return `${h}:${m}:${s}`
}
function fmt(sec) { return secToHms(sec) }
</script>

<style scoped></style>
