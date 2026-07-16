<?php
header('Content-Type: text/css; charset=UTF-8');
?>
:root {
    --cte-navy: #1a2a40;
    --cte-navy-light: #2c3e5a;
    --cte-red: #c0392b;
    --cte-red-hover: #a93226;
    --cte-bg: #f4f7f6;
    --cte-green: #27ae60;
    --cte-blue-input: #3498db;
    --cte-header-blue: #1f4e79;
    --cte-header-light: #d6e4f0;
    --cte-payroll-green: #c6efce;
    --cte-nd-red: #ffc7ce;
}

body.cte-body { background: var(--cte-bg); font-family: 'Segoe UI', system-ui, sans-serif; }
.cte-navbar { background: var(--cte-navy) !important; }
.cte-logo {
    background: var(--cte-red);
    color: #fff;
    font-weight: 700;
    padding: 2px 10px;
    border-radius: 4px;
    text-transform: lowercase;
}

.cte-progress {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: space-between;
}
.cte-step {
    flex: 1;
    min-width: 90px;
    text-align: center;
    padding: 10px 6px;
    background: #fff;
    border: 2px solid #ddd;
    border-radius: 8px;
    text-decoration: none;
    color: #555;
    font-size: 0.75rem;
}
.cte-step .num {
    display: block;
    width: 28px;
    height: 28px;
    line-height: 28px;
    margin: 0 auto 4px;
    border-radius: 50%;
    background: #eee;
    font-weight: bold;
}
.cte-step.active { border-color: var(--cte-red); color: var(--cte-navy); }
.cte-step.active .num { background: var(--cte-red); color: #fff; }
.cte-step.done { border-color: var(--cte-green); }
.cte-step.done .num { background: var(--cte-green); color: #fff; }

.cte-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    padding: 1.5rem;
    margin-bottom: 1rem;
}
.cte-banner-info {
    background: var(--cte-header-light);
    border-left: 4px solid var(--cte-red);
    padding: 10px 16px;
    border-radius: 4px;
}
.cte-btn-primary { background: var(--cte-red); border-color: var(--cte-red); }
.cte-btn-primary:hover { background: var(--cte-red-hover); border-color: var(--cte-red-hover); }

.table-cte thead { background: var(--cte-header-blue); color: #fff; }
.table-cte tbody tr:nth-child(even) { background: var(--cte-header-light); }
.table-cte .col-nomina { background: var(--cte-payroll-green) !important; }
.table-cte .col-nd { background: var(--cte-nd-red) !important; }

.kpi-card {
    background: #fff;
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
    border-top: 4px solid var(--cte-navy);
}
.kpi-card.kpi-ok { border-top-color: var(--cte-green); }
.kpi-card.kpi-alert { border-top-color: var(--cte-red); }
.kpi-value { font-size: 1.4rem; font-weight: 700; color: var(--cte-navy); }

.semaforo-pendiente { color: #e74c3c; }
.semaforo-cumplido { color: #27ae60; }
.semaforo-tardio { color: #e67e22; }

.campo-auto { border-left: 3px solid var(--cte-green); }
.campo-manual { border-left: 3px solid var(--cte-blue-input); }

@media (max-width: 768px) {
    .cte-step .lbl { display: none; }
}
