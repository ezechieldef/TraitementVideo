<template>
    <div>

        <div class="bg-white shadow rounded-md p-2 mb-3 text-sky-600 text-sm">
            <i class="ti ti-info-circle me-1 "></i> <strong class="me-2">ASTUCE</strong> : Procédez au découpage
            automatique. Supprimez les sections
            indésirables au besoin afin de conserver uniquement celles que vous souhaitez résumer.
        </div>

        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4 backdrop-blur-md">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h2 class="text-md font-semibold theme-title">Sectionner</h2>
                    <p class="theme-muted-text text-sm">Découper la vidéo</p>
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
                    <button class="px-3 py-1 rounded-md bg-slate-600 text-white hover:bg-slate-700 disabled:opacity-50"
                        :disabled="busy || !video?.id" @click="autoSplit">Découper automatiquement</button>
                    <button class="px-3 py-1 rounded-md bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
                        :disabled="busy || !video?.id" @click="openModal(null)">Ajouter une section</button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            <!-- : sections as cards -->
            <div class="rounded-xl border border-gray-500/20 overflow-hidden theme-surface relative ">
                <div class="px-3 py-2 theme-surface font-semibold flex  justify-between gap-3">
                    <span>Sections</span>
                    <div v-if="generatingAuto"
                        class="flex items-center gap-2 text-xs font-normal text-indigo-600 me-auto ms-4">
                        <span
                            class="inline-block w-4 h-4 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></span>
                        <span>Découpage automatique en cours…</span>
                        <button
                            class="text-[10px] uppercase tracking-wide px-2 py-0.5 rounded border border-indigo-300 hover:bg-indigo-50"
                            @click="cancelAutoGeneration">Annuler</button>
                    </div>
                    <div class="flex items-center justify-end flex-wrap gap-3"
                        v-if="!generatingAuto && selectedIds.length > 0">
                        <label class="flex items-center text-nowrap gap-2 text-sm">
                            <input type="checkbox" :checked="allSelected" @change="toggleSelectAll($event)" />
                            <span>Tout sélectionner</span>
                        </label>
                        <button
                            class="px-3 py-1 rounded-md text-nowrap bg-red-600 text-white disabled:opacity-50 flex items-center gap-2"
                            :disabled="selectedIds.length === 0 || bulkDeleting" @click="bulkDelete">
                            <span v-if="bulkDeleting"
                                class="inline-block w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                            <span>{{ bulkDeleting ? 'Suppression…' : `Supprimer sélection (${selectedIds.length})`
                            }}</span>
                        </button>
                    </div>
                </div>
                <!-- Bulk deleting overlay -->
                <div v-if="bulkDeleting"
                    class="absolute inset-0 bg-black/10 backdrop-blur-[1px] flex items-center justify-center z-10 pointer-events-none">
                    <div class="flex items-center gap-3 px-4 py-2 rounded-md bg-black/40 text-white">
                        <span
                            class="inline-block w-5 h-5 border-2 border-white/40 border-t-white rounded-full animate-spin"></span>
                        <span>Suppression en cours…</span>
                    </div>
                </div>
                <div class="p-3">

                    <div v-if="sections.length > 0" class="grid grid-cols-1 sm:grid-cols-1 gap-3">
                        <div v-for="s in sections" :key="s.id"
                            class="group relative rounded-lg border border-gray-500/20 bg-white/50 dark:bg-white/5 p-3 shadow-sm hover:shadow-md transition">

                            <div class="flex items-center justify-between gap-2 mb-2">
                                <input type="checkbox" :value="s.id" v-model="selectedIds" />



                                <div class="text-xs text-slate-500 flex items-center gap-2">

                                    <span class="inline-flex items-center gap-1"><i class="ti ti-clock text-xs"></i>
                                        {{ fmt(s.debut) }} - {{ fmt(s.fin) }}</span>
                                    <span class="h-3 w-px bg-slate-300/60"></span>
                                </div>


                                <div class="flex items-center gap-1 shrink-0">

                                    <span class="inline-flex items-center gap-1 bg-slate-100 rounded-lg p-1 text-xs"><i
                                            class="ti ti-globe text-xs"></i>
                                        {{ s.langue || '-' }}</span>
                                    <span class="inline-flex items-center gap-1 bg-slate-100 rounded-lg p-1 text-xs"><i
                                            class="ti ti-file-text text-xs"></i>
                                        {{ s.resumes_count ?? 0 }}</span>
                                </div>
                            </div>
                            <div class="font-medium truncate" :title="s.titre || 'Sans titre'">
                                {{ s.titre || 'Sans titre' }}</div>
                            <div class="flex items-center gap-2 mt-2 w-full">

                                <button
                                    class="size-8 px-2 w-full rounded-md border border-gray-400/40 flex items-center justify-center hover:bg-black/5 bg-[#F9C98B]"
                                    title="Lire la section" @click="playFrom(s.debut)">
                                    <i class="ti ti-player-play me-2"></i>
                                    <span class="">Lire la section</span>
                                </button>
                                <button
                                    class="size-8 px-2 rounded-md border border-gray-400/40 flex items-center justify-center hover:bg-black/5"
                                    title="Modifier" @click="openModal(s)">
                                    <i class="ti ti-pencil"></i>
                                    <span class="sr-only">Modifier</span>
                                </button>
                                <button
                                    class="size-8 px-2 rounded-md border border-red-400/40 flex items-center justify-center hover:bg-red-50 text-red-600 disabled:opacity-50"
                                    :disabled="deletingId === s.id" title="Supprimer" @click="removeSection(s)">
                                    <span v-if="deletingId === s.id"
                                        class="inline-block w-4 h-4 border-2 border-red-600/40 border-t-red-600 rounded-full animate-spin"></span>
                                    <i v-else class="ti ti-trash"></i>
                                    <span class="sr-only">Supprimer</span>
                                </button>
                                <!-- <button class="px-2.5 w-full py-2 rounded-md bg-[#F9C98B] text-black text-xs">Générer
                                    résumé</button> -->


                            </div>
                        </div>


                    </div>
                    <div v-else class="px-3 py-6 text-center theme-muted-text text-sm">Aucune section pour le moment.
                    </div>
                </div>
            </div>
            <!-- : YouTube iframe -->
            <div class="rounded-xl border border-gray-500/20 overflow-hidden theme-body theme-surface md:col-span-2"
                style="position: sticky; top: 50px; align-self: flex-start;">
                <div class="px-3 py-2 theme-surface font-semibold flex items-center justify-between gap-3">
                    <span>Prévisualisation</span>

                </div>
                <div class="aspect-video bg-black my-2 mx-2 rounded-xl">
                    <iframe v-if="video?.youtube_id" ref="playerEl" class="w-full h-full rounded-xl" :src="playerSrc"
                        title="YouTube video"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowfullscreen></iframe>
                    <div v-else class="h-64 flex items-center justify-center theme-muted-text">Aucune vidéo</div>
                </div>
            </div>

        </div>


        <!-- Modal add/edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 app-modal">
            <div
                :class="['w-[95vw]', showTranscription ? 'max-w-5xl' : 'max-w-2xl', 'rounded-lg theme-surface p-4 shadow-lg']">
                <div class="text-md font-semibold mb-1">{{ editing ? 'Modifier la section' : 'Nouvelle section' }}</div>
                <p v-if="formError" class="text-xs text-red-600 mb-2">{{ formError }}</p>
                <div :class="showTranscription ? 'grid grid-cols-1 md:grid-cols-2 gap-4' : 'space-y-3'">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="text-xs theme-muted-text">Afficher la transcription</label>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" v-model="showTranscription" class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:bg-red-600 transition">
                                </div>
                                <div
                                    class="absolute left-1 top-1 w-3.5 h-3.5 bg-white rounded-full transition peer-checked:translate-x-5">
                                </div>
                            </label>
                        </div>
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
                        <div>
                            <label class="text-xs theme-muted-text block mb-1">Langue</label>
                            <select v-model="form.langue"
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
                                <option v-for="l in availableLangues" :key="l" :value="l">{{ l }}</option>
                                <option :value="''">(non spécifié)</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2">
                            <input id="extract" v-model="form.extract" type="checkbox" />
                            <label for="extract" class="text-xs">Sauvegarder + extraire la transcription</label>
                        </div>
                    </div>
                    <div v-if="showTranscription" class="border rounded-md p-3 overflow-auto max-h-[60vh] bg-black/5">
                        <div class="text-xs theme-muted-text mb-2">Aperçu de la transcription (section)</div>
                        <pre class="text-xs whitespace-pre-wrap leading-5">{{ modalTranscription }}</pre>
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
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
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
                            <select v-model="autoLangue" disabled
                                class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm bg-gray-50 text-gray-600">
                                <option :value="autoLangue">{{ autoLangue || '—' }}</option>
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
const emit = defineEmits(['canProceed', 'refreshStep'])
const route = inject('route')

