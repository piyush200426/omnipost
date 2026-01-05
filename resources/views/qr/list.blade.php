@extends('layouts.index')
@section('title','QR Codes')

@section('content')

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold">QR Codes</h1>
    <a href="{{ route('qr.builder.create') }}"
       class="bg-purple-600 hover:bg-purple-700 text-white px-5 py-2.5 rounded-lg">
        Create QR
    </a>
</div>

@if($qrCodes->count() === 0)
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <h2 class="text-2xl font-semibold text-gray-800">
        You haven't created any QR Codes yet
    </h2>
    <p class="text-gray-500 mt-2">
        Create your first QR code to get started.
    </p>
    <a href="{{ route('qr.builder.create') }}"
       class="mt-6 px-6 py-3 bg-purple-600 text-white rounded-xl">
        ➕ Create QR
    </a>
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
@foreach($qrCodes as $qr)
<div class="bg-white rounded-xl border p-4 relative group">
<img
  src="{{ $qr->qr_image_url 
        ? asset('storage/'.$qr->qr_image_url) 
        : asset('images/qr-placeholder.png') }}"
  alt="{{ $qr->title }}"
  class="w-full h-48 object-contain mb-3 rounded-lg border"
/>



    <h3 class="font-semibold text-gray-800">{{ $qr->title }}</h3>
    <p class="text-xs text-gray-500">{{ $qr->scans }} scans</p>
    
    <div class="flex gap-2 mt-4">
     <button
    type="button"
    onclick="openQrDetails(this)"
    data-qr-id="{{ $qr->_id }}"
    data-title="{{ $qr->title }}"
    data-type="{{ $qr->qr_type }}"
    data-mode="{{ $qr->qr_mode }}"
    data-scans="{{ $qr->scans }}"
data-image="{{ asset('storage/'.$qr->qr_image_url) }}"
data-download="{{ $qr->qr_image_url ? asset('storage/'.$qr->qr_image_url) : '' }}"
    data-status="{{ $qr->is_active ? 'Active' : 'Inactive' }}"
    data-created="{{ $qr->created_at->format('M d, Y') }}"
    data-short-url="{{ $qr->short_url }}"
data-qr-data="{{ e($qr->qr_mode === 'dynamic' ? url('/qr/'.$qr->short_url) : $qr->qr_data) }}"
    data-edit-url="{{ route('qr.edit', $qr->_id) }}"
    class="flex-1 text-center px-3 py-1.5 text-sm rounded-lg border border-blue-500 text-blue-600 hover:bg-blue-50">
    👁 View
</button>

        <a href="{{ route('qr.edit', $qr->_id) }}"
           class="flex-1 text-center px-3 py-1.5 text-sm rounded-lg border border-indigo-500 text-indigo-600 hover:bg-indigo-50">
            ✏️ Edit
        </a>
        
        <form method="POST"
              action="{{ route('qr.delete', $qr->_id) }}"
              onsubmit="return confirm('Are you sure?');">
            @csrf
            @method('DELETE')
            <button type="submit"
           class="flex-1 text-center px-3 py-1.5 text-sm rounded-lg border border-indigo-500 text-indigo-600 hover:bg-indigo-50">
                🗑 Delete
            </button>
        </form>
    </div>
</div>
@endforeach
</div>
@endif

