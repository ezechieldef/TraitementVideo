<template>
    <div>
        <!-- Header / Langue -->
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4 backdrop-blur-md">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-md font-semibold theme-title">Résumé</h2>
                    <p class="theme-muted-text text-sm">Générer et gérer les résumés de sections</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-xs theme-muted-text">Langue</label>
                        <select v-model="selectedLang"
                            class="rounded-md border border-slate-300 px-2 py-1 text-sm min-w-[90px]">
                            <option v-for="l in availableLangues" :key="l" :value="l">{{ l }}</option>
                            <option v-if="availableLangues.length === 0" :value="''">—</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <SectionStack :sections="sections" v-model:selectedId="selectedSectionId" :deletingId="deletingId"
            @play="openPlay" @remove="removeSection" />

        <!-- Two blocks: Sections | Résumés -->
        <div class="">
            <!-- Sections -->


            <!-- Résumés -->
            <div class="rounded-xl border border-gray-500/20 overflow-hidden theme-surface md:col-span-2">
                <div class="px-3 py-2 theme-surface font-semibold flex items-center justify-between gap-3">
                    <span class="theme-body">Résumés</span>
                    <button class="px-3 py-1 rounded-md  bg-green-500 text-white hover:bg-green-700 disabled:opacity-50"
                        :disabled="!selectedSection" @click="openGenerate">
                        <i class="ti ti-sparkles me-1"></i>
                        Générer résumé
                    </button>
                </div>
            </div>
            <div>
                <div class="mt-4">

                    <div v-if="resumeLoading" class="text-sm theme-muted-text">Chargement des résumés…</div>
                    <div v-else-if="resumes.length === 0" class="text-sm theme-muted-text">Aucun résumé pour cette
                        section.</div>
                    <div v-else class="space-y-0 grid grid-cols-1 md:grid-cols-2 gap-4 ">
                        <div v-for="r in resumes" :key="r.id"
                            class="rounded-md border border-gray-500/20 p-3 theme-surface theme-body relative">
                            <div v-if="r.is_processing"
                                class="absolute inset-0 bg-black/5 backdrop-blur-[1px] rounded-md flex flex-col items-center justify-center text-xs gap-2">
                                <span
                                    class="inline-block w-6 h-6 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></span>
                                <span>Génération…</span>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="font-medium truncate">{{ r.titre || 'Sans titre' }}</div>
                                    <div
                                        class="inline-flex items-center gap-1 theme-muted rounded-lg p-1 text-xs shrink-0">
                                        <i class="ti ti-world text-xs"></i>
                                        {{ r.langue || '-' }}
                                    </div>
                                    <span v-if="r.isApproved"
                                        class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 rounded-lg px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                        <i class="ti ti-check"></i> APPROUVÉ
                                    </span>
                                    <span v-else-if="r.error_message"
                                        class="inline-flex items-center gap-1 bg-red-100 text-red-700 rounded-lg px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">
                                        <i class="ti ti-alert-triangle"></i> ERREUR
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <button class="px-2 py-1 rounded-md border hover:bg-black/5 text-xs" title="Lire"
                                        @click="openResume(r)" :disabled="r.is_processing">
                                        <i class="ti ti-eye me-1"></i> Lire
                                    </button>
                                    <button
                                        class="px-2 py-1 rounded-md border border-red-400/40 hover:bg-red-50 text-red-600 text-xs disabled:opacity-50"
                                        :disabled="deletingResumeId === r.id || r.is_processing" title="Supprimer"
                                        @click="removeResume(r)">
                                        <span v-if="deletingResumeId === r.id"
                                            class="inline-block w-4 h-4 border-2 border-red-600/40 border-t-red-600 rounded-full animate-spin"></span>
                                        <span v-else class="inline-flex items-center gap-1"><i class="ti ti-trash"></i>
                                            Supprimer</span>
                                    </button>
                                    <button v-if="r.error_message"
                                        class="px-2 py-1 rounded-md border border-orange-400/60 text-orange-600 hover:bg-orange-50 text-xs"
                                        @click="retryResume(r)" :disabled="retryingId === r.id">
                                        <span v-if="retryingId === r.id"
                                            class="inline-block w-4 h-4 border-2 border-orange-600/40 border-t-orange-600 rounded-full animate-spin"></span>
                                        <span v-else class="inline-flex items-center gap-1"><i
                                                class="ti ti-refresh"></i> Relancer</span>
                                    </button>
                                </div>
                            </div>
                            <div class="mt-2 text-sm" :style="clamp4">
                                <template v-if="r.error_message">
                                    <span class="text-red-600 text-xs">Erreur: {{ r.error_message }}</span>
                                </template>
                                <template v-else>
                                    {{ r.contenu }}
                                </template>
                            </div>
                            <div class="mt-3" v-if="!r.isApproved && !r.is_processing && !r.error_message">
                                <button
                                    class="px-3 py-1 rounded-md text-xs font-medium bg-slate-600 hover:bg-slate-700 text-white disabled:opacity-40 flex items-center gap-1"
                                    :disabled="approvingId === r.id || r.is_processing || r.error_message"
                                    @click="approveResume(r)">
                                    <span v-if="approvingId === r.id"
                                        class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                                    <span v-else class="inline-flex items-center gap-1">
                                        <i class="ti ti-badge-check"></i>
                                        Sélectionner et approuver ce résumé
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Play modal (YouTube iframe) -->
        <div v-if="showPlay" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 app-modal">
            <div class="w-[95vw] max-w-5xl rounded-lg theme-surface p-4 shadow-lg">
                <div class="text-md font-semibold mb-2">Lecture — {{ playSectionData?.titre || 'Section' }}</div>
                <div class="aspect-video bg-black rounded-xl overflow-hidden">
                    <iframe v-if="video?.youtube_id" class="w-full h-full rounded-xl" :src="playSrc"
                        title="YouTube video"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                    <div v-else class="h-64 flex items-center justify-center theme-muted-text">Aucune vidéo</div>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="px-3 py-1 rounded border" @click="closePlay">Fermer</button>
                </div>
            </div>
        </div>

        <!-- Generate résumé modal -->
        <div v-if="showGenerateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 app-modal">
            <div class="w-[95vw] max-w-2xl rounded-lg theme-surface p-4 shadow-lg">
                <div class="text-md font-semibold mb-2">Générer un résumé</div>
                <div class="space-y-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Choisir un prompte (Résumé)</label>
                            <select v-model="selectedPrompteId"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="p in promptesResume" :key="p.id" :value="p.id">{{ p.titre || ('#' + p.id)
                                }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Clé API configurée</label>
                            <select v-model="selectedTokenId"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="t in apiTokens" :key="t.id" :value="t.id">
                                    {{ t.entite_titre || '—' }} · {{ t.llm_name }} ({{ t.model_version }}) · #{{ t.id }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Langue de travail (lecture seule)</label>
                            <select v-model="generateLangue" disabled
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm bg-gray-50 text-gray-600">
                                <option :value="generateLangue">{{ generateLangue || '—' }}</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs theme-muted-text block mb-1">Personnaliser le prompte</label>
                        <textarea v-model="generatePrompt" rows="10"
                            class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"></textarea>
                        <p v-if="generateError" class="mt-1 text-xs text-red-600">{{ generateError }}</p>
                    </div>
                    <p class="text-xs theme-muted-text">Une couche d'instructions système peut être ajoutée côté serveur
                        pour
                        imposer un format standardisé.</p>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="px-3 py-1 rounded border" @click="closeGenerate">Annuler</button>
                    <button class="px-3 py-1 rounded bg-slate-600 text-white disabled:opacity-50"
                        :disabled="generateSubmitting" @click="submitGenerate">{{ generateSubmitting ? 'Envoi…' :
                            'Valider' }}</button>
                </div>
            </div>
        </div>

        <!-- Read résumé modal -->
        <div v-if="showResumeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 app-modal">
            <div class="w-[95vw] max-w-3xl rounded-lg theme-surface p-4 shadow-lg">
                <div class="text-md font-semibold mb-2">Résumé — {{ resumeToRead?.titre || 'Sans titre' }}</div>
                <div class="border rounded-md p-3 max-h-[70vh] overflow-auto theme-body">
                    <pre class="whitespace-pre-wrap text-sm leading-6">{{ resumeToRead?.contenu }}</pre>
                </div>
                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="px-3 py-1 rounded border" @click="closeResume">Fermer</button>
                </div>
            </div>
        </div>
    </div>

</template>

<script setup>
import { ref, computed, onMounted, inject, watch } from 'vue'
defineOptions({ name: 'StepResumeST' })
import SectionStack from '../SectionStack.vue'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

const props = defineProps({ video: { type: Object, default: null } })
const emit = defineEmits(['canProceed', 'refreshStep'])
const route = inject('route')

// Sections & languages
const sections = ref([])
const availableLangues = ref([])
const selectedLang = ref('')
const selectedSectionId = ref(null)
const selectedSection = computed(() => (sections.value || []).find(s => s.id === selectedSectionId.value) || null)

// Resumes state
const resumes = ref([])
const resumeLoading = ref(false)

// Auth headers
const authToken = ref('')
const headers = ref({})

// Play modal
const showPlay = ref(false)
const playSectionData = ref(null)
const playSrc = computed(() => {
    if (!props.video?.youtube_id || !playSectionData.value) { return '' }
    const start = Math.max(0, Number(playSectionData.value.debut || 0))
    return `https://www.youtube.com/embed/${props.video.youtube_id}?autoplay=1&start=${start}&enablejsapi=1`
})

// Generate resume modal
const showGenerateModal = ref(false)
const promptesResume = ref([])
const apiTokens = ref([])
const selectedPrompteId = ref(null)
const selectedTokenId = ref(null)
const generatePrompt = ref('')
const generateLangue = ref('')
const generateError = ref('')
const generateSubmitting = ref(false)

// Read/Delete resume state
const showResumeModal = ref(false)
const resumeToRead = ref(null)
const deletingResumeId = ref(null)
const approvingId = ref(null)
const retryingId = ref(null)
const pollTimer = ref(null)
const POLL_INTERVAL_MS = 3500

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
const clamp4 = { display: '-webkit-box', WebkitLineClamp: '4', WebkitBoxOrient: 'vertical', overflow: 'hidden' }

onMounted(async () => {
    authToken.value = JSON.parse(localStorage.getItem('auth_token') || 'null') || ''
    headers.value = { Authorization: authToken.value ? `Bearer ${authToken.value}` : '', accept: 'application/json', 'content-type': 'application/json' }
    try { promptesResume.value = JSON.parse(localStorage.getItem('promptes_resume') || '[]') || [] } catch { }
    try { apiTokens.value = JSON.parse(localStorage.getItem('api_tokens_configured') || '[]') || [] } catch { }
    await refreshAll()
})
watch(() => props.video?.id, async () => { await refreshAll() })

async function refreshAll() {
    if (!props.video?.id) return
    await Promise.all([loadLanguages(), loadSections()])
    // Select default section
    if (!selectedSectionId.value && sections.value.length > 0) {
        selectedSectionId.value = sections.value[0].id
    }
    await loadResumes()
    // Allow proceeding if there is at least one section
    emit('canProceed', sections.value.length > 0)
}

async function loadSections() {
    if (!props.video?.id) return
    try {
        const url = route('api.videos.sections.index', { video: props.video.id })
        const params = {}
        if (selectedLang.value) { params.langue = selectedLang.value }
        const { data } = await window.axios.get(url, { headers: headers.value, params })
        const prevSelected = selectedSectionId.value
        sections.value = data?.sections || []
        const idSet = new Set(sections.value.map(s => s.id))
        if (!idSet.has(prevSelected)) {
            selectedSectionId.value = sections.value[0]?.id ?? null
        }
    } catch {
        sections.value = []
        selectedSectionId.value = null
    }
}

async function loadLanguages() {
    try {
        const url = route('api.videos.transcription.languages', { video: props.video.id })
        const { data } = await window.axios.get(url, { headers: headers.value })
        availableLangues.value = data?.languages || []
        if (!selectedLang.value) {
            const base = (props.video?.langue || '').split('-')[0]
            selectedLang.value = base || availableLangues.value[0] || ''
        }
        if (!generateLangue.value) {
            generateLangue.value = selectedLang.value || availableLangues.value[0] || ''
        }
    } catch { availableLangues.value = [] }
}

async function loadResumes() {
    resumes.value = []
    if (!props.video?.id || !selectedSectionId.value) return
    resumeLoading.value = true
    try {
        let url
        try {
            url = route('api.videos.sections.resumes.index', { video: props.video.id, section: selectedSectionId.value })
        } catch {
            url = `/api/videos/${props.video.id}/sections/${selectedSectionId.value}/resumes`
        }
        const { data } = await window.axios.get(url, { headers: headers.value })
        resumes.value = data?.resumes || []
    } catch {
        resumes.value = []
    } finally {
        resumeLoading.value = false
        scheduleOrStopPolling()
    }
}

function selectSection(s) {
    if (!s) return
    selectedSectionId.value = s.id
    loadResumes()
}

function openPlay(s) { playSectionData.value = s; showPlay.value = true }
function closePlay() { showPlay.value = false; playSectionData.value = null }

function openResume(r) { resumeToRead.value = r; showResumeModal.value = true }
function closeResume() { showResumeModal.value = false; resumeToRead.value = null }

async function removeSection(s) {
    if (!props.video?.id || !s?.id) return
    if (sections.value.length <= 1) {
        await Swal.fire({ icon: 'info', title: 'Impossible de supprimer', text: 'Il doit rester au moins une section.' })
        return
    }
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
        deletingId.value = s.id
        const url = route ? route('api.videos.sections.destroy', { video: props.video.id, section: s.id }) : `/api/videos/${props.video.id}/sections/${s.id}`
        await window.axios.delete(url, { headers: headers.value })
        await loadSections()
        // Maintain a valid selection
        if (sections.value.length > 0 && !sections.value.find(x => x.id === selectedSectionId.value)) {
            selectedSectionId.value = sections.value[0].id
        }
        await loadResumes()
        // Inform parent that step progression may have changed (e.g., removing the only section)
        emit('refreshStep')
        await Swal.fire({ icon: 'success', title: 'Section supprimée', timer: 1200, showConfirmButton: false })
    } catch (e) {
        await Swal.fire({ icon: 'error', title: 'Échec de la suppression', text: e?.response?.data?.message || e?.message || 'Erreur inconnue' })
    } finally {
        deletingId.value = null
    }
}
const deletingId = ref(null)

