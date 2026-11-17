
document.addEventListener("DOMContentLoaded", () => {

    const modelos = ["mini", "max", "sport", "pro"];

    // 1. Obtener los productos de MySQL mediante PHP
    async function cargarProductos() {
        try {
            const respuesta = await fetch(
                "../php/controllers/ProductoController.php"
            );

            if (!respuesta.ok) {
                throw new Error("Error HTTP: " + respuesta.status);
            }

            const datos = await respuesta.json();

            if (!datos.success) {
                throw new Error("No se pudieron cargar los productos");
            }

            datos.productos.forEach(producto => {
                const modelo = producto.ID_PRODUCTO
                    .replace(/^NS-/i, "")
                    .toLowerCase();

                const modal = document.getElementById(
                    "modal-" + modelo
                );

                if (!modal) return;

                const precioElemento = modal.querySelector(
                    ".modal-body p:last-child"
                );

                if (precioElemento) {
                    precioElemento.textContent = new Intl.NumberFormat(
                        "es-MX",
                        {
                            style: "currency",
                            currency: "MXN"
                        }
                    ).format(Number(producto.PRECIO));
                }
            });

            console.log("Productos cargados:", datos.productos);

        } catch (error) {
            console.error("Error al cargar productos:", error);
        }
    }

    // 2. Abrir las ventanas de los modelos
    const tarjetas = document.querySelectorAll(".modelo-card");

    tarjetas.forEach(tarjeta => {
        const titulo = tarjeta.querySelector("h3");

        if (!titulo) return;

        const modelo = titulo.textContent.trim().toLowerCase();

        tarjeta.addEventListener("click", () => {
            const modal = document.getElementById(
                "modal-" + modelo
            );

            if (modal) {
                modal.classList.add("activo");
            }
        });
    });

    // 3. Cerrar ventanas
    modelos.forEach(modelo => {
        const modal = document.getElementById("modal-" + modelo);

        if (!modal) return;

        const botonesCerrar = [
            document.getElementById("cerrar-" + modelo),
            document.getElementById("btn-cerrar-modal-" + modelo)
        ];

        botonesCerrar.forEach(boton => {
            if (boton) {
                boton.addEventListener("click", () => {
                    modal.classList.remove("activo");
                });
            }
        });

        modal.addEventListener("click", evento => {
            if (evento.target === modal) {
                modal.classList.remove("activo");
            }
        });
    });

    // 4. Cerrar ventanas con Escape
    document.addEventListener("keydown", evento => {
        if (evento.key === "Escape") {
            document.querySelectorAll(".modal.activo")
                .forEach(modal => modal.classList.remove("activo"));
        }
    });

    // 5. Botón iniciar sesión
    const botonLogin = document.querySelector(".btn-login");

    if (botonLogin) {
        botonLogin.addEventListener("click", () => {
            window.location.href = "login.html";
        });
    }

    // 6. Cargar los datos al iniciar la página
    cargarProductos();

});
