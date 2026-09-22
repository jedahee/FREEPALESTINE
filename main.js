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

// Adjunta un listener solo si el elemento existe (las páginas interiores
// como /la-voz-palestina no tienen todos los elementos de la portada).
function on(el, evt, fn, opts) {
  if (el) el.addEventListener(evt, fn, opts);
}

// Escapa texto para que nunca se interprete como HTML (previene XSS).
function esc(s) {
  return String(s)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

// Convierte un subconjunto seguro de Markdown a HTML. Solo emite etiquetas
// de una lista blanca: el resto del contenido siempre va escapado.
function inlineMd(src) {
  return esc(src)
    .replace(/`([^`]+)`/g, "<code>$1</code>")
    .replace(/\*\*([^*]+)\*\*/g, "<strong>$1</strong>")
    .replace(/\*([^*]+)\*/g, "<em>$1</em>")
    .replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, '<a href="$2" rel="noopener noreferrer" target="_blank">$1</a>');
}

function mdToHtml(md) {
  const lines = String(md || "").split("\n");
  const out = [];
  const n = lines.length;
  for (let i = 0; i < n; i++) {
    const line = lines[i];
    const h = line.match(/^(#{1,3})\s+(.*)$/);
    if (h) {
      const level = h[1].length;
      out.push("<h" + level + ">" + inlineMd(h[2]) + "</h" + level + ">");
      continue;
    }
    const q = line.match(/^>\s?(.*)$/);
    if (q) {
      out.push("<blockquote>" + inlineMd(q[1]) + "</blockquote>");
      continue;
    }
    const ul = line.match(/^[-*+]\s+(.*)$/);
    const ol = ul ? null : line.match(/^\d+[.)]\s+(.*)$/);
    if (ul || ol) {
      const tag = ul ? "ul" : "ol";
      const items = [ul ? ul[1] : ol[1]];
      while (i + 1 < n) {
        const next = lines[i + 1];
        const nu = /^[-*+]\s+(.*)$/.test(next);
        const no = !nu && /^\d+[.)]\s+(.*)$/.test(next);
        if (ul ? !nu : !no) break;
        i++;
        const m = lines[i].match(/^[-*+]\s+(.*)$/) || lines[i].match(/^\d+[.)]\s+(.*)$/);
        items.push(m[1]);
      }
      out.push("<" + tag + ">" + items.map((it) => "<li>" + inlineMd(it) + "</li>").join("") + "</" + tag + ">");
      continue;
    }
    if (line.trim() === "") continue;
    out.push("<p>" + inlineMd(line) + "</p>");
  }
  return out.join("\n");
}

// Inicializa el editor Markdown del formulario de opiniones (toolbar + vista previa).
function initMdEditor() {
  const ta = document.getElementById("op-msg");
  if (!ta) return;
  const root = ta.closest(".md-editor");
  if (!root) return;
  const preview = root.querySelector(".md-preview");

  const wrap = (before, after) => {
    const start = ta.selectionStart;
    const end = ta.selectionEnd;
    const val = ta.value;
    ta.value = val.slice(0, start) + before + val.slice(start, end) + after + val.slice(end);
    const caret = start + before.length;
    ta.focus();
    ta.setSelectionRange(caret, caret + (end - start));
    ta.dispatchEvent(new Event("keyup", { bubbles: true }));
  };

  root.querySelectorAll("[data-md]").forEach((btn) => {
    on(btn, "click", () => {
      const sel = ta.value.slice(ta.selectionStart, ta.selectionEnd) || "texto";
      switch (btn.dataset.md) {
        case "h1": wrap("\n# ", "\n"); break;
        case "h2": wrap("\n## ", "\n"); break;
        case "h3": wrap("\n### ", "\n"); break;
        case "bold": wrap("**", "**"); break;
        case "italic": wrap("*", "*"); break;
        case "code": wrap("`", "`"); break;
        case "quote": wrap("\n> ", "\n"); break;
        case "ul": wrap("\n- ", "\n"); break;
        case "ol": wrap("\n1. ", "\n"); break;
        case "link":
          wrap("[", "](https://ejemplo.com)"); break;
      }
    });
  });

  const render = () => {
    if (!preview) return;
    const html = mdToHtml(ta.value);
    if (ta.value.trim() === "") {
      preview.hidden = true;
    } else {
      preview.hidden = false;
      preview.innerHTML = html;
    }
  };

  on(ta, "input", render);
  render();
}

document.addEventListener("DOMContentLoaded", function () {
  const loader = document.querySelector(".loader-container");
  const notification = document.querySelector(".notification");
  const social_networks_container = document.querySelector(".share__networks-container.hidden");
  const input_sign_mail = document.querySelector(".input-sign.mail");
  const input_contact_mail = document.querySelector(".input-sign.mail-contact");
  const input_op_name = document.querySelector(".input-sign.op-name");
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

  on(to_sign, "click", function () {
    if (!social_networks_container.classList.contains("hidden"))
      social_networks_container.classList.add("hidden");
  });

  on(icon_close, "click", function () {
    document.querySelector(".popup")?.classList.add("hidden");
  });

  on(btn_close, "click", function () {
    if (notification) notification.classList.add("hidden");
  });

  on(social_networks, "click", function (e) {
    e.preventDefault();
    social_networks_container.classList.toggle("hidden");
  });

  on(input_sign_mail, "keyup", function () {
    validateInput(this);
    toggleSignButton(input_sign_mail, input_sign_name, to_sign);
  });

  on(input_sign_name, "keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
    toggleSignButton(input_sign_mail, input_sign_name, to_sign);
  });

  on(input_op_name, "keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
  });

  on(input_contact_mail, "keyup", function () {
    validateInput(this);
    toggleOpinionButton(this, input_contact_subject, textarea_contact_msg, docInput, send_email);
  });

  on(input_contact_subject, "keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
    toggleOpinionButton(input_contact_mail, this, textarea_contact_msg, docInput, send_email);
  });

  on(textarea_contact_msg, "keyup", function () {
    if (this.value.length > 0) this.parentNode.classList.add("correct");
    else this.parentNode.classList.remove("correct");
    toggleOpinionButton(input_contact_mail, input_contact_subject, this, docInput, send_email);
  });

  const docInput = document.getElementById("op-doc");
  on(docInput, "change", function () {
    toggleOpinionButton(input_contact_mail, input_contact_subject, textarea_contact_msg, docInput, send_email);
  });

  on(to_sign, "click", async function () {
    const name = input_sign_name.value;
    const email = input_sign_mail.value;
    to_sign.classList.add("loading", "disabled");
    to_sign.textContent = t("sending");
    loader.classList.remove("hidden");

    await sign(name, email);

    resetSignButton();
    loader.classList.add("hidden");
  });

  on(send_email, "click", async function () {
    const subject = input_contact_subject ? input_contact_subject.value : "";
    const email = input_contact_mail ? input_contact_mail.value : "";
    const msg = textarea_contact_msg ? textarea_contact_msg.value : "";
    const docInput = document.getElementById("op-doc");
    const imageFileInput = document.getElementById("op-image-file");
    const MAX_FILE_MB = 5;

    for (const fileInput of [docInput, imageFileInput]) {
      const file = fileInput?.files?.[0];
      if (file && file.size > MAX_FILE_MB * 1024 * 1024) {
        setPopup(true, t("file_too_big"));
        return;
      }
    }

    loader.classList.remove("hidden");

    try {
      const config = await loadConfig();
      const fd = new FormData();
      fd.append("action", "send_notification");
      fd.append("name", input_op_name ? input_op_name.value : "");
      fd.append("email", email);
      fd.append("subject", subject);
      fd.append("msg", msg);
      fd.append("image", document.getElementById("op-image")?.value || "");
      fd.append("author_url", document.getElementById("op-author-url")?.value || "");
      fd.append("website", document.getElementById("op-website")?.value || "");
      fd.append("csrf_token", getCsrfToken());
      fd.append("lang", currentLang);
      if (docInput?.files?.[0]) fd.append("doc_file", docInput.files[0]);
      if (imageFileInput?.files?.[0]) fd.append("image_file", imageFileInput.files[0]);

      const res = await fetch(config.emailProxy, {
        method: "POST",
        body: fd,
      });
      const result = await res.json();

      if (result.status) {
        setPopup(false, t("msg_ok"));
        if (input_op_name) input_op_name.value = "";
        document.querySelector(".input-sign.op-name")?.parentNode.classList.remove("correct");
        document.querySelector(".input-sign.mail-contact").value = "";
        document.querySelector(".input-sign.subject").value = "";
        document.querySelector(".msg textarea").value = "";
        const opImageInput = document.getElementById("op-image");
        if (opImageInput) opImageInput.value = "";
        const opAuthorUrl = document.getElementById("op-author-url");
        if (opAuthorUrl) opAuthorUrl.value = "";
        if (docInput) docInput.value = "";
        if (imageFileInput) imageFileInput.value = "";
      } else {
        setPopup(true, result.text || t("msg_fail"));
      }
    } catch {
      setPopup(true, t("msg_fail"));
    }

    loader.classList.add("hidden");
  });

  // Slider de organizaciones que apoyan y colaboran (scroll-snap + flechas)
  const sliderTrack = document.querySelector("[data-slider-track]");
  if (sliderTrack) {
    const sliderPrev = document.querySelector("[data-slider-prev]");
    const sliderNext = document.querySelector("[data-slider-next]");

    const sliderStep = () => {
      const card = sliderTrack.querySelector(".support-card");
      if (!card) return sliderTrack.clientWidth;
      const gap = parseFloat(getComputedStyle(sliderTrack).gap) || 0;
      return card.offsetWidth + gap;
    };

    const updateSliderArrows = () => {
      if (!sliderPrev || !sliderNext) return;
      const maxScroll = sliderTrack.scrollWidth - sliderTrack.clientWidth;
      sliderPrev.classList.toggle("is-disabled", sliderTrack.scrollLeft <= 4);
      sliderNext.classList.toggle("is-disabled", sliderTrack.scrollLeft >= maxScroll - 4);
    };

    sliderPrev && sliderPrev.addEventListener("click", () => sliderTrack.scrollBy({ left: -sliderStep(), behavior: "smooth" }));
    sliderNext && sliderNext.addEventListener("click", () => sliderTrack.scrollBy({ left: sliderStep(), behavior: "smooth" }));
    sliderTrack.addEventListener("scroll", updateSliderArrows, { passive: true });
    window.addEventListener("resize", updateSliderArrows);
    updateSliderArrows();
  }

  initMdEditor();
  getData(api_endpoint);

  document.querySelectorAll(".goal-deliverable").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const type = this.dataset.deliverable;
      if (type === "recursos") {
        const target = document.getElementById("recursos");
        if (target) target.scrollIntoView({ behavior: "smooth" });
      }
    });
  });

  // Navegación interna con ancla (ej. /#share_opinion, /#eventos): al cargar
  // una página que llega con un `#hash`, desplazamos suavemente hasta la sección.
  if (window.location.hash) {
    const target = document.getElementById(window.location.hash.slice(1));
    if (target) {
      window.setTimeout(function () {
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 100);
    }
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

function toggleOpinionButton(mailInput, subjectInput, msgInput, docInput, btn) {
  const hasDoc = docInput && docInput.files && docInput.files.length > 0;
  const hasText = isValidEmail(mailInput) && subjectInput.value.length > 0 && msgInput.value.length > 0;
  if (hasDoc || hasText)
    btn.classList.remove("disabled");
  else btn.classList.add("disabled");
}

function shareOnFacebook() {
  window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(window.location.href)}`, "_blank");
}

function shareOnX() {
  window.open(`https://x.com/intent/post?url=${encodeURIComponent(window.location.href)}&text=${encodeURIComponent(document.title)}`, "_blank");
}

function shareOnLinkedIn() {
  window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(window.location.href)}`, "_blank");
}

function shareOnWhatsApp() {
  window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(document.title)}%20${encodeURIComponent(window.location.href)}`, "_blank");
}

