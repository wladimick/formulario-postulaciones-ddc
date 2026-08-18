$( document ).ready(function() {
    $('#formulario').submit(function(e) {
        e.preventDefault();
        $("#modalCargando").modal("show");

        var fileInput = $('#curriculum')[0];
        // if (fileInput.files.length === 0) {
        //     alert("Por favor suba un archivo.");
        //     return;
        // }

        // Create FormData object and append the file
        var formData = new FormData();
        formData.append('curriculum', fileInput.files[0]);

        // Header
        var oHeader = {alg: 'HS256', typ: 'JWT'};
        // Payload
        var oPayload = {};
        var tNow = KJUR.jws.IntDate.get('now');
        var tEnd = KJUR.jws.IntDate.get('now + 1day');

        oPayload.nombres = $("#nombres").val();
        oPayload.apellidos = $("#apellidos").val();
        oPayload.fechaDeNacimiento = $("#fechaDeNacimiento").val();
        oPayload.genero = $("#genero").val();
        oPayload.estadoCivil = $("#estadoCivil").val();
        oPayload.nacionalidad = $("#nacionalidad").val();
        oPayload.paisDeOrigen = $("#paisDeOrigen").val();
        oPayload.numeroDePasaporte = $("#numeroDePasaporte").val();
        oPayload.direccionCalle = $("#direccionCalle").val();
        oPayload.direccionNumero = $("#direccionNumero").val();
        oPayload.villaPoblacion = $("#villaPoblacion").val();
        oPayload.comuna = $("#comuna").val();
        oPayload.region = $("#region").val();
        oPayload.celular = $("#celular").val();
        oPayload.email = $("#email").val();
        oPayload.contactoDeEmergencia = $("#contactoDeEmergencia").val();
        oPayload.telefonoContactoDeEmergencia = $("#telefonoContactoDeEmergencia").val();
        oPayload.disenoCalle = $("#disenoCalle").val();
        oPayload.enQuePlantaDeseaTrabajar = $("#enQuePlantaDeseaTrabajar").val();
        oPayload.temporadasTrabajadasEnDDC = $("#temporadasTrabajadasEnDDC").val();
        oPayload.trabajoAlQuePostula = $("#trabajoAlQuePostula").val();
        oPayload.disponibilidadDeTurnos = $("#disponibilidadDeTurnos").val();
        oPayload.tallaDePantalon = $("input[name=tallaDePantalon]:checked", "#formulario").val();
        oPayload.tallaDePolera = $("input[name=tallaDePolera]:checked", "#formulario").val();
        oPayload.numeroDeCalzado = $("#numeroDeCalzado").val();
        oPayload.nivelEducacional = $("#nivelEducacional").val();
        oPayload.experienciasLaboralesPrevias = $("#experienciasLaboralesPrevias").val();
        oPayload.comoSeEnteroDelTrabajo = $("#comoSeEnteroDelTrabajo").val();

        // Sign JWT
        var sHeader = JSON.stringify(oHeader);
        var sPayload = JSON.stringify(oPayload);
        var sJWT = KJUR.jws.JWS.sign("HS256", sHeader, sPayload, {rstr: "secret"});
        
        $.ajax({
            type: "POST",
            url: "email.php",
            headers: {
              'Authorization': 'Bearer ' + sJWT
            },
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) { 
                console.log(response);
                alert(response);
                $("#modalCargando").modal("hide");
            },
            error: function (ajaxresult, status) { 
                alert("Error al enviar solicitud AJAX."); 
                $("#modalCargando").modal("hide");
            }
       });
    });
});