<template>
    <div>
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4">
            <div class="flex justify-between flex-wrap">
                <div class="">
                    <h2 class="text-md font-semibold theme-title ">Transcrire</h2>
                    <p class="theme-muted-text text-sm">Récupérer et corriger la transcription</p>
                </div>
                <button type="button"
                    class="px-3 py-1 rounded-md bg-teal-600 hover:bg-teal-700 text-white disabled:opacity-50 text-sm"
                    :disabled="loading || !video?.id" @click="fetchFromYouTube">
                    {{ loading ? 'Récupération…' : 'Récupérer depuis YouTube' }}
                </button>
            </div>
        </div>

        <div class="space-y-3">


            <div>
                <label class="block text-xs theme-muted-text mb-1">Transcription (une ligne par entrée, commencez la
                    ligne par la seconde)</label>
                <textarea v-model="contenu" rows="14"
                    class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400"
                    placeholder="0 Bonjour…\n6 Et bienvenue…\n12 Aujourd'hui, nous allons…"></textarea>
                <div class="mt-1 text-xs theme-muted-text flex items-center gap-2">
                    <span v-if="langue" class="inline-flex items-center gap-1">Langue: <span class="font-medium">{{
                        langue }}</span></span>
                    <span v-if="savedAt">• Dernier enregistrement: {{ savedAt }}</span>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <select v-model="langue" class="rounded-md border border-slate-300 px-2 py-1 text-sm">
                    <option value="fr">FR</option>
                    <option value="en">EN</option>
                </select>
                <button type="button"
                    class="px-3 py-2 rounded-md bg-[#F9C98B] text-black hover:brightness-95 disabled:opacity-50"
                    :disabled="saving || !video?.id || !contenu.trim()" @click="saveTranscription">
                    {{ saving ? 'Enregistrement…' : 'Enregistrer' }}
                </button>
                <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
                <span v-if="notice" class="text-xs text-emerald-700">{{ notice }}</span>
            </div>

            <div class="mt-2 text-xs theme-muted-text">Video ID: {{ video?.id ?? '—' }}</div>
        </div>
    </div>

</template>

<script setup>
import { ref, watch, onMounted } from 'vue'

const props = defineProps({ video: { type: Object, required: true } })

const contenu = ref('')
const langue = ref('fr')
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const notice = ref('')
const savedAt = ref('')

onMounted(() => {
    // Defaults from video if available

    if (props.video?.langue) {
        const base = String(props.video.langue).split('-')[0]
        if (base) langue.value = base
    }
    // Load latest saved transcription
    if (props.video?.id) {
        loadLatest()
    }
})

watch(() => props.video?.id, () => {
    contenu.value = ''
    error.value = ''
    notice.value = ''
    savedAt.value = ''
})

async function fetchFromYouTube() {
    if (!props.video?.id) return
    loading.value = true
    error.value = ''
    notice.value = ''
    try {
        const { data } = await window.axios.get(`/api/videos/${props.video.id}/transcription/youtube`)
        if (data?.success) {
            contenu.value = data.transcription?.contenu ?? ''
            langue.value = data.transcription?.langue ? String(data.transcription.langue).split('-')[0] : langue.value
            savedAt.value = new Date().toLocaleString()
            notice.value = 'Transcription récupérée.'
        } else {
            error.value = data?.message || 'Échec de la récupération.'
        }
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Erreur réseau.'
    } finally {
        loading.value = false
    }
}

async function saveTranscription() {
    if (!props.video?.id) return
    saving.value = true
    error.value = ''
    notice.value = ''
    try {
        const payload = { langue: langue.value, contenu: contenu.value }
        const { data } = await window.axios.post(`/api/videos/${props.video.id}/transcription`, payload)
        if (data?.success) {
            savedAt.value = new Date().toLocaleString()
            notice.value = 'Transcription enregistrée.'
        } else {
            error.value = data?.message || 'Échec de l\'enregistrement.'
        }
    } catch (e) {
        error.value = e?.response?.data?.message || e?.message || 'Erreur réseau.'
    } finally {
        saving.value = false
    }
}

async function loadLatest() {
    try {
        const { data } = await window.axios.get(`/api/videos/${props.video.id}/transcription`)
        if (data?.success && data.transcription) {
            contenu.value = data.transcription.contenu ?? ''
            if (data.transcription.langue) {
                langue.value = String(data.transcription.langue).split('-')[0]
            }
            savedAt.value = data.transcription.created_at
        }
    } catch (e) {
        // silent
    }
}
</script>
