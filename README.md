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

O ambiente Docker e o endpoint `GET /health` estão configurados. Cadastro, consulta e exclusão de cafés ainda não foram implementados.

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

$bytes = [byte[]]::new(32)
$generator = [System.Security.Cryptography.RandomNumberGenerator]::Create()
try {
    $generator.GetBytes($bytes)
} finally {
    $generator.Dispose()
}

$password = [Convert]::ToBase64String($bytes).TrimEnd('=').Replace('+', '-').Replace('/', '_')
$envLines = @(Get-Content .env)
$passwordFound = $false
$envLines = foreach ($line in $envLines) {
    if ($line -match '^POSTGRES_PASSWORD=') {
        $passwordFound = $true
        "POSTGRES_PASSWORD=$password"
    } else {
        $line
    }
}
if (-not $passwordFound) {
    $envLines += "POSTGRES_PASSWORD=$password"
}
Set-Content -Path .env -Value $envLines -Encoding ascii
Remove-Variable password
[Array]::Clear($bytes, 0, $bytes.Length)
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

O teste de integração faz uma requisição HTTP real ao endpoint. Com os serviços Docker iniciados, execute:

```powershell
.\vendor\bin\phpunit --testsuite integration
```

Por padrão, a integração usa `http://127.0.0.1:8083`; configure `COFFEE_LAB_API_URL` se a API estiver em outro endereço. Os testes foram criados, mas ainda não foram executados.

## Validações pendentes

Build das imagens, estado dos serviços, carregamento de `pdo_pgsql` no contêiner e teste HTTP via Nginx/PHP-FPM dependem do Docker Desktop ativo. Não foram validados nesta etapa.

## Documentação

A documentação técnica será adicionada durante o desenvolvimento.
