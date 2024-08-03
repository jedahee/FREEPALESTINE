// Variables gloables
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const characters =
  "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
const api_endpoint = "https://data.techforpalestine.org/api/v3/summary.json";
const url_config = "config/config.json";

window.onload = function (e) {
  AOS.init(); // Iniciar AOS

  // Variables que contienen la URL actual
  shareUrl = encodeURIComponent(window.location.href);
  shareTitle = encodeURIComponent(document.title);

  // Iniciar slider Swiper
  const swiper = new Swiper(".swiper", {
    navigation: {
      nextEl: ".swiper-button-next",
      prevEl: ".swiper-button-prev",
    },
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
  });

  // Variables DOM
  let input_sign_container_mail = document.querySelector(
    ".share__sign__input-container.mail"
  );

  loader = document.querySelector(".loader-container");
  popup_msg = document.querySelector(".popup .msg");
  popup = document.querySelector(".popup");
  notification = document.querySelector(".notification");

  let input_sign_container_name = document.querySelector(
    ".share__sign__input-container.name"
  );
  let social_networks_container = document.querySelector(
    ".share__networks-container.hidden"
  );
  let input_sign_mail = document.querySelector(".input-sign.mail");
  let input_contact_mail = document.querySelector(".input-sign.mail-contact");
  let input_contact_subject = document.querySelector(".input-sign.subject");
  let textarea_contact_msg = document.querySelector(".msg textarea");
  let input_sign_name = document.querySelector(".input-sign.name");
  let date = document.querySelector(".date");
  let to_sign = document.querySelector(".to-sign");
  let send_email = document.querySelector(".send-email");
  let social_networks = document.querySelector(".social-networks");
  let icon_close = document.querySelector(".popup .icon.close");
  let btn_close = document.querySelector(".notification .btn");

  // Obtener la fecha actual
  const now = new Date();

  // Formatear la fecha
  const dayName = getDayName(now.getDay());
  const day = now.getDate();
  const monthName = getMonthName(now.getMonth());
  const year = now.getFullYear();

  // Construir la fecha en el formato deseado
  const formattedDate = `${dayName}, ${day} de ${monthName}, ${year}`;
  date.textContent = formattedDate;

  // EventListeners
  to_sign.addEventListener("click", function (e) {
    e.preventDefault();
    if (!social_networks_container.classList.contains("hidden"))
      social_networks_container.classList.add("hidden");
  });

  icon_close.addEventListener("click", function (e) {
    e.preventDefault();
    popup.classList.add("hidden");
  });

  if (btn_close) {
    btn_close.addEventListener("click", function (e) {
      e.preventDefault();
      notification.classList.add("hidden");
    });
  }
  // Listener - Ocultar/Mostrar icono de redes
  social_networks.addEventListener("click", function (e) {
    e.preventDefault();
    social_networks_container.classList.toggle("hidden");
  });

  // Listeners - Validación de los campos del fomulario
  input_sign_mail.addEventListener("keyup", function (e) {
    validateInput(this);

    if (isValidEmail(this) && input_sign_name.value.length > 0)
      to_sign.classList.remove("disabled");
    else to_sign.classList.add("disabled");
  });

  input_sign_name.addEventListener("keyup", function (e) {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");

    if (isValidEmail(input_sign_mail) && this.value.length > 0)
      to_sign.classList.remove("disabled");
    else to_sign.classList.add("disabled");
  });

  input_contact_mail.addEventListener("keyup", function (e) {
    validateInput(this);

    if (
      isValidEmail(this) &&
      input_contact_subject.value.length > 0 &&
      textarea_contact_msg.value.length > 0
    )
      send_email.classList.remove("disabled");
    else send_email.classList.add("disabled");
  });

  input_contact_subject.addEventListener("keyup", function (e) {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");

    if (
      isValidEmail(input_contact_mail) &&
      this.value.length > 0 &&
      textarea_contact_msg.value.length > 0
    )
      send_email.classList.remove("disabled");
    else send_email.classList.add("disabled");
  });

  textarea_contact_msg.addEventListener("keyup", function (e) {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");

    if (
      isValidEmail(input_contact_mail) &&
      this.value.length > 0 &&
      input_contact_subject.value.length > 0
    )
      send_email.classList.remove("disabled");
    else send_email.classList.add("disabled");
  });

  // Listeners - Enviar firma / mensaje
  to_sign.addEventListener("click", function (e) {
    e.preventDefault();
    let name = input_sign_name.value;
    let email = input_sign_mail.value;
    loader.classList.remove("hidden");

    setTimeout(function (e) {
      sign(name, email);
    }, 1000);
  });

  send_email.addEventListener("click", function (e) {
    e.preventDefault();
    let subject = input_contact_subject.value;
    let email = input_contact_mail.value;
    let msg = textarea_contact_msg.value;

    loader.classList.remove("hidden"); // Aparece el laoder

    loadConfig().then((config) => {
      const emailjsTemplateIdNotification =
        config.emailjsTemplateIdNotification;
      const emailjsUserId = config.emailjsUserId;
      const emailjsServiceId = config.emailjsServiceId;

      emailjs.init(emailjsUserId);

      // Crear contenido HTML personalizado para la notificación
      const notificationEmailContent = `
      <h1>${email} te escribió:</h1>
      <p>Correo: ${email}</p>
      <p>Asunto: ${subject}</p>
      <p>Mensaje: ${msg}</p>`;

      // Parámetros para la notificación
      const notificationTemplateParams = {
        to_email: config.fpEmail,
        from_email: email,
        subject: subject,
        message_html: notificationEmailContent,
      };

      // Enviar notificación con EMAIL JS
      emailjs
        .send(
          emailjsServiceId,
          emailjsTemplateIdNotification,
          notificationTemplateParams
        )
        .then(
          (response) => {
            setPopup(
              false,
              "¡Se ha enviado el mensaje correctamente! En breve lo revisaremos."
            );
            document.querySelector(".input-sign.mail-contact").value = "";
            document.querySelector(".input-sign.subject").value = "";
            document.querySelector(".msg textarea").value = "";

            loader.classList.add("hidden");
          },
          (error) => {
            setPopup(
              false,
              "No se ha podido envíar el mensaje. Intentelo de nuevo más tarde."
            );
            loader.classList.add("hidden");
          }
        );
    });
  });

  getData(api_endpoint);
};

