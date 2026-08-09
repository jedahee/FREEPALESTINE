const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
const api_endpoint = "https://data.techforpalestine.org/api/v3/summary.json";
const url_config = "config/config.json";

// Traducciones inyectadas por el servidor (window.I18N) + idioma actual
const I18N = window.I18N || {};
const currentLang = document.documentElement.lang || "es";

// Traduce una clave de window.I18N sustituyendo {param}. Fallback: español (el es.js siempre está).
function t(key, params) {
  let msg = I18N[key] || key;
  if (params) {
    for (const k in params) {
      msg = msg.split("{" + k + "}").join(params[k]);
    }
  }
  return msg;
}

// Formatea un número con el separador de miles del idioma actual (igual que t_num en PHP).
const numSeparators = {
  es: ".", en: ",", fr: ".", pt: ".", ar: ".",
};
function num(n) {
  const sep = numSeparators[currentLang] || ".";
  return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, sep);
}

document.addEventListener("DOMContentLoaded", function () {
  const loader = document.querySelector(".loader-container");
  const notification = document.querySelector(".notification");
  const social_networks_container = document.querySelector(".share__networks-container.hidden");
  const input_sign_mail = document.querySelector(".input-sign.mail");
  const input_contact_mail = document.querySelector(".input-sign.mail-contact");
  const input_contact_subject = document.querySelector(".input-sign.subject");
  const textarea_contact_msg = document.querySelector(".msg textarea");
  const input_sign_name = document.querySelector(".input-sign.name");
  const date = document.querySelector(".date");
  const to_sign = document.querySelector(".to-sign");
  const send_email = document.querySelector(".send-email");
  const social_networks = document.querySelector(".social-networks");
  const icon_close = document.querySelector(".popup .icon.close");
  const btn_close = document.querySelector(".notification .btn");

  if (date) {
    date.textContent = new Intl.DateTimeFormat(currentLang, {
      weekday: "long", year: "numeric", month: "long", day: "numeric",
    }).format(new Date());
  }

  // Selector de idioma (dropdown) + recuerda la elección manual en una cookie
  const langSwitcher = document.querySelector("[data-lang-switcher]");
  if (langSwitcher) {
    const langTrigger = langSwitcher.querySelector(".lang-switcher__trigger");
    const langMenu = langSwitcher.querySelector(".lang-switcher__menu");

    const setMenu = (open) => {
      langMenu.classList.toggle("is-open", open);
      langTrigger.setAttribute("aria-expanded", open ? "true" : "false");
    };

    langTrigger.addEventListener("click", function (e) {
      e.stopPropagation();
      setMenu(!langMenu.classList.contains("is-open"));
    });

    document.addEventListener("click", function () {
      setMenu(false);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") setMenu(false);
    });

    langSwitcher.querySelectorAll(".lang-switcher__option").forEach(function (opt) {
      opt.addEventListener("click", function () {
        const code = this.getAttribute("lang");
        if (code) {
          document.cookie = "fp_lang=" + code + "; path=/; max-age=63072000; SameSite=Lax";
        }
      });
    });
  }

  to_sign.addEventListener("click", function () {
    if (!social_networks_container.classList.contains("hidden"))
      social_networks_container.classList.add("hidden");
  });

  icon_close.addEventListener("click", function () {
    document.querySelector(".popup").classList.add("hidden");
  });

  if (btn_close) {
    btn_close.addEventListener("click", function () {
      notification.classList.add("hidden");
    });
  }

  social_networks.addEventListener("click", function (e) {
    e.preventDefault();
    social_networks_container.classList.toggle("hidden");
  });

  input_sign_mail.addEventListener("keyup", function () {
    validateInput(this);
    toggleSignButton(input_sign_mail, input_sign_name, to_sign);
  });

  input_sign_name.addEventListener("keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
    toggleSignButton(input_sign_mail, input_sign_name, to_sign);
  });

  input_contact_mail.addEventListener("keyup", function () {
    validateInput(this);
    toggleContactButton(this, input_contact_subject, textarea_contact_msg, send_email);
  });

  input_contact_subject.addEventListener("keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
    toggleContactButton(input_contact_mail, this, textarea_contact_msg, send_email);
  });

  textarea_contact_msg.addEventListener("keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
    toggleContactButton(input_contact_mail, input_contact_subject, this, send_email);
  });

  to_sign.addEventListener("click", async function () {
    const name = input_sign_name.value;
    const email = input_sign_mail.value;
    to_sign.classList.add("loading", "disabled");
    to_sign.textContent = t("sending");
    loader.classList.remove("hidden");

    await sign(name, email);

    resetSignButton();
    loader.classList.add("hidden");
  });

  send_email.addEventListener("click", async function () {
    const subject = input_contact_subject.value;
    const email = input_contact_mail.value;
    const msg = textarea_contact_msg.value;
    loader.classList.remove("hidden");

    try {
      const config = await loadConfig();
      const res = await fetch(config.emailProxy, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          action: "send_notification",
          email, subject, msg,
          website: document.getElementById("contact-website")?.value || "",
          csrf_token: getCsrfToken(),
          lang: currentLang,
        }),
      });
      const result = await res.json();

      if (result.status) {
        setPopup(false, t("msg_ok"));
        document.querySelector(".input-sign.mail-contact").value = "";
        document.querySelector(".input-sign.subject").value = "";
        document.querySelector(".msg textarea").value = "";
      } else {
        setPopup(true, result.text || t("msg_fail"));
      }
    } catch {
      setPopup(true, t("msg_fail"));
    }

    loader.classList.add("hidden");
  });

  getData(api_endpoint);

  document.querySelectorAll(".goal-deliverable").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const type = this.dataset.deliverable;
      if (type === "stickers") {
        const popup = document.getElementById("goal-popup");
        if (popup) popup.classList.remove("hidden");
        document.body.style.overflow = "hidden";
      } else if (type === "recursos") {
        const target = document.getElementById("recursos");
        if (target) target.scrollIntoView({ behavior: "smooth" });
      }
    });
  });

  const goalPopup = document.getElementById("goal-popup");
  function closeGoalPopup() {
    if (!goalPopup) return;
    goalPopup.classList.add("hidden");
    document.body.style.overflow = "";
  }
  if (goalPopup) {
    goalPopup.querySelectorAll("[data-close='goal-popup']").forEach(function (el) {
      el.addEventListener("click", closeGoalPopup);
    });
    goalPopup.addEventListener("click", function (e) {
      if (e.target === goalPopup) closeGoalPopup();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeGoalPopup();
    });
  }
});

