<script setup>
    import { ref, onMounted } from 'vue'
    import api from '../utils/request'
    
    const props = defineProps({
        leadId: { type: Number, required: true }
    })
    
    const activities = ref([])
    const loading = ref(false)
    
    const fetchActivities = async () => {
        loading.value = true
        try {
            const res = await api.get(`/leads/${props.leadId}/activities`)
            activities.value = res.data
            // console.log(activities.value)
        } catch (e) {
            console.error("Timeline failed", e)
        } finally {
            loading.value = false
        }
    }
    
    // Color Mapping for "Wow Factor"
    // const config = {
    //     system: {  color: 'bg-blue-500', label: 'System Action' },
    //     external_api: { color: 'bg-orange-500', label: 'External Action' },
    //     status_change: { color: 'bg-purple-500', label: 'Pipeline Move' },
    //     note: { color: 'bg-green-500', label: 'User Notes' },
    //     default: {  color: 'bg-yellow-400', label: 'User Action' }
    // }

    // 1. Define your SVG Paths separately to keep config clean
const ICONS = {
    server: "M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 01-2 2v4a2 2 0 012 2h14a2 2 0 012-2v-4a2 2 0 01-2-2m-2-4h.01M17 16h.01",
    zap: "M13 10V3L4 14h7v7l9-11h-7z",
    switch: "M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4",
    document: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",
    user: "M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z",
    fire: "M17.657 18.657A8 8 0 016.343 7.343S7 9 9 10c0-2 .5-5 2.986-7C14 5 16.09 5.777 17.656 7.343A7.975 7.975 0 0120 13a7.975 7.975 0 01-2.343 5.657z M9.879 16.121A3 3 0 1012.015 11L11 14H9c0 .768.293 1.536.879 2.121z",
    alert: "M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
};

// 2. The Master Configuration
const configMap = {
    // --- System & External Core ---
    'system':                   { color: 'bg-blue-500',   icon: ICONS.server, label: 'System Action' },
    'system_inserted':          { color: 'bg-blue-600',   icon: ICONS.server, label: 'System Entry' },
    'external_system_inserted': { color: 'bg-blue-700',   icon: ICONS.zap, label: 'External API Entry' },

    // --- Pipeline/Status ---
    'system_updated_status':          { color: 'bg-purple-500', icon: ICONS.switch, label: 'System Move Pipline Stage' },
    'external_system_updated_status': { color: 'bg-purple-600', icon: ICONS.switch, label: 'External API Move Pipline Stage' },

    // --- Temperature (New Visuals) ---
    'system_updated_temperature':          { color: 'bg-rose-500', icon: ICONS.fire, label: 'System Temp Update' },
    'external_system_updated_temperature': { color: 'bg-rose-600', icon: ICONS.fire, label: 'External API Temp Update' },

    // --- Notes ---
    'system_added_note':          { color: 'bg-green-500', icon: ICONS.document, label: 'User Added Note' },
    'external_system_added_note': { color: 'bg-green-600', icon: ICONS.document, label: 'External API Added Note' },

    // --- Errors & Defaults ---
    'system_error': { color: 'bg-red-600',    icon: ICONS.alert, label: 'System Error' },
    'default':      { color: 'bg-yellow-400', icon: ICONS.user,  label: 'User Action' }
};

// 3. Helper function to use in template
const getItemConfig = (type) => {
    return configMap[type] || configMap['default'];
};
    
    const formatTime = (date) => new Date(date).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
    
    onMounted(fetchActivities)
    </script>
    
    <template>
        <div class="space-y-6">
            <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Activity Timeline</h3>
            
            <div v-if="loading" class="animate-pulse space-y-4">
                <div v-for="i in 3" :key="i" class="flex gap-4">
                    <div class="w-10 h-10 bg-slate-200 dark:bg-slate-800 rounded-full flex-shrink-0"></div>
                    <div class="flex-1 space-y-2 py-1">
                        <div class="h-4 bg-slate-200 dark:bg-slate-800 rounded w-1/4"></div>
                        <div class="h-3 bg-slate-200 dark:bg-slate-800 rounded w-3/4"></div>
                    </div>
                </div>
            </div>
    
            <div v-else-if="activities.length > 0" class="relative">
                
                <div 
                    class="absolute left-5 top-2 bottom-4 w-0.5 bg-slate-200 dark:bg-slate-800" 
                    aria-hidden="true"
                ></div>
    
                <div class="space-y-6 relative"> <div v-for="item in activities" :key="item.id" class="relative flex gap-4">
                        
                        <div 
                            :class="getItemConfig(item.type).color" 
                            class="relative z-10 flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center ring-4 ring-white dark:ring-slate-950"
                        >
                            <svg 
                                class="w-5 h-5 text-white" 
                                fill="none" 
                                viewBox="0 0 24 24" 
                                stroke="currentColor"
                            >
                                <path 
                                    stroke-linecap="round" 
                                    stroke-linejoin="round" 
                                    stroke-width="1.5" 
                                    :d="getItemConfig(item.type).icon" 
                                />
                            </svg>
                        </div>
    
                        <div class="flex-1 pt-2"> <div class="flex justify-between items-start mb-1">
                                <span class="text-xs font-bold text-slate-900 dark:text-white">
                                    {{ getItemConfig(item.type).label }}
                                </span>
                                <span class="text-[10px] font-medium text-slate-400 italic whitespace-nowrap ml-2">
                                    {{ formatTime(item.created_at) }}
                                </span>
                            </div>
                            <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed">
                                {{ item.content }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
    
            <div v-else class="text-center py-10 bg-slate-50 dark:bg-slate-900/50 rounded-xl border-2 border-dashed border-slate-200 dark:border-slate-800">
                <p class="text-sm text-slate-400 italic">No activity recorded for this lead yet.</p>
            </div>
        </div>
    </template>