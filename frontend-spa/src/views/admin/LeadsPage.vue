<script setup>
import { onMounted, ref } from 'vue'
import api from '../../utils/request'

const leads = ref([])
const loading = ref(false)
const showLead = ref(false)
const activeLead = ref(null)

const fetchLeads = async () => {
  loading.value = true
  const { data } = await api.get('/leads')
  leads.value = data
  loading.value = false
}

const viewLead = (lead) => {
  activeLead.value = lead
  showLead.value = true
}

onMounted(fetchLeads)
</script>

<template>
  <div class="space-y-4">
    <h2 class="text-xl font-semibold">Leads</h2>
    <div v-if="loading">Loading leads...</div>
    <div v-else class="table-card overflow-x-auto">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Form ID</th>
            <th>Created</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="lead in leads" :key="lead.id">
            <td>{{ lead.id }}</td>
            <td>{{ lead.form_id }}</td>
            <td>{{ new Date(lead.created_at).toLocaleString() }}</td>
            <td class="text-right">
              <button class="btn-secondary" @click="viewLead(lead)">View Lead</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="showLead && activeLead"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
    >
      <div class=" dark:bg-slate-900 rounded-xl p-6 shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-lg mx-auto">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-lg font-bold text-slate-700 dark:text-slate-100">Lead Details (ID: {{ activeLead.id }})</h3>
          <button
            class="px-3 py-1 text-xs rounded border border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-900 text-slate-500 hover:bg-slate-200 dark:hover:bg-slate-800"
            @click="showLead = false; activeLead = null;"
          >
            Close
          </button>
        </div>
        <div class="overflow-x-auto max-h-96">
          <table class="min-w-full w-full rounded border-collapse text-sm mb-4">
            <tbody>
              <tr v-for="(value, key) in activeLead.payload" :key="key">
                <td class="font-semibold px-3 py-2 text-slate-600 dark:text-slate-200">{{ key }}</td>
                <td class="px-3 py-2 text-right text-slate-800 dark:text-slate-100">{{ Array.isArray(value) ? value.join(', ') : value }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>