const sections = ref([])
const transcript = ref('')
const busy = ref(false)
const bulkDeleting = ref(false)
const deletingId = ref(null)
const showModal = ref(false)
const editing = ref(false)
const editingId = ref(null)
const form = ref({ titre: '', debutStr: '00:00:00', finStr: '00:00:10', langue: '', extract: true })
const formError = ref('')
const submitting = ref(false)
const showTranscription = ref(false)
const modalTranscription = ref('')

// Auto modal state
const showAutoModal = ref(false)
const promptesSection = ref([])
const llms = ref([])
const apiTokens = ref([])
const selectedPrompteId = ref(null)
const selectedLlmId = ref(null)
const selectedTokenId = ref(null)
const autoPrompt = ref('')
const autoError = ref('')
const autoSubmitting = ref(false)
const generatingAuto = ref(false)
const autoPollTimer = ref(null)
const autoPollAttempts = ref(0)
const baseSectionCount = ref(0)
const AUTO_POLL_INTERVAL_MS = 3500
const MAX_AUTO_POLL_ATTEMPTS = 25
const availableLangues = ref([])
const autoLangue = ref('')
const selectedLang = ref('')
const selectedIds = ref([])

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
    try { apiTokens.value = JSON.parse(localStorage.getItem('api_tokens_configured') || '[]') || [] } catch { }
})
watch(() => props.video?.id, async () => { await refreshAll() })

