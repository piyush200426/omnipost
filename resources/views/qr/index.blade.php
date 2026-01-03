@extends('layouts.index')
@section('title','QR Builder')

@section('content')
@if(isset($qr))
<script>
    window.EDIT_QR = @json($qr);
    window.EDIT_SETTINGS = @json(
        is_string($qr->settings) ? json_decode($qr->settings, true) : $qr->settings
    );
</script>
@endif

<div class="mb-6">
    <a href="{{ route('qr.builder') }}" 
       class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        <span>Back to QR List</span>
    </a>
</div>

<!-- Alpine component se pehle -->
<script>
    // Optional: Initial domain set karo
    window.INITIAL_DOMAIN = '{{ config('app.url') }}';
</script>

<!-- Load Alpine.js FIRST -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://unpkg.com/qr-code-styling@1.6.0/lib/qr-code-styling.js"></script>

<div x-data="qrBuilder()" x-init="init()" class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-10 p-4 md:p-6">

    <!-- LEFT PANEL -->
    <div class="space-y-6 md:space-y-8">
        <!-- QR NAME -->
        <div>
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">QR Code Name</label>
            <input x-model="name" placeholder="e.g. For Instagram" 
                   class="w-full mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
        </div>

        <!-- DOMAIN (for dynamic QR) -->
        <template x-if="mode === 'dynamic'">
            <div>
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">Domain</label>
                <div class="relative">
                    <select x-model="domain" class="w-full mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 pr-10 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all appearance-none">
                        <option value="https://qrul.co">https://qrul.co</option>
                        <option value="https://custom.domain">https://custom.domain</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Choose domain to generate the link with when using dynamic QR codes. Not applicable for static QR codes.
                </p>
                <hr class="my-4 dark:border-gray-700">
            </div>
        </template>

        <!-- MODE SELECTOR -->
        <div>
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">QR Type</label>
            <div class="flex gap-3 mt-3">
                <button @click="mode='static'; payloadType='text'" 
                        :class="mode=='static' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" 
                        class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-md flex-1">
                    Static QR
                </button>
                <button @click="mode='dynamic'; payloadType='link'" 
                        :class="mode=='dynamic' ? 'bg-blue-600 text-white shadow-lg' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600'" 
                        class="px-4 md:px-6 py-2 md:py-3 rounded-lg font-medium transition-all duration-200 hover:shadow-md flex-1">
                    Dynamic QR
                </button>
            </div>
        </div>

        <!-- QR TYPES SECTION -->
        <div>
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 block">
                <span x-text="mode === 'static' ? 'Static QR' : 'Dynamic QR'"></span>
            </label>
            
            <!-- STATIC QR TYPES -->
            <template x-if="mode === 'static'">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 md:gap-3">
                    <button @click="payloadType='text'" 
                            :class="payloadType==='text' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Text</span>
                        <span class="mt-1 text-lg">📝</span>
                    </button>
                    <button @click="payloadType='sms'" 
                            :class="payloadType==='sms' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">SMS & Message</span>
                        <span class="mt-1 text-lg">💬</span>
                    </button>
                    <button @click="payloadType='wifi'" 
                            :class="payloadType==='wifi' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">WiFi</span>
                        <span class="mt-1 text-lg">📶</span>
                    </button>
                    <button @click="payloadType='vcard'" 
                            :class="payloadType==='vcard' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Static vCard</span>
                        <span class="mt-1 text-lg">👤</span>
                    </button>
                    <button @click="payloadType='event'" 
                            :class="payloadType==='event' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Event</span>
                        <span class="mt-1 text-lg">📅</span>
                    </button>
                </div>
            </template>

            <!-- DYNAMIC QR TYPES -->
            <template x-if="mode === 'dynamic'">
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 md:gap-3">
                    <button @click="payloadType='link'" 
                            :class="payloadType==='link' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Link</span>
                        <span class="mt-1 text-lg">🔗</span>
                    </button>
                    <button @click="payloadType='email'" 
                            :class="payloadType==='email' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Email</span>
                        <span class="mt-1 text-lg">📧</span>
                    </button>
                    <button @click="payloadType='phone'" 
                            :class="payloadType==='phone' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Phone</span>
                        <span class="mt-1 text-lg">📱</span>
                    </button>
                    <button @click="payloadType='sms'" 
                            :class="payloadType==='sms' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">SMS</span>
                        <span class="mt-1 text-lg">💬</span>
                    </button>
                    <button @click="payloadType='vcard'" 
                            :class="payloadType==='vcard' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">vCard</span>
                        <span class="mt-1 text-lg">👤</span>
                    </button>
                    <button @click="payloadType='application'" 
                            :class="payloadType==='application' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Application</span>
                        <span class="mt-1 text-lg">📱</span>
                    </button>
                    <button @click="payloadType='file'" 
                            :class="payloadType==='file' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">File</span>
                        <span class="mt-1 text-lg">📎</span>
                    </button>
                    <button @click="payloadType='whatsapp'" 
                            :class="payloadType==='whatsapp' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Whatsapp</span>
                        <span class="mt-1 text-lg">💚</span>
                    </button>
                    <button @click="payloadType='cryptocurrency'" 
                            :class="payloadType==='cryptocurrency' ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-600'" 
                            class="flex flex-col items-center justify-center p-3 md:p-4 border rounded-lg transition-all duration-200 hover:shadow-md">
                        <span class="text-sm font-medium">Cryptocurrency</span>
                        <span class="mt-1 text-lg">₿</span>
                    </button>
                </div>
            </template>
        </div>

        <!-- PAYLOAD INPUT -->
        <div x-show="showPayloadInput" class="transition-all duration-300">
            <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 capitalize mb-2 block" x-text="getPayloadLabel()"></label>
            
            <!-- TEXT -->
            <template x-if="payloadType === 'text'">
                <div class="space-y-3">
                    <textarea x-model="payloadValue" placeholder="Enter text here..." rows="4"
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                    <!-- Static Text Sub-Options -->
                    <div class="grid grid-cols-2 gap-3">
                        <button @click="payloadType='sms'" 
                                class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="text-sm font-medium">SMS & Message</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Create SMS QR</div>
                        </button>
                        <button @click="payloadType='wifi'" 
                                class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="text-sm font-medium">WiFi</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">WiFi Connection</div>
                        </button>
                        <button @click="payloadType='vcard'" 
                                class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="text-sm font-medium">Static vCard</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Contact Card</div>
                        </button>
                        <button @click="payloadType='event'" 
                                class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <div class="text-sm font-medium">Event</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Calendar Event</div>
                        </button>
                    </div>
                </div>
            </template>

            <!-- SMS - STATIC -->
            <template x-if="payloadType === 'sms' && mode === 'static'">
                <div class="space-y-3 mt-2">
                    <input x-model="staticSMS.phone" placeholder="Phone Number e.g. 123456789" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticSMS.message" placeholder="Message e.g. Job Application" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </template>

            <!-- SMS - DYNAMIC -->
            <template x-if="payloadType === 'sms' && mode === 'dynamic'">
                <input x-model="payloadValue" placeholder="Enter phone number and message (e.g., +1234567890:Hello)" 
                       class="w-full mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
            </template>

            <!-- WiFi -->
            <template x-if="payloadType === 'wifi'">
                <div class="space-y-3 mt-2">
                    <input x-model="wifi.ssid" placeholder="Network SSID e.g. 123456789" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="wifi.password" placeholder="Password (Optional)" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <select x-model="wifi.encryption" 
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="WEP">WEP</option>
                        <option value="WPA">WPA/WPA2</option>
                        <option value="">No Encryption</option>
                    </select>
                </div>
            </template>

            <!-- Static vCard -->
            <template x-if="payloadType === 'vcard' && mode === 'static'">
                <div class="space-y-3 mt-2">
                    <input x-model="staticVcard.firstName" placeholder="First Name e.g. John" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.lastName" placeholder="Last Name e.g. Doe" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.organization" placeholder="Organization e.g. Internet Inc" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.phone" placeholder="Phone e.g. +112345689" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.cell" placeholder="Cell e.g. +112345689" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.fax" placeholder="Fax e.g. +112345689" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.email" placeholder="Email e.g. someone@domain.com" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="staticVcard.website" placeholder="Website e.g. https://domain.com" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </template>

            <!-- Dynamic vCard -->
            <template x-if="payloadType === 'vcard' && mode === 'dynamic'">
                <input x-model="payloadValue" placeholder="Enter vCard data or URL" 
                       class="w-full mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
            </template>

            <!-- Event -->
            <template x-if="payloadType === 'event'">
                <div class="space-y-3 mt-2">
                    <input x-model="event.title" placeholder="Title" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="event.description" placeholder="Description" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="event.location" placeholder="Location" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="event.url" placeholder="URL" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="event.start" type="datetime-local" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="event.end" type="datetime-local" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </template>

            <!-- Link -->
            <template x-if="payloadType === 'link'">
                <div class="space-y-3">
                    <input x-model="payloadValue" placeholder="Enter URL (e.g., https://example.com)" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <div x-show="mode === 'dynamic'" class="text-sm text-gray-600 dark:text-gray-400">
                        Dynamic link will be created at: <span class="font-medium" x-text="domain + '/user/iq/rcraie'"></span>
                    </div>
                </div>
            </template>

            <!-- Email -->
            <template x-if="payloadType === 'email'">
                <div class="space-y-3 mt-2">
                    <input x-model="email.to" placeholder="e.g. someone@domain.com" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="email.subject" placeholder="Subject e.g. Job Application" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <textarea x-model="email.message" placeholder="Your message here to be sent as email" 
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                </div>
            </template>

            <!-- Phone -->
            <template x-if="payloadType === 'phone'">
                <input x-model="payloadValue" placeholder="Phone Number e.g. 123-55789" 
                       class="w-full mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
            </template>

            <!-- Application -->
            <template x-if="payloadType === 'application'">
                <div class="space-y-3 mt-2">
                    <input x-model="application.appStore" placeholder="Link to App Store https://" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="application.googlePlay" placeholder="Link to Google Play https://" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <input x-model="application.other" placeholder="Link for others* https://" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </template>

            <!-- File -->
            <template x-if="payloadType === 'file'">
                <div class="space-y-3 mt-2">
                    <div class="border border-gray-300 dark:border-gray-600 rounded-xl p-4 dark:bg-gray-800">
                        <p class="text-sm mb-2 text-gray-700 dark:text-gray-300">File Upload (Image or PDF)</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            This can be used to upload an image or a PDF. Most common uses are restaurant menu, promotional poster and resume.
                        </p>
                        <input type="file" @change="handleFileUpload" accept=".jpg,.jpeg,.png,.pdf" 
                               class="w-full border border-gray-300 dark:border-gray-600 rounded-xl px-4 py-2 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Acceptable file: jpg, png, pdf. Max 2MB.
                        </p>
                    </div>
                </div>
            </template>

            <!-- WhatsApp -->
            <template x-if="payloadType === 'whatsapp'">
                <div class="space-y-3 mt-2">
                    <input x-model="whatsapp.phone" placeholder="Phone Number e.g. +123456789" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <textarea x-model="whatsapp.message" placeholder="Message e.g. Your message here to be sent as email" 
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 h-32 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"></textarea>
                </div>
            </template>

            <!-- Cryptocurrency -->
            <template x-if="payloadType === 'cryptocurrency'">
                <div class="space-y-3 mt-2">
                    <select x-model="crypto.type" 
                            class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="bitcoin">Bitcoin</option>
                        <option value="ethereum">Ethereum</option>
                        <option value="bitcoincash">Bitcoin Cash</option>
                    </select>
                    <input x-model="crypto.address" placeholder="Wallet Address e.g. Enter your public wallet address" 
                           class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                </div>
            </template>
        </div>

        <!-- TEMPLATE & DESIGN SECTION -->
        <div class="space-y-6 border-t border-gray-200 dark:border-gray-700 pt-6">
            <!-- TEMPLATES -->
            <div>
                <label class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 block">Your Design</label>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <button @click="openPanel='templates'; applyTemplate('default')" 
                            :class="activeTemplate === 'default' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                            class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                        Default
                    </button>
                    <button @click="openPanel='templates'; applyTemplate('dark')" 
                            :class="activeTemplate === 'dark' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                            class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                        Dark
                    </button>
                    <button @click="openPanel='templates'; applyTemplate('colorful')" 
                            :class="activeTemplate === 'colorful' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                            class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                        Colorful
                    </button>
                    <button @click="openPanel='templates'; applyTemplate('gradient')" 
                            :class="activeTemplate === 'gradient' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                            class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                        Gradient
                    </button>
                </div>
            </div>

            <!-- COLOR PANEL (Shows when Colors is clicked) -->
            <div x-show="openPanel === 'colors'" 
                 class="border border-gray-300 dark:border-gray-600 rounded-xl p-4 bg-gray-50 dark:bg-gray-800 transition-all duration-300">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300">Colors</h4>
                    <button @click="openPanel=null" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">✕</button>
                </div>
                <div class="space-y-4">
                    <!-- Color Type -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Color Type</label>
                        <div class="flex gap-3">
                            <button @click="colorType='single'" 
                                    :class="colorType==='single' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="px-4 py-2 border rounded-lg transition-all duration-200">
                                Single Color
                            </button>
                            <button @click="colorType='gradient'" 
                                    :class="colorType==='gradient' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="px-4 py-2 border rounded-lg transition-all duration-200">
                                Gradient Color
                            </button>
                        </div>
                    </div>

                    <!-- Single Color -->
                    <template x-if="colorType === 'single'">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Foreground</label>
                                <input type="color" x-model="fg" class="w-full h-10 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Background</label>
                                <input type="color" x-model="bg" class="w-full h-10 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                            </div>
                        </div>
                    </template>

                    <!-- Gradient Color -->
                    <template x-if="colorType === 'gradient'">
                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Gradient Start</label>
                                <input type="color" x-model="gradientStart" class="w-full h-10 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Gradient Stop</label>
                                <input type="color" x-model="gradientStop" class="w-full h-10 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Gradient Direction</label>
                                <select x-model="gradientDirection" 
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <option value="horizontal">Horizontal</option>
                                    <option value="vertical">Vertical</option>
                                    <option value="diagonal">Diagonal</option>
                                    <option value="radial">Radial</option>
                                </select>
                            </div>
                        </div>
                    </template>

                    <!-- Eye Colors -->
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Eye Colors</h5>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Eye Frame Color</label>
                                <input type="color" x-model="eyeFrameColor" class="w-full h-8 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Eye Color</label>
                                <input type="color" x-model="eyeColor" class="w-full h-8 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESIGN PANEL (Shows when Design is clicked) -->
            <div x-show="openPanel === 'design'" 
                 class="border border-gray-300 dark:border-gray-600 rounded-xl p-4 bg-gray-50 dark:bg-gray-800 transition-all duration-300">
                <div class="flex justify-between items-center mb-3">
                    <h4 class="font-semibold text-gray-700 dark:text-gray-300">Design</h4>
                    <button @click="openPanel=null" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">✕</button>
                </div>
                <div class="space-y-4">
                    <!-- Custom Logo -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Custom Logo</label>
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 dark:bg-gray-700">
                            <input type="file" @change="handleLogoUpload" accept="image/*" 
                                   class="w-full dark:text-white">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                Logo can now be embedded in the QR code. Please note that embedded logos can sometimes lead to unstable QR codes so please check to make sure the QR works.
                            </p>
                        </div>
                    </div>

                    <!-- Logo Preview -->
                    <div x-show="logo">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Logo Preview</label>
                        <div class="w-20 h-20 border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
                            <img :src="logo" alt="Logo" class="w-full h-full object-contain">
                        </div>
                    </div>

                    <!-- Size -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Size</label>
                        <input type="range" x-model="logoSize" min="20" max="100" 
                               class="w-full accent-blue-600">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Logo Size: <span x-text="logoSize"></span>%</div>
                    </div>

                    <!-- Matrix Style -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Matrix Style</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="dotStyle='square'" 
                                    :class="dotStyle==='square' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Square
                            </button>
                            <button @click="dotStyle='rounded'" 
                                    :class="dotStyle==='rounded' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Rounded
                            </button>
                            <button @click="dotStyle='dots'" 
                                    :class="dotStyle==='dots' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Dots
                            </button>
                            <button @click="dotStyle='classy'" 
                                    :class="dotStyle==='classy' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Classy
                            </button>
                        </div>
                    </div>

                    <!-- Eye Style -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Eye Style</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button @click="eyeStyle='square'" 
                                    :class="eyeStyle==='square' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Square
                            </button>
                            <button @click="eyeStyle='rounded'" 
                                    :class="eyeStyle==='rounded' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Rounded
                            </button>
                            <button @click="eyeStyle='circle'" 
                                    :class="eyeStyle==='circle' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                                    class="p-2 border rounded-lg transition-all duration-200">
                                Circle
                            </button>
                        </div>
                    </div>

                    <!-- Frame Style -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Frame Style</label>
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Text</label>
                                <input x-model="frameText" placeholder="e.g. Scan me" maxlength="20" 
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Limit of 20 characters</div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Frame Color</label>
                                    <input type="color" x-model="frameColor" class="w-full h-8 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                                </div>
                                <div>
                                    <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Text Color</label>
                                    <input type="color" x-model="textColor" class="w-full h-8 cursor-pointer rounded-lg border border-gray-300 dark:border-gray-600">
                                </div>
                            </div>
                            <div>
                                <label class="text-xs text-gray-600 dark:text-gray-400 mb-1 block">Text Font</label>
                                <select x-model="textFont" 
                                        class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    <option value="Arial">Arial</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="Verdana">Verdana</option>
                                    <option value="Tahoma">Tahoma</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Margin -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Margin</label>
                        <input x-model="margin" type="number" min="0" max="50" placeholder="e.g. 10" 
                               class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>

                    <!-- Error Correction -->
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 block">Error Correction</label>
                        <select x-model="errorCorrection" 
                                class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="L">L (7%)</option>
                            <option value="M">M (15%)</option>
                            <option value="Q">Q (25%)</option>
                            <option value="H">H (30%)</option>
                        </select>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            Error correction allows better readability when code is damaged or dirty but increases QR data
                        </p>
                    </div>
                </div>
            </div>

            <!-- COLOR & DESIGN BUTTONS -->
            <div class="grid grid-cols-2 gap-4">
                <button @click="openPanel = openPanel === 'colors' ? null : 'colors'" 
                        :class="openPanel === 'colors' ? 'bg-blue-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
                        class="py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-all duration-200 hover:shadow-md">
                    <span>🎨</span> Colors
                </button>
                <button @click="openPanel = openPanel === 'design' ? null : 'design'" 
                        :class="openPanel === 'design' ? 'bg-blue-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'" 
                        class="py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-all duration-200 hover:shadow-md">
                    <span>🛠️</span> Design
                </button>
            </div>
        </div>

        <!-- GENERATE QR BUTTON -->
        <div>
            <button @click="generateQR()" 
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.02]">
                Generate QR
            </button>
        </div>

        <!-- SAVE QR TO DATABASE BUTTON -->
        <div x-show="qr" class="transition-all duration-300">
            <button @click="saveQR()" class="w-full bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white py-3 md:py-4 rounded-xl font-semibold text-base md:text-lg shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-[1.02] flex items-center justify-center gap-2">
                <span>💾</span> 
                <span x-text="window.EDIT_QR ? 'Update QR' : 'Save QR'"></span>
            </button>
        </div>

        <!-- INFO MESSAGE -->
        <div class="text-xs text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700 pt-4">
            <p>You will be able to download the QR code in PDF or SVG after it has been generated. If you are using a fancy design, your QR code might not be readable. If that is the case, you can increase Error Correction to ensure optimal readability. It is recommended to test the QR code before saving it.</p>
        </div>
    </div>

    <!-- RIGHT PREVIEW PANEL -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 md:p-8 flex flex-col shadow-xl border border-gray-200 dark:border-gray-700">
        <!-- Preview Info -->
        <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400">
                <span>23°C Clear</span>
                <span x-text="new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                <span x-text="new Date().toLocaleDateString()"></span>
            </div>
        </div>

        <!-- QR Code Container -->
        <h3 class="font-semibold text-lg text-gray-700 dark:text-gray-300 mb-4">QR Code</h3>
        <div id="qrPreview" class="w-full max-w-[280px] mx-auto h-[280px] mb-6 flex items-center justify-center bg-white dark:bg-gray-900 p-4 rounded-lg border border-gray-300 dark:border-gray-600">
            <p class="text-gray-400 dark:text-gray-500 text-center">QR Preview will appear here</p>
        </div>

        <!-- Frame Text (if any) -->
        <div x-show="frameText" class="text-center mb-6" :style="`color: ${textColor}; font-family: ${textFont}`">
            <p x-text="frameText" class="text-sm font-medium"></p>
        </div>

        <!-- TEMPLATES -->
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Templates</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <button @click="applyTemplate('default')" 
                        :class="activeTemplate === 'default' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                        class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                    Default
                </button>
                <button @click="applyTemplate('dark')" 
                        :class="activeTemplate === 'dark' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                        class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                    Dark
                </button>
                <button @click="applyTemplate('colorful')" 
                        :class="activeTemplate === 'colorful' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                        class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                    Colorful
                </button>
                <button @click="applyTemplate('gradient')" 
                        :class="activeTemplate === 'gradient' ? 'bg-blue-100 dark:bg-blue-900 border-blue-500 text-blue-700 dark:text-blue-300' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'" 
                        class="p-2 md:p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 text-center transition-all duration-200">
                    Gradient
                </button>
            </div>
        </div>

        <!-- DOWNLOAD BUTTONS -->
        <div class="grid grid-cols-2 gap-3">
            <button @click="downloadQR('png')" 
                    :disabled="!qr" 
                    :class="qr ? 'bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white shadow-lg hover:shadow-xl' : 'bg-gray-400 dark:bg-gray-600 text-gray-200'" 
                    class="py-3 rounded-lg font-medium transition-all duration-200 hover:scale-[1.02]">
                Download PNG
            </button>
            <button @click="downloadQR('svg')" 
                    :disabled="!qr" 
                    :class="qr ? 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg hover:shadow-xl' : 'bg-gray-400 dark:bg-gray-600 text-gray-200'" 
                    class="py-3 rounded-lg font-medium transition-all duration-200 hover:scale-[1.02]">
                Download SVG
            </button>
        </div>
    </div>
