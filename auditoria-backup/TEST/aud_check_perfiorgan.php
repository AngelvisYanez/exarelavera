<?php
$m = new mysqli('127.0.0.1', 'root', '', 'servicios');
if ($m->connect_error) die("Error: " . $m->connect_error);

echo "=== PERMISOS DE PROCESOS DE AUDITORIA PARA PERFIL 1130 (CAPACITACION VIDEOS) ===\n";
$res = $m->query("SELECT po.Per_Cod, p.Per_Des, pr.Pcs_Cod, pr.Pcs_Nom, pr.Pcs_Lin 
                  FROM perfiorgan po 
                  JOIN perfiles p ON po.Per_Cod = p.Per_Cod 
                  JOIN procesos pr ON po.Pcs_Cod = pr.Pcs_Cod 
                  WHERE po.Per_Cod = 1130 AND pr.Pcs_Nom LIKE 'aud_%'");

while ($r = $res->fetch_assoc()) {
    echo "Pcs_Cod={$r['Pcs_Cod']} | {$r['Pcs_Nom']} ('{$r['Pcs_Lin']}') | Rol: {$r['Per_Des']}\n";
}