// Esta función depsliegua una notificaciin flotante
function setPopup(error, msg) {
  if (error) {
    popup.classList.add("error");
    popup.classList.remove("success");
  } else {
    popup.classList.remove("error");
    popup.classList.add("success");
  }

  popup_msg.textContent = msg;

  if (popup.classList.contains("hidden")) popup.classList.remove("hidden");
}

// Función para cargar la configuración desde config.json
function loadConfig() {
  return fetch(url_config).then((response) => {
    if (!response.ok) {
      setPopup(true, "Error al cargar el archivo de configuración");
    }
    return response.json();
  });
}

// Esta función retorna una cadena de texto generada aleatoriamente
function generateRandomString() {
  let result = "";
  const charactersLength = characters.length;
  for (let i = 0; i < charactersLength; i++) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
  }

  return result;
}

// Funciín que devuelve la URL actual completa
function getCurrentDomain() {
  const { protocol, hostname } = window.location;
  return `${protocol}//${hostname}`;
}

// Función que guarda una cadena aleatoria en el archivo JSON correspondiente
function saveRandomString(url, name, email, randomString) {
  return fetch(url, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      email: email,
      name: name,
      randomString: randomString,
      action: "SaveString",
    }),
  }).then(function (response) {
    return response.json();
  });
}

