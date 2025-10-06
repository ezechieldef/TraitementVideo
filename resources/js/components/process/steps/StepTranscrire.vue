<template>
    <div>
        <!-- Global blocking overlay when busy -->
        <div v-if="isBusy" class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3 rounded-lg theme-surface p-6 shadow">
                <div class="h-8 w-8 rounded-full border-2 border-slate-300 border-t-teal-600 animate-spin"></div>
                <div class="text-sm theme-muted-text">Chargement…</div>
            </div>
        </div>
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4">
            <div class="flex justify-between flex-wrap">
                <div class="">
                    <h2 class="text-md font-semibold theme-title ">Transcrire</h2>
                    <p class="theme-muted-text text-sm">Récupérer et corriger la transcription</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a :href="video?.url || '#'" target="_blank" rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-md border-gray-500/20 border text-red-500  px-3 py-1 shadow-sm"
                        :class="{ 'pointer-events-none opacity-60': isBusy || !video?.url }"
                        :aria-disabled="isBusy || !video?.url" :tabindex="(isBusy || !video?.url) ? -1 : 0">
                        <i class="ti ti-brand-youtube text-base"></i>
                        <span>Ouvrir sur YouTube</span>
                    </a>

                    <select v-model="langue" class="rounded-md border border-slate-300 px-2 py-1 text-sm"
                        :disabled="isBusy">
                        <option v-for="(lang, index) in availableLangues" :key="index" :value="lang"
                            class="capitalize ">{{ lang }}</option>
                        <option :value="'__custom'">Autre…</option>
                    </select>
                    <input v-if="langue === '__custom'" v-model="customLang" placeholder="ex: fr, en"
                        class="rounded-md border border-slate-300 px-2 py-1 text-sm" style="width: 90px;"
                        :disabled="isBusy" />

                    <button type="button"
                        class="px-3 py-1 rounded-md bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50 text-sm"
                        :disabled="isBusy || !video?.id" @click="refreshFromYouTube">
                        {{ loading ? 'Récupération…' : 'Récupérer depuis YouTube' }}
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-3">


            <div>
                <label class="block text-xs theme-muted-text mb-1">Transcription (une ligne par entrée, commencez la
                    ligne par la seconde)</label>
                <textarea v-model="contenu" rows="14"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400"
                    placeholder="0 Bonjour…\n6 Et bienvenue…\n12 Aujourd'hui, nous allons…"
                    :disabled="isBusy"></textarea>
                <div class="mt-1 text-xs theme-muted-text flex items-center gap-2">
                    <span v-if="langue" class="inline-flex items-center gap-1">Langue: <span class="font-medium">{{
                        langue }}</span></span>
                    <span v-if="savedAt">• Dernier enregistrement: {{ savedAt }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button"
                    class="px-3 py-2 rounded-md bg-[#F9C98B] text-black hover:brightness-95 disabled:opacity-50"
                    :disabled="isBusy || !video?.id || !contenu.trim()" @click="saveTranscription">
                    {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
                <span v-if="notice" class="text-xs text-emerald-700">{{ notice }}</span>
            </div>

        </div>
    </div>

</template>

<script setup>
import { ref, computed, watch, onMounted, inject } from 'vue'

const props = defineProps({ video: { type: Object, required: true } })

const route = inject('route')

const contenu = ref('')
const langue = ref('')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const notice = ref('')
const savedAt = ref('')
const availableLangues = ref([])
const customLang = ref('')
// Global busy blocker
const busyCount = ref(0)
const isBusy = computed(() => busyCount.value > 0)
function withBusy(fn) {
    busyCount.value++
    return Promise.resolve().then(fn).finally(() => {
        busyCount.value = Math.max(0, busyCount.value - 1)
    })
}
const authToken = ref('')
const requestHeaders = ref({})

onMounted(async () => {
    authToken.value = JSON.parse(localStorage.getItem('auth_token') || 'null') || ''
    requestHeaders.value = {
        Authorization: authToken.value ? `Bearer ${authToken.value}` : '',
        'content-type': 'application/json',
        accept: 'application/json',
    }
    if (!props.video?.id) return
    await loadLanguages()
    if (props.video?.langue) {
        const base = String(props.video.langue).split('-')[0]
        if (base) langue.value = base
    }
    if (!langue.value && availableLangues.value.length > 0) {
        langue.value = availableLangues.value[0]
    }
    await loadByLanguage()
})

watch(() => props.video, async (v) => {
    contenu.value = ''
    error.value = ''
    notice.value = ''
    savedAt.value = ''
    availableLangues.value = []
    langue.value = ''
    if (v?.id) {
        await loadLanguages()
        if (v?.langue) {
            const base = String(v.langue).split('-')[0]
            if (base) langue.value = base
        }
        if (!langue.value && availableLangues.value.length > 0) {
            langue.value = availableLangues.value[0]
        }
        await loadByLanguage()
    }
}, { immediate: false })

watch(langue, async (val, old) => {
    if (!props.video?.id || !val || val === old) return
    if (val === '__custom') return
    await loadByLanguage()
})

async function refreshFromYouTube() {
    if (!props.video?.id) return
    await withBusy(async () => {
        loading.value = true
        error.value = ''
        notice.value = ''
        try {
            const url = route('api.videos.transcription.fetchAll', { video: props.video.id })
            const { data } = await window.axios.get(url, { headers: requestHeaders.value })
            if (data?.success) {
                notice.value = 'Transcriptions mises à jour depuis YouTube.'
                await loadLanguages()
                await loadByLanguage()
            } else {
                error.value = data?.message || 'Échec de la récupération.'
            }
        } catch (e) {
            error.value = e?.response?.data?.message || e?.message || 'Erreur réseau.'
        } finally {
            loading.value = false
        }
    })
}

function isValidTranscription(text) {
    const lines = String(text || '').split(/\r?\n/)
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i].trim()
        if (!line) continue
        if (!/^\[[0-9]{2}:[0-9]{2}:[0-9]{2}\]\s+.+/.test(line)) {
            return { ok: false, line: i + 1 }
        }
    }
    return { ok: true }
}

