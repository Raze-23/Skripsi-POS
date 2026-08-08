@page {
    size: A4;
    margin: 15mm;
}

body {
    font-family: 'Georgia', serif;
    color: #1a1a1a;
    background-color: #ffffff;
    line-height: 1.6;
    margin: 0;
    padding: 0;
}

.primary-color { color: #065f46; }
.accent-color { color: #d4af37; }

/* ===== Header & Logo ===== */
.header {
    text-align: center;
    margin-bottom: 50px;
    border-bottom: 2px solid #d4af37;
    padding-bottom: 20px;
}

.logo-group {
    display: table;
    margin: 0 auto 15px;
}

.logo {
    width: 120px;
    height: auto;
    margin-bottom: 15px;
}

.header h1 {
    font-size: 28px;
    font-weight: 300;
    letter-spacing: 6px;
    margin: 0;
    text-transform: uppercase;
    color: #065f46;
}

.header p {
    font-size: 12px;
    font-style: italic;
    color: #d4af37;
    letter-spacing: 2px;
    margin-top: 5px;
    text-transform: uppercase;
}

/* ===== Daftar Produk (minimalis: nama + harga saja) ===== */
.menu-container {
    width: 100%;
    margin-top: 30px;
}

.menu-item {
    width: 100%;
    margin-bottom: 22px;
    page-break-inside: avoid;
}

.menu-main {
    width: 100%;
    display: table;
}

.menu-title {
    display: table-cell;
    text-align: left;
    font-size: 16px;
    font-weight: bold;
    color: #065f46;
    padding-right: 10px;
}

.menu-line {
    display: table-cell;
    border-bottom: 1px dotted #d4af37;
    width: auto;
    opacity: 0.5;
}

.menu-price {
    display: table-cell;
    text-align: right;
    font-size: 16px;
    font-weight: bold;
    color: #065f46;
    padding-left: 10px;
    width: 120px;
}

/* ===== Footer ===== */
.footer {
    position: fixed;
    bottom: 0;
    width: 100%;
    text-align: center;
    font-size: 10px;
    color: #999;
    border-top: 1px solid #eee;
    padding-top: 10px;
}
