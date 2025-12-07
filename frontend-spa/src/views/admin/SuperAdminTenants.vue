<script setup>
import { onMounted, ref } from 'vue'
import api from '../../utils/request'
import { useApiCache } from '../../composables/useApiCache'; // Adjust path as necessary


const tenants = ref([])
const loading = ref(false)
const saving = ref(false)
const { fetchDataWithCache } = useApiCache();


const showForm = ref(false)
const editingTenant = ref(null)

const form = ref({
  name: '',
  domain: '',
  status: 'active',
  enabled_modules: [] // New field
})

// Available Modules Definition
const availableModules = ref([]);

const error = ref('')

const resetForm = () => {
  form.value = {
    name: '',
    domain: '',
    status: 'active',
    enabled_modules: [] // Default all
  }
  editingTenant.value = null
  error.value = ''
}


const fetchModules = async () => {
  // loading.value = true
  const data = await fetchDataWithCache(
      'tenant_modules_cache',
      600000, // ttl = 10 mins
      () => api.get('/tenants/modules')
    );
    availableModules.value = data.filter(item => item.id !== 'tenants')
  // loading.value = false
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
  form.value = {
    name: tenant.name || '',
    domain: tenant.domain || '',
    status: tenant.status || 'active',
    // Ensure we handle potential nulls from backend
    enabled_modules: tenant.enabled_modules || []
  }
  error.value = ''
  showForm.value = true
}

const saveTenant = async () => {
  saving.value = true
  error.value = ''

  try {
    const payload = {
      name: form.value.name,
      domain: form.value.domain || null,
      status: form.value.status,
      enabled_modules: form.value.enabled_modules // Send array
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

    fetchTenants() // Refresh to get formatted data
    showForm.value = false
    resetForm()
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save tenant'
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
      <h2 class="text-xl font-semibold">Tenants</h2>
      <button
        class="px-3 py-2 text-sm rounded bg-blue-600  hover:bg-blue-700"
        @click="openCreate"
      >
        Create Tenant
      </button>
    </div>

    <div v-if="showForm" class="bg-white dark:bg-slate-900 shadow-lg rounded-xl border border-slate-200 dark:border-slate-800 p-6 space-y-6">
      <h3 class="text-lg font-bold text-slate-900 dark:text-white">
        {{ editingTenant ? 'Edit Tenant' : 'New Tenant' }}
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-4">
           <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Name</label>
            <input v-model="form.name" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Domain</label>
            <input v-model="form.domain" type="text" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white" placeholder="optional" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Status</label>
            <select v-model="form.status" class="w-full border rounded-lg px-3 py-2 text-sm dark:bg-slate-800 dark:border-slate-700 dark:text-white">
              <option value="active">Active</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>
        </div>

        <div class="bg-slate-50 dark:bg-slate-900/50 p-4 rounded-lg border border-slate-100 dark:border-slate-800">
            <label class="block text-sm font-bold text-slate-700 dark:text-slate-300 mb-3">Enabled Modules</label>
            <div class="space-y-2">
                <div v-for="mod in availableModules" :key="mod.id" class="flex items-center">
                    <input type="checkbox" :id="`mod-${mod.id}`" :value="mod.id" v-model="form.enabled_modules" 
                           class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800">
                    <label :for="`mod-${mod.id}`" class="ml-2 text-sm text-slate-700 dark:text-slate-300">{{ mod.label }}</label>
                </div>
            </div>
            <p class="text-xs text-slate-500 mt-3">Disabling a module will immediately revoke access for all users and API keys in this tenant.</p>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-4 border-t dark:border-slate-800">
        <button class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg dark:text-slate-300 dark:hover:bg-slate-800" @click="showForm = false">Cancel</button>
        <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm" :disabled="saving" @click="saveTenant">
            {{ saving ? 'Saving...' : 'Save Tenant' }}
        </button>
      </div>
    </div>

    <div v-if="loading">Loading...</div>
    <table
      v-else
      class="min-w-full  shadow rounded overflow-hidden"
    >
      <thead class="bg-gray-50">
        <tr>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Domain</th>
          <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
          <th class="px-4 py-2" />
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="tenant in tenants"
          :key="tenant.id"
          class="border-t"
        >
          <td class="px-4 py-2 text-sm">{{ tenant.id }}</td>
          <td class="px-4 py-2 text-sm">{{ tenant.name }}</td>
          <td class="px-4 py-2 text-sm">{{ tenant.domain }}</td>
          <td class="px-4 py-2 text-sm">
            <span
              class="px-2 py-1 rounded-full text-xs"
              :class="tenant.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
            >
              {{ tenant.status }}
            </span>
          </td>
          <td class="px-4 py-2 text-right space-x-2">
            <button
              class="px-3 py-1 text-xs rounded border border-gray-300 hover:bg-gray-50"
              @click="openEdit(tenant)"
            >
              Edit
            </button>
            <button
              class="px-3 py-1 text-xs rounded bg-blue-600  hover:bg-blue-700"
              @click="toggleStatus(tenant)"
            >
              {{ tenant.status === 'active' ? 'Suspend' : 'Activate' }}
            </button>
            <button
              class="px-3 py-1 text-xs rounded bg-red-600  hover:bg-red-700"
              @click="deleteTenant(tenant)"
            >
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