// Copia el enlace de la entrada (opinion detail) al portapapeles y
// muestra una confirmación "Enlace copiado" sobre el botón durante 2s.
function copyOpinionLink(btn) {
  if (!btn) return;
  const nameEl = btn.querySelector(".opinion-share__name");
  const originalText = nameEl ? nameEl.textContent : "";
  const originalAria = btn.getAttribute("aria-label") || originalText;
  const url = btn.dataset.url || window.location.href;

  const flash = (ok) => {
    if (!ok || !nameEl) return;
    const copied = btn.dataset.copied || originalText;
    nameEl.textContent = copied;
    btn.setAttribute("aria-label", copied);
    btn.classList.add("is-copied");
    clearTimeout(btn._copyTimer);
    btn._copyTimer = setTimeout(function () {
      nameEl.textContent = originalText;
      btn.setAttribute("aria-label", originalAria);
      btn.classList.remove("is-copied");
    }, 2000);
  };

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(url)
      .then(() => flash(true))
      .catch(() => flash(copyOpinionLinkFallback(url)));
  } else {
    flash(copyOpinionLinkFallback(url));
  }
}

// Respaldo para navegadores sin Clipboard API.
function copyOpinionLinkFallback(url) {
  const ta = document.createElement("textarea");
  ta.value = url;
  ta.setAttribute("readonly", "");
  ta.style.position = "fixed";
  ta.style.top = "-9999px";
  document.body.appendChild(ta);
  ta.select();
  let copied = false;
  try {
    copied = document.execCommand("copy");
  } catch (e) {
    copied = false;
  }
  document.body.removeChild(ta);
  return copied;
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
      const s1 = document.querySelector(".extra-info .sect1 > h3");
      const s2 = document.querySelector(".extra-info .sect2 > h3");
      if (s1) s1.textContent = num(killedTotal);
      if (s2) s2.textContent = num(killedChildren);
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