async function refreshAll() {
    if (!props.video?.id) return
    await Promise.all([loadSections(), loadLanguages()])
    // Load transcript after languages so we can apply selectedLang
    await loadTranscript()
}

async function loadSections() {
    if (!props.video?.id) return
    try {
        const url = route('api.videos.sections.index', { video: props.video.id })
        const params = {}
        if (selectedLang.value) { params.langue = selectedLang.value }
        const { data } = await window.axios.get(url, { headers: headers.value, params })
        sections.value = data?.sections || []
        // Inform parent if we can proceed (at least one section). If none and we filtered by language,
        // perform a quick fallback check without language to see if any section exists globally.
        let can = (sections.value.length > 0)
        if (!can && selectedLang.value) {
            try {
                const { data: dataAll } = await window.axios.get(url, { headers: headers.value })
                can = (dataAll?.sections || []).length > 0
            } catch { /* ignore */ }
        }
        emit('canProceed', can)
        // Prune selection to existing ids
        const existing = new Set((sections.value || []).map(s => s.id))
        selectedIds.value = selectedIds.value.filter(id => existing.has(id))
    } catch { sections.value = [] }
}
async function loadTranscript() {
    try {
        const url = selectedLang.value
            ? route('api.videos.transcription.showByLanguage', { video: props.video.id, langue: selectedLang.value })
            : route('api.videos.transcription.latest', { video: props.video.id })
        const { data } = await window.axios.get(url, { headers: headers.value })
        transcript.value = data?.transcription?.contenu || ''
    } catch { transcript.value = '' }
}

async function loadLanguages() {
    try {
        const url = route('api.videos.transcription.languages', { video: props.video.id })
        const { data } = await window.axios.get(url, { headers: headers.value })
        availableLangues.value = data?.languages || []
        // Set defaults once
        if (!selectedLang.value) {
            const base = (props.video?.langue || '').split('-')[0]
            selectedLang.value = base || availableLangues.value[0] || ''
        }
        if (!autoLangue.value) autoLangue.value = selectedLang.value || availableLangues.value[0] || ''
        if (!form.value.langue) form.value.langue = selectedLang.value || availableLangues.value[0] || ''
    } catch { availableLangues.value = [] }
}

