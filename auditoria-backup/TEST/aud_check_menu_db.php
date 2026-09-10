<?php
$databases = array('servicios', 'exa');

foreach ($databases as $db) {
    echo "=== DATABASE: {$db} ===\n";
    $m = new mysqli('127.0.0.1', 'root', '', $db);
    if ($m->connect_error) {
        echo "Error: {$m->connect_error}\n";
        continue;
    }
    $res = $m->query("SELECT p.Pcs_Cod, p.Pcs_Nom, p.Pcs_Lin, p.Org_Cod, o.Org_Des, r.Rut_Des 
                      FROM procesos p 
                      LEFT JOIN organizado o ON p.Org_Cod = o.Org_Cod 
                      LEFT JOIN rutas r ON p.Rut_Cod = r.Rut_Cod
                      WHERE p.Pcs_Nom LIKE '%aud_%' OR r.Rut_Des LIKE '%auditoria%'");
    while ($r = $res->fetch_assoc()) {
        echo "Pcs_Cod={$r['Pcs_Cod']} | {$r['Pcs_Nom']} | '{$r['Pcs_Lin']}' | Org_Cod={$r['Org_Cod']} ({$r['Org_Des']}) | Ruta={$r['Rut_Des']}\n";
    }
}
