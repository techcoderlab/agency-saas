<template>
    <div class="space-y-6">
        <div class="page-header">
            <div><h2 class="page-title">AI Agents</h2><p class="page-subtitle">Manage your AI workforce.</p></div>
            <div class="flex gap-2">
                 <button @click="openModal()" class="btn-primary">+ New Agent</button>
                 <button @click="loadChats" class="btn-secondary">Refresh</button>
            </div>
        </div>
        
        <div class="card p-4"><input v-model="search" type="text" placeholder="Search agents..." class="form-input pl-4"></div>
    
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <div v-for="chat in filteredChats" :key="chat.id" class="card p-6 flex flex-col h-full hover:shadow-md transition-shadow">
                <div class="flex justify-between items-baseline mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center font-bold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">{{ chat.name[0] }}</div>
                        <div><h3 class="font-bold text-slate-900 dark:text-white">{{ chat.name }}</h3></div>
                    </div>
                    <span :class="['w-2 h-2 rounded-full', statuses[chat.id]==='active'?'bg-emerald-500':'bg-red-500']"></span>
                </div>
                <div class="flex-1 mb-6">
                    <label class="form-label mt-2">Agent Integration</label>
                    <div class="text-xs font-mono bg-slate-50 dark:bg-slate-950 p-2 rounded border border-slate-100 dark:border-slate-800 break-all">{{ chat.webhook_url }}</div>
                </div>

                <div class="flex gap-2 mt-auto">
                    <router-link :to="`/admin/ai-chats/${chat.id}`" class="btn-primary flex-1">Chat</router-link>
                    <button @click="openModal(chat)" class="btn-icon"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg></button>
                    <button @click="deleteChat(chat.id)" class="btn-icon text-red-500 hover:bg-red-50"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                </div>
            </div>
        </div>
    
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="card w-full max-w-lg">
                    <div class="card-header"><h3 class="card-title">{{ isEditing ? 'Edit' : 'Create' }} Agent</h3></div>
                    <div class="card-body space-y-4">
                        <div class="form-group"><label class="form-label">Name</label><input v-model="form.name" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Webhook URL</label><input v-model="form.webhook_url" type="url" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Secret</label><input v-model="form.webhook_secret" type="password" autocomplete="new-password" class="form-input"></div>
                        <div class="form-group"><label class="form-label">Welcome Message</label><textarea v-model="form.welcome_message" rows="3" class="form-textarea"></textarea></div>
                    </div>
                    <div class="p-6 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-2">
                        <button @click="showModal=false" class="btn-secondary">Cancel</button>
                        <button @click="saveChat" class="btn-primary">Save</button>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
    </template>
    
    <script setup>
    import { ref, onMounted, computed } from 'vue';
    import request from '@/utils/request';
    
    const chats = ref([]); const showModal = ref(false); const isEditing = ref(false); const search = ref(''); const statuses = ref({});
    const form = ref({ name: '', webhook_url: '', webhook_secret: '', welcome_message: '' });
    
    const filteredChats = computed(() => !search.value ? chats.value : chats.value.filter(c => c.name.toLowerCase().includes(search.value.toLowerCase())));
    onMounted(loadChats);
    async function loadChats() { try { const {data}=await request.get('/ai-chats'); chats.value=data; chats.value.forEach(async c => { try{const{data}=await request.get(`/ai-chats/${c.id}/status`);statuses.value[c.id]=data.status}catch{statuses.value[c.id]='inactive'} }) } catch(e){} }
    function openModal(c=null) { isEditing.value=!!c; form.value=c?{...c}:{name:'',webhook_url:'',webhook_secret:'',welcome_message:''}; showModal.value=true }
    async function saveChat() { try { isEditing.value ? await request.put(`/ai-chats/${form.value.id}`,form.value) : await request.post('/ai-chats',form.value); showModal.value=false; loadChats() } catch(e){ alert('Failed') } }
    async function deleteChat(id) { if(confirm('Delete?')) { await request.delete(`/ai-chats/${id}`); loadChats() } }
    </script>