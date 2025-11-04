document.addEventListener("DOMContentLoaded", function () {
    document
        .getElementById("contact-form")
        .addEventListener("submit", function (event) {
            event.preventDefault();

            const submitButton = this.querySelector(
                'input[type="submit"]',
            );
            // Desactiva el botón y cambia el texto
            submitButton.disabled = true;
            submitButton.value = "Enviando...";

            // Crea un objeto FormData
            const formData = new FormData(this);

            fetch(this.action, {
                method: "POST",
                body: formData,
            }).then(response => {
                // We get the JSON body regardless of the HTTP status
                return response.json().then(data => {
                    // If the response was not ok, we throw an error with the server message
                    if (!response.ok) {
                        // The 'data' object here is the parsed JSON from the error response
                        throw { serverError: true, data: data };
                    }
                    // If the response was ok, we just pass the data along
                    return data;
                });
            }).then((data) => {
                // This block now only handles successful (2xx) responses
                alert(data.message); // Muestra el mensaje de éxito
                this.reset(); // Restablece el formulario
            })
                .catch((error) => {
                    if (error.serverError) {
                        // This is a validation or server-side error with a JSON body
                        let errorMessage = "Por favor corrija los siguientes errores:\n";
                        errorMessage += error.data.message.join("\n");
                        alert(errorMessage);
                    } else {
                        // This is a network error or other unexpected issue
                        console.error(error);
                        alert("Ocurrió un error de red al enviar el formulario. Por favor, intente de nuevo.");
                    }
                })
                .finally(() => {
                    // Vuelve a habilitar el botón y restablece el texto
                    submitButton.disabled = false;
                    submitButton.value = "Enviar";
                });
        });
});
