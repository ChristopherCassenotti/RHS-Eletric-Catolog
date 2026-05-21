# RHS Electric - Sistema de Catálogo e Administração

Sistema web desenvolvido para catálogo de veículos elétricos, com painel administrativo para cadastro de produtos, imagens e categorias.

## Funcionalidades

- Catálogo de produtos
- Categorias dinâmicas
- Página de detalhes do produto
- Painel administrativo
- Login de administrador
- Upload de imagens via Cloudinary
- Integração com WhatsApp
- Backend em PHP com MySQL

## Tecnologias

- HTML
- CSS
- JavaScript
- PHP
- MySQL
- Cloudinary

## Configuração

Copie:

api/config/env.example.php

para:

api/config/env.php

e preencha com suas credenciais locais.

## Banco de dados

O arquivo `database/schema.sql` contém apenas a estrutura das tabelas.
Não há dados reais, credenciais, usuários ou senhas neste repositório.

## Segurança

- O arquivo `api/config/env.php` não deve ser enviado ao GitHub.
- Use `api/config/env.example.php` apenas como modelo.
- Em produção, apague `api/auth/create-first-admin.php` depois de criar o primeiro administrador.
- O arquivo `database/schema.sql` contém apenas a estrutura do banco, sem dados reais.
