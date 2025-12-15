<?php
session_start();
include 'koneksi.php';
include 'functions.php';

// Cek login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kalkulator Harga - RSUD Pringsewu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }

        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }

        .section-title::before {
            content: "📊";
            margin-right: 10px;
            font-size: 20px;
        }

        .input-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-field {
            display: flex;
            flex-direction: column;
        }

        .form-field label {
            font-size: 13px;
            font-weight: 500;
            color: #555;
            margin-bottom: 5px;
        }

        .form-field input {
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-field input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .items-container {
            margin-top: 20px;
        }

        .item-row {
            display: grid;
            grid-template-columns: 50px 2fr 1fr 1fr 60px;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-add {
            background: #17a2b8;
            color: white;
            margin-top: 10px;
        }

        .btn-add:hover {
            background: #138496;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }

        .results-section {
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .price {
            text-align: right;
            font-weight: 600;
            color: #28a745;
        }

        .number {
            text-align: center;
        }

        .result-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .result-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .summary-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .summary-item:last-child {
            border-bottom: none;
            font-size: 16px;
            font-weight: 700;
            padding-top: 12px;
        }

        .summary-label {
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .container {
                box-shadow: none;
            }
            .section, .action-buttons {
                display: none;
            }
        }

        .no-print {
            display: block;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧮 Kalkulator Harga</h1>
        <p class="subtitle">Sistem Perhitungan Harga dengan Berbagai Skenario PPN, Diskon, dan Ongkir</p>

        <form id="calculatorForm">
            <!-- Data Umum -->
            <div class="section no-print">
                <div class="section-title">Data Umum</div>
                <div class="input-group">
                    <div class="form-field">
                        <label for="hargaTotal">Harga Total (Rp)</label>
                        <input type="number" id="hargaTotal" placeholder="Contoh: 1000000" step="0.01" required>
                    </div>
                    <div class="form-field">
                        <label for="hargaDiskon">Harga Diskon (Rp)</label>
                        <input type="number" id="hargaDiskon" placeholder="Contoh: 50000" step="0.01" value="0">
                    </div>
                    <div class="form-field">
                        <label for="ongkir">Ongkos Kirim (Rp)</label>
                        <input type="number" id="ongkir" placeholder="Contoh: 25000" step="0.01" value="0">
                    </div>
                </div>
            </div>

            <!-- Data Item -->
            <div class="section no-print">
                <div class="section-title">Data Item Barang</div>
                <div class="items-container">
                    <div class="item-row">
                        <label style="font-weight: 600; font-size: 12px;">No.</label>
                        <label style="font-weight: 600; font-size: 12px;">Nama Item</label>
                        <label style="font-weight: 600; font-size: 12px;">Nilai Hitung (Rp)</label>
                        <label style="font-weight: 600; font-size: 12px;">Jumlah Barang</label>
                        <label style="font-weight: 600; font-size: 12px;">Aksi</label>
                    </div>
                    <div id="itemsList">
                        <!-- Item rows will be added here dynamically -->
                    </div>
                    <button type="button" class="btn btn-add" onclick="addItem()">+ Tambah Item</button>
                </div>
            </div>

            <div class="action-buttons no-print">
                <button type="submit" class="btn btn-primary">🔍 Hitung Semua Skenario</button>
                <button type="button" class="btn btn-success" onclick="window.print()">🖨️ Cetak Hasil</button>
                <button type="button" class="btn btn-danger" onclick="resetForm()">🔄 Reset</button>
            </div>
        </form>

        <!-- Results Section -->
        <div id="resultsSection" class="results-section" style="display: none;">
            
        </div>
    </div>

    <script>
        let itemCount = 0;

        // Add only one item on page load
        window.onload = function() {
            addItem();
        };

        function addItem() {
            itemCount++;
            const itemsList = document.getElementById('itemsList');
            const itemRow = document.createElement('div');
            itemRow.className = 'item-row';
            itemRow.id = 'item-' + itemCount;
            itemRow.innerHTML = `
                <input type="text" value="${itemCount}" readonly style="text-align: center; background: #f0f0f0;">
                <input type="text" class="item-name" placeholder="Nama barang" required>
                <input type="number" class="item-value" placeholder="Nilai hitung" step="0.01" required>
                <input type="number" class="item-qty" placeholder="Jumlah" step="1" min="1" required>
                <button type="button" class="btn btn-danger" onclick="removeItem(${itemCount})">✕</button>
            `;
            itemsList.appendChild(itemRow);
        }

        function removeItem(id) {
            const item = document.getElementById('item-' + id);
            if (item) {
                item.remove();
                renumberItems();
            }
        }

        function renumberItems() {
            const items = document.querySelectorAll('#itemsList .item-row');
            items.forEach((item, index) => {
                const numberInput = item.querySelector('input[readonly]');
                if (numberInput) {
                    numberInput.value = index + 1;
                }
            });
        }

        function resetForm() {
            if (confirm('Apakah Anda yakin ingin mereset semua data?')) {
                document.getElementById('calculatorForm').reset();
                document.getElementById('itemsList').innerHTML = '';
                document.getElementById('resultsSection').style.display = 'none';
                itemCount = 0;
                addItem();
            }
        }

        document.getElementById('calculatorForm').addEventListener('submit', function(e) {
            e.preventDefault();
            calculateAll();
        });

        function calculateAll() {
            const hargaTotal = parseFloat(document.getElementById('hargaTotal').value) || 0;
            const hargaDiskon = parseFloat(document.getElementById('hargaDiskon').value) || 0;
            const ongkir = parseFloat(document.getElementById('ongkir').value) || 0;

            const items = [];
            const itemRows = document.querySelectorAll('#itemsList .item-row');
            
            itemRows.forEach((row, index) => {
                const name = row.querySelector('.item-name').value;
                const value = parseFloat(row.querySelector('.item-value').value) || 0;
                const qty = parseInt(row.querySelector('.item-qty').value) || 1;
                
                if (name && value > 0 && qty > 0) {
                    items.push({
                        no: index + 1,
                        name: name,
                        value: value,
                        qty: qty
                    });
                }
            });

            if (items.length === 0) {
                alert('Harap masukkan minimal 1 item!');
                return;
            }


            // Generate all calculation scenarios (8 skenario)
            const results = {
                scenario1: calculateScenario1(items), // harga belum PPN
                scenario2: calculateScenario2(items), // harga termasuk PPN
                scenario3: calculateScenario3(items, hargaDiskon), // harga belum PPN + diskon
                scenario4: calculateScenario4(items, hargaDiskon), // harga termasuk PPN + diskon
                scenario5: calculateScenario5(items, ongkir), // harga belum PPN + ongkir
                scenario6: calculateScenario6(items, ongkir), // harga termasuk PPN + ongkir
                scenario7: calculateScenario7(items, hargaDiskon, ongkir), // harga belum PPN + diskon + ongkir
                scenario8: calculateScenario8(items, hargaDiskon, ongkir) // harga termasuk PPN + diskon + ongkir
            };

            displayResults(results, hargaTotal, hargaDiskon, ongkir);
        }

        // Skenario 1: Harga Belum PPN
        function calculateScenario1(items) {
            return items.map(item => ({
                ...item,
                hargaPerItem: item.value / item.qty,
                totalHarga: item.value
            }));
        }

        // Skenario 2: Harga Termasuk PPN
        function calculateScenario2(items) {
            return items.map(item => {
                const hargaTermasukPPN = item.value * 1.11;
                const hargaPerItem = hargaTermasukPPN / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: hargaTermasukPPN
                };
            });
        }

        // Skenario 3: Harga Belum PPN dengan Diskon
        function calculateScenario3(items, hargaDiskon) {
            const totalNilaiHitung = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => {
                const proporsi = item.value / totalNilaiHitung;
                const potonganDiskon = hargaDiskon * proporsi;
                const nilaiSetelahDiskon = item.value - potonganDiskon;
                const hargaPerItem = nilaiSetelahDiskon / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: nilaiSetelahDiskon
                };
            });
        }

        // Skenario 4: Harga Termasuk PPN dengan Diskon
        function calculateScenario4(items, hargaDiskon) {
            const totalNilaiHitung = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => {
                const proporsi = item.value / totalNilaiHitung;
                const potonganDiskon = hargaDiskon * proporsi;
                const nilaiSetelahDiskon = item.value - potonganDiskon;
                const hargaTermasukPPN = nilaiSetelahDiskon * 1.11;
                const hargaPerItem = hargaTermasukPPN / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: hargaTermasukPPN
                };
            });
        }

        // Skenario 5: Harga Belum PPN termasuk Ongkir
        function calculateScenario5(items, ongkir) {
            const totalNilaiHitung = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => {
                const proporsi = item.value / totalNilaiHitung;
                const potonganOngkir = ongkir * proporsi;
                const nilaiSetelahOngkir = item.value + potonganOngkir;
                const hargaPerItem = nilaiSetelahOngkir / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: nilaiSetelahOngkir
                };
            });
        }

        // Skenario 6: Harga Termasuk PPN termasuk Ongkir
        function calculateScenario6(items, ongkir) {
            const totalNilaiHitung = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => {
                const proporsi = item.value / totalNilaiHitung;
                const potonganOngkir = ongkir * proporsi;
                const nilaiSetelahOngkir = item.value + potonganOngkir;
                const hargaTermasukPPN = nilaiSetelahOngkir * 1.11;
                const hargaPerItem = hargaTermasukPPN / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: hargaTermasukPPN
                };
            });
        }

        // Skenario 7: Harga Belum PPN dengan Diskon dan Ongkir
        function calculateScenario7(items, hargaDiskon, ongkir) {
            const totalNilaiHitung = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => {
                const proporsi = item.value / totalNilaiHitung;
                const potonganDiskon = hargaDiskon * proporsi;
                const potonganOngkir = ongkir * proporsi;
                const nilaiSetelahPotongan = item.value - potonganDiskon + potonganOngkir;
                const hargaPerItem = nilaiSetelahPotongan / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: nilaiSetelahPotongan
                };
            });
        }

        // Skenario 8: Harga Termasuk PPN dengan Diskon dan Ongkir
        function calculateScenario8(items, hargaDiskon, ongkir) {
            const totalNilaiHitung = items.reduce((sum, item) => sum + item.value, 0);
            return items.map(item => {
                const proporsi = item.value / totalNilaiHitung;
                const potonganDiskon = hargaDiskon * proporsi;
                const potonganOngkir = ongkir * proporsi;
                const nilaiSetelahPotongan = item.value - potonganDiskon + potonganOngkir;
                const hargaTermasukPPN = nilaiSetelahPotongan * 1.11;
                const hargaPerItem = hargaTermasukPPN / item.qty;
                return {
                    ...item,
                    hargaPerItem: hargaPerItem,
                    totalHarga: hargaTermasukPPN
                };
            });
        }

        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(angka);
        }

        function createTable(title, data, color) {
            const totalHarga = data.reduce((sum, item) => sum + item.totalHarga, 0);
            
            let html = `
                <div class="result-card">
                    <h3>${title}</h3>
                    <table>
                        <thead>
                            <tr>
                                <th class="number">No.</th>
                                <th>Nama Item</th>
                                <th class="number">Jumlah</th>
                                <th class="price">Harga per Item</th>
                                <th class="price">Total Harga</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            data.forEach(item => {
                html += `
                    <tr>
                        <td class="number">${item.no}</td>
                        <td>${item.name}</td>
                        <td class="number">${item.qty}</td>
                        <td class="price">${formatRupiah(item.hargaPerItem)}</td>
                        <td class="price">${formatRupiah(item.totalHarga)}</td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                    <div class="summary-box">
                        <div class="summary-item">
                            <span class="summary-label">TOTAL KESELURUHAN:</span>
                            <span class="summary-value">${formatRupiah(totalHarga)}</span>
                        </div>
                    </div>
                </div>
            `;

            return html;
        }

        function displayResults(results, hargaTotal, hargaDiskon, ongkir) {
            let html = '<h2 style="text-align: center; margin-bottom: 20px; color: #333;">📊 Hasil Perhitungan</h2>';
            
            html += `
                <div class="result-card">
                    <h3>📋 Ringkasan Input</h3>
                    <div class="summary-box">
                        <div class="summary-item">
                            <span class="summary-label">Harga Total:</span>
                            <span class="summary-value">${formatRupiah(hargaTotal)}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Harga Diskon:</span>
                            <span class="summary-value">${formatRupiah(hargaDiskon)}</span>
                        </div>
                        <div class="summary-item">
                            <span class="summary-label">Ongkos Kirim:</span>
                            <span class="summary-value">${formatRupiah(ongkir)}</span>
                        </div>
                    </div>
                </div>
            `;

            html += createTable('1️⃣ Harga Belum PPN', results.scenario1);
            html += createTable('2️⃣ Harga Termasuk PPN', results.scenario2);
            html += createTable('3️⃣ Harga Belum PPN dengan Diskon', results.scenario3);
            html += createTable('4️⃣ Harga Termasuk PPN dengan Diskon', results.scenario4);
            html += createTable('5️⃣ Harga Belum PPN termasuk Ongkir', results.scenario5);
            html += createTable('6️⃣ Harga Termasuk PPN termasuk Ongkir', results.scenario6);
            html += createTable('7️⃣ Harga Belum PPN dengan Diskon dan Ongkir', results.scenario7);
            html += createTable('8️⃣ Harga Termasuk PPN dengan Diskon dan Ongkir', results.scenario8);

            document.getElementById('resultsSection').innerHTML = html;
            document.getElementById('resultsSection').style.display = 'block';

            // Scroll to results
            document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
        }
    </script>
</body>
</html>
