# Coffee Lab API

## Sobre o projeto

API REST para cadastro e consulta de cafés especiais. O endpoint inicial implementado é `GET /health`.

**Formação:** FullCycle — Geração Tech
**Nível planejado:** Projeto B — API de domínio único

## Tecnologias

- PHP 8.4
- Nginx
- PHP-FPM
- PostgreSQL
- Docker Compose
- Composer com autoload PSR-4 (`CoffeeLab\\`)
- PHPUnit

## Estado

O ambiente Docker, `GET /health`, os endpoints de cadastro e consulta de cafés e a persistência PostgreSQL estão implementados. A migration precisa ser aplicada ao banco antes de usar as rotas de cafés.

## API de cafés

- `POST /coffees`: cadastra um café e retorna HTTP 201 com os dados do café.
- `GET /coffees`: retorna HTTP 200 e a lista disponível para a requisição.
- `GET /coffees/{id}`: retorna HTTP 200 quando encontra o café, HTTP 404 quando não existe ou HTTP 400 para UUID inválido.

JSON malformado retorna HTTP 400 e dados de cadastro inválidos retornam HTTP 422. As respostas de erro não incluem mensagens internas.

### Persistência

As rotas HTTP usam `PdoCoffeeRepository` e persistem os cafés no PostgreSQL. `InMemoryCoffeeRepository` permanece disponível para testes unitários, sem compartilhar dados entre instâncias. `GET /health` não abre conexão com o banco, portanto sua resposta de saúde da aplicação não depende da disponibilidade do PostgreSQL.

A aplicação lê `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` e `DB_PASSWORD`. O Compose mapeia esses valores a partir de `POSTGRES_DB`, `POSTGRES_USER` e `POSTGRES_PASSWORD` do `.env`; host e porta internos são `database:5432`. Não crie nem versione um `.env` com credenciais reais no repositório.

A migration versionada cria a tabela e suas restrições, sem `DROP`, `TRUNCATE` ou extensão PostgreSQL adicional. Ela deve ser aplicada uma única vez a cada banco; não é idempotente e uma segunda aplicação falhará se a tabela já existir. Depois de iniciar o serviço PostgreSQL, aplique-a ao banco da aplicação:

```powershell
Get-Content .\database\migrations\202611140001_create_coffees.sql -Raw |
    docker compose exec -T database sh -c 'psql -v ON_ERROR_STOP=1 -U "$POSTGRES_USER" -d "$POSTGRES_DB"'
```

O nome e as variáveis de usuário são obtidos do ambiente do serviço `database`. Se a migration falhar, `ON_ERROR_STOP` faz o comando retornar erro.

#### Testes de integração do repositório PDO

Os testes PDO são deliberadamente separados do Compose normal. A configuração [compose.integration.yaml](./compose.integration.yaml) declara somente um PostgreSQL descartável e um runner PHPUnit: não inicia a API nem o banco normal, não publica portas e usa uma rede Docker interna isolada, sem rota para os demais serviços. O banco usa `tmpfs`, recebe a migration e cria o usuário restrito apenas no bootstrap; seus dados desaparecem ao remover o contêiner.

Para habilitar o ambiente, copie o exemplo para um arquivo local protegido, substitua o placeholder por uma senha de teste aleatória e não versione nem compartilhe `.env.integration`:

```powershell
Copy-Item .env.integration.example .env.integration
```

`.env.integration` é ignorado pelo Git e pelo contexto Docker. Contém um usuário administrativo e um usuário de teste, ambos exclusivos desse cluster descartável. O administrativo existe apenas dentro do PostgreSQL temporário para inicialização; o runner PHPUnit recebe apenas o usuário de teste, que tem `CONNECT`, `USAGE` no schema e `SELECT`/`INSERT`/`UPDATE` na tabela. Nenhuma variável de conexão do banco normal é passada ao runner. Fora do Compose dedicado, a conexão de teste falha se as variáveis estiverem ausentes, se variáveis `DB_*` da aplicação estiverem presentes, se o host não for `database-test` ou se `current_database()` não corresponder ao banco configurado.

Execute o ambiente descartável e recolha o código de saída do PHPUnit:

```powershell
docker compose --env-file .env.integration -f compose.integration.yaml --profile integration up --build --abort-on-container-exit --exit-code-from integration-tests integration-tests
```

Depois remova os contêineres e a rede do ambiente dedicado:

```powershell
docker compose --env-file .env.integration -f compose.integration.yaml --profile integration down
```

