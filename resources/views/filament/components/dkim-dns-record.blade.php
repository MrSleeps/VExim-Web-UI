<div class="space-y-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
        <h3 class="text-lg font-medium text-blue-800 dark:text-blue-200 mb-3">DKIM DNS Record for {{ $domain }}</h3>
        
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">TXT Record Name:</label>
                <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded font-mono text-sm break-all">
                    {{ $name }}
                </div>
                <p class="text-xs text-gray-500 mt-1">Create a TXT record with this name in your DNS zone</p>
            </div>
            
            <div>
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">TXT Record Value:</label>
                <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-800 rounded font-mono text-sm break-all">
                    {{ $value }}
                </div>
                <p class="text-xs text-gray-500 mt-1">Copy this entire value as the TXT record content</p>
            </div>
            
            <div class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                <strong>Selector:</strong> {{ $selector }}
            </div>
        </div>
        
        <div class="mt-4 flex gap-2">
            <button onclick="copyToClipboard('{{ addslashes($name) }}')" class="text-xs bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 rounded px-2 py-1">
                Copy Name
            </button>
            <button onclick="copyToClipboard('{{ addslashes($value) }}')" class="text-xs bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 rounded px-2 py-1">
                Copy Value
            </button>
        </div>
    </div>
    
    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
        <p class="text-sm text-yellow-800 dark:text-yellow-200">
            <strong>Note:</strong> After adding the DNS record, DNS propagation may take up to 24-48 hours. 
            You can verify the record using: <code class="text-xs">dig TXT {{ $name }}</code>
        </p>
    </div>
</div>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Optional: Show a temporary notification
        const btn = event.target;
        const originalText = btn.textContent;
        btn.textContent = 'Copied!';
        setTimeout(function() {
            btn.textContent = originalText;
        }, 2000);
    });
}
</script>
