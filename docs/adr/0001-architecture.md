# ADR 0001: Arquitetura da Coffee Lab API

- **Status:** Aceito

## Contexto

O Projeto B requer uma API pequena, centrada em um domínio principal, com separação suficiente para manter as regras de negócio testáveis sem impor a complexidade de serviços distribuídos. A aplicação cadastra e consulta cafés, valida identificadores UUID e níveis de torra, e persiste os dados no PostgreSQL no fluxo HTTP normal.

Esta decisão registra a arquitetura observada no código e as razões adotadas nesta documentação. As alternativas abaixo são alternativas avaliadas para este ADR; não há registro de que tenham sido discutidas anteriormente.

## Decisão

Manter uma única aplicação/API de domínio único, organizada em camadas com responsabilidades distintas:

- **Domínio (`src/Domain/Coffee/`):** `Coffee`, `CoffeeId` e `RoastLevel` representam os conceitos e invariantes do domínio sem depender de HTTP ou PDO.
- **Aplicação (`src/Application/Coffee/`):** `CreateCoffee`, `GetCoffee` e `ListCoffees` coordenam os casos de uso contra o contrato `CoffeeRepository`.
- **HTTP (`src/Controller/`, `src/Http/` e `public/index.php`):** o ponto de entrada faz o roteamento e a composição; os controladores interpretam as requisições e constroem respostas JSON.
- **Persistência (`src/Infrastructure/Persistence/`):** `PdoCoffeeRepository` implementa o contrato do repositório para o fluxo normal; `PdoConnectionFactory` configura a conexão. `InMemoryCoffeeRepository` mantém uma implementação simples para testes unitários sem serviço externo.

O PostgreSQL é a persistência do ambiente normal porque a aplicação já usa esse banco no Compose e a implementação PDO oferece armazenamento relacional durável para os registros. A migration versionada define a tabela e as restrições de persistência.

Os testes unitários usam `InMemoryCoffeeRepository` para exercitar casos de uso e controladores sem depender de um banco ou de estado externo. Os testes de integração da persistência usam `PdoCoffeeRepository` e um PostgreSQL descartável configurado separadamente.

## Alternativas avaliadas

1. **Dividir o domínio em microsserviços:** não adotada. O escopo observado tem um único domínio e não há necessidade comprovada de implantação, escala ou autonomia de equipes que justifique a complexidade distribuída.
2. **Acessar PDO diretamente dos controladores ou casos de uso:** não adotada. Isso acoplaria regras de aplicação e transporte a detalhes do banco e dificultaria testar os casos de uso isoladamente.
3. **Usar somente armazenamento em memória:** não adotada para a aplicação normal, pois os registros não sobreviveriam ao ciclo da requisição/processo e não atenderiam à persistência esperada.
4. **Usar um banco diferente no ambiente normal:** não adotada nesta implementação; PostgreSQL já é a tecnologia configurada e utilizada pelo projeto.

## Consequências

### Positivas

- As regras do domínio e os casos de uso não dependem diretamente do PostgreSQL.
- O contrato `CoffeeRepository` permite substituir a persistência em testes unitários.
- A API mantém um único processo de implantação e evita complexidade operacional de microsserviços.
- O uso de PostgreSQL fornece persistência compartilhada e restrições relacionais para os cafés.
- A rota `GET /health` pode responder sem abrir conexão com o banco.

### Negativas

- Há duas implementações de repositório que precisam permanecer coerentes.
- Testes de integração dependem de PHP com `pdo_pgsql`, Docker e PostgreSQL, além do bootstrap correto do ambiente descartável.
- A migration precisa ser aplicada ao banco antes que as rotas de cafés possam operar.
- A separação em camadas introduz mais classes e arquivos do que uma implementação mínima concentrada em um único script.

## Validação

Esta decisão descreve a estrutura e a configuração presentes no código. A documentação não constitui evidência de execução: o build de containers, a migration e os testes com PostgreSQL precisam ser executados no ambiente apropriado antes de afirmar validação runtime.
