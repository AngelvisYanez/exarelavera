$(function(){
   
    var flag_pass = 1;
    var flag_existe = 1;
    var password = document.getElementById('Usu_Pal');
    var meter = document.getElementById('password-strength-meter');
    var text = document.getElementById('password-strength-text');
    /**********************
     * VALIDACIONES
     **********************/
    
    /*
     * Validacion current password
     */
    $("#Usu_Pal_c").on("blur",function(){
        $.getDataJson("",{searchPass:true, Usu_Pal: $("#Usu_Pal_c").val()},function(res)
        {
            if (res.existe === 1)
            {
                $("#Usu_Pal_c").fieldValid(true);
                flag_existe = 0;
            }
            else
            {
                $("#Usu_Pal_c").fieldValid(false,"Password actual incorrecto");
                flag_existe = 1;
            }            
        });
    });
    
    $("#Usu_Pal").on("blur",function(){
        validatePass("Usu_Pal","Usu_Pal_C");
     });
    
    $("#Usu_Pal_C").on("blur",function(){
        validatePass("Usu_Pal","Usu_Pal_C");
     });
    
    /*
     * Valida Passwords
     */
    function validatePass(Usu_Pal,Usu_Pal_C)
    {
        if( $('#'+Usu_Pal).val() !== $('#'+Usu_Pal_C).val())
        {
            $('#'+Usu_Pal).fieldValid(false,"No coinciden los caracteres");
            $('#'+Usu_Pal_C).fieldValid(false,"No coinciden los caracteres");
            $('#'+Usu_Pal_C).val("");            
            flag_pass = 1;
        }
        else
        {
            $('#'+Usu_Pal).fieldValid(true);
            $('#'+Usu_Pal_C).fieldValid(true);
            flag_pass = 0;
        }
    }
    
    /********************************************
     * EVENTOS CRUD
     ********************************************/
    
    /*********
     * GUARDAR
     *********/
    $("#btnGuardar").on("click", function(e){
       e.preventDefault();
       if (flag_pass === 0 && flag_existe === 0)
       {
           $.createDialogConfirm("Est&aacute; seguro de realizar esta accion ?","",function(){
               $.getDataJson("",{updatePass:true, Usu_Pal: $("#Usu_Pal").val()},function(res){
               $("#Usu_Pal_c").val("");
               $("#Usu_Pal_c").fieldValid("");
               $("#Usu_Pal").val("");
               $("#Usu_Pal").fieldValid("");
               $("#Usu_Pal_C").val("");
               $("#Usu_Pal_C").fieldValid("");
               meter.value = "";
               text.innerHTML = "";
               $.alert(res.message);
           });
           });          
       }
       else
       {
           $.alert("Verificar password actual y nuevo password");
       }
    });
    
    var strength = {
        0: "Muy Baja",
        1: "Baja",
        2: "Debil",
        3: "Bueno",
        4: "Excelente !"
      }

    password.addEventListener('input', function() {
      var val = password.value;
      var result = zxcvbn(val);

      // Update the password strength meter
      meter.value = result.score;

      // Update the text indicator
      if (val !== "") {
        text.innerHTML = "Dificultad: " + strength[result.score]; 
      } else {
        text.innerHTML = "";
      }
    });
});


