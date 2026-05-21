console.log("HOME DINAMICA CARREGADA - 20260521 CONTADORES DINAMICOS");

(function injectHomeLayoutFix() {
  if (document.getElementById("rhs-home-layout-fix")) return;

  const style = document.createElement("style");
  style.id = "rhs-home-layout-fix";
  style.textContent = '';
  document.head.appendChild(style);
})();

document.addEventListener("DOMContentLoaded", async () => {
  const categoriesGrid = document.querySelector("[data-home-categories]");
  const featuredGrid = document.querySelector("[data-home-featured-products]");

  const categories = [
    { key: "scooters", label: "Scooters Elétricas", img: "assets/img/categoria-scooter.png", alt: "Scooters elétricas", icon: "🛵" },
    { key: "e-bikes", label: "Bicicletas Elétricas", img: "assets/img/categoria-bike.png", alt: "Bicicletas elétricas", icon: "🚲" },
    { key: "triciclos", label: "Triciclos Elétricos", img: "assets/img/categoria-triciclo.png", alt: "Triciclos elétricos", icon: "🛺" },
    { key: "patinetes", label: "Patinetes Elétricos", img: "assets/img/categoria-patinete.png", alt: "Patinetes elétricos", icon: "🛴" },
    { key: "acessorios", label: "Acessórios", img: "assets/img/categoria-acessorios.png", alt: "Acessórios", icon: "⚡" }
  ];

  const escapeHTML = (value) => {
    if (window.RHS?.escapeHTML) return RHS.escapeHTML(value);
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  };

  const formatPrice = (value) => {
    if (window.RHS?.formatPrice) return RHS.formatPrice(value);
    return Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
  };

  function categoryLabel(key) {
    const category = categories.find((item) => item.key === key);
    return category?.label || key || "Produto";
  }

  function categoryIcon(key) {
    const category = categories.find((item) => item.key === key);
    return category?.icon || "⚡";
  }

  function normalizeCategoryKey(value) {
    const raw = String(value || "")
      .toLowerCase()
      .trim()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/_/g, "-")
      .replace(/\s+/g, "-")
      .replace(/[^a-z0-9-]/g, "-")
      .replace(/-+/g, "-")
      .replace(/^-|-$/g, "");

    const map = {
      "scooter": "scooters",
      "scooters": "scooters",
      "scooter-eletrica": "scooters",
      "scooters-eletricas": "scooters",

      "e-bike": "e-bikes",
      "e-bikes": "e-bikes",
      "ebike": "e-bikes",
      "ebikes": "e-bikes",
      "bike": "e-bikes",
      "bikes": "e-bikes",
      "bicicleta": "e-bikes",
      "bicicletas": "e-bikes",
      "bicicleta-eletrica": "e-bikes",
      "bicicletas-eletricas": "e-bikes",

      "triciclo": "triciclos",
      "triciclos": "triciclos",
      "triciclo-eletrico": "triciclos",
      "triciclos-eletricos": "triciclos",

      "patinete": "patinetes",
      "patinetes": "patinetes",
      "patinete-eletrico": "patinetes",
      "patinetes-eletricos": "patinetes",

      "acessorio": "acessorios",
      "acessorios": "acessorios",
    };

    return map[raw] || raw;
  }

  function normalizeCounts(counts = {}) {
    const normalized = {};

    Object.entries(counts || {}).forEach(([key, value]) => {
      const categoryKey = normalizeCategoryKey(key);
      if (!categoryKey) return;
      normalized[categoryKey] = (normalized[categoryKey] || 0) + Number(value || 0);
    });

    return normalized;
  }

  function pluralModel(count) {
    return `${count} ${count === 1 ? "modelo" : "modelos"}`;
  }

  function categoryKeyFromCard(card) {
    const href = card.getAttribute("href") || "";
    try {
      const url = new URL(href, window.location.href);
      return normalizeCategoryKey(url.searchParams.get("cat"));
    } catch (error) {
      return "";
    }
  }

  function updateStaticCategoryCounters(counts = {}) {
    document.querySelectorAll(".home-category-card").forEach((card) => {
      const key = categoryKeyFromCard(card);
      if (!key) return;

      const count = Number(counts[key] || 0);
      const counter = card.querySelector("p");

      if (counter) {
        counter.textContent = pluralModel(count);
      }
    });
  }

  function installmentText(price) {
    const numericPrice = Number(price || 0);
    if (!numericPrice) return "Consulte condições";
    return `ou 12x de ${formatPrice(numericPrice / 12)}`;
  }

  function optimizeCloudinaryImage(url, width = 700, height = 760) {
    const value = String(url || "");
    if (!value.includes("res.cloudinary.com") || !value.includes("/image/upload/")) return value;
    if (value.includes("/f_auto") || value.includes("/c_fill")) return value;
    return value.replace("/image/upload/", `/image/upload/f_auto,q_auto,c_fill,g_auto,w_${width},h_${height}/`);
  }

  function renderImageFallback(icon = "⚡") {
    return `<div class="home-image-fallback">${escapeHTML(icon)}</div>`;
  }

  function renderCategories(counts = {}) {
    const normalizedCounts = normalizeCounts(counts);

    // Se o HTML já tiver os cards fixos, atualiza apenas os números.
    // Assim não recria as imagens nem quebra o layout.
    if (!categoriesGrid) {
      updateStaticCategoryCounters(normalizedCounts);
      return;
    }

    categoriesGrid.innerHTML = categories.map((category) => {
      const count = Number(normalizedCounts[category.key] || 0);

      return `
        <a class="home-category-card" href="categoria.html?cat=${encodeURIComponent(category.key)}">
          <div class="home-category-img">
            <img src="${escapeHTML(category.img)}" alt="${escapeHTML(category.alt)}" loading="lazy" />
          </div>
          <h3>${escapeHTML(category.label)}</h3>
          <p>${pluralModel(count)}</p>
        </a>
      `;
    }).join("");
  }

  function renderFeatured(products = []) {
    if (!featuredGrid) return;

    const featured = products.slice(0, 4);

    if (!featured.length) {
      featuredGrid.innerHTML = `
        <div class="home-product-card" style="grid-column: 1 / -1; min-height: 180px; display:flex; align-items:center; justify-content:center; text-align:center; color:#8e9298;">
          Nenhum produto marcado como destaque no momento.
        </div>
      `;
      return;
    }

    featuredGrid.innerHTML = featured.map((product) => {
      const rawImage = product.image || product.images?.[0] || "";
      const image = optimizeCloudinaryImage(rawImage);
      const tag = product.tag || "Destaque";
      const tagColor = product.tagColor || "red";
      const icon = product.icon || categoryIcon(product.category);
      const type = product.type || categoryLabel(product.category).toUpperCase();
      const detailUrl = `produto.html?id=${encodeURIComponent(product.id)}`;

      return `
        <article class="home-product-card">
          ${tag ? `<div class="home-product-badge ${escapeHTML(tagColor)}">${escapeHTML(tag)}</div>` : ""}

          <div class="home-product-img">
            <a href="${detailUrl}" aria-label="Ver ${escapeHTML(product.name)}">
              ${image
                ? `<img src="${escapeHTML(image)}" alt="${escapeHTML(product.name)}" loading="lazy"  />`
                : renderImageFallback(icon)
              }
            </a>
          </div>

          <div class="home-product-content">
            <span>${escapeHTML(type)}</span>
            <h3>${escapeHTML(product.name)}</h3>

            <div class="home-product-specs">
              <p><i class="fas fa-bolt"></i> ${escapeHTML(product.speed || "-")}</p>
              <p><i class="fas fa-battery-full"></i> ${escapeHTML(product.autonomy || "-")}</p>
            </div>

            <strong class="home-product-price">${formatPrice(product.price)}</strong>
            <small>${installmentText(product.price)}</small>

            <a href="${detailUrl}">Ver detalhes</a>
          </div>
        </article>
      `;
    }).join("");
  }

  async function loadHomeData() {
    try {
      const response = await fetch("api/products/summary.php", {
        credentials: "include",
        cache: "no-store"
      });
      const data = await response.json().catch(() => ({}));

      if (!response.ok || data.success === false) {
        throw new Error(data.message || "Erro ao carregar dados da home.");
      }

      renderCategories(data.counts || {});
      renderFeatured(data.featured || []);
    } catch (error) {
      console.error("Erro na home dinâmica:", error);
      renderCategories({});
      if (featuredGrid) {
        featuredGrid.innerHTML = `
          <div class="home-product-card" style="grid-column: 1 / -1; min-height: 180px; display:flex; align-items:center; justify-content:center; text-align:center; color:#8e9298;">
            Não foi possível carregar os destaques agora.
          </div>
        `;
      }
    }
  }

  await loadHomeData();
});
