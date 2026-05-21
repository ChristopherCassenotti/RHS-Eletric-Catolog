document.addEventListener("DOMContentLoaded", async () => {
  const grid = document.querySelector("[data-products-grid]");
  const countEl = document.querySelector("[data-products-count]");
  const orderSelect = document.querySelector("[data-order]");
  const filterButtons = document.querySelectorAll("[data-category-filter]");

  const categoryTitle = document.querySelector("[data-category-title]");
  const categoryDescription = document.querySelector("[data-category-description]");
  const categoryBreadcrumb = document.querySelector("[data-category-breadcrumb]");

  const params = new URLSearchParams(window.location.search);
  const pageCategory = params.get("cat");

  const categoryInfo = {
    scooters: {
      title: "Scooters",
      breadcrumb: "Scooters",
      description: "Scooters elétricas para quem busca economia, conforto e praticidade no dia a dia."
    },
    "e-bikes": {
      title: "Bicicletas",
      breadcrumb: "E-bikes",
      description: "Bicicletas elétricas para mobilidade urbana, lazer e deslocamentos inteligentes."
    },
    triciclos: {
      title: "Triciclos",
      breadcrumb: "Triciclos",
      description: "Triciclos elétricos ideais para trabalho, carga e deslocamentos com mais estabilidade."
    },
    patinetes: {
      title: "Patinetes",
      breadcrumb: "Patinetes",
      description: "Patinetes elétricos práticos, compactos e perfeitos para trajetos urbanos."
    },
    acessorios: {
      title: "Acessórios",
      breadcrumb: "Acessórios",
      description: "Acessórios para completar sua experiência com mobilidade elétrica."
    }
  };

  let products = [];
  let activeCategory = pageCategory || "todos";

  function updateCategoryHeader() {
    if (!pageCategory) return;

    const info = categoryInfo[pageCategory] || {
      title: "Categoria",
      breadcrumb: "Categoria",
      description: "Veja os modelos disponíveis nessa categoria e escolha o produto ideal para sua rotina."
    };

    if (categoryTitle) categoryTitle.textContent = info.title;
    if (categoryBreadcrumb) categoryBreadcrumb.textContent = info.breadcrumb;
    if (categoryDescription) categoryDescription.textContent = info.description;
  }

  function renderProducts() {
    let filteredProducts = [...products];

    if (activeCategory !== "todos") {
      filteredProducts = filteredProducts.filter((product) => product.category === activeCategory);
    }

    const order = orderSelect?.value || "featured";

    filteredProducts.sort((a, b) => {
      if (order === "price-asc") return Number(a.price || 0) - Number(b.price || 0);
      if (order === "price-desc") return Number(b.price || 0) - Number(a.price || 0);
      if (order === "name") return String(a.name || "").localeCompare(String(b.name || ""));
      return 0;
    });

    if (countEl) {
      countEl.textContent = `${filteredProducts.length} produto${filteredProducts.length !== 1 ? "s" : ""}`;
    }

    if (!grid) return;

    if (!filteredProducts.length) {
      grid.innerHTML = `
        <div class="empty-state">
          Nenhum produto encontrado nessa categoria.
          <br><br>
          <a class="category-outline-btn" href="produtos.html">Ver todos os produtos</a>
        </div>
      `;
      return;
    }

    grid.innerHTML = filteredProducts.map((product) => `
      <article class="product-page-card">
        ${product.tag ? `<span class="product-page-badge ${RHS.escapeHTML(product.tagColor || "red")}">${RHS.escapeHTML(product.tag)}</span>` : ""}

        ${product.status ? `
          <span class="product-page-badge gray">
            ${RHS.escapeHTML(product.status)}
          </span>
        ` : ""}

        <div class="product-page-image">
          ${product.image ? `<img src="${RHS.escapeHTML(product.image)}" alt="${RHS.escapeHTML(product.name)}">` : `<div class="product-fallback">${RHS.escapeHTML(product.icon || "⚡")}</div>`}
        </div>

        <div class="product-page-content">
          <span>${RHS.escapeHTML(product.type || "PRODUTO ELÉTRICO")}</span>

          <h3>${RHS.escapeHTML(product.name)}</h3>

          <div class="product-page-specs">
            <p><i class="fas fa-bolt"></i> ${RHS.escapeHTML(product.speed || "-")}</p>
            <p><i class="fas fa-battery-full"></i> ${RHS.escapeHTML(product.autonomy || "-")}</p>
          </div>

          <strong class="product-page-price">${RHS.formatPrice(product.price)}</strong>
          ${product.oldPrice ? `<small>De ${RHS.formatPrice(product.oldPrice)}</small>` : ""}

          <a href="produto.html?id=${encodeURIComponent(product.id)}">Ver detalhes</a>
        </div>
      </article>
    `).join("");
  }

  async function loadProducts() {
    if (grid) grid.innerHTML = `<div class="empty-state">Carregando produtos...</div>`;
    try {
      products = await RHS.fetchProducts();
      updateCategoryHeader();
      renderProducts();
    } catch (error) {
      if (grid) grid.innerHTML = `<div class="empty-state">Erro ao carregar produtos.<br>${RHS.escapeHTML(error.message)}</div>`;
    }
  }

  filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      filterButtons.forEach((item) => item.classList.remove("active"));
      button.classList.add("active");

      activeCategory = button.dataset.categoryFilter || "todos";
      renderProducts();
    });
  });

  orderSelect?.addEventListener("change", renderProducts);
  await loadProducts();
});
