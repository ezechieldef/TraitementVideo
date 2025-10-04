<template>
    <div class="space-y-6">
        <!-- Stepper header -->
        <div class="">
            <ol class="flex items-center gap-3 flex-wrap border border-gray-500/30 py-2 px-3 rounded-lg">
                <li v-for="(s, idx) in steps" :key="s.key" class="flex items-center gap-2"
                    :class="isDisabled(idx) ? 'cursor-not-allowed' : 'cursor-pointer'" @click="goTo(idx)">
                    <div :class="badgeClasses(idx)">
                        {{ idx + 1 }}
                    </div>
                    <div>
                        <div
                            :class="['text-sm font-semibold', currentIndex === idx ? 'theme-title' : 'theme-muted-text']">
                            {{ s.title }}</div>
                        <div class="text-xs theme-muted-text">{{ s.subtitle }}</div>
                    </div>
                    <span v-if="idx < steps.length - 1" class="mx-2 text-slate-400">»</span>
                </li>
            </ol>
        </div>

        <!-- Step content -->
        <div class="">
            <component :is="currentComponent" :video="video" />
        </div>

        <!-- Footer navigation -->
        <div class="flex items-center justify-between text-sm">
            <button class="rounded-lg px-4 py-2 theme-muted-text hover-theme-muted disabled:opacity-50"
                :disabled="currentIndex === 0" @click="prev">
                Précédent
            </button>
            <div class="theme-muted-text">Étape {{ currentIndex + 1 }}/{{ steps.length }}</div>
            <button class="rounded-lg px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white disabled:opacity-50"
                :disabled="currentIndex >= maxAllowedIndex" @click="next">
                {{ currentIndex === steps.length - 1 ? 'Terminer' : 'Suivant' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import StepTranscrire from './steps/StepTranscrire.vue'
import StepSectionner from './steps/StepSectionner.vue'
import StepResumeST from './steps/StepResumeST.vue'
import StepPersonnaliser from './steps/StepPersonnaliser.vue'
import StepExporter from './steps/StepExporter.vue'

const steps = [
    { key: 'transcrire', title: 'Transcrire', subtitle: 'Générer la transcription', component: StepTranscrire },
    { key: 'sectionner', title: 'Sectionner', subtitle: 'Découper la vidéo', component: StepSectionner },
    { key: 'resume', title: 'Resumé ST', subtitle: 'Faire le résumé', component: StepResumeST },
    { key: 'personnaliser', title: 'Personnaliser', subtitle: 'Ajuster le résumé', component: StepPersonnaliser },
    { key: 'exporter', title: 'Exporter', subtitle: 'Exporter ou téléchargez votre taf', component: StepExporter },
]

const currentIndex = ref(0)
const currentComponent = computed(() => steps[currentIndex.value].component)

const video = ref(null)
const maxAllowedIndex = computed(() => {
    const vStep = Number(video.value?.step ?? 0)
    // Autorise jusqu'à step + 1, borné par la dernière étape
    return Math.min(vStep + 1, steps.length - 1)
})
onMounted(() => {
    try {
        const raw = localStorage.getItem('video_data')

        video.value = raw ? JSON.parse(raw) : null

        // Initialise la position courante sur la progression sauvegardée
        const initial = Number(video.value?.step ?? 0)
        currentIndex.value = Math.max(0, Math.min(initial, maxAllowedIndex.value))
    } catch (e) {
        video.value = null
    }
})

function next() {
    if (currentIndex.value < steps.length - 1 && currentIndex.value < maxAllowedIndex.value) {
        currentIndex.value++
    }
}
function prev() {
    if (currentIndex.value > 0) {
        currentIndex.value--
    }
}

function isDisabled(idx) {
    return idx > maxAllowedIndex.value
}

function goTo(idx) {
    if (!isDisabled(idx)) {
        currentIndex.value = idx
    }
}

function badgeClasses(idx) {
    const base = 'size-8 rounded-lg flex items-center justify-center text-sm font-semibold border'
    if (idx === currentIndex.value) {
        return `${base} bg-slate-600 text-white border-transparent`
    }
    if (isDisabled(idx)) {
        return `${base} bg-white text-slate-800 border-slate-200`
    }
    // Étape active possible mais non courante
    return `${base} bg-[#F9C98B] text-black border-transparent`
}
</script>
