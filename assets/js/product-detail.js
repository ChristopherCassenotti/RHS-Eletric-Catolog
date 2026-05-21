document.addEventListener("DOMContentLoaded", async () => {
  const id = RHS.getParam("id") || "";
  const root = document.querySelector("[data-product-detail]");

  if (!root) return;

  function galleryHTML(product) {
    const images = Array.isArray(product.images) && product.images.length
      ? product.images
      : [product.image].filter(Boolean);

    if (!images.length) return RHS.productImage(product);

    return `
      <div class="rhs-product-gallery" style="display:grid;gap:12px;width:100%;">
        <div class="rhs-gallery-main" style="border-radius:24px;overflow:hidden;background:rgba(255,255,255,.06);">
          <img data-gallery-main src="${RHS.escapeHTML(images[0])}" alt="${RHS.escapeHTML(product.name)}" style="width:100%;height:460px;object-fit:cover;display:block;">
        </div>

        ${images.length > 1 ? `
          <div class="rhs-gallery-thumbs" style="display:grid;grid-template-columns:repeat(${Math.min(images.length, 5)},1fr);gap:10px;">
            ${images.map((src, index) => `
              <button type="button" data-gallery-thumb="${RHS.escapeHTML(src)}" style="border:1px solid rgba(255,255,255,.14);padding:0;border-radius:14px;overflow:hidden;background:rgba(255,255,255,.05);height:84px;">
                <img src="${RHS.escapeHTML(src)}" alt="Imagem ${index + 1} de ${RHS.escapeHTML(product.name)}" style="width:100%;height:100%;object-fit:cover;display:block;">
              </button>
            `).join("")}
          </div>
        ` : ""}
      </div>
    `;
  }

  function renderProduct(product) {
    const category = (typeof RHS_CATEGORIES !== "undefined" && RHS_CATEGORIES[product.category]?.label) || product.category;

    root.innerHTML = `
      <div class="detail-layout">
        <div class="detail-media>${galleryHTML(product)}</div>
        <aside class="detail-panel">
          <span class="badge">${RHS.escapeHTML(product.tag || category || "Produto")}</span>
          <h1>${RHS.escapeHTML(product.name)}</h1>
          <div>
            <span class="price">${RHS.formatPrice(product.price)}</span>
            ${product.oldPrice ? `<span class="old-price">${RHS.formatPrice(product.oldPrice)}</span>` : ""}
          </div>
          <p>${RHS.escapeHTML(product.description || "Produto cadastrado no banco de dados da RHS Electric.")}</p>
          <div class="spec-list">
            <div class="spec-item"><span>Categoria</span><strong>${RHS.escapeHTML(category || "-")}</strong></div>
            <div class="spec-item"><span>Autonomia</span><strong>${RHS.escapeHTML(product.autonomy || "-")}</strong></div>
            <div class="spec-item"><span>Velocidade</span><strong>${RHS.escapeHTML(product.speed || "-")}</strong></div>
            <div class="spec-item"><span>Potência</span><strong>${RHS.escapeHTML(product.power || "-")}</strong></div>
          </div>
          <a class="btn block" href="contato.html">Falar com especialista</a>
          <br><br>
          <a class="btn secondary block" href="produtos.html">Voltar para produtos</a>
        </aside>
      </div>
    `;

    root.querySelectorAll("[data-gallery-thumb]").forEach((button) => {
      button.addEventListener("click", () => {
        const main = root.querySelector("[data-gallery-main]");
        if (main) main.src = button.dataset.galleryThumb;
      });
    });
  }

  root.innerHTML = `<div class="empty-state">Carregando produto do banco de dados...</div>`;

  try {
    const product = await RHS.fetchProduct(id);
    renderProduct(product);
  } catch (error) {
    root.innerHTML = `
      <div class="empty-state">
        Não foi possível carregar o produto do banco de dados.<br>
        ${RHS.escapeHTML(error.message)}<br><br>
        <a class="btn" href="produtos.html">Voltar para produtos</a>
      </div>
    `;
  }
});
