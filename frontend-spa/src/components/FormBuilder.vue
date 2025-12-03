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

// State
const name = ref(props.form?.name || '')
const webhookUrl = ref(props.form?.webhook_url || '')
const webhookSecret = ref(props.form?.webhook_secret || '')
const schemaText = ref(JSON.stringify(props.form?.schema || [], null, 2))
const isActive = ref(props.form?.is_active ?? true)
const error = ref('')
const loading = ref(false)

// Watchers
watch(
  () => props.form,
  (form) => {
    name.value = form?.name || ''
    webhookUrl.value = form?.webhook_url || ''
    webhookSecret.value = form?.webhook_secret || ''
    schemaText.value = JSON.stringify(form?.schema || [], null, 2)
    isActive.value = form?.is_active ?? true
  },
)

// Computed Schema for Preview
const parsedSchema = computed(() => {
  try {
    return JSON.parse(schemaText.value || '[]')
  } catch {
    return null
  }
})

// Actions
const save = async () => {
  error.value = ''
  if (!parsedSchema.value) {
    error.value = 'Invalid JSON Schema'
    return
  }
  if (!name.value.trim()) {
    error.value = 'Form name is required'
    return
  }

  loading.value = true
  try {
    const payload = {
      name: name.value,
      schema: parsedSchema.value,
      webhook_url: webhookUrl.value || null,
      webhook_secret: webhookSecret.value || null,
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
  <div class="flex flex-col h-full">
    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shrink-0">
      <div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ form ? 'Edit Form' : 'Create New Form' }}</h3>
        <p class="text-xs text-slate-500">Configure your form schema and settings.</p>
      </div>
      <button 
        @click="emit('cancel')" 
        class="text-slate-400 hover:text-slate-500 transition-colors"
      >
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="flex-1 flex overflow-hidden">
      
      <div class="w-1/2 flex flex-col border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-y-auto">
        <div class="p-6 space-y-6">
          
          <div class="space-y-4">
            <div class="space-y-1">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Form Name</label>
              <input
                v-model="name"
                type="text"
                class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm placeholder-slate-400 focus:border-primary focus:ring-1 focus:ring-primary dark:text-white"
                placeholder="e.g. Contact Us"
              />
            </div>

            <div class="space-y-1">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                Webhook URL <span class="text-xs font-normal text-slate-400">(Optional Override)</span>
              </label>
              <input
                v-model="webhookUrl"
                type="url"
                class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm placeholder-slate-400 focus:border-primary focus:ring-1 focus:ring-primary dark:text-white"
                placeholder="https://n8n.your-domain.com/webhook/..."
              />
            </div>
            <div class="space-y-1">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                Webhook Secret <span class="text-xs font-normal text-slate-400">(Optional)</span>
              </label>
              <input
                v-model="webhookSecret"
                type="password"
                class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm placeholder-slate-400 focus:border-primary focus:ring-1 focus:ring-primary dark:text-white"
                placeholder="Secret key for signature verification"
              />
              <p class="text-xs text-slate-500 mt-1">If set, we send a `X-Webhook-Signature` header.</p>
            </div>

            <div class="flex items-center gap-2 pt-1">
              <input
                id="active_toggle"
                v-model="isActive"
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-900"
              />
              <label for="active_toggle" class="text-sm font-medium text-slate-700 dark:text-slate-300 select-none">Form is Active</label>
            </div>
          </div>

          <hr class="border-slate-100 dark:border-slate-800" />

          <div class="space-y-2 flex-1 flex flex-col">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">Schema Definition (JSON)</label>
              <span 
                class="text-xs px-2 py-0.5 rounded"
                :class="parsedSchema ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
              >
                {{ parsedSchema ? 'Valid JSON' : 'Invalid Syntax' }}
              </span>
            </div>
            <textarea
              v-model="schemaText"
              class="flex-1 w-full min-h-[300px] rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 px-4 py-3 font-mono text-xs leading-relaxed text-slate-600 dark:text-slate-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none resize-none"
              spellcheck="false"
              placeholder='[
  {
    "type": "text",
    "name": "full_name",
    "label": "Full Name",
    "placeholder": "John Doe",
    "required": true
  }
]'
            ></textarea>
            <p class="text-xs text-slate-400">Define your fields array here. Changes reflect instantly in the preview.</p>
          </div>

        </div>
      </div>

      <div class="w-1/2 bg-slate-100 dark:bg-slate-900/50 overflow-y-auto border-l border-slate-200 dark:border-slate-800 flex flex-col">
        <div class="p-6 flex-1">
           <div class="mb-4 flex items-center justify-between">
              <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500">Live Preview</h4>
              <div class="flex gap-1">
                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                <div class="w-2 h-2 rounded-full bg-green-400"></div>
              </div>
           </div>

           <div class="mx-auto max-w-md bg-white dark:bg-slate-950 rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
              <div class="p-6 md:p-8 space-y-6">
                 
                 <div class="text-center space-y-2 mb-6">
                    <div class="h-10 w-10 bg-primary/10 text-primary rounded-lg mx-auto flex items-center justify-center">
                       <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ name || 'Untitled Form' }}</h2>
                 </div>

                 <div v-if="!parsedSchema" class="py-10 text-center text-sm text-red-500 bg-red-50 dark:bg-red-900/10 rounded-lg border border-red-100 dark:border-red-900/20">
                    Fix JSON to see preview
                 </div>

                 <div v-else-if="parsedSchema.length === 0" class="py-10 text-center text-sm text-slate-400 bg-slate-50 dark:bg-slate-900 rounded-lg border border-dashed border-slate-200 dark:border-slate-800">
                    No fields defined yet
                 </div>

                 <form v-else class="space-y-5" @submit.prevent>
                    <div v-for="(field, idx) in parsedSchema" :key="idx" class="space-y-1.5">
                       
                       <label class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                          {{ field.label || field.name }}
                          <span v-if="field.required" class="text-red-500">*</span>
                       </label>

                       <input 
                          v-if="['text', 'email', 'number', 'tel', 'url'].includes(field.type || 'text')"
                          :type="field.type || 'text'"
                          :placeholder="field.placeholder"
                          class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary dark:text-white disabled:opacity-75 disabled:cursor-not-allowed"
                          disabled
                       />

                       <textarea 
                          v-else-if="field.type === 'textarea'"
                          :placeholder="field.placeholder"
                          class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary dark:text-white disabled:opacity-75 disabled:cursor-not-allowed resize-none"
                          rows="3"
                          disabled
                       ></textarea>

                       <select 
                          v-else-if="field.type === 'select'"
                          class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 px-3 py-2 text-sm shadow-sm focus:border-primary focus:ring-1 focus:ring-primary dark:text-white disabled:opacity-75 disabled:cursor-not-allowed appearance-none"
                          disabled
                       >
                          <option v-if="field.placeholder" value="">{{ field.placeholder }}</option>
                          <option v-for="opt in field.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                       </select>

                       <div v-else-if="field.type === 'checkbox-group'" class="space-y-2 pt-1">
                          <div v-for="opt in field.options" :key="opt.value" class="flex items-center gap-2">
                             <input type="checkbox" disabled class="rounded border-slate-300 text-primary dark:border-slate-700 bg-white dark:bg-slate-900" />
                             <span class="text-sm text-slate-600 dark:text-slate-400">{{ opt.label }}</span>
                          </div>
                       </div>

                       <div v-else-if="field.type === 'radio-group'" class="space-y-2 pt-1">
                          <div v-for="opt in field.options" :key="opt.value" class="flex items-center gap-2">
                             <input type="radio" disabled class="border-slate-300 text-primary dark:border-slate-700 bg-white dark:bg-slate-900" />
                             <span class="text-sm text-slate-600 dark:text-slate-400">{{ opt.label }}</span>
                          </div>
                       </div>
                        
                       <div v-else class="text-xs text-red-500">Unsupported field type: {{ field.type }}</div>

                    </div>

                    <div class="pt-4">
                      <button disabled class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary opacity-50 cursor-not-allowed">
                        Submit Form
                      </button>
                    </div>
                 </form>

              </div>
           </div>
        </div>
      </div>

    </div>

    <div class="bg-white dark:bg-slate-950 border-t border-slate-200 dark:border-slate-800 px-6 py-4 flex justify-between items-center shrink-0">
      <div class="text-sm text-red-600 font-medium">{{ error }}</div>
      <div class="flex items-center gap-3">
        <button 
          @click="emit('cancel')"
          class="px-4 py-2 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-900 transition-colors"
        >
          Cancel
        </button>
        <button 
          @click="save"
          :disabled="loading"
          class="px-6 py-2 rounded-lg text-sm font-medium text-white bg-primary hover:bg-primary/90 shadow-lg shadow-primary/20 transition-all disabled:opacity-50 disabled:shadow-none"
        >
          {{ loading ? 'Saving...' : 'Save Form' }}
        </button>
      </div>
    </div>
  </div>
</template>