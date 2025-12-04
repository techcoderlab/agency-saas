<script setup>
import { onMounted, ref, reactive } from 'vue'
import api from '../../utils/request'

const webhooks = ref([])
const loading = ref(false)
const showCreateModal = ref(false)

const form = reactive({
    name: '',
    url: '',
    secret: '',
    events: []
})

// System Wide Events Configuration
const availableEvents = [
    { category: 'Leads', label: 'Lead Created', value: 'lead.created' },
    { category: 'Leads', label: 'Lead Status Update', value: 'lead.updated.status' },
    { category: 'Leads', label: 'Lead Temperature Update', value: 'lead.updated.temperature' },
    { category: 'Leads', label: 'Any Lead Update', value: 'lead.updated' },
    // Future expansion:
    // { category: 'System', label: 'User Created', value: 'user.created' },
]

const fetchWebhooks = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/webhooks')
        webhooks.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const openCreateModal = () => {
    form.name = ''
    form.url = ''
    form.secret = ''
    form.events = []
    showCreateModal.value = true
}

const createWebhook = async () => {
    if (!form.url || form.events.length === 0) return
    try {
        await api.post('/webhooks', form)
        showCreateModal.value = false
        await fetchWebhooks()
    } catch (e) {
        alert('Failed to create webhook')
    }
}

const deleteWebhook = async (id) => {
    if(!confirm('Are you sure you want to delete this webhook?')) return
    try {
        await api.delete(`/webhooks/${id}`)
        await fetchWebhooks()
    } catch (e) {
        console.error(e)
    }
}

onMounted(fetchWebhooks)
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">System Webhooks</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configure external triggers for system events.</p>
            </div>
            <div class="flex items-center gap-3">

                <button 
                    @click="openCreateModal" 
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                >
                    + Add Webhook
                </button>


                <button 
                    @click="fetchWebhooks" 
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium dark:text-black bg-white border hover:bg-slate-50"
                >
                    Refresh
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-if="loading" class="col-span-full py-12 text-center text-slate-500">Loading webhooks...</div>
            <div v-else-if="webhooks.length === 0" class="col-span-full py-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl">
                <p class="text-slate-500">No webhooks configured yet.</p>
                <button @click="openCreateModal" class="mt-2 text-primary font-medium hover:underline">Create your first one</button>
            </div>

            <div v-for="hook in webhooks" :key="hook.id" class="bg-white dark:bg-slate-950 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:border-primary/50 transition-colors group relative">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-2">
                         <div class="p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-lg">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                         </div>
                         <div>
                             <h3 class="font-bold text-slate-900 dark:text-white text-sm">{{ hook.name || 'Unnamed Webhook' }}</h3>
                             <span class="text-[10px] uppercase tracking-wider font-bold text-green-600 bg-green-50 px-2 py-0.5 rounded-full" v-if="hook.is_active">Active</span>
                         </div>
                    </div>
                    <button @click="deleteWebhook(hook.id)" class="text-slate-400 hover:text-red-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Target URL</label>
                        <div class="text-xs font-mono text-slate-600 dark:text-slate-300 break-all bg-slate-50 dark:bg-slate-900 p-1.5 rounded border border-slate-100 dark:border-slate-800 mt-1">
                            {{ hook.url }}
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase">Subscribed Events</label>
                        <div class="flex flex-wrap gap-1.5 mt-1">
                            <span v-for="ev in hook.events" :key="ev" class="text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 px-2 py-1 rounded">
                                {{ ev }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Transition name="modal">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>
                <div class="relative w-full max-w-lg bg-white dark:bg-slate-950 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800">
                        <h3 class="text-lg font-bold text-slate-900 dark:text-white">Add New Webhook</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Name</label>
                            <input v-model="form.name" type="text" placeholder="e.g. Zapier Lead Sync" class="w-full border rounded p-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Payload URL <span class="text-red-500">*</span></label>
                            <input v-model="form.url" type="url" placeholder="https://hooks.zapier.com/..." class="w-full border rounded p-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Secret (Optional)</label>
                            <input v-model="form.secret" type="text" placeholder="Signing Secret for HMAC" class="w-full border rounded p-2 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-2">Trigger Events <span class="text-red-500">*</span></label>
                            <div class="space-y-2 max-h-40 overflow-y-auto p-2 border border-slate-100 dark:border-slate-800 rounded-lg">
                                <label v-for="evt in availableEvents" :key="evt.value" class="flex items-center gap-3 cursor-pointer p-1 hover:bg-slate-50 dark:hover:bg-slate-900 rounded">
                                    <input type="checkbox" :value="evt.value" v-model="form.events" class="rounded border-slate-300 text-primary focus:ring-primary">
                                    <span class="text-sm text-slate-700 dark:text-slate-300">{{ evt.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 rounded-b-xl flex justify-end gap-3">
                        <button @click="showCreateModal = false" class="text-slate-500 text-sm font-medium hover:text-slate-700">Cancel</button>
                        <button @click="createWebhook" :disabled="!form.url || form.events.length === 0" class="bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-slate-800 disabled:opacity-50">Create Webhook</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>