<script setup>
import { onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { z } from 'zod'
import api from '../../utils/request'
import PublicFormRenderer from '../../components/PublicFormRenderer.vue'

const route = useRoute()

const loading = ref(false)
const submitting = ref(false)
const form = ref(null)
const values = ref({})
const successMessage = ref('')
const errorMessage = ref('')
const fieldErrors = ref({})

const fetchForm = async () => {
  loading.value = true
  errorMessage.value = ''
  try {
    const { data } = await api.get(`/public/form/${route.params.uuid}`)
    form.value = data
    values.value = {}
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Form not found'
  } finally {
    loading.value = false
  }
}

const submit = async () => {
  submitting.value = true
  successMessage.value = ''
  errorMessage.value = ''
  fieldErrors.value = {}

  if (form.value?.schema) {
    try {
      const shape = {}
      ;(form.value.schema || []).forEach((field) => {
        if (!field.name) return
        let schema = z.any()

        if (['text', 'textarea'].includes(field.type)) {
          schema = z.string()
        } else if (field.type === 'email') {
          schema = z.string().email()
        } else if (field.type === 'number' || field.type === 'range') {
          schema = z.number().or(z.string().transform((val) => Number(val)))
        } else if (field.type === 'select' && field.multiple) {
          schema = z.array(z.string())
        } else if (field.type === 'select') {
          schema = z.string()
        } else if (field.type === 'radio-group') {
          schema = z.string()
        } else if (field.type === 'checkbox-group') {
          schema = z.array(z.string())
        } else {
          schema = z.any()
        }

        if (field.required) {
          schema = schema.refine((val) => {
            if (Array.isArray(val)) return val.length > 0
            return val !== null && val !== undefined && val !== ''
          }, { message: 'This field is required.' })
        } else {
          schema = schema.optional()
        }

        if (field.min && typeof field.min === 'number' && ['text', 'textarea'].includes(field.type)) {
          schema = schema.refine((val) => typeof val === 'string' && val.length >= field.min, {
            message: `Minimum length is ${field.min}`,
          })
        }

        if (field.max && typeof field.max === 'number' && ['text', 'textarea'].includes(field.type)) {
          schema = schema.refine((val) => typeof val === 'string' && val.length <= field.max, {
            message: `Maximum length is ${field.max}`,
          })
        }

        shape[field.name] = schema
      })

      const validator = z.object(shape)
      validator.parse(values.value)
    } catch (e) {
      if (e instanceof z.ZodError) {
        const errors = {}
        e.issues.forEach((issue) => {
          const key = issue.path[0]
          if (!errors[key]) {
            errors[key] = issue.message
          }
        })
        fieldErrors.value = errors
        submitting.value = false
        return
      }
    }
  }
  try {
    await api.post(`/public/form/${route.params.uuid}/submit`, values.value)
    successMessage.value = 'Thank you! Your response has been recorded.'
    values.value = {}
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Submission failed'
  } finally {
    submitting.value = false
  }
}

onMounted(fetchForm)
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
          <div class="w-full max-w-xl bg-slate-900 text-slate-100 shadow-xl rounded-2xl border border-slate-800 p-6">
      <div v-if="loading">
        Loading form...
      </div>
      <div v-else-if="!form">
        <p class="text-red-600">
          {{ errorMessage || 'Form not available.' }}
        </p>
      </div>
      <div v-else>
        <h1 class="text-2xl font-semibold mb-4 text-slate-50">
          {{ form.name }}
        </h1>

        <PublicFormRenderer
          v-model="values"
          :schema="form.schema"
          :errors="fieldErrors"
        />

        <p
          v-if="successMessage"
          class="mt-3 text-sm text-emerald-400"
        >
          {{ successMessage }}
        </p>
        <p
          v-if="errorMessage"
          class="mt-3 text-sm text-red-400"
        >
          {{ errorMessage }}
        </p>

        <button
          class="mt-4 w-full bg-violet-600  py-2 rounded-xl hover:bg-violet-500 disabled:opacity-50"
          :disabled="submitting"
          @click="submit"
        >
          {{ submitting ? 'Submitting...' : 'Submit' }}
        </button>
      </div>
    </div>
  </div>
</template>


