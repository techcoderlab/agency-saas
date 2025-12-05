<script setup>
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../utils/request'

const route = useRoute()
const router = useRouter()
const lead = ref(null)
const loading = ref(true)
const noteContent = ref('')

const fetchLead = async () => {
  try {
    const { data } = await api.get(`/leads/${route.params.id}`)
    lead.value = data
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

const updateLead = async () => {
    try {
        await api.put(`/leads/${lead.value.id}`, {
            status: lead.value.status,
            temperature: lead.value.temperature
        })
        // Optional: Add a toast notification here
    } catch (e) {
        alert('Failed to save')
    }
}

const addNote = async () => {
    if(!noteContent.value) return
    try {
        const { data } = await api.post(`/leads/${lead.value.id}/note`, { content: noteContent.value })
        lead.value.activities.unshift(data)
        noteContent.value = ''
    } catch (e) {
        alert('Failed to add note')
    }
}

// Helper to map backend colors to Tailwind classes
const getColorClasses = (color) => {
    const map = {
        blue: 'bg-blue-50 text-blue-700 border-blue-200',
        yellow: 'bg-yellow-50 text-yellow-700 border-yellow-200',
        green: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        red: 'bg-red-50 text-red-700 border-red-200',
        gray: 'bg-slate-100 text-slate-600 border-slate-200',
        orange: 'bg-orange-50 text-orange-700 border-orange-200',
        purple: 'bg-purple-50 text-purple-700 border-purple-200',
    }
    return map[color] || map.gray
}

const statusColor = computed(() => {
    if (!lead.value) return ''
    
    // Use dynamic config if available
    if (lead.value.crm_config?.statuses) {
        const statusConfig = lead.value.crm_config.statuses.find(s => s.slug === lead.value.status)
        if (statusConfig) {
            return getColorClasses(statusConfig.color)
        }
    }

    // Fallback for safety
    switch(lead.value.status) {
        case 'new': return 'bg-blue-50 text-blue-700 border-blue-200'
        case 'contacted': return 'bg-yellow-50 text-yellow-700 border-yellow-200'
        case 'closed': return 'bg-emerald-50 text-emerald-700 border-emerald-200'
        default: return 'bg-slate-100 text-slate-600 border-slate-200'
    }
})

const tempColor = computed(() => {
    switch(lead.value?.temperature) {
        case 'hot': return 'text-red-600 bg-red-50 border-red-200'
        case 'warm': return 'text-orange-600 bg-orange-50 border-orange-200'
        default: return 'text-slate-600 bg-slate-100 border-slate-200'
    }
})

// Helper to format payload keys (e.g., "first_name" -> "First Name")
const formatKey = (key) => {
    return key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

// Available statuses from config or default
const availableStatuses = computed(() => {
    if (lead.value?.crm_config?.statuses) {
        return lead.value.crm_config.statuses
    }
    return [
        { slug: 'new', label: 'New' },
        { slug: 'contacted', label: 'Contacted' },
        { slug: 'closed', label: 'Closed' }
    ]
})

onMounted(fetchLead)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <div class="flex items-center gap-3">
            <button @click="router.back()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Lead Details</h2>
        </div>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 ml-8">View and manage lead information and activity.</p>
      </div>
      
      <div v-if="lead" class="font-mono text-xs text-slate-500 bg-slate-100 dark:bg-slate-800 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700">
        ID: {{ lead.id }}
      </div>
    </div>

    <div v-if="loading" class="text-slate-500 py-12 text-center">Loading...</div>

    <div v-else class="space-y-6">
        
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex justify-between items-center">
                <span class="font-semibold text-slate-700 dark:text-slate-300">Submission Details</span>
                <span class="text-xs text-slate-500">{{ new Date(lead.created_at).toLocaleString() }}</span>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-6 mb-6 pb-6 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <span class="block text-xs font-bold text-slate-500 uppercase mb-1">Source</span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                            {{ lead.source || 'Unknown' }}
                        </span>
                    </div>
                    <div v-if="lead.form">
                        <span class="block text-xs font-bold text-slate-500 uppercase mb-1">Form</span>
                        <span class="text-sm font-medium text-blue-600 dark:text-blue-400">
                            {{ lead.form.name }}
                        </span>
                    </div>
                    <div v-if="lead.meta_data?.ip_address">
                        <span class="block text-xs font-bold text-slate-500 uppercase mb-1">IP Address</span>
                        <span class="text-sm text-slate-600 dark:text-slate-400 font-mono">{{ lead.meta_data.ip_address }}</span>
                    </div>
                </div>

                <div v-if="lead.payload && Object.keys(lead.payload).length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="(value, key) in lead.payload" :key="key" class="group">
                        <dt class="text-xs font-bold text-slate-500 uppercase mb-1 group-hover:text-blue-600 transition-colors">{{ formatKey(key) }}</dt>
                        <dd class="text-sm text-slate-900 dark:text-white break-words">{{ value || '-' }}</dd>
                    </div>
                </div>
                <div v-else class="text-sm text-slate-400 italic">
                    No form data available for this submission.
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 font-semibold text-slate-700 dark:text-slate-300">
                        Lead Management
                    </div>
                    
                    <div class="p-6 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Status</label>
                            <div class="relative">
                                <select v-model="lead.status" @change="updateLead" 
                                    class="w-full appearance-none bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 pr-8">
                                    <option v-for="status in availableStatuses" :key="status.slug" :value="status.slug">
                                        {{ status.label }}
                                    </option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                            <div class="mt-2">
                                <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors', statusColor]">
                                    {{ availableStatuses.find(s => s.slug === lead.status)?.label || lead.status }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Temperature</label>
                            <div class="relative">
                                <select v-model="lead.temperature" @change="updateLead" 
                                    class="w-full appearance-none bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 pr-8">
                                    <option value="cold">Cold</option>
                                    <option value="warm">Warm</option>
                                    <option value="hot">Hot</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </div>
                            </div>
                             <div class="mt-2">
                                 <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border transition-colors', tempColor]">
                                    {{ lead.temperature.charAt(0).toUpperCase() + lead.temperature.slice(1) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2 space-y-6">
                
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm p-4">
                    <textarea 
                        v-model="noteContent" 
                        class="w-full border border-slate-200 dark:border-slate-700 rounded-lg p-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 transition-shadow" 
                        rows="3" 
                        placeholder="Write a note about this lead..."
                    ></textarea>
                    <div class="flex justify-end mt-3">
                        <button 
                            @click="addNote" 
                            :disabled="!noteContent"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium dark:text-black bg-white border hover:bg-slate-50 shadow-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            Post Note
                        </button>
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 font-semibold text-slate-700 dark:text-slate-300 flex justify-between items-center">
                        <span>Activity Feed</span>
                        <span class="text-xs font-normal text-slate-500">{{ lead.activities.length }} events</span>
                    </div>
                    
                    <div class="divide-y divide-slate-200 dark:divide-slate-800 max-h-[600px] overflow-y-auto">
                        <div v-if="lead.activities.length === 0" class="p-8 text-center text-slate-500 text-sm italic">
                            No activity recorded yet.
                        </div>

                        <div v-for="activity in lead.activities" :key="activity.id" class="p-4 hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors group">
                            <div class="flex items-start gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide border bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700">
                                            {{ activity.type }}
                                        </span>
                                        <span class="text-xs text-slate-400 dark:text-slate-500 whitespace-nowrap">
                                            {{ new Date(activity.created_at).toLocaleString() }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap">{{ activity.content }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>