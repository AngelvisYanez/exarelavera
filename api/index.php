            return strtolower(isset($t['name']) ? $t['name'] : '') === $modLower;
        }));
        $spec['info']['description'] = "Documentación de la API REST - Módulo: **" . htmlspecialchars($modulo) . "**.";
    } else {
        // Por defecto: Directorio Operativo: Contactos, Plantas, Choferes, Vehículos
        $publicPaths = array('/v1/contactos', '/v1/plantas', '/v1/choferes', '/v1/vehiculos');
        $filteredPaths = array();
        foreach ($publicPaths as $p) {
            if (isset($spec['paths'][$p])) {
                $filteredPaths[$p] = $spec['paths'][$p];
            }
        }
        $spec['paths'] = !empty($filteredPaths) ? $filteredPaths : new stdClass();

        $spec['tags'] = array(
            array(
                'name' => 'contactos',
                'description' => 'Directorio de contactos autorizados para notificaciones operativas (solo lectura)'
            ),
            array(
                'name' => 'plantas',
                'description' => 'Directorio de plantas de beneficio y ubicaciones operativas (solo lectura)'
            ),
            array(
                'name' => 'choferes',
                'description' => 'Directorio de choferes y conductores por planta (solo lectura)'
            ),
            array(
                'name' => 'vehiculos',
                'description' => 'Directorio de volquetas mineras y vehículos de carga por planta (solo lectura)'
            )
        );

        $spec['info']['description'] = "## EXA Contable API - Directorio Operativo\n\n" .
            "Endpoints autorizados para la integración y consulta de contactos para notificaciones, plantas de beneficio, choferes de planta y vehículos/volquetas asignadas.\n\n" .
            "Requiere autenticación mediante token Bearer con permisos habilitados sobre cada recurso.";
    }

    echo json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
};