async function saveTranscription() {
    if (!props.video?.id) return
    await withBusy(async () => {
        saving.value = true
        error.value = ''
        notice.value = ''
        try {
            const selectedLang = langue.value === '__custom' ? customLang.value.trim() : langue.value
            if (!selectedLang) {
                error.value = 'Veuillez choisir une langue.'
                return
            }
            const check = isValidTranscription(contenu.value)
            if (!check.ok) {
                error.value = `Format invalide à la ligne ${check.line}. Chaque ligne doit commencer par [HH:MM:SS].`
                return
            }
            const payload = { langue: selectedLang, contenu: contenu.value }
            const url = route ? route('api.videos.transcription.save', { video: props.video.id }) : `/api/videos/${props.video.id}/transcription`
            const { data } = await window.axios.post(url, payload, { headers: requestHeaders.value })
            if (data?.success) {
                savedAt.value = new Date().toLocaleString()
                notice.value = 'Transcription enregistrée.'
                if (!availableLangues.value.includes(selectedLang)) {
                    availableLangues.value.push(selectedLang)
                }
                langue.value = selectedLang
                customLang.value = ''
            } else {
                error.value = data?.message || 'Échec de l\'enregistrement.'
            }
        } catch (e) {
            error.value = e?.response?.data?.message || e?.message || 'Erreur réseau.'
        } finally {
            saving.value = false
        }
    })
}

async function loadLanguages() {
    await withBusy(async () => {
        try {
            const url = route ? route('api.videos.transcription.languages', { video: props.video.id }) : `/api/videos/${props.video.id}/transcription/languages`
            const { data } = await window.axios.get(url, { headers: requestHeaders.value })
            if (data?.success) {
                availableLangues.value = data.languages || []
            }
        } catch (e) {
            availableLangues.value = []
        }
    })
}

async function loadByLanguage() {
    if (!langue.value) return
    await withBusy(async () => {
        try {
            const url = route ? route('api.videos.transcription.showByLanguage', { video: props.video.id, langue: langue.value }) : `/api/videos/${props.video.id}/transcription/${langue.value}`
            const { data } = await window.axios.get(url, { headers: requestHeaders.value })
            if (data?.success && data.transcription) {
                contenu.value = data.transcription.contenu ?? ''
                savedAt.value = data.transcription.created_at
            } else {
                contenu.value = ''
                savedAt.value = ''
            }
        } catch (e) {
            contenu.value = ''
            savedAt.value = ''
        }
    })
}
</script>