O armazenamento PostgreSQL de integração é temporário; nenhum volume persistente ou conexão de produção é usado por essa configuração. Não passe uma configuração local diferente da documentação nem conecte testes a bancos externos.

Os testes unitários da política não requerem conexão PostgreSQL e podem ser executados isoladamente quando as dependências estiverem disponíveis:

```powershell
.\vendor\bin\phpunit tests/Unit/Infrastructure/Persistence/PdoTestDatabaseGuardTest.php
```

O runner dedicado executa a suíte PDO. Os testes usam UUIDs exclusivos, transação e rollback; não há limpeza geral de tabelas ou bancos. Sem o arquivo `.env.integration` ou sem o Compose/serviço de teste dedicado, as validações devem falhar antes de iniciar operações de persistência.

O ambiente, a migration no container descartável e as suítes PHPUnit ainda não foram executados; não há resultados de runtime.

## Instalação e execução

Requisitos: Docker Desktop com o daemon em execução e Docker Compose.

No PowerShell, na raiz do repositório, crie o arquivo local de ambiente caso ele ainda não exista:

```powershell
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
} else {
    Write-Error ".env já existe; preserve-o e revise-o manualmente."
}
```

O `.dockerignore` impede que arquivos locais como `.env`, dependências, caches, logs e credenciais privadas sejam enviados no contexto de build. A exceção mantém `.env.example` disponível como documentação.

O valor de `POSTGRES_PASSWORD` em `.env.example` é um placeholder explícito e não deve ser usado. Gere uma senha aleatória e grave-a diretamente no `.env` local. O exemplo abaixo não imprime a senha:

```powershell
if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
}

$envLines = @(Get-Content .env)
foreach ($name in @('POSTGRES_PASSWORD')) {
    $bytes = [byte[]]::new(32)
    $generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
    try {
        $generator.GetBytes($bytes)
    } finally {
        $generator.Dispose()
    }

    $password = [Convert]::ToBase64String($bytes).TrimEnd('=').Replace('+', '-').Replace('/', '_')
    $found = $false
    $envLines = foreach ($line in $envLines) {
        if ($line -match "^$name=") {
            $found = $true
            "$name=$password"
        } else {
            $line
        }
    }
    if (-not $found) {
        $envLines += "$name=$password"
    }
    Remove-Variable password
    [Array]::Clear($bytes, 0, $bytes.Length)
}
Set-Content -Path .env -Value $envLines -Encoding ascii
```

Mantenha `.env` somente na máquina local. Não compartilhe nem versione esse arquivo.

Valide a configuração e construa a imagem PHP:

```powershell
docker compose config --quiet
docker compose build
```

Inicie os serviços e confira seu estado:

```powershell
docker compose up -d
docker compose ps
```

Verifique se o PHP carregou a extensão PDO PostgreSQL:

```powershell
docker compose exec app php -r 'echo extension_loaded("pdo_pgsql") ? "pdo_pgsql: loaded" : "pdo_pgsql: missing", PHP_EOL;'
```

Teste o endpoint:

```powershell
Invoke-RestMethod -Uri http://localhost:8083/health -Method Get
```

O resultado esperado é HTTP 200 com `status` igual a `ok` e `service` igual a `coffee-lab-api`.

Encerre os contêineres sem remover o volume persistente do PostgreSQL:

```powershell
docker compose down
```

## Testes

O PHPUnit está incluído como dependência de desenvolvimento e configurado em `phpunit.xml`. Quando as dependências estiverem disponíveis, execute os testes unitários:

```powershell
.\vendor\bin\phpunit --testsuite unit
```

Os testes de integração fazem requisições HTTP independentes aos endpoints. Com os serviços Docker iniciados, execute:

```powershell
.\vendor\bin\phpunit --testsuite integration
```

Por padrão, a integração usa `http://127.0.0.1:8083`; configure `COFFEE_LAB_API_URL` se a API estiver em outro endereço. Os testes verificam respostas individuais e não dependem de um fluxo POST seguido de GET. Os testes foram criados, mas ainda não foram executados.

## Validações pendentes

Build das imagens, estado dos serviços, migration, conexão PDO, carregamento de `pdo_pgsql` no contêiner e testes HTTP/PDO dependem do Docker Desktop e PostgreSQL ativos. Não foram validados nesta etapa.

## Documentação

A documentação técnica será adicionada durante o desenvolvimento.
