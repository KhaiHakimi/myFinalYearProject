<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div>
                    <h2 class="text-2xl font-black leading-tight text-blue-900 uppercase tracking-tighter">
                        User Management
                    </h2>
                    <p class="text-sm text-blue-600/70 font-medium mt-1">Manage registered passengers and administrative accounts</p>
                </div>
                <div class="mt-4 md:mt-0 bg-blue-100/50 px-4 py-2 rounded-full border border-blue-200">
                    <span class="text-xs font-black text-blue-800 uppercase tracking-widest">{{ users.length }} Total Users</span>
                </div>
            </div>
        </template>

        <div class="py-12 bg-slate-50 min-h-screen">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-8">
                
                <!-- Users Table Card -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-[2rem] border border-blue-100">
                    <div class="p-8 border-b border-blue-50 bg-blue-900 flex justify-between items-center">
                        <h3 class="text-lg font-black text-white uppercase tracking-widest flex items-center">
                            <span class="bg-yellow-400 w-2 h-6 mr-3 rounded-full"></span>
                            Registered Accounts
                        </h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-blue-50/50 text-[10px] uppercase font-black tracking-widest text-blue-900/40 border-b border-blue-100">
                                    <th class="p-5 pl-8">User Details</th>
                                    <th class="p-5">Email Address</th>
                                    <th class="p-5">Registered Date</th>
                                    <th class="p-5 text-center">Role</th>
                                    <th class="p-5 pr-8 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm font-medium">
                                <tr v-for="user in users" :key="user.id" class="border-b border-blue-50 hover:bg-blue-50/30 transition group">
                                    <td class="p-5 pl-8">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-black text-lg border-2 border-white shadow-sm group-hover:scale-110 transition-transform">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div>
                                                <div class="font-black text-blue-900 text-base">{{ user.name }}</div>
                                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">ID: #{{ String(user.id).padStart(4, '0') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-5">
                                        <div class="font-bold text-gray-600 flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            {{ user.email }}
                                        </div>
                                    </td>
                                    <td class="p-5">
                                        <div class="font-black text-gray-800">{{ new Date(user.created_at).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) }}</div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase">{{ new Date(user.created_at).toLocaleTimeString('en-GB', { hour: '2-digit', minute:'2-digit' }) }}</div>
                                    </td>
                                    <td class="p-5 text-center">
                                        <span class="px-3 py-1 text-[9px] font-black uppercase tracking-widest rounded-full shadow-sm border"
                                            :class="{
                                                'bg-rose-50 text-rose-700 border-rose-200': user.is_admin,
                                                'bg-blue-50 text-blue-600 border-blue-200': !user.is_admin
                                            }">
                                            {{ user.is_admin ? 'Administrator' : 'Customer' }}
                                        </span>
                                    </td>
                                    <td class="p-5 pr-8 text-right">
                                        <button @click="confirmDelete(user)"
                                            class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-widest transition-all bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white border border-rose-100 shadow-sm hover:shadow-md">
                                            Revoke Access
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="users.length === 0">
                                    <td colspan="5" class="py-16 text-center text-gray-400 font-bold bg-gray-50/50">
                                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span class="text-lg">No other users found in the system.</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <Modal :show="userToDelete !== null" @close="closeModal">
            <div class="p-8">
                <div class="flex items-center justify-center w-16 h-16 mx-auto bg-rose-100 rounded-full mb-6">
                    <svg class="w-8 h-8 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-black text-slate-900 text-center uppercase tracking-tighter mb-2">
                    Revoke User Access?
                </h2>
                <p class="text-sm text-slate-500 text-center font-medium mb-8 leading-relaxed">
                    Are you sure you want to permanently delete the account for <br>
                    <span class="font-black text-rose-600">{{ userToDelete?.name }}</span> ({{ userToDelete?.email }})? 
                    <br>This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeModal" class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button @click="deleteUser" class="px-6 py-3 bg-rose-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-rose-700 transition-colors shadow-lg hover:shadow-xl hover:-translate-y-0.5 transform">
                        Yes, Delete User
                    </button>
                </div>
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    users: {
        type: Array,
        required: true
    }
});

const userToDelete = ref(null);

const confirmDelete = (user) => {
    userToDelete.value = user;
};

const closeModal = () => {
    userToDelete.value = null;
};

const deleteUser = () => {
    if (userToDelete.value) {
        router.delete(route('users.destroy', userToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};
</script>
