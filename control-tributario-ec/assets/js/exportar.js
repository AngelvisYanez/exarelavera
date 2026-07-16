/**
 * EXA Control Tributario — Modulo Exportacion v2
 * Excel: ExcelJS con diseno profesional completo
 * PDF:   jsPDF + AutoTable
 */

/* ═══ UTILIDADES ═══ */
const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

function getContribuyente() {
    return {
        ruc:    (document.querySelector('.contribuyente-pill .ruc')   ||{textContent:''}).textContent.trim()||'N/A',
        nombre: (document.querySelector('.contribuyente-pill .nombre')||{textContent:''}).textContent.trim()||'N/A',
        anio:   (document.getElementById('select-anio')||{value:new Date().getFullYear()}).value
    };
}
function fmtFecha() {
    const d=new Date();
    return String(d.getDate()).padStart(2,'0')+'/'+String(d.getMonth()+1).padStart(2,'0')+'/'+d.getFullYear();
}
function parseVal(txt) {
    if (txt === undefined || txt === null) return '';
    let s = String(txt).trim();
    if (s === '' || s === '-') return 0;
    
    // Si contiene letras u otros caracteres no numéricos, es texto
    const clean = s.replace(/[0-9.,$%\s-]/g, '');
    if (clean !== '') {
        return s; // Conservar como texto
    }
    
    // Si es un RUC o identificación larga, devolverlo como string para evitar truncar ceros
    if (/^\d{10,13}$/.test(s)) {
        return s;
    }
    
    // Clean currency symbols, spaces, percent signs
    s = s.replace(/[$\s%]/g, '');
    
    const lastDot = s.lastIndexOf('.');
    const lastComma = s.lastIndexOf(',');
    
    if (lastDot !== -1 && lastComma !== -1) {
        if (lastDot < lastComma) {
            // Spanish/Ecuador style: 41.350,51
            s = s.replace(/\./g, '').replace(/,/g, '.');
        } else {
            // US style: 41,350.51
            s = s.replace(/,/g, '');
        }
    } else if (lastComma !== -1) {
        const afterComma = s.length - lastComma - 1;
        if (afterComma === 3) {
            s = s.replace(/,/g, '');
        } else {
            s = s.replace(/,/g, '.');
        }
    } else if (lastDot !== -1) {
        const afterDot = s.length - lastDot - 1;
        if (afterDot === 3) {
            s = s.replace(/\./g, '');
        }
    }
    
    const n = parseFloat(s);
    return isNaN(n) ? s : n;
}


/* ═══ PALETA COLORES EXCEL (ARGB) ═══ */
const XL = {
    navy:'FF0D1B2A', navy2:'FF1A3A5C', blue:'FF1D4ED8',
    green:'FF059669', orange:'FFD97706', purple:'FF7C3AED',
    red:'FFDC2626', gray:'FF6B7280', dark:'FF374151',
    white:'FFFFFFFF', light:'FFF8FAFC', alt:'FFEEF2F7',
    text:'FF0F172A', border:'FFE2E8F0', muted:'FF94A3B8'
};
const TH_COLOR_MAP = {
    'th-azul':XL.blue,'th-verde':XL.green,'th-naranja':XL.orange,
    'th-morado':XL.purple,'th-rojo':XL.red,'th-gris':XL.gray,'th-dark':XL.dark
};
function thColor(th) {
    for(const[c,v] of Object.entries(TH_COLOR_MAP)) if(th.classList.contains(c)) return v;
    return XL.navy;
}

/* ═══ HELPERS DE ESTILO ═══ */
function bAll(argb) { const b={style:'thin',color:{argb:argb||XL.border}}; return{top:b,left:b,bottom:b,right:b}; }
function applyFill(cell,argb){ cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:argb}}; }
function headerCell(cell,bg){
    applyFill(cell,bg||XL.navy);
    cell.font={bold:true,color:{argb:XL.white},size:8,name:'Calibri'};
    cell.alignment={horizontal:'center',vertical:'middle',wrapText:true};
    cell.border=bAll(XL.border);
}
function titleCell(cell,bg,sz){
    applyFill(cell,bg||XL.navy);
    cell.font={bold:true,color:{argb:XL.white},size:sz||12,name:'Calibri'};
    cell.alignment={horizontal:'left',vertical:'middle',indent:1};
}
function dataCell(cell,isAlt,leftAlign){
    applyFill(cell,isAlt?XL.alt:XL.light);
    cell.font={color:{argb:XL.text},size:8,name:'Calibri'};
    cell.alignment={horizontal:leftAlign?'left':'right',vertical:'middle'};
    cell.border=bAll(XL.border);
}
function totalCell(cell){
    applyFill(cell,XL.navy);
    cell.font={bold:true,color:{argb:XL.white},size:8,name:'Calibri'};
    cell.alignment={horizontal:'right',vertical:'middle'};
    cell.border=bAll(XL.navy2);
}
function kpiCell(cell,bg){
    applyFill(cell,bg||XL.navy2);
    cell.font={bold:true,color:{argb:XL.white},size:9,name:'Calibri'};
    cell.alignment={horizontal:'left',vertical:'middle',indent:1};
    cell.border=bAll(XL.border);
}

