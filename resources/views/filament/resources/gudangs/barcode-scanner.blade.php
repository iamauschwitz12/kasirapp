<script>
document.addEventListener('DOMContentLoaded', function() {
    let barcodeBuffer = '';
    let barcodeTimeout;
    
    // Barcode scanner biasanya mengirim input sangat cepat (< 100ms per karakter)
    // dan diakhiri dengan Enter
    document.addEventListener('keypress', function(e) {
        // Jika ada input/textarea/select yang sedang fokus, skip listener ini
        const activeElement = document.activeElement;
        const isInputFocused = activeElement && 
            (activeElement.tagName === 'INPUT' || 
             activeElement.tagName === 'TEXTAREA' || 
             activeElement.tagName === 'SELECT' ||
             activeElement.isContentEditable);
        
        // Hanya proses jika tidak ada input yang fokus
        if (isInputFocused && activeElement !== document.body) {
            return;
        }
        
        // Clear timeout sebelumnya
        clearTimeout(barcodeTimeout);
        
        // Jika Enter ditekan, proses barcode
        if (e.key === 'Enter' && barcodeBuffer.length > 0) {
            e.preventDefault();
            processBarcode(barcodeBuffer.trim());
            barcodeBuffer = '';
            return;
        }
        
        // Tambahkan karakter ke buffer
        barcodeBuffer += e.key;
        
        // Set timeout untuk reset buffer jika input terlalu lambat (bukan dari scanner)
        barcodeTimeout = setTimeout(function() {
            barcodeBuffer = '';
        }, 100); // Reset jika lebih dari 100ms antara karakter
    });
    
    function processBarcode(barcode) {
        console.log('Barcode detected:', barcode);
        
        // Call Livewire method to find product
        @this.call('findProductByBarcode', barcode).then(result => {
            if (result.success && result.product) {
                fillProductData(result.product);
                
                // Show success notification
                new FilamentNotification()
                    .title('Produk ditambahkan')
                    .body(result.product.barcode_number + ' - ' + result.product.nama_produk)
                    .success()
                    .send();
            } else {
                // Show error notification
                new FilamentNotification()
                    .title('Produk tidak ditemukan')
                    .body('Barcode "' + barcode + '" tidak ditemukan dalam database')
                    .danger()
                    .send();
            }
        }).catch(error => {
            console.error('Error finding product:', error);
            new FilamentNotification()
                .title('Error')
                .body('Gagal mencari produk: ' + error.message)
                .danger()
                .send();
        });
    }
    
    function fillProductData(product) {
        // Cari semua repeater items
        const repeaterItems = document.querySelectorAll('[wire\\:key^="products"]');
        let targetItem = null;
        let targetIndex = -1;
        
        // Cari item yang product_id-nya masih kosong
        repeaterItems.forEach((item, index) => {
            const selectInput = item.querySelector('[wire\\:model*="product_id"]');
            if (selectInput) {
                const wireName = selectInput.getAttribute('wire:model') || 
                                 selectInput.getAttribute('wire:model.live') ||
                                 selectInput.getAttribute('wire:model.defer');
                
                // Check if this field is empty
                if (wireName) {
                    const value = @this.get(wireName);
                    if (!value || value === '' || value === null) {
                        targetItem = item;
                        targetIndex = index;
                        return false; // break loop
                    }
                }
            }
        });
        
        // Jika tidak ada item kosong, gunakan item terakhir
        if (!targetItem && repeaterItems.length > 0) {
            targetIndex = repeaterItems.length - 1;
            targetItem = repeaterItems[targetIndex];
        }
        
        if (targetItem !== null && targetIndex >= 0) {
            // Set product_id via Livewire
            const productIdPath = 'data.products.' + targetIndex + '.product_id';
            @this.set(productIdPath, product.id);
            
            console.log('Product added to repeater index:', targetIndex);
        } else {
            console.warn('No repeater item found to fill product data');
        }
    }
});
</script>