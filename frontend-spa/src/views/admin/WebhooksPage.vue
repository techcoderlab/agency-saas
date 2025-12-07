<script setup>
    import { onMounted, ref, reactive } from 'vue'
    import api from '../../utils/request'
    
    const webhooks = ref([]); const loading = ref(false); const showCreateModal = ref(false);
    const form = reactive({ name: '', url: '', secret: '', events: [] });
    const availableEvents = [ { label: 'Lead Created', value: 'lead.created' }, { label: 'Lead Status Update', value: 'lead.updated.status' }, { label: 'Any Update', value: 'lead.updated' } ];
    
    const fetchWebhooks = async () => { loading.value=true; try { const {data}=await api.get('/webhooks'); webhooks.value=data } catch(e){} finally{loading.value=false} }
    const openCreate = () => { Object.assign(form, {name:'',url:'',secret:'',events:[]}); showCreateModal.value=true }
    const createWebhook = async () => { if(!form.url)return; try{ await api.post('/webhooks',form); showCreateModal.value=false; fetchWebhooks() }catch(e){} }
    const deleteWebhook = async (id) => { if(!confirm('Delete?'))return; try{ await api.delete(`/webhooks/${id}`); fetchWebhooks() }catch(e){} }
    onMounted(fetchWebhooks)
    </script>
    
    <template>
        <div class="space-y-6">
            <div class="page-header">
                <div><h2 class="page-title">Webhooks</h2><p class="page-subtitle">External event triggers.</p></div>
                <button @click="openCreate" class="btn-primary">+ Add Webhook</button>
            </div>
    
            <div v-if="loading" class="text-center py-12 text-slate-500">Loading...</div>
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-if="webhooks.length===0" class="col-span-full py-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-slate-500">No webhooks found.</div>
                <div v-for="hook in webhooks" :key="hook.id" class="card p-5 group">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-3">
                             <div class="p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-lg"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg></div>
                             <div class="flex items-baseline"><h3 class="font-bold text-slate-900 dark:text-white text-sm mr-3">{{ hook.name }}</h3><span v-if="hook.is_active" class="badge badge-green mt-1">Active</span></div>
                        </div>
                        <button @click="deleteWebhook(hook.id)" class="btn-icon text-red-400 hover:text-red-600 hover:bg-red-50"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <div class="space-y-3">
                        <div><label class="form-label">Hook to</label><div class="text-xs font-mono bg-slate-50 dark:bg-slate-950 p-2 rounded border border-slate-100 dark:border-slate-800 break-all">{{ hook.url }}</div></div>
                        <div><label class="form-label">Trigers on</label><div class="flex flex-wrap gap-1 mt-1"><span v-for="ev in hook.events" :key="ev" class="badge badge-slate">{{ ev }}</span></div></div>
                    </div>
                </div>
            </div>
    
            <Transition name="modal">
                <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCreateModal=false"></div>
                    <div class="card w-full max-w-lg relative">
                        <div class="card-header"><span class="card-title">New Webhook</span></div>
                        <div class="card-body space-y-4">
                            <div class="form-group"><label class="form-label">Name</label><input v-model="form.name" class="form-input"></div>
                            <div class="form-group"><label class="form-label">URL</label><input v-model="form.url" type="url" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Secret</label><input v-model="form.secret" class="form-input"></div>
                            <div class="form-group"><label class="form-label">Events</label>
                                <div class="space-y-2 max-h-40 overflow-y-auto border border-slate-200 dark:border-slate-700 rounded p-2">
                                    <label v-for="ev in availableEvents" :key="ev.value" class="flex items-center gap-2"><input type="checkbox" v-model="form.events" :value="ev.value" class="rounded border-slate-300 text-slate-900"><span class="text-sm text-slate-700 dark:text-slate-300">{{ ev.label }}</span></label>
                                </div>
                            </div>
                        </div>
                        <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                            <button @click="showCreateModal=false" class="btn-secondary">Cancel</button>
                            <button @click="createWebhook" class="btn-primary">Create</button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </template>