document.addEventListener("DOMContentLoaded", () => {
  const form = document.querySelector("[data-contact-form]");

  if (!form) return;

  form.addEventListener("submit", (event) => {
    event.preventDefault();

    const data = new FormData(form);

    const nome = data.get("nome") || "";
    const email = data.get("email") || "";
    const assunto = data.get("assunto") || "";
    const mensagem = data.get("mensagem") || "";

    const texto = `
Olá, vim pelo site da RHS Electric.

Nome: ${nome}
E-mail: ${email}
Assunto: ${assunto}

Mensagem:
${mensagem}
    `.trim();

    const numeroWhatsApp = "5542999999999";
    const url = `https://wa.me/${numeroWhatsApp}?text=${encodeURIComponent(texto)}`;

    window.open(url, "_blank");
  });
});