<template>
    <div>
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4 backdrop-blur-md">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-md font-semibold theme-title">Sectionner</h2>
                    <p class="theme-muted-text text-sm">Découper la vidéo</p>
                </div>
                <div class="flex items-center gap-3">
                    <button class="px-3 py-1 rounded-md bg-slate-600 text-white hover:bg-slate-700 disabled:opacity-50"
                        :disabled="busy || !video?.id" @click="autoSplit">Découper automatiquement</button>
                    <button class="px-3 py-1 rounded-md bg-red-600 text-white hover:bg-red-700 disabled:opacity-50"
                        :disabled="busy || !video?.id" @click="openModal(null)">Ajouter une section</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Left: YouTube iframe -->
            <div class="rounded-lg border border-gray-500/20 overflow-hidden theme-body">
                <div class="aspect-video bg-black">
                    <iframe v-if="video?.youtube_id" ref="playerEl" class="w-full h-full" :src="playerSrc"
                        title="YouTube video"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                    <div v-else class="h-64 flex items-center justify-center theme-muted-text">Aucune vidéo</div>
                </div>
            </div>

            <!-- Right: transcript live view -->
            <div class="rounded-lg border border-gray-500/20 p-3 overflow-auto max-h-[60vh]">
                <h3 class="text-sm font-semibold theme-title mb-2">Transcription</h3>
                <pre class="text-xs whitespace-pre-wrap leading-5">{{ transcript }}</pre>
            </div>
        </div>

        <!-- Sections table -->
        <div class="mt-5 rounded-lg border border-gray-500/20 overflow-hidden">
            <div class="px-3 py-2 theme-surface font-semibold">Sections</div>
            <div class="divide-y divide-gray-200/50">
                <div v-for="s in sections" :key="s.id" class="flex items-center gap-3 px-3 py-2">
                    <button
                        class="size-8 rounded-full border border-gray-400/40 flex items-center justify-center text-red-500"
                        title="Lire la section" @click="playFrom(s.debut)">
                        <i class="ti ti-player-play"></i>
                    </button>
                    <div class="flex-1 min-w-0">
                        <div class="font-medium truncate">{{ s.titre || 'Sans titre' }}</div>
                    </div>
                    <div class="w-28 text-sm tabular-nums">{{ fmt(s.debut) }}</div>
                    <div class="w-28 text-sm tabular-nums">{{ fmt(s.fin) }}</div>
                    <div class="w-16 text-center text-sm">{{ s.resumes_count ?? 0 }}</div>
                    <div class="flex items-center gap-2">
                        <button class="px-2 py-1 rounded border border-gray-400/40"
                            @click="openModal(s)">Modifier</button>
                        <button class="text-red-600" @click="removeSection(s)">Supprimer</button>
                        <button class="px-3 py-1 rounded-md bg-red-600 text-white">Générer résumé</button>
                    </div>
                </div>
                <div v-if="sections.length === 0" class="px-3 py-6 text-center theme-muted-text text-sm">Aucune section
                    pour le moment.</div>
            </div>
        </div>

        <!-- Modal add/edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 app-modal">
            <div class="w-[95vw] max-w-md rounded-lg theme-surface p-4 shadow-lg">
                <div class="text-md font-semibold mb-1">{{ editing ? 'Modifier la section' : 'Nouvelle section' }}</div>
                <p v-if="formError" class="text-xs text-red-600 mb-2">{{ formError }}</p>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs theme-muted-text block mb-1">Titre</label>
                        <input v-model="form.titre" type="text"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Début</label>
                            <input v-model="form.debutStr" type="time" step="1"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Fin</label>
                            <input v-model="form.finStr" type="time" step="1"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="extract" v-model="form.extract" type="checkbox" />
                        <label for="extract" class="text-xs">Sauvegarder + extraire la transcription</label>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="px-3 py-1 rounded border" @click="closeModal">Annuler</button>
                    <button class="px-3 py-1 rounded bg-slate-600 text-white disabled:opacity-50" :disabled="submitting"
                        @click="submitSection">{{ submitting ? 'Enregistrement…' : 'Sauvegarder' }}</button>
                </div>
            </div>
        </div>

        <!-- Modal découpage automatique -->
        <div v-if="showAutoModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 app-modal">
            <div class="w-[95vw] max-w-2xl rounded-lg theme-surface p-4 shadow-lg">
                <div class="text-md font-semibold mb-2">Découpage automatique</div>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Choisir un prompte (Section)</label>
                            <select v-model="selectedPrompteId"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="p in promptesSection" :key="p.id" :value="p.id">{{ p.titre || ('#' +
                                    p.id)
                                    }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Modèle LLM configuré</label>
                            <select v-model="selectedLlmId"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="l in llms" :key="l.llm_id" :value="l.llm_id">{{ l.name }} ({{
                                    l.model_version }})</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs theme-muted-text block mb-1">Personnaliser le prompte</label>
                        <textarea v-model="autoPrompt" rows="10"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <p v-if="autoError" class="mt-1 text-xs text-red-600">{{ autoError }}</p>
                    </div>
                    <p class="text-xs theme-muted-text">Note: Une couche d'instructions système sera ajoutée
                        automatiquement pour demander une réponse JSON conforme.</p>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="px-3 py-1 rounded border" @click="closeAuto">Annuler</button>
                    <button class="px-3 py-1 rounded bg-slate-600 text-white disabled:opacity-50"
                        :disabled="autoSubmitting" @click="submitAuto">{{ autoSubmitting ? 'Envoi…' : 'Valider'
                        }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

const props = defineProps({ video: { type: Object, default: null } })
const route = inject('route')

const sections = ref([])
const transcript = ref('')
const busy = ref(false)
const showModal = ref(false)
const editing = ref(false)
const editingId = ref(null)
const form = ref({ titre: '', debutStr: '00:00:00', finStr: '00:00:10', extract: true })
const formError = ref('')
const submitting = ref(false)

// Auto modal state
const showAutoModal = ref(false)
const promptesSection = ref([])
const llms = ref([])
const selectedPrompteId = ref(null)
const selectedLlmId = ref(null)
const autoPrompt = ref('')
const autoError = ref('')
const autoSubmitting = ref(false)

const playerEl = ref(null)
const playerSrc = computed(() => {
    if (!props.video?.youtube_id) return ''
    const start = 0
    return `https://www.youtube.com/embed/${props.video.youtube_id}?enablejsapi=1&start=${start}`
})

function hmsToSec(hms) {
    const [h = '0', m = '0', s = '0'] = String(hms || '0:0:0').split(':')
    return Number(h) * 3600 + Number(m) * 60 + Number(s)
}
function secToHms(sec) {
    const h = String(Math.floor(sec / 3600)).padStart(2, '0')
    const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0')
    const s = String(sec % 60).padStart(2, '0')
    return `${h}:${m}:${s}`
}
function fmt(sec) { return secToHms(Number(sec || 0)) }

const authToken = ref('')
const headers = ref({})
onMounted(async () => {
    authToken.value = JSON.parse(localStorage.getItem('auth_token') || 'null') || ''
    headers.value = { Authorization: authToken.value ? `Bearer ${authToken.value}` : '', accept: 'application/json', 'content-type': 'application/json' }
    await refreshAll()
    // Load promptes and llms from localStorage (provided by the controller via blade)
    try { promptesSection.value = JSON.parse(localStorage.getItem('promptes_section') || '[]') || [] } catch { }
    try { llms.value = JSON.parse(localStorage.getItem('llms_configured') || '[]') || [] } catch { }
})
watch(() => props.video?.id, async () => { await refreshAll() })

async function refreshAll() {
    if (!props.video?.id) return
    await Promise.all([loadSections(), loadTranscript()])
}

async function loadSections() {
    if (!props.video?.id) return
    try {
        const url = route('api.videos.sections.index', { video: props.video.id })
        const { data } = await window.axios.get(url, { headers: headers.value })
        sections.value = data?.sections || []
    } catch { sections.value = [] }
}
async function loadTranscript() {
    try {
        const url = route('api.videos.transcription.latest', { video: props.video.id })
        const { data } = await window.axios.get(url, { headers: headers.value })
        transcript.value = data?.transcription?.contenu || ''
    } catch { transcript.value = '' }
}

function openModal(s) {
    editing.value = !!s
    editingId.value = s?.id || null
    form.value = {
        titre: s?.titre || '',
        debutStr: fmt(s?.debut || 0),
        finStr: fmt(s?.fin || 0),
        extract: true,
    }
    formError.value = ''
    showModal.value = true
}
function closeModal() { showModal.value = false }

async function submitSection() {
    if (!props.video?.id) return
    const payload = {
        titre: form.value.titre || null,
        debut: hmsToSec(form.value.debutStr),
        fin: hmsToSec(form.value.finStr),
        extract: !!form.value.extract,
    }
    // Client-side validation
    if (Number.isNaN(payload.debut) || Number.isNaN(payload.fin)) {
        formError.value = 'Heures invalides. Utilisez HH:MM:SS.'
        return
    }
    if (payload.fin <= payload.debut) {
        formError.value = 'La fin doit être supérieure au début.'
        return
    }
    submitting.value = true
    try {
        if (editing.value && editingId.value) {
            const url = route ? route('api.videos.sections.update', { video: props.video.id, section: editingId.value }) : `/api/videos/${props.video.id}/sections/${editingId.value}`
            await window.axios.put(url, payload, { headers: headers.value })
        } else {
            const url = route ? route('api.videos.sections.store', { video: props.video.id }) : `/api/videos/${props.video.id}/sections`
            await window.axios.post(url, payload, { headers: headers.value })
        }
        showModal.value = false
        formError.value = ''
        await loadSections()
    } catch (e) {
        formError.value = e?.response?.data?.message || 'Échec de l\'enregistrement.'
    } finally {
        submitting.value = false
    }
}

async function removeSection(s) {
    if (!props.video?.id || !s?.id) return
    const confirmResult = await Swal.fire({
        title: 'Supprimer cette section ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    })
    if (!confirmResult.isConfirmed) return
    try {
        const url = route ? route('api.videos.sections.destroy', { video: props.video.id, section: s.id }) : `/api/videos/${props.video.id}/sections/${s.id}`
        await window.axios.delete(url, { headers: headers.value })
        await loadSections()
        await Swal.fire({ icon: 'success', title: 'Section supprimée', timer: 1200, showConfirmButton: false })
    } catch (e) {
        await Swal.fire({ icon: 'error', title: 'Échec de la suppression', text: e?.response?.data?.message || e?.message || 'Erreur inconnue' })
    }
}

function playFrom(seconds) {
    if (!props.video?.youtube_id) return
    // reload iframe with start param for simplicity
    const base = `https://www.youtube.com/embed/${props.video.youtube_id}?autoplay=1&start=${Math.max(0, Number(seconds || 0))}`
    if (playerEl.value) {
        playerEl.value.src = base
    }
}

async function autoSplit() {
    if (!props.video?.id) return
    // Must have configured LLMs
    if (!llms.value || llms.value.length === 0) {
        alert('Aucun LLM configuré. Veuillez configurer une clé API puis réessayez.')
        return
    }
    // Initialize modal defaults
    selectedPrompteId.value = promptesSection.value?.[0]?.id ?? null
    selectedLlmId.value = llms.value?.[0]?.llm_id ?? null
    autoPrompt.value = promptesSection.value?.[0]?.contenu ?? ''
    autoError.value = ''
    showAutoModal.value = true
}

function closeAuto() { showAutoModal.value = false }

async function submitAuto() {
    autoError.value = ''
    if (!selectedLlmId.value) { autoError.value = 'Veuillez sélectionner un LLM configuré.'; return }
    const chosen = promptesSection.value.find(p => p.id === selectedPrompteId.value)
    const basePrompt = (autoPrompt.value || chosen?.contenu || '').trim()
    if (!basePrompt) { autoError.value = 'Le prompte ne peut pas être vide.'; return }

    // Add system layer (not persisted): ask for strict JSON format
    const systemInstruction = `\n\n[INSTRUCTION SYSTEME]\nTu es un service qui découpe une vidéo en sections à partir d'une transcription.\nTu dois répondre STRICTEMENT en JSON de la forme:\n{\n  "sections": [\n    { "titre": "string", "debut": "HH:MM:SS", "fin": "HH:MM:SS" }\n  ]\n}\nAucune autre sortie n'est autorisée.`
    const finalPrompt = basePrompt + systemInstruction

    autoSubmitting.value = true
    try {
        const url = route ? route('api.videos.sections.auto', { video: props.video.id }) : `/api/videos/${props.video.id}/sections/auto`
        // Send model info and custom_instruction to be stored; finalPrompt is used for the LLM call trigger server-side later
        await window.axios.post(url, {
            llm_id: selectedLlmId.value,
            custom_instruction: basePrompt, // persisted
            prompt: finalPrompt, // for LLM processing pipeline (not persisted)
        }, { headers: headers.value })
        showAutoModal.value = false
        await loadSections()
    } catch (e) {
        autoError.value = e?.response?.data?.message || 'Échec du découpage automatique.'
    } finally {
        autoSubmitting.value = false
    }
}

// Watcher to update textarea when template changes
watch(selectedPrompteId, (val, old) => {
    const chosen = promptesSection.value.find(p => p.id === val)
    if (!chosen) return
    // Update the textarea only if the current value equals the old template or is empty
    if (!autoPrompt.value || autoPrompt.value === (promptesSection.value.find(p => p.id === old)?.contenu || '')) {
        autoPrompt.value = chosen.contenu || ''
    }
})
</script>