function openModal(s) {
    editing.value = !!s
    editingId.value = s?.id || null
    form.value = {
        titre: s?.titre || '',
        debutStr: fmt(s?.debut || 0),
        finStr: fmt(s?.fin || 0),
        langue: s?.langue || (selectedLang.value || availableLangues.value[0] || ''),
        extract: true,
    }
    formError.value = ''
    showTranscription.value = false
    modalTranscription.value = ''
    showModal.value = true
}
function closeModal() { showModal.value = false }

async function submitSection() {
    if (!props.video?.id) return
    const payload = {
        titre: form.value.titre || null,
        debut: hmsToSec(form.value.debutStr),
        fin: hmsToSec(form.value.finStr),
        langue: form.value.langue || null,
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
        // A new or updated section could change progression (especially a first section)
        emit('refreshStep')
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
        deletingId.value = s.id
        const url = route ? route('api.videos.sections.destroy', { video: props.video.id, section: s.id }) : `/api/videos/${props.video.id}/sections/${s.id}`
        await window.axios.delete(url, { headers: headers.value })
        await loadSections()
        emit('refreshStep')
        await Swal.fire({ icon: 'success', title: 'Section supprimée', timer: 1200, showConfirmButton: false })
    } catch (e) {
        await Swal.fire({ icon: 'error', title: 'Échec de la suppression', text: e?.response?.data?.message || e?.message || 'Erreur inconnue' })
    } finally {
        deletingId.value = null
    }
}

const allSelected = computed(() => sections.value.length > 0 && selectedIds.value.length === sections.value.length)
function toggleSelectAll(ev) {
    const checked = !!ev?.target?.checked
    if (checked) {
        selectedIds.value = (sections.value || []).map(s => s.id)
    } else {
        selectedIds.value = []
    }
}

