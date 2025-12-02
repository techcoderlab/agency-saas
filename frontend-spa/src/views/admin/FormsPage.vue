<script setup>
import { onMounted, ref } from 'vue'
import api from '../../utils/request'
import FormBuilder from '../../components/FormBuilder.vue'

const forms = ref([])
const loading = ref(false)
const showBuilder = ref(false)
const editingForm = ref(null)
const showPayload = ref(false)
const payloadForm = ref(null)

const buildPayloadExample = (schema) => {
  const example = {}
  ;(schema || []).forEach((field, index) => {
    const key = field.name || `field_${index + 1}`
    if (field.type === 'checkbox-group' || field.multiple) {
      example[key] = []
    } else if (field.type === 'range') {
      example[key] = field.min ?? 0
    } else {
      example[key] = ''
    }
  })
  return example
}

const fetchForms = async () => {
  loading.value = true
  const { data } = await api.get('/forms')
  forms.value = data
  loading.value = false
}

const editForm = (form) => {
  editingForm.value = form
  showBuilder.value = true
}

const newForm = () => {
  editingForm.value = null
  showBuilder.value = true
}

const toggleActive = async (form) => {
  const { data } = await api.put(`/forms/${form.id}`, {
    is_active: !form.is_active,
  })
  Object.assign(form, data)
}

const deleteForm = async (form) => {
  if (!confirm(`Delete form "${form.name}"? This cannot be undone.`)) return

  await api.delete(`/forms/${form.id}`)
  forms.value = forms.value.filter((f) => f.id !== form.id)
}

const viewPayload = (form) => {
  payloadForm.value = {
    name: form.name,
    example: buildPayloadExample(form.schema),
  }
  showPayload.value = true
}

const handleFormSaved = () => {
  showBuilder.value = false
  fetchForms()
}

onMounted(fetchForms)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h2 class="text-xl font-semibold">Forms</h2>
      <button
        class="px-3 py-2 text-sm rounded bg-blue-600  hover:bg-blue-700"
        @click="newForm"
      >
        New Form
      </button>
    </div>

    <div v-if="loading">Loading forms...</div>
    <div v-else class="table-card overflow-x-auto">
      <table
        class="table"
      >
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Status</th>
            <th>Webhook</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="form in forms"
            :key="form.id"
          >
            <td>{{ form.id }}</td>
            <td>{{ form.name }}</td>
            <td>
              <span
                class="px-2 py-1 rounded-full text-xs font-semibold"
                :class="form.is_active ? 'bg-green-400/20 text-green-600 dark:bg-green-300/10 dark:text-green-400' : 'bg-slate-400/20 text-slate-700 dark:bg-slate-700/60 dark:text-slate-400'"
              >
                {{ form.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="text-xs max-w-xs truncate">{{ form.n8n_webhook_url || '—' }}</td>
            <td class="text-right space-x-2 whitespace-nowrap">
              <button class="btn-secondary" @click="toggleActive(form)">
                {{ form.is_active ? 'Deactivate' : 'Activate' }}
              </button>
              <button class="btn-primary" @click="editForm(form)">Edit</button>
              <button class="btn-danger" @click="deleteForm(form)">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <FormBuilder
      v-if="showBuilder"
      :form="editingForm"
      @saved="handleFormSaved"
      @cancel="showBuilder = false"
    />

    <div
      v-if="showPayload && payloadForm && editingForm"
      class="fixed inset-0 z-40 flex items-center justify-center bg-black/70"
    >
      <div class="w-full max-w-lg mx-auto  dark:bg-slate-900 text-gray-900 dark:text-slate-100 rounded-xl shadow-2xl border border-slate-800 p-6 space-y-5">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100">Form Structure — {{ editingForm.name }}</h3>
          <button
            class="text-sm px-3 py-1 rounded border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-slate-500 hover:bg-slate-200 hover:dark:bg-slate-800"
            @click="showPayload = false; payloadForm = null; editingForm = null;"
          >
            Close
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full w-full rounded border-collapse text-sm">
            <thead>
              <tr>
                <th class="px-3 py-2 text-left font-semibold uppercase text-xs dark:text-slate-300">Field</th>
                <th class="px-3 py-2 text-left font-semibold uppercase text-xs dark:text-slate-300">Type</th>
                <th class="px-3 py-2 text-left font-semibold uppercase text-xs dark:text-slate-300">Required</th>
                <th class="px-3 py-2 text-left font-semibold uppercase text-xs dark:text-slate-300">Example</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(field, idx) in editingForm.schema" :key="field.name || idx">
                <td class="px-3 py-2">{{ field.label || field.name || ("field-" + (idx + 1)) }}</td>
                <td class="px-3 py-2">{{ (field.type || 'text').replace('-', ' ').replace(/\b\w/g, c => c.toUpperCase()) }}</td>
                <td class="px-3 py-2">
                  <span class="inline-block px-2 rounded-full text-xs font-medium" :class="field.required ? 'bg-rose-600/10 text-rose-500 dark:bg-rose-600/20' : 'bg-slate-400/10 text-slate-400 dark:bg-slate-500/20'">{{ field.required ? 'Required' : 'Optional' }}</span>
                </td>
                <td class="px-3 py-2">
                  {{ field.placeholder ||
                    (field.type === 'color' ? '#0ea5e9' :
                     field.type === 'range' ? (field.min ?? 0) :
                     field.type === 'select' && Array.isArray(field.options) ? (field.options[0]?.label || field.options[0]?.value || '') :
                     field.type === 'checkbox-group' ? '[ ]' :
                     field.type === 'radio-group' ? (field.options[0]?.label || field.options[0]?.value || '') :
                     field.type === 'file' ? 'File Upload' :
                     '')
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>


