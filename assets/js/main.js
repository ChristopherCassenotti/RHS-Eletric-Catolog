const RHS = (() => {
  const productKey = "RHS_PRODUCTS";
  const cartKey = "RHS_CART";
  let productsCache = [];

  async function requestJSON(url, options = {}) {
    const response = await fetch(url, {
      credentials: "include",
      cache: "no-store",
      ...options
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
      throw new Error(data.message || `Erro ao buscar dados no banco. Status ${response.status}.`);
    }
    return data;
  }

  async function fetchProducts(params = {}) {
    const query = new URLSearchParams();
    if (params.category && params.category !== "todos") query.set("category", params.category);

    const url = `api/products/list.php${query.toString() ? `?${query}` : ""}`;
    const data = await requestJSON(url);
    productsCache = data.products || [];
    localStorage.setItem(productKey, JSON.stringify(productsCache));
    return productsCache;
  }

  async function fetchProduct(id = "") {
    const query = id ? `?id=${encodeURIComponent(id)}` : "";
    const data = await requestJSON(`api/products/detail.php${query}`);
    return data.product;
  }

  function getProducts() {
    if (productsCache.length) return productsCache;
    try {
      const stored = JSON.parse(localStorage.getItem(productKey)) || [];
      if (stored.length) return stored;
    } catch {}
    if (typeof RHS_DEFAULT_PRODUCTS !== "undefined") return RHS_DEFAULT_PRODUCTS;
    return [];
  }

  function saveProducts(products) {
    productsCache = products || [];
    localStorage.setItem(productKey, JSON.stringify(productsCache));
  }

  function getCart() {
    try { return JSON.parse(localStorage.getItem(cartKey)) || []; }
    catch { return []; }
  }

  function saveCart(cart) {
    localStorage.setItem(cartKey, JSON.stringify(cart));
    updateCartCount();
  }

  function getParam(name) {
    return new URLSearchParams(window.location.search).get(name);
  }

  function formatPrice(value) {
    return Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
  }

  function productImage(product, extraClass = "") {
    if (product.image) {
      return `<img src="${escapeHTML(product.image)}" alt="${escapeHTML(product.name)}" loading="lazy" onerror="this.parentElement.innerHTML='<div class=&quot;product-fallback&quot;>${product.icon || "⚡"}</div>'">`;
    }
    return `<div class="product-fallback ${extraClass}">${product.icon || "⚡"}</div>`;
  }

  function renderProductCard(product) {
    const category = (typeof RHS_CATEGORIES !== "undefined" && RHS_CATEGORIES[product.category]?.label) || product.category || "Produto";
    return `
      <article class="card product-card">
        <a href="produto.html?id=${encodeURIComponent(product.id)}" class="product-image" aria-label="Ver ${escapeHTML(product.name)}">
          ${productImage(product)}
          <span class="badge product-badge">${escapeHTML(product.tag || category)}</span>
        </a>
        <div class="product-content">
          <h3><a href="produto.html?id=${encodeURIComponent(product.id)}">${escapeHTML(product.name)}</a></h3>
          <p>${escapeHTML(product.description || "Produto de mobilidade elétrica RHS Electric.")}</p>
          <div class="product-meta">
            <div><strong>${escapeHTML(product.autonomy || "-")}</strong><span>Autonomia</span></div>
            <div><strong>${escapeHTML(product.speed || "-")}</strong><span>Velocidade</span></div>
            <div><strong>${escapeHTML(product.power || "-")}</strong><span>Potência</span></div>
          </div>
          <div class="price-row">
            <div>
              <span class="price">${formatPrice(product.price)}</span>
              ${product.oldPrice ? `<span class="old-price">${formatPrice(product.oldPrice)}</span>` : ""}
            </div>
            <a class="btn small" href="produto.html?id=${encodeURIComponent(product.id)}">Detalhes</a>
          </div>
        </div>
      </article>
    `;
  }

  function addToCart(id, qty = 1) {
    const products = getProducts();
    const product = products.find(item => item.id === id);
    if (!product) return toast("Produto não encontrado.");
    const cart = getCart();
    const existing = cart.find(item => item.id === id);
    if (existing) existing.qty += Number(qty || 1);
    else cart.push({ id, qty: Number(qty || 1) });
    saveCart(cart);
    toast("Produto adicionado ao carrinho.");
  }

  function cartTotals() {
    const products = getProducts();
    const cart = getCart();
    const items = cart.map(item => {
      const product = products.find(p => p.id === item.id);
      return product ? { ...item, product, subtotal: product.price * item.qty } : null;
    }).filter(Boolean);
    const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
    const shipping = subtotal > 0 ? 0 : 0;
    return { items, subtotal, shipping, total: subtotal + shipping };
  }

  function updateCartCount() {
    const total = getCart().reduce((sum, item) => sum + Number(item.qty || 0), 0);
    document.querySelectorAll("[data-cart-count]").forEach(el => el.textContent = total);
  }

  function escapeHTML(value) {
    return String(value ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  let toastTimer;
  function toast(message) {
    let el = document.querySelector(".toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "toast";
      document.body.appendChild(el);
    }
    el.textContent = message;
    el.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove("show"), 2300);
  }

  function initGlobal() {
    updateCartCount();
    document.querySelectorAll("[data-year]").forEach(el => el.textContent = new Date().getFullYear());

    const current = location.pathname.split("/").pop() || "index.html";
    document.querySelectorAll(".menu a").forEach(link => {
      const href = link.getAttribute("href");
      if (href === current || (current === "" && href === "index.html")) link.classList.add("active");
    });

    const toggle = document.querySelector("[data-mobile-toggle]");
    if (toggle) {
      toggle.addEventListener("click", () => document.body.classList.toggle("menu-open"));
    }
  }

  document.addEventListener("DOMContentLoaded", initGlobal);

  return {
    requestJSON,
    fetchProducts,
    fetchProduct,
    getProducts,
    saveProducts,
    getCart,
    saveCart,
    getParam,
    formatPrice,
    renderProductCard,
    productImage,
    addToCart,
    cartTotals,
    updateCartCount,
    toast,
    escapeHTML
  };
})();
