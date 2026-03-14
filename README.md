# Bemobile Payment API
### Desafio Back-end BeTalent - Implementação Nível 2

Este projeto é um orquestrador de pagamentos de alta disponibilidade construído com **Laravel 12**. A API gerencia cobranças em múltiplos gateways com lógica de *failover*, RBAC e proteção contra duplicidade de transações.

---

## 🎯 Status da Implementação

Esta solução cobre todos os requisitos dos **Níveis 1 e 2**, além de grande parte do **Nível 3**:

* **Nível 1 & 2**:
    * Valor da compra calculado no back-end via produto e quantidades.
    * Gateways com autenticação completa (Bearer Token e Headers customizados).
* **Nível 3 (Avançado)**:
    * **Múltiplos Produtos**: Suporte a múltiplos produtos por transação via tabelas pivô.
    * **Gestão de Roles**: Permissões granulares para Admin, Manager, Finance e User.
    * **TDD (Test-Driven Development)**: Suíte completa de testes de funcionalidade.

---

## 🚀 Como Começar

### 1. Requisitos do Sistema
* **PHP 8.3+**
* **Composer**
* **MySQL 8.0+**
* **Docker** (Necessário para rodar os mocks dos gateways)

### 2. Instalação
```bash
# Clone o repositório
git clone <seu-url-do-repositorio>
cd <nome-da-pasta>

# Instale as dependências
composer install
```

### 3. Configurar Ambiente
```bash
cp .env.example .env
cp .env.testing.example .env.testing
```
### 4. Seeding e Gateway
```bash
php artisan migrate --seed
```
```bash
docker run -p 3001:3001 -p 3002:3002 matheusprotzen/gateways-mock
```

### 5. 👤 Usuários da Seed
| Email | Role | Nível de Acesso |
| :--- | :--- | :--- |
| **admin@admin.com** | `ADMIN` | Acesso total ao sistema. |
| **manager@manager.com** | `MANAGER` | Gerenciar produtos e usuários. |
| **finance@finance.com** | `FINANCE` | Gerenciar produtos e realizar reembolsos. |
| **user@user.com** | `USER` | Acesso a rotas públicas e gerais. |

### 6. Tabela de Rotas da API

| Método | URI | Role | Descrição |
| :--- | :--- | :--- | :--- |
| **POST** | `/api/login` | **Público** | Autenticação e geração de token Sanctum. |
| **POST** | `/api/buy` | **Público** | Ponto de entrada para compras multi-gateway. |
| **POST** | `/api/logout` | **Autenticado** | Revoga o token de acesso atual. |
| **GET** | `/api/products` | `admin, manager, finance` | Listagem de todos os produtos. |
| **POST** | `/api/products` | `admin, manager, finance` | Cadastro de um novo produto. |
| **GET** | `/api/products/{id}` | `admin, manager, finance` | Visualização detalhada de um produto. |
| **PUT/PATCH**| `/api/products/{id}` | `admin, manager, finance` | Atualização de dados do produto. |
| **DELETE** | `/api/products/{id}` | `admin, manager, finance` | Remoção (Soft Delete) de um produto. |
| **GET** | `/api/users` | `admin, manager` | Listagem de todos os usuários. |
| **POST** | `/api/users` | `admin, manager` | Criação de novos usuários do sistema. |
| **GET** | `/api/users/{id}` | `admin, manager` | Visualização detalhada de um usuário. |
| **PUT/PATCH**| `/api/users/{id}` | `admin, manager` | Atualização de perfil ou role de usuário. |
| **DELETE** | `/api/users/{id}` | `admin, manager` | Remoção de um usuário do sistema. |
| **POST** | `/api/transactions/{id}/refund` | `admin, finance` | Processa reembolso no gateway original. |
| **GET** | `/api/clients` | **Autenticado** | Listagem de clientes que realizaram compras. |
| **GET** | `/api/clients/{id}` | **Autenticado** | Detalhes do cliente e histórico de transações. |
| **GET** | `/api/transactions` | **Autenticado** | Listagem paginada de todas as vendas. |
| **GET** | `/api/transactions/{id}` | **Autenticado** | Detalhes de uma transação específica. |
| **GET** | `/api/gateways` | **Autenticado** | Lista status e prioridade dos gateways. |
| **PATCH** | `/api/gateways/{id}/change-status` | **Autenticado** | Ativa ou desativa um gateway específico. |
| **PATCH** | `/api/gateways/{id}/priority` | **Autenticado** | Altera a prioridade de execução dos gateways. |

## 🧪 Testes Realizados

### 📡 Gateways
* **list all gateways**: Garante a listagem correta dos gateways configurados.
* **can update gateway priority**: Valida a alteração da ordem de prioridade.
* **cannot set invalid priority**: Proteção contra valores de prioridade fora do esperado.
* **toggle gateway active status**: Testa a ativação e desativação dinâmica de gateways.

### 📋 Listings
* **can list transactions with relations**: Valida a listagem de transações.
* **can show transaction detail**: Detalhaes individuais de uma venda.
* **can list all clients**: Listagem de todos os clientes registrados.
* **can show client with purchase history**: Valida a visualização do cliente junto ao seu histórico de compras.

### 💳 Payment
* **calculate price on backend**: Garante que o cálculo de `quantidade * valor` ocorra no servidor.
* **transaction is assigned to existing client by email**: Valida a vinculação correta ao encontrar um e-mail já cadastrado.
* **transaction uses existing client even with different name**: Ignorar nome e pegar nome atrelado ao email.
* **gateway fallback mechanism**: Valida a tentativa no segundo gateway após falha no primeiro.
* **products table pivot record creation**: Valida a persistência na tabela `transaction_products`.
* **find a product by normalized name**: Busca de produto via ID ou Nome.
* **return 502 if all gateways fail**: Tratamento de erro quando nenhum serviço está disponível.
* **luhn validation on card number**: Validação local do algoritmo de Luhn para números de cartão.
* **it rejects invalid cvv formats**: Validação de formato (3-4 dígitos) para CVV.
* **idempotency prevents duplicate transactions**: Proteção contra double-charge (cobrança duplicada).

### 📦 Products
* **authorized roles can manage products**: Valida acesso de Admin, Manager e Finance.
* **can update product**: Atualização de dados cadastrais.
* **unauthorized roles cannot manage products**: Proteção de rotas contra acesso indevido.

### 🔄 Refund
* **manager cannot refund transaction**: Garante que o papel de Manager não tenha acesso financeiro.
* **cannot refund already refunded transaction**: Impede reembolsos duplicados.
* **finance can refund completed transaction**: Permite o reembolso para role finance.
* **cannot refund incomplete transaction**: Impede o reembolso de vendas incompletas  eu com erro.

### 🛡️ RBAC
* **login req valid email format**: Validação de entrada no login.
* **protected routes need auth**: Garante que rotas privadas exijam o token Sanctum.
* **role check is case insensitive**: Só pra confirmar que roles em lowecase são válidas também.
* **user with insufficient role gets 403**: Validação do middleware de permissões.

### 👤 User CRUD
* **finance cannot access user crud**: Restrição do Finance sobre a gestão de usuários.
* **role assignment is case insensitive**: Flexibilidade na case de atribuição de cargos.
* **authorized roles can manage users**: Roles autorizadas conseguem usar os recursos da API.
* **cannot create user with invalid role**: Validação de integridade nas Roles.

## Postman/Insomnia
- O arquivo API Testing Collection.json pode ser importado ao Postman para ajudar com os testes. As variáveis já estão prontas, só testar e ser feliz :D
