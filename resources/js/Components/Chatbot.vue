<template>
    <!-- Chat Toggle Button -->
    <button v-if="!isOpen" @click="isOpen = true"
        class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-tr from-indigo-600 to-cyan-500 rounded-full shadow-2xl flex items-center justify-center transform hover:scale-110 transition-all z-50 text-white hover:text-white border-2 border-white/20">
        <svg class="w-6 h-6 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
        </svg>
    </button>

    <!-- Chat Window -->
    <div v-if="isOpen"
        class="fixed bottom-6 right-6 w-[380px] h-[580px] bg-slate-50 flex flex-col rounded-[2rem] shadow-2xl z-50 overflow-hidden border border-slate-200 shadow-indigo-900/20 will-change-transform transform transition-all">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-4 text-white flex justify-between items-center z-10 shadow-md relative">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4IiBoZWlnaHQ9IjgiPgo8cmVjdCB3aWR0aD0iOCIgaGVpZ2h0PSI4IiBmaWxsPSIjZmZmIiBmaWxsLW9wYWNpdHk9IjAuMDUiLz4KPHBhdGggZD0iTTAgMEw4IDhaTTAgOEw4IDBaIiBzdHJva2U9IiNmZmYiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz4KPC9zdmc+')] opacity-50 mix-blend-overlay"></div>
            
            <div class="flex items-center gap-3 relative z-10">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-md border border-white/30">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-black tracking-wider text-sm">FerryCast AI</h3>
                    <p class="text-[10px] text-blue-100 font-bold uppercase tracking-widest flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                        Smart Agent Online
                    </p>
                </div>
            </div>
            
            <button @click="isOpen = false" class="text-white/70 hover:text-white transition-colors relative z-10 p-2 rounded-full hover:bg-white/10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto p-5 space-y-4 bg-slate-50 scroll-smooth" ref="chatContainer">
            
            <!-- Date Divider -->
            <div class="flex justify-center my-2">
                <span class="text-[9px] font-black uppercase tracking-widest text-slate-400 bg-slate-200 px-3 py-1 rounded-full text-center">
                    Today
                </span>
            </div>

            <div v-for="(msg, index) in messages" :key="index"
                class="flex flex-col max-w-[85%] animate-fade-in-up"
                :class="msg.role === 'user' ? 'ml-auto items-end' : 'mr-auto items-start'">
                
                <span class="text-[9px] font-bold text-slate-400 mb-1 ml-1" v-if="msg.role === 'bot'">AI Agent</span>
                <span class="text-[9px] font-bold text-slate-400 mb-1 mr-1" v-if="msg.role === 'user'">You</span>

                <div class="px-4 py-3 rounded-2xl text-sm font-medium shadow-sm leading-relaxed whitespace-pre-line"
                    :class="msg.role === 'user' 
                        ? 'bg-indigo-600 text-white rounded-br-sm' 
                        : 'bg-white text-slate-700 border border-slate-200 rounded-bl-sm markdown-body'">
                    <span v-html="formatMessage(msg.content)"></span>
                </div>
                
                <div v-if="msg.action === 'show_booking' && msg.schedule_id" class="mt-2 w-full">
                    <button @click="goToBooking(msg.schedule_id)" class="bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-colors w-full py-2.5 rounded-xl text-xs font-black uppercase tracking-widest shadow-sm">
                        View & Book Safest Departure
                    </button>
                </div>
            </div>
            
            <!-- Typing Indicator -->
            <div v-if="isTyping" class="mr-auto w-16 bg-white border border-slate-200 px-4 py-3.5 rounded-2xl rounded-bl-sm shadow-sm flex items-center gap-1.5 animate-fade-in-up">
                <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-1.5 h-1.5 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
        </div>

        <!-- Input Area -->
        <div class="p-4 bg-white border-t border-slate-100 relative">
            <div class="flex mt-1">
                <button v-for="suggestion in suggestions" :key="suggestion" @click="sendSuggestion(suggestion)"
                    class="mr-2 mb-3 bg-slate-100 hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 text-[10px] font-black uppercase tracking-wider px-3 py-1.5 rounded-full whitespace-nowrap transition-colors flex-shrink-0">
                    {{ suggestion }}
                </button>
            </div>

            <form @submit.prevent="sendMessage" class="relative flex items-center">
                <input v-model="userInput" type="text"
                    placeholder="Ask about safe routes, weather, or schedules..."
                    class="w-full bg-slate-50 border-slate-200 rounded-xl pr-12 pl-4 py-3 placeholder:text-slate-400 text-sm text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 shadow-inner"
                    :disabled="isTyping">
                
                <button type="submit" :disabled="!userInput.trim() || isTyping"
                    class="absolute right-2 top-1.5 bottom-1.5 w-9 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>
            </form>
            <div class="text-center mt-2">
                <span class="text-[8px] font-black uppercase tracking-widest text-slate-400">Powered by FerryCast AI Risk Engine</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const isOpen = ref(false)
const userInput = ref('')
const isTyping = ref(false)
const chatContainer = ref(null)

const page = usePage()

const suggestions = [
    "Safest ferry to Langkawi tomorrow?",
    "Routes from Kedah",
    "What page am I on?"
]

const messages = ref([
    {
        role: 'bot',
        content: "Hi there! I'm your AI Travel Agent. I analyze live marine weather and safety conditions. \n\nHow can I help you today?",
        action: null,
    }
])

const scrollToBottom = () => {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight
        }
    })
}

// Convert markdown bold to HTML
const formatMessage = (text) => {
    let formatted = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    return formatted
}

const sendSuggestion = (text) => {
    userInput.value = text
    sendMessage()
}

const goToBooking = (scheduleId) => {
    isOpen.value = false; // close chat
    router.visit(route('schedules.index', { book: scheduleId }));
}

const sendMessage = async () => {
    const text = userInput.value.trim()
    if (!text) return

    // Add user message
    messages.value.push({
        role: 'user',
        content: text
    })
    
    userInput.value = ''
    isTyping.value = true
    scrollToBottom()

    try {
        const response = await window.axios.post(window.route('chatbot.message'), {
            message: text,
            context_url: window.location.pathname // Send current page context
        })

        messages.value.push({
            role: 'bot',
            content: response.data.reply,
            action: response.data.action || null,
            schedule_id: response.data.schedule_id || null
        })
    } catch (e) {
        messages.value.push({
            role: 'bot',
            content: "Sorry, I'm having trouble connecting to the AI grid right now. Please try again later.",
        })
    } finally {
        isTyping.value = false
        scrollToBottom()
    }
}
</script>

<style scoped>
.animate-fade-in-up {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Hide scrollbar but keep functionality */
.overflow-y-auto::-webkit-scrollbar {
    display: none;
}
.overflow-y-auto {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
