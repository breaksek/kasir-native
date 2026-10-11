/**
 * Bluetooth Printer Manager
 * Support: Printer thermal ESC/POS (58mm & 80mm)
 * Browser: Chrome/Edge (Desktop & Android)
 */

const BTPrinter = (() => {
    let device = null;
    let characteristic = null;

    // UUID umum untuk printer thermal
    const PRINTER_SERVICES = [
        0xFF00, 0xFFE0, 0xFFF0, 0x18F0,
        '0000ff00-0000-1000-8000-00805f9b34fb',
        '0000ffe0-0000-1000-8000-00805f9b34fb',
        '0000fff0-0000-1000-8000-00805f9b34fb',
        '000018f0-0000-1000-8000-00805f9b34fb',
        '49535343-fe7d-4ae5-8fa9-9fafd205e455', // HM-10
        'e7810a71-73ae-499d-8c15-faa9aef0c3f2'  // Umum printer
    ];

    /**
     * Connect ke printer Bluetooth
     */
    async function connect() {
        if (!navigator.bluetooth) {
            throw new Error('Browser tidak mendukung Web Bluetooth. Gunakan Chrome/Edge terbaru.');
        }

        try {
            device = await navigator.bluetooth.requestDevice({
                acceptAllDevices: true,
                optionalServices: PRINTER_SERVICES
            });

            console.log('Device:', device.name);

            const server = await device.gatt.connect();
            const services = await server.getPrimaryServices();

            for (const service of services) {
                try {
                    const chars = await service.getCharacteristics();
                    for (const char of chars) {
                        // Cari characteristic yang bisa write
                        if (char.properties.write || char.properties.writeWithoutResponse) {
                            characteristic = char;
                            console.log('✅ Characteristic found:', char.uuid);
                            break;
                        }
                    }
                    if (characteristic) break;
                } catch (e) {
                    console.warn('Skip service:', service.uuid, e.message);
                }
            }

            if (!characteristic) throw new Error('Tidak ada karakteristik write ditemukan');

            device.addEventListener('gattserverdisconnected', () => {
                console.log('Printer terputus');
                characteristic = null;
                if (window.onPrinterDisconnect) window.onPrinterDisconnect();
            });

            return { name: device.name || 'Printer' };
        } catch (err) {
            console.error('Connect error:', err);
            throw err;
        }
    }

    /**
     * Kirim data ke printer (chunked)
     */
    async function write(data) {
        if (!characteristic) throw new Error('Printer belum terhubung');

        const CHUNK_SIZE = 20; // BLE limit
        const bytes = new Uint8Array(data);

        for (let i = 0; i < bytes.length; i += CHUNK_SIZE) {
            const chunk = bytes.slice(i, i + CHUNK_SIZE);
            if (characteristic.properties.writeWithoutResponse) {
                await characteristic.writeValueWithoutResponse(chunk);
            } else {
                await characteristic.writeValue(chunk);
            }
            await new Promise(r => setTimeout(r, 20)); // delay antar chunk
        }
    }

    /**
     * ESC/POS Command Builder
     */
    const ESC = 0x1B, GS = 0x1D, LF = 0x0A;

    function textToBytes(str) {
        // Convert ke Latin-1/ASCII
        const bytes = [];
        for (let i = 0; i < str.length; i++) {
            const c = str.charCodeAt(i);
            bytes.push(c < 256 ? c : 0x3F); // '?' untuk non-ASCII
        }
        return bytes;
    }

    const cmd = {
        init:        () => [ESC, 0x40],
        alignLeft:   () => [ESC, 0x61, 0],
        alignCenter: () => [ESC, 0x61, 1],
        alignRight:  () => [ESC, 0x61, 2],
        boldOn:      () => [ESC, 0x45, 1],
        boldOff:     () => [ESC, 0x45, 0],
        doubleOn:    () => [GS, 0x21, 0x11], // double width+height
        doubleOff:   () => [GS, 0x21, 0x00],
        fontBig:     () => [ESC, 0x21, 0x30],
        fontNormal:  () => [ESC, 0x21, 0x00],
        feed:        (n = 1) => Array(n).fill(LF),
        cut:         () => [GS, 0x56, 0x42, 0x00],
        line:        (char = '-', len = 32) => textToBytes(char.repeat(len) + '\n')
    };

    /**
     * Format 2 kolom (kiri-kanan)
     */
    function padLine(left, right, width = 32) {
        left = String(left);
        right = String(right);
        const space = Math.max(1, width - left.length - right.length);
        return left + ' '.repeat(space) + right + '\n';
    }

    /**
     * Format 3 kolom (nama | qty x harga | subtotal)
     */
    function padItemLine(nama, qty, harga, subtotal, width = 32) {
        let line1 = nama + '\n';
        let left = `  ${qty} x ${formatRp(harga)}`;
        let right = formatRp(subtotal);
        let space = Math.max(1, width - left.length - right.length);
        let line2 = left + ' '.repeat(space) + right + '\n';
        return line1 + line2;
    }

    function formatRp(n) {
        return 'Rp' + Number(n).toLocaleString('id-ID');
    }

    /**
     * Cetak Struk
     * @param {Object} data - Data transaksi
     * @param {Object} toko - Data toko
     * @param {number} width - Lebar karakter (32 untuk 58mm, 48 untuk 80mm)
     */
    async function printStruk(data, toko, width = 32) {
        let buf = [];

        // Header
        buf.push(...cmd.init());
        buf.push(...cmd.alignCenter());
        buf.push(...cmd.boldOn());
        buf.push(...cmd.doubleOn());
        buf.push(...textToBytes(toko.nama_toko + '\n'));
        buf.push(...cmd.doubleOff());
        buf.push(...cmd.boldOff());
        buf.push(...textToBytes(toko.alamat + '\n'));
        if (toko.telepon) buf.push(...textToBytes('Telp: ' + toko.telepon + '\n'));
        buf.push(...cmd.line('='));

        // Info Transaksi
        buf.push(...cmd.alignLeft());
        buf.push(...textToBytes(padLine(data.kode, data.tanggal, width)));
        buf.push(...cmd.line('-'));

        // Items
        for (const it of data.items) {
            buf.push(...textToBytes(padItemLine(it.nama, it.qty, it.harga, it.harga * it.qty, width)));
        }

        buf.push(...cmd.line('-'));
        buf.push(...textToBytes(padLine('TOTAL', formatRp(data.total), width)));
        buf.push(...textToBytes(padLine('BAYAR', formatRp(data.bayar), width)));
        buf.push(...textToBytes(padLine('KEMBALI', formatRp(data.kembalian), width)));
        buf.push(...cmd.line('='));

        // Footer
        buf.push(...cmd.alignCenter());
        buf.push(...textToBytes(toko.footer_struk + '\n'));
        buf.push(...textToBytes('\n\n\n\n'));
        buf.push(...cmd.cut());

        await write(new Uint8Array(buf));
    }

    /**
     * Test Print
     */
    async function testPrint() {
        let buf = [];
        buf.push(...cmd.init());
        buf.push(...cmd.alignCenter());
        buf.push(...cmd.boldOn());
        buf.push(...cmd.doubleOn());
        buf.push(...textToBytes('TEST PRINT\n'));
        buf.push(...cmd.doubleOff());
        buf.push(...cmd.boldOff());
        buf.push(...textToBytes('Printer Bluetooth OK\n'));
        buf.push(...textToBytes(new Date().toLocaleString('id-ID') + '\n\n\n'));
        buf.push(...cmd.cut());
        await write(new Uint8Array(buf));
    }

    function isConnected() { return !!characteristic; }
    function getDeviceName() { return device?.name || '-'; }

    async function disconnect() {
        if (device?.gatt?.connected) device.gatt.disconnect();
        device = null;
        characteristic = null;
    }

    return { connect, printStruk, testPrint, isConnected, getDeviceName, disconnect };
})();