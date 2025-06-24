# Sistema de Lista de Ramais

Este é um sistema web para gerenciamento de ramais telefônicos, desenvolvido com PHP, HTML5, CSS, JavaScript e Docker.

## Funcionalidades Principais

- **Visualização Pública:**
  - Listagem de ramais organizada por setores em formato "Drop Down" (acordeão).
  - Busca "live" por nome de pessoa ou número do ramal.
  - Botão para limpar a busca e resetar a listagem.
- **Área de Administrador (Acesso Protegido):**
  - **Perfil Admin:**
    - Gerenciar Pessoas: Incluir, editar, excluir.
    - Gerenciar Atribuição de Ramais: Atribuir ramais vagos a pessoas, alterar/desatribuir ramal de uma pessoa.
  - **Perfil Super-Admin:**
    - Todas as funcionalidades do Admin.
    - Gerenciar Setores: Adicionar, editar, excluir (com validação de ramais associados).
    - Gerenciar Ramais (Completo): Adicionar novo ramal (número, tipo, setor), editar todos os detalhes, excluir ramal.
    - Listagem completa de ramais com filtros (por tipo, status) e busca.
    - Gerenciar Usuários: Adicionar, editar (username, perfil, senha), excluir usuários (Admin/Super-Admin), com proteção para o último Super-Admin.

## Tecnologias Utilizadas

- **Backend:** PHP (PDO para acesso ao banco de dados)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla JS para interatividade e AJAX)
- **Banco de Dados:** MySQL (configurado via Docker)
- **Servidor Web:** Apache (configurado via Docker com PHP-FPM)
- **Containerização:** Docker, Docker Compose

## Estrutura do Projeto

```
.
├── Dockerfile              # Define a imagem Docker para a aplicação PHP/Apache
├── docker-compose.yml      # Orquestra os serviços da aplicação e do banco de dados
├── db/
│   └── init.sql            # Script SQL para criação do schema e dados iniciais
├── public/                 # Raiz do servidor web
│   ├── admin/              # Scripts e interface da área administrativa
│   │   ├── actions/        # Scripts PHP para processar ações do admin (AJAX)
│   │   └── ...             # Páginas PHP do admin (login.php, index.php, manage_*.php)
│   ├── css/                # Arquivos CSS (style.css, admin_style.css, etc.)
│   ├── js/                 # Arquivos JavaScript (main.js, admin_main.js)
│   └── index.php           # Página inicial pública
├── src/                    # Código PHP backend e includes
│   ├── ajax/               # Scripts PHP para requisições AJAX da parte pública
│   ├── includes/           # Arquivos PHP reutilizáveis (db.php, session_auth.php, headers, footers)
│   └── utils/              # Scripts utilitários (ex: generate_password_hash.php)
└── README.md               # Este arquivo
```

## Configuração e Setup (Usando Docker)

1.  **Pré-requisitos:**
    *   Docker instalado ([https://www.docker.com/get-started](https://www.docker.com/get-started))
    *   Docker Compose instalado (geralmente vem com o Docker Desktop)

2.  **Clonar o Repositório (se aplicável):**
    ```bash
    git clone <url-do-repositorio>
    cd <nome-da-pasta-do-projeto>
    ```

3.  **Construir e Iniciar os Containers:**
    Na raiz do projeto (onde o `docker-compose.yml` está localizado), execute:
    ```bash
    docker-compose up -d --build
    ```
    - O comando `--build` força a reconstrução da imagem da aplicação se houverem alterações no `Dockerfile` ou no código fonte copiado para a imagem.
    - O `-d` executa os containers em modo "detached" (background).

4.  **Acessar a Aplicação:**
    *   **Lista de Ramais (Pública):** `http://localhost:83`
        *   *(Nota: A porta `83` no host foi configurada no `docker-compose.yml`. Se diferente, ajuste a URL.)*
    *   **Área Administrativa:** `http://localhost:83/admin/`

5.  **Usuário Administrador Padrão:**
    *   **Usuário:** `admin`
    *   **Senha:** `admin_password`
    *   Este usuário é um Super-Admin. É altamente recomendável alterar esta senha após o primeiro login através da interface de gerenciamento de usuários (se implementada a alteração de senha própria) ou diretamente no banco de dados com um hash gerado. O script `src/utils/generate_password_hash.php` pode ser usado para gerar um novo hash (execute-o dentro do container ou em um ambiente PHP compatível).

6.  **Parar os Containers:**
    ```bash
    docker-compose down
    ```

7.  **Acesso ao Banco de Dados (Opcional):**
    *   O MySQL está rodando no container `db` e não expõe porta para o host por padrão no `docker-compose.yml` atual.
    *   Para conectar diretamente (e.g., com um cliente MySQL), você pode:
        1.  Expor a porta no `docker-compose.yml` (ex: `ports: - "33061:3306"` para o serviço `db`).
        2.  Conectar-se de dentro do container `app`: `docker-compose exec app mysql -u user -p'password' phone_extensions`

## Estrutura do Banco de Dados (Resumo)

-   **`sectors`**: Armazena os setores da empresa.
    -   `id` (PK), `name`
-   **`persons`**: Armazena informações das pessoas.
    -   `id` (PK), `name`
-   **`extensions`**: Armazena os ramais telefônicos.
    -   `id` (PK), `number` (número do ramal), `type` (ENUM: 'Interno', 'Externo'), `sector_id` (FK para `sectors`), `person_id` (FK para `persons`, pode ser NULL), `status` (ENUM: 'Vago', 'Atribuído')
-   **`users`**: Armazena os usuários do sistema administrativo.
    -   `id` (PK), `username`, `password_hash`, `profile` (ENUM: 'Admin', 'Super-Admin')

## Possíveis Melhorias Futuras

-   Interface para Super-Admin alterar a própria senha ou de outros usuários de forma mais direta.
-   Logs de auditoria para ações administrativas.
-   Testes automatizados (unitários, integração).
-   Funcionalidade de importação/exportação de ramais/pessoas.
-   Melhorias na interface do usuário (UI/UX), talvez com um framework CSS/JS mais robusto.
-   Paginação para listas longas na área administrativa.
-   Realocação de ramais ao excluir um setor (atualmente impede a exclusão).
