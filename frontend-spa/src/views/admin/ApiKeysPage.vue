<script setup>
    import { onMounted, ref } from 'vue'
    import api from '../../utils/request'
    import CopyButton from '../../components/ui/CopyButton.vue'
    import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
    
    const keys = ref([])
    const loading = ref(false)
    const showCreateModal = ref(false)
    const showSuccessModal = ref(false)
    const processing = ref(false)
    
    // Form State
    const isEditing = ref(false)
    const editingId = ref(null)
    // We store the current key being edited/viewed to calculate expiry logic in modal
    const currentKey = ref(null) 
    
    const form = ref({
        name: '',
        abilities: [],
        expiration_days: 90
    })
    
    const newKeyToken = ref('')
    const permissionGroups = ref([])
    
    const expiryOptions = [
        { label: '30 Days', value: 30 },
        { label: '60 Days', value: 60 },
        { label: '90 Days', value: 90 },
        { label: 'Never Expires', value: null }
    ]
    
    const fetchKeys = async () => {
        loading.value = true
        try {
            const { data } = await api.get('/api-keys')
            keys.value = data.keys
            permissionGroups.value = data.permission_groups
        } catch (e) {
            console.error(e)
        } finally {
            loading.value = false
        }
    }
    
    const openCreateModal = () => {
        isEditing.value = false
        editingId.value = null
        currentKey.value = null
        form.value = {
            name: '',
            abilities: ['leads:read'], 
            expiration_days: 90
        }
        showCreateModal.value = true
    }
    
    const openEditModal = (key) => {
        isEditing.value = true
        editingId.value = key.id
        currentKey.value = key
        form.value = {
            name: key.name,
            abilities: [...key.abilities],
            // Expiration is not editable
        }
        showCreateModal.value = true
    }
    
    const saveKey = async () => {
        if (!form.value.name) return
        processing.value = true
        
        try {
            if (isEditing.value) {
                // Update (Name/Scope only)
                const payload = {
                    name: form.value.name,
                    abilities: form.value.abilities
                }
                const { data } = await api.put(`/api-keys/${editingId.value}`, payload)
                
                const index = keys.value.findIndex(k => k.id === editingId.value)
                if (index !== -1) keys.value[index] = data.entry
                
                showCreateModal.value = false
            } else {
                // Create
                const { data } = await api.post('/api-keys', form.value)
                keys.value.unshift(data.entry)
                newKeyToken.value = data.token
                showCreateModal.value = false
                showSuccessModal.value = true
            }
        } catch (e) {
            alert(`Failed: ` + (e.response?.data?.message || e.message))
        } finally {
            processing.value = false
        }
    }
    
    const rotateKey = async (key) => {
        if (!confirm(`Rotate key "${key.name}"?\n\nThis will DELETE the old key immediately and generate a new one. Any systems using the old key will stop working until updated.`)) return
        
        processing.value = true
        try {
            const { data } = await api.post(`/api-keys/${key.id}/rotate`)
            
            // Update list: Remove old, Add new (Rotation creates a new ID usually)
            keys.value = keys.value.filter(k => k.id !== key.id)
            keys.value.unshift(data.entry)
            
            // Close Edit Modal if open
            showCreateModal.value = false
            
            // Show Secret
            newKeyToken.value = data.token
            showSuccessModal.value = true
        } catch (e) {
            alert('Failed to rotate key: ' + (e.response?.data?.message || e.message))
        } finally {
            processing.value = false
        }
    }
    
    const revokeKey = async (key) => {
        if (!confirm(`Revoke "${key.name}"? This cannot be undone.`)) return
        try {
            await api.delete(`/api-keys/${key.id}`)
            keys.value = keys.value.filter(k => k.id !== key.id)
            if(isEditing.value) showCreateModal.value = false
        } catch (e) {
            alert('Failed to revoke key')
        }
    }
    
    // --- Logic for 10% Time Remaining ---
    const getExpiryStatus = (key) => {
        if (!key.expires_at) return { label: 'Never Expires', class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }
        
        const created = new Date(key.created_at).getTime()
        const expires = new Date(key.expires_at).getTime()
        const now = new Date().getTime()
        
        if (now > expires) return { label: 'Expired', class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }
        
        const totalLifespan = expires - created
        const remaining = expires - now
        const percentage = (remaining / totalLifespan) * 100
        
        // Format Date
        const dateStr = new Date(key.expires_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
        
        if (percentage <= 10) {
            return { label: `Expires ${dateStr}`, class: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800' }
        }
        
        return { label: `Expires ${dateStr}`, class: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800' }
    }
    
    const formatDate = (date) => {
        if (!date) return 'Never'
        return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
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
          <button @click="openCreateModal" 
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
                            <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Permissions</th>
                            <th class="px-6 py-4 font-semibold text-slate-700 dark:text-slate-300">Status</th>
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
                                <div class="flex flex-wrap gap-1.5 max-w-xs">
                                    <span v-if="key.abilities.includes('*')" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                        Full Access
                                    </span>
                                    <span v-else v-for="ability in key.abilities.slice(0, 2)" :key="ability" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 border border-slate-200 dark:border-slate-700">
                                        {{ ability }}
                                    </span>
                                    <span v-if="key.abilities.length > 2" class="text-xs text-slate-400">+{{ key.abilities.length - 2 }} more</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 align-middle">
                                 <span :class="['px-2.5 py-1 rounded-full text-xs font-medium border', getExpiryStatus(key).class]">
                                    {{ getExpiryStatus(key).label }}
                                 </span>
                            </td>
                            <td class="px-6 py-4 align-middle text-slate-500 text-xs">
                                {{ formatDate(key.last_used_at) }}
                            </td>
                            <td class="px-6 py-4 align-middle text-slate-500 text-xs">
                                {{ formatDate(key.created_at) }}
                            </td>
                            <td class="px-6 py-4 align-middle text-right">
                                <div class="flex justify-end items-center gap-2 transition-opacity">
                                    <button @click="rotateKey(key)" title="Rotate Token" class="p-1.5 text-slate-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    </button>
                                    <button @click="openEditModal(key)" class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                                        Edit
                                    </button>
                                    <button @click="revokeKey(key)" class="text-slate-400 hover:text-red-600 transition-colors text-sm font-medium">
                                        Revoke
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
        <TransitionRoot appear :show="showCreateModal" as="template">
            <Dialog as="div" @close="showCreateModal = false" class="relative z-50">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 p-6 text-left shadow-xl transition-all border border-slate-200 dark:border-slate-800 flex flex-col max-h-[90vh]">
                            
                            <div class="flex justify-between items-start mb-6">
                                <DialogTitle as="h3" class="text-lg font-bold leading-6 text-slate-900 dark:text-white">
                                    {{ isEditing ? 'Edit API Key' : 'Create New API Key' }}
                                </DialogTitle>
                                <button v-if="isEditing" @click="rotateKey(currentKey)" class="text-xs flex items-center gap-1.5 text-orange-600 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/20 dark:text-orange-400 dark:hover:bg-orange-900/40 px-3 py-1.5 rounded-lg transition-colors font-medium border border-orange-200 dark:border-orange-900">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                    Rotate Token
                                </button>
                            </div>
                            
                            <div class="space-y-6 overflow-y-auto pr-2 custom-scrollbar">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Key Name</label>
                                        <input v-model="form.name" type="text" placeholder="e.g. Zapier Integration" class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:text-white" />
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                            {{ isEditing ? 'Current Status' : 'Token Expires' }}
                                        </label>
                                        
                                        <select v-if="!isEditing" v-model="form.expiration_days" class="block w-full rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:text-white">
                                            <option v-for="opt in expiryOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                                        </select>
    
                                        <div v-else class="flex items-center h-[38px]">
                                            <span :class="['inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border', getExpiryStatus(currentKey).class]">
                                                {{ getExpiryStatus(currentKey).label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
    
                                <div>
                                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Permissions Scope</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-60 overflow-y-auto custom-scrollbar p-1 border border-slate-100 dark:border-slate-800/50 rounded-lg">
                                        <div v-for="group in permissionGroups" :key="group.name" class="p-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 h-fit">
                                            <h4 class="text-xs font-bold uppercase text-slate-500 mb-3 sticky top-0 bg-inherit z-10">{{ group.name }}</h4>
                                            <div class="space-y-3">
                                                <div v-for="scope in group.scopes" :key="scope.id" class="flex items-start">
                                                    <div class="flex h-5 items-center">
                                                        <input :id="scope.id" v-model="form.abilities" :value="scope.id" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-800" />
                                                    </div>
                                                    <div class="ml-3">
                                                        <label :for="scope.id" class="text-sm font-medium text-slate-900 dark:text-slate-200 block">{{ scope.label }}</label>
                                                        <span class="text-[10px] text-slate-500 dark:text-slate-400 leading-tight block">{{ scope.desc }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <div class="mt-6 flex justify-end gap-3 shrink-0 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <button type="button" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 rounded-lg dark:text-slate-300 dark:hover:bg-slate-800" @click="showCreateModal = false">Cancel</button>
                                <button type="button" :disabled="!form.name || processing" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm disabled:opacity-50" @click="saveKey">
                                    {{ processing ? 'Saving...' : (isEditing ? 'Update Key' : 'Generate Key') }}
                                </button>
                            </div>
    
                        </DialogPanel>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    
        <TransitionRoot appear :show="showSuccessModal" as="template">
            <Dialog as="div" @close="showSuccessModal = false" class="relative z-50">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" />
                <div class="fixed inset-0 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4 text-center">
                        <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-white dark:bg-slate-900 p-6 text-left align-middle shadow-xl transition-all border border-green-200 dark:border-green-900">
                            <div class="flex items-center gap-3 mb-4 text-green-600 dark:text-green-400">
                                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-full">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                </div>
                                <DialogTitle as="h3" class="text-lg font-bold leading-6">
                                    API Key Ready
                                </DialogTitle>
                            </div>
                            
                            <div class="mt-2">
                                <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
                                    This is your new secret key. <span class="font-bold text-red-500">Copy it now, it won't be shown again.</span>
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
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
      </div>
    </template>