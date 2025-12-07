<script setup>
  import { ref, onMounted } from 'vue'
  import api from '../../utils/request'
  import FormBuilder from '../../components/FormBuilder.vue'
  
  const leads = ref([])
  const loadingLeads = ref(false)
  
  const forms = ref([])
  const loadingForms = ref(false)
  const showBuilder = ref(false)
  const editingForm = ref(null)
  
  const fetchLeads = async () => {
    loadingLeads.value = true
    try {
      const { data } = await api.get('/leads')
      leads.value = data.data || data // Handle pagination wrap if exists
    } catch(e) {} finally {
      loadingLeads.value = false
    }
  }
  
  const fetchForms = async () => {
    loadingForms.value = true
    try {
      const { data } = await api.get('/forms')
      forms.value = data
    } catch(e) {} finally {
      loadingForms.value = false
    }
  }
  
  const editForm = (form) => {
    editingForm.value = form
    showBuilder.value = true
  }
  
  const newForm = () => {
    editingForm.value = null
    showBuilder.value = true
  }
  
  const handleFormSaved = () => {
    showBuilder.value = false
    fetchForms()
  }
  
  onMounted(() => {
    fetchLeads()
    fetchForms()
  })
  </script>
  
  <template>
    <div class="space-y-8">
      
      <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Dashboard</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Overview of your agency performance.</p>
      </div>
  
      <section class="card overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex justify-between items-center">
          <h3 class="font-semibold text-slate-800 dark:text-slate-200">Recent Leads</h3>
          <router-link to="/admin/leads" class="text-xs font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">View All</router-link>
        </div>
        
        <div v-if="loadingLeads" class="p-8 text-center text-slate-500 text-sm">Loading data...</div>
        
        <div v-else class="overflow-x-auto">
          <table class="table">
              <thead>
              <tr>
                  <th>ID</th>
                  <th>Source Form</th>
                  <th>Status</th>
                  <th>Date</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="lead in leads.slice(0, 5)" :key="lead.id">
                  <td class="font-mono text-xs text-slate-500">#{{ lead.id }}</td>
                  <td class="font-medium text-slate-700 dark:text-slate-300">
                      {{ lead.form?.name || 'Unknown Form' }}
                  </td>
                  <td>
                      <span class="px-2 py-0.5 rounded-full text-xs border bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700 capitalize">
                          {{ lead.status }}
                      </span>
                  </td>
                  <td class="text-slate-500">
                      {{ new Date(lead.created_at).toLocaleDateString() }}
                  </td>
              </tr>
              <tr v-if="leads.length === 0">
                  <td colspan="4" class="text-center py-6 text-slate-500 italic">No leads found.</td>
              </tr>
              </tbody>
          </table>
        </div>
      </section>
  
      <section class="card overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex justify-between items-center">
          <h3 class="font-semibold text-slate-800 dark:text-slate-200">Active Forms</h3>
          <button
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-900 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-900 transition-colors"
            @click="newForm"
          >
            + New Form
          </button>
        </div>
  
        <div v-if="loadingForms" class="p-8 text-center text-slate-500 text-sm">Loading data...</div>
  
        <div v-else class="overflow-x-auto">
          <table class="table">
              <thead>
              <tr>
                  <th>Name</th>
                  <th>Status</th>
                  <th>Schema Fields</th>
                  <th class="text-right">Actions</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="form in forms.slice(0, 5)" :key="form.id">
                  <td class="font-bold text-slate-900 dark:text-white">{{ form.name }}</td>
                  <td>
                  <span
                      class="px-2 py-0.5 rounded-full text-xs border"
                      :class="form.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-900/30' : 'bg-slate-100 text-slate-600 border-slate-200'"
                  >
                      {{ form.is_active ? 'Active' : 'Inactive' }}
                  </span>
                  </td>
                  <td class="text-slate-500 text-xs">{{ form.schema?.length || 0 }} fields</td>
                  <td class="text-right">
                  <button
                      class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium transition-colors"
                      @click="editForm(form)"
                  >
                      Edit
                  </button>
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
      </section>
    </div>
  </template>