<!-- QR DETAILS MODAL -->
<div id="qrModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="p-6">
            <!-- Modal Header -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">QR Details</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            
            <!-- Modal Content -->
            <div class="space-y-6">
                <!-- QR Image -->
                <div class="flex justify-center">
                    <img id="modalQrImage" src="" alt="QR Code" class="w-64 h-64 object-contain border rounded-lg">
                </div>
                
                <!-- Details Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Title</label>
                        <p id="modalTitle" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">QR Type</label>
                        <p id="modalType" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">QR Mode</label>
                        <p id="modalMode" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Total Scans</label>
                        <p id="modalScans" class="text-lg font-semibold text-gray-800"></p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Status</label>
                        <span id="modalStatus" class="px-3 py-1 text-xs font-semibold rounded-full"></span>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-500">Created Date</label>
                        <p id="modalCreated" class="text-sm text-gray-600"></p>
                    </div>
                </div>
                
                <!-- Short URL (for dynamic QR) -->
                <div id="shortUrlSection" class="hidden">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Short URL</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="modalShortUrl" readonly 
                               class="flex-1 border border-gray-300 rounded-lg px-3 py-2 bg-gray-50">
                        <button onclick="copyShortUrl()" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Copy
                        </button>
                    </div>
                </div>
                
                <!-- QR Data -->
                <div>
                    <label class="block text-sm font-medium text-gray-500 mb-2">QR Data/Content</label>
                    <textarea id="modalData" rows="3" readonly 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-sm"></textarea>
                </div>
                
                <!-- Buttons -->
                <div class="flex gap-3 pt-4 border-t">
                    <a id="modalEditLink" href="#"
                       class="flex-1 text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                        ✏️ Edit QR
                    </a>
                    <a id="modalDownloadLink" href="#" target="_blank"
                       class="flex-1 text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        ⬇️ Download QR
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to open modal with QR details
function openQrDetails(button) {

    // 🔑 short code
    const shortUrl = button.getAttribute('data-short-url');

    // 🖼️ QR IMAGE URL (SAFE FOR LOCAL + LIVE)
const fullImageUrl = button.getAttribute('data-image');


    // OTHER DATA
    const qrId = button.getAttribute('data-qr-id');
    const title = button.getAttribute('data-title');
    const type = button.getAttribute('data-type');
    const mode = button.getAttribute('data-mode');
    const scans = button.getAttribute('data-scans');
    const status = button.getAttribute('data-status');
    const created = button.getAttribute('data-created');
    const qrData = button.getAttribute('data-qr-data');
    const editUrl = button.getAttribute('data-edit-url');

    // SET IMAGE + DOWNLOAD
    document.getElementById('modalQrImage').src = fullImageUrl;
document.getElementById('modalDownloadLink').href =
    button.getAttribute('data-download');
document.getElementById('modalTitle').textContent = title;

    // TEXT DATA
    document.getElementById('modalType').textContent = type;
    document.getElementById('modalMode').textContent = mode;
    document.getElementById('modalScans').textContent = scans;
    document.getElementById('modalCreated').textContent = created;
    document.getElementById('modalData').value = qrData;
    document.getElementById('modalEditLink').href = editUrl;

    // STATUS
    const statusElem = document.getElementById('modalStatus');
    if (status === 'Active') {
        statusElem.textContent = 'Active';
        statusElem.className =
            'px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800';
    } else {
        statusElem.textContent = 'Inactive';
        statusElem.className =
            'px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800';
    }

    // SHORT URL (only dynamic)
    const shortUrlSection = document.getElementById('shortUrlSection');
    if (mode === 'dynamic' && shortUrl) {
        shortUrlSection.classList.remove('hidden');
     document.getElementById('modalShortUrl').value =
    `${window.location.origin}/qr/${shortUrl}`;

    } else {
        shortUrlSection.classList.add('hidden');
    }

    // SHOW MODAL
    document.getElementById('qrModal').classList.remove('hidden');
}

// Function to copy short URL
function copyShortUrl() {
    const input = document.getElementById('modalShortUrl');
    input.select();
    input.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(input.value)
        .then(() => {
            // Show success message
            const btn = document.querySelector('[onclick="copyShortUrl()"]');
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            btn.classList.remove('bg-blue-600');
            btn.classList.add('bg-green-600');
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.classList.remove('bg-green-600');
                btn.classList.add('bg-blue-600');
            }, 2000);
        })
        .catch(err => {
            console.error('Failed to copy:', err);
            alert('Failed to copy URL');
        });
}

// Function to close modal
function closeModal() {
    document.getElementById('qrModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target.id === 'qrModal') {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('qrModal').classList.contains('hidden')) {
        closeModal();
    }
});
</script>

@endsection