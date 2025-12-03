<script setup>
import { onMounted, ref, reactive, watch } from 'vue'
import api from '../../utils/request'
import { useRouter } from 'vue-router'

const router = useRouter()
const leads = ref([])
const loading = ref(false)

// Lead Modal State
const showLead = ref(false)
const activeLead = ref(null)

// Webhook Modal State
const showWebhooks = ref(false)
const webhooks = ref([])
const loadingWebhooks = ref(false)
const webhookForm = reactive({ name: '', url: '', secret: '', events: [] })

// --- Filter State ---
const filters = reactive({
    search: '',
    status: 'all',
    temperature: 'all',
    source: 'all',
    date_from: '',
    date_to: ''
})

// Debounce timer for search
let searchTimer = null

const availableEvents = [
  { label: 'Lead Created', value: 'lead.created' },
  { label: 'Status Changed', value: 'lead.updated.status' },
  { label: 'Temperature Changed', value: 'lead.updated.temperature' },
  { label: 'Any Update', value: 'lead.updated' }
]

// --- Leads Logic ---
const fetchLeads = async () => {
  loading.value = true
  try {
    // Build query params from filters
    const params = { ...filters }
    // Remove empty keys to keep URL clean
    Object.keys(params).forEach(key => {
        if (params[key] === '' || params[key] === 'all') delete params[key]
    })

    const { data } = await api.get('/leads', { params })
    leads.value = data.data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// Watch filters for changes
watch(filters, (newVal, oldVal) => {
    // If only search changed, debounce it
    if (newVal.search !== oldVal.search) {
        clearTimeout(searchTimer)
        searchTimer = setTimeout(() => {
            fetchLeads()
        }, 500) // 500ms delay for typing
    } else {
        // Immediate fetch for dropdowns/dates
        fetchLeads()
    }
}, { deep: true })

const clearFilters = () => {
    filters.search = ''
    filters.status = 'all'
    filters.temperature = 'all'
    filters.source = 'all'
    filters.date_from = ''
    filters.date_to = ''
    // fetchLeads will trigger via watch
}

const viewLead = (lead) => {
  activeLead.value = lead
  showLead.value = true
}

const closeLead = () => {
  showLead.value = false
  setTimeout(() => { activeLead.value = null }, 200)
}

const getStatusColor = (status) => {
    const map = { new: 'bg-blue-100 text-blue-700', contacted: 'bg-yellow-100 text-yellow-700', closed: 'bg-green-100 text-green-700' }
    return map[status] || 'bg-gray-100 text-gray-600'
}

const getTempColor = (temp) => {
    const map = { cold: 'text-blue-500', warm: 'text-orange-500', hot: 'text-red-600 font-bold' }
    return map[temp] || 'text-gray-500'
}

const openLead = (lead) => {
    router.push({ name: 'lead-details', params: { id: lead.id } })
}

// --- Webhooks Logic (Preserved) ---
const openWebhooks = async () => { showWebhooks.value = true; await fetchWebhooks() }
const closeWebhooks = () => { showWebhooks.value = false; webhookForm.name = ''; webhookForm.url = ''; webhookForm.secret = ''; webhookForm.events = [] }
const fetchWebhooks = async () => { 
    loadingWebhooks.value = true
    try { const { data } = await api.get('/webhooks'); webhooks.value = data } 
    catch (e) { console.error(e) } 
    finally { loadingWebhooks.value = false }
}
const createWebhook = async () => {
    if (!webhookForm.url || webhookForm.events.length === 0) return
    try { await api.post('/webhooks', webhookForm); closeWebhooks(); await fetchWebhooks(); showWebhooks.value = true; } // simplistic reset
    catch (e) { alert('Failed'); console.error(e) }
}
const deleteWebhook = async (id) => {
    if(!confirm('Are you sure?')) return
    try { await api.delete(`/webhooks/${id}`); await fetchWebhooks() } catch (e) { console.error(e) }
}

onMounted(fetchLeads)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Leads</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage form submissions.</p>
      </div>
      <div class="flex items-center gap-3">
        <button @click="openWebhooks" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
          <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
          Webhooks
        </button>
        <button @click="fetchLeads" :disabled="loading" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-100 transition-colors shadow-sm">
          <span v-if="loading">Loading...</span>
          <span v-else>Refresh</span>
        </button>
      </div>
    </div>

    <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
            <input 
                v-model="filters.search" 
                type="text" 
                placeholder="Search by ID, Email, Source, or Content..." 
                class="block w-full pl-10 pr-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg leading-5 bg-white dark:bg-slate-900 placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm"
            >
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select v-model="filters.status" class="block w-full py-1.5 px-2 text-sm border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-900 dark:text-slate-200">
                    <option value="all">All Statuses</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Temperature</label>
                <select v-model="filters.temperature" class="block w-full py-1.5 px-2 text-sm border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-900 dark:text-slate-200">
                    <option value="all">All Temps</option>
                    <option value="cold">Cold</option>
                    <option value="warm">Warm</option>
                    <option value="hot">Hot</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Source</label>
                <select v-model="filters.source" class="block w-full py-1.5 px-2 text-sm border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-900 dark:text-slate-200">
                    <option value="all">All Sources</option>
                    <option value="form">Form Submission</option>
                    <option value="csv">CSV Import</option>
                    <option value="manual">Manual Entry</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">From Date</label>
                <input v-model="filters.date_from" type="date" class="block w-full py-1.5 px-2 text-sm border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-900 dark:text-slate-200">
            </div>

            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-500 mb-1">To Date</label>
                    <input v-model="filters.date_to" type="date" class="block w-full py-1.5 px-2 text-sm border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-900 dark:text-slate-200">
                </div>
                <button @click="clearFilters" title="Clear Filters" class="mb-[1px] p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors border border-transparent hover:border-slate-200 dark:hover:border-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800">
            <tr>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">ID</th>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Lead</th>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Status</th>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Temp</th>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Date</th>
              <th class="px-6 py-4 text-right font-semibold text-slate-700 dark:text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors">
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-mono text-xs">{{ lead.id }}</td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                <div class="font-medium">{{ lead.payload?.email || 'No Email' }}</div>
                <div class="text-xs text-slate-500">{{ lead.source }} {{ lead.source === 'form' ? '( ' + lead.form_id + ' )' : '' }}</div>
              </td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                <span :class="['px-2 py-1 rounded-full text-xs capitalize', getStatusColor(lead.status)]">
                 {{ lead.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                <span :class="['text-xs capitalize flex items-center gap-1', getTempColor(lead.temperature)]">
                 ● {{ lead.temperature }}
                </span>
              </td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                {{ new Date(lead.created_at).toLocaleString() }}
              </td>
              <td class="px-6 py-4 text-right">
                <button 
                  class="text-sm font-medium text-primary hover:text-primary/80 transition-colors"
                  @click="viewLead(lead)"
                >
                Quick View
              </button>
              <button @click="openLead(lead)" class="ml-3 text-blue-500 hover:underline">Open</button>
              </td>
            </tr>
            <tr v-if="leads.length === 0 && !loading">
              <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                No leads found matching your filters.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Transition name="modal">
      <div v-if="showLead" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeLead"></div>
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-950 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh] transform transition-all">
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Submission Details</h3>
            <button @click="closeLead" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-500 dark:hover:bg-slate-900 transition-colors">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>
          <div class="overflow-y-auto p-6">
            <div v-if="activeLead" class="space-y-6">
              <div class="grid grid-cols-2 gap-4 rounded-lg bg-slate-50 dark:bg-slate-900 p-4 border border-slate-100 dark:border-slate-800">
                 <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lead ID</span>
                    <p class="mt-1 text-sm font-mono text-slate-700 dark:text-slate-300">{{ activeLead.id }}</p>
                 </div>
                 <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Submitted</span>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ new Date(activeLead.created_at).toLocaleString() }}</p>
                 </div>
              </div>
              <div>
                <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-3">Form Data</h4>
                <div class="space-y-3">
                  <div v-for="(value, key) in activeLead.payload" :key="key" class="group rounded-lg border border-slate-200 dark:border-slate-800 p-3 hover:border-primary/50 transition-colors">
                    <dt class="text-xs font-medium text-slate-500 uppercase mb-1.5">{{ key }}</dt>
                    <dd class="text-sm text-slate-800 dark:text-slate-200 break-all leading-relaxed">
                      {{ typeof value === 'object' ? JSON.stringify(value, null, 2) : value }}
                    </dd>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="border-t border-slate-100 dark:border-slate-800 px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 rounded-b-xl">
            <button @click="closeLead" class="w-full inline-flex justify-center items-center px-4 py-2 border border-slate-300 dark:border-slate-700 shadow-sm text-sm font-medium rounded-lg text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <Transition name="modal">
      <div v-if="showWebhooks" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeWebhooks"></div>
        <div class="relative w-full max-w-3xl bg-white dark:bg-slate-950 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh] transform transition-all">
          
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Webhook Manager</h3>
            <button @click="closeWebhooks" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-500 dark:hover:bg-slate-900 transition-colors">
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-6 space-y-8">
            
            <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-100 dark:border-slate-800">
                <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-4">Add New Webhook</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="col-span-2 md:col-span-1">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Name (Optional)</label>
                        <input v-model="webhookForm.name" type="text" placeholder="e.g. N8N Status Sync" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm py-2">
                    </div>
                     <div class="col-span-2 md:col-span-1">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Secret (Optional)</label>
                        <input v-model="webhookForm.secret" type="text" placeholder="Signing Secret" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm py-2">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-500 mb-1">Payload URL <span class="text-red-500">*</span></label>
                        <input v-model="webhookForm.url" type="url" placeholder="https://api.example.com/webhook" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-sm py-2">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="block text-xs font-medium text-slate-500 mb-2">Trigger Events <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-3">
                        <label v-for="evt in availableEvents" :key="evt.value" class="inline-flex items-center gap-2 cursor-pointer bg-white dark:bg-slate-800 px-3 py-1.5 rounded border border-slate-200 dark:border-slate-700">
                            <input type="checkbox" :value="evt.value" v-model="webhookForm.events" class="rounded border-slate-300 text-primary focus:ring-primary">
                            <span class="text-sm text-slate-700 dark:text-slate-300">{{ evt.label }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-4 flex justify-end">
                    <button 
                        @click="createWebhook" 
                        :disabled="!webhookForm.url || webhookForm.events.length === 0"
                        class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors disabled:opacity-50"
                    >
                        Create Webhook
                    </button>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-medium text-slate-900 dark:text-white mb-3">Active Webhooks</h4>
                <div v-if="loadingWebhooks" class="text-sm text-slate-500">Loading webhooks...</div>
                <div v-else-if="webhooks.length === 0" class="text-sm text-slate-500 italic">No webhooks configured.</div>
                
                <div v-else class="space-y-3">
                    <div v-for="hook in webhooks" :key="hook.id" class="flex items-start justify-between bg-white dark:bg-slate-900 p-4 rounded-lg border border-slate-200 dark:border-slate-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-slate-900 dark:text-slate-100 text-sm">{{ hook.name || 'Unnamed Webhook' }}</span>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">Active</span>
                            </div>
                            <div class="text-xs text-slate-500 font-mono mt-1 break-all">{{ hook.url }}</div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="ev in hook.events" :key="ev" class="text-[10px] uppercase tracking-wide bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                                    {{ ev }}
                                </span>
                            </div>
                        </div>
                        <button @click="deleteWebhook(hook.id)" class="text-red-500 hover:text-red-700 text-xs font-medium px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>

          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style>