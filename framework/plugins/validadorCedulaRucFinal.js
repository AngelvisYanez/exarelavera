"use strict";

 class ValidacionCedulaRucService {
  /**
   * Permite validar cualquier número de identificación, puede ser cédula, ruc
   * de persona natural, ruc de sociedad pública, ruc de sociedad privada
   *
   * @param identificacion
   * @return
   */
constructor(){
    this.resp={success:false,message:'Identificacion no valida',tipo_abrev:''};
}

  static esIdentificacionValida(identificacion) {
  	this.resp={success:false,message:'Identificacion no valida',tipo_abrev:''};
    if (this.isNullOrEmpty(identificacion)) {
      return this.resp;
    } else {
      var longitud = identificacion.length;
      this.esNumeroIdentificacionValida(identificacion, longitud);

      if (longitud === 10) {
        if(this.esCedulaValida(identificacion)){
        	return this.resp={success:true,message:'Identificacion valida',tipo_abrev:'NA'};
        }
        else{
        	return this.resp={success:false,message:'Identificacion no valida'};
        }
      } else if (longitud === 13) {
        var tercerDigito = parseInt(identificacion.substring(2, 3),10);
        var ultimo = identificacion.substring(10, 13);
        var cedulaRuc = identificacion.substring(0, 10);

        if (0 <= tercerDigito && tercerDigito <= 5 && ultimo == '001') {
          if(this.esCedulaValida(cedulaRuc)){
          	return this.resp={success:true,message:'Identificacion valida',tipo_abrev:'NA'};
          }
          else{
          	return this.resp={success:false,message:'Identificacion no valida'};
          }
        } else if (6 === tercerDigito) {
          if(this.esCodigoProvinciaValido(identificacion)){
          	return this.resp={success:true,message:'Identificacion valida',tipo_abrev:'PU'};
          }
          else{
          	return this.resp={success:false,message:'Identificacion no valida'};
          }
        } else if (9 === tercerDigito) {
          if(this.esCodigoProvinciaValido(identificacion)){
          	return this.resp={success:true,message:'Identificacion valida',tipo_abrev:'PR'};
          }
          else{
          	return this.resp={success:false,message:'Identificacion no valida'};
          }
        } else {
          return this.resp;
        }
      } else {
        return this.resp={success:true,message:'Identificacion valida pasaporte',tipo_abrev:'PA'};
      }
    }
  }

  /**
   * Permite verificar si un número de cédula es válido o no
   * @param numeroCedula
   * @return
   */
  static esCedulaValida(numeroCedula) {
    const esIdentificacionValida = this.validacionesPrevias(
      numeroCedula,
      10,
      0
    );

    if (esIdentificacionValida) {
      const ultimoDigito = parseInt(numeroCedula.charAt(9), 10);

      return this.algoritmoVerificaIdentificacion(
        numeroCedula,
        ultimoDigito,
        0
      );
    } else {
      return this.resp;
    }
  }

  /**
   * Permite verificar si un número de ruc de cualquier tipo es válido o no
   *
   * @param numeroRuc
   * @return
   */
  static esRucValido(numeroRuc) {
    return (
      this.esRucPersonaNaturalValido(numeroRuc) ||
      this.esRucSociedadPrivadaValido(numeroRuc) ||
      this.esRucSociedadPublicaValido(numeroRuc)
    );
  }

  /**
   * Permite verificar si un número de ruc para personas naturales es válido o no.
   *
   * @param numeroRuc
   * @return
   */
  static esRucPersonaNaturalValido(numeroRuc) {
    const esIdentificacionValida = this.validacionesPrevias(
      numeroRuc,
      13,
      1
    );

    if (esIdentificacionValida) {
      const ultimoDigito = parseInt(numeroRuc.charAt(9), 10);
      return this.algoritmoVerificaIdentificacion(
        numeroRuc,
        ultimoDigito,
        1
      );
    } else {
      return false;
    }
  }

  /**
   * Permite verificar si un número de ruc para sociedades privadas es válido o no.
   *
   * @param numeroRuc
   * @return
   */
  static esRucSociedadPrivadaValido(numeroRuc) {
    const esIdentificacionValida = this.validacionesPrevias(
      numeroRuc,
      13,
      2
    );
    if (esIdentificacionValida) {
      const ultimoDigito = parseInt(numeroRuc.charAt(9), 10);
      return this.algoritmoVerificaIdentificacion(
        numeroRuc,
        ultimoDigito,
        2
      );
    } else {
      return false;
    }
  }

  /**
   * Permite verificar si un número de ruc para sociedades públicas es válido o no.
   *
   * @param numeroRuc
   * @return
   */
  static esRucSociedadPublicaValido(numeroRuc) {
    const esIdentificacionValida = this.validacionesPrevias(
      numeroRuc,
      13,
      3
    );
    if (esIdentificacionValida) {
      const ultimoDigito = parseInt(numeroRuc.charAt(8), 10);
      return this.algoritmoVerificaIdentificacion(
        numeroRuc,
        ultimoDigito,
        3
      );
    } else {
      return false;
    }
  }

  /**
   * VALIDACIONES PREVIAS AL ALGORITMO DE IDENTIFICACIÓN PARA CÉDULA Y RUC
   * @param contenido
   */
  static isNullOrEmpty(contenido) {
    return undefined === contenido || null === contenido || '' === contenido;
  }

  /**
   * @param identificacion
   * @param longitud
   * @param tipoIdentificacion
   * @param validarEstablecimiento
   */
  static validacionesPrevias(
    identificacion,
    longitud,
    tipoIdentificacion
  ) {
    if (0 === tipoIdentificacion) {
      return (
        this.esNumeroIdentificacionValida(identificacion, longitud) &&
        this.esCodigoProvinciaValido(identificacion) &&
        this.esTercerDigitoValido(identificacion, tipoIdentificacion)
      );
    } else {
      return (
        this.esNumeroIdentificacionValida(identificacion, longitud) &&
        this.esCodigoProvinciaValido(identificacion) &&
        this.esTercerDigitoValido(identificacion, tipoIdentificacion) &&
        this.esCodigoEstablecimientoValido(identificacion)
      );
    }
  }

  /**
   * @param numeroIdentificacion
   * @param longitud
   */
  static esNumeroIdentificacionValida(
    numeroIdentificacion,
    longitud
  ) {
    return (
      numeroIdentificacion.length === longitud &&
      /^\d+$/.test(numeroIdentificacion)
    );
  }

  /**
   * @param numeroCedula
   */
  static esCodigoProvinciaValido(numeroCedula) {
    const numeroProvincia = parseInt(numeroCedula.substring(0, 2), 10);
    return numeroProvincia > 0 && numeroProvincia <= 24;
  }

  /**
   * @param numeroRuc
   * @return
   */
  static esCodigoEstablecimientoValido(numeroRuc) {
    const ultimosTresDigitos = parseInt(
      numeroRuc.substring(10, 13),
      10
    );
    return !(ultimosTresDigitos < 1);
  }

  /**
   * Tercer dígito:
   * <p>
   * RUC jurídicos y extranjeros sin cédula: 9
   * <p>
   * RUC públicos: 6
   * <p>
   * RUC natural menor a 6: (0,1,2,3,4,5)
   *
   * @param numeroCedula
   * @param tipoIdentificacion
   *            de documento cedula, ruc
   * @return
   */
  static esTercerDigitoValido(
    numeroCedula,
    tipoIdentificacion
  ) {
    const tercerDigito = parseInt(numeroCedula.substring(2, 3), 10);

    if (tipoIdentificacion === 0) {
      return this.esTercerDigitoCedulaValido(tercerDigito);
    }

    if (tipoIdentificacion === 1) {
      return this.verificarTercerDigitoRucNatural(tercerDigito);
    }

    if (tipoIdentificacion === 3) {
      return this.verificarTercerDigitoRucPublica(tercerDigito);
    }

    if (tipoIdentificacion === 2) {
      return this.verificarTercerDigitoRucPrivada(tercerDigito);
    }

    return false;
  }

  /**
   * @param tercerDigito
   * @return
   */
  static esTercerDigitoCedulaValido(tercerDigito) {
    return !isNaN(tercerDigito) && !(tercerDigito < 0 && tercerDigito > 5);
  }

  /**
   * @param tercerDigito
   * @return
   */
  static verificarTercerDigitoRucNatural(tercerDigito) {
    return tercerDigito >= 0 || tercerDigito <= 5;
  }

  /**
   * @param tercerDigito
   * @return
   */
  static verificarTercerDigitoRucPrivada(tercerDigito) {
    return tercerDigito === 9;
  }

  /**
   * @param tercerDigito
   * @return
   */
  static verificarTercerDigitoRucPublica(tercerDigito) {
    return tercerDigito === 6;
  }

  /**
   * ALGORITMO DE VALIDACION DE IDENTIFICACION
   */

  /**
   * @param numeroIdentificacion
   * @param ultimoDigito
   * @param tipoIdentificacion
   * @return
   */
  static algoritmoVerificaIdentificacion(
    numeroIdentificacion,
    ultimoDigito,
    tipoIdentificacion
  ) {
    const sumatoria = this.sumarDigitosIdentificacion(
      numeroIdentificacion,
      tipoIdentificacion
    );

    const digitoVerificador = this.obtenerDigitoVerificador(
      sumatoria,
      tipoIdentificacion
    );

    return ultimoDigito === digitoVerificador;
  }

  /**
   * @param numeroIdentificacion
   * @param tipoIdentificacion
   * @return
   */
  static sumarDigitosIdentificacion(
    numeroIdentificacion,
    tipoIdentificacion
  ) {
    let coeficientes = this.obtenerCoeficientes(tipoIdentificacion);
    const identificacion = numeroIdentificacion.split('');

    let sumatoriaCocienteIdentificacion = 0;

    for (let posicion = 0; posicion < coeficientes.length; posicion++) {
      const resultado =
        parseInt(identificacion[posicion], 10) * coeficientes[posicion];

      const sumatoria = this.sumatoriaMultiplicacion(
        resultado,
        tipoIdentificacion
      );

      sumatoriaCocienteIdentificacion =
        sumatoriaCocienteIdentificacion + sumatoria;
    }

    return sumatoriaCocienteIdentificacion;
  }

  /**
   * @param multiplicacionValores
   * @param tipoIdentificacion
   * @return
   */
  static sumatoriaMultiplicacion(
    multiplicacionValores,
    tipoIdentificacion
  ) {
    if (tipoIdentificacion === 0) {
      return multiplicacionValores >= 10
        ? multiplicacionValores - 9
        : multiplicacionValores;
    } else if (
      tipoIdentificacion === 1
    ) {
      const identificacion = String(multiplicacionValores).split('');
      let sumatoria = 0;

      for (let posicion = 0; posicion < identificacion.length; posicion++) {
        sumatoria = sumatoria + parseInt(identificacion[posicion], 10);
      }

      return sumatoria;
    } else {
      return multiplicacionValores;
    }
  }

  /**
   * @param tipoIdentificacion
   * @return
   */
  static obtenerCoeficientes(
    tipoIdentificacion
  ){
    if (
      tipoIdentificacion === 0 ||
      tipoIdentificacion === 1
    ) {
      return [2, 1, 2, 1, 2, 1, 2, 1, 2];
    } else if (
      tipoIdentificacion === 2
    ) {
      return [4, 3, 2, 7, 6, 5, 4, 3, 2];
    } else if (
      tipoIdentificacion === 3
    ) {
      return [3, 2, 7, 6, 5, 4, 3, 2];
    } else {
      return null;
    }
  }

  /**
   * @param sumatoria
   * @param tipoIdentificacion
   * @return
   */
  static obtenerDigitoVerificador(
    sumatoria,
    tipoIdentificacion
  ) {
    let residuo = 0;

    if (
      tipoIdentificacion === 0 ||
      tipoIdentificacion === 1
    ) {
      residuo = sumatoria % 10;
      return residuo === 0 ? 0 : 10 - residuo;
    } else if (
      tipoIdentificacion === 3 ||
      tipoIdentificacion === 2
    ) {
      residuo = sumatoria % 11;
      return residuo === 0 ? 0 : 11 - residuo;
    } else {
      return null;
    }
  }
}