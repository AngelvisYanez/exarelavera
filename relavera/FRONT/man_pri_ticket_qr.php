<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket Manifiesto - QR</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #000;
            background: #f5f5f5;
            padding: 10px;
        }

        .ticket {
            width: 210mm;
            max-width: 210mm;
            height: 148.5mm;
            max-height: 148.5mm;
            margin: 0 auto;
            padding: 10px 15px;
            background: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            text-align: center;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #000;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .header h2 {
            font-size: 10px;
            font-weight: normal;
            color: #333;
            line-height: 1.2;
        }

        .info-section {
            margin-bottom: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
        }

        .info-box {
            border: 1px solid #ddd;
            padding: 6px;
            background: #f9f9f9;
            border-radius: 3px;
        }

        .info-box h3 {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 4px;
            padding-bottom: 2px;
            border-bottom: 1px solid #ccc;
            color: #000;
            line-height: 1.2;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 8px;
            padding: 1px 0;
            line-height: 1.3;
        }

        .info-row.full-width {
            grid-column: 1 / -1;
        }

        .info-label {
            font-weight: bold;
            color: #333;
            min-width: 70px;
        }

        .info-value {
            text-align: right;
            word-break: break-word;
            flex: 1;
            color: #000;
            font-size: 8px;
        }

        .info-value.left {
            text-align: left;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .qr-section {
            text-align: center;
            margin: 6px 0;
            padding: 6px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            background: #f9f9f9;
        }

        .qr-code {
            margin: 0 auto;
            display: block;
            width: 80px;
            height: 80px;
            max-width: 100%;
            border: 1px solid #000;
            background: #fff;
            padding: 3px;
            object-fit: contain;
        }
        
        .qr-code[src=""],
        .qr-code[src*="{QR_CODE_IMAGE}"] {
            display: none;
        }

        .qr-text {
            margin-top: 4px;
            font-size: 8px;
            font-weight: bold;
            color: #000;
            line-height: 1.2;
        }

        .footer {
            text-align: center;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #000;
            font-size: 7px;
            color: #666;
        }

        .footer p {
            margin: 2px 0;
            line-height: 1.2;
        }

        .highlight {
            background: #ffffcc;
            padding: 1px 3px;
            font-weight: bold;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            margin: 6px 0 4px 0;
            padding-bottom: 2px;
            border-bottom: 1px solid #000;
            text-transform: uppercase;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
                background: #fff;
            }

            .ticket {
                border: none;
                box-shadow: none;
                padding: 10px 15px;
                width: 210mm;
                height: 148.5mm;
                max-height: 148.5mm;
                page-break-after: always;
                page-break-inside: avoid;
            }

            @page {
                size: A4;
                margin: 0;
            }

            .no-print {
                display: none;
            }
        }

        @media screen {
            .print-button {
                text-align: center;
                margin: 20px 0;
            }

            .print-button button {
                background: #007bff;
                color: white;
                border: none;
                padding: 12px 30px;
                font-size: 16px;
                border-radius: 5px;
                cursor: pointer;
            }

            .print-button button:hover {
                background: #0056b3;
            }
        }
    </style>
</head>
<body>
    <div class="print-button no-print">
        <button onclick="window.print();">Imprimir Ticket</button>
    </div>

    <div class="ticket">
        <!-- Encabezado -->
        <div class="header">
            <h1>MINISTERIO DEL AMBIENTE</h1>
            <h2>MANIFIESTO DE DESECHOS PELIGROSOS</h2>
        </div>

        <!-- Información del Manifiesto -->
        <div class="info-section">
            <div class="section-title">Información del Manifiesto</div>
            
            <div class="info-grid">
                <div class="info-box">
                    <h3>Datos Generales</h3>
                    <div class="info-row">
                        <span class="info-label">Código:</span>
                        <span class="info-value highlight">{Man_Cod}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">N° Manifiesto:</span>
                        <span class="info-value">{Man_Num}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Fecha:</span>
                        <span class="info-value">{Man_Fec}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Hora:</span>
                        <span class="info-value">{Man_Hor}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado:</span>
                        <span class="info-value">{estado}</span>
                    </div>
                </div>

                <div class="info-box">
                    <h3>Cliente</h3>
                    <div class="info-row">
                        <span class="info-label">Nombre:</span>
                        <span class="info-value left">{cliente}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Cédula/RUC:</span>
                        <span class="info-value">{Cli_Ced}</span>
                    </div>
                </div>
            </div>

            <div class="separator"></div>

            <div class="info-grid">
                <div class="info-box">
                    <h3>Transporte</h3>
                    <div class="info-row">
                        <span class="info-label">Chofer:</span>
                        <span class="info-value left">{chofer}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Vehículo:</span>
                        <span class="info-value">{Veh_Pla}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tipo Transporte:</span>
                        <span class="info-value">{Mat_Des}</span>
                    </div>
                </div>

                <div class="info-box">
                    <h3>Desechos</h3>
                    <div class="info-row">
                        <span class="info-label">Tipo:</span>
                        <span class="info-value left">{Tde_Des}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Peso (kg):</span>
                        <span class="info-value">{Man_Pes}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Total:</span>
                        <span class="info-value highlight">{total}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Código QR -->
        <div class="qr-section">
            <div id="qr-container">
                <img src="{QR_CODE_IMAGE}" alt="Código QR del Manifiesto" class="qr-code" 
                     onerror="this.onerror=null; handleQRError(this);" />
            </div>
            <p class="qr-text">Escanea el código QR</p>
            <p style="margin-top: 2px; font-size: 7px; color: #666;">
                Código: <strong>{Man_Cod}</strong> | N°: <strong>{Man_Num}</strong>
            </p>
        </div>
        
        <script>
            // Función para manejar errores del QR
            function handleQRError(imgElement) {
                if (imgElement) {
                    imgElement.style.display = 'none';
                    var container = imgElement.parentNode;
                    if (container) {
                        // Verificar si ya existe un fallback
                        var existingFallback = container.querySelector('.qr-fallback');
                        if (!existingFallback) {
                            var fallback = document.createElement('div');
                            fallback.className = 'qr-fallback';
                            fallback.style.cssText = 'width: 80px; height: 80px; margin: 0 auto; background: #f0f0f0; border: 1px solid #999; display: flex; align-items: center; justify-content: center;';
                            fallback.innerHTML = '<p style="font-size: 8px; color: #666; margin: 0; text-align: center;">QR<br/>No disponible</p>';
                            container.insertBefore(fallback, imgElement);
                        }
                    }
                }
            }
            
            // Verificar si el placeholder no fue reemplazado al cargar la página
            (function() {
                var qrImg = document.querySelector('.qr-code');
                if (qrImg) {
                    var src = qrImg.src || '';
                    // Verificar si el placeholder no fue reemplazado o si la imagen es inválida
                    if (src.indexOf('{QR_CODE_IMAGE}') !== -1 || 
                        src === '' || 
                        src.indexOf('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ') !== -1) {
                        handleQRError(qrImg);
                    } else {
                        // Verificar si la imagen se carga correctamente
                        qrImg.onload = function() {
                            // Si la imagen se carga, verificar que tenga un tamaño válido
                            if (this.naturalWidth === 0 || this.naturalHeight === 0) {
                                handleQRError(this);
                            }
                        };
                    }
                }
            })();
        </script>

        <!-- Pie de página -->
        <div class="footer">
            <p><strong>Generado:</strong> {fecha_impresion}</p>
            <p>Conserve este documento como comprobante</p>
        </div>
    </div>
</body>
</html>

