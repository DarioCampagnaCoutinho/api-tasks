# API Tasks

API REST de gerenciamento de tarefas construída com **Laravel 13**, autenticação via **Laravel Sanctum** e controle de acesso com **Spatie Laravel Permission (ACL)**. Ambiente containerizado com **Docker** e banco de dados **PostgreSQL**.

---

## Stack

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 13 / PHP 8.3 |
| Autenticação | Laravel Sanctum (Bearer Token) |
| Autorização | Spatie Laravel Permission v6 (Roles + Permissions + Policies) |
| Banco de dados | PostgreSQL 16 |
| Servidor web | Nginx + PHP-FPM |
| Containerização | Docker + Docker Compose |

---

## Como funciona

### Autenticação

O cliente envia as credenciais (`email` + `password`) para o endpoint de login. A API retorna um **Bearer Token** gerado pelo Sanctum. Todas as rotas protegidas exigem esse token no header `Authorization`.

```
Authorization: Bearer <token>
```

O token é invalidado no logout. No login, todos os tokens anteriores do usuário são revogados e um novo é gerado.

---

### Sistema de Roles e Permissões (ACL)

```mermaid
graph LR
    subgraph admin
        a1["tasks.viewAny"]
        a2["tasks.view"]
        a3["tasks.create"]
        a4["tasks.update"]
        a5["tasks.delete"]
        a6["users.viewAny"]
        a7["users.view"]
        a8["users.update"]
        a9["users.delete"]
    end
    subgraph user
        b1["tasks.view"]
        b2["tasks.create"]
        b3["tasks.update"]
        b4["tasks.delete"]
    end
```

**Diferença de comportamento por role:**

| Ação | Admin | User |
|---|---|---|
| Listar tarefas | Vê **todas** as tarefas | Vê apenas **as suas** |
| Ver tarefa | Qualquer tarefa | Apenas as suas |
| Criar tarefa | ✅ | ✅ |
| Editar tarefa | Qualquer tarefa | Apenas as suas |
| Excluir tarefa | Qualquer tarefa | Apenas as suas |
| Gerenciar usuários | ✅ | ❌ (403) |

---

### Fluxo de Autenticação

```mermaid
sequenceDiagram
    Cliente->>API: POST /api/auth/login
    API->>PostgreSQL: verificar credenciais
    PostgreSQL-->>API: usuario encontrado
    API->>PostgreSQL: revogar tokens anteriores
    API->>PostgreSQL: criar novo token Sanctum
    API-->>Cliente: token Bearer
    Cliente->>API: GET /api/tasks com TOKEN
    API->>PostgreSQL: validar token
    PostgreSQL-->>API: usuario autenticado
    API->>API: executar TaskPolicy
    API-->>Cliente: lista de tarefas
```

---

### Fluxo de Autorização (Policy)

```mermaid
graph TD
    A[Request com Bearer Token] --> B{auth:sanctum}
    B -->|token invalido| C[401 Unauthorized]
    B -->|token valido| D[Controller]
    D --> E{Policy}
    E -->|admin| F[200 Acesso total ao recurso]
    E -->|user dono do recurso| G[200 Acesso permitido]
    E -->|user sem permissao| H[403 Forbidden]
```

---

### Arquitetura dos Containers

```mermaid
graph LR
    A[Postman] -->|HTTP 8000| B[Nginx]
    B -->|FastCGI 9000| C[PHP-FPM]
    C -->|pgsql 5432| D[(PostgreSQL)]
```

---

## Estrutura do Projeto

```
app/
├── Enums/
│   ├── TaskPriority.php     # low | medium | high
│   └── TaskStatus.php       # pending | in_progress | completed
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── TaskController.php
│   │   └── UserController.php
│   ├── Requests/
│   │   ├── Auth/            # LoginRequest, RegisterRequest
│   │   ├── Task/            # StoreTaskRequest, UpdateTaskRequest
│   │   └── User/            # UpdateUserRequest
│   └── Resources/
│       ├── TaskResource.php
│       └── UserResource.php
├── Models/
│   ├── Task.php
│   └── User.php
└── Policies/
    ├── TaskPolicy.php
    └── UserPolicy.php
database/
├── migrations/
└── seeders/
    ├── RoleAndPermissionSeeder.php
    └── UserSeeder.php
docker/
├── nginx/default.conf
└── php/docker-entrypoint.sh
apirest/
└── api-tasks.postman_collection.json
```

---

## Instalação e Execução

### Pré-requisitos

- Docker
- Docker Compose

### Subindo o ambiente

```bash
# Clone o projeto e entre na pasta
git clone <repo-url>
cd api-tasks

# Suba os containers (build + migrate + seed automático)
docker compose up -d --build
```

A API estará disponível em `http://localhost:8000`.

### Comandos úteis (Makefile)

