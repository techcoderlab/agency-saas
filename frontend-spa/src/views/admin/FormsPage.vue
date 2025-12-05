<script setup>
import { onMounted, ref } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import api from '../../utils/request'
import FormBuilder from '../../components/FormBuilder.vue'
import CopyButton from '../../components/ui/CopyButton.vue'

const forms = ref([])
const loading = ref(false)
const showBuilder = ref(false)
const editingForm = ref(null)
const showPayload = ref(false)
const payloadForm = ref(null)

const getPublicLink = (id) => {
  return `${window.location.origin}/public/form/${id}`
}

const buildPayloadExample = (schema) => {
  const example = {}
  ;(schema || []).forEach((field, index) => {
    const key = field.name || `field_${index + 1}`
    if (field.type === 'checkbox-group' || field.multiple) example[key] = []
    else if (field.type === 'range') example[key] = field.min ?? 0
    else example[key] = ''
  })
  return example
}

const fetchForms = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/forms')
    forms.value = data
  } catch (e) { console.error(e) } finally { loading.value = false }
}

const editForm = (form) => {
  editingForm.value = form
  showBuilder.value = true
}

const newForm = () => {
  editingForm.value = null
  showBuilder.value = true
}

const deleteForm = async (form) => {
  if (!confirm(`Delete form "${form.name}"?`)) return
  try {
    await api.delete(`/forms/${form.id}`)
    forms.value = forms.value.filter((f) => f.id !== form.id)
  } catch (e) { alert('Failed to delete') }
}

const viewPayload = (form) => {
  payloadForm.value = { name: form.name, example: buildPayloadExample(form.schema) }
  showPayload.value = true
}

const handleFormSaved = () => {
  showBuilder.value = false
  fetchForms()
}

onMounted(fetchForms)
</script>

<template>
  <div>
    <!-- List View -->
    <div v-if="!showBuilder" class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Forms</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage data collection forms.</p>
        </div>
        <button @click="newForm" 
        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
          + Add Form
        </button>
      </div>

      <div v-if="loading && forms.length === 0" class="text-slate-500 py-8 text-center">Loading...</div>
      
      <div v-else class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm overflow-visible">
        <div class="overflow-x-auto min-h-[250px]">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Form Details</th>
                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Status</th>
                <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Webhook</th>
                <th class="px-6 py-4 text-right font-semibold text-slate-700 dark:text-slate-300">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
              <tr v-for="form in forms" :key="form.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors">
                
                <td class="px-6 py-4 align-top">
                  <div class="font-bold text-slate-900 dark:text-slate-100">{{ form.name }}</div>
                  <div class="flex items-center gap-2 mt-1">
                     <span class="text-xs font-mono text-slate-500">{{ form.id }}</span>
                     <CopyButton :text="form.id" title="Copy Form ID" />
                  </div>
                </td>

                <td class="px-6 py-4 align-top">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border"
                    :class="form.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                    {{ form.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>

                <td class="px-6 py-4 align-top">
                  <div v-if="form.webhook_url" class="flex items-center gap-2 max-w-[200px]">
                     <span class="truncate text-xs text-slate-600 dark:text-slate-400 font-mono" :title="form.webhook_url">{{ form.webhook_url }}</span>
                     <CopyButton :text="form.webhook_url" title="Copy Webhook" />
                  </div>
                  <span v-else class="text-xs text-slate-400 italic">Default</span>
                </td>

                <td class="px-6 py-4 text-right align-top relative">
                   <div class="flex justify-end items-center gap-2">
                      <a :href="getPublicLink(form.id)" target="_blank" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="View Live Form">
                          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                      </a>
                      
                      <Menu as="div" class="relative inline-block text-left">
                         <MenuButton class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 13a1 1 0 100-2 1 1 0 000 2zm0-5a1 1 0 100-2 1 1 0 000 2zm0 10a1 1 0 100-2 1 1 0 000 2z"/></svg>
                         </MenuButton>
                         <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                            <MenuItems class="absolute right-0 mt-2 w-48 origin-top-right rounded-lg bg-white dark:bg-slate-900 shadow-lg ring-1 ring-black/5 focus:outline-none z-50 border border-slate-100 dark:border-slate-800">
                               <div class="p-1">
                                  <MenuItem v-slot="{ active }">
                                     <button @click="viewPayload(form)" :class="[active ? 'bg-slate-50 dark:bg-slate-800 text-blue-600' : 'text-slate-700 dark:text-slate-300', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                                        View Schema Payload
                                     </button>
                                  </MenuItem>
                                  <MenuItem v-slot="{ active }">
                                     <button @click="editForm(form)" :class="[active ? 'bg-slate-50 dark:bg-slate-800 text-blue-600' : 'text-slate-700 dark:text-slate-300', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                                        Edit Form
                                     </button>
                                  </MenuItem>
                                  <MenuItem v-slot="{ active }">
                                     <button @click="deleteForm(form)" :class="[active ? 'bg-red-50 dark:bg-red-900/20 text-red-600' : 'text-red-600', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                                        Delete Form
                                     </button>
                                  </MenuItem>
                               </div>
                            </MenuItems>
                         </transition>
                      </Menu>
                   </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Payload Modal -->
      <div v-if="showPayload && payloadForm" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
          <div class="bg-white dark:bg-slate-900 rounded-xl max-w-lg w-full p-6 shadow-xl border border-slate-200 dark:border-slate-800">
              <h3 class="text-lg font-bold mb-4 text-slate-900 dark:text-white">Payload Structure</h3>
              <pre class="bg-slate-50 dark:bg-slate-950 p-4 rounded-lg text-xs font-mono overflow-auto max-h-[60vh] text-slate-600 dark:text-slate-300">{{ JSON.stringify(payloadForm.example, null, 2) }}</pre>
              <div class="mt-4 flex justify-end">
                  <button @click="showPayload = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium">Close</button>
              </div>
          </div>
      </div>
    </div>

    <!-- Builder View (Swaps with List) -->
    <FormBuilder v-else :form="editingForm" @saved="handleFormSaved" @cancel="showBuilder = false" />
  </div>
</template>