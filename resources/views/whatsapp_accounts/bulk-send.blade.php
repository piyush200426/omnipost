@extends('layouts.index')

@section('title', 'WhatsApp Bulk Send')

@section('content')

<meta name="csrf-token" content="{{ csrf_token() }}">

<div
    x-data="bulkSendManager()"
    x-init="init()"
    class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50 p-4 md:p-6"
>

    <!-- MAIN CONTAINER -->
    <div class="max-w-4xl mx-auto">
        
        <!-- HEADER SECTION -->
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl shadow">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Bulk Message Sender</h1>
                    <p class="text-gray-600">Send approved WhatsApp template messages to multiple contacts</p>
                </div>
            </div>
        </div>

        <!-- PROGRESS STEPS -->
        <div class="mb-8">
            <div class="flex items-center justify-between relative">
                <!-- Progress Line -->
                <div class="absolute top-5 left-0 w-full h-0.5 bg-gray-200 z-0"></div>
                
                <!-- Step 1 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2"
                         :class="form.whatsapp_account_id 
                             ? 'bg-green-500 text-white shadow-lg' 
                             : 'bg-white border-2 border-gray-300 text-gray-400'">
                        <span class="font-bold">1</span>
                    </div>
                    <span class="text-sm font-medium">Account</span>
                </div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2"
                         :class="form.template_name 
                             ? 'bg-green-500 text-white shadow-lg' 
                             : 'bg-white border-2 border-gray-300 text-gray-400'">
                        <span class="font-bold">2</span>
                    </div>
                    <span class="text-sm font-medium">Template</span>
                </div>

                <!-- Step 3 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2"
                         :class="form.contact_ids.length > 0 
                             ? 'bg-green-500 text-white shadow-lg' 
                             : 'bg-white border-2 border-gray-300 text-gray-400'">
                        <span class="font-bold">3</span>
                    </div>
                    <span class="text-sm font-medium">Contacts</span>
                </div>

                <!-- Step 4 -->
                <div class="flex flex-col items-center relative z-10">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2"
                         :class="form.whatsapp_account_id && form.template_name && form.contact_ids.length > 0 
                             ? 'bg-green-500 text-white shadow-lg' 
                             : 'bg-white border-2 border-gray-300 text-gray-400'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium">Send</span>
                </div>
            </div>
        </div>

        <!-- STEP 1: SELECT WHATSAPP ACCOUNT -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-blue-100 to-blue-50 flex items-center justify-center">
                        <span class="font-bold text-blue-600">1</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Select WhatsApp Account</h2>
                        <p class="text-sm text-gray-600">Choose which account to send from</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <select
                        x-model="form.whatsapp_account_id"
                        class="pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition appearance-none bg-white"
                    >
                        <option value="">Select a WhatsApp account</option>
                        <template x-for="a in accounts" :key="a._id">
                            <option :value="a._id" x-text="a.provider.toUpperCase() + ' - ' + a.phone_number_id"></option>
                        </template>
                    </select>
                </div>

                <div x-show="form.whatsapp_account_id" class="mt-4 p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-sm text-blue-700">Account selected ✓</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 2: TEMPLATE DETAILS -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-purple-100 to-purple-50 flex items-center justify-center">
                        <span class="font-bold text-purple-600">2</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Template Details</h2>
                        <p class="text-sm text-gray-600">Configure your message template</p>
                    </div>
                </div>
            </div>

            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Template Name
                        <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            placeholder="e.g., hello_world, order_confirmation"
                            x-model="form.template_name"
                            class="pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Template Variables
                        <span class="text-gray-500 font-normal">(comma separated)</span>
                    </label>
                    <div class="relative">
                        <div class="absolute top-3 left-3 pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <textarea
                            placeholder="Enter variables separated by commas
