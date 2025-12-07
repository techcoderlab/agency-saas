<script setup>
import { onMounted, ref, reactive, watch, computed } from 'vue'
import api from '../../utils/request'
import { useRouter } from 'vue-router'

const router = useRouter()
const leads = ref([])
const loading = ref(false)
const viewMode = ref('board') // 'list' | 'board'

// --- Dynamic Configuration State ---
const crmConfig = ref({
    entity_name_singular: 'Lead',
    entity_name_plural: 'Leads',
    statuses: [
        { slug: 'new', label: 'New', color: 'blue' },
        { slug: 'contacted', label: 'Contacted', color: 'yellow' },
        { slug: 'closed', label: 'Closed', color: 'green' }
    ]
})

// --- Stats State ---
const stats = ref({
    total: 0,
    new_today: 0,
    hot_leads: 0,
    conversion_rate: '0%'
})

// Modals State
const showLead = ref(false)
const activeLead = ref(null)
// const showWebhooks = ref(false)
const showSettings = ref(false) // CRM Builder Modal

// Webhook Form
// const webhooks = ref([])
// const loadingWebhooks = ref(false)
// const webhookForm = reactive({ name: '', url: '', secret: '', events: [] })

// Settings Form
const settingsForm = reactive({
    entity_name_singular: '',
    entity_name_plural: '',
    statuses: []
})

// --- Filter State ---
const filters = reactive({
    search: '',
    status: 'all',
    temperature: 'all',
    source: 'all',
    date_from: '',
    date_to: ''
})

// --- Computed Properties for Dynamic Kanban ---
const kanbanColumns = computed(() => {
    const cols = {}
    
    // 1. Init columns from config
    crmConfig.value.statuses.forEach(status => {
        cols[status.slug] = []
    })
    
    // 2. Add catch-all for unknown statuses (safety net)
    cols['__other__'] = []

    // 3. Sort leads
    leads.value.forEach(lead => {
        if (cols[lead.status]) {
            cols[lead.status].push(lead)
        } else {
            cols['__other__'].push(lead)
        }
    })
    
    return cols
})

// Helper to get color for a status slug
const getStatusColor = (statusSlug) => {
    const status = crmConfig.value.statuses.find(s => s.slug === statusSlug)
    const color = status ? status.color : 'gray'
    
    // Map simplified color names to Tailwind classes
    const colorMap = {
        blue: 'bg-blue-100 text-blue-700 border-blue-200',
        green: 'bg-green-100 text-green-700 border-green-200',
        yellow: 'bg-yellow-100 text-yellow-700 border-yellow-200',
        red: 'bg-red-100 text-red-700 border-red-200',
        purple: 'bg-purple-100 text-purple-700 border-purple-200',
        gray: 'bg-slate-100 text-slate-600 border-slate-200'
    }

    return colorMap[color] || colorMap['gray']
}

// --- N8N Automation Trigger ---
const triggerAutomation = async (actionName) => {
    if(!confirm(`Run automation: ${actionName}?`)) return
    
    try {
        const updatedPayload = { 
            ...activeLead.value.payload, 
            _trigger_action: actionName,
            _trigger_time: new Date().toISOString()
        }
        
        await api.put(`/leads/${activeLead.value.id}`, {
            status: activeLead.value.status, 
            temperature: activeLead.value.temperature,
            payload: updatedPayload
        })
        
        alert(`Automation "${actionName}" triggered!`)
        closeLead()
        fetchData()
    } catch (e) {
        alert('Error triggering automation')
    }
}

// --- Data Fetching ---
const fetchData = async () => {
    loading.value = true
    try {
        // Fetch Stats AND Config together (Assuming backend injects config in stats response)
        const statsRes = await api.get('/leads/stats')
        if (statsRes.data.stats) {
            stats.value = statsRes.data.stats
        } else {
            // Fallback for older backend
            stats.value = statsRes.data
        }
        
        if (statsRes.data.config) {
            crmConfig.value = statsRes.data.config
        }

        // Fetch Leads
        const params = { ...filters }
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === 'all') delete params[key]
        })
        
        // Optimization for board view
        if (viewMode.value === 'board') params.per_page = 100

        const leadsRes = await api.get('/leads', { params })
        leads.value = leadsRes.data.data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

