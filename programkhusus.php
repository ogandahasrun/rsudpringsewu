<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

// Query untuk mengambil nama instansi dan logo dari database
$query_instansi = "SELECT nama_instansi, logo FROM setting LIMIT 1";
$result_instansi = mysqli_query($koneksi, $query_instansi);
$nama_instansi = "RSUD PRINGSEWU"; // default jika tidak ada di database
$logo_src = "images/logo.png"; // default jika tidak ada di database
if ($row_instansi = mysqli_fetch_assoc($result_instansi)) {
    $nama_instansi = $row_instansi['nama_instansi'];
    if (!empty($row_instansi['logo'])) {
        // Konversi BLOB ke base64
        $logo_blob = $row_instansi['logo'];
        $logo_base64 = base64_encode($logo_blob);
        $logo_src = "data:image/png;base64," . $logo_base64;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Farmasi - <?php echo htmlspecialchars($nama_instansi); ?></title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #059669;
            --primary-dark: #065f46;
            --primary-light: #ecfdf5;
            --bg-body: #f1f5f9;
            --surface: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 14px rgba(0,0,0,0.07);
            --shadow-lg: 0 12px 28px rgba(0,0,0,0.1);
            --radius-lg: 16px;
            --radius-md: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px 40px;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            background: var(--surface);
            color: var(--text-main);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .btn-nav:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }

        .btn-logout {
            color: #dc2626;
            border-color: #fecaca;
            background: #fff5f5;
        }

        .btn-logout:hover {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        /* Header Card */
        .header-card {
            background: linear-gradient(135deg, #065f46 0%, #0d9488 100%);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            color: #fff;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            position: relative;
            overflow: hidden;
        }

        .header-card::after {
            content: '';
            position: absolute;
            top: -40%;
            right: -5%;
            width: 320px;
            height: 320px;
            background: radial-gradient(circle, rgba(255,255,255,0.12), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .header-logo {
            background: #fff;
            padding: 8px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .header-logo img {
            width: 60px;
            height: auto;
            max-height: 70px;
            object-fit: contain;
            display: block;
        }

        .header-info {
            flex: 1;
            min-width: 200px;
        }

        .header-info h1 {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.25;
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }

        .header-info p {
            font-size: 0.95rem;
            color: rgba(255, 255, 255, 0.88);
            font-weight: 400;
        }

        .header-badge {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
            border: 1px solid rgba(255,255,255,0.25);
            white-space: nowrap;
        }

        /* Controls Section (Search + Category Filter) */
        .controls-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 18px 24px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 1rem;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 12px 44px 12px 46px;
            border-radius: 999px;
            border: 1.5px solid var(--border);
            font-size: 0.95rem;
            font-family: inherit;
            color: var(--text-main);
            background: #f8fafc;
            transition: all 0.2s ease;
            outline: none;
        }

        .search-input:focus {
            background: #fff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }

        .search-clear {
            position: absolute;
            right: 14px;
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.1rem;
            cursor: pointer;
            padding: 4px;
            display: none;
            transition: color 0.2s;
        }

        .search-clear:hover {
            color: var(--text-main);
        }

        .category-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .category-pill {
            background: #f1f5f9;
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .category-pill:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        .category-pill.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
        }

        /* Menu Grid (Widescreen Responsive: 6 to 8 columns on desktop) */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            gap: 16px;
        }

        .menu-item {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 20px 12px;
            text-decoration: none;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: var(--shadow-sm);
            min-height: 125px;
            justify-content: center;
        }

        .menu-item:hover {
            transform: translateY(-4px);
            border-color: #cbd5e1;
            box-shadow: var(--shadow-lg);
        }

        .menu-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
            transition: transform 0.2s ease;
        }

        .menu-item:hover .menu-icon-wrapper {
            transform: scale(1.1);
        }

        .menu-icon-wrapper i {
            font-size: 1.45rem;
        }

        .menu-title {
            font-size: 0.84rem;
            font-weight: 600;
            line-height: 1.35;
            color: #1e293b;
            word-break: break-word;
        }

        .menu-item.hidden {
            display: none !important;
        }

        /* Empty Search State */
        .empty-state {
            grid-column: 1 / -1;
            background: var(--surface);
            border: 1px dashed var(--border);
            border-radius: var(--radius-lg);
            padding: 48px 24px;
            text-align: center;
            display: none;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .empty-state h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .empty-state p {
            color: var(--text-muted);
            font-size: 0.88rem;
            margin-bottom: 16px;
        }

        .btn-reset-search {
            background: var(--primary-light);
            color: var(--primary);
            border: 1px solid #a7f3d0;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.2s;
        }

        .btn-reset-search:hover {
            background: #d1fae5;
        }

        /* Footer */
        footer {
            margin-top: 36px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.82rem;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 16px 12px 30px;
            }
            .header-card {
                flex-direction: column;
                text-align: center;
                padding: 24px 20px;
                gap: 16px;
            }
            .header-info h1 {
                font-size: 1.4rem;
            }
            .menu-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
                gap: 12px;
            }
            .category-pills {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 4px;
            }
            .category-pill {
                flex-shrink: 0;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Top Navigation -->
    <div class="top-bar">
        <a href="index.php" class="btn-nav">
            <i class="fas fa-arrow-left"></i> Kembali ke Menu Utama
        </a>
        <a href="logout.php" class="btn-nav btn-logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Header Card -->
    <div class="header-card">
        <a href="index.php" class="header-logo" title="Kembali ke Beranda">
            <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="Logo">
        </a>
        <div class="header-info">
            <h1>Menu Program Khusus</h1>
            <p><?php echo htmlspecialchars($nama_instansi); ?></p>
        </div>
    </div>

    <!-- Menu Grid -->
    <div class="menu-grid" id="menuGrid">

        <!-- 1. Hepatitis C -->
        <a href="hepatitisc.php" class="menu-item" data-category="pelayanan" data-keywords="data barang master obat medis farmasi">
            <div class="menu-icon-wrapper" style="background: rgba(79, 70, 229, 0.1);">
                <i class="fas fa-box" style="color: #4f46e5;"></i>
            </div>
            <span class="menu-title">Hepatitis C</span>
        </a>

        <!-- 2. Morfologi Darah Tepi -->
        <a href="morfologidarahtepi.php" class="menu-item" data-category="pelayanan" data-keywords="skp farmasi sasaran keselamatan pasien telaah obat">
            <div class="menu-icon-wrapper" style="background: rgba(5, 150, 105, 0.1);">
                <i class="fas fa-capsules" style="color: #059669;"></i>
            </div>
            <span class="menu-title">Morfologi Darah Tepi</span>
        </a> 

        <!-- 3. Monitor Antibiotik -->
        <a href="monitor_antibiotik.php" class="menu-item" data-category="stok" data-keywords="stok farmasi gudang sisa jumlah obat">
            <div class="menu-icon-wrapper" style="background: rgba(14, 165, 233, 0.1);">
                <i class="fas fa-warehouse" style="color: #0ea5e9;"></i>
            </div>
            <span class="menu-title">Monitor Antibiotik</span>
        </a>

        <!-- Empty State Container -->
        <div class="empty-state" id="emptyState">
            <i class="fas fa-search"></i>
            <h3>Menu tidak ditemukan</h3>
            <p>Tidak ada menu yang sesuai dengan kata kunci "<span id="emptyKeyword"></span>".</p>
            <button class="btn-reset-search" onclick="resetFilters()">Tampilkan Semua Menu</button>
        </div>

    </div>

    <!-- Footer -->
    <footer>by IT RSUD Pringsewu</footer>
</div>

<script>
(function() {
    const searchInput = document.getElementById('searchInput');
    const searchClear = document.getElementById('searchClear');
    const categoryPills = document.querySelectorAll('.category-pill');
    const menuItems = document.querySelectorAll('.menu-item');
    const emptyState = document.getElementById('emptyState');
    const emptyKeyword = document.getElementById('emptyKeyword');
    const visibleCountSpan = document.getElementById('visibleCount');

    let currentCategory = 'all';
    let currentSearch = '';

    function filterMenu() {
        let visibleCount = 0;
        const q = currentSearch.toLowerCase().trim();

        menuItems.forEach(item => {
            const title = (item.querySelector('.menu-title')?.textContent || '').toLowerCase();
            const keywords = (item.getAttribute('data-keywords') || '').toLowerCase();
            const category = item.getAttribute('data-category') || '';

            const matchCategory = (currentCategory === 'all' || category === currentCategory);
            const matchSearch = (!q || title.includes(q) || keywords.includes(q));

            if (matchCategory && matchSearch) {
                item.classList.remove('hidden');
                visibleCount++;
            } else {
                item.classList.add('hidden');
            }
        });

        visibleCountSpan.textContent = visibleCount;

        if (visibleCount === 0) {
            emptyState.style.display = 'block';
            emptyKeyword.textContent = q || currentCategory;
        } else {
            emptyState.style.display = 'none';
        }
    }

    // Search Input Event
    searchInput.addEventListener('input', function() {
        currentSearch = this.value;
        searchClear.style.display = this.value ? 'block' : 'none';
        filterMenu();
    });

    // Clear Search
    searchClear.addEventListener('click', function() {
        searchInput.value = '';
        currentSearch = '';
        this.style.display = 'none';
        searchInput.focus();
        filterMenu();
    });

    // Category Filter Pills
    categoryPills.forEach(pill => {
        pill.addEventListener('click', function() {
            categoryPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            currentCategory = this.getAttribute('data-category');
            filterMenu();
        });
    });

    // Reset Filters Function
    window.resetFilters = function() {
        searchInput.value = '';
        currentSearch = '';
        searchClear.style.display = 'none';
        currentCategory = 'all';
        categoryPills.forEach(p => {
            p.classList.toggle('active', p.getAttribute('data-category') === 'all');
        });
        filterMenu();
    };

    // Keyboard Shortcut: press '/' to focus search, 'Esc' to clear
    document.addEventListener('keydown', function(e) {
        if (e.key === '/' && document.activeElement !== searchInput) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        } else if (e.key === 'Escape' && document.activeElement === searchInput) {
            resetFilters();
            searchInput.blur();
        }
    });

})();
</script>

</body>
</html>