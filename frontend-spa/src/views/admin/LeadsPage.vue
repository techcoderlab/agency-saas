<script setup>
import { onMounted, ref } from 'vue'
import api from '../../utils/request'

const leads = ref([])
const loading = ref(false)
const showLead = ref(false)
const activeLead = ref(null)

const fetchLeads = async () => {
  loading.value = true
  try {
    const { data } = await api.get('/leads')
    leads.value = data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const viewLead = (lead) => {
  activeLead.value = lead
  showLead.value = true
}

const closeLead = () => {
  showLead.value = false
  setTimeout(() => {
    activeLead.value = null
  }, 200)
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
      <button 
        @click="fetchLeads" 
        :disabled="loading"
        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-900 dark:text-slate-100 transition-colors shadow-sm"
      >
        <span v-if="loading">Loading...</span>
        <span v-else>Refresh</span>
      </button>
    </div>

    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
          <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800">
            <tr>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">ID</th>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Form ID</th>
              <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Submitted At</th>
              <th class="px-6 py-4 text-right font-semibold text-slate-700 dark:text-slate-300">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="lead in leads" :key="lead.id" class="hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors">
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400 font-mono text-xs">{{ lead.id }}</td>
              <td class="px-6 py-4 text-slate-900 dark:text-slate-200 font-medium">{{ lead.form_id }}</td>
              <td class="px-6 py-4 text-slate-600 dark:text-slate-400">
                {{ new Date(lead.created_at).toLocaleString() }}
              </td>
              <td class="px-6 py-4 text-right">
                <button 
                  class="text-sm font-medium text-primary hover:text-primary/80 transition-colors"
                  @click="viewLead(lead)"
                >
                  View Details
                </button>
              </td>
            </tr>
            <tr v-if="leads.length === 0 && !loading">
              <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                No leads found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Transition name="modal">
      <div v-if="showLead" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" role="dialog" aria-modal="true">
        
        <div 
          class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
          @click="closeLead"
        ></div>

        <div class="relative w-full max-w-2xl bg-white dark:bg-slate-950 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh] transform transition-all">
          
          <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white">Submission Details</h3>
            <button 
              @click="closeLead"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-500 dark:hover:bg-slate-900 transition-colors"
            >
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
                  <div 
                    v-for="(value, key) in activeLead.payload" 
                    :key="key"
                    class="group rounded-lg border border-slate-200 dark:border-slate-800 p-3 hover:border-primary/50 transition-colors"
                  >
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
            <button 
              @click="closeLead"
              class="w-full inline-flex justify-center items-center px-4 py-2 border border-slate-300 dark:border-slate-700 shadow-sm text-sm font-medium rounded-lg text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
            >
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