// Esta función es la que regustra la firma
function sign(name, email) {
  loadConfig().then((config) => {
    // Carga el archivo de configuraciín
    const randomString = generateRandomString();
    saveRandomString(config.urlBackend, name, email, randomString).then(
      // Crea la petición de firma
      (result) => {
        if (result.status) {
          const emailjsUserId = config.emailjsUserId;
          const emailjsServiceId = config.emailjsServiceId;
          const emailjsTemplateIdUser = config.emailjsTemplateIdUser;

          emailjs.init(emailjsUserId);

          const validate_user_url =
            getCurrentDomain() +
            "/freepalestine/" + // ! Borrar antes de subir a PRO
            `backend/save_signature.php?name=${name}&email=${email}&randomString=${randomString}&action=Sign`;

          const cancel_sign_user_url =
            getCurrentDomain() +
            "/freepalestine/" + // ! Borrar antes de subir a PRO
            `backend/save_signature.php?name=${name}&email=${email}&randomString=${randomString}&action=CancelSign`;

          // Crear contenido HTML personalizado para el correo del usuario
          const userEmailContent = `
          <div>
            <h1 style="font-family: system-ui">Gracias por tu Firma ✊</h1>
            <p style="font-family: 'Google Sans';">Hola ${name},</p>
            <p style="font-family: 'Google Sans';">Muchas gracias por tu apoyo, significa mucho para nosotros.</p>
            <p style="font-family: 'Google Sans';">Para finalizar su firma pulse en el siguiente enlace:</p>
            <p style="font-family: 'Google Sans';"><a target="_blank" style="margin: 5px 0;font-size: 15px;font-weight: 400;color:white;padding:.5rem 1rem;background-color:#D80032;border-radius:8px;text-decoration:none" href="${validate_user_url}">Completar firma</a></p>
            <p style="font-family: 'Google Sans';">Gracias a tu apoyo estamos más cerca de completar las metas establecidas en la <a target="_blank" style="font-weight: bold;color: #D80034;" href="${
              getCurrentDomain() + "/freepalestine/"
            }">web</a></p>
            <p style="font-family: 'Google Sans';">¡Un abrazo!</p>
            <p style="font-family: 'Google Sans'; font-size: 10px;">Si deseas eliminar tu firma de nuestros datos. Pulse <a style="color: #d80032;" href="${cancel_sign_user_url}">aquí</a></p>
          </div>`;

          // Parámetros para el correo del usuario
          const userTemplateParams = {
            to_email: email,
            from_name: name,
            message_html: userEmailContent,
          };

          // Enviar correo al usuario
          emailjs
            .send(emailjsServiceId, emailjsTemplateIdUser, userTemplateParams)
            .then(
              (response) => {
                setPopup(
                  false,
                  "Se ha enviado un correo de confirmación a: " +
                    email +
                    ". Revise la bandeja de entrada"
                );
                document.querySelector(".input-sign.mail").value = "";
                document.querySelector(".input-sign.name").value = "";

                loader.classList.add("hidden");
              },
              (error) => {
                setPopup(
                  true,
                  "Error al enviar el correo electrónico. Por favor, intente firmar de nuevo"
                );
              }
            );
        } else {
          loader.classList.add("hidden");
          setPopup(true, result.text);
        }
      }
    );
  });
}

// Estas funciones se encargan de contruir la URL para compartir en redes sociales
function shareOnFacebook() {
  const facebookUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareUrl}`;
  window.open(facebookUrl, "_blank");
}

function shareOnTwitter() {
  const twitterUrl = `https://twitter.com/intent/tweet?url=${shareUrl}&text=${shareTitle}`;
  window.open(twitterUrl, "_blank");
}

function shareOnLinkedIn() {
  const linkedInUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${shareUrl}`;
  window.open(linkedInUrl, "_blank");
}

function shareOnWhatsApp() {
  const whatsAppUrl = `https://api.whatsapp.com/send?text=${shareTitle}%20${shareUrl}`;
  window.open(whatsAppUrl, "_blank");
}

// Función para validar el email
function isValidEmail(emailInput) {
  return emailRegex.test(emailInput.value);
}

// Función que valida los campos de los formularios y les actualiza el diseño a correcto/incorrecto
function validateInput($this) {
  if ($this.value.length > 0 && isValidEmail($this)) {
    $this.parentNode.classList.add("correct");
  } else {
    $this.parentNode.classList.remove("correct");
  }
}

// Función para obtener el nombre del día de la semana en castellano
function getDayName(dayIndex) {
  const days = [
    "Domingo",
    "Lunes",
    "Martes",
    "Miércoles",
    "Jueves",
    "Viernes",
    "Sábado",
  ];
  return days[dayIndex];
}

// Función para obtener el nombre del mes en castellano
function getMonthName(monthIndex) {
  const months = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
  ];
  return months[monthIndex];
}

// Funciín para obtener los datos de las personas y niñ@s asesinados por Israel. Datos recogidos de una API
function getData(url) {
  fetch(url)
    .then((response) => {
      if (!response.ok) {
        setPopup(true, "Error en la conexión a internet");
      }
      return response.json();
    })
    .then((data) => {
      document.querySelector(".extra-info .sect1 > h3").textContent =
        data.gaza.killed.total;

      document.querySelector(".extra-info .sect2 > h3").textContent =
        data.gaza.killed.children;
    })
    .catch((error) => {
      console.error("Hubo un problema con la petición:", error);
    });
}