function toggleSignButton(mailInput, nameInput, btn) {
  if (isValidEmail(mailInput) && nameInput.value.length > 0)
    btn.classList.remove("disabled");
  else btn.classList.add("disabled");
}

function toggleContactButton(mailInput, subjectInput, msgInput, btn) {
  if (isValidEmail(mailInput) && subjectInput.value.length > 0 && msgInput.value.length > 0)
    btn.classList.remove("disabled");
  else btn.classList.add("disabled");
}

function shareOnFacebook() {
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, "_blank");
}

function shareOnTwitter() {
  window.open(`https://twitter.com/intent/tweet?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(document.title)}`, "_blank");
}

function shareOnLinkedIn() {
  window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`, "_blank");
}

function shareOnWhatsApp() {
  window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(document.title)}%20${encodeURIComponent(window.location.href)}`, "_blank");
}

function setPopup(error, msg) {
  const popup = document.querySelector(".popup");
  const popup_msg = document.querySelector(".popup .msg");
  if (!popup || !popup_msg) return;

  popup.classList.add(error ? "error" : "success");
  popup.classList.remove(error ? "success" : "error");
  popup_msg.textContent = msg;

  if (popup.classList.contains("hidden")) popup.classList.remove("hidden");
}

function loadConfig() {
  return fetch(url_config).then((response) => {
    if (!response.ok) {
      setPopup(true, t("config_error"));
      throw new Error("HTTP " + response.status);
    }
    return response.json();
  });
}

function generateRandomString() {
  let result = "";
  for (let i = 0; i < characters.length; i++) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return result;
}

function getCurrentDomain() {
  const { protocol, hostname } = window.location;
  return `${protocol}//${hostname}`;
}

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : "";
}

function saveRandomString(url, name, email, randomString) {
  return fetch(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ email, name, randomString, action: "SaveString", csrf_token: getCsrfToken() }),
  }).then((r) => r.json());
}

function resetSignButton() {
  const btn = document.querySelector(".to-sign");
  if (!btn) return;
  btn.classList.remove("loading", "disabled");
  btn.textContent = t("sign_btn");
}

async function sign(name, email) {
  try {
    const config = await loadConfig();
    const randomString = generateRandomString();
    const result = await saveRandomString(config.urlBackend, name, email, randomString);

    if (!result.status) {
      setPopup(true, result.text);
      return;
    }

    const base = getCurrentDomain();
    const params = `name=${encodeURIComponent(name)}&email=${encodeURIComponent(email)}&randomString=${randomString}&lang=${currentLang}`;
    const validateUrl = `${base}/backend/save_signature.php?${params}&action=Sign`;
    const cancelUrl = `${base}/backend/save_signature.php?${params}&action=CancelSign`;

    const emailRes = await fetch(config.emailProxy, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        action: "send_user", name, email, validateUrl, cancelUrl, baseUrl: base,
        csrf_token: getCsrfToken(),
        lang: currentLang,
      }),
    });
    const emailResult = await emailRes.json();

    if (emailResult.status) {
      setPopup(false, t("sign_confirm_email", { email }));
      document.querySelector(".input-sign.mail").value = "";
      document.querySelector(".input-sign.name").value = "";
    } else {
      setPopup(true, t("sign_email_error"));
    }
  } catch {
    setPopup(true, t("sign_process_error"));
  }
}

function isValidEmail(emailInput) {
  return emailRegex.test(emailInput.value);
}

function validateInput($this) {
  if ($this.value.length > 0 && isValidEmail($this)) {
    $this.parentNode.classList.add("correct");
  } else {
    $this.parentNode.classList.remove("correct");
  }
}

function getData(url) {
  fetch(url)
    .then((response) => {
      if (!response.ok) {
        setPopup(true, t("internet_error"));
        throw new Error("HTTP " + response.status);
      }
      return response.json();
    })
    .then((data) => {
      const killedTotal = data.gaza.killed.total;
      const killedChildren = data.gaza.killed.children;
      document.querySelector(".extra-info .sect1 > h3").textContent = num(killedTotal);
      document.querySelector(".extra-info .sect2 > h3").textContent = num(killedChildren);
      const ld = document.getElementById("ld-casualties");
      if (ld) {
        try {
          const obj = JSON.parse(ld.textContent);
          if (obj.variableMeasured && obj.variableMeasured.length >= 2) {
            obj.variableMeasured[0].value = killedTotal;
            obj.variableMeasured[1].value = killedChildren;
            ld.textContent = JSON.stringify(obj);
          }
        } catch (e) {
          console.error("Error actualizando JSON-LD:", e);
        }
      }
    })
    .catch((error) => {
      console.error("Hubo un problema con la petición:", error);
    });
}
