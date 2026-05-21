document.addEventListener("DOMContentLoaded", async () => {
  const form = document.querySelector("[data-login-form]");
  const errorEl = document.querySelector("[data-login-error]");
  const submitBtn = document.querySelector("[data-login-submit]");

  const params = new URLSearchParams(window.location.search);
  const redirect = params.get("redirect") || "admin-produtos.php";

  function showError(message) {
    if (!errorEl) return;
    errorEl.textContent = message;
    errorEl.style.display = "block";
  }

  async function checkLogged() {
    try {
      const response = await fetch("api/auth/me.php", { credentials: "include" });
      if (response.ok) window.location.href = redirect;
    } catch {}
  }

  await checkLogged();

  form?.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (errorEl) errorEl.style.display = "none";

    const data = new FormData(form);
    const email = String(data.get("email") || "").trim();
    const senha = String(data.get("senha") || "");

    if (!email || !senha) {
      showError("Informe e-mail e senha.");
      return;
    }

    try {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = "Entrando...";
      }

      const response = await fetch("api/auth/login.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ email, senha })
      });

      const result = await response.json().catch(() => ({}));
      if (!response.ok || !result.success) {
        throw new Error(result.message || "Não foi possível entrar.");
      }

      window.location.href = redirect;
    } catch (error) {
      showError(error.message || "Erro ao fazer login.");
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Entrar no painel";
      }
    }
  });
});
