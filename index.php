                <form id="form-change-pass">
                    <div class="form-group position-relative mb-3">
                        <input class="form-control" type="password" id="old_pass" placeholder="Clave Actual" required />
                        <i class="bi bi-key-fill form-control-icon"></i>
                    </div>
                    <div class="form-group position-relative mb-3">
                        <input class="form-control" type="password" id="new_pass" placeholder="Nueva Clave" required />
                        <i class="bi bi-shield-lock-fill form-control-icon"></i>
                    </div>
                    <div class="form-group position-relative mb-3">
                        <input class="form-control" type="password" id="conf_pass" placeholder="Confirmar Clave" required />
                        <i class="bi bi-check-circle-fill form-control-icon"></i>
                    </div>
                    
                    <div class="login-actions mt-4">
                        <button class="btn btn-primary w-100" type="button" id="btnSavePass" onclick="saveNewPass()">
                            Actualizar Contraseña <i class="bi bi-save-fill ms-1"></i>
                        </button>
                        <button class="btn btn-link w-100 mt-2 text-decoration-none small text-secondary" type="button" onclick="location.href='administrador/LOGICA/logout.php'">
                            Cancelar y Salir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de cambio de contraseña obligatorio -->
    <div class="modal fade" id="modalDefaultPass" tabindex="-1" role="dialog" aria-labelledby="modalLabel" data-bs-backdrop="static" data-bs-keyboard="false" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 16px; box-shadow: 0 15px 50px rgba(0,0,0,0.4); border: none; overflow: hidden;">
                <div class="modal-header" style="background: #801326; color: #fff; border: none; padding: 18px 24px;">
                    <h5 class="modal-title fw-bold" id="modalLabel">
                        <i class="bi bi-shield-lock-fill me-2"></i> ALERTA DE SEGURIDAD
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center" style="padding: 35px 30px; background: #fff;">
                    <div class="mb-3">
                        <i class="bi bi-key-fill" style="font-size: 54px; color: #801326; opacity: 0.9;"></i>
                    </div>
                    <p class="mb-3" style="font-size: 1.05rem; color: #333; line-height: 1.6;">
                        Su cuenta está usando una contraseña genérica.<br>
                        <strong style="color: #801326;">Se le recomienda cambiar su contraseña</strong> para continuar.
                    </p>
                    <div class="py-2">
                        <a href="javascript:void(0)" class="fs-5 fw-bold"
                            style="color: #801326; text-decoration: underline;"
                            onclick="switchToChangePass()">
                            Cambiar Contraseña
                        </a>
                    </div>
                    <p class="text-secondary small mt-3">
                        De lo contrario, no podrá acceder al sistema.
                    </p>
                </div>
                <div class="modal-footer justify-content-center" style="background: #fdfdfd; border-top: 1px solid #f0f0f0; padding: 12px;">
                    <small class="text-muted"><i class="bi bi-info-circle me-1"></i> Esta es una política de seguridad obligatoria.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div><span>&copy; <?php date_default_timezone_set('UTC'); echo date("Y"); ?>. EXA Sistema Contable - Todos los derechos reservados.</span></div>
    </footer>