Example: John Doe, https://omnipost.in/abc, 25% discount"
                            x-model="form.variables"
                            rows="4"
                            class="pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition"
                        ></textarea>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Each variable will be replaced in your template in order
                    </p>
                </div>

                <div x-show="form.template_name" class="p-4 bg-purple-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <span class="text-sm text-purple-700">Template configured ✓</span>
                            <p class="text-xs text-purple-600 mt-1">
                                Variables: <span x-text="form.variables.split(',').length" class="font-bold"></span> provided
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP 3: SELECT CONTACTS -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-green-100 to-green-50 flex items-center justify-center">
                        <span class="font-bold text-green-600">3</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Select Contacts</h2>
                        <p class="text-sm text-gray-600">Choose recipients for your message</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Select All & Counter -->
                <div class="flex items-center justify-between mb-4">
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <div class="relative">
                            <input 
                                type="checkbox" 
                                @change="toggleAll($event)"
                                class="sr-only peer"
                            >
                            <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-green-500 peer-checked:border-green-500 transition-colors flex items-center justify-center">
                                <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">
                            Select all contacts
                        </span>
                    </label>

                    <div class="text-sm text-gray-600">
                        <span class="font-bold" x-text="form.contact_ids.length"></span> selected
                        <span class="mx-1">•</span>
                        <span x-text="contacts.length"></span> total
                    </div>
                </div>

                <!-- Contacts List -->
                <div class="border border-gray-300 rounded-lg overflow-hidden">
                    <div class="max-h-96 overflow-y-auto">
                        <template x-for="c in contacts" :key="c._id">
                            <label class="flex items-center gap-4 px-4 py-3 border-b border-gray-200 hover:bg-gray-50 transition-colors cursor-pointer group">
                                <div class="relative">
                                    <input
                                        type="checkbox"
                                        :value="c._id"
                                        x-model="form.contact_ids"
                                        class="sr-only peer"
                                    >
                                    <div class="w-5 h-5 border-2 border-gray-300 rounded peer-checked:bg-green-500 peer-checked:border-green-500 transition-colors flex items-center justify-center">
                                        <svg class="w-3 h-3 text-white hidden peer-checked:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-100 to-blue-50 flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600" 
                                                      x-text="c.name ? c.name.charAt(0).toUpperCase() : 'C'">
                                                </span>
                                            </div>
                                            <div>
                                                <span class="font-medium text-gray-900" x-text="c.name ?? 'No Name'"></span>
                                                <p class="text-xs text-gray-500" x-text="c.phone_number"></p>
                                            </div>
                                        </div>
                                        <span class="text-xs text-gray-400 group-hover:text-gray-600">
                                            Contact
                                        </span>
                                    </div>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <div x-show="form.contact_ids.length > 0" class="mt-4 p-4 bg-green-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <span class="text-sm text-green-700">
                                <span x-text="form.contact_ids.length" class="font-bold"></span> contacts selected ✓
                            </span>
                            <p class="text-xs text-green-600 mt-1">
                                Ready to send your message
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEND BUTTON -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-gradient-to-r from-orange-100 to-orange-50 flex items-center justify-center">
                        <span class="font-bold text-orange-600">4</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Send Messages</h2>
                        <p class="text-sm text-gray-600">Review and send your bulk messages</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <!-- Summary -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Send Summary</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-xs text-blue-600 mb-1">Account</p>
                            <p class="font-medium" x-text="form.whatsapp_account_id ? 'Selected' : 'Not selected'"></p>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <p class="text-xs text-purple-600 mb-1">Template</p>
                            <p class="font-medium truncate" x-text="form.template_name || 'Not set'"></p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-xs text-green-600 mb-1">Recipients</p>
                            <p class="font-medium" x-text="form.contact_ids.length"></p>
                        </div>
                    </div>
                </div>

                <!-- Send Button -->
                <div class="flex justify-end">
                    <button
                        @click="sendBulk()"
                        :disabled="sending || !form.whatsapp_account_id || !form.template_name || form.contact_ids.length === 0"
                        class="group inline-flex items-center gap-3 px-8 py-4 rounded-xl font-semibold transition-all duration-300"
                        :class="sending || !form.whatsapp_account_id || !form.template_name || form.contact_ids.length === 0
                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                            : 'bg-gradient-to-r from-green-500 to-emerald-600 text-white hover:shadow-xl hover:scale-105'"
                    >
                        <template x-if="!sending">
                            <svg class="w-5 h-5 group-hover:animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </template>
                        <template x-if="sending">
                            <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </template>
                        <span x-text="sending ? 'Sending Messages...' : 'Send WhatsApp Messages'"></span>
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
function bulkSendManager() {
    return {
        accounts: [],
        contacts: [],
        sending: false,

        form: {
            whatsapp_account_id: '',
            template_name: '',
            variables: '',
            contact_ids: []
        },

        init() {
            this.fetchAccounts();
            this.fetchContacts();
        },

        fetchAccounts() {
            fetch('/whatsapp-accounts')
                .then(r => r.json())
                .then(r => this.accounts = r.data ?? []);
        },

        fetchContacts() {
            fetch('/contacts')
                .then(r => r.json())
                .then(r => this.contacts = r.data ?? []);
        },

        toggleAll(e) {
            if (e.target.checked) {
                this.form.contact_ids = this.contacts.map(c => c._id);
            } else {
                this.form.contact_ids = [];
            }
        },

        async sendBulk() {
            if (!this.form.whatsapp_account_id || !this.form.template_name || this.form.contact_ids.length === 0) {
                alert('Please complete all steps');
                return;
            }

            this.sending = true;

            // STEP 1: PREPARE
            const prepareRes = await fetch('/whatsapp/bulk/prepare', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    whatsapp_account_id: this.form.whatsapp_account_id,
                    contact_ids: this.form.contact_ids,
                    template_name: this.form.template_name,
                    payload: {
                        body: this.form.variables.split(',').map(v => v.trim())
                    }
                })
            }).then(r => r.json());

            if (!prepareRes.success) {
                alert(prepareRes.message || 'Prepare failed');
                this.sending = false;
                return;
            }

            // STEP 2: EXECUTE
            const execRes = await fetch('/whatsapp/bulk/execute', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    log_ids: prepareRes.log_ids
                })
            }).then(r => r.json());

            this.sending = false;

            alert(`Done!\nSent: ${execRes.sent}\nFailed: ${execRes.failed}`);
        }
    }
}
</script>
@endsection