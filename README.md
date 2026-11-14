# Coffee Lab API

## Sobre o projeto

API REST para cadastro e consulta de cafés especiais, com persistência PostgreSQL e endpoint de saúde.

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

O ambiente Docker e a API REST foram validados localmente com PostgreSQL 17. A migration inicial foi aplicada ao banco local. Os testes de desempenho foram executados localmente, não em produção. As duas execuções do GitHub Actions registradas (#1 e #2) foram aprovadas.

- **Testes unitários:** 40 aprovados, 86 asserções.
- **Integração PDO:** 4 aprovados, 13 asserções, em PostgreSQL descartável e isolado.
- **HTTP e observabilidade:** endpoints REST e persistência PostgreSQL validados; logs JSON e cabeçalho `X-Request-Id` verificados.
- **Smoke k6 local:** 5.832 requisições, p95 de 21,38 ms.
- **SLO k6 local:** 20 VUs por 5 minutos, 90.866 requisições, p95 de 121,81 ms e 100% de sucesso. Consulte [docs/desempenho.md](./docs/desempenho.md) para os dados e limitações.
- **Cobertura unitária no CI:** 96,70% (176 de 182 linhas); o limite mínimo configurado é 70%. A medição corresponde ao escopo unitário configurado no CI, não a toda a aplicação.
- **GitHub Actions:** CI #1 e CI #2 aprovados; os jobs PHP/unit e OpenAPI foram aprovados nas duas execuções.

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

Os testes PDO são deliberadamente separados do Compose normal. A configuração [compose.integration.yaml](./compose.integration.yaml) declara somente um PostgreSQL descartável e um runner PHPUnit: não inicia a API nem o banco normal, não publica portas e usa uma rede Docker interna isolada, sem rota para os demais serviços. O banco usa `tmpfs`, recebe a migration e cria o usuário restrito apenas no bootstrap; seus dados desaparecem ao remover o contêiner. A suíte local foi executada: 4 testes e 13 asserções aprovados.

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

O resultado informado acima é da execução local confirmada; execute novamente no seu ambiente ao modificar o código ou a configuração.

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

## Observabilidade HTTP

Cada requisição processada pela aplicação gera uma linha JSON pelo mecanismo `error_log` do PHP, encaminhado ao log do contêiner `app`. O registro contém:

- `timestamp`: horário UTC em formato ISO 8601.
- `level`: `info` para respostas abaixo de 400, `warning` para 4xx e `error` para 5xx.
- `method`: método HTTP normalizado.
- `route`: rota normalizada (`/health`, `/coffees`, `/coffees/{id}` ou `unmatched`); IDs, query strings e caminhos não reconhecidos não são registrados.
- `status`: código HTTP final.
- `duration_ms`: duração do processamento no servidor em milissegundos, arredondada a três casas.
- `correlation_id`: valor seguro recebido em `X-Request-Id` ou um identificador aleatório gerado; o mesmo valor é devolvido no cabeçalho `X-Request-Id`.
- `error_type`: classe da exceção, somente quando ocorre uma resposta 500. Mensagens de exceção não são registradas.

Corpos de requisição, credenciais e valores de query string não são incluídos nos logs. Para acompanhar os registros:

```powershell
docker compose logs -f app
```

O `X-Request-Id` só aceita UUID v4; valores inválidos ou com caracteres de controle são substituídos por um UUID gerado pelo servidor. Falhas ao serializar ou escrever um registro não alteram a resposta HTTP nem propagam uma exceção para a requisição.

### Métrica e SLO proposto

`duration_ms` é a métrica de duração por requisição. Ela é medida com `hrtime()` ao redor do roteamento, do acesso ao repositório e da geração da resposta; portanto inclui o trabalho PHP/DB síncrono, mas não representa a latência vista pelo cliente nem inclui toda a transferência pela rede. As linhas JSON permitem análise posterior, mas não há agregador ou armazenamento de séries temporais configurado.

**SLO inicial proposto:** em uma execução de cinco minutos com 20 usuários virtuais, pelo menos 95% das requisições HTTP devem completar em menos de 500 ms. No teste local documentado em [docs/desempenho.md](./docs/desempenho.md), o p95 foi 121,81 ms e o threshold foi aprovado; isso não comprova desempenho em produção. O teste k6 mede latência do cliente (incluindo rede), diferente de `duration_ms` nos logs.

## Testes

O PHPUnit está incluído como dependência de desenvolvimento e configurado em `phpunit.xml`. Execute a suíte unitária com um alvo posicional explícito, compatível com PHPUnit 11:

```powershell
.\vendor\bin\phpunit --testsuite unit tests/Unit
```

Os testes de integração HTTP fazem requisições independentes aos endpoints. Com os serviços Docker iniciados, execute:

```powershell
.\vendor\bin\phpunit --testsuite integration
```

Por padrão, a integração usa `http://127.0.0.1:8083`; configure `COFFEE_LAB_API_URL` se a API estiver em outro endereço. Os testes verificam respostas individuais e não dependem de um fluxo POST seguido de GET.

## Integração contínua

O workflow [.github/workflows/ci.yml](./.github/workflows/ci.yml) é executado em pull requests direcionados a `main` e em pushes para `main`. Ele instala PHP 8.4 e as extensões exigidas, executa `composer install` usando o `composer.lock`, verifica a sintaxe PHP, roda somente a suíte unitária do PHPUnit, gera um relatório Clover e valida o contrato OpenAPI 3.1 com Redocly CLI.

O job de testes falha se a cobertura de linhas medida no escopo unitário configurado em `phpunit.xml` ficar abaixo de 70%. Nas execuções CI #1 e #2, a cobertura foi de 96,70% (176 de 182 linhas) nesse escopo; esse resultado não representa cobertura de toda a aplicação. O repositório PDO e a fábrica de conexão não entram no denominador porque sua validação depende do PostgreSQL. O relatório Clover é publicado como artefato do workflow por 14 dias.

Para investigar falhas, abra a execução na aba **Actions** do GitHub ou os checks do pull request e expanda o primeiro passo com falha. O artefato `phpunit-coverage`, quando gerado, contém o relatório de cobertura. O pipeline não recebe credenciais e não executa testes de integração HTTP ou PostgreSQL.

Migration, conexão PDO e testes de integração do repositório continuam dependentes do ambiente PostgreSQL descartável documentado acima; eles não são cobertos por este workflow.

## Arquitetura e documentação

A aplicação é uma API de domínio único, organizada em camadas:

```text
src/
├── Domain/Coffee/                 # Entidade, identificador e nível de torra
├── Application/Coffee/            # Casos de uso e contrato CoffeeRepository
├── Controller/                    # Adaptadores HTTP dos endpoints
├── Http/                           # Respostas JSON e logs HTTP
└── Infrastructure/Persistence/    # Repositórios PDO e em memória
public/index.php                    # Ponto de entrada, rotas e composição
```

- [Contrato OpenAPI 3.1](./docs/openapi.yaml)
- [ADR 0001 — Arquitetura da aplicação](./docs/adr/0001-architecture.md)
- [Código da camada de domínio](./src/Domain/)
- [Código dos casos de uso](./src/Application/)
- [Controladores e resposta HTTP](./src/Controller/)
- [Implementações de persistência](./src/Infrastructure/Persistence/)

Consulte [Estado](#estado) para os resultados verificados e suas limitações. A cobertura informada corresponde somente ao escopo unitário medido no CI; os resultados de desempenho são locais e não representam produção.
