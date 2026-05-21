<?php require_once __DIR__ . '/api/auth/require-auth-page.php'; ?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Admin Produtos | RHS Electric</title>
    <meta
      name="description"
      content="Administração de produtos RHS Electric."
    />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />

    <link
      rel="stylesheet"
      href="//use.fontawesome.com/releases/v5.0.7/css/all.css"
    />
    <link rel="stylesheet" href="assets/css/styles.css?v=2" />
  </head>

  <body>
    <header class="topbar">
      <div class="container nav">
        <a class="brand" href="index.html" aria-label="RHS Electric Home">
          <img src="logo.png" alt="" width="200px" />
        </a>

        <nav class="menu" aria-label="Menu principal">
          <a href="index.html">Início</a>
          <a href="produtos.html">Produtos</a>
          <a href="sobre.html">Sobre</a>
          <a href="assistencia.html">Assistência</a>
          <a href="faq.html">FAQ</a>
          <a href="contato.html">Contato</a>
        </nav>

        <div class="nav-actions">
          <a
            class="top-whatsapp-btn"
            href="https://wa.me/5542999999999"
            target="_blank"
            style="display: flex; gap: 5px"
          >
            <i class="fa-brands fa-whatsapp"></i>
            WhatsApp
          </a>

          <button
            class="icon-btn mobile-toggle"
            data-mobile-toggle
            aria-label="Abrir menu"
          >
            ☰
          </button>
        </div>
      </div>
    </header>

    <main class="admin-page">
      <section class="admin-hero">
        <div class="container">
          <span>PAINEL ADMIN</span>
          <h1>Gerenciar <strong>produtos</strong></h1>
          <p>Cadastre, edite e organize os produtos da RHS Electric.</p>
        </div>
      </section>

      <section class="admin-products-section">
        <div class="container admin-layout-v2">
          <form class="admin-form-v2" data-admin-form>
            <div class="admin-form-head">
              <div>
                <span>CADASTRO</span>
                <h2 data-form-title>Novo produto</h2>
              </div>

              <button
                class="admin-clear-btn"
                type="button"
                data-cancel-edit
                style="display: none"
              >
                Cancelar edição
              </button>
            </div>

            <input type="hidden" name="id" />

            <label class="admin-field required-field">
              <span>Nome <em class="required-badge">Preencher</em></span>
              <input name="name" required placeholder="Nome do produto" />
            </label>

            <label class="admin-field required-field">
              <span>Categoria <em class="required-badge">Preencher</em></span>
              <select name="category" required>
                <option value="">Selecione</option>
                <option value="scooters">Scooters</option>
                <option value="e-bikes">E-bikes</option>
                <option value="triciclos">Triciclos</option>
                <option value="patinetes">Patinetes</option>
                <option value="acessorios">Acessórios</option>
              </select>
            </label>

            <div class="admin-form-grid">
              <label class="admin-field required-field">
                <span>Preço <em class="required-badge">Preencher</em></span>
                <input
                  name="price"
                  type="number"
                  required
                  placeholder="12990"
                />
              </label>

              <label class="admin-field">
                <span>Preço antigo</span>
                <input name="oldPrice" type="number" placeholder="14990" />
              </label>

              <label class="admin-field">
                <span>Tipo</span>
                <input name="type" placeholder="SCOOTER ELÉTRICA" />
              </label>

              <label class="admin-field">
                <span>Tag</span>
                <input name="tag" placeholder="Mais vendido" />
              </label>

              <label class="admin-field">
                <span>Cor da tag</span>
                <select name="tagColor">
                  <option value="red">Vermelho</option>
                  <option value="gray">Cinza escuro</option>
                  <option value="light">Cinza claro</option>
                  <option value="gray">Cinza</option>
                </select>
              </label>

              <label class="admin-field">
                <span>Status</span>
                <select name="status">
                  <option value="">Disponível</option>
                  <option value="Indisponível">Indisponível</option>
                </select>
              </label>

              <label class="admin-field" style="align-content: end;">
                <span>Destaque na home</span>
                <label style="display:flex;align-items:center;gap:10px;width:max-content;color:#fff;font-weight:800;">
                  <input name="featured" type="checkbox" value="1" style="width:18px;height:18px;" />
                  Exibir em destaque
                </label>
              </label>

              <label class="admin-field">
                <span>Autonomia</span>
                <input name="autonomy" placeholder="75 km" />
              </label>

              <label class="admin-field">
                <span>Velocidade</span>
                <input name="speed" placeholder="50 km/h" />
              </label>

              <label class="admin-field">
                <span>Potência</span>
                <input name="power" placeholder="3000W" />
              </label>

              <label class="admin-field">
                <span>Ícone</span>
                <input name="icon" value="⚡" placeholder="🛵" />
              </label>
            </div>

            <label class="admin-field admin-field-full">
              <span>Descrição</span>
              <textarea
                name="description"
                placeholder="Descrição comercial do produto"
              ></textarea>
            </label>

            <label class="admin-field admin-field-full">
              <span>Imagens do produto</span>
              <input
                name="images[]"
                type="file"
                accept="image/*"
                multiple
                data-image-input
              />
              <small
                >Você pode selecionar várias imagens de uma vez ou adicionar aos poucos. Elas serão enviadas
                para o Cloudinary.</small
              >
            </label>

            <div class="admin-image-preview" data-image-preview></div>

            <button class="admin-submit-btn" type="submit" data-submit-btn>
              Cadastrar produto
            </button>

            <button class="admin-reset-btn" type="button" data-reset-products>
              Restaurar padrão
            </button>
          </form>

          <div class="admin-list-area">
            <div class="admin-list-head">
              <div>
                <span>LISTA</span>
                <h2>Produtos cadastrados</h2>
                <p>
                  Produtos salvos no banco de dados e exibidos no site.
                </p>
              </div>
            </div>

            <div class="admin-products-v2" data-admin-products></div>
          </div>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="container footer-grid">
        <div>
          <a class="brand" href="index.html">
            <img src="logo.png" alt="" width="200px" />
          </a>
          <p>
            Mobilidade elétrica com tecnologia, economia e atendimento
            especializado.
          </p>
        </div>

        <div>
          <h4>Loja</h4>
          <a href="produtos.html">Produtos</a>
          <a href="categoria.html?cat=scooters">Scooters</a>
          <a href="categoria.html?cat=e-bikes">E-bikes</a>
          <a href="categoria.html?cat=triciclos">Triciclos</a>
        </div>

        <div>
          <h4>Suporte</h4>
          <a href="assistencia.html">Assistência técnica</a>
          <a href="faq.html">Perguntas frequentes</a>
          <a href="contato.html">Fale conosco</a>
        </div>

        <div>
          <h4>Contato</h4>
          <p>WhatsApp: (00) 00000-0000</p>
          <p>Atendimento: Seg. a Sex. das 8h às 18h</p>
          <p>© <span data-year></span> RHS Electric</p>
        </div>
      </div>
    </footer>

    <div class="toast" aria-live="polite"></div>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/admin.js?v=20260511-7-home-dynamic"></script>
  </body>
</html>
