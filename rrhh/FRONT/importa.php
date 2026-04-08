<?php
/**
 * Fecha de creacion 2025-08-28
 */
require_once('../../administrador/LOGICA/seguridad.php');
require_once('../LOGICA/rhu_log_contrato.php');
require_once('../../Librerias/procedimientos/almacenados_standar.php');
require_once('../../Librerias/postclass.php');

/* Creacion del Objeto de conexion */
$obBD_conexion = new Class_Log_Conexion_rrhh($Ses_Dat_Dis);

/* Creacion del objeto mysql para las consultas  */
$obBD_con1 = new Class_Log_Datos_rrhh;

$info = $obBD_con1->getRowConsultaSql("SELECT * FROM empresas WHERE Emp_Cod=".$Ses_Emp_Cod, $obBD_conexion);
$sucu = $obBD_con1->getRowConsultaSql("SELECT * FROM sucursal INNER JOIN ciudad ON (sucursal.Ciu_Cod=ciudad.Ciu_Cod) WHERE Emp_Cod=".$Ses_Emp_Cod, $obBD_conexion);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8" />
<!--TITLE><?Php echo $Ses_Sys_Nom; ?></TITLE-->
<TITLE><?Php echo "Excel/Roles [EXA]"; ?></TITLE>
<meta charset="UTF-8">
<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/chosen/chosen-1.4.2/chosen.min.css" />
<link rel="stylesheet" href="../../framework/jquery/bootstrap/popover/jquery.flyout.css">
<link rel="stylesheet" href="../../framework/jquery/summernote/summernote.css">
 <?Php require_once("../../mascaras/model1/estilos/jqgrid5.php") ?>
 <script src="../../framework/jquery/bootstrap/popover/jquery.flyout.js"></script>

