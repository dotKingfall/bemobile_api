# Bemobile Payment API
### Desafio Backend BeTalent - Implementação Nível 2

Este projeto é um orquestrador de pagamentos de alta disponibilidade construído com **Laravel 12**. A API gerencia cobranças em múltiplos gateways com lógica de *failover*, RBAC e proteção contra duplicidade de transações.

*Mesmo com alguns extras, o foco foi a implementação de nível 2 :)

---

## 🎯 Status da Implementação

Esta solução cobre todos os requisitos dos **Níveis 1 e 2** e parte do **Nível 3**:

* **Nível 1 e 2**:
    * Valor da compra calculado no backend por quantity * amount
    * Gateways com autenticação completa
* **Nível 3**:
    * **Gestão de Roles**: Gateways tem autenticação
    * **TDD**: Também com collection no Postman

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
git clone https://github.com/dotKingfall/bemobile_api.git
cd bemobile_api

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
# Gerar chave e preparar banco
php artisan key:generate
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
| **POST** | `/api/login` | **Público** | Login e token Sanctum |
| **POST** | `/api/buy` | **Público** | Ponto de entrada para compras multi-gateway. |
| **POST** | `/api/logout` | **Autenticado** | Faz logff :D |
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

### 7. Rodando os Testes
```bash
# Garante que a suíte de testes passe
php artisan test
```

### 📡 Gateways
* **list all gateways**: Verifica a lista de gateways configurados
* **can update gateway priority**: Testa se muda a prioridade corretamente
* **cannot set invalid priority**: Bloqueia prioridades inválidas
* **toggle gateway active status**: Testa o toggle dos gateways

### 📋 Listings
* **can list transactions with relations**: Testa a lista de transações
* **can show transaction detail**: Detalhaes individuais da venda.
* **can list all clients**: Listagem de todos os clientes registrados.
* **can show client with purchase history**: Verifica se mostra o cliente com seu histórico de compras

### 💳 Payment
* **calculate price on backend**: Garante que o cálculo de `quantity * amount` ocorra no servidor.
* **transaction is assigned to existing client by email**: Testa se vincula certo ao achar um e-mail já cadastrado
* **transaction uses existing client even with different name**: Ignorar nome e pegar nome atrelado ao email.
* **gateway fallback mechanism**: Testa se tenta o segundo gateway caso o primeiro falhe
* **products table pivot record creation**: Valida a persistência na tabela `transaction_products`.
* **find a product by normalized name**: Busca de produto por ID ou Nome.
* **return 502 if all gateways fail**: Tratamento de erro quando nenhum serviço está disponível.
* **luhn validation on card number**: Validação local do algoritmo de Luhn para números de cartão.
* **it rejects invalid cvv formats**: Validação de formato (3-4 dígitos) para CVV.
* **idempotency prevents duplicate transactions**: Proteção contra double-charge.

### 📦 Products
* **authorized roles can manage products**: Valida acesso de Admin, Manager e Finance.
* **can update product**: Testa update do produto.
* **unauthorized roles cannot manage products**: Proteção de rotas contra acesso indevido.

### 🔄 Refund
* **manager cannot refund transaction**: Garante que manager não tenha acesso a refunds.
* **cannot refund already refunded transaction**: Impede reembolsos duplicados.
* **finance can refund completed transaction**: Permite o reembolso para role finance.
* **cannot refund incomplete transaction**: Impede o reembolso de vendas incompletas ou com erro.

### 🛡️ RBAC
* **login req valid email format**: Validação de entrada no login.
* **protected routes need auth**: Garante que rotas privadas exijam o token Sanctum.
* **role check is case insensitive**: Só pra confirmar que roles em lowecase são válidas também.
* **user with insufficient role gets 403**: Testa o bloqueio por falta de permissão.

### 👤 User CRUD
* **finance cannot access user crud**: Bloqueia role finance de gerenciar usuários
* **role assignment is case insensitive**: Flexibilidade na case de atribuição de cargos.
* **authorized roles can manage users**: Roles autorizadas conseguem usar os recursos da API.
* **cannot create user with invalid role**: Validação de integridade nas Roles.

## Postman/Insomnia
- O arquivo API Testing Collection.json pode ser importado ao Postman para ajudar com os testes. As variáveis já estão prontas, só testar e ser feliz :D