```bash
make setup    # build + sobe containers
make up       # sobe containers existentes
make down     # para containers
make bash     # abre shell no container app
make logs     # exibe logs em tempo real
make fresh    # recria banco do zero (migrate:fresh --seed)
make tinker   # abre Laravel Tinker
make test     # executa os testes
```

---

## Usuários de Seed

| Email | Senha | Role |
|---|---|---|
| `admin@example.com` | `password` | admin |
| `user@example.com` | `password` | user |

---

## Endpoints

### Auth

| Método | Rota | Autenticação | Descrição |
|---|---|---|---|
| `POST` | `/api/auth/register` | ❌ | Registra novo usuário (role: `user`) |
| `POST` | `/api/auth/login` | ❌ | Autentica e retorna Bearer Token |
| `GET` | `/api/auth/me` | ✅ | Dados do usuário autenticado |
| `POST` | `/api/auth/logout` | ✅ | Revoga o token atual |

#### POST /api/auth/register

```json
// Request
{
  "name": "João Silva",
  "email": "joao@example.com",
  "password": "password",
  "password_confirmation": "password"
}

// Response 201
{
  "data": { "id": 3, "name": "João Silva", "email": "joao@example.com", "roles": ["user"] },
  "token": "1|abc123...",
  "message": "Usuário registrado com sucesso."
}
```

#### POST /api/auth/login

```json
// Request
{
  "email": "admin@example.com",
  "password": "password"
}

// Response 200
{
  "data": { "id": 1, "name": "Admin", "email": "admin@example.com", "roles": ["admin"] },
  "token": "2|xyz456...",
  "message": "Login realizado com sucesso."
}
```

---

### Tasks

Todas as rotas exigem `Authorization: Bearer <token>`.

| Método | Rota | Role | Descrição |
|---|---|---|---|
| `GET` | `/api/tasks` | admin / user | Lista tarefas (admin vê todas, user vê as suas) |
| `POST` | `/api/tasks` | admin / user | Cria uma tarefa |
| `GET` | `/api/tasks/{id}` | admin / user | Detalhe de uma tarefa |
| `PUT` | `/api/tasks/{id}` | admin / user | Atualiza uma tarefa |
| `DELETE` | `/api/tasks/{id}` | admin / user | Remove uma tarefa |

#### Filtros disponíveis em GET /api/tasks

| Parâmetro | Valores | Exemplo |
|---|---|---|
| `status` | `pending`, `in_progress`, `completed` | `?status=pending` |
| `priority` | `low`, `medium`, `high` | `?priority=high` |
| `per_page` | número inteiro | `?per_page=10` |

#### POST /api/tasks

```json
// Request
{
  "title": "Implementar OAuth2",
  "description": "Adicionar login social ao sistema",
  "status": "pending",
  "priority": "high",
  "due_date": "2026-12-31"
}

// Response 201
{
  "data": {
    "id": 1,
    "title": "Implementar OAuth2",
    "description": "Adicionar login social ao sistema",
    "status": "pending",
    "priority": "high",
    "due_date": "2026-12-31",
    "user": { "id": 1, "name": "Admin", "email": "admin@example.com" },
    "created_at": "2026-06-20T00:00:00.000000Z",
    "updated_at": "2026-06-20T00:00:00.000000Z"
  },
  "message": "Tarefa criada com sucesso."
}
```

#### Valores aceitos nos Enums

| Campo | Valores |
|---|---|
| `status` | `pending` · `in_progress` · `completed` |
| `priority` | `low` · `medium` · `high` |

---

### Users (somente Admin)

| Método | Rota | Descrição |
|---|---|---|
| `GET` | `/api/users` | Lista todos os usuários |
| `GET` | `/api/users/{id}` | Detalhe de um usuário |
| `PUT` | `/api/users/{id}` | Atualiza dados e/ou role |
| `DELETE` | `/api/users/{id}` | Remove um usuário |

#### PUT /api/users/{id} — Troca de role

```json
// Request
{
  "role": "admin"
}

// Response 200
{
  "data": { "id": 2, "name": "User", "email": "user@example.com", "roles": ["admin"] },
  "message": "Usuário atualizado com sucesso."
}
```

---

### Respostas de erro

| Código | Situação |
|---|---|
| `401` | Token ausente ou inválido |
| `403` | Sem permissão para o recurso |
| `404` | Recurso não encontrado |
| `422` | Erro de validação nos campos |

```json
// 422 Unprocessable Entity
{
  "message": "The title field is required.",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

---

## Importar no Postman / Insomnia

O arquivo de collection está em `apirest/api-tasks.postman_collection.json`.

- **Postman:** File → Import → selecione o arquivo
- **Insomnia:** Application → Preferences → Data → Import Data → From File

O token é preenchido automaticamente após Login ou Register.

---

## Licença

MIT