</div>

@if(isset($qr))
<script>
    window.qrId = "{{ (string) $qr->_id }}";
    console.log('EDIT QR ID:', window.qrId);
</script>
@endif

<script>
// 🔥 यहाँ आपका COMPLETE ORIGINAL Alpine.js component code paste करें
// Define the Alpine.js component
document.addEventListener('alpine:init', () => {
    Alpine.data('qrBuilder', () => ({
        editId: null,
        name: '',
        mode: 'static',
        domain: 'https://qrul.co',
        payloadType: 'text',
        payloadValue: '',
        activeTemplate: 'default',
        openPanel: null, // 'templates', 'colors', 'design'
        
        // Color settings
        colorType: 'single',
        fg: '#000000',
        bg: '#ffffff',
        gradientStart: '#667eea',
        gradientStop: '#764ba2',
        gradientDirection: 'horizontal',
        eyeFrameColor: '#000000',
        eyeColor: '#000000',
        
        // Design settings
        logo: null,
        logoSize: 60,
        dotStyle: 'rounded',
        eyeStyle: 'rounded',
        frameText: '',
        frameColor: '#000000',
        textColor: '#000000',
        textFont: 'Arial',
        margin: 10,
        errorCorrection: 'M',
        
        // Data objects for different types
        staticSMS: {
            phone: '',
            message: ''
        },
        wifi: {
            ssid: '',
            password: '',
            encryption: 'WEP'
        },
        staticVcard: {
            firstName: '',
            lastName: '',
            organization: '',
            phone: '',
            cell: '',
            fax: '',
            email: '',
            website: ''
        },
        event: {
            title: '',
            description: '',
            location: '',
            url: '',
            start: '',
            end: ''
        },
        email: {
            to: '',
            subject: '',
            message: ''
        },
        application: {
            appStore: '',
            googlePlay: '',
            other: ''
        },
        whatsapp: {
            phone: '',
            message: ''
        },
        crypto: {
            type: 'bitcoin',
            address: ''
        },
        
        // QR instance
        qr: null,
        uploadedFile: null,
        
        get showPayloadInput() {
            return this.payloadType && this.payloadType !== '';
        },

        getPayloadLabel() {
            const labels = {
                'text': 'Text',
                'sms': 'SMS & Message',
                'wifi': 'WiFi',
                'vcard': 'vCard',
                'event': 'Event',
                'link': 'Link',
                'email': 'Email',
                'phone': 'Phone',
                'application': 'Application',
                'file': 'File',
                'whatsapp': 'WhatsApp',
                'cryptocurrency': 'Cryptocurrency'
            };
            return labels[this.payloadType] || 'Value';
        },

        getDynamicDomain() {
            return this.domain;
        },
getQRData() {
    let data = '';
    
    switch(this.payloadType) {
        case 'text':
            data = this.payloadValue || 'Sample Text';
            break;
            
        case 'sms':
            if (this.mode === 'static') {
                data = `smsto:${this.staticSMS.phone}:${this.staticSMS.message}`;
            } else {
                // 🔥 DYNAMIC SMS WITH TRACKING
                if (this.mode === 'dynamic') {
                    const trackingId = this.generateTrackingId('sms');
                    data = `${this.domain}/track/${trackingId}`;
                    
                    this.trackingData = {
                        id: trackingId,
                        type: 'sms',
                        phone: this.payloadValue?.split(':')[0] || '+1234567890',
                        message: this.payloadValue?.split(':')[1] || 'Hello'
                    };
                } else {
                    data = this.payloadValue || 'smsto:+1234567890:Hello';
                }
            }
            break;
            
        case 'wifi':
            const enc = this.wifi.encryption || 'nopass';
            data = `WIFI:T:${enc};S:${this.wifi.ssid};P:${this.wifi.password};;`;
            break;
            
        case 'vcard':
            if (this.mode === 'static') {
                data = `BEGIN:VCARD\nVERSION:3.0\nN:${this.staticVcard.lastName};${this.staticVcard.firstName}\nFN:${this.staticVcard.firstName} ${this.staticVcard.lastName}\nORG:${this.staticVcard.organization}\nTEL;TYPE=WORK,VOICE:${this.staticVcard.phone}\nTEL;TYPE=CELL:${this.staticVcard.cell}\nTEL;TYPE=FAX:${this.staticVcard.fax}\nEMAIL:${this.staticVcard.email}\nURL:${this.staticVcard.website}\nEND:VCARD`;
            } else {
                // 🔥 DYNAMIC VCARD WITH TRACKING
                if (this.mode === 'dynamic') {
                    const trackingId = this.generateTrackingId('vcard');
                    data = `${this.domain}/track/${trackingId}`;
                    
                    this.trackingData = {
                        id: trackingId,
                        type: 'vcard',
                        vcardData: this.payloadValue || 'BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nEND:VCARD'
                    };
                } else {
                    data = this.payloadValue || 'BEGIN:VCARD\nVERSION:3.0\nFN:John Doe\nEND:VCARD';
                }
            }
            break;
            
        case 'event':
            data = `BEGIN:VEVENT\nSUMMARY:${this.event.title}\nDESCRIPTION:${this.event.description}\nLOCATION:${this.event.location}\nURL:${this.event.url}\nDTSTART:${this.event.start}\nDTEND:${this.event.end}\nEND:VEVENT`;
            break;
            
        case 'link':
            // 🔥 DYNAMIC LINK WITH TRACKING
            if (this.mode === 'dynamic') {
                const trackingId = this.generateTrackingId('link');
                data = `${this.domain}/track/${trackingId}`;
                
                this.trackingData = {
                    id: trackingId,
                    type: 'link',
                    originalUrl: this.payloadValue || 'https://example.com'
                };
                
                // Show tracking info to user
                console.log('Tracking URL created:', data);
                console.log('Original URL:', this.trackingData.originalUrl);
            } else {
                // Static link - no tracking
                let url = this.payloadValue || 'https://example.com';
                if (url && !url.startsWith('http')) {
                    url = 'https://' + url;
                }
                data = url;
            }
            break;
            
        case 'email':
            // 🔥 DYNAMIC EMAIL WITH TRACKING
            if (this.mode === 'dynamic') {
                const trackingId = this.generateTrackingId('email');
                data = `${this.domain}/track/${trackingId}`;
                
                this.trackingData = {
                    id: trackingId,
                    type: 'email',
                    to: this.email.to || 'test@example.com',
                    subject: this.email.subject || 'Hello',
                    message: this.email.message || 'Message'
                };
            } else {
                // Static email
                data = `mailto:${this.email.to}?subject=${encodeURIComponent(this.email.subject)}&body=${encodeURIComponent(this.email.message)}`;
            }
            break;
            
        case 'phone':
            // 🔥 DYNAMIC PHONE WITH TRACKING
            if (this.mode === 'dynamic') {
                const trackingId = this.generateTrackingId('phone');
                data = `${this.domain}/track/${trackingId}`;
                
                this.trackingData = {
                    id: trackingId,
                    type: 'phone',
                    phone: this.payloadValue?.replace(/[^0-9+]/g, '') || '+1234567890'
                };
            } else {
                // Static phone
                data = `tel:${this.payloadValue.replace(/[^0-9+]/g, '')}`;
            }
            break;
            
        case 'application':
            data = this.application.other || this.application.appStore || this.application.googlePlay || 'https://example.com';
            break;
            
        case 'file':
            data = this.uploadedFile ? URL.createObjectURL(this.uploadedFile) : 'https://example.com/file.pdf';
            break;
            
        case 'whatsapp':
            // 🔥 DYNAMIC WHATSAPP WITH TRACKING
            if (this.mode === 'dynamic') {
                const trackingId = this.generateTrackingId('whatsapp');
                data = `${this.domain}/track/${trackingId}`;
                
                const encodedMessage = encodeURIComponent(this.whatsapp.message);
                this.trackingData = {
                    id: trackingId,
                    type: 'whatsapp',
                    phone: this.whatsapp.phone.replace(/[^0-9+]/g, ''),
                    message: this.whatsapp.message,
                    whatsappUrl: `https://wa.me/${this.whatsapp.phone.replace(/[^0-9+]/g, '')}?text=${encodedMessage}`
                };
            } else {
                // Static WhatsApp
                const encodedMessage = encodeURIComponent(this.whatsapp.message);
                data = `https://wa.me/${this.whatsapp.phone.replace(/[^0-9+]/g, '')}?text=${encodedMessage}`;
            }
            break;
            
        case 'cryptocurrency':
            data = `${this.crypto.type}:${this.crypto.address}`;
            break;
            
        default:
            data = 'Sample QR Code Data';
    }
    
    return data;
},

// 🔥 NEW: Generate unique tracking ID
generateTrackingId(type = 'qr') {
    const timestamp = Date.now();
    const randomStr = Math.random().toString(36).substring(2, 10);
    const typePrefix = type.substring(0, 3);
    return `${typePrefix}_${timestamp}_${randomStr}`;
},

// 🔥 NEW: Get tracking info for display
getTrackingInfo() {
    if (this.mode === 'dynamic' && this.trackingData) {
        return {
            trackingUrl: `${this.domain}/track/${this.trackingData.id}`,
            type: this.trackingData.type,
            originalData: this.trackingData
        };
    }
    return null;
},
        generateQR() {
            const data = this.getQRData();
            
            // Clear previous QR
            const qrContainer = document.getElementById('qrPreview');
            qrContainer.innerHTML = '';
            
            // Determine background color
            let background;
            if (this.colorType === 'gradient') {
                if (this.gradientDirection === 'horizontal') {
                    background = `linear-gradient(to right, ${this.gradientStart}, ${this.gradientStop})`;
                } else if (this.gradientDirection === 'vertical') {
                    background = `linear-gradient(to bottom, ${this.gradientStart}, ${this.gradientStop})`;
                } else if (this.gradientDirection === 'diagonal') {
                    background = `linear-gradient(45deg, ${this.gradientStart}, ${this.gradientStop})`;
                } else {
                    background = `radial-gradient(${this.gradientStart}, ${this.gradientStop})`;
                }
            } else {
                background = this.bg;
            }

            // Create QR code with all design options
        const config = {
    width: 260,
    height: 260,
    data: data,

    dotsOptions: {
        color: this.colorType === 'gradient'
            ? this.gradientStart
            : this.fg,
        type: this.dotStyle
    },

    backgroundOptions: {
        color: this.bg   // ✅ ALWAYS solid
    },

    qrOptions: {
        errorCorrectionLevel: this.errorCorrection
    },

    margin: parseInt(this.margin)
};

            // Add logo if uploaded
            if (this.logo) {
                config.image = this.logo;
                config.imageOptions = {
                    crossOrigin: 'anonymous',
                    margin: 5,
                    imageSize: this.logoSize / 100
                };
            }

            this.qr = new QRCodeStyling(config);
            this.qr.append(qrContainer);
        },

        handleLogoUpload(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.logo = e.target.result;
                    if (this.qr) {
                        this.generateQR();
                    }
                };
                reader.readAsDataURL(file);
            }
        },

        handleFileUpload(event) {
            const file = event.target.files[0];
            if (file) {
                if (file.size > 2 * 1024 * 1024) {
                    alert('File size must be less than 2MB');
                    event.target.value = '';
                    return;
                }
                
                const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
                if (!validTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and PDF files are allowed');
                    event.target.value = '';
                    return;
                }
                
                this.uploadedFile = file;
            }
        },

        downloadQR(format) {
            if (this.qr) {
                const name = this.name || 'qr-code';
                this.qr.download({
                    name: name,
                    extension: format
                });
            } else {
                alert('Please generate QR code first');
            }
        },

        async saveQR() {
            // ================= VALIDATION =================
            if (!this.name.trim()) {
                alert('Please enter QR name');
                return;
            }

            if (
                !this.payloadValue?.toString().trim() &&
                !['file', 'wifi', 'vcard', 'event', 'email', 'whatsapp', 'application'].includes(this.payloadType)
            ) {
                alert('Please enter QR content');
                return;
            }

            if (!this.qr) {
                alert('Please generate QR first');
                return;
            }

            // ================= GET QR IMAGE (PNG + SVG) =================
            const qrPngBlob = await this.qr.getRawData('png');
            const qrSvgBlob = await this.qr.getRawData('svg');

            const pngBase64 = await new Promise(resolve => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.readAsDataURL(qrPngBlob);
            });

            const svgText = await qrSvgBlob.text();
            const svgBase64 = 'data:image/svg+xml;base64,' + btoa(svgText);

            // ================= DESIGN DATA =================
            const designData = {
                color_type: this.colorType,
                foreground: this.fg,
                background: this.bg,
                gradient_start: this.gradientStart,
                gradient_stop: this.gradientStop,
                gradient_direction: this.gradientDirection,
                eye_frame_color: this.eyeFrameColor,
                eye_color: this.eyeColor,
                dot_style: this.dotStyle,
                eye_style: this.eyeStyle,
                frame_text: this.frameText,
                frame_color: this.frameColor,
                text_color: this.textColor,
                text_font: this.textFont,
                margin: this.margin,
                error_correction: this.errorCorrection,
                logo_size: this.logoSize,
                has_logo: !!this.logo
            };

            // ================= PAYLOAD (BACKEND SAFE) =================
            let payloadObject;

            if (this.payloadType === 'sms' && this.mode === 'static') {
                payloadObject = this.staticSMS;
            } else if (this.payloadType === 'wifi') {
                payloadObject = this.wifi;
            } else if (this.payloadType === 'vcard' && this.mode === 'static') {
                payloadObject = this.staticVcard;
            } else if (this.payloadType === 'event') {
                payloadObject = this.event;
            } else if (this.payloadType === 'email') {
                payloadObject = this.email;
            } else if (this.payloadType === 'application') {
                payloadObject = this.application;
            } else if (this.payloadType === 'whatsapp') {
                payloadObject = this.whatsapp;
            } else if (this.payloadType === 'cryptocurrency') {
                payloadObject = this.crypto;
            } else {
                payloadObject = { value: this.payloadValue };
            }

            // ================= API DATA =================
            const saveData = {
                name: this.name,
                mode: this.mode,
                domain: this.domain,
                payload_type: this.payloadType,
                payload_value: JSON.stringify(payloadObject),
                design: designData,
                qr_png: pngBase64,
                qr_svg: svgBase64
            };

            // ================= LOADING STATE =================
            const saveBtn = document.querySelector('button[onclick*="saveQR"]');
            const originalText = saveBtn?.innerHTML;
            if (saveBtn) {
                saveBtn.innerHTML = window.EDIT_QR ? 'Updating...' : 'Saving...';
                saveBtn.disabled = true;
            }

            try {
                const isEditMode = !!window.EDIT_QR;
                const apiUrl = isEditMode
                    ? `/qr/update/${window.qrId}`
                    : '/qr/store';
                const method = 'POST';

                const response = await fetch(apiUrl, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(saveData)
                });

                const data = await response.json();

                if (data.success) {
                    alert(isEditMode ? 'QR updated successfully!' : 'QR saved successfully!');
                    setTimeout(() => {
                        window.location.href = '{{ route("qr.builder") }}';
                    }, 1500);
                } else {
                    alert('Error: ' + (data.message || 'Failed to save QR'));
                }
            } catch (error) {
                console.error(error);
                alert('Network error. Please try again.');
            } finally {
                if (saveBtn) {
                    saveBtn.innerHTML = originalText;
                    saveBtn.disabled = false;
                }
            }
        },

        applyTemplate(template) {
            this.activeTemplate = template;
            this.openPanel = 'templates';
            
            switch(template) {
                case 'dark':
                    this.colorType = 'single';
                    this.fg = '#ffffff';
                    this.bg = '#000000';
                    this.eyeColor = '#ffffff';
                    this.eyeFrameColor = '#ffffff';
                    break;
                case 'colorful':
                    this.colorType = 'single';
                    this.fg = '#4F46E5';
                    this.bg = '#FEF3C7';
                    this.eyeColor = '#DC2626';
                    this.eyeFrameColor = '#7C3AED';
                    break;
                case 'gradient':
                    this.colorType = 'gradient';
                    this.fg = '#000000';
                    this.gradientStart = '#667eea';
                    this.gradientStop = '#764ba2';
                    this.gradientDirection = 'diagonal';
                    this.eyeColor = '#7C3AED';
                    this.eyeFrameColor = '#5B21B6';
                    break;
                default:
                    this.colorType = 'single';
                    this.fg = '#000000';
                    this.bg = '#ffffff';
                    this.eyeColor = '#000000';
                    this.eyeFrameColor = '#000000';
            }
            
            // Regenerate QR if it exists
            if (this.qr) {
                this.generateQR();
            }
        },

        // Initialize with a default QR
        init() {
            // Set domain to qrul.co as per your design
 fetch('/qr-builder/get-domain')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.domain) {
                this.domain = data.domain;
            } else {
                this.domain = 'https://qrul.co'; // Fallback
            }
        })
        .catch(() => {
            this.domain = 'https://qrul.co'; // Fallback
        });

            // 🔥 EDIT MODE
            if (window.EDIT_QR) {
                const qr = window.EDIT_QR;
                const s  = window.EDIT_SETTINGS || {};

                // BASIC
                this.name        = qr.title;
                this.mode        = qr.qr_mode;
                this.payloadType = qr.qr_type;
                this.domain      = qr.domain || 'https://qrul.co';

                // PAYLOAD
                try {
                    const parsed = JSON.parse(qr.qr_data);
                    if (this.payloadType === 'sms' && this.mode === 'static') {
                        this.staticSMS = parsed;
                    } else if (this.payloadType === 'wifi') {
                        this.wifi = parsed;
                    } else if (this.payloadType === 'vcard' && this.mode === 'static') {
                        this.staticVcard = parsed;
                    } else if (this.payloadType === 'event') {
                        this.event = parsed;
                    } else if (this.payloadType === 'email') {
                        this.email = parsed;
                    } else if (this.payloadType === 'application') {
                        this.application = parsed;
                    } else if (this.payloadType === 'whatsapp') {
                        this.whatsapp = parsed;
                    } else if (this.payloadType === 'cryptocurrency') {
                        this.crypto = parsed;
                    } else {
                        this.payloadValue = parsed?.value ?? qr.qr_data;
                    }
                } catch {
                    this.payloadValue = qr.qr_data;
                }

                // DESIGN
                this.colorType        = s.color_type ?? 'single';
                this.fg               = s.foreground ?? '#000000';
                this.bg               = s.background ?? '#ffffff';
                this.gradientStart    = s.gradient_start ?? '#667eea';
                this.gradientStop     = s.gradient_stop ?? '#764ba2';
                this.gradientDirection= s.gradient_direction ?? 'horizontal';
                this.eyeFrameColor    = s.eye_frame_color ?? '#000000';
                this.eyeColor         = s.eye_color ?? '#000000';
                this.dotStyle         = s.dot_style ?? 'rounded';
                this.eyeStyle         = s.eye_style ?? 'rounded';
                this.frameText        = s.frame_text ?? '';
                this.frameColor       = s.frame_color ?? '#000000';
                this.textColor        = s.text_color ?? '#000000';
                this.textFont         = s.text_font ?? 'Arial';
                this.margin           = s.margin ?? 10;
                this.errorCorrection  = s.error_correction ?? 'M';
                this.logoSize         = s.logo_size ?? 60;

                // PREVIEW EXISTING QR IMAGE
                setTimeout(() => {
                    this.generateQR();
                }, 200);

            } else {
                // CREATE MODE - Set default name as per your design
                this.name = 'e.g. For Instagram';
                setTimeout(() => {
                    this.generateQR();
                }, 100);
            }
        }
    }));
});
</script>

<!-- Responsive Styles -->
<style>
@media (max-width: 768px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }
    
    #qrPreview {
        width: 240px;
        height: 240px;
        max-width: 100%;
    }
    
    .grid-cols-3 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .text-base {
        font-size: 14px;
    }
    
    .p-8 {
        padding: 1rem;
    }
    
    .gap-10 {
        gap: 1.5rem;
    }
}

@media (max-width: 640px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .grid-cols-3 {
        grid-template-columns: 1fr;
    }
    
    .flex-col {
        flex-direction: column;
    }
    
    .space-y-8 > * + * {
        margin-top: 1.5rem;
    }
}

/* Dark mode transitions */
.dark .transition-all {
    transition: all 0.3s ease;
}

/* Smooth scrolling */
html {
    scroll-behavior: smooth;
}

/* Custom scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.dark ::-webkit-scrollbar-track {
    background: #374151;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.dark ::-webkit-scrollbar-thumb {
    background: #4b5563;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.dark ::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Focus styles */
*:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
}

*:focus-visible {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Smooth transitions */
button, input, select, textarea, .transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Loading animation */
@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>

@endsection