/* ═══ DESCARGA ═══ */
async function downloadXlsx(wb,filename){
    const buf=await wb.xlsx.writeBuffer();
    const blob=new Blob([buf],{type:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'});
    const a=document.createElement('a'); 
    a.href=URL.createObjectURL(blob); 
    a.download=filename; 
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(a.href), 500);
}

/* ═══ TABLA MAESTRA — EXCEL ═══ */
async function exportarExcelTabla(wbGlobal = null){
    const{ruc,nombre,anio}=getContribuyente();
    const tabla=document.getElementById('tabla-maestra');
    if(!tabla||!tabla.querySelector('tbody tr')){ showToast('Sin datos','Sube los PDFs primero','warning'); return; }

    const wb = wbGlobal || new ExcelJS.Workbook(); if(!wbGlobal) wb.creator='EXA Control Tributario';

    // ═══ PALETA EXACTA ════════════════════════════════════════════════════
    const C={
        title:  'FF1F4E78',   // #1F4E78 — azul marino: título, subtítulo, total
        group:  'FF2C3E50',   // #2C3E50 — azul oscuro: grupos (fila 3)
        col:    'FF34495E',   // #34495E — gris/azul: encabezados col (filas 4-5)
        bdr:    'FFBDC3C7',   // #BDC3C7 — gris claro: bordes
        white:  'FFFFFFFF',
        sub:    'FFB0C4DE',   // texto subtítulo pálido
        abbr:   'FFDDE6EF',   // texto fila 5 abreviada
        text:   'FF2C3E50',   // texto datos
        odd:    'FFFFFFFF',   // filas impares blanco
        even:   'FFF5F6FA',   // filas pares gris suave
    };
    const bd=()=>({style:'thin',color:{argb:C.bdr}});
    const bds=()=>({top:bd(),left:bd(),bottom:bd(),right:bd()});
    const fill=(cell,color)=>{ cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:color}}; };

    // ═══ LEER CABECERAS DEL DOM ═══════════════════════════════════════════
    const theadTrs=tabla.querySelectorAll('thead tr');
    const groups=[], subHdrs=[];
    const visibleCols=[0]; // Siempre incluye la columna "Mes" en el índice 0
    theadTrs[0].querySelectorAll('th').forEach(th=>{
        if(th.classList.contains('d-none')) return;
        groups.push({ text:th.textContent.trim(),
                      cs:parseInt(th.getAttribute('colspan')||'1'),
                      rs:parseInt(th.getAttribute('rowspan')||'1') });
    });
    theadTrs[1].querySelectorAll('th').forEach((th, idx) => {
        if(!th.classList.contains('d-none')) {
            subHdrs.push(th.textContent.trim());
            visibleCols.push(idx + 1); // +1 porque tds[0] es Mes
        }
    });
    const totalCols=subHdrs.length+1;
    // Abreviaciones: quitar código numérico inicial (ej "401 V. Bruto 15%" → "V. Bruto 15%")
    const subShort=subHdrs.map(h=>h.replace(/^\d+\s/,''));

    // Crear hoja con paneles congelados y gridlines
    const ws=wb.addWorksheet('Tabla Maestra',{
        views:[{state:'frozen',xSplit:1,ySplit:5,showGridLines:true}]
    });

    // ═══ PASO 1: AGREGAR TODAS LAS FILAS PRIMERO (evita bug del row counter) ═
    // Fila 1 — Título
    ws.addRow([`EXA Control Tributario -- Tabla Maestra ${anio}`]);

    // Fila 2 — Subtítulo
    ws.addRow([`RUC: ${ruc}  |  ${nombre}  |  Generado: ${fmtFecha()}`]);

    // Fila 3 — Grupos
    const grpArr=new Array(totalCols).fill('');
    let gci=2;
    const grpInfo=[];
    groups.forEach(g=>{
        if(g.rs>=2) return;   // "Mes" tiene rowspan → se trata aparte
        grpArr[gci-1]=g.text;
        grpInfo.push({col:gci,span:g.cs});
        gci+=g.cs;
    });
    ws.addRow(grpArr);                         // ← fila 3

    // Fila 4 — Encabezados completos
    ws.addRow([''].concat(subHdrs));           // ← fila 4

    // ═══ PASO 2: AHORA SÍ aplicar merges y estilos (todas las filas ya existen) ═

    // Alturas
    ws.getRow(1).height=24;
    ws.getRow(2).height=14;
    ws.getRow(3).height=18;
    ws.getRow(4).height=28;

    // ── Fila 1: Título ─────────────────────────────────────────────────────
    ws.mergeCells(1,1,1,totalCols);
    const f1=ws.getCell(1,1);
    fill(f1,C.title);
    f1.font={bold:true,color:{argb:C.white},size:16,name:'Calibri'};
    f1.alignment={horizontal:'left',vertical:'middle',indent:1};

    // ── Fila 2: Subtítulo ──────────────────────────────────────────────────
    ws.mergeCells(2,1,2,totalCols);
    const f2=ws.getCell(2,1);
    fill(f2,C.title);
    f2.font={italic:true,color:{argb:C.sub},size:10,name:'Calibri'};
    f2.alignment={horizontal:'left',vertical:'middle',indent:1};

    // ── Columna "Mes": merge filas 3-4, fondo #1F4E78 ─────────────────────
    ws.mergeCells(3,1,4,1);
    const mesC=ws.getCell(3,1);
    fill(mesC,C.title);
    mesC.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
    mesC.alignment={horizontal:'center',vertical:'middle',wrapText:true};
    mesC.border=bds();
    mesC.value='Mes';

    // ── Fila 3: Grupos (#2C3E50) con merge por span ────────────────────────
    grpInfo.forEach(m=>{
        const ec=m.col+m.span-1;
        if(m.span>1) ws.mergeCells(3,m.col,3,ec);
        for(let c=m.col;c<=ec;c++){
            const cell=ws.getCell(3,c);
            fill(cell,C.group);
            cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
            cell.alignment={horizontal:'center',vertical:'middle',wrapText:true};
            cell.border=bds();
        }
        ws.getCell(3,m.col).value=grpArr[m.col-1];
    });

    // ── Fila 4: Encabezados completos (#34495E, negrita) ───────────────────
    subHdrs.forEach((txt,i)=>{
        const cell=ws.getCell(4,i+2);
        fill(cell,C.col);
        cell.font={bold:true,color:{argb:C.white},size:9,name:'Calibri'};
        cell.alignment={horizontal:'center',vertical:'middle',wrapText:true};
        cell.border=bds();
        cell.value=txt;
    });

    // ═══ PASO 3: FILAS DE DATOS (Ene–Dic) comienzan en fila 5 ═════════════
    let dr=5;
    tabla.querySelectorAll('tbody tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length===0) return;
        const mes=tds[0].textContent.trim();
        if(!MESES.includes(mes)) return;  // filtra cualquier fila no-mes

        const even=(dr%2===0);
        const row=[]; 
        visibleCols.forEach(idx => {
            if(tds[idx]) row.push(parseVal(tds[idx].textContent));
            else row.push(0);
        });
        
        ws.addRow(row);
        ws.getRow(dr).height=15;

        // Inyectar valores y fórmulas
        for(let ci=0; ci<row.length; ci++){
            const cIdx = ci+1;
            const cell = ws.getCell(dr, cIdx);
            fill(cell, even ? C.even : C.odd);
            cell.border = bds();

            if(cIdx === 1){
                // Columna A (Mes)
                cell.font={bold:true,color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment={horizontal:'left',vertical:'middle',indent:1};
            } else {
                cell.font={color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment={horizontal:'right',vertical:'middle'};
                cell.numFmt='$#,##0.00';

                // Fórmulas Automáticas Requeridas (estas columnas están al principio y nunca se ocultan)
                if(cIdx === 7){
                    // Columna G (TOTAL V. NETAS) = B + C - D - E
                    cell.value = { formula: `B${dr}+C${dr}-D${dr}-E${dr}`, result: row[ci] || 0 };
                } else if(cIdx === 13){
                    // Columna M (TOTAL NETO COMPRAS) = H + I - J - K
                    cell.value = { formula: `H${dr}+I${dr}-J${dr}-K${dr}`, result: row[ci] || 0 };
                } else if(cIdx === 14){
                    // Columna N (RESULTADO V - C) = G - M
                    cell.value = { formula: `G${dr}-M${dr}`, result: row[ci] || 0 };
                } else {
                    // Valor normal numérico
                    cell.value = row[ci] || 0;
                }
            }
        }
        dr++;
    });

    // ═══ PASO 4: FILA TOTAL ANUAL (fila 17, fondo #1F4E78) ═══════════════
    tabla.querySelectorAll('tfoot tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length===0) return;
        if(tds[0].textContent.trim()!=='TOTAL ANUAL') return;

        const row=[]; 
        visibleCols.forEach(idx => {
            if(tds[idx]) row.push(parseVal(tds[idx].textContent));
            else row.push(0);
        });
        ws.addRow(row);
        ws.getRow(dr).height=16;

        for(let ci=0; ci<row.length; ci++){
            const cIdx = ci+1;
            const cell = ws.getCell(dr, cIdx);
            fill(cell, C.title);
            cell.font={bold:true,color:{argb:C.white},size:9,name:'Calibri'};
            cell.border = bds();

            if(cIdx === 1){
                cell.alignment={horizontal:'left',vertical:'middle',indent:1};
            } else {
                cell.alignment={horizontal:'right',vertical:'middle'};
                cell.numFmt='$#,##0.00';
                // Fórmula de suma automática para cada columna
                const colLetter = ws.getColumn(cIdx).letter;
                cell.value = { formula: `SUM(${colLetter}5:${colLetter}16)`, result: row[ci] || 0 };
            }
        }
        dr++;
    });

    // ═══ ANCHOS ═══════════════════════════════════════════════════════════
    ws.getColumn(1).width=15;
    for(let c=2;c<=totalCols;c++) ws.getColumn(c).width=10;

    if(!wbGlobal) { await downloadXlsx(wb,`EXA_Tabla_Maestra_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','Tabla Maestra exportada','success');
}



/* ═══ HELPER: sheet simple desde tabla HTML ═══ */
async function buildSimpleSheet(wb,sheetName,tableEl,bgColor){
    if(!tableEl) return;
    const ws=wb.addWorksheet(sheetName);
    // Cabeceras
    const headThs=tableEl.querySelectorAll('thead th');
    const hdrs=[]; headThs.forEach(th=>{ if(!th.classList.contains('d-none')) hdrs.push(th.textContent.trim()); });
    ws.addRow(hdrs); ws.getRow(1).height=20;
    hdrs.forEach((_,i)=>headerCell(ws.getCell(1,i+1),bgColor||XL.navy));
    // Datos
    let dr=2;
    tableEl.querySelectorAll('tbody tr').forEach((tr,idx)=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length === 0) return;
        const row=[]; tds.forEach(td=>row.push(parseVal(td.textContent)));
        ws.addRow(row); ws.getRow(dr).height=13;
        row.forEach((_,ci)=>{ const cell=ws.getCell(dr,ci+1); dataCell(cell,idx%2===1,ci===0); if(ci===0) cell.font={bold:true,size:8,color:{argb:XL.text},name:'Calibri'}; });
        dr++;
    });
    tableEl.querySelectorAll('tfoot tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length === 0) return;
        const row=[]; tds.forEach(td=>row.push(parseVal(td.textContent)));
        ws.addRow(row); ws.getRow(dr).height=15;
        row.forEach((_,ci)=>totalCell(ws.getCell(dr,ci+1))); dr++;
    });
    // Anchos
    hdrs.forEach((_,i)=>{ ws.getColumn(i+1).width= i===0?14:12; });
    return ws;
}

/* ═══ RESUMEN IR — EXCEL ═══ */
async function exportarExcelIR(wbGlobal = null){
    const {ruc, nombre, anio} = getContribuyente();
    const wb = wbGlobal || new ExcelJS.Workbook();
    if (!wbGlobal) wb.creator = 'EXA Control Tributario';
    
    // Fetch config for formulas
    let tablas_ir = null;
    let tarifa_sociedad = 0.25;
    try {
        const res = await fetch(window.BASE_URL + 'config/parametros.json');
        const params = await res.json();
        tablas_ir = params.tablas_ir;
        tarifa_sociedad = params.tarifa_sociedad || 0.25;
    } catch(e) {
        console.warn("No se pudo cargar parametros.json, se usaran valores por defecto", e);
    }
    
    let tabla = null;
    if (tablas_ir) {
        const anioInt = parseInt(anio) || new Date().getFullYear();
        tabla = tablas_ir[anioInt];
        if (!tabla) {
            const years = Object.keys(tablas_ir).map(Number).sort((a,b)=>b-a);
            tabla = tablas_ir[years[0]];
        }
    }
    
    const ws = wb.addWorksheet('Resumen IR');
    ws.getColumn(1).width = 48;
    ws.getColumn(2).width = 22;
    ws.getColumn(3).width = 22;
    ws.getColumn(4).width = 4; // Espacio
    ws.getColumn(5).width = 22;
    ws.getColumn(6).width = 20;
    ws.getColumn(7).width = 20;
    ws.getColumn(8).width = 20;
    
    const regimen = document.getElementById('ir-table-regimen') ? document.getElementById('ir-table-regimen').value : 'pn';
    const isSoc = (regimen === 'soc');
    
    // Title
    ws.mergeCells('A1:H1');
    ws.getCell('A1').value = `EXA -- Borrador de Impuesto a la Renta ${anio} (${isSoc ? 'SOCIEDAD' : 'PERSONA NATURAL'})`;
    titleCell(ws.getCell('A1'), XL.navy, 12);
    ws.getRow(1).height = 20;
    
    ws.mergeCells('A2:H2');
    ws.getCell('A2').value = `RUC: ${ruc}  |  ${nombre}  |  Generado: ${fmtFecha()}`;
    applyFill(ws.getCell('A2'), XL.navy2); 
    ws.getCell('A2').font = {color: {argb: XL.muted}, size: 9};
    ws.getRow(2).height = 14;
    
    ws.addRow([]);
    
    // Helper para leer DOM
    const getVal = (id) => {
        const el = document.getElementById(id);
        if (!el) return 0;
        return parseVal(el.value !== undefined ? el.value : el.textContent);
    };
    const getRetencionesDecl = () => {
        const el = document.getElementById('input-ret-recibidas-estimado');
        if (el && el.parentElement && el.parentElement.previousElementSibling) {
            return parseVal(el.parentElement.previousElementSibling.textContent);
        }
        return 0;
    };
    
    const rows = [];
    let rIdx = 4;
    
    // Headers
    ws.addRow(['RUBRO', 'DECLARADO', 'ESTIMADO']);
    ws.getRow(rIdx).height = 20;
    headerCell(ws.getCell(rIdx, 1), XL.dark);
    headerCell(ws.getCell(rIdx, 2), XL.gray);
    headerCell(ws.getCell(rIdx, 3), XL.blue);
    rIdx++;
    
    const C = {
        group: 'FFCFE2FF', 
        groupTxt: 'FF084298', 
        border: 'FFDEE2E6',
        text: 'FF212529',
        inputBg: 'FFEBF5FF',
        subBg: 'FFF8F9FA'
    };
    
    const addGroupRow = (title) => {
        ws.addRow([title]);
        ws.mergeCells(rIdx, 1, rIdx, 3);
        const c = ws.getCell(rIdx, 1);
        applyFill(c, XL.blue);
        c.font = {bold: true, color: {argb: XL.white}, size: 10, name: 'Calibri'};
        c.border = bAll(C.border);
        rIdx++;
    };
    
    const addDataRow = (title, valDecl, valEst, isInput = false, cellFormat = '$#,##0.00', isBold = false) => {
        ws.addRow([title, valDecl, valEst]);
        const c1 = ws.getCell(rIdx, 1);
        const c2 = ws.getCell(rIdx, 2);
        const c3 = ws.getCell(rIdx, 3);
        
        [c1, c2, c3].forEach(c => {
            c.border = bAll(C.border);
            c.alignment = {vertical: 'middle'};
        });
        
        c1.font = {bold: isBold, color: {argb: C.text}, size: 9, name: 'Calibri'};
        c2.font = {bold: isBold, color: {argb: 'FF6C757D'}, size: 9, name: 'Calibri'};
        c3.font = {bold: isBold, color: {argb: isInput ? 'FF0D6EFD' : C.text}, size: 9, name: 'Calibri'};
        
        applyFill(c2, C.subBg); 
        c1.alignment = {horizontal: 'left', vertical: 'middle', indent: isBold ? 0 : 1};
        c2.alignment = {horizontal: 'right', vertical: 'middle'};
        c3.alignment = {horizontal: 'right', vertical: 'middle'};
        
        if (isInput) {
            applyFill(c3, C.inputBg);
        } else {
            applyFill(c3, isBold ? 'FFF1F5F9' : XL.white);
        }
        
        c2.numFmt = cellFormat;
        c3.numFmt = cellFormat;
        
        const r = rIdx;
        rIdx++;
        return r;
    };
    
    addGroupRow('INGRESOS');
    const rVentas = addDataRow('VENTAS', getVal('val-ventas-decl'), getVal('input-ventas-estimado'), true);
    const rRend = addDataRow('RENDIMIENTOS FINANCIEROS', getVal('input-rendimientos-decl'), getVal('input-rendimientos'), true);
    const rSueldo = addDataRow('BASE SUELDO 107', getVal('input-sueldo-107-decl'), getVal('input-sueldo-107'), true);
    
    const rTotIng = addDataRow('TOTAL DE INGRESOS', 
        { formula: `SUM(B${rVentas}:B${rSueldo})` },
        { formula: `SUM(C${rVentas}:C${rSueldo})` },
        false, '$#,##0.00', true
    );
    
    addGroupRow('COSTOS Y GASTOS');
    const rCompras = addDataRow('COMPRAS', getVal('val-compras-decl'), getVal('input-compras-estimado'), true);
    const rSueldos = addDataRow('SUELDOS', getVal('val-sueldos-decl'), getVal('input-sueldos-estimado'), true);
    const rSS = addDataRow('SEGURIDAD SOCIAL', getVal('val-seguridad-decl'), getVal('input-seguridad-social-estimado'), true);
    const rDepr = addDataRow('DEPRECIACION Y PROVISION', getVal('input-depreciacion-decl'), getVal('input-depreciacion'), true);
    const rOtros = addDataRow('OTROS COSTOS Y GASTOS', getVal('input-otros-gastos-ad-decl'), getVal('input-otros-gastos-ad'), true);
    
    const rTotGas = addDataRow('TOTAL DE GASTOS', 
        { formula: `SUM(B${rCompras}:B${rOtros})` },
        { formula: `SUM(C${rCompras}:C${rOtros})` },
        false, '$#,##0.00', true
    );
    
    addGroupRow('CONCILIACION TRIBUTARIA');
    const rUtilAntes = addDataRow('UTILIDAD ANTES DE PARTICIPACION (Ingresos - Gastos)',
        { formula: `B${rTotIng}-B${rTotGas}` },
        { formula: `C${rTotIng}-C${rTotGas}` },
        false, '$#,##0.00', true
    );
    
    const rPart = addDataRow('(-) PARTICIPACION 15% TRABAJADORES',
        { formula: `MAX(0, B${rUtilAntes}*0.15)` },
        { formula: `MAX(0, C${rUtilAntes}*0.15)` }
    );
    ws.getCell(rPart, 2).font = {bold: true, color: {argb: XL.red}, size: 9, name: 'Calibri'};
    ws.getCell(rPart, 3).font = {bold: true, color: {argb: XL.red}, size: 9, name: 'Calibri'};
    
    const rGasND = addDataRow('(+) GASTOS NO DEDUCIBLES', getVal('input-gastos-nd-decl'), getVal('input-gastos-nd'), true);
    
    let rGasPer = null;
    if (!isSoc) {
        rGasPer = addDataRow('(-) GASTOS PERSONALES', getVal('input-gastos-personales-decl'), getVal('input-gastos-personales'), true);
    }
    
    const rPerdidas = addDataRow('(-) AMORTIZACION PERDIDAS TRIBUTARIAS (Cas. 837)', getVal('input-perdidas-decl'), getVal('input-perdidas'), true);
    
    let fBaseDecl = `MAX(0, B${rUtilAntes} - B${rPart} + B${rGasND} - B${rPerdidas}`;
    let fBaseEst = `MAX(0, C${rUtilAntes} - C${rPart} + C${rGasND} - C${rPerdidas}`;
    if (!isSoc) {
        fBaseDecl += ` - B${rGasPer}`;
        fBaseEst += ` - C${rGasPer}`;
    }
    fBaseDecl += `)`;
    fBaseEst += `)`;
    
    const rBase = addDataRow('UTILIDAD GRAVABLE (Base Imponible)', 
        { formula: fBaseDecl },
        { formula: fBaseEst },
        false, '$#,##0.00', true
    );
    
    let sriStartRow = isSoc ? 19 : 23;
    let maxRowConfig = tabla ? sriStartRow + tabla.length - 1 : sriStartRow;
    
    let fIrDecl, fIrEst;
    if (isSoc) {
        fIrDecl = `MAX(0, B${rBase} * G14)`;
        fIrEst = `MAX(0, C${rBase} * H14)`;
    } else {
        fIrDecl = `VLOOKUP(B${rBase}, E${sriStartRow}:H${maxRowConfig}, 3, TRUE) + (MAX(0, B${rBase} - VLOOKUP(B${rBase}, E${sriStartRow}:H${maxRowConfig}, 1, TRUE)) * VLOOKUP(B${rBase}, E${sriStartRow}:H${maxRowConfig}, 4, TRUE))`;
        fIrEst = `VLOOKUP(C${rBase}, E${sriStartRow}:H${maxRowConfig}, 3, TRUE) + (MAX(0, C${rBase} - VLOOKUP(C${rBase}, E${sriStartRow}:H${maxRowConfig}, 1, TRUE)) * VLOOKUP(C${rBase}, E${sriStartRow}:H${maxRowConfig}, 4, TRUE))`;
    }
    
    const rIrCausado = addDataRow('IMPUESTO A LA RENTA CAUSADO', 
        { formula: fIrDecl },
        { formula: fIrEst },
        false, '$#,##0.00', true
    );
    ws.getCell(rIrCausado, 2).font = {bold: true, color: {argb: XL.red}, size: 10, name: 'Calibri'};
    ws.getCell(rIrCausado, 3).font = {bold: true, color: {argb: XL.red}, size: 10, name: 'Calibri'};
    
    const rUtilNeta = addDataRow('UTILIDAD NETA (Utilidad Gravable - IR Causado)', 
        { formula: `B${rBase}-B${rIrCausado}` },
        { formula: `C${rBase}-C${rIrCausado}` },
        false, '$#,##0.00', true
    );
    ws.getCell(rUtilNeta, 2).font = {bold: true, color: {argb: XL.green}, size: 9, name: 'Calibri'};
    ws.getCell(rUtilNeta, 3).font = {bold: true, color: {argb: XL.green}, size: 9, name: 'Calibri'};
    
    addGroupRow('LIQUIDACION DE IMPUESTO');
    const rIrLiq = addDataRow('IMPUESTO CAUSADO', 
        { formula: `B${rIrCausado}` },
        { formula: `C${rIrCausado}` },
        false, '$#,##0.00', true
    );
    
    const rRet = addDataRow('(-) RETENCIONES RECIBIDAS', getRetencionesDecl(), getVal('input-ret-recibidas-estimado'), true);
    const rCred = addDataRow('(-) CREDITO TRIBUTARIO AÑO ANTERIOR', getVal('input-credito-anterior-decl'), getVal('input-credito-anterior'), true);
    const rAnticipo = addDataRow('(-) ANTICIPO PAGADO (AÑO ANTERIOR)', getVal('input-anticipo-decl'), getVal('input-anticipo'), true);
    
    const rSaldo = addDataRow('SALDO A PAGAR / A FAVOR', 
        { formula: `B${rIrLiq}-B${rRet}-B${rCred}-B${rAnticipo}` },
        { formula: `C${rIrLiq}-C${rRet}-C${rCred}-C${rAnticipo}` },
        false, '$#,##0.00', true
    );
    
    applyFill(ws.getCell(rSaldo, 1), 'FFE0F2FE');
    ws.getCell(rSaldo, 1).font = {bold: true, color: {argb: 'FF0369A1'}, size: 11, name: 'Calibri'};
    applyFill(ws.getCell(rSaldo, 2), 'FFE0F2FE');
    ws.getCell(rSaldo, 2).font = {bold: true, color: {argb: 'FF0369A1'}, size: 11, name: 'Calibri'};
    applyFill(ws.getCell(rSaldo, 3), 'FFE0F2FE');
    ws.getCell(rSaldo, 3).font = {bold: true, color: {argb: 'FF0369A1'}, size: 11, name: 'Calibri'};
    
    // ════ PANELES LATERALES DERECHOS ════
    const addRightPanelTitle = (title, r) => {
        ws.mergeCells(`E${r}:H${r}`);
        const c = ws.getCell(`E${r}`);
        c.value = title;
        applyFill(c, XL.blue);
        c.font = {bold: true, color: {argb: XL.white}, size: 10, name: 'Calibri'};
        c.border = bAll(C.border);
        c.alignment = {vertical: 'middle', horizontal: 'center'};
    };
    
    const addRightSubTitle = (r) => {
        ws.mergeCells(`E${r}:F${r}`);
        const c1 = ws.getCell(`E${r}`); c1.value = 'CONCEPTO';
        const c2 = ws.getCell(`G${r}`); c2.value = 'DECLARADO';
        const c3 = ws.getCell(`H${r}`); c3.value = 'ESTIMADO';
        
        [c1, c2, c3].forEach(c => {
            applyFill(c, XL.dark);
            c.font = {bold: true, color: {argb: XL.white}, size: 9, name: 'Calibri'};
            c.border = bAll(C.border);
            c.alignment = {vertical: 'middle', horizontal: 'center'};
        });
        ws.getCell(`F${r}`).border = bAll(C.border);
    };
    
    const addRightData = (label, valDecl, valEst, r, numFmt = '$#,##0.00', boldVal = true, customValColor = XL.blue) => {
        ws.mergeCells(`E${r}:F${r}`);
        const c1 = ws.getCell(`E${r}`);
        c1.value = label;
        c1.font = {bold: true, color: {argb: C.text}, size: 9, name: 'Calibri'};
        c1.border = bAll(C.border);
        applyFill(c1, C.subBg);
        c1.alignment = {vertical: 'middle', horizontal: 'left'};
        ws.getCell(`F${r}`).border = bAll(C.border);
        
        const processVal = (col, val) => {
            const c = ws.getCell(`${col}${r}`);
            if (val === null || val === undefined || val === '') { c.value = ''; } 
            else if (typeof val === 'object' || typeof val === 'number') { c.value = val; } 
            else { c.value = { formula: val }; }
            
            c.font = {bold: boldVal, color: {argb: customValColor}, size: 10, name: 'Calibri'};
            c.border = bAll(C.border);
            c.alignment = {vertical: 'middle', horizontal: 'right'};
            if(numFmt) c.numFmt = numFmt;
        };
        
        processVal('G', valDecl);
        processVal('H', valEst);
    };

    // Panel 1: RESUMEN
    addRightPanelTitle('RESUMEN', 4);
    addRightSubTitle(5);
    addRightData('Ventas / Ingresos', `B${rTotIng}`, `C${rTotIng}`, 6);
    addRightData('Base Imponible', `B${rBase}`, `C${rBase}`, 7, '$#,##0.00', true, XL.orange);
    addRightData('IR Causado', `B${rIrCausado}`, `C${rIrCausado}`, 8, '$#,##0.00', true, XL.red);
    addRightData('Nómina IESS', `B${rSueldos}`, `C${rSueldos}`, 9, '$#,##0.00', false, C.text);
    
    // Panel 2: CALCULO DEL IMPUESTO
    addRightPanelTitle('CÁLCULO DEL IMPUESTO', 11);
    addRightSubTitle(12);
    
    let sriTitleRow;
    if (isSoc) {
        addRightData('BASE IMPONIBLE', `B${rBase}`, `C${rBase}`, 13);
        addRightData('TARIFA SOCIEDAD', tarifa_sociedad, tarifa_sociedad, 14, '0%', false, C.text);
        addRightData('IMPUESTO CAUSADO', `B${rIrCausado}`, `C${rIrCausado}`, 15, '$#,##0.00', true, XL.red);
        sriTitleRow = 17;
    } else {
        addRightData('BASE IMPONIBLE', `B${rBase}`, `C${rBase}`, 13);
        addRightData('(-) FRACCIÓN BÁSICA', 
            `VLOOKUP(B${rBase}, E${sriStartRow}:H${maxRowConfig}, 1, TRUE)`, 
            `VLOOKUP(C${rBase}, E${sriStartRow}:H${maxRowConfig}, 1, TRUE)`, 14, '$#,##0.00', false, C.text);
            
        addRightData('(=) FRACCIÓN EXCEDENTE', 
            `MAX(0, G13 - G14)`, 
            `MAX(0, H13 - H14)`, 15, '$#,##0.00', false, C.text);
            
        addRightData('(x) % IMP. EXCEDENTE', 
            `VLOOKUP(B${rBase}, E${sriStartRow}:H${maxRowConfig}, 4, TRUE)`, 
            `VLOOKUP(C${rBase}, E${sriStartRow}:H${maxRowConfig}, 4, TRUE)`, 16, '0%', false, C.text);
            
        addRightData('(=) IMP. FRAC. EXCEDENTE', 
            `G15 * G16`, 
            `H15 * H16`, 17, '$#,##0.00', false, C.text);
            
        addRightData('(+) IMP. FRAC. BÁSICA', 
            `VLOOKUP(B${rBase}, E${sriStartRow}:H${maxRowConfig}, 3, TRUE)`, 
            `VLOOKUP(C${rBase}, E${sriStartRow}:H${maxRowConfig}, 3, TRUE)`, 18, '$#,##0.00', false, C.text);
            
        addRightData('(=) IMPUESTO CAUSADO', 
            `G17 + G18`, 
            `H17 + H18`, 19, '$#,##0.00', true, XL.red);
        sriTitleRow = 21;
    }
    
    // Panel 3: TABLA SRI
    addRightPanelTitle(`TABLA IMPUESTO A LA RENTA ${anio} (SRI)`, sriTitleRow);
    ['Fracción Básica', 'Exceso Hasta', 'Imp. Frac. Básica', '% Imp. Exc.'].forEach((h, i) => {
        const c = ws.getCell(sriTitleRow + 1, 5 + i);
        c.value = h;
        applyFill(c, XL.dark);
        c.font = {bold: true, color: {argb: XL.white}, size: 9, name: 'Calibri'};
        c.border = bAll(C.border);
        c.alignment = {horizontal: 'center', vertical: 'middle'};
    });
    
    let tr = sriStartRow;
    if (tabla) {
        tabla.forEach(row => {
            for(let cIdx=0; cIdx<4; cIdx++){
                const cell = ws.getCell(tr, 5+cIdx);
                cell.value = row[cIdx];
                cell.border = bAll(C.border);
                cell.alignment = {vertical: 'middle', horizontal: 'right'};
                cell.font = {size: 9, name: 'Calibri', color: {argb: C.text}};
                if (cIdx === 3) {
                    cell.numFmt = '0%';
                } else {
                    cell.numFmt = '$#,##0.00';
                }
            }
            tr++;
        });
    }
    
    if(!wbGlobal) { await downloadXlsx(wb, `EXA_Resumen_IR_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','Resumen IR exportado','success');
}

/* ═══ DETALLE IESS — EXCEL ═══ */
async function exportarExcelIESS(wbGlobal = null){
    const{ruc,nombre,anio}=getContribuyente();
    const tabla=document.getElementById('tabla-iess');
    if(!tabla||!tabla.querySelector('tbody tr') || tabla.querySelector('tbody tr td.text-muted')){ showToast('Sin datos','Sube el Excel de IESS primero','warning'); return; }

    const wb = wbGlobal || new ExcelJS.Workbook(); if(!wbGlobal) wb.creator='EXA Control Tributario';
    
    // Paleta Navy/Slate (C)
    const C={
        title:  'FF1F4E78',   // #1F4E78 — azul marino: título, subtítulo, total
        group:  'FF2C3E50',   // #2C3E50 — azul oscuro: grupos
        col:    'FF34495E',   // #34495E — gris/azul: encabezados col
        bdr:    'FFBDC3C7',   // #BDC3C7 — gris claro: bordes
        white:  'FFFFFFFF',
        sub:    'FFB0C4DE',   // texto subtítulo
        text:   'FF2C3E50',   // texto datos
        odd:    'FFFFFFFF',   // filas impares
        even:   'FFF5F6FA',   // filas pares
    };
    const bd=()=>({style:'thin',color:{argb:C.bdr}});
    const bds=()=>({top:bd(),left:bd(),bottom:bd(),right:bd()});
    const fill=(cell,color)=>{ cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:color}}; };

    // Hoja principal
    const ws=wb.addWorksheet('Detalle IESS',{
        views:[{state:'frozen',xSplit:1,ySplit:3,showGridLines:true}]
    });
    
    const hdrs=tabla.querySelectorAll('thead th');
    const totalCols = hdrs.length;

    // Fila 1 - Título
    ws.addRow([`EXA Control Tributario -- Detalle IESS / Nómina ${anio}`]);
    ws.mergeCells(1,1,1,totalCols);
    ws.getRow(1).height=24;
    const f1=ws.getCell(1,1);
    fill(f1,C.title);
    f1.font={bold:true,color:{argb:C.white},size:14,name:'Calibri'};
    f1.alignment={horizontal:'left',vertical:'middle',indent:1};

    // Fila 2 - Subtítulo
    ws.addRow([`RUC: ${ruc}  |  ${nombre}  |  Generado: ${fmtFecha()}`]);
    ws.mergeCells(2,1,2,totalCols);
    ws.getRow(2).height=14;
    const f2=ws.getCell(2,1);
    fill(f2,C.title);
    f2.font={italic:true,color:{argb:C.sub},size:8,name:'Calibri'};
    f2.alignment={horizontal:'left',vertical:'middle',indent:1};

    // Fila 3 - Cabeceras
    const hdrArr=[]; hdrs.forEach(th=>hdrArr.push(th.textContent.trim()));
    ws.addRow(hdrArr); ws.getRow(3).height=20;
    hdrArr.forEach((_,i)=>{
        const cell = ws.getCell(3,i+1);
        fill(cell,C.col);
        cell.font={bold:true,color:{argb:C.white},size:9,name:'Calibri'};
        cell.alignment={horizontal:'center',vertical:'middle',wrapText:true};
        cell.border=bds();
    });

    // Datos
    let dr=4;
    tabla.querySelectorAll('tbody tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length === 0) return;
        const row=[];
        tds.forEach((td, ci)=>{
            if(ci === 0) row.push(td.textContent.trim()); // Keep string month name
            else row.push(parseVal(td.textContent));
        });
        ws.addRow(row); ws.getRow(dr).height=15;

        const even=(dr%2===0);
        for(let ci=0; ci<row.length; ci++){
            const cIdx=ci+1;
            const cell=ws.getCell(dr,cIdx);
            fill(cell, even ? C.even : C.odd);
            cell.border=bds();
            
            if(cIdx===1){
                cell.font={bold:true,color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment={horizontal:'left',vertical:'middle',indent:1};
            } else {
                cell.font={color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment={horizontal:'right',vertical:'middle'};
                if(cIdx === 2) {
                    cell.numFmt='#,##0';
                } else {
                    cell.numFmt='$#,##0.00';
                }
            }
        }
        dr++;
    });

    // Fila de Totales
    tabla.querySelectorAll('tfoot tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length === 0) return;
        const row=[];
        tds.forEach((td, ci)=>{
            if(ci === 0) row.push('TOTAL');
            else row.push(parseVal(td.textContent));
        });
        ws.addRow(row); ws.getRow(dr).height=18;

        for(let ci=0; ci<row.length; ci++){
            const cIdx=ci+1;
            const cell=ws.getCell(dr,cIdx);
            fill(cell,C.title);
            cell.border=bds();
            
            if(cIdx===1){
                cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
                cell.alignment={horizontal:'left',vertical:'middle',indent:1};
            } else {
                cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
                cell.alignment={horizontal:'right',vertical:'middle'};
                if(cIdx === 2) {
                    cell.numFmt='#,##0';
                } else {
                    cell.numFmt='$#,##0.00';
                }
                const colLetter = ws.getColumn(cIdx).letter;
                cell.value = { formula: `SUM(${colLetter}4:${colLetter}${dr-1})`, result: row[ci] || 0 };
            }
        }
        dr++;
    });

    ws.getColumn(1).width=14;
    for(let c=2; c<=totalCols; c++) ws.getColumn(c).width=16;

    if(!wbGlobal) { await downloadXlsx(wb,`EXA_IESS_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','Detalle IESS exportado','success');
}

/* ═══ ATS — EXCEL ═══ */
async function exportarExcelATS(wbGlobal = null){
    const{ruc,nombre,anio}=getContribuyente();
    const tabla=document.getElementById('tabla-ats-resumen');
    if(!tabla||!tabla.querySelector('tbody tr') || tabla.querySelector('tbody tr td.text-muted')){ showToast('Sin datos','Sube el ATS XML primero','warning'); return; }

    const wb = wbGlobal || new ExcelJS.Workbook(); if(!wbGlobal) wb.creator='EXA Control Tributario';

    // ═══ PALETA EXACTA ════════════════════════════════════════════════════
    const C={
        title:  'FF1F4E78',   // #1F4E78 — azul marino: título, subtítulo, total
        group:  'FF2C3E50',   // #2C3E50 — azul oscuro: grupos (fila 3)
        col:    'FF34495E',   // #34495E — gris/azul: encabezados col (filas 4-5)
        bdr:    'FFBDC3C7',   // #BDC3C7 — gris claro: bordes
        white:  'FFFFFFFF',
        sub:    'FFB0C4DE',   // texto subtítulo pálido
        abbr:   'FFDDE6EF',   // texto fila 5 pálido
        text:   'FF2C3E50',   // texto datos
        odd:    'FFFFFFFF',   // filas impares blanco
        even:   'FFF5F6FA',   // filas pares gris suave
    };
    const bd=()=>({style:'thin',color:{argb:C.bdr}});
    const bds=()=>({top:bd(),left:bd(),bottom:bd(),right:bd()});
    const fill=(cell,color)=>{ cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:color}}; };

    // ═══ LEER CABECERAS DEL DOM ═══════════════════════════════════════════
    const theadTrs=tabla.querySelectorAll('thead tr');
    const groups=[], subHdrs=[];
    const visibleCols=[0]; // Siempre incluye la columna "Mes" en el índice 0
    theadTrs[0].querySelectorAll('th').forEach(th=>{
        if(th.classList.contains('d-none')) return;
        groups.push({ text:th.textContent.trim(),
                      cs:parseInt(th.getAttribute('colspan')||'1'),
                      rs:parseInt(th.getAttribute('rowspan')||'1') });
    });
    theadTrs[1].querySelectorAll('th').forEach((th, idx) => {
        if(!th.classList.contains('d-none')) {
            subHdrs.push(th.textContent.trim());
            visibleCols.push(idx + 1); // +1 porque tds[0] es Mes
        }
    });
    const totalCols=subHdrs.length+1;

    // Crear hoja con paneles congelados y gridlines
    const ws=wb.addWorksheet('Resumen ATS',{
        views:[{state:'frozen',xSplit:1,ySplit:4,showGridLines:true}]
    });

    // ═══ AGREGAR FILAS ═══
    // Fila 1 — Título
    ws.addRow([`EXA Control Tributario -- Detalle ATS ${anio}`]);

    // Fila 2 — Subtítulo
    ws.addRow([`RUC: ${ruc}  |  ${nombre}  |  Generado: ${fmtFecha()}`]);

    // Fila 3 — Grupos
    const grpArr=new Array(totalCols).fill('');
    let gci=2;
    const grpInfo=[];
    groups.forEach(g=>{
        if(g.rs>=2) return;   // "Mes" tiene rowspan
        grpArr[gci-1]=g.text;
        grpInfo.push({col:gci,span:g.cs});
        gci+=g.cs;
    });
    ws.addRow(grpArr);

    // Fila 4 — Encabezados completos
    ws.addRow([''].concat(subHdrs));

    // Alturas
    ws.getRow(1).height=24;
    ws.getRow(2).height=14;
    ws.getRow(3).height=18;
    ws.getRow(4).height=28;

    // Estilos Fila 1: Título
    ws.mergeCells(1,1,1,totalCols);
    const f1=ws.getCell(1,1);
    fill(f1,C.title);
    f1.font={bold:true,color:{argb:C.white},size:16,name:'Calibri'};
    f1.alignment={horizontal:'left',vertical:'middle',indent:1};

    // Estilos Fila 2: Subtítulo
    ws.mergeCells(2,1,2,totalCols);
    const f2=ws.getCell(2,1);
    fill(f2,C.title);
    f2.font={italic:true,color:{argb:C.sub},size:10,name:'Calibri'};
    f2.alignment={horizontal:'left',vertical:'middle',indent:1};

    // Columna "Mes" merge fila 3-4
    ws.mergeCells(3,1,4,1);
    const mesC=ws.getCell(3,1);
    fill(mesC,C.title);
    mesC.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
    mesC.alignment={horizontal:'center',vertical:'middle',wrapText:true};
    mesC.border=bds();
    mesC.value='Mes';

    // Fila 3: Grupos
    grpInfo.forEach(m=>{
        const ec=m.col+m.span-1;
        if(m.span>1) ws.mergeCells(3,m.col,3,ec);
        for(let c=m.col;c<=ec;c++){
            const cell=ws.getCell(3,c);
            fill(cell,C.group);
            cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
            cell.alignment={horizontal:'center',vertical:'middle',wrapText:true};
            cell.border=bds();
        }
        ws.getCell(3,m.col).value=grpArr[m.col-1];
    });

    // Fila 4: Encabezados completos
    subHdrs.forEach((txt,i)=>{
        const cell=ws.getCell(4,i+2);
        fill(cell,C.col);
        cell.font={bold:true,color:{argb:C.white},size:9,name:'Calibri'};
        cell.alignment={horizontal:'center',vertical:'middle',wrapText:true};
        cell.border=bds();
        cell.value=txt;
    });

    // Filas de Datos (Ene-Dic) comienzan en fila 5
    let dr=5;
    tabla.querySelectorAll('tbody tr').forEach(tr=>{
        const tds=tr.querySelectorAll('td');
        if(tds.length===0) return;
        const mes=tds[0].textContent.trim();
        if(!MESES.includes(mes)) return;

        const even=(dr%2===0);
        const row=[];
        visibleCols.forEach((idx, cSeq) => {
            if(tds[idx]) {
                if(cSeq === 0) row.push(tds[idx].textContent.trim()); // Month name as string
                else row.push(parseVal(tds[idx].textContent));
            } else {
                row.push(0);
            }
        });

        ws.addRow(row);
        ws.getRow(dr).height=15;

        for(let ci=0; ci<row.length; ci++){
            const cIdx = ci+1;
            const cell = ws.getCell(dr, cIdx);
            fill(cell, even ? C.even : C.odd);
            cell.border = bds();

            if(cIdx === 1){
                cell.font={bold:true,color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment={horizontal:'left',vertical:'middle',indent:1};
            } else {
                cell.font={color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment={horizontal:'right',vertical:'middle'};
                cell.numFmt='$#,##0.00';
            }
        }
        dr++;
    });

    // Fila de Totales en el tfoot
    const tfootTr=tabla.querySelector('tfoot tr');
    if(tfootTr){
        const tds=tfootTr.querySelectorAll('td');
        if(tds.length>0){
            const row=[];
            visibleCols.forEach((idx, cSeq) => {
                if(tds[idx]) {
                    if(cSeq === 0) row.push('TOTAL');
                    else row.push(parseVal(tds[idx].textContent));
                } else {
                    row.push('');
                }
            });

            ws.addRow(row);
            ws.getRow(dr).height=18;

            for(let ci=0; ci<row.length; ci++){
                const cIdx=ci+1;
                const cell=ws.getCell(dr,cIdx);
                fill(cell,C.title);
                cell.border=bds();

                if(cIdx === 1){
                    cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
                    cell.alignment={horizontal:'left',vertical:'middle',indent:1};
                } else {
                    cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
                    cell.alignment={horizontal:'right',vertical:'middle'};
                    if(typeof row[ci] === 'number' || (cIdx > 1 && row[ci] !== '')){
                        cell.numFmt='$#,##0.00';
                    }
                    const colLetter = ws.getColumn(cIdx).letter;
                    cell.value = { formula: `SUM(${colLetter}5:${colLetter}${dr-1})`, result: row[ci] || 0 };
                }
            }
            dr++;
        }
    }

    // Ajustar anchos de columnas
    ws.getColumn(1).width=14;
    for(let c=2; c<=totalCols; c++){
        ws.getColumn(c).width=15;
    }

    // Exportar tabla de anulados si existe y tiene datos
    const tablaAnulados = document.getElementById('tabla-ats-anulados');
    if (tablaAnulados && tablaAnulados.querySelector('tbody tr') && !tablaAnulados.querySelector('tbody tr td.text-muted')) {
        const wsAnul = wb.addWorksheet('Anulados',{
            views:[{state:'frozen',ySplit:3,showGridLines:true}]
        });

        wsAnul.addRow([`EXA Control Tributario -- Comprobantes Anulados ${anio}`]);
        wsAnul.addRow([`RUC: ${ruc}  |  ${nombre}  |  Generado: ${fmtFecha()}`]);
        
        const headersAnul = [];
        tablaAnulados.querySelectorAll('thead th').forEach(th => headersAnul.push(th.textContent.trim()));
        wsAnul.addRow(headersAnul);

        wsAnul.getRow(1).height=24;
        wsAnul.getRow(2).height=14;
        wsAnul.getRow(3).height=20;

        wsAnul.mergeCells(1,1,1,headersAnul.length);
        fill(wsAnul.getCell(1,1),C.title);
        wsAnul.getCell(1,1).font={bold:true,color:{argb:C.white},size:14,name:'Calibri'};
        wsAnul.getCell(1,1).alignment={horizontal:'left',vertical:'middle',indent:1};

        wsAnul.mergeCells(2,1,2,headersAnul.length);
        fill(wsAnul.getCell(2,1),C.title);
        wsAnul.getCell(2,1).font={italic:true,color:{argb:C.sub},size:9,name:'Calibri'};
        wsAnul.getCell(2,1).alignment={horizontal:'left',vertical:'middle',indent:1};

        headersAnul.forEach((txt,i)=>{
            const cell=wsAnul.getCell(3,i+1);
            fill(cell,C.col);
            cell.font={bold:true,color:{argb:C.white},size:10,name:'Calibri'};
            cell.alignment={horizontal:'center',vertical:'middle'};
            cell.border=bds();
        });

        let drA = 4;
        tablaAnulados.querySelectorAll('tbody tr').forEach(tr => {
            const tds = tr.querySelectorAll('td');
            if (tds.length === 0) return;
            const rowVal = [];
            tds.forEach((td, ci) => {
                if (ci === 0) rowVal.push(td.textContent.trim()); // Month name as string
                else rowVal.push(parseVal(td.textContent));
            });
            wsAnul.addRow(rowVal);
            wsAnul.getRow(drA).height=15;

            const even = (drA % 2 === 0);
            rowVal.forEach((val, ci) => {
                const cell = wsAnul.getCell(drA, ci+1);
                fill(cell, even ? C.even : C.odd);
                cell.border = bds();
                cell.font = {color:{argb:C.text},size:9,name:'Calibri'};
                cell.alignment = {horizontal: ci===0?'left':'center',vertical:'middle'};
            });
            drA++;
        });

        headersAnul.forEach((_,i)=>{ wsAnul.getColumn(i+1).width=i===6?30:16; });
    }

    if(!wbGlobal) { await downloadXlsx(wb,`EXA_ATS_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','Detalle ATS exportado','success');
}

/* ═══ DETALLE EXA ERP — EXCEL ═══ */
async function exportarExcelEXADetalle(wbGlobal = null){
    const{ruc,nombre,anio}=getContribuyente();
    const tabla=document.getElementById('tabla-exa-detalle');
    if(!tabla||!tabla.querySelector('tbody tr') || tabla.querySelector('tbody tr td.text-muted')){ showToast('Sin datos','Sincroniza o sube el Excel de EXA primero','warning'); return; }

    const wb = wbGlobal || new ExcelJS.Workbook(); if(!wbGlobal) wb.creator='EXA Control Tributario';

    // ═══ PALETA EXACTA ════════════════════════════════════════════════════
    const C={
        title:  'FF1F4E78',   // #1F4E78 — azul marino: título, subtítulo, total
        group:  'FF2C3E50',   // #2C3E50 — azul oscuro: grupos
        col:    'FF34495E',   // #34495E — gris/azul: encabezados col
        bdr:    'FFBDC3C7',   // #BDC3C7 — gris claro: bordes
        white:  'FFFFFFFF',
        sub:    'FFB0C4DE',   // texto subtítulo pálido
        text:   'FF2C3E50',   // texto datos
        odd:    'FFFFFFFF',   // filas impares blanco
        even:   'FFF5F6FA',   // filas pares gris suave
    };
    const bd=()=>({style:'thin',color:{argb:C.bdr}});
    const bds=()=>({top:bd(),left:bd(),bottom:bd(),right:bd()});
    const fill=(cell,color)=>{ cell.fill={type:'pattern',pattern:'solid',fgColor:{argb:color}}; };

    // Leer cabeceras
    const theadTrs = tabla.querySelectorAll('thead tr');
    const subThs = theadTrs[2].querySelectorAll('th');
    
    const subHdrs = [];
    // Ventas details (11 columns)
    for(let i=0; i<11; i++) {
        subHdrs.push(subThs[i] ? subThs[i].textContent.trim() : '');
    }
    // Compras details (10 columns)
    for(let i=11; i<21; i++) {
        subHdrs.push(subThs[i] ? subThs[i].textContent.trim() : '');
    }
    // V - C
    subHdrs.push('V - C');
    // Retenciones details (7 columns)
    for(let i=21; i<28; i++) {
        subHdrs.push(subThs[i] ? subThs[i].textContent.trim() : '');
    }
    // Form 103 details (remaining cells)
    for(let i=28; i<subThs.length; i++) {
        subHdrs.push(subThs[i].textContent.trim());
    }

    const totalCols = subHdrs.length + 1;

    // Crear hoja
    const ws=wb.addWorksheet('Detalle EXA ERP',{
        views:[{state:'frozen',xSplit:1,ySplit:5,showGridLines:true}]
    });

    // Fila 1 - Título
    ws.addRow([`EXA Control Tributario -- Detalle EXA ERP ${anio}`]);
    ws.mergeCells(1,1,1,totalCols);
    ws.getRow(1).height=24;
    const f1=ws.getCell(1,1);
    fill(f1,C.title);
    f1.font={bold:true,color:{argb:C.white},size:16,name:'Calibri'};
    f1.alignment={horizontal:'left',vertical:'middle',indent:1};

    // Fila 2 - Subtítulo
    ws.addRow([`RUC: ${ruc}  |  ${nombre}  |  Generado: ${fmtFecha()}`]);
    ws.mergeCells(2,1,2,totalCols);
    ws.getRow(2).height=14;
    const f2=ws.getCell(2,1);
    fill(f2,C.title);
    f2.font={italic:true,color:{argb:C.sub},size:10,name:'Calibri'};
    f2.alignment={horizontal:'left',vertical:'middle',indent:1};

    // Fila 3 — Grupos Nivel 1
    const grp1Arr = new Array(totalCols).fill('');
    grp1Arr[1] = 'FORMULARIO 104';
    grp1Arr[30] = 'FORMULARIO 103';
    ws.addRow(grp1Arr); // Fila 3
    ws.getRow(3).height=18;

    // Fila 4 — Grupos Nivel 2
    const grp2Arr = new Array(totalCols).fill('');
    grp2Arr[1] = 'VENTAS';
    grp2Arr[12] = 'COMPRAS';
    grp2Arr[22] = 'V - C';
    grp2Arr[23] = 'RETENCIONES IVA';
    grp2Arr[30] = 'FORMULARIO 103';
    ws.addRow(grp2Arr); // Fila 4
    ws.getRow(4).height=18;

    // Fila 5 — Encabezados completos
    ws.addRow([''].concat(subHdrs)); // Fila 5
    ws.getRow(5).height=28;

    // Merges
    ws.mergeCells(3, 2, 3, 30);
    ws.mergeCells(3, 31, 3, totalCols);
    ws.mergeCells(4, 2, 4, 12);
    ws.mergeCells(4, 13, 4, 22);
    ws.mergeCells(4, 24, 4, 30);
    ws.mergeCells(4, 31, 4, totalCols);
    ws.mergeCells(3, 1, 5, 1);
    ws.mergeCells(4, 23, 5, 23);

    // Estilos de Headers
    const mesC = ws.getCell(3, 1);
    fill(mesC, C.title);
    mesC.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
    mesC.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    for(let r=3; r<=5; r++) ws.getCell(r, 1).border = bds();
    mesC.value = 'MES';

    const vcC = ws.getCell(4, 23);
    fill(vcC, C.group);
    vcC.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
    vcC.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
    ws.getCell(4, 23).border = bds();
    ws.getCell(5, 23).border = bds();
    vcC.value = 'V - C';

    // Fila 3 formatting
    for(let c=2; c<=30; c++) {
        const cell = ws.getCell(3, c);
        fill(cell, C.group);
        cell.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
    }
    for(let c=31; c<=totalCols; c++) {
        const cell = ws.getCell(3, c);
        fill(cell, C.group);
        cell.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
    }

    // Fila 4 formatting
    for(let c=2; c<=12; c++) {
        const cell = ws.getCell(4, c);
        fill(cell, C.group);
        cell.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
    }
    for(let c=13; c<=22; c++) {
        const cell = ws.getCell(4, c);
        fill(cell, C.group);
        cell.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
    }
    for(let c=24; c<=30; c++) {
        const cell = ws.getCell(4, c);
        fill(cell, C.group);
        cell.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
    }
    for(let c=31; c<=totalCols; c++) {
        const cell = ws.getCell(4, c);
        fill(cell, C.group);
        cell.font = { bold: true, color: { argb: C.white }, size: 10, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
    }

    // Fila 5 formatting
    for(let c=2; c<=totalCols; c++) {
        if(c === 23) continue; // Merged
        const cell = ws.getCell(5, c);
        fill(cell, C.col);
        cell.font = { bold: true, color: { argb: C.white }, size: 9, name: 'Calibri' };
        cell.alignment = { horizontal: 'center', vertical: 'middle', wrapText: true };
        cell.border = bds();
        cell.value = subHdrs[c-2];
    }

    // Datos
    let dr = 6;
    tabla.querySelectorAll('tbody tr').forEach(tr => {
        const tds = tr.querySelectorAll('td');
        if(tds.length === 0) return;
        const mes = tds[0].textContent.trim();
        const isTotalRow = mes.toUpperCase().includes('TOTAL');

        const row = [];
        row.push(mes); // keep string month name / total name
        for(let idx=1; idx<tds.length; idx++) {
            row.push(parseVal(tds[idx].textContent));
        }

        ws.addRow(row);
        ws.getRow(dr).height = isTotalRow ? 18 : 15;

        const even = (dr % 2 === 0);
        for(let ci=0; ci<row.length; ci++) {
            const cIdx = ci+1;
            const cell = ws.getCell(dr, cIdx);
            cell.border = bds();

            if (isTotalRow) {
                fill(cell, C.title);
                cell.font = { bold: true, color: { argb: C.white }, size: 9, name: 'Calibri' };
                if (cIdx === 1) {
                    cell.alignment = { horizontal: 'left', vertical: 'middle', indent: 1 };
                } else {
                    cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    cell.numFmt = '$#,##0.00';
                    const colLetter = ws.getColumn(cIdx).letter;
                    cell.value = { formula: `SUM(${colLetter}6:${colLetter}${dr-1})`, result: row[ci] || 0 };
                }
            } else {
                fill(cell, even ? C.even : C.odd);
                if (cIdx === 1) {
                    cell.font = { bold: true, color: { argb: C.text }, size: 9, name: 'Calibri' };
                    cell.alignment = { horizontal: 'left', vertical: 'middle', indent: 1 };
                } else {
                    cell.font = { color: { argb: C.text }, size: 9, name: 'Calibri' };
                    cell.alignment = { horizontal: 'right', vertical: 'middle' };
                    cell.numFmt = '$#,##0.00';
                    cell.value = row[ci] || 0;
                }
            }
        }
        dr++;
    });

    ws.getColumn(1).width=15;
    for(let c=2; c<=totalCols; c++) ws.getColumn(c).width=12;

    if(!wbGlobal) { await downloadXlsx(wb,`EXA_Detalle_EXA_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','Detalle EXA exportado','success');
}

/* ═══ RETENCIONES — EXCEL ═══ */
async function exportarExcelRetenciones(wbGlobal = null){
    const{ruc,nombre,anio}=getContribuyente();
    const wb = wbGlobal || new ExcelJS.Workbook(); if(!wbGlobal) wb.creator='EXA Control Tributario';
    
    // Nombres descriptivos para las pestañas de acuerdo con la visual
    const tablasInfo = [
        { selector: '#tab-ret table:nth-of-type(1)', name: 'Evolución Mensual', color: XL.purple },
        { selector: '#ret-codigos-renta-tbody', parentTable: true, name: 'Códigos Renta', color: XL.blue },
        { selector: '#ret-codigos-iva-tbody', parentTable: true, name: 'Códigos IVA', color: XL.orange },
        { selector: '#ret-agentes-tbody', parentTable: true, name: 'Top 10 Agentes', color: XL.green }
    ];

    for (const info of tablasInfo) {
        let t = null;
        if (info.parentTable) {
            const tbody = document.querySelector(info.selector);
            if (tbody) t = tbody.closest('table');
        } else {
            t = document.querySelector(info.selector);
        }
        if (t && t.querySelector('tbody tr')) {
            await buildSimpleSheet(wb, info.name, t, info.color);
        }
    }

    if(!wbGlobal) { await downloadXlsx(wb,`EXA_Retenciones_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','Retenciones exportadas','success');
}

/* ═══ F101 — EXCEL ═══ */
async function exportarExcelF101(wbGlobal = null){
    const{ruc,nombre,anio}=getContribuyente();
    const wb = wbGlobal || new ExcelJS.Workbook(); if(!wbGlobal) wb.creator='EXA Control Tributario';
    const hojas=[['#f101-balance table','Balance',XL.blue],['#f101-resultados table','Resultados',XL.green],
        ['#f101-conciliacion table','Conciliacion',XL.orange],['#f101-indicadores table','Indicadores',XL.purple],
        ['#f101-alertas table','Alertas',XL.red]];
    for(const[sel,nm,bg] of hojas){ const t=document.querySelector(sel); if(t) await buildSimpleSheet(wb,nm,t,bg); }
    if(!wbGlobal) { await downloadXlsx(wb,`EXA_F101_${ruc}_${anio}.xlsx`); }
    showToast('Excel descargado','F101 exportado','success');
}

/* ═══ PDF FUNCIONES — DISEÑO LIMPIO Y ELEGANTE ═══ */
function pdfHeader(doc, titulo, subtitulo) {
    const { ruc, nombre, anio } = getContribuyente();
    const pW = doc.internal.pageSize.getWidth();
    
    // 1. Izquierda: Logo y Nombre App
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.setTextColor(13, 27, 42); 
    doc.text('EXA', 14, 12);
    
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(16);
    doc.setTextColor(148, 163, 184); 
    doc.text('|', 28, 12);
    
    doc.setFontSize(12);
    doc.setTextColor(71, 85, 105); 
    doc.text('Control Tributario', 32, 12);
    
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(13);
    doc.setTextColor(29, 78, 216); 
    doc.text(titulo, 14, 20); // Solo titulo (ya contiene el año)
    
    // 2. Derecha: Metadatos (Alineados a la derecha usando un solo String por fila para evitar solapamientos)
    doc.setFontSize(8.5);
    doc.setFont('helvetica', 'normal'); doc.setTextColor(71, 85, 105);
    doc.text('RUC: ' + ruc, pW - 14, 8, { align: 'right' });
    doc.text('Contribuyente: ' + nombre, pW - 14, 13, { align: 'right' });
    doc.text('Año Fiscal: ' + anio + '   |   Fecha de Impresión: ' + fmtFecha(), pW - 14, 18, { align: 'right' });

    // 3. Línea divisoria Navy gruesa
    doc.setLineWidth(0.8);
    doc.setDrawColor(13, 27, 42); 
    doc.line(14, 24, pW - 14, 24);
    
    return 30; 
}

function pdfFooter(doc) {
    const pW = doc.internal.pageSize.getWidth();
    const pH = doc.internal.pageSize.getHeight();
    const n = doc.internal.getNumberOfPages();
    
    for (let i = 1; i <= n; i++) {
        doc.setPage(i);
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(7.5);
        doc.setTextColor(100, 116, 139);
        doc.text(`Página ${i} de ${n}`, pW - 14, pH - 8, { align: 'right' });
    }
}

function kpiRow(doc, kpis, y) {
    const pW = doc.internal.pageSize.getWidth();
    const w = (pW - 28) / kpis.length;
    kpis.forEach(([label, val], i) => {
        const x = 14 + i * w;
        doc.setFillColor(248, 250, 252);
        doc.roundedRect(x, y, w - 3, 13, 1.5, 1.5, 'F');
        doc.setDrawColor(226, 232, 240);
        doc.setLineWidth(0.12);
        doc.roundedRect(x, y, w - 3, 13, 1.5, 1.5, 'S');
        
        doc.setFontSize(5.5);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(100, 116, 139);
        doc.text(label, x + (w - 3) / 2, y + 5, { align: 'center' });
        
        doc.setFontSize(9);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(13, 27, 42);
        doc.text(val, x + (w - 3) / 2, y + 11, { align: 'center' });
        doc.setFont('helvetica', 'normal');
    });
    return y + 17;
}

function exportarPdfTabla(){
    const{ruc,nombre,anio}=getContribuyente();
    const t=document.getElementById('tabla-maestra');
    if(!t||!t.querySelector('tbody tr')){ showToast('Sin datos','Sube los PDFs primero','warning'); return; }
    const doc=new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a3'});
    let y=pdfHeader(doc,'Tabla Maestra '+anio,'RUC: '+ruc+' | '+nombre);
    y=kpiRow(doc,[
        ['Ventas netas',(document.getElementById('kpi-ventas')||{textContent:'$0.00'}).textContent],
        ['Pagado SRI',(document.getElementById('kpi-pagado')||{textContent:'$0.00'}).textContent],
        ['IVA causado',(document.getElementById('kpi-iva-causado')||{textContent:'$0.00'}).textContent],
        ['Nomina IESS',(document.getElementById('kpi-nomina')||{textContent:'$0.00'}).textContent],
        ['IVA prox.',(document.getElementById('kpi-iva-pend')||{textContent:'$0.00'}).textContent],
    ],y);
    doc.autoTable({html:'#tabla-maestra',startY:y,
        styles:{fontSize:5.5,cellPadding:1.2,textColor:[15,23,42]},
        headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold',fontSize:6},
        footStyles:{fillColor:[26,58,92],textColor:[255,255,255],fontStyle:'bold'},
        alternateRowStyles:{fillColor:[248,250,252]},
        tableLineColor:[226,232,240],tableLineWidth:0.1,margin:{left:14,right:14}});
    pdfFooter(doc); doc.save('EXA_Tabla_Maestra_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Tabla Maestra exportada','success');
}
function exportarPdfIR(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
    let y=pdfHeader(doc,'Resumen IR '+anio,'RUC: '+ruc+' | '+nombre);
    y=kpiRow(doc,[
        ['Ventas/Ingresos',(document.getElementById('ir-kpi-ingresos')||{textContent:'$0.00'}).textContent],
        ['Base Imponible',(document.getElementById('ir-kpi-base')||{textContent:'$0.00'}).textContent],
        ['IR Estimado',(document.getElementById('ir-kpi-causado')||{textContent:'$0.00'}).textContent],
        ['Nomina IESS',(document.getElementById('ir-kpi-nomina')||{textContent:'$0.00'}).textContent],
    ],y);
    const t=document.querySelector('#ir-conciliacion-col table');
    if(t) doc.autoTable({html:t,startY:y,styles:{fontSize:8.5,cellPadding:3,textColor:[15,23,42]},headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold'},alternateRowStyles:{fillColor:[248,250,252]},columnStyles:{0:{cellWidth:120},1:{cellWidth:50,halign:'right'}},margin:{left:14,right:14}});
    pdfFooter(doc); doc.save('EXA_Resumen_IR_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Resumen IR exportado','success');
}
function exportarPdfIESS(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a4'});
    let y=pdfHeader(doc,'Detalle IESS / Nomina '+anio,'RUC: '+ruc+' | '+nombre);
    doc.autoTable({html:'#tabla-iess',startY:y,styles:{fontSize:8,cellPadding:2.5,textColor:[15,23,42]},headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold'},footStyles:{fillColor:[26,58,92],textColor:[255,255,255],fontStyle:'bold'},alternateRowStyles:{fillColor:[248,250,252]},columnStyles:{0:{fontStyle:'bold'}},margin:{left:14,right:14}});
    pdfFooter(doc); doc.save('EXA_IESS_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Detalle IESS exportado','success');
}
function exportarPdfATS(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a3'});
    let y=pdfHeader(doc,'Detalle ATS '+anio,'RUC: '+ruc+' | '+nombre);
    y=kpiRow(doc,[
        ['Base 15%',(document.getElementById('ats-v-base15')||{textContent:'$0.00'}).textContent],
        ['IVA Ventas',(document.getElementById('ats-v-iva')||{textContent:'$0.00'}).textContent],
        ['Ret. IVA',(document.getElementById('ats-v-ret-iva')||{textContent:'$0.00'}).textContent],
    ],y);
    [['#ats-ventas table','Ventas'],['#ats-compras table','Compras']].forEach(([sel,lbl])=>{
        const t=document.querySelector(sel); if(!t) return;
        doc.setFontSize(10);doc.setFont('helvetica','bold');doc.setTextColor(13,27,42);
        doc.text(lbl,14,y);y+=4;
        doc.autoTable({html:t,startY:y,styles:{fontSize:6,cellPadding:1.5,textColor:[15,23,42]},headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold'},footStyles:{fillColor:[26,58,92],textColor:[255,255,255],fontStyle:'bold'},alternateRowStyles:{fillColor:[248,250,252]},margin:{left:14,right:14}});
        y=doc.lastAutoTable.finalY+10;
    });
    pdfFooter(doc); doc.save('EXA_ATS_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Detalle ATS exportado','success');
}
function exportarPdfEXADetalle(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a3'}); // a3 por el gran numero de columnas
    let y=pdfHeader(doc,'Detalle EXA ERP '+anio,'RUC: '+ruc+' | '+nombre);
    doc.autoTable({
        html:'#tabla-exa-detalle',
        startY:y,
        styles:{fontSize:5,cellPadding:1,textColor:[15,23,42]},
        headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold'},
        footStyles:{fillColor:[26,58,92],textColor:[255,255,255],fontStyle:'bold'},
        alternateRowStyles:{fillColor:[248,250,252]},
        columnStyles:{0:{fontStyle:'bold'}},
        margin:{left:14,right:14}
    });
    pdfFooter(doc); doc.save('EXA_Detalle_EXA_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Detalle EXA exportado','success');
}
function exportarPdfRetenciones(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'landscape',unit:'mm',format:'a4'});
    let y=pdfHeader(doc,'Analisis Retenciones '+anio,'RUC: '+ruc+' | '+nombre);
    y=kpiRow(doc,[
        ['Comprobantes',(document.getElementById('ret-kpi-docs')||{textContent:'0'}).textContent],
        ['Total Retenido',(document.getElementById('ret-kpi-total')||{textContent:'$0.00'}).textContent],
        ['Agentes Unicos',(document.getElementById('ret-kpi-agentes')||{textContent:'0'}).textContent],
        ['Prom. Mensual',(document.getElementById('ret-kpi-promedio')||{textContent:'$0.00'}).textContent],
    ],y);
    document.querySelectorAll('#tab-ret table').forEach(t=>{
        doc.autoTable({html:t,startY:y,styles:{fontSize:7.5,cellPadding:2,textColor:[15,23,42]},headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold'},footStyles:{fillColor:[26,58,92],textColor:[255,255,255],fontStyle:'bold'},alternateRowStyles:{fillColor:[248,250,252]},margin:{left:14,right:14}});
        y=doc.lastAutoTable.finalY+10;
    });
    pdfFooter(doc); doc.save('EXA_Retenciones_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Retenciones exportadas','success');
}
function exportarPdfF101(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
    let y=pdfHeader(doc,'Analisis F101 '+anio,'RUC: '+ruc+' | '+nombre);
    [['#f101-balance table','Balance General'],['#f101-resultados table','Estado de Resultados'],
     ['#f101-conciliacion table','Conciliacion Tributaria'],['#f101-indicadores table','Indicadores Financieros'],
     ['#f101-alertas table','Alertas']].forEach(([sel,titulo])=>{
        const t=document.querySelector(sel); if(!t) return;
        if(y>doc.internal.pageSize.getHeight()-40){doc.addPage();y=pdfHeader(doc,'F101 (cont.)','');}
        doc.setFontSize(10);doc.setFont('helvetica','bold');doc.setTextColor(13,27,42);doc.text(titulo,14,y);y+=4;
        doc.autoTable({html:t,startY:y,styles:{fontSize:8,cellPadding:2.5,textColor:[15,23,42]},headStyles:{fillColor:[13,27,42],textColor:[255,255,255],fontStyle:'bold'},footStyles:{fillColor:[26,58,92],textColor:[255,255,255],fontStyle:'bold'},alternateRowStyles:{fillColor:[248,250,252]},margin:{left:14,right:14}});
        y=doc.lastAutoTable.finalY+12;
    });
    pdfFooter(doc); doc.save('EXA_F101_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','F101 exportado','success');
}
function exportarPdfDashboard(){
    const{ruc,nombre,anio}=getContribuyente();
    const doc=new jspdf.jsPDF({orientation:'portrait',unit:'mm',format:'a4'});
    let y=pdfHeader(doc,'Dashboard Tributario '+anio,'RUC: '+ruc+' | '+nombre);
    kpiRow(doc,[
        ['Decl. Cumplidas',(document.getElementById('dash-cumplidas')||{textContent:'0'}).textContent],
        ['PDFs Faltantes',(document.getElementById('dash-faltantes')||{textContent:'0'}).textContent],
        ['F103 Pendientes',(document.getElementById('dash-f103')||{textContent:'0'}).textContent],
        ['IESS al Dia',(document.getElementById('dash-iess')||{textContent:'0%'}).textContent],
        ['IVA prox. mes',(document.getElementById('dash-iva')||{textContent:'$0.00'}).textContent],
    ],y);
    pdfFooter(doc); doc.save('EXA_Dashboard_'+ruc+'_'+anio+'.pdf');
    showToast('PDF descargado','Dashboard exportado','success');
}

/* ═══ TOAST ═══ */
function showToast(titulo,mensaje,tipo){
    const cols={success:'#10b981',warning:'#f59e0b',error:'#f43f5e',info:'#2563eb'};
    const t=document.createElement('div');
    t.style.cssText='position:fixed;bottom:24px;right:24px;z-index:99999;background:#0d1b2a;color:#fff;border-radius:12px;padding:14px 18px;min-width:260px;box-shadow:0 12px 40px rgba(0,0,0,.4);border-left:4px solid '+(cols[tipo]||'#10b981')+';font-family:Outfit,sans-serif;animation:slideUp .25s ease;';
    t.innerHTML='<div style="font-weight:700;font-size:13px;margin-bottom:3px;">'+titulo+'</div><div style="font-size:11px;color:#94a3b8;">'+mensaje+'</div>';
    document.body.appendChild(t);
    setTimeout(()=>{t.style.transition='opacity .4s';t.style.opacity='0';},3200);
    setTimeout(()=>t.remove(),3600);
}

function getActiveTab() {
    const active = document.querySelector('#mainTab .nav-link.active');
    if (!active) return 'documentos';
    const target = active.dataset.bsTarget || '';
    return target.replace('#tab-','');
}

/* ── MODAL DE EXPORTACION TIPO MODELO 3 ── */
function mostrarModalExportacion() {
    const old = document.getElementById('modal-exportar');
    if (old) old.remove();

    const tab = getActiveTab();
    const tabs = {
        'tabla':     { label: 'Tabla Maestra',       xlFn: 'exportarExcelTabla',       pdfFn: 'exportarPdfTabla' },
        'ir':        { label: 'Resumen IR',           xlFn: 'exportarExcelIR',          pdfFn: 'exportarPdfIR' },
        'dashboard': { label: 'Dashboard',            xlFn: null,                        pdfFn: 'exportarPdfDashboard' },
        'iess':      { label: 'Detalle IESS',         xlFn: 'exportarExcelIESS',        pdfFn: 'exportarPdfIESS' },
        'xml':       { label: 'Detalle ATS',          xlFn: 'exportarExcelATS',         pdfFn: 'exportarPdfATS' },
        'ret':       { label: 'Análisis Retenciones', xlFn: 'exportarExcelRetenciones', pdfFn: 'exportarPdfRetenciones' },
        'f101':      { label: 'Análisis F101',        xlFn: 'exportarExcelF101',        pdfFn: 'exportarPdfF101' },
    };

    const info = tabs[tab] || { label: 'Reporte General', xlFn: 'exportarExcelTabla', pdfFn: 'exportarPdfTabla' };
    const { ruc, nombre, anio } = getContribuyente();

    const modal = document.createElement('div');
    modal.id = 'modal-exportar';
    modal.innerHTML = `
    <div style="position:fixed;inset:0;background:rgba(13,27,42,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(4px);">
      <div style="background:#fff;border-radius:16px;padding:0;width:420px;max-width:95vw;box-shadow:0 24px 80px rgba(13,27,42,0.25);overflow:hidden;animation:slideUp 0.25s ease;">
        <!-- Header -->
        <div style="background:linear-gradient(135deg,#0d1b2a 0%,#1a3a5c 100%);padding:20px 24px;display:flex;align-items:center;justify-content:space-between;">
          <div>
            <div style="color:#fff;font-size:16px;font-weight:700;font-family:'Outfit',sans-serif;">
              <span style="background:rgba(255,255,255,0.15);border-radius:6px;padding:2px 8px;font-size:12px;margin-right:8px;">EXA</span>
              Generar Reporte
            </div>
            <div style="color:#94a3b8;font-size:11px;margin-top:4px;">${info.label} — Año ${anio}</div>
          </div>
          <button onclick="document.getElementById('modal-exportar').remove()" 
            style="background:rgba(255,255,255,0.1);border:none;color:#fff;width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;">✕</button>
        </div>

        <!-- Contribuyente info -->
        <div style="background:#f8fafc;padding:12px 24px;border-bottom:1px solid #e2e8f0;display:flex;gap:16px;">
          <div>
            <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">RUC</div>
            <div style="font-size:13px;font-weight:700;color:#0f172a;">${ruc}</div>
          </div>
          <div>
            <div style="font-size:10px;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.05em;">Contribuyente</div>
            <div style="font-size:13px;color:#0f172a;">${nombre}</div>
          </div>
        </div>

        <!-- Botones -->
        <div style="padding:24px;">
          <p style="font-size:12px;color:#64748b;margin-bottom:16px;">Selecciona el formato de exportación para <strong>${info.label}</strong>:</p>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            ${info.xlFn ? `
            <button onclick="${info.xlFn}();document.getElementById('modal-exportar').remove();"
              style="background:#f0fdf4;border:2px solid #bbf7d0;border-radius:12px;padding:18px 12px;cursor:pointer;text-align:center;transition:all .2s;display:flex;flex-direction:column;align-items:center;gap:6px;"
              onmouseover="this.style.background='#dcfce7';this.style.borderColor='#4ade80'"
              onmouseout="this.style.background='#f0fdf4';this.style.borderColor='#bbf7d0'">
              <span style="font-size:28px;">📊</span>
              <span style="font-weight:700;color:#166534;font-size:14px;">Excel</span>
              <span style="font-size:10px;color:#4ade80;font-weight:600;background:#166534;padding:2px 8px;border-radius:99px;color:#fff;">.xlsx</span>
              <span style="font-size:10px;color:#64748b;">Datos diseñados</span>
            </button>` : `<div style="background:#f8fafc;border-radius:12px;padding:18px 12px;text-align:center;color:#cbd5e1;font-size:12px;border:2px dashed #e2e8f0;display:flex;align-items:center;justify-content:center;">Excel no disponible</div>`}

            ${info.pdfFn ? `
            <button onclick="${info.pdfFn}();document.getElementById('modal-exportar').remove();"
              style="background:#fff1f2;border:2px solid #fecdd3;border-radius:12px;padding:18px 12px;cursor:pointer;text-align:center;transition:all .2s;display:flex;flex-direction:column;align-items:center;gap:6px;"
              onmouseover="this.style.background='#ffe4e6';this.style.borderColor='#fb7185'"
              onmouseout="this.style.background='#fff1f2';this.style.borderColor='#fecdd3'">
              <span style="font-size:28px;">📄</span>
              <span style="font-weight:700;color:#9f1239;font-size:14px;">PDF</span>
              <span style="font-size:10px;color:#fb7185;font-weight:600;background:#9f1239;padding:2px 8px;border-radius:99px;color:#fff;">.pdf</span>
              <span style="font-size:10px;color:#64748b;">Documento formal</span>
            </button>` : ''}
          </div>

          <!-- Exportar todo -->
          <button onclick="exportarTodo();document.getElementById('modal-exportar').remove();"
            style="width:100%;margin-top:14px;background:linear-gradient(135deg,#0d1b2a,#1a3a5c);border:none;color:#fff;border-radius:12px;padding:13px;cursor:pointer;font-weight:700;font-size:13px;transition:all .2s;"
            onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
            <span style="font-size:16px;">🚀</span> Exportar TODO (Excel + PDF)
          </button>
        </div>
      </div>
    </div>`;

    document.body.appendChild(modal);

    const esc = (e) => { if (e.key === 'Escape') { modal.remove(); document.removeEventListener('keydown', esc); } };
    document.addEventListener('keydown', esc);
}

async function exportarTodo() {
    showToast('Generando...', 'Exportando todas las pestañas. Espere...', 'info');
    await new Promise(r => setTimeout(r, 300));
    try { await exportarExcelTabla(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { exportarPdfTabla(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { await exportarExcelIR(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { exportarPdfIR(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { await exportarExcelIESS(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { exportarPdfIESS(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { await exportarExcelATS(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { exportarPdfATS(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { await exportarExcelEXADetalle(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { exportarPdfEXADetalle(); } catch(e) { console.warn(e); }
    await new Promise(r => setTimeout(r, 200));
    try { exportarPdfDashboard(); } catch(e) { console.warn(e); }
    showToast('¡Listo!', 'Todos los reportes generados con éxito', 'success');
}

document.addEventListener('DOMContentLoaded', () => {
    const btnGenerar = document.getElementById('btn-generar');
    if (btnGenerar) {
        btnGenerar.removeAttribute('onclick');
        btnGenerar.addEventListener('click', mostrarModalExportacion);
    }
});




async function exportarExcelAuditoria(wbGlobal = null) {
    const { ruc, anio } = getContribuyente();
    const wb = wbGlobal || new ExcelJS.Workbook();
    if (!wbGlobal) wb.creator = 'EXA Control Tributario';

    const ws = wb.addWorksheet('Auditoría');
    let dr = 1;

    const exportTable = (tableEl, title) => {
        if (!tableEl || !tableEl.querySelector('tbody tr')) return;
        
        ws.getCell(dr, 1).value = title;
        ws.getCell(dr, 1).font = { bold: true, size: 12, color: { argb: XL.navy } };
        dr += 2;

        const headThs = tableEl.querySelectorAll('thead th');
        const hdrs = []; 
        headThs.forEach(th => { if (!th.classList.contains('d-none')) hdrs.push(th.textContent.trim()); });
        ws.getRow(dr).values = hdrs;
        ws.getRow(dr).height = 20;
        hdrs.forEach((_, i) => headerCell(ws.getCell(dr, i + 1), XL.navy));
        dr++;

        tableEl.querySelectorAll('tbody tr').forEach((tr, idx) => {
            const tds = tr.querySelectorAll('td');
            if (tds.length === 0) return;
            const row = []; 
            tds.forEach(td => row.push(parseVal(td.textContent)));
            ws.getRow(dr).values = row;
            ws.getRow(dr).height = 13;
            row.forEach((_, ci) => { 
                const cell = ws.getCell(dr, ci + 1); 
                dataCell(cell, idx % 2 === 1, ci === 0); 
                if (ci === 0) cell.font = { bold: true, size: 8, color: { argb: XL.text }, name: 'Calibri' }; 
            });
            dr++;
        });

        tableEl.querySelectorAll('tfoot tr').forEach(tr => {
            const tds = tr.querySelectorAll('td');
            if (tds.length === 0) return;
            const row = []; 
            tds.forEach(td => row.push(parseVal(td.textContent)));
            ws.getRow(dr).values = row;
            ws.getRow(dr).height = 15;
            row.forEach((_, ci) => totalCell(ws.getCell(dr, ci + 1))); 
            dr++;
        });
        dr += 2; // spacing
        
        hdrs.forEach((_, i) => { ws.getColumn(i + 1).width = i === 0 ? 18 : 12; });
    };

    exportTable(document.getElementById('tabla-auditoria-ventas'), 'Auditoría Ventas');
    exportTable(document.getElementById('tabla-auditoria-compras'), 'Auditoría Compras');

    if (!wbGlobal) {
        await downloadXlsx(wb, `EXA_Auditoria_${ruc}_${anio}.xlsx`);
        showToast('Excel descargado', 'Auditoría exportada', 'success');
    }
}

async function exportarTodoExcel() {
    showToast('Generando...', 'Exportando todas las pestañas a un solo Excel. Espere...', 'info');
    const { ruc, anio } = getContribuyente();
    const wb = new ExcelJS.Workbook(); 
    wb.creator = 'EXA Control Tributario';
    
    try { await exportarExcelTabla(wb); } catch(e) { console.warn(e); }
    try { await exportarExcelIR(wb); } catch(e) { console.warn(e); }
    try { await exportarExcelIESS(wb); } catch(e) { console.warn(e); }
    try { await exportarExcelATS(wb); } catch(e) { console.warn(e); }
    try { await exportarExcelEXADetalle(wb); } catch(e) { console.warn(e); }
    try { await exportarExcelRetenciones(wb); } catch(e) { console.warn(e); }
    try { await exportarExcelAuditoria(wb); } catch(e) { console.warn(e); }
    
    
    // Rename and filter sheets
    const keepSheets = {
        'Tabla Maestra': 'Tabla Maestra',
        'Resumen IR': 'Resumen IR',
        'Detalle IESS': 'Detalle IESS',
        'Resumen ATS': 'Detalle ATS',
        'Detalle EXA ERP': 'Detalle EXA',
        'Evolución Mensual': 'Análisis de Retenciones',
        'Auditoría': 'Auditoría',
        
    };
    
    const sheetsToRemove = [];
    wb.eachSheet((ws, sheetId) => {
        if (keepSheets[ws.name]) {
            ws.name = keepSheets[ws.name];
        } else {
            sheetsToRemove.push(sheetId);
        }
    });
    
    sheetsToRemove.forEach(id => wb.removeWorksheet(id));

    await downloadXlsx(wb, `EXA_Reporte_Completo_${ruc}_${anio}.xlsx`);
    showToast('¡Listo!', 'Reporte consolidado generado con éxito', 'success');
}
