<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../utils/request'
import LeadActivityTimeline from '../../components/LeadActivityTimeline.vue'

const route = useRoute()
const router = useRouter()
const lead = ref(null)
const loading = ref(true)
const noteContent = ref('')

const fetchLead = async () => {
  try {
    const { data } = await api.get(`/leads/${route.params.id}`)
    lead.value = data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}
const updateLead = async () => {
  try {
    await api.put(`/leads/${lead.value.id}`, {
      status: lead.value.status,
      temperature: lead.value.temperature,
    })
  } catch (e) {
    console.log(e)
  }
}
const addNote = async () => {
  if (!noteContent.value) return
  try {
    const { data } = await api.post(`/leads/${lead.value.id}/note`, { content: noteContent.value })
    lead.value.activities.unshift(data)
    noteContent.value = ''
  } catch (e) {
    console.error(e)
  }
}

const getBadgeClass = (color) => {
  const map = {
    blue: 'badge-blue',
    green: 'badge-green',
    yellow: 'badge-yellow',
    red: 'badge-red',
    gray: 'badge-slate',
  }
  return map[color] || 'badge-slate'
}
const statusBadge = computed(() => {
  if (!lead.value?.crm_config?.statuses) return 'badge-slate'
  const s = lead.value.crm_config.statuses.find((x) => x.slug === lead.value.status)
  return s ? getBadgeClass(s.color) : 'badge-slate'
})
const tempBadge = computed(() => {
  const map = { hot: 'badge-red', warm: 'badge-yellow', cold: 'badge-blue' }
  return map[lead.value?.temperature] || 'badge-slate'
})
const availableStatuses = computed(
  () =>
    lead.value?.crm_config?.statuses || [
      { slug: 'new', label: 'New' },
      { slug: 'contacted', label: 'Contacted' },
      { slug: 'closed', label: 'Closed' },
    ],
)
const formatKey = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())

onMounted(fetchLead)
</script>

<template>
  <div class="space-y-6">
    <div class="page-header">
      <div class="flex items-center gap-4">
        <button @click="router.back()" class="btn-icon">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
        </button>
        <div>
          <h2 class="page-title">Manage {{ lead?.crm_config?.entity_name_singular }}</h2>
          <p class="page-subtitle">View and manage activity.</p>
        </div>
      </div>
      <div v-if="lead" class="font-mono text-xs text-slate-500">ID: {{ lead.id }}</div>
    </div>

    <div v-if="loading" class="text-center py-12 text-slate-500">Loading...</div>

    <div v-else class="space-y-6">
      <div class="card">
        <div class="card-header">
          <span class="card-title">Details</span>
          <span class="text-xs text-slate-500">{{
            new Date(lead.created_at).toLocaleDateString()
          }}</span>
        </div>
        <div class="card-body">
          <div
            class="flex flex-wrap gap-6 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800"
          >
            <div>
              <span class="form-label mb-1">Source</span>
              <span class="badge badge-slate">{{ lead.source || 'Unknown' }}</span>
            </div>
            <div v-if="lead.form">
              <span class="form-label mb-1">Form</span>
              <span class="text-sm font-medium text-blue-600 dark:text-blue-400">{{
                lead.form.name
              }}</span>
            </div>
          </div>
          <div v-if="lead.payload" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div v-for="(value, key) in lead.payload" :key="key">
              <dt class="form-label mb-1 normal-case text-slate-500">{{ formatKey(key) }}</dt>
              <dd class="text-sm text-slate-900 dark:text-white break-words">
                {{ (Array.isArray(value) ? value.join(', ') : value) || '-' }}
              </dd>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
          <div class="card">
            <div class="card-header"><span class="card-title">Management</span></div>
            <div class="card-body space-y-4">
              <div class="form-group">
                <label class="form-label">Status</label>
                <select v-model="lead.status" @change="updateLead" class="form-select">
                  <option v-for="s in availableStatuses" :key="s.slug" :value="s.slug">
                    {{ s.label }}
                  </option>
                </select>
                <div class="mt-2">
                  <span :class="['badge', statusBadge]">{{
                    availableStatuses.find((s) => s.slug === lead.status)?.label || lead.status
                  }}</span>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Temperature</label>
                <select v-model="lead.temperature" @change="updateLead" class="form-select">
                  <option value="cold">Cold</option>
                  <option value="warm">Warm</option>
                  <option value="hot">Hot</option>
                </select>
                <div class="mt-2">
                  <span :class="['badge', tempBadge]">{{ lead.temperature }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="lg:col-span-2 space-y-6">
          <div class="card p-4">
            <textarea
              v-model="noteContent"
              class="form-textarea mb-3"
              rows="3"
              placeholder="Add a note..."
            ></textarea>
            <div class="flex justify-end">
              <button @click="addNote" :disabled="!noteContent" class="btn-primary">
                Post Note
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-1 gap-8">
            <div
              class="bg-white dark:bg-slate-950 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm"
            >
              <LeadActivityTimeline :leadId="lead.id" />
            </div>
          </div>
          <!-- <div class="card">
                        <div class="card-header">
                            <span class="card-title">Activity Feed</span>
                            <span class="text-xs text-slate-500">{{ lead.activities.length }} events</span>
                        </div>
                        <div class="divide-y divide-slate-100 dark:divide-slate-800 max-h-[500px] overflow-y-auto">
                            <div v-for="act in lead.activities" :key="act.id" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                <div class="flex justify-between mb-1">
                                    <span class="badge badge-slate uppercase tracking-wider text-[10px]">{{ act.type }}</span>
                                    <span class="text-xs text-slate-400">{{ new Date(act.created_at).toLocaleString() }}</span>
                                </div>
                                <p class="text-sm text-slate-700 dark:text-slate-300 mt-2">{{ act.content }}</p>
                            </div>
                        </div>
                    </div> -->
        </div>
      </div>
    </div>
  </div>
</template>
