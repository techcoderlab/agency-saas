<script setup>
import { ref, watch, computed } from 'vue'
import api from '../utils/request'

const props = defineProps({
  form: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['saved', 'cancel'])

const name = ref(props.form?.name || '')
const n8nWebhookUrl = ref(props.form?.n8n_webhook_url || '')
const schemaText = ref(JSON.stringify(props.form?.schema || [], null, 2))
const isActive = ref(props.form?.is_active ?? true)
const error = ref('')
const loading = ref(false)

watch(
  () => props.form,
  (form) => {
    name.value = form?.name || ''
    n8nWebhookUrl.value = form?.n8n_webhook_url || ''
    schemaText.value = JSON.stringify(form?.schema || [], null, 2)
    isActive.value = form?.is_active ?? true
  },
)

const parsedSchema = computed(() => {
  try {
    return JSON.parse(schemaText.value || '[]')
  } catch {
    return null
  }
})

const save = async () => {
  error.value = ''
  if (!parsedSchema.value) {
    error.value = 'Schema must be valid JSON'
    return
  }

  loading.value = true
  try {
    const payload = {
      name: name.value,
      schema: parsedSchema.value,
      n8n_webhook_url: n8nWebhookUrl.value || null,
      is_active: isActive.value,
    }

    if (props.form?.id) {
      await api.put(`/forms/${props.form.id}`, payload)
    } else {
      await api.post('/forms', payload)
    }

    emit('saved')
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to save form'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="bg-white dark:bg-slate-900/80 shadow-xl rounded-2xl p-6 md:p-8 max-w-4xl mx-auto border dark:border-slate-800 space-y-5 transition-colors duration-300">
    <h3 class="text-2xl font-bold accent mb-4">{{ form ? 'Edit Form' : 'New Form' }}</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
      <!-- Form Fields -->
      <div class="space-y-6">
        <div>
          <label class="block text-base font-semibold mb-1 text-slate-700 dark:text-slate-200">Form Name</label>
          <input
            v-model="name"
            type="text"
            class="w-full rounded-xl py-2 px-4 bg-white border border-slate-200 text-slate-900 dark:bg-slate-900/70 dark:border-slate-700 dark:text-slate-100 shadow-inner focus:outline-none focus:ring-2 focus:ring-violet-500"
          />
        </div>
        <div>
          <label class="block text-base font-semibold mb-1 text-slate-700 dark:text-slate-200">n8n Webhook URL</label>
          <input
            v-model="n8nWebhookUrl"
            type="url"
            class="w-full rounded-xl py-2 px-4 bg-white border border-slate-200 text-slate-900 dark:bg-slate-900/70 dark:border-slate-700 dark:text-slate-100 shadow-inner focus:outline-none focus:ring-2 focus:ring-violet-500"
            placeholder="optional – override tenant-level automation"
          />
        </div>
        <div class="flex items-center gap-2">
          <input
            id="active"
            v-model="isActive"
            type="checkbox"
            class="rounded border-slate-400 dark:border-slate-600 text-violet-600 dark:text-violet-500 focus:ring-violet-500"
          />
          <label for="active" class="text-base select-none font-semibold text-slate-700 dark:text-slate-200">Active</label>
        </div>
        <div>
          <label class="block text-base font-semibold mb-1 text-slate-700 dark:text-slate-200">Schema (JSON)</label>
          <textarea
            v-model="schemaText"
            rows="8"
            class="w-full rounded-xl py-2 px-4 bg-white border border-slate-200 font-mono text-sm text-slate-900 dark:bg-slate-900/70 dark:border-slate-700 dark:text-slate-100 shadow-inner focus:outline-none focus:ring-2 focus:ring-violet-500"
            placeholder='[{ "type":"text", "name":"name", "label":"Full Name" }, ...]'
          />
        </div>
      </div>
      <!-- Preview -->
      <div class="space-y-3">
        <h4 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-2">Preview</h4>
        <div class="rounded-xl border dark:border-slate-800 bg-slate-50 dark:bg-slate-900 shadow-inner p-4 space-y-3 max-h-[28rem] overflow-auto">
          <div v-if="!parsedSchema" class="text-xs text-red-600">Invalid JSON</div>
          <div v-else class="space-y-4">
            <div
              v-for="(field, index) in parsedSchema"
              :key="index"
              class="flex flex-col gap-2"
            >
              <label class="block text-xs font-semibold text-slate-600 dark:text-slate-300 ml-1">
                {{ field.label || field.name || 'Field ' + (index + 1) }}
              </label>
              <input
                v-if="['text','email','number'].includes(field.type)"
                :type="field.type"
                :placeholder="field.placeholder"
                disabled
                class="w-full rounded-lg py-2 px-3 bg-white border border-slate-200 text-slate-900 dark:bg-slate-800/80 dark:border-slate-700 dark:text-slate-300 shadow-inner text-xs outline-none"
              />
              <textarea
                v-else-if="field.type === 'textarea'"
                :placeholder="field.placeholder"
                disabled
                class="w-full rounded-lg py-2 px-3 bg-white border border-slate-200 text-slate-900 dark:bg-slate-800/80 dark:border-slate-700 dark:text-slate-300 shadow-inner text-xs outline-none resize-none"
              />
              <select
                v-else-if="field.type === 'select'"
                disabled
                class="w-full rounded-lg py-2 px-3 bg-white border border-slate-200 text-slate-900 dark:bg-slate-800/80 dark:border-slate-700 dark:text-slate-300 shadow-inner text-xs"
              >
                <option v-for="option in field.options || []" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
              <input
                v-else-if="field.type === 'color'"
                type="color"
                disabled
                class="w-14 h-8 rounded-lg border border-slate-300 dark:border-slate-700 bg-transparent"
                :value="field.default || '#6366f1'"
              />
              <input
                v-else-if="field.type === 'range'"
                type="range"
                :min="field.min || 0"
                :max="field.max || 100"
                disabled
                class="w-full accent-violet-500"
              />
              <div v-else class="w-full rounded-lg py-2 px-3 bg-white border border-slate-200 text-slate-900 dark:bg-slate-800/80 dark:border-slate-700 dark:text-slate-300 shadow-inner text-xs">
                Field
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <p v-if="error" class="text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    <div class="flex justify-end space-x-2">
      <button
        class="btn-secondary"
        @click="emit('cancel')"
      >
        Cancel
      </button>
      <button
        class="btn-primary"
        :disabled="loading"
        @click="save"
      >
        {{ loading ? 'Saving...' : 'Save' }}
      </button>
    </div>
  </div>
</template>