<!-- SheetJS (XLS/XLSX parser) -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<style>
  body{font-family: Arial, Helvetica, sans-serif; margin:5px; color:#111}
  h1{margin: 0 0 12px 0; font-size:20px}
  .controls{display:flex; gap:12px; align-items:center; margin-bottom:14px; flex-wrap:wrap}
  input[type="file"]{padding:6px}
  .note{font-size:12px; color:#555}
  table{border-collapse: collapse; width:100%; margin-top:10px}  
  th{background:#5B6F87; text-align:left; color: #EDEDED; border:1px solid #b1afafff; padding:6px 8px; font-size:12px}
  td{background:#D5D8DB; color: #2B2B2E;  border:1px solid #c3c0c0; padding:1px 8px; font-size:12px}
  
  button{cursor:pointer; padding:1px 6px; border-radius:3px; border:1px solid #ccc; background:#fff}
  button:hover{background:#f7f7f7}
  .hidden{display:none}

  /* Estilos del rol (A4) */
  #printArea{width:240mm; min-height:350mm; padding:14mm; background:#fff; color:#000}
  .role{page-break-after:always}
  .role:last-child{page-break-after:auto}
  .role-header{text-align:center;margin-bottom:6px}
  .role-header h2{font-size:16px;margin:0}
  .role-sub{font-size:12px;margin-top:2px}
  .line{border-top:1px solid #000;margin:6px 0}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
  .grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
  .box{border:1px solid #000;padding:6px}
  .box h3{font-size:12px;margin:0 0 4px 0}
  .kv{display:flex;justify-content:space-between;font-size:16px;margin:2px 0}
  .kv i{opacity:.5}
  .totals{display:flex;justify-content:space-between;margin-top:6px;font-weight:bold}
  .firmas{margin-top:16px}
  .firmas .grid-3 div{font-size:11px;text-align:center}
  .firmas .linea{border-top:1px solid #000;margin:18px 0 4px 0}
  @media print{
    body *{visibility:hidden !important}
    #printArea,#printArea *{visibility:visible !important}
    #printArea{position:absolute;left:0;top:0}
  }
</style>
</head>
<body>
	<div class="panel panel-main">
    	<div class="panel-heading exa-header"><h3 class="panel-title">&raquo;  Gerenerar Roles desde Excel</h3></div>
		<div class="panel-body ui-widget-content ui-corner-bottom exa-body">
			<div class="row">
				
					<div class="col-md-6 form-horizontal normal">
						<fieldset class="exa-fieldset">                           
						<legend class="Titulos2">Datos del Contrato</legend>  
							<div class="form-group">                     
								<label class="col-xs-2 control-label label-sm required" for="Prs_Ced">C&eacute;dula/R.U.C.:</label>  
								<div class="col-sm-4">
									<input id="ruc" class="form-control input-xs" value="<?Php echo $info['Emp_Ruc']?>"/> 
								</div>                                                
							</div>
							<div class="form-group">                 
								<label class="col-xs-2 control-label label-sm required" for="Prs_Ced">Empresa:</label>  
								<div class="col-xs-10">
									<input id="empresa" class="form-control input-xs" value="<?Php echo $info['Emp_Nom']?>" />
								</div>                                                
							</div> 
							<div class="form-group">
								<label class="col-xs-2 control-label label-xs required" for="Prs_Ced">Teléfono:</label>  
								<div class="col-xs-3">
									<input id="tel" class="form-control input-xs" value="<?Php echo $sucu['Suc_Tel']?>" />
								</div>
								<label class="col-xs-2 control-label label-xs required" for="Prs_Ced">Ciudad:</label>  
								<div class="col-xs-5">
									<input id="ciudad" class="form-control input-xs" value="<?Php echo $sucu['Ciu_Des']?> - EL ORO - ECUADOR" />
								</div>                                                            
							</div>   
							<div class="form-group">
								<label class="col-xs-2 control-label label-xs required" for="Prs_Ced">Cargar EXCEL:</label>  
								<div class="col-xs-5">
									<input type="file" id="file" accept=".xls,.xlsx" />    
								</div>
							</div>
							<div class="form-group">									
								<div class="col-xs-12 text-right">
									<!--<button class="btn btn-primary btn-xs" id="btnExport"><i class="glyphicon glyphicon-print"></i> Exportar Excel (normalizado)</button>-->
									<button class="btn btn-info btn-xs" id="btnPrintGroup"><i class="glyphicon glyphicon-print"></i> Rol Grupal</button>
								</div>
							</div>	
						</fieldset> 								
					</div>
					
				<div class="col-md-12">
					<div class="note">
						Recomendado: que tu Excel tenga columnas como <em>Nombre, CI, Cargo, Ingreso, PeriodoDesde, PeriodoHasta,</em> y valores numéricos
						(Sueldo, Décimo Tercero, Décimo Cuarto, Horas Extra, IESS, Anticipos, Préstamos, etc.).<br/>
						El sistema clasifica automáticamente como <b>Descuentos</b> las columnas con palabras: <code>IESS, DESCUENTO, ANTICIPO, PRESTAM, MULTA, RETENCION</code>.
						El resto de columnas numéricas se incluyen como <b>Ingresos</b>.
					</div>	
				</div>								
			
			</div> 
			<table id="tabla">
				 <thead>
					<tr>
						<th width="3%">#</th>
						<th width="7%">Cédula</th>
						<th width="15%">Apellidos</th>
						<th width="15%">Nombres</th>
						<!--<th width="8%">Cargo</th>-->
						<th width="5%" style="text-align: center">Días</th>
						<th width="8%" style="text-align: center">Periodo</th>
						<th width="8%" class="num"  style="text-align: right">Total Ingresos</th>
						<th width="8%" class="num"  style="text-align: right">Total Egresos</th>
						<th width="8%" class="num"  style="text-align: right">Neto</th>
						<th width="10%" style="text-align: center">Acciones</th>
					</tr>
					</thead>
				<tbody>						
				</tbody>
			</table>
			<!-- Área de impresión -->
			<div id="printArea" class="hidden"></div>
		</div>  		
	</div>
</body>

<script>
/* ==================== CONFIG ==================== */
const FALLBACK_SI_NO_HAY_TOTALES = false;
const FALLBACK_NETO_DESDE_TOTALES = true;

/* ==================== Helpers ==================== */
const norm = s => String(s||"").toLowerCase()
  .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
  .replace(/[\s\.\-_/]/g,'');

function money(n){ if(n===null||n===undefined||n==="") return ""; return Number(n||0).toLocaleString('es-EC',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function sum(a){ return a.reduce((x,y)=>x+y,0); }
function toNumber(v){
  if (v == null || v === "") return 0;
  if (typeof v === "number") return v;
  let s = String(v).trim();
  if (!s) return 0;
  s = s.replace(/[^0-9,.\-]/g, "");
  const lastComma = s.lastIndexOf(","), lastDot = s.lastIndexOf(".");
  let dec = (lastComma>-1 && lastDot>-1) ? (lastComma>lastDot ? "," : ".") : (lastComma>-1 ? "," : ".");
  if (dec === ","){ s = s.replace(/\./g,""); s = s.replace(/,/g,"."); } else { s = s.replace(/,/g,""); }
  const n = parseFloat(s);
  return Number.isFinite(n) ? n : 0;
}
function esc(s){ return String(s??"").replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function formatDate(v){
  if(v==null||v==="") return "";
  if(typeof v==="number"){ const base=new Date(Date.UTC(1899,11,30)); const d=new Date(base.getTime()+v*86400000); return d.toISOString().slice(0,10); }
  return String(v);
}

/* ==================== Patrones ==================== */
const GROUP_RX  = /(ingres|ingreso|egreso|egresos|informaci|totales|firma)/i;
const INCOME_MATCH  = /(ingres|ingreso)/i;
const EXPENSE_MATCH = /(egres|egreso)/i;
const TOTAL_ING_RES = /(tot(\.|al)?\s*ing(res(os)?)?|total\s*ing(res(os)?))/i;
const TOTAL_EGR_RES = /(tot(\.|al)?\s*egr(es(os)?)?|total\s*egr(es(os)?))/i;
const TOTAL_NET_RES = /(neto(\s*total)?|l[ií]quido(\s*a)?\s*(recibir|pagar|cobrar)|total\s*neto|neto\s*a\s*(recibir|pagar|cobrar))/i;

/* ==================== Identificación ==================== */
const ID_KEYS = {
  cedula: /^\s*(c[eé]dula|cedula|c\.?\s*i\.?|^ci$|^c\.?i\.?$|dni|identidad)\s*$/i,
  apellidos: /^\s*apellidos?\s*$/i,
  nombres: /^\s*nombres?\s*$/i,
  cargo: /^\s*(cargo|puesto)\s*$/i,
  dias: /^\s*d[ií]as?\s*$/i,
  ingreso: /(ingreso|fecha[_\s-]?ingreso)/i,
  periodo_desde: /(desde|periodo[_\s-]?desde|fecha[_\s-]?desde)/i,
  periodo_hasta: /(hasta|periodo[_\s-]?hasta|fecha[_\s-]?hasta)/i,
  periodo: /(periodo|mes|periodo\s*mes)/i   // ✅ acepta PERIODO, MES, PERIODO MES
};

let rows = [];

/* ==================== Eventos ==================== */
document.getElementById('file').addEventListener('change', handleFile);
//document.getElementById('btnExport').addEventListener('click', exportExcel);
document.getElementById('btnPrintGroup').addEventListener('click', printGroup);

/* ==================== Procesar Excel ==================== */
function handleFile(ev){
  const f = ev.target.files[0]; if(!f) return;
  const rd = new FileReader();
  rd.onload = e => {
    const wb = XLSX.read(new Uint8Array(e.target.result), {type:'array'});
    const ws = wb.Sheets[wb.SheetNames[0]];
    const mat = XLSX.utils.sheet_to_json(ws, {header:1, defval:"", raw:false});
    const map = findHeaderStructure(mat);

    if (map) {
      const data = mat.slice(map.dataStart).filter(row => row.some(c => String(c).trim() !== ""));
      rows = data.map(arr => normalizeFromArray(arr, map.cols));
    } else {
      const json = XLSX.utils.sheet_to_json(ws, {defval:"", raw:false});
      rows = json.map(normalizeFallback);
    }
    renderTable(rows);
    alert("Cargados " + rows.length + " registros.");
  };
  rd.readAsArrayBuffer(f);
}

function findHeaderStructure(mat){
  let groupRow = -1;
  for (let i=0; i<3 && i<mat.length; i++){
    const row = mat[i] || [];
    const hits = row.filter(c => GROUP_RX.test(String(c))).length;
    if (hits >= 1){ groupRow = i; break; }
  }
  if (groupRow === -1) return null;
  const fieldRow = groupRow + 1;
  if (!mat[fieldRow]) return null;

  const maxCols = Math.max(...mat.slice(groupRow, groupRow+2).map(r => r.length));
  const cols = [];
  let lastGroup = "";
  for (let j=0; j<maxCols; j++){
    const g = (mat[groupRow][j] ?? "");
    const f = (mat[fieldRow][j] ?? "");
    const group = g || lastGroup;
    if (group) lastGroup = group;
    cols.push({ idx:j, group, groupNorm:norm(group), field:f, fieldNorm:norm(f) });
  }
  return { cols, dataStart: fieldRow + 1 };
}

function normalizeFromArray(arr, cols){
  const ingresos = [];
  const egresos  = [];
  const otros    = [];
  let cedula="", apellidos="", nombres="", cargo="", dias="", ingreso="", pdesde="", phasta="", periodo="";

  cols.forEach(c=>{
    const raw = arr[c.idx] ?? "";
    const val = toNumber(raw);
    const label = c.field || `Col ${c.idx+1}`;

    if (ID_KEYS.cedula.test(c.field)        && !cedula)   cedula = String(raw);
    if (ID_KEYS.apellidos.test(c.field)     && !apellidos) apellidos = String(raw);
    if (ID_KEYS.nombres.test(c.field)       && !nombres) nombres = String(raw);
    if (ID_KEYS.cargo.test(c.field)         && !cargo)    cargo = String(raw);
    if (ID_KEYS.dias.test(c.field)          && !dias)     dias = String(raw);
    if (ID_KEYS.ingreso.test(c.field)       && !ingreso)  ingreso = formatDate(raw);
    if (ID_KEYS.periodo_desde.test(c.field) && !pdesde)   pdesde  = formatDate(raw);
    if (ID_KEYS.periodo_hasta.test(c.field) && !phasta)   phasta  = formatDate(raw);
    if (ID_KEYS.periodo.test(c.field)       && !periodo)  periodo = String(raw);

    const isIncome  = INCOME_MATCH.test(c.group || "");
    const isExpense = EXPENSE_MATCH.test(c.group || "");
    const isTotIngLabel = TOTAL_ING_RES.test(label);
    const isTotEgrLabel = TOTAL_EGR_RES.test(label);
    const isNetoLabel   = TOTAL_NET_RES.test(label);

    if (isIncome || isTotIngLabel)  ingresos.push({concepto: label, valor: val});
    else if (isExpense || isTotEgrLabel) egresos.push({concepto: label, valor: val});
    else if (isNetoLabel) otros.push({concepto: label, valor: val});
  });

  const rubroTotIng = ingresos.find(x => TOTAL_ING_RES.test(x.concepto));
  const rubroTotEgr = egresos .find(x => TOTAL_EGR_RES.test(x.concepto));
  const rubroNeto   = otros.find(x=>TOTAL_NET_RES.test(x.concepto))
                    || ingresos.find(x=>TOTAL_NET_RES.test(x.concepto))
                    || egresos .find(x=>TOTAL_NET_RES.test(x.concepto));

  let totalIngresosMostrar = rubroTotIng ? rubroTotIng.valor : null;
  let totalEgresosMostrar  = rubroTotEgr ? rubroTotEgr.valor : null;
  let netoMostrar          = rubroNeto   ? rubroNeto.valor
                           : (FALLBACK_NETO_DESDE_TOTALES && totalIngresosMostrar!=null && totalEgresosMostrar!=null
                              ? (totalIngresosMostrar - totalEgresosMostrar) : null);

  return { cedula, apellidos, nombres, cargo, dias, ingreso, periodo_desde:pdesde, periodo_hasta:phasta, periodo,
    ingresos, egresos, totalIngresosMostrar, totalEgresosMostrar, netoMostrar };
}

/* ==================== Tabla ==================== */
function renderTable(data){
  const tb = document.querySelector("#tabla tbody");
  tb.innerHTML = "";
  data.forEach((r,i)=>{
    const periodo = r.periodo || ((r.periodo_desde||r.periodo_hasta) ? `${r.periodo_desde||"?"} al ${r.periodo_hasta||"?"}` : "");
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${i+1}</td>
      <td>${esc(r.cedula)}</td>
      <td>${esc(r.apellidos)}</td>
      <td>${esc(r.nombres)}</td>
      <td class="num" style="text-align: center;">${esc(r.dias)}</td>
      <td style="text-align: center;">${esc(periodo)}</td>      
      <td class="num" style="text-align: right;">${r.totalIngresosMostrar!=null ? ('$ ' + money(r.totalIngresosMostrar)) : ''}</td>
      <td class="num" style="text-align: right;">${r.totalEgresosMostrar!=null ? ('$ ' + money(r.totalEgresosMostrar)) : ''}</td>
      <td class="num" style="text-align: right;" ><b>${r.netoMostrar!=null ? ('$ ' + money(r.netoMostrar)) : ''}</b></td>
      <td style="text-align: center;">
        <button onclick="printOne(${i})" class="btn btn-info btn-xs"><span class="glyphicon glyphicon-print"></span> Imprimir rol</button>
        <button onclick="downloadOneExcel(${i})" class="btn btn-success btn-xs"><i class="fa fa-download"></i> Excel</button>
      </td>`;
    tb.appendChild(tr);
  });
}

/* ==================== Impresión ==================== */
function printOne(i){
  const area = document.getElementById("printArea");
  area.classList.remove("hidden");
  area.innerHTML = buildRoleHTML(rows[i]);
  window.print();
  setTimeout(()=>area.classList.add("hidden"), 300);
}
function printGroup(){
  if(!rows.length){ alert("Primero importa un Excel."); return; }
  const area = document.getElementById("printArea");
  area.classList.remove("hidden");
  area.innerHTML = rows.map(r => buildRoleHTML(r,true)).join("");
  window.print();
  setTimeout(()=>area.classList.add("hidden"), 300);
}

/* ==================== Exportar ==================== */
function exportExcel(){
  if(!rows.length){ alert("Primero importa un Excel."); return; }
  const flat = rows.map(r=>({
    Cedula: r.cedula, Apellidos: r.apellidos, Nombres: r.nombres, Periodo: r.periodo, Dias: r.dias,
    Total_Ingresos: r.totalIngresosMostrar, Total_Egresos: r.totalEgresosMostrar, Neto: r.netoMostrar
  }));
  const ws = XLSX.utils.json_to_sheet(flat);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Roles");
  XLSX.writeFile(wb, "roles_usando_totales_excel.xlsx");
}

/* ==================== Plantilla de impresión ==================== */
function buildRoleHTML(r, wrap=false){
  const empresa = document.getElementById("empresa").value || "";
  const ruc = document.getElementById("ruc").value || "";
  const tel = document.getElementById("tel").value || "";
  const ciudad = document.getElementById("ciudad").value || "";

  const ingresosHtml = r.ingresos.map(x => {
    // Identificar si el concepto es horas suplementarias o extraordinarias
    const conceptoNorm = norm(x.concepto);
    const esHoras = conceptoNorm === norm("Can.Hrs. Suplem.") || conceptoNorm === norm("Can.Hrs. Extrao.");
    return `<div class="kv"><span>${esc(x.concepto)}</span><span>${esHoras ? money(x.valor) : ('$ ' + money(x.valor))}</span></div>`;
  }).join("");

  /*const ingresosHtml = r.ingresos.map(x=>
    `<div class="kv"><span>${esc(x.concepto)}</span><span>$ ${money(x.valor)}</span></div>`).join("");*/
  const egresosHtml  = r.egresos.map(x=>
    `<div class="kv"><span>${esc(x.concepto)}</span><span>$ ${money(x.valor)}</span></div>`).join("");

  const pieIng = r.ingresos.some(x => TOTAL_ING_RES.test(x.concepto)) ? "" :
    `<div class="totals"><span>TOTAL INGRESOS</span><span>${r.totalIngresosMostrar!=null ? ('$ '+money(r.totalIngresosMostrar)) : ''}</span></div>`;
  const pieEgr = r.egresos.some(x => TOTAL_EGR_RES.test(x.concepto)) ? "" :
    `<div class="totals"><span>TOTAL EGRESOS</span><span>${r.totalEgresosMostrar!=null ? ('$ '+money(r.totalEgresosMostrar)) : ''}</span></div>`;
  const pieNeto = (r.ingresos.concat(r.egresos)).some(x => TOTAL_NET_RES.test(x.concepto)) ? "" :
    `<div class="totals" style="font-size:14px">
       <span>NETO A RECIBIR</span><span>${r.netoMostrar!=null ? ('$ '+money(r.netoMostrar)) : ''}</span>
     </div>`;

  const inner = `
  <div class="role">
    <div class="role-header">
      <div style="font-size:11px;color:#444">EXA - Software Contable</div>
      <h2>${esc(empresa)}</h2>
      <div class="role-sub">R.U.C.: ${esc(ruc)} · TELÉFONO: ${esc(tel)}<br/>${esc(ciudad)}</div>
      <div class="line"></div>
      <div style="font-size:13px;font-weight:bold">ROL DE PAGOS</div>
    </div>

    <div class="box">
      <div class="grid-3">
        <div><b>Apellidos:</b> ${esc(r.apellidos)}</div>
        <div><b>Nombres:</b> ${esc(r.nombres)}</div>
        <div><b>C.I.:</b> ${esc(r.cedula)}</div>
      </div>
      <div class="grid-3" style="margin-top:4px">
        <div><b>Periodo:</b> ${esc(r.periodo || r.periodo_desde || r.periodo_hasta || "")}</div>
        <div><b>Días:</b> ${esc(r.dias)}</div>
        <div style="text-align:right"><b>Fecha:</b> ${getFechaFinMes(r.periodo || r.periodo_desde || r.periodo_hasta || r.mes || "")}</div>
      </div>
    </div>

    <div class="grid-2" style="margin-top:8px">
      <div class="box">
        <h3>INGRESOS</h3>
        ${ingresosHtml}
        ${pieIng}
      </div>
      <div class="box">
        <h3>EGRESOS</h3>
        ${egresosHtml}
        ${pieEgr}
      </div>
    </div>

    <div class="box" style="margin-top:8px">
      ${pieNeto}
    </div>

    <div class="firmas">
      <div class="grid-3">
        <div><div class="linea"></div><div>RECIBÍ CONFORME<br/>${esc(r.apellidos)} ${esc(r.nombres)}</div></div>
        <div><div class="linea"></div><div>EMPLEADOR(A)</div></div>
        <div><div class="linea"></div><div>CONTADOR(A)</div></div>
      </div>
    </div>
  </div>`;
  return wrap ? inner : `<div class="role-wrap">${inner}</div>`;
}

function getFechaFinMes(periodo) {
  if (!periodo) return "";

  periodo = String(periodo).trim().toUpperCase();

  // Diccionario de meses en español
  const meses = {
    "ENERO": 1, "FEBRERO": 2, "MARZO": 3, "ABRIL": 4,
    "MAYO": 5, "JUNIO": 6, "JULIO": 7, "AGOSTO": 8,
    "SEPTIEMBRE": 9, "SETIEMBRE": 9, "OCTUBRE": 10,
    "NOVIEMBRE": 11, "DICIEMBRE": 12
  };

  // 1️⃣ Si el periodo es tipo "2024-06" o "2024/06"
  let match = periodo.match(/^(\d{4})[-\/](\d{2})$/);
  if (match) {
    let year = parseInt(match[1], 10);
    let month = parseInt(match[2], 10);
    let lastDay = new Date(year, month, 0).getDate();
    return `${lastDay}/${String(month).padStart(2, '0')}/${year}`;
  }

  // 2️⃣ Si el periodo es tipo "2024-06-01 al 2024-06-30"
  let range = periodo.split(" AL ");
  if (range.length === 2) {
    return range[1]; // ya viene en formato fecha
  }

  // 3️⃣ Si el periodo es tipo fecha completa YYYY-MM-DD
  let dateMatch = periodo.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (dateMatch) {
    return periodo.split("-").reverse().join("/"); // lo formateamos DD/MM/YYYY
  }

  // 4️⃣ Si el periodo es un nombre de mes (ejemplo: "JULIO")
  if (meses[periodo]) {
    let year = new Date().getFullYear(); // puedes cambiarlo si necesitas otro año
    let month = meses[periodo];
    let lastDay = new Date(year, month, 0).getDate();
    return `${lastDay}/${String(month).padStart(2, '0')}/${year}`;
  }

  // 5️⃣ Caso por defecto: devolver tal cual
  return periodo;
}
</script>
</html>
