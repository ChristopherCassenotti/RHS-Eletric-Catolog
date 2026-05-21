console.log("ADMIN API NOVO CARREGADO - 20260511-7 HOME DINAMICA + DESTAQUE");
document.addEventListener("DOMContentLoaded", async () => {
  const form = document.querySelector("[data-admin-form]");
  const list = document.querySelector("[data-admin-products]");
  const formTitle = document.querySelector("[data-form-title]");
  const cancelEditBtn = document.querySelector("[data-cancel-edit]");
  const resetBtn = document.querySelector("[data-reset-products]");
  const submitBtn = document.querySelector("[data-submit-btn]");
  const imageInput = document.querySelector("[data-image-input]");
  const imagePreview = document.querySelector("[data-image-preview]");

  let currentImages = [];
  let selectedFiles = [];
  let products = [];
  let csrfToken = "";

  if (resetBtn) resetBtn.style.display = "none";

  function getCategoryLabel(category) {
    const labels = {
      scooters: "Scooters elétricas",
      "e-bikes": "E-bikes",
      triciclos: "Triciclos elétricos",
      patinetes: "Patinetes elétricos",
      acessorios: "Acessórios"
    };

    return labels[category] || category || "Sem categoria";
  }

  function formatPrice(value) {
    return Number(value || 0).toLocaleString("pt-BR", {
      style: "currency",
      currency: "BRL"
    });
  }

  function escapeHTML(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  async function apiJSON(url, options = {}) {
    const response = await fetch(url, {
      credentials: "include",
      ...options,
      headers: {
        ...(options.headers || {}),
        ...(csrfToken ? { "X-CSRF-Token": csrfToken } : {})
      }
    });

    const data = await response.json().catch(() => ({}));

    if (response.status === 401) {
      window.location.href = "admin-login.html?redirect=admin-produtos.php";
      return Promise.reject(new Error("Sessão expirada."));
    }

    if (!response.ok || data.success === false) {
      throw new Error(data.message || "Erro na requisição.");
    }

    return data;
  }

  async function ensureLogged() {
    const response = await fetch("api/auth/me.php", { credentials: "include" });
    const data = await response.json().catch(() => ({}));

    if (!response.ok || !data.logged) {
      window.location.href = "admin-login.html?redirect=admin-produtos.php";
      return false;
    }

    csrfToken = data.csrfToken || "";
    addLogoutButton(data.user);
    return true;
  }

  function addLogoutButton(user) {
    const navActions = document.querySelector(".nav-actions");
    if (!navActions || document.querySelector("[data-admin-logout]")) return;

    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "top-whatsapp-btn";
    btn.dataset.adminLogout = "1";
    btn.style.border = "0";
    btn.style.cursor = "pointer";
    btn.innerHTML = `<i class="fas fa-sign-out-alt"></i> Sair`;

    btn.addEventListener("click", async () => {
      try {
        await apiJSON("api/auth/logout.php", { method: "POST" });
      } finally {
        window.location.href = "admin-login.html";
      }
    });

    navActions.insertBefore(btn, navActions.firstChild);
  }

  function updateRequiredBadges() {
    const requiredFields = document.querySelectorAll(".required-field");

    requiredFields.forEach((field) => {
      const input = field.querySelector("input[required], select[required], textarea[required]");
      if (!input) return;

      const value = String(input.value || "").trim();
      field.classList.toggle("is-filled", !!value);
    });
  }

  function initRequiredBadges() {
    const requiredInputs = document.querySelectorAll(
      ".required-field input[required], .required-field select[required], .required-field textarea[required]"
    );

    requiredInputs.forEach((input) => {
      input.addEventListener("input", updateRequiredBadges);
      input.addEventListener("change", updateRequiredBadges);
    });

    updateRequiredBadges();
  }

  function renderImagePreview(images = [], options = {}) {
    if (!options.keepCurrent) {
      currentImages = images;
    }

    if (!imagePreview) return;

    const fileUrls = selectedFiles.map((file) => ({
      src: URL.createObjectURL(file),
      label: file.name
    }));

    const existingUrls = (options.showSelectedOnly ? [] : currentImages).map((src) => ({
      src,
      label: "Imagem atual"
    }));

    const all = [...existingUrls, ...fileUrls];

    if (!all.length) {
      imagePreview.innerHTML = "";
      return;
    }

    imagePreview.innerHTML = `
      <div style="grid-column: 1 / -1; color: var(--muted); font-weight: 700; margin-bottom: 8px;">
        ${selectedFiles.length ? `${selectedFiles.length} nova(s) imagem(ns) selecionada(s).` : `${currentImages.length} imagem(ns) atual(is).`}
      </div>
      ${all.map((item, index) => `
        <div style="position: relative;">
          <img src="${escapeHTML(item.src)}" alt="${escapeHTML(item.label)}">
          ${index >= existingUrls.length ? `<button type="button" data-remove-selected-image="${index - existingUrls.length}" style="position:absolute;top:6px;right:6px;border:0;border-radius:999px;background:#ff5069;color:#fff;width:28px;height:28px;font-weight:900;">×</button>` : ""}
        </div>
      `).join("")}
    `;
  }

  function addSelectedFiles(files) {
    const incoming = Array.from(files || []);

    incoming.forEach((file) => {
      if (!file || !file.type.startsWith("image/")) return;

      const alreadyExists = selectedFiles.some((current) =>
        current.name === file.name &&
        current.size === file.size &&
        current.lastModified === file.lastModified
      );

      if (!alreadyExists) selectedFiles.push(file);
    });

    renderImagePreview([], { keepCurrent: true });
  }

  function renderProducts() {
    if (!list) return;

    if (!products.length) {
      list.innerHTML = `<div class="admin-empty">Nenhum produto cadastrado.</div>`;
      return;
    }

    list.innerHTML = products.map((product) => {
      const mainImage = product.image || product.images?.[0];

      return `
        <article class="admin-product-v2">
          <div class="admin-product-thumb">
            ${
              mainImage
                ? `<img src="${escapeHTML(mainImage)}" alt="${escapeHTML(product.name)}">`
                : `<span>${escapeHTML(product.icon || "⚡")}</span>`
            }
          </div>

          <div class="admin-product-info">
            <h3>${escapeHTML(product.name)}</h3>
            <p>${formatPrice(product.price)} • ${escapeHTML(getCategoryLabel(product.category))}</p>
            <small>${product.images?.length || 0} imagem(ns) cadastrada(s)${product.featured ? " • Produto em destaque na home" : ""}</small>
          </div>

          <div class="admin-product-actions">
            <button class="admin-edit-btn" type="button" data-edit="${escapeHTML(product.id)}">
              Editar
            </button>

            <button class="admin-delete-btn" type="button" data-delete="${escapeHTML(product.id)}">
              Excluir
            </button>
          </div>
        </article>
      `;
    }).join("");
  }

  async function loadProducts() {
    if (list) list.innerHTML = `<div class="admin-empty">Carregando produtos...</div>`;
    const data = await apiJSON("api/products/list.php?admin=1");
    products = data.products || [];
    renderProducts();
  }

  if (imageInput) {
    imageInput.addEventListener("change", () => {
      addSelectedFiles(imageInput.files || []);
      // Limpa o input para permitir selecionar outra imagem depois sem substituir a lista acumulada.
      imageInput.value = "";
      console.log("Imagens acumuladas para envio:", selectedFiles.length, selectedFiles.map((f) => f.name));
    });
  }

  if (imagePreview) {
    imagePreview.addEventListener("click", (event) => {
      const btn = event.target.closest("[data-remove-selected-image]");
      if (!btn) return;

      const index = Number(btn.dataset.removeSelectedImage);
      selectedFiles.splice(index, 1);
      renderImagePreview([], { keepCurrent: true });
    });
  }

  if (form) {
    form.addEventListener("submit", async (event) => {
      event.preventDefault();

      if (!form.reportValidity()) return;

      const editingId = String(form.elements.id.value || "").trim();
      const endpoint = editingId ? "api/products/update.php" : "api/products/create.php";

      try {
        submitBtn.classList.add("is-loading");
        submitBtn.textContent = editingId ? "Atualizando..." : "Cadastrando...";

        const data = new FormData(form);

        // IMPORTANTÍSSIMO:
        // Não enviamos múltiplas imagens dentro do create/update.
        // Alguns ambientes PHP/Hostinger acabam recebendo só 1 arquivo no multipart.
        // Por isso criamos/atualizamos o produto primeiro e depois enviamos UMA imagem por requisição.
        data.delete("images");
        data.delete("images[]");
        data.delete("image");
        data.delete("file");

        const filesToUpload = [...selectedFiles];
        const selectedCount = filesToUpload.length;

        console.log("Produto será salvo primeiro. Imagens serão enviadas individualmente:", selectedCount, filesToUpload.map((f) => f.name));

        const result = await apiJSON(endpoint, {
          method: "POST",
          body: data
        });

        const productDbId = result.product?.dbId || result.product?.databaseId || result.dbId || result.product_id;
        let uploadedCount = 0;

        if (selectedCount && !productDbId) {
          throw new Error("Produto salvo, mas a API não retornou o ID interno para anexar as imagens.");
        }

        for (let i = 0; i < filesToUpload.length; i++) {
          const file = filesToUpload[i];
          submitBtn.textContent = `Enviando imagem ${i + 1}/${filesToUpload.length}...`;

          const imageData = new FormData();
          imageData.append("product_id", productDbId);
          imageData.append("image", file, file.name);

          const uploadResult = await apiJSON("api/products/upload-image.php", {
            method: "POST",
            body: imageData
          });

          uploadedCount += Number(uploadResult.uploaded_count || 0);
        }

        form.reset();
        form.elements.id.value = "";
        selectedFiles = [];
        renderImagePreview([]);

        if (formTitle) formTitle.textContent = "Novo produto";
        if (cancelEditBtn) cancelEditBtn.style.display = "none";

        await loadProducts();
        alert(`${editingId ? "Produto atualizado" : "Produto cadastrado"} com sucesso. Imagens selecionadas: ${selectedCount}. Imagens salvas: ${uploadedCount}.`);
      } catch (error) {
        alert(error.message || "Erro ao salvar produto.");
      } finally {
        submitBtn.classList.remove("is-loading");
        submitBtn.textContent = "Cadastrar produto";
      }
    });
  }

  if (list) {
    list.addEventListener("click", async (event) => {
      const editBtn = event.target.closest("[data-edit]");
      const deleteBtn = event.target.closest("[data-delete]");

      if (editBtn) {
        const id = editBtn.dataset.edit;
        const product = products.find((item) => item.id === id);

        if (!product || !form) return;

        form.elements.id.value = product.id;
        form.elements.name.value = product.name || "";
        form.elements.category.value = product.category || "";
        form.elements.price.value = product.price || "";
        form.elements.oldPrice.value = product.oldPrice || "";
        form.elements.type.value = product.type || "";
        form.elements.tag.value = product.tag || "";
        form.elements.tagColor.value = product.tagColor || "red";
        form.elements.status.value = product.status || "";
        if (form.elements.featured) form.elements.featured.checked = !!product.featured;
        form.elements.icon.value = product.icon || "⚡";
        form.elements.autonomy.value = product.autonomy || "";
        form.elements.speed.value = product.speed || "";
        form.elements.power.value = product.power || "";
        form.elements.description.value = product.description || "";

        selectedFiles = [];
        renderImagePreview(product.images || [product.image].filter(Boolean));
        updateRequiredBadges();

        if (formTitle) formTitle.textContent = "Editar produto";
        if (cancelEditBtn) cancelEditBtn.style.display = "inline-flex";

        window.scrollTo({ top: 0, behavior: "smooth" });
      }

      if (deleteBtn) {
        const id = deleteBtn.dataset.delete;
        const confirmed = confirm("Deseja excluir este produto? Essa ação também remove as imagens da Cloudinary quando possível.");
        if (!confirmed) return;

        try {
          await apiJSON("api/products/delete.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id })
          });
          await loadProducts();
        } catch (error) {
          alert(error.message || "Erro ao excluir produto.");
        }
      }
    });
  }

  if (cancelEditBtn) {
    cancelEditBtn.addEventListener("click", () => {
      form.reset();
      form.elements.id.value = "";
      selectedFiles = [];
      renderImagePreview([]);
      updateRequiredBadges();

      if (formTitle) formTitle.textContent = "Novo produto";
      cancelEditBtn.style.display = "none";
    });
  }

  const logged = await ensureLogged();
  if (!logged) return;

  initRequiredBadges();
  await loadProducts();
});
