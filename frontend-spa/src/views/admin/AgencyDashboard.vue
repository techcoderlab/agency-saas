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
  const { data } = await api.get('/leads')
  leads.value = data
  loadingLeads.value = false
}

const fetchForms = async () => {
  loadingForms.value = true
  const { data } = await api.get('/forms')
  forms.value = data
  loadingForms.value = false
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
    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Leads</h2>
      </div>
      <div v-if="loadingLeads">Loading leads...</div>
      <table
        v-else
        class="min-w-full  shadow rounded overflow-hidden"
      >
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Form ID</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Created</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="lead in leads"
            :key="lead.id"
            class="border-t"
          >
            <td class="px-4 py-2 text-sm">{{ lead.id }}</td>
            <td class="px-4 py-2 text-sm">{{ lead.form_id }}</td>
            <td class="px-4 py-2 text-sm">
              {{ new Date(lead.created_at).toLocaleString() }}
            </td>
          </tr>
        </tbody>
      </table>
    </section>

    <section>
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">Forms</h2>
        <button
          class="px-3 py-2 text-sm rounded bg-blue-600  hover:bg-blue-700"
          @click="newForm"
        >
          New Form
        </button>
      </div>
      <div v-if="loadingForms">Loading forms...</div>
      <table
        v-else
        class="min-w-full  shadow rounded overflow-hidden mb-4"
      >
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">ID</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Name</th>
            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Active</th>
            <th class="px-4 py-2" />
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="form in forms"
            :key="form.id"
            class="border-t"
          >
            <td class="px-4 py-2 text-sm">{{ form.id }}</td>
            <td class="px-4 py-2 text-sm">{{ form.name }}</td>
            <td class="px-4 py-2 text-sm">
              <span
                class="px-2 py-1 rounded-full text-xs"
                :class="form.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
              >
                {{ form.is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-4 py-2 text-right">
              <button
                class="px-3 py-1 text-xs rounded bg-blue-600  hover:bg-blue-700"
                @click="editForm(form)"
              >
                Edit
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <FormBuilder
        v-if="showBuilder"
        :form="editingForm"
        @saved="handleFormSaved"
        @cancel="showBuilder = false"
      />
    </section>
  </div>
</template>


