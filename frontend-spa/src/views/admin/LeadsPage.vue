<script setup>
    import { onMounted, ref, reactive, watch, computed } from 'vue'
    import api from '../../utils/request'
    import { useRouter } from 'vue-router'
    
    const router = useRouter()
    const leads = ref([])
    const loading = ref(false)
    const viewMode = ref('board') // 'list' | 'board'
    
    // Default Config (Overwritten by Backend)
    const crmConfig = ref({
        entity_name_singular: 'Lead',
        entity_name_plural: 'Leads',
        statuses: [
            { slug: 'new', label: 'New', color: 'blue' },
            { slug: 'contacted', label: 'Contacted', color: 'yellow' },
            { slug: 'closed', label: 'Closed', color: 'green' }
        ]
    })
    
    // Stats
    const stats = ref({
        total: 0,
        new_today: 0,
        hot_leads: 0,
        conversion_rate: '0%'
    })
    
    const showLead = ref(false)
    const activeLead = ref(null)
    
    // Filters
    const filters = reactive({
        search: '',
        status: 'all',
        temperature: 'all',
        source: 'all',
        date_from: '',
        date_to: ''
    })
    
    // --- Dynamic Kanban Columns ---
    const kanbanColumns = computed(() => {
        const cols = {}
        
        // Initialize columns based on Config Statuses
        crmConfig.value.statuses.forEach(status => {
            cols[status.slug] = []
        })
        
        // Catch-all for deleted/unknown statuses
        cols['__other__'] = []
    
        // Sort leads into columns
        leads.value.forEach(lead => {
            if (cols[lead.status]) {
                cols[lead.status].push(lead)
            } else {
                cols['__other__'].push(lead)
            }
        })
        
        return cols
    })
    
    // Status Color Helper
    const getStatusColor = (statusSlug) => {
        const status = crmConfig.value.statuses.find(s => s.slug === statusSlug)
        const color = status ? status.color : 'gray'
        
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
    
    const fetchData = async () => {
        loading.value = true
        try {
            const statsRes = await api.get('/leads/stats')
            
            // Handle stats structure
            if (statsRes.data.stats) {
                stats.value = statsRes.data.stats
            } else {
                stats.value = statsRes.data
            }
            
            // Apply Backend Config
            if (statsRes.data.config) {
                crmConfig.value = statsRes.data.config
            }
    
            const params = { ...filters }
            Object.keys(params).forEach(key => {
                if (params[key] === '' || params[key] === 'all') delete params[key]
            })
            
            if (viewMode.value === 'board') params.per_page = 100
    
            const leadsRes = await api.get('/leads', { params })
            leads.value = leadsRes.data.data
        } catch (e) {
            console.error(e)
        } finally {
            loading.value = false
        }
    }
    
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
                                <span class="font-mono text-xs opacity-60 text-slate-900 dark:text-white">{{ new Date(lead.created_at).toLocaleDateString() }}</span>
                                <span :class="['w-3 h-3 rounded-full', lead.temperature === 'hot' ? 'bg-red-500 animate-pulse' : 'bg-slate-950']"></span>
                            </div>
                            <div class="font-medium text-sm truncate text-slate-900 dark:text-white">
                                {{ lead.payload?.email || 'No Email' }}
                            </div>
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