async function bulkDelete() {
    if (!props.video?.id || selectedIds.value.length === 0) return
    const count = selectedIds.value.length
    const confirmResult = await Swal.fire({
        title: `Supprimer ${count} section${count > 1 ? 's' : ''} ?`,
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
    bulkDeleting.value = true
    try {
        const ids = [...selectedIds.value]
        const urlFor = (id) => route ? route('api.videos.sections.destroy', { video: props.video.id, section: id }) : `/api/videos/${props.video.id}/sections/${id}`
        const tasks = ids.map(id => window.axios.delete(urlFor(id), { headers: headers.value }).then(() => ({ id, ok: true })).catch(e => ({ id, ok: false, err: e })))
        const results = await Promise.allSettled(tasks)
        const flat = results.map(r => (r.status === 'fulfilled' ? r.value : r.reason))
        const okCount = flat.filter(x => x?.ok).length
        const failCount = flat.length - okCount
        await loadSections()
        emit('refreshStep')
        selectedIds.value = []
        if (failCount === 0) {
            await Swal.fire({ icon: 'success', title: `${okCount} supprimée${okCount > 1 ? 's' : ''}`, timer: 1400, showConfirmButton: false })
        } else {
            await Swal.fire({ icon: 'warning', title: `${okCount} supprimée${okCount > 1 ? 's' : ''}, ${failCount} échec${failCount > 1 ? 's' : ''}`, text: 'Certaines suppressions ont échoué.' })
        }
    } finally {
        bulkDeleting.value = false
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
    // Must have configured API tokens
    if (!apiTokens.value || apiTokens.value.length === 0) {
        alert('Aucune clé API configurée. Veuillez configurer une clé puis réessayez.')
        return
    }
    // Initialize modal defaults
    selectedPrompteId.value = promptesSection.value?.[0]?.id ?? null
    selectedTokenId.value = apiTokens.value?.[0]?.id ?? null
    autoPrompt.value = promptesSection.value?.[0]?.contenu ?? ''
    autoError.value = ''
    // verrouiller sur la langue de travail
    autoLangue.value = selectedLang.value || availableLangues.value[0] || ''
    showAutoModal.value = true
}

function closeAuto() { showAutoModal.value = false }

async function submitAuto() {
    autoError.value = ''
    if (!selectedTokenId.value) { autoError.value = 'Veuillez sélectionner une clé API configurée.'; return }
    const chosen = promptesSection.value.find(p => p.id === selectedPrompteId.value)
    const basePrompt = (autoPrompt.value || chosen?.contenu || '').trim()
    if (!basePrompt) { autoError.value = 'Le prompte ne peut pas être vide.'; return }

    // Add system layer (not persisted): ask for strict JSON format
    const systemInstruction = `\n\n[INSTRUCTION SYSTEME]\nTu es un service qui découpe une vidéo en sections à partir d'une transcription.\nTu dois répondre STRICTEMENT en JSON de la forme:\n{\n  "sections": [\n    { "titre": "string", "debut": "HH:MM:SS", "fin": "HH:MM:SS" }\n  ]\n}\nAucune autre sortie n'est autorisée.`
    const finalPrompt = basePrompt + systemInstruction

    autoSubmitting.value = true
    // baseline count before triggering async
    baseSectionCount.value = sections.value.length
    try {
        const url = route ? route('api.videos.sections.auto', { video: props.video.id }) : `/api/videos/${props.video.id}/sections/auto`
        // Send model info and custom_instruction to be stored; finalPrompt is used for the LLM call trigger server-side later
        const { data, status } = await window.axios.post(url, {
            token_id: selectedTokenId.value,
            custom_instruction: basePrompt, // persisted
            prompt: finalPrompt, // for LLM processing pipeline (not persisted)
            langue: selectedLang.value || autoLangue.value || null,
            async: true,
        }, { headers: headers.value })
        showAutoModal.value = false
        if (status === 202 && data?.queued) {
            // Async path
            generatingAuto.value = true
            autoPollAttempts.value = 0
            scheduleAutoPolling(true)
        } else {
            // Legacy sync path (sections already created)
            await loadSections()
            emit('refreshStep')
            await Swal.fire({ icon: 'success', title: 'Découpage terminé', timer: 1400, showConfirmButton: false })
        }
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

// Reload transcript when language changes
watch(selectedLang, async () => {
    autoLangue.value = selectedLang.value || autoLangue.value
    await Promise.all([loadTranscript(), loadSections()])
    // Keep defaults in modals aligned
    if (form.value && !editing.value) form.value.langue = selectedLang.value
})

// Compute modal transcription when toggled or times change
watch([showTranscription, () => form.value.debutStr, () => form.value.finStr, () => transcript.value], () => {
    if (!showTranscription.value) { modalTranscription.value = ''; return }
    const start = hmsToSec(form.value.debutStr)
    const end = hmsToSec(form.value.finStr)
    modalTranscription.value = sliceTranscript(transcript.value || '', start, end)
})

function sliceTranscript(text, startSec, endSec) {
    const lines = String(text || '').split(/\r?\n/)
    const out = []
    for (const line of lines) {
        const m = line.match(/^\[([0-9]{2}):([0-9]{2}):([0-9]{2})\]\s*(.+)$/)
        if (!m) continue
        const sec = Number(m[1]) * 3600 + Number(m[2]) * 60 + Number(m[3])
        if (sec >= startSec && sec <= endSec) out.push(line)
    }
    return out.join('\n')
}

function scheduleAutoPolling(force = false) {
    if (autoPollTimer.value) {
        clearTimeout(autoPollTimer.value)
        autoPollTimer.value = null
    }
    if (!generatingAuto.value && !force) { return }
    autoPollTimer.value = setTimeout(async () => {
        autoPollAttempts.value += 1
        await loadSections()
        // Success condition: new sections appeared
        if (sections.value.length > baseSectionCount.value) {
            generatingAuto.value = false
            emit('refreshStep')
            await Swal.fire({ icon: 'success', title: 'Sections générées', timer: 1500, showConfirmButton: false })
            return
        }
        if (autoPollAttempts.value >= MAX_AUTO_POLL_ATTEMPTS) {
            generatingAuto.value = false
            await Swal.fire({ icon: 'warning', title: 'Découpage lent', text: 'Aucune nouvelle section détectée. Réessayez plus tard.' })
            return
        }
        scheduleAutoPolling()
    }, AUTO_POLL_INTERVAL_MS)
}

function cancelAutoGeneration() {
    generatingAuto.value = false
    if (autoPollTimer.value) { clearTimeout(autoPollTimer.value); autoPollTimer.value = null }
}

// If user leaves component while polling, clear timer
onMounted(() => {
    // no-op now, could restore state from localStorage if needed
})
</script>
