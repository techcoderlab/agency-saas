<template>
    <div class="h-[calc(100vh-100px)] flex flex-col -m-4 sm:-m-8">
      <div class=" border-b border-slate-200 dark:border-slate-800 px-6 py-3 flex items-center justify-between shrink-0 z-10 transition-colors duration-300">
        <div class="flex items-center gap-4">
            <button @click="router.back()" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <div>
              <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                  {{ chatConfig?.name || 'Loading...' }}
                  <span v-if="connectionStatus === 'active'" class="flex h-2 w-2 relative" title="Workflow Active">
                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                      <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                  </span>
                  <span v-else class="h-2 w-2 rounded-full bg-red-500" title="Offline"></span>
              </h2>
              <div class="flex items-center gap-2 text-xs text-slate-500">
                <span v-if="isLoadingMore" class="text-blue-500 animate-pulse">Loading previous messages...</span>
                <span v-else-if="hasMoreMessages" class="text-slate-400">Scroll up for more</span>
                <span v-else class="text-slate-400">History loaded</span>
              </div>
            </div>
        </div>
      </div>
  
      <div class="flex-1 relative overflow-hidden flex flex-col transition-colors duration-300">
        <deep-chat
            ref="deepChatRef"
            v-if="chatConfig && styleConfig"
            
            :demo="false"
            :history.prop="history"
            :connect.prop="requestConfig"
            :requestInterceptor.prop="requestInterceptor"
            :responseInterceptor.prop="handleResponse" 
            :introMessage.prop="introMessage"
            
            :mixedFiles="true"
            :microphone.prop="{
                files: { format: 'mp3', maxDurationSeconds: 120 },
                button: { position: 'outside-right' }
            }"
            
            :messageStyles.prop="styleConfig.messageStyles"
            :textInput.prop="styleConfig.textInput"
            :submitButtonStyles.prop="styleConfig.submitButtonStyles"
            :auxiliaryStyle.prop="styleConfig.auxiliaryStyle"
            :attachmentButtonStyle.prop="styleConfig.attachmentButtonStyle"
            
            class="deep-chat-host"
            style="width:100%; height:100%; border:none; background:transparent;"
        ></deep-chat>
          
          <div v-else class="flex items-center justify-center h-full">
              <div class="animate-pulse flex flex-col items-center">
                  <div class="h-12 w-12 bg-slate-200 rounded-full mb-4"></div>
                  <div class="h-4 w-32 bg-slate-200 rounded"></div>
              </div>
          </div>
      </div>
    </div>
</template>
  
<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import request from '@/utils/request';
import 'deep-chat';

const route = useRoute();
const router = useRouter();
const deepChatRef = ref(null);
const chatConfig = ref(null);
const history = ref([]); 
const chatId = route.params.id;

// Pagination & Status
const nextCursor = ref(null);
const hasMoreMessages = ref(false);
const isLoadingMore = ref(false);
const connectionStatus = ref('checking'); 

const isDark = ref(document.documentElement.classList.contains('dark'));
let observer = null;
let scrollContainer = null;

// --- 1. INFINITE SCROLL LOGIC ---
const setupScrollListener = () => {
    const element = deepChatRef.value;
    if (!element || !element.shadowRoot) return;

    const shadowRoot = element.shadowRoot;
    let scrollRebindTimer = null;

    // Debounced connector
    const connectScroll = () => {
        clearTimeout(scrollRebindTimer);

        scrollRebindTimer = setTimeout(() => {
            const newContainer = shadowRoot.querySelector('#messages');
            if (!newContainer) return;

            // Remove old listener if container changed
            if (scrollContainer && scrollContainer !== newContainer) {
                scrollContainer.removeEventListener('scroll', handleScroll);
            }

            scrollContainer = newContainer;
            scrollContainer.addEventListener('scroll', handleScroll);

            console.log("scroll listener attached");
        }, 50);
    };

    // Attach first time
    connectScroll();

    // Re-attach every time DeepChat re-renders the messages list
    const shadowObserver = new MutationObserver(() => {
        connectScroll();
    });

    shadowObserver.observe(shadowRoot, { childList: true, subtree: true });
};


let scrollLocked = false;
let pendingLoad = false;

const handleScroll = async () => {
    if (!scrollContainer || scrollLocked) {
        // If a load is in progress, mark pending
        if (scrollContainer && scrollContainer.scrollTop < 50) {
            pendingLoad = true;
        }
        return;
    }

    if (scrollContainer.scrollTop < 50 && hasMoreMessages.value && !isLoadingMore.value) {
        await loadMoreHistory();
    }
};

async function loadMoreHistory() {
    isLoadingMore.value = true;
    
    // 1. Capture current scroll height BEFORE loading new items
    const oldHeight = scrollContainer.scrollHeight;
    const oldTop = scrollContainer.scrollTop;

    try {
        const { data } = await request.get(`/ai-chats/${chatId}/history`, {
            params: { before_id: nextCursor.value }
        });

        // 2. Prepend messages
        if (data.messages.length > 0) {
            history.value = [...data.messages, ...history.value];
            nextCursor.value = data.next_cursor;
            hasMoreMessages.value = data.has_more;

            // 3. RESTORE SCROLL POSITION (The Magic Fix)
            // Wait for Deep Chat to re-render with new messages
            setTimeout(() => {
                const newHeight = scrollContainer.scrollHeight;
                // Jump the scrollbar down by the amount of new content added
                scrollContainer.scrollTop = newHeight - oldHeight + oldTop;
                isLoadingMore.value = false;
            }, 50); // Small buffer for rendering
        } else {
            console.log('finished!')
            isLoadingMore.value = false;
        }

    } catch (e) {
        console.error(e);
        isLoadingMore.value = false;
    }
}