function openGenerate() {
    if (!selectedSection.value) return
    selectedPrompteId.value = promptesResume.value?.[0]?.id ?? null
    selectedTokenId.value = apiTokens.value?.[0]?.id ?? null
    generatePrompt.value = (promptesResume.value?.[0]?.contenu || '')
    generateError.value = ''
    generateLangue.value = selectedLang.value || selectedSection.value.langue || availableLangues.value[0] || ''
    showGenerateModal.value = true
}
function closeGenerate() { showGenerateModal.value = false }

async function submitGenerate() {
    generateError.value = ''
    if (!selectedSection.value) { generateError.value = 'Aucune section sélectionnée.'; return }
    if (!selectedTokenId.value) { generateError.value = 'Veuillez sélectionner une clé API configurée.'; return }
    const promptText = (generatePrompt.value || (promptesResume.value.find(p => p.id === selectedPrompteId.value)?.contenu || '')).trim()
    if (!promptText) { generateError.value = 'Le prompte ne peut pas être vide.'; return }

    generateSubmitting.value = true
    try {
        let url
        try {
            url = route('api.videos.sections.resumes.generate', { video: props.video.id, section: selectedSection.value.id })
        } catch {
            url = `/api/videos/${props.video.id}/sections/${selectedSection.value.id}/resumes/generate`
        }
        await window.axios.post(url, {
            token_id: selectedTokenId.value,
            custom_instruction: promptText,
            langue: generateLangue.value || null,
        }, { headers: headers.value })
        showGenerateModal.value = false
        await loadResumes()
        // Generation may unlock next step
        emit('refreshStep')
        // Poll until generated
        scheduleOrStopPolling(true)
    } catch (e) {
        generateError.value = e?.response?.data?.message || 'Échec de la génération du résumé.'
    } finally {
        generateSubmitting.value = false
    }
}

// Update prompt textarea when template changes (if not manually edited)
watch(selectedPrompteId, (val, old) => {
    const chosen = promptesResume.value.find(p => p.id === val)
    if (!chosen) return
    if (!generatePrompt.value || generatePrompt.value === (promptesResume.value.find(p => p.id === old)?.contenu || '')) {
        generatePrompt.value = chosen.contenu || ''
    }
})

// Reload when language changes
watch(selectedLang, async () => {
    await loadSections()
    await loadResumes()
    if (!generateLangue.value) generateLangue.value = selectedLang.value
})

// Reload resumes when selected section changes (via carousel or click)
watch(selectedSectionId, async () => { await loadResumes() })

// Emit refreshStep automatically when resumes list transitions between empty and non-empty
watch(() => resumes.value.length, (now, prev) => {
    if ((now === 0) !== (prev === 0)) {
        emit('refreshStep')
    }
})

async function removeResume(r) {
    if (!props.video?.id || !selectedSectionId.value || !r?.id) return
    const confirmResult = await Swal.fire({
        title: 'Supprimer ce résumé ?',
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
        deletingResumeId.value = r.id
        let url
        try {
            url = route('api.videos.sections.resumes.destroy', { video: props.video.id, section: selectedSectionId.value, resume: r.id })
        } catch {
            url = `/api/videos/${props.video.id}/sections/${selectedSectionId.value}/resumes/${r.id}`
        }
        await window.axios.delete(url, { headers: headers.value })
        await loadResumes()
        // Resume deletion might downgrade progression if last resume removed
        emit('refreshStep')
        await Swal.fire({ icon: 'success', title: 'Résumé supprimé', timer: 1200, showConfirmButton: false })
    } catch (e) {
        await Swal.fire({ icon: 'error', title: 'Échec de la suppression', text: e?.response?.data?.message || e?.message || 'Erreur inconnue' })
    } finally {
        deletingResumeId.value = null
    }
}

async function approveResume(r) {
    if (!props.video?.id || !selectedSectionId.value || !r?.id) return
    // If this resume already approved, short-circuit
    if (r.isApproved) {
        await Swal.fire({ icon: 'info', title: 'Déjà approuvé', timer: 1200, showConfirmButton: false })
        return
    }
    // If more than one resume exists ask if we keep others
    let keepOthers = true
    if (resumes.value.length > 1) {
        const result = await Swal.fire({
            title: 'Conserver les autres résumés ?',
            html: '<p class="text-sm">Vous avez choisi de retenir ce résumé.<br/>Souhaitez-vous conserver les autres restants ou les supprimer ?</p>',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Conserver les autres',
            denyButtonText: 'Supprimer les autres',
            cancelButtonText: 'Annuler',
            reverseButtons: true,
        })
        if (result.isDismissed) return
        if (result.isDenied) { keepOthers = false }
    }
    approvingId.value = r.id
    try {
        let url
        try {
            url = route('api.videos.sections.resumes.approve', { video: props.video.id, section: selectedSectionId.value, resume: r.id })
        } catch {
            url = `/api/videos/${props.video.id}/sections/${selectedSectionId.value}/resumes/${r.id}/approve`
        }
        await window.axios.post(url, { keep_others: keepOthers }, { headers: headers.value })
        await loadResumes()
        emit('refreshStep')
        await Swal.fire({ icon: 'success', title: 'Résumé approuvé', timer: 1300, showConfirmButton: false })
    } catch (e) {
        await Swal.fire({ icon: 'error', title: 'Échec de l\'approbation', text: e?.response?.data?.message || e?.message || 'Erreur inconnue' })
    } finally {
        approvingId.value = null
    }
}

function scheduleOrStopPolling(force = false) {
    if (pollTimer.value) {
        clearTimeout(pollTimer.value)
        pollTimer.value = null
    }
    const hasProcessing = resumes.value.some(r => r.is_processing)
    if (hasProcessing || force) {
        pollTimer.value = setTimeout(async () => {
            await loadResumes()
        }, POLL_INTERVAL_MS)
    }
}

async function retryResume(r) {
    if (!r || r.is_processing) return
    retryingId.value = r.id
    try {
        await removeResume(r)
        openGenerate()
    } finally {
        retryingId.value = null
    }
}

onMounted(() => scheduleOrStopPolling())
</script>
