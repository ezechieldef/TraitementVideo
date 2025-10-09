<template>
    <div>
        <div class="theme-surface my-2 py-2 px-4 rounded-lg mb-4">
            <h2 class="text-md font-semibold theme-title">Personnaliser</h2>
            <p class="theme-muted-text text-sm">Ajuster les résumés approuvés ou en cours</p>
        </div>
        <div class="mb-4 flex items-center justify-between flex-wrap gap-3">
            <div class="text-xs theme-muted-text">
                <span v-if="loading">Chargement des résumés…</span>
                <span v-else>{{ resumes.length }} résumé(s) affiché(s)</span>
            </div>
            <div class="flex items-center gap-2 text-xs" v-if="dirtyIds.size > 0">
                <span class="text-amber-600">Modifications non sauvegardées ({{ dirtyIds.size }})</span>
                <button class="px-2 py-1 rounded border text-xs" @click="reload">Recharger</button>
            </div>
        </div>
        <ResumeCarousel :resumes="resumes" v-model:selectedId="selectedId" :saving="savingId !== null" @save="onSave" />
    </div>
</template>

<script setup>
import { ref, watch, onMounted, inject } from 'vue'
import ResumeCarousel from '../ResumeCarousel.vue'
import Swal from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

const props = defineProps({ video: { type: Object, default: null } })
const route = inject('route')

const resumes = ref([])
const selectedId = ref(null)
const loading = ref(false)
const savingId = ref(null)
const dirtyIds = ref(new Set())

const authToken = ref('')
const headers = ref({})

onMounted(async () => {
    authToken.value = JSON.parse(localStorage.getItem('auth_token') || 'null') || ''
    headers.value = { Authorization: authToken.value ? `Bearer ${authToken.value}` : '', accept: 'application/json', 'content-type': 'application/json' }
    await loadAll()
})
watch(() => props.video?.id, async () => { await loadAll() })

async function loadAll() {
    resumes.value = []
    selectedId.value = null
    if (!props.video?.id) return
    loading.value = true
    try {
        const url = route ? route('api.videos.resumes.index', { video: props.video.id }) : `/api/videos/${props.video.id}/resumes`
        const { data } = await window.axios.get(url, { headers: headers.value })
        const all = data?.resumes || []
        // Supprimer totalement les résumés en cours de génération
        resumes.value = all.filter(r => !r.is_processing && r.titre !== "Génération en cours…")
        selectedId.value = resumes.value.length > 0 ? resumes.value[0].id : null
    } catch (e) {
        resumes.value = []
    } finally {
        loading.value = false
    }
}

function markDirty(id) { dirtyIds.value.add(id) }

async function onSave(payload) {
    if (!props.video?.id || !payload?.id) return
    savingId.value = payload.id
    try {
        const url = route ? route('api.videos.resumes.update', { video: props.video.id, resume: payload.id }) : `/api/videos/${props.video.id}/resumes/${payload.id}`
        await window.axios.put(url, { titre: payload.titre, contenu: payload.contenu }, { headers: headers.value })
        // Update local copy
        const idx = resumes.value.findIndex(r => r.id === payload.id)
        if (idx >= 0) {
            resumes.value[idx] = { ...resumes.value[idx], titre: payload.titre, contenu: payload.contenu }
            dirtyIds.value.delete(payload.id)
        }
        await Swal.fire({ icon: 'success', title: 'Résume sauvegardé', timer: 1200, showConfirmButton: false })
    } catch (e) {
        await Swal.fire({ icon: 'error', title: 'Échec de la sauvegarde', text: e?.response?.data?.message || e?.message || 'Erreur inconnue' })
    } finally {
        savingId.value = null
    }
}

async function reload() { await loadAll(); dirtyIds.value.clear() }
</script>