// --- 2. INTERCEPTORS ---
const requestInterceptor = (requestDetails) => {
    if (requestDetails.body instanceof FormData) {
        const newForm = new FormData();
        let extractedText = '';
        const entries = Array.from(requestDetails.body.entries());
        
        entries.forEach(([key, value]) => {
            if (key === 'files') {
                newForm.append('files[]', value);
            } else if (key === 'message1') {
                newForm.append(key, value);
                try {
                    if (typeof value === 'string') {
                        const msgs = JSON.parse(value);
                        // console.log(msgs)
                        extractedText = msgs.text;
                    }
                } catch (e) {}
            } else {
                newForm.append(key, value);
            }
        });
        if (extractedText) newForm.append('text_content', extractedText);
        requestDetails.body = newForm;
    }
    return requestDetails;
};

const handleResponse = (response) => {
    if (response.output) return { text: response.output };
    if (response.data && typeof response.data === 'string') return { text: response.data };
    return response;
};

onMounted(async () => {
    try {
        const configRes = await request.get(`/ai-chats`);
        chatConfig.value = configRes.data.find(c => c.id == chatId);
        
        // Initial Load (Recent 50)
        const { data } = await request.get(`/ai-chats/${chatId}/history`);
        console.log(data)
        history.value = data.messages;
        nextCursor.value = data.next_cursor;
        hasMoreMessages.value = data.has_more;

        
        const data2  = await request.get(`/ai-chats/${chatId}/status`);        
        connectionStatus.value = data2.data.status;

        // Initialize Scroll Listener after mount
        setTimeout(setupScrollListener, 1000);

    } catch (e) { console.error("Error init chat", e); }

    observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark');
    });
    observer.observe(document.documentElement, { attributes: true });
});

onUnmounted(() => { 
    if (observer) observer.disconnect(); 
    if (scrollContainer) scrollContainer.removeEventListener('scroll', handleScroll);
});

// --- CONFIGS ---
const placeholderText = computed(() => 'Type a message...');
const introMessage = computed(() => 
    (chatConfig.value?.welcome_message && history.value.length === 0) 
    ? { text: chatConfig.value.welcome_message } 
    : undefined
);

const requestConfig = computed(() => {
    if (!chatConfig.value) return {};
    return {
        url: `${import.meta.env.VITE_API_BASE_URL || ''}/ai-chats/${chatId}/chat`,
        method: 'POST',
        headers: { 'Authorization': `Bearer ${localStorage.getItem('token') || ''}` }
    };
});

const styleConfig = computed(() => {
    const dark = isDark.value;
    const c = {
        bg: dark ? '#0B1121' : '#ffffff',
        inputBg: dark ? '#1E293B' : '#fafafa',
        text: dark ? '#f1f5f9' : '#0f172a',
        placeholder: dark ? '#64748b' : '#a1a1aa',
        border: dark ? '#334155' : '#e4e4e7',
        userBubble: dark ? '#334155' : '#f4f4f5',
        aiBubble: dark ? '#020617' : '#ffffff',
    };

    return {
        textInput: {
            placeholder: { text: placeholderText.value, style: { color: c.placeholder } },
            styles: {
                container: {
                    backgroundColor: c.inputBg,
                    borderRadius: '10px',
                    border: `3px solid ${c.border}`,
                    maxWidth: '760px',
                    padding: '12px 18px',
                    boxShadow: dark ? '0 0 15px rgba(0,0,0,0.2)' : '0 4px 18px rgba(0,0,0,0.06)',
                    transition: 'all 0.3s ease',
                },
                text: { color: c.text, fontSize: '1rem' }
            }
        },
        submitButtonStyles: {
            submit: {
                container: {
                    default: { backgroundColor: dark ? '#ffffff' : '#18181b',  borderRadius: '50%', width: '34px', height: '34px', margin: '12px', transition: 'transform 0.2s' },
                    hover: { transform: 'scale(1.08)' },
                    click: { transform: 'scale(0.92)' }
                },
                svg: { styles: { default: { color: dark ? '#18181b' : '#ffffff' } } }
            }
        },
        attachmentButtonStyle: {
            styles: {
                default: {
                    filter: dark ? 'brightness(0) saturate(100%) invert(90%)' : 'brightness(0) saturate(100%) invert(30%)',          
                    margin: '12px',
                    opacity: '0.9'
                }
            }
        },
        messageStyles: {
            default: {
                shared: { bubble: { borderRadius: '15px', padding: '14px 20px', fontSize: '1rem', lineHeight: '1.6', marginTop: '30px'} },
                user: { bubble: { backgroundColor: c.userBubble, color: c.text} },
                ai: { bubble: { backgroundColor: c.aiBubble, color: c.text, border: `1px solid ${c.border}` } }
            }
        },
        auxiliaryStyle: `
            #messages { max-width: 65%; margin: 0 auto; padding-bottom: 220px; }
            ::-webkit-scrollbar { width: 6px; background-color: transparent }
            ::-webkit-scrollbar-thumb { background: ${dark ? '#334155' : '#c7c7d1'}; border-radius: 10px; }
        `
    };
});
</script>

<style scoped>
:deep(deep-chat) {
    display: block;
    width: 100%;
    height: 100%;
}
</style>