// Watchers
watch(viewMode, fetchData)
let searchTimer = null
watch(filters, (newVal, oldVal) => {
    if (newVal.search !== oldVal.search) {
        clearTimeout(searchTimer)
        searchTimer = setTimeout(fetchData, 500)
    } else {
        fetchData()
    }
}, { deep: true })


// --- Settings Logic ---
const openSettings = () => {
    // Clone current config into form
    settingsForm.entity_name_singular = crmConfig.value.entity_name_singular
    settingsForm.entity_name_plural = crmConfig.value.entity_name_plural
    // Deep copy statuses to avoid reference issues
    settingsForm.statuses = JSON.parse(JSON.stringify(crmConfig.value.statuses))
    showSettings.value = true
}

const addStatus = () => {
    settingsForm.statuses.push({ slug: '', label: '', color: 'gray' })
}

const removeStatus = (index) => {
    settingsForm.statuses.splice(index, 1)
}

const saveSettings = async () => {
    try {
        // Auto-generate slugs if empty
        settingsForm.statuses.forEach(s => {
            if(!s.slug) s.slug = s.label.toLowerCase().replace(/\s+/g, '_')
        })

        const { data } = await api.post('/tenants/crm-config', settingsForm)
        crmConfig.value = data
        showSettings.value = false
        fetchData() // Refresh to apply changes
    } catch (e) {
        alert('Failed to save settings. Ensure all fields are filled.')
    }
}


// --- Standard Helpers ---
const viewLead = (lead) => { activeLead.value = lead; showLead.value = true }
const closeLead = () => { showLead.value = false; setTimeout(() => activeLead.value = null, 200) }
const getTempColor = (temp) => {
    const map = { cold: 'text-blue-500', warm: 'text-orange-500', hot: 'text-red-600 font-bold' }
    return map[temp] || 'text-gray-500'
}
const clearFilters = () => {
    filters.search = ''; filters.status = 'all'; filters.temperature = 'all';
    filters.source = 'all'; filters.date_from = ''; filters.date_to = ''
}
const openLead = (lead) => {
    router.push({ name: 'lead-details', params: { id: lead.id } })
}

// Webhook logic (preserved)
// const openWebhooks = async () => { showWebhooks.value = true; try { const { data } = await api.get('/webhooks'); webhooks.value = data } catch (e) {} }
// const closeWebhooks = () => { showWebhooks.value = false }
// const createWebhook = async () => { try { await api.post('/webhooks', webhookForm); webhookForm.url=''; webhookForm.events=[]; await api.get('/webhooks').then(r => webhooks.value = r.data) } catch (e) {} }
// const deleteWebhook = async (id) => { if(confirm('Sure?')) { await api.delete(`/webhooks/${id}`); await api.get('/webhooks').then(r => webhooks.value = r.data) } }
// const availableEvents = [
//   { label: 'Created', value: 'lead.created' }, { label: 'Status Update', value: 'lead.updated.status' },
//   { label: 'Temp Update', value: 'lead.updated.temperature' }, { label: 'Any Update', value: 'lead.updated' }
// ]

onMounted(fetchData)
</script>

<template>
  <div class="space-y-6">
    
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total {{ crmConfig.entity_name_plural }}</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total }}</span>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">New Today</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.new_today }}</span>
                <span v-if="stats.new_today > 0" class="text-xs font-medium text-green-600 bg-green-100 px-1.5 py-0.5 rounded-full">Active</span>
            </div>
        </div>
         <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Hot {{ crmConfig.entity_name_plural }}</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.hot_leads }}</span>
                <span class="text-xs text-slate-400">High Priority</span>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Conversion</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.conversion_rate }}</span>
            </div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ crmConfig.entity_name_plural }} Pipeline</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage {{ crmConfig.entity_name_plural.toLowerCase() }} and track progress.</p>
      </div>
      
      <div class="flex items-center gap-3">
        <div class="bg-slate-100 dark:bg-slate-800 p-1 rounded-lg flex items-center">
             <button @click="viewMode = 'list'" title="List View" :class="['px-3 py-1.5 text-sm font-medium rounded-md transition-all', viewMode === 'list' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500']">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
             </button>
             <button @click="viewMode = 'board'" title="Kanban View" :class="['px-3 py-1.5 text-sm font-medium rounded-md transition-all', viewMode === 'board' ? 'bg-white dark:bg-slate-700 shadow-sm' : 'text-slate-500']">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" /></svg>
             </button>
        </div>

        <button 
          @click="openSettings" 
          class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
        >
           <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
           Config
        </button>

        <button @click="fetchData" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium dark:text-slate-200 bg-white dark:bg-slate-950 border dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
        </button>

      </div>
    </div>

    <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
         <div class="relative">
             <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </div>
             <input v-model="filters.search" type="text" placeholder="Search by ID, Email, Source..." class="block w-full pl-10 pr-3 py-2 border rounded-lg dark:bg-slate-900 dark:border-slate-700">
         </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
                <select v-model="filters.status" class="block w-full py-1.5 px-2 text-sm border border-slate-300 dark:border-slate-700 rounded-lg dark:bg-slate-900 dark:text-slate-200">
                    <option value="all">All Statuses</option>
                    <option v-for="status in crmConfig.statuses" :key="status.slug" :value="status.slug">
                        {{ status.label }}
                    </option>
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

    <div v-if="viewMode === 'board'" class="overflow-x-auto pb-4">
        <div class="flex gap-6 min-w-full">
            <div 
                v-for="status in crmConfig.statuses" 
                :key="status.slug" 
                class="min-w-[300px] w-[300px] bg-slate-50 dark:bg-slate-900/30 rounded-xl p-4 border border-slate-200 dark:border-slate-800 flex flex-col"
            >
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-bold text-slate-700 dark:text-slate-200">{{ status.label }}</h3>
                    <span class="text-xs font-bold px-2 py-1 rounded-full bg-slate-200 " :class="getStatusColor(status.slug).split(' ')[1]">
                        {{ kanbanColumns[status.slug]?.length || 0 }}
                    </span>
                </div>
                
                <div class="space-y-3 flex-1 overflow-y-auto max-h-[70vh]">
                    <div 
                        v-for="lead in kanbanColumns[status.slug]" 
                        :key="lead.id"
                        @click="openLead(lead)"
                        class="bg-white dark:bg-slate-950 px-4 py-3 rounded-lg shadow-sm border border-l-4 cursor-pointer hover:shadow-md transition-all"
                        :class="getStatusColor(status.slug)"
                    >
                        <div class="flex justify-between items-start mb-2">
                            <!-- <span class="font-mono text-xs opacity-60">#{{ lead.id }}</span> -->
                            <span class="font-mono text-xs opacity-60 text-slate-900 dark:text-white">{{ new Date(lead.created_at).toLocaleDateString() }}</span>
                            <span :class="['w-3 h-3 rounded-full', lead.temperature === 'hot' ? 'bg-red-500 animate-pulse' : 'bg-slate-950']"></span>
                        </div>
                        <div class="font-medium text-sm truncate text-slate-900 dark:text-white">
                            {{ lead.payload?.email || 'No Email' }}
                        </div>
                         <!-- <div class="mt-2 flex items-center justify-between text-xs opacity-70">
                            <span>{{ new Date(lead.created_at).toLocaleDateString() }}</span>
                        </div> -->
                    </div>
                </div>
            </div>

            <div v-if="kanbanColumns['__other__'] && kanbanColumns['__other__'].length > 0" class="min-w-[300px] bg-red-50 dark:bg-red-900/10 rounded-xl p-4 border border-red-200">
                <h3 class="font-bold text-red-700 mb-4">Unmapped</h3>
                 <div class="space-y-3">
                    <div v-for="lead in kanbanColumns['__other__']" :key="lead.id" @click="viewLead(lead)" class="bg-white p-4 rounded-lg border border-red-200 cursor-pointer">
                         <div class="text-xs text-red-500 font-bold mb-1">Status: {{ lead.status }}</div>
                         <div class="font-medium text-sm">{{ lead.payload?.email }}</div>
                    </div>
                 </div>
            </div>
        </div>
    </div>

    <div v-else class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 dark:bg-slate-900/50">
            <tr>
              <th class="px-6 py-4">ID</th>
              <th class="px-6 py-4">{{ crmConfig.entity_name_singular }}</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Temp</th>
              <th class="px-6 py-4">Date</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="lead in leads" :key="lead.id" class="">
               <td class="px-6 py-4 font-mono text-xs">{{ lead.id }}</td>
               <td class="px-6 py-4">{{ lead.payload?.email || 'No Email' }}</td>
               <td class="px-6 py-4">
                 <span class="px-2 py-1 rounded-full text-xs border" :class="getStatusColor(lead.status)">
                    {{ crmConfig.statuses.find(s => s.slug === lead.status)?.label || lead.status }}
                 </span>
               </td>
               <td class="px-6 py-4"><span :class="getTempColor(lead.temperature)">● {{ lead.temperature }}</span></td>
               <td class="px-6 py-4">{{ new Date(lead.created_at).toLocaleDateString() }}</td>
               <td class="px-6 py-4 text-right">
                <!-- <button @click="viewLead(lead)" class="text-blue-600 hover:underline">View</button> -->
                <button @click="openLead(lead)" class="ml-3 text-blue-500 hover:underline">Open</button>
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
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ crmConfig.entity_name_singular }} ID</span>
                    <p class="mt-1 text-sm font-mono text-slate-700 dark:text-slate-300">{{ activeLead.id }}</p>
                 </div>
                 <div>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Submitted</span>
                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-300">{{ new Date(activeLead.created_at).toLocaleString() }}</p>
                 </div>
              </div>

              <!-- <div class="mt-6">
                <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">Automation Actions</h4>
                <div class="flex flex-wrap gap-2">
                    <button @click="triggerAutomation('send_whatsapp_reminder')" class="px-3 py-1.5 bg-green-50 text-green-700 border border-green-200 rounded-md text-xs font-medium hover:bg-green-100 transition-colors">📲 Send WhatsApp</button>
                    <button @click="triggerAutomation('resend_verification')" class="px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-md text-xs font-medium hover:bg-blue-100 transition-colors">🔄 Resend OTP</button>
                    <button @click="triggerAutomation('generate_invoice')" class="px-3 py-1.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-md text-xs font-medium hover:bg-purple-100 transition-colors">📄 Gen Invoice</button>
                </div>
              </div> -->

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
      <div v-if="showSettings" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showSettings = false"></div>
        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-950 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh]">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">CRM Configuration</h3>
            </div>
            <div class="p-6 overflow-y-auto space-y-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Singular Name</label>
                        <input v-model="settingsForm.entity_name_singular" type="text" placeholder="e.g. Order" class="w-full border rounded p-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Plural Name</label>
                        <input v-model="settingsForm.entity_name_plural" type="text" placeholder="e.g. Orders" class="w-full border rounded p-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-500">Pipeline Stages (Statuses)</label>
                        <button @click="addStatus" class="text-xs bg-blue-50 text-blue-600 px-2 py-1 rounded hover:bg-blue-100 font-medium">+ Add Stage</button>
                    </div>
                    <div class="space-y-2">
                        <div v-for="(status, index) in settingsForm.statuses" :key="index" class="flex items-center gap-2">
                            <input v-model="status.label" type="text" placeholder="Label" class="flex-1 border rounded p-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                            <select v-model="status.color" class="border rounded p-2 text-sm w-32 dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                                <option value="gray">Gray</option>
                                <option value="blue">Blue</option>
                                <option value="green">Green</option>
                                <option value="yellow">Yellow</option>
                                <option value="red">Red</option>
                                <option value="purple">Purple</option>
                            </select>
                            <input v-model="status.slug" type="text" placeholder="slug_id" class="w-32 border rounded p-2 text-sm font-mono bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400" title="System ID (Slug)">
                            <button @click="removeStatus(index)" class="text-red-500 hover:bg-red-50 p-2 rounded transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 rounded-b-xl flex justify-end gap-3">
                <button @click="showSettings = false" class="text-slate-500 text-sm font-medium hover:text-slate-700 px-4 py-2">Cancel</button>
                <button @click="saveSettings" class="bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-800 transition-colors">Save Configuration</button>
            </div>
        </div>
      </div>
    </Transition>

    <!-- <Transition name="modal">
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
                    <button @click="createWebhook" :disabled="!webhookForm.url || webhookForm.events.length === 0" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-md transition-colors disabled:opacity-50">Create Webhook</button>
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
                        <button @click="deleteWebhook(hook.id)" class="text-red-500 hover:text-red-700 text-xs font-medium px-2 py-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">Delete</button>
                    </div>
                </div>
            </div>
          </div>
        </div>
      </div>
    </Transition> -->
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