<script setup>
import { onMounted, ref } from 'vue'
import api from '../../utils/request'

const tenants = ref([])
const loading = ref(false)
const saving = ref(false)

const showForm = ref(false)
const editingTenant = ref(null)

const form = ref({
  name: '',
  domain: '',
  status: 'active',
})

const error = ref('')

const resetForm = () => {
  form.value = {
    name: '',
    domain: '',
    status: 'active',
  }
  editingTenant.value = null
  error.value = ''
}

const fetchTenants = async () => {
  loading.value = true
  const { data } = await api.get('/tenants')
  tenants.value = data
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

    <div
      v-if="showForm"
      class=" shadow rounded p-4 space-y-4"
    >
      <h3 class="text-lg font-semibold">
        {{ editingTenant ? 'Edit Tenant' : 'New Tenant' }}
      </h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-3">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
            <input
              v-model="form.name"
              type="text"
              class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Domain</label>
            <input
              v-model="form.domain"
              type="text"
              class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-500"
              placeholder="optional"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select
              v-model="form.status"
              class="w-full border rounded px-3 py-2 focus:outline-none focus:ring focus:border-blue-500"
            >
              <option value="active">
                Active
              </option>
              <option value="suspended">
                Suspended
              </option>
            </select>
          </div>
        </div>

        <div />
      </div>

      <p
        v-if="error"
        class="text-sm text-red-600"
      >
        {{ error }}
      </p>

      <div class="flex justify-end space-x-2">
        <button
          class="px-3 py-2 text-sm rounded border hover:bg-gray-50"
          @click="
            showForm = false;
            resetForm();
          "
        >
          Cancel
        </button>
        <button
          class="px-3 py-2 text-sm rounded bg-blue-600  hover:bg-blue-700 disabled:opacity-50"
          :disabled="saving"
          @click="saveTenant"
        >
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

