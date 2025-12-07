<script setup>
  import { onMounted, ref } from 'vue'
  import api from '../../utils/request'
  import { useApiCache } from '../../composables/useApiCache'; 
  
  const tenants = ref([])
  const loading = ref(false)
  const saving = ref(false)
  const { fetchDataWithCache } = useApiCache();
  
  const showForm = ref(false)
  const editingTenant = ref(null)
  
  // Exact structure required by backend
  const defaultCrmConfig = {
      entity_name_singular: 'Lead',
      entity_name_plural: 'Leads',
      statuses: [
          { slug: 'new', label: 'New', color: 'blue' },
          { slug: 'contacted', label: 'Contacted', color: 'yellow' },
          { slug: 'closed', label: 'Closed', color: 'green' }
      ]
  }
  
  const form = ref({
    name: '',
    domain: '',
    status: 'active',
    enabled_modules: [],
    crm_config: JSON.parse(JSON.stringify(defaultCrmConfig))
  })
  
  const availableModules = ref([]);
  const error = ref('')
  
  const resetForm = () => {
    form.value = {
      name: '',
      domain: '',
      status: 'active',
      enabled_modules: [],
      crm_config: JSON.parse(JSON.stringify(defaultCrmConfig))
    }
    editingTenant.value = null
    error.value = ''
  }
  
  const fetchModules = async () => {
    const data = await fetchDataWithCache(
        'tenant_modules_cache',
        600000, 
        () => api.get('/tenants/modules')
      );
      availableModules.value = data.filter(item => item.id !== 'tenants')
  }
  
  const fetchTenants = async () => {
    loading.value = true
    const { data } = await api.get('/tenants')
    tenants.value = data.tenants
    await fetchModules()
    loading.value = false
  }
  
  const openCreate = () => {
    resetForm()
    showForm.value = true
  }
  
  const openEdit = (tenant) => {
    editingTenant.value = tenant
    
    // Load existing config or fallback to default structure
    let existingConfig = null
    if (tenant.settings && tenant.settings.crm_config) {
        existingConfig = tenant.settings.crm_config
    }
  
    form.value = {
      name: tenant.name || '',
      domain: tenant.domain || '',
      status: tenant.status || 'active',
      enabled_modules: tenant.enabled_modules || [],
      crm_config: existingConfig || JSON.parse(JSON.stringify(defaultCrmConfig))
    }
    error.value = ''
    showForm.value = true
  }
  
  // Helper to manage status array
  const addStatus = () => {
      form.value.crm_config.statuses.push({ slug: '', label: '', color: 'gray' })
  }
  const removeStatus = (index) => {
      form.value.crm_config.statuses.splice(index, 1)
  }
  
  const saveTenant = async () => {
    saving.value = true
    error.value = ''
  
    try {
      // 1. Auto-generate slugs if user left them empty
      if (form.value.enabled_modules.includes('leads')) {
           form.value.crm_config.statuses.forEach(s => {
              if(!s.slug && s.label) {
                  s.slug = s.label.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '')
              }
          })
      }
  
      // 2. Construct Payload
      const payload = {
        name: form.value.name,
        domain: form.value.domain || null,
        status: form.value.status,
        enabled_modules: form.value.enabled_modules,
        // Only send crm_config if the module is enabled
        crm_config: form.value.enabled_modules.includes('leads') ? form.value.crm_config : null
      }
  
      let response
      if (editingTenant.value) {
        response = await api.patch(`/tenants/${editingTenant.value.id}`, payload)
      } else {
        response = await api.post('/tenants', payload)
      }
  
      const saved = response.data
      if (editingTenant.value) {
        const idx = tenants.value.findIndex((t) => t.id === saved.id)
        if (idx !== -1) tenants.value[idx] = saved
      } else {
        tenants.value.unshift(saved)
      }
  
      fetchTenants() 
      showForm.value = false
      resetForm()
    } catch (e) {
      console.error(e)
      error.value = e.response?.data?.message || 'Failed to save tenant. Please check inputs.'
    } finally {
      saving.value = false
    }
  }
  
  const deleteTenant = async (tenant) => {
    if (!confirm(`Delete tenant "${tenant.name}"? This cannot be undone.`)) return
    await api.delete(`/tenants/${tenant.id}`)
    tenants.value = tenants.value.filter((t) => t.id !== tenant.id)
  }
  
  const toggleStatus = async (tenant) => {
    const { data } = await api.patch(`/tenants/${tenant.id}`, {
      status: tenant.status === 'active' ? 'suspended' : 'active',
    })
    Object.assign(tenant, data)
  }
  
  onMounted(fetchTenants)
  </script>
  
  <template>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Tenants</h2>
        <button
          class="px-3 py-2 text-sm rounded-lg bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 transition-colors font-medium"
          @click="openCreate"
        >
          + Create Tenant
        </button>
      </div>
  
      <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showForm = false"></div>
        <div class="relative w-full max-w-4xl bg-white dark:bg-slate-950 shadow-2xl rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh]">
          
          <div class="p-6 border-b border-slate-100 dark:border-slate-800">
              <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                  {{ editingTenant ? 'Edit Tenant' : 'New Tenant' }}
              </h3>
          </div>
  
          <div class="p-6 overflow-y-auto space-y-8">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                  <div class="space-y-6">
                      <div class="space-y-4">
                          <div>
                              <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Name</label>
                              <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-500 outline-none" placeholder="Company Name" />
                          </div>
                          <div>
                              <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Domain</label>
                              <input v-model="form.domain" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-500 outline-none" placeholder="subdomain (optional)" />
                          </div>
                          <div>
                              <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Status</label>
                              <select v-model="form.status" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-500 outline-none">
                                  <option value="active">Active</option>
                                  <option value="suspended">Suspended</option>
                              </select>
                          </div>
                      </div>
  
                      <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-lg border border-slate-100 dark:border-slate-800">
                          <label class="block text-xs font-bold text-slate-500 uppercase mb-3">Enabled Modules</label>
                          <div class="space-y-2">
                              <div v-for="mod in availableModules" :key="mod.id" class="flex items-center">
                                  <input type="checkbox" :id="`mod-${mod.id}`" :value="mod.id" v-model="form.enabled_modules" 
                                      class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900 dark:border-slate-600 dark:bg-slate-800">
                                  <label :for="`mod-${mod.id}`" class="ml-2 text-sm text-slate-700 dark:text-slate-300 font-medium cursor-pointer">{{ mod.label }}</label>
                              </div>
                          </div>
                      </div>
                  </div>
  
                  <div class="space-y-6">
                      <div v-if="form.enabled_modules.includes('leads')" class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-5 shadow-sm">
                          <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-4 border-b border-slate-100 dark:border-slate-800 pb-2">
                              CRM Pipeline Settings
                          </h4>
                          
                          <div class="grid grid-cols-2 gap-4 mb-4">
                              <div>
                                  <label class="block text-xs font-bold text-slate-500 mb-1">Singular Name</label>
                                  <input v-model="form.crm_config.entity_name_singular" type="text" placeholder="e.g. Lead" class="w-full border rounded px-2 py-1.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                              </div>
                              <div>
                                  <label class="block text-xs font-bold text-slate-500 mb-1">Plural Name</label>
                                  <input v-model="form.crm_config.entity_name_plural" type="text" placeholder="e.g. Leads" class="w-full border rounded px-2 py-1.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                              </div>
                          </div>
  
                          <div>
                              <div class="flex items-center justify-between mb-2">
                                  <label class="block text-xs font-bold text-slate-500">Pipeline Stages</label>
                                  <button @click="addStatus" class="text-xs text-blue-600 hover:text-blue-800 font-bold">+ Add Stage</button>
                              </div>
                              <div class="space-y-2 max-h-[300px] overflow-y-auto pr-1 custom-scrollbar">
                                  <div v-for="(status, index) in form.crm_config.statuses" :key="index" class="flex items-center gap-2">
                                      <input v-model="status.label" type="text" placeholder="Label" class="flex-1 border rounded px-2 py-1.5 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                                      
                                      <select v-model="status.color" class="border rounded px-2 py-1.5 text-sm w-24 dark:bg-slate-800 dark:border-slate-700 dark:text-white">
                                          <option value="gray">Gray</option>
                                          <option value="blue">Blue</option>
                                          <option value="green">Green</option>
                                          <option value="yellow">Yellow</option>
                                          <option value="red">Red</option>
                                          <option value="purple">Purple</option>
                                      </select>
                                      
                                      <input v-model="status.slug" type="text" placeholder="slug_id" class="w-24 border rounded px-2 py-1.5 text-sm font-mono bg-slate-50 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-400" title="System ID (Slug)">
                                      
                                      <button @click="removeStatus(index)" class="text-slate-400 hover:text-red-500 p-1">
                                          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                      </button>
                                  </div>
                              </div>
                          </div>
                      </div>
                      
                      <div v-else class="h-full flex flex-col items-center justify-center text-slate-400 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-lg p-8">
                          <svg class="w-10 h-10 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                          <p class="text-sm font-medium">Select 'CRM' to configure pipeline settings.</p>
                      </div>
                  </div>
              </div>
  
              <div v-if="error" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-3 rounded text-sm text-center">
                  {{ error }}
              </div>
          </div>
  
          <div class="flex justify-end gap-3 p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 rounded-b-xl">
              <button class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg dark:text-slate-300 dark:hover:bg-slate-800 transition-colors" @click="showForm = false">Cancel</button>
              <button class="px-4 py-2 text-sm font-medium text-white bg-slate-900 hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 rounded-lg shadow-sm transition-colors" :disabled="saving" @click="saveTenant">
                  {{ saving ? 'Saving...' : 'Save Tenant' }}
              </button>
          </div>
        </div>
      </div>
  
      <div v-if="loading" class="text-center py-12 text-slate-500">Loading tenants...</div>
      <div v-else class="bg-white dark:bg-slate-900 shadow-sm rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
          <div class="overflow-x-auto">
              <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 border-b border-slate-200 dark:border-slate-800">
                  <tr>
                  <th class="px-6 py-4 font-semibold uppercase text-xs">Name</th>
                  <th class="px-6 py-4 font-semibold uppercase text-xs">Domain</th>
                  <th class="px-6 py-4 font-semibold uppercase text-xs">Modules</th>
                  <th class="px-6 py-4 font-semibold uppercase text-xs">Status</th>
                  <th class="px-6 py-4" />
                  </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                  <tr
                  v-for="tenant in tenants"
                  :key="tenant.id"
                  class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors"
                  >
                  <td class="px-6 py-4 font-medium text-slate-900 dark:text-white">
                      {{ tenant.name }}
                      <span class="block text-xs font-mono font-normal text-slate-400 mt-0.5">ID: {{ tenant.id }}</span>
                  </td>
                  <td class="px-6 py-4 text-slate-600 dark:text-slate-300">{{ tenant.domain || '—' }}</td>
                  <td class="px-6 py-4">
                       <div class="flex flex-wrap gap-1 max-w-xs">
                          <span v-for="mod in tenant.enabled_modules?.slice(0,3)" :key="mod" class="px-1.5 py-0.5 rounded text-[10px] uppercase font-bold bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                              {{ mod }}
                          </span>
                          <span v-if="tenant.enabled_modules?.length > 3" class="text-xs text-slate-400">+{{ tenant.enabled_modules.length - 3 }}</span>
                      </div>
                  </td>
                  <td class="px-6 py-4">
                      <span
                      class="px-2.5 py-1 rounded-full text-xs font-medium border"
                      :class="tenant.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/30' : 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-400'"
                      >
                      {{ tenant.status }}
                      </span>
                  </td>
                  <td class="px-6 py-4 text-right space-x-2">
                      <button
                      class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-bold uppercase tracking-wide"
                      @click="openEdit(tenant)"
                      >
                      Edit
                      </button>
                      <span class="text-slate-300 dark:text-slate-700">|</span>
                      <button
                      class="text-slate-500 hover:text-slate-800 dark:text-slate-400 text-xs font-bold uppercase tracking-wide"
                      @click="toggleStatus(tenant)"
                      >
                      {{ tenant.status === 'active' ? 'Suspend' : 'Activate' }}
                      </button>
                      <span class="text-slate-300 dark:text-slate-700">|</span>
                      <button
                      class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-bold uppercase tracking-wide"
                      @click="deleteTenant(tenant)"
                      >
                      Delete
                      </button>
                  </td>
                  </tr>
              </tbody>
              </table>
          </div>
      </div>
    </div>
  </template>