<template>

<div class="space-y-6">
      <!-- <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col transition-all hover:border-slate-300 dark:hover:border-slate-700">
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Agents</span>
              <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.total }}</span>
              </div>
          </div>
          <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col transition-all hover:border-slate-300 dark:hover:border-slate-700">
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Sessions</span>
              <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.active }}</span>
                  <span class="text-xs font-medium text-emerald-600 bg-emerald-100 px-1.5 py-0.5 rounded-full">Live</span>
              </div>
          </div>
          <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col transition-all hover:border-slate-300 dark:hover:border-slate-700">
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Messages (24h)</span>
              <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ stats.messages_today }}</span>
              </div>
          </div>
          <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col transition-all hover:border-slate-300 dark:hover:border-slate-700">
              <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Backend</span>
              <div class="flex items-baseline gap-2 mt-1">
                  <span class="text-2xl font-bold text-slate-900 dark:text-white">n8n</span>
                  <span class="text-xs text-slate-400">Connected</span>
              </div>
          </div>
      </div> -->
  
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">AI Agents</h2>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage, train, and chat with your AI workforce.</p>
        </div>
        
        <div class="flex items-center gap-3">
          <button @click="openModal()" 
            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100">
             <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
             Create Agent
          </button>
          <button @click="loadChats" class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium dark:text-slate-200 bg-white dark:bg-slate-950 border dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
          </button>
        </div>
      </div>
  
      <div class="bg-white dark:bg-slate-950 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm">
           <div class="relative w-full">
               <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
              </div>
               <input v-model="search" type="text" placeholder="Search agents by name..." class="block w-full pl-10 pr-3 py-2 border rounded-lg text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-500 focus:border-transparent">
           </div>
      </div>
  
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="chat in filteredChats" :key="chat.id" 
             class="group bg-white dark:bg-slate-950 rounded-xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col h-full">
          
          <div class="flex items-start justify-between mb-4">
              <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300 font-bold text-xl border border-slate-200 dark:border-slate-700">
                      {{ chat.name[0].toUpperCase() }}
                  </div>
                  <div>
                      <h3 class="font-bold text-slate-800 dark:text-slate-100 text-lg leading-tight">{{ chat.name }}</h3>
                      <span class="text-xs text-slate-500">Updated {{ new Date(chat.updated_at || chat.created_at).toLocaleDateString() }}</span>
                  </div>
              </div>
              <div class="relative">
                <div class="flex items-center gap-1.5 mt-1">
                    <span class="flex h-2 w-2 relative">
                    <span v-if="statuses[chat.id] === 'active'" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span :class="{
                        'bg-emerald-500': statuses[chat.id] === 'active',
                        'bg-red-500': statuses[chat.id] === 'inactive',
                        'bg-slate-300': !statuses[chat.id]
                    }" class="relative inline-flex rounded-full h-2 w-2"></span>
                    </span>
                    <span class="text-[10px] font-medium text-slate-500 uppercase tracking-wide">
                        {{ statuses[chat.id] === 'active' ? 'Online' : (statuses[chat.id] === 'inactive' ? 'Offline' : 'Checking...') }}
                    </span>
                </div>
              </div>
          </div>
  
          <div class="flex-1 space-y-3 mb-6">
               <div class="bg-slate-50 dark:bg-slate-900/50 rounded-lg p-2 border border-slate-100 dark:border-slate-800">
                   <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Webhook</div>
                   <div class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300">
                       <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                       {{ chat.webhook_url }}
                   </div>
               </div>
          </div>
  
          <div class="flex items-center gap-2 mt-auto">
              <router-link :to="`/admin/ai-chats/${chat.id}`" 
                  class="flex-1 inline-flex justify-center items-center px-4 py-2.5 text-sm font-semibold text-white bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-slate-200 rounded-lg transition-colors shadow-sm">
                  Open Chat
              </router-link>
              
              <button @click="openModal(chat)" class="p-2.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors border border-transparent hover:border-blue-100 dark:hover:border-blue-800">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
              </button>
              <button @click="deleteChat(chat.id)" class="p-2.5 text-slate-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors border border-transparent hover:border-red-100 dark:hover:border-red-800">
                   <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
              </button>
          </div>
        </div>
      </div>
  
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog">
          <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
          <div class="relative w-full max-w-lg bg-white dark:bg-slate-950 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col overflow-hidden">
              <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                  <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ isEditing ? 'Edit Agent Configuration' : 'Create New Agent' }}</h3>
                  <p class="text-xs text-slate-500 mt-1">Configure connection details for your AI agent.</p>
              </div>
              
              <form @submit.prevent="saveChat" class="p-6 space-y-5">
                  <div>
                      <label class="block text-xs font-bold text-slate-500 mb-1.5">Agent Name <span class="text-red-500">*</span></label>
                      <input v-model="form.name" type="text" placeholder="e.g. Sales Assistant" class="w-full border rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-900 outline-none transition-shadow" required>
                  </div>
                  <div>
                      <label class="block text-xs font-bold text-slate-500 mb-1.5">Webhook URL <span class="text-red-500">*</span></label>
                      <input v-model="form.webhook_url" type="url" placeholder="https://primary.n8n.cloud/webhook/..." class="w-full border rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-900 outline-none transition-shadow" required>
                      <p class="text-[10px] text-slate-400 mt-1">The endpoint where the agent will send prompts and files.</p>
                  </div>
                  <div>
                      <label class="block text-xs font-bold text-slate-500 mb-1.5">Authorization Header (Optional)</label>
                      <input v-model="form.webhook_secret" type="password" placeholder="Bearer sk-..." class="w-full border rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-900 outline-none transition-shadow">
                  </div>
                  <div>
                      <label class="block text-xs font-bold text-slate-500 mb-1.5">Welcome Message</label>
                      <textarea v-model="form.welcome_message" rows="3" placeholder="Hello! I can help you analyze documents. Upload a file to start." class="w-full border rounded-lg p-2.5 text-sm dark:bg-slate-900 dark:border-slate-700 dark:text-white focus:ring-2 focus:ring-slate-900 outline-none transition-shadow"></textarea>
                  </div>
              </form>
  
              <div class="p-6 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/50 flex justify-end gap-3">
                  <button @click="showModal = false" class="text-slate-600 dark:text-slate-400 text-sm font-medium hover:text-slate-900 dark:hover:text-white px-4 py-2 transition-colors">Cancel</button>
                  <button @click="saveChat" class="bg-slate-900 text-white text-sm font-bold px-6 py-2 rounded-lg hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 transition-colors shadow-lg shadow-slate-900/20">
                      {{ isEditing ? 'Save Changes' : 'Create Agent' }}
                  </button>
              </div>
          </div>
        </div>
      </Transition>
    </div>
  </template>
  
  <script setup>
  import { ref, onMounted, computed } from 'vue';
  import request from '@/utils/request';
  
  const chats = ref([]);
  const showModal = ref(false);
  const isEditing = ref(false);
  const form = ref({ name: '', webhook_url: '', webhook_secret: '', welcome_message: '' });
  const search = ref('');
  const statuses = ref({}); // Store status by ID

  
    const filteredChats = computed(() => {
        if(!search.value) return chats.value;
        return chats.value.filter(c => c.name.toLowerCase().includes(search.value.toLowerCase()));
    });

    onMounted(loadChats);

    async function loadChats() {
    try {
        const { data } = await request.get('/ai-chats');
        chats.value = data;
        // Trigger status checks for all
        checkAllStatuses();
    } catch(e) { console.error(e); }
    }

    async function checkAllStatuses() {
        chats.value.forEach(async (chat) => {
            try {
                // Lazy check
                const { data } = await request.get(`/ai-chats/${chat.id}/status`);
                statuses.value[chat.id] = data.status;
            } catch {
                statuses.value[chat.id] = 'inactive';
            }
        });
    }
  
  function openModal(chat = null) {
    isEditing.value = !!chat;
    form.value = chat ? { ...chat } : { name: '', webhook_url: '', webhook_secret: '', welcome_message: '' };
    showModal.value = true;
  }
  
  async function saveChat() {
    try {
        if (isEditing.value) {
          await request.put(`/ai-chats/${form.value.id}`, form.value);
        } else {
          await request.post('/ai-chats', form.value);
        }
        showModal.value = false;
        loadChats();
    } catch (e) {
        alert('Failed to save agent details.');
    }
  }
  
  async function deleteChat(id) {
    if(confirm('Are you sure you want to delete this agent?')) {
      await request.delete(`/ai-chats/${id}`);
      loadChats();
    }
  }
  </script>
  
  <style scoped>
  .modal-enter-active,
  .modal-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
  }
  .modal-enter-from,
  .modal-leave-to {
    opacity: 0;
    transform: scale(0.95);
  }
  </style>