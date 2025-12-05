<script setup>
import { onMounted, ref, computed } from 'vue'
import api from '../../utils/request'
import CopyButton from '../../components/ui/CopyButton.vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const keys = ref([])
const loading = ref(false)
const showCreateModal = ref(false)
const showSuccessModal = ref(false)
const processing = ref(false)

// New Key Form State
const newKeyName = ref('')
const selectedAbilities = ref(['leads:read', 'forms:read'])
const newKeyToken = ref('') // Stores the raw token temporarily

// Available Permissions Configuration
const availableAbilities = [
    { id: 'leads:read', label: 'Read Leads', description: 'View lead details and lists' },
    { id: 'leads:write', label: 'Write Leads', description: 'Create and update leads' },
    { id: 'forms:read', label: 'Read Forms', description: 'View form configurations' },
    { id: 'forms:write', label: 'Write Forms', description: 'Create and edit forms' },
]

const fetchKeys = async () => {
    loading.value = true
    try {
        const { data } = await api.get('/api-keys')
        keys.value = data
    } catch (e) {
        console.error(e)
    } finally {
        loading.value = false
    }
}

const createKey = async () => {
    if (!newKeyName.value) return
    processing.value = true
    try {
        const { data } = await api.post('/api-keys', {
            name: newKeyName.value,
            abilities: selectedAbilities.value
        })
        
        // Add to list immediately
        keys.value.unshift(data.entry)
        
        // Show Secret
        newKeyToken.value = data.token
        showCreateModal.value = false
        showSuccessModal.value = true
        
        // Reset Form
        newKeyName.value = ''
        selectedAbilities.value = ['leads:read', 'forms:read']
    } catch (e) {
        alert('Failed to create key: ' + (e.response?.data?.message || e.message))
    } finally {
        processing.value = false
    }
}

const revokeKey = async (key) => {
    if (!confirm(`Are you sure you want to revoke the key "${key.name}"? This application will verify immediately stop working.`)) return
    try {
        await api.delete(`/api-keys/${key.id}`)
        keys.value = keys.value.filter(k => k.id !== key.id)
    } catch (e) {
        alert('Failed to revoke key')
    }
}

// Helpers
const formatDate = (date) => {
    if (!date) return 'Never'
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
}

const isRecent = (date) => {
    if (!date) return false
    const diff = new Date() - new Date(date)
    return diff < 86400000 // 24 hours
}

onMounted(fetchKeys)
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">API Keys</h2>
        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage access tokens for external integrations.</p>
      </div>
      <button @click="showCreateModal = true" 
        class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition-colors">
        + Create New Key
      </button>
    </div>

    <div v-if="loading && keys.length === 0" class="text-slate-500 py-12 text-center">Loading API Keys...</div>

    <div v-else class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-800">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Name</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Permissions (Scopes)</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Last Used</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Created</th>
                        <th class="px-6 py-4 text-right font-semibold text-slate-700 dark:text-slate-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    <tr v-if="keys.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500 italic">
                            No API keys found. Create one to get started.
                        </td>
                    </tr>
                    <tr v-for="key in keys" :key="key.id" class="group hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors">
                        <td class="px-6 py-4 align-middle">
                            <div class="font-medium text-slate-900 dark:text-white flex items-center gap-2">
                                <div class="p-1.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 16.464l-1.414 1.414a1 1 0 01-1.414 0l-1.414-1.414a1 1 0 010-1.414l1.414-1.414L15 7zm3 5a1 1 0 11-2 0 1 1 0 012 0z" /></svg>
                                </div>
                                {{ key.name }}
                            </div>
                        </td>
                        <td class="px-6 py-4 align-middle">
                            <div class="flex flex-wrap gap-1.5 max-w-md">
                                <span v-if="key.abilities.includes('*')" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                    Full Access
                                </span>
                                <span v-else v-for="ability in key.abilities" :key="ability" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                    {{ ability }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 align-middle">
                            <span :class="[
                                'text-xs font-medium px-2 py-0.5 rounded-full',
                                isRecent(key.last_used_at) 
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                    : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                            ]">
                                {{ formatDate(key.last_used_at) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 align-middle text-slate-500 text-xs">
                            {{ formatDate(key.created_at) }}
                        </td>
                        <td class="px-6 py-4 align-middle text-right">
                            <button @click="revokeKey(key)" class="text-slate-400 hover:text-red-600 transition-colors text-sm font-medium">
                                Revoke
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Key Modal -->
    <TransitionRoot appear :show="showCreateModal" as="template">
        <Dialog as="div" @close="showCreateModal = false" class="relative z-50">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                        <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 p-6 text-left align-middle shadow-xl transition-all border border-slate-200 dark:border-slate-800">
                            <DialogTitle as="h3" class="text-lg font-bold leading-6 text-slate-900 dark:text-white">
                                Create New API Key
                            </DialogTitle>
                            
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Key Name</label>
                                    <input v-model="newKeyName" type="text" placeholder="e.g. Zapier Integration" class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:text-white" />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Permissions</label>
                                    <div class="space-y-2 border border-slate-200 dark:border-slate-800 rounded-lg p-3 max-h-48 overflow-y-auto bg-slate-50 dark:bg-slate-900/50">
                                        <div v-for="ability in availableAbilities" :key="ability.id" class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input :id="ability.id" v-model="selectedAbilities" :value="ability.id" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800" />
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label :for="ability.id" class="font-medium text-slate-900 dark:text-slate-200">{{ ability.label }}</label>
                                                <p class="text-slate-500 dark:text-slate-400 text-xs">{{ ability.description }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="inline-flex justify-center rounded-lg border border-transparent px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="showCreateModal = false">
                                    Cancel
                                </button>
                                <button type="button" :disabled="!newKeyName || processing" class="inline-flex justify-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:opacity-50" @click="createKey">
                                    {{ processing ? 'Generating...' : 'Generate Key' }}
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>

    <!-- Success Modal (Show Token) -->
    <TransitionRoot appear :show="showSuccessModal" as="template">
        <Dialog as="div" @close="showSuccessModal = false" class="relative z-50">
            <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100" leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4 text-center">
                    <TransitionChild as="template" enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100" leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95">
                        <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 p-6 text-left align-middle shadow-xl transition-all border border-green-200 dark:border-green-900">
                            <div class="flex items-center gap-3 mb-4 text-green-600 dark:text-green-400">
                                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-full">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <DialogTitle as="h3" class="text-lg font-bold leading-6">
                                    API Key Generated
                                </DialogTitle>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    Please copy your new API key immediately. <span class="font-bold text-red-500">You will not be able to see it again!</span>
                                </p>
                                
                                <div class="relative">
                                    <input type="text" readonly :value="newKeyToken" class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 px-4 py-3 text-sm font-mono text-slate-900 dark:text-white pr-12" />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                        <CopyButton :text="newKeyToken" />
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="button" class="inline-flex justify-center rounded-lg border border-transparent bg-slate-900 dark:bg-white dark:text-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:hover:bg-slate-200 focus:outline-none" @click="showSuccessModal = false">
                                    I have copied it
                                </button>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
  </